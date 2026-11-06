<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_afirst_assistant\local;

use core_text;
use ZipArchive;

/**
 * Pulls the text a learner sees out of a SCORM package, so it can become an assistant source.
 *
 * Articulate Rise 360 exports keep the whole course as base64-encoded JSON passed to
 * deserialize("...") in scormcontent/index.html (older exports: __resolveJsonp(..., "...") in
 * scormcontent/locales/*.js). Blocks are walked in lesson order and only their text fields are kept;
 * media, styling, settings and the SCORM driver are ignored. Other packages fall back to the
 * visible text of the HTML files their imsmanifest.xml lists.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scorm_text_extractor {

    /** @var int Stop collecting text beyond this many bytes. */
    public const MAX_TEXT_BYTES = 2 * 1024 * 1024;

    /** @var int Package entries larger than this (uncompressed) are never read. */
    public const MAX_ENTRY_BYTES = 20 * 1024 * 1024;

    /** @var string[] Fields of a Rise block item that hold learner-visible text, in display order. */
    private const RISE_TEXT_FIELDS = [
        'date', 'heading', 'title', 'front', 'back', 'description', 'paragraph', 'caption', 'code',
        'answers', 'feedback', 'feedbackCorrect', 'feedbackIncorrect', 'slides', 'responses', 'items',
    ];

    /** @var string Rise placeholder for an untouched flashcard side. */
    private const RISE_PLACEHOLDER = '/^(Front|Back) of card \d+$/i';

    /** @var int Bytes collected so far by the current extraction. */
    private int $size = 0;

    /** @var string[] Text blocks collected so far, in course order. */
    private array $blocks = [];

    /**
     * Learner-visible text of a SCORM zip package.
     *
     * @param string $zippath Path of the package on disk.
     * @return string Plain text, blocks separated by blank lines; empty when nothing readable was found.
     */
    public static function from_zip(string $zippath): string {
        $zip = new ZipArchive();
        if ($zip->open($zippath, ZipArchive::RDONLY) !== true) {
            throw new \moodle_exception('error:nofile', 'block_afirst_assistant');
        }
        try {
            $extractor = new self();
            $course = $extractor->find_rise_course($zip);
            if ($course !== null) {
                $extractor->add_rise_course($course);
            } else {
                $extractor->add_manifest_html($zip);
            }
            return $extractor->result();
        } finally {
            $zip->close();
        }
    }

    /**
     * Learner-visible text of the package of a SCORM course module.
     *
     * @param \cm_info|\stdClass $cm Course module of a mod_scorm instance.
     * @return string Plain text; empty when the module has no package file.
     */
    public static function from_cm(\cm_info|\stdClass $cm): string {
        $context = \context_module::instance($cm->id);
        $files = get_file_storage()->get_area_files($context->id, 'mod_scorm', 'package', 0, 'sortorder DESC, id ASC', false);
        $file = reset($files);
        if (!$file) {
            return '';
        }
        $path = $file->copy_content_to_temp('block_afirst_assistant', 'scorm_');
        if ($path === false) {
            throw new \moodle_exception('error:nofile', 'block_afirst_assistant');
        }
        try {
            return self::from_zip($path);
        } finally {
            @unlink($path);
        }
    }

    /**
     * Turn an HTML fragment into plain text: one line per paragraph, list item or table row.
     *
     * @param string $html
     * @return string
     */
    public static function html_to_plain(string $html): string {
        if (!mb_check_encoding($html, 'UTF-8')) {
            // Older authoring tools save pages in Windows-1252.
            $html = core_text::convert($html, 'windows-1252', 'utf-8');
        }
        $html = preg_replace('~<(script|style|noscript|template|svg|head)\b.*?</\1\s*>~is', ' ', $html);
        $html = preg_replace('~<!--.*?-->~s', ' ', $html);
        // Keep each table row on one line: paragraphs inside a cell become spaces.
        $html = preg_replace_callback('~(<t[dh]\b[^>]*>)(.*?)(</t[dh]\s*>)~is',
            fn($m) => $m[1] . preg_replace('~<(br|/?p|/?div|/?li|/?ul|/?ol)\b[^>]*>~i', ' ', $m[2]) . $m[3], $html);
        $html = preg_replace('~<(br|hr)\b[^>]*>~i', "\n", $html);
        $html = preg_replace('~</(t[dh])\s*>~i', ' | ', $html);
        $html = preg_replace('~</?(p|div|li|ul|ol|tr|table|h[1-6]|blockquote|pre|section|article|header|footer|dt|dd)\b[^>]*>~i',
            "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $lines = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim(preg_replace('/[ \t]+/u', ' ', $line), " \t|");
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        return implode("\n", $lines);
    }

    /**
     * The decoded Rise course data, when the package is a Rise export.
     *
     * @param ZipArchive $zip
     * @return array|null The "course" object.
     */
    private function find_rise_course(ZipArchive $zip): ?array {
        // Only the call is matched; the (possibly megabytes long) base64 argument is measured with strspn,
        // which a regex quantifier could not do within PCRE's limits.
        $patterns = [
            '~\bdeserialize\(\s*["\']~',
            '~__resolveJsonp\(\s*["\'][^"\']*["\']\s*,\s*["\']~',
        ];
        $candidates = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            if (preg_match('~^(.*/)?scormcontent/(index\.html|locales/[^/]+\.js)$~i', $name)) {
                $candidates[] = $name;
            }
        }
        foreach ($candidates as $name) {
            $content = $this->read_entry($zip, $name);
            if ($content === null) {
                continue;
            }
            foreach ($patterns as $pattern) {
                if (!preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
                    continue;
                }
                $start = $m[0][1] + strlen($m[0][0]);
                $length = strspn($content, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=', $start);
                $data = json_decode((string)base64_decode(substr($content, $start, $length), true), true);
                if (is_array($data['course']['lessons'] ?? null)) {
                    return $data['course'];
                }
            }
        }
        return null;
    }

    /**
     * Collect the text of a Rise course: course title and description, then each lesson's title and blocks.
     *
     * @param array $course
     */
    private function add_rise_course(array $course): void {
        $this->add_html($course['title'] ?? '');
        $this->add_html($course['description'] ?? '');
        $mondrian = $course['mondrian'] ?? [];
        foreach ($course['lessons'] as $lesson) {
            if (!is_array($lesson) || !empty($lesson['deleted'])) {
                continue;
            }
            $this->add_html($lesson['title'] ?? '');
            $this->add_html($lesson['description'] ?? '');
            foreach ($lesson['items'] ?? [] as $block) {
                if (is_array($block)) {
                    $this->add_rise_block($block, $mondrian);
                }
            }
        }
    }

    /**
     * Collect the text of one Rise block.
     *
     * @param array $block
     * @param array $mondrian Course-level store of custom ("mondrian") block content.
     */
    private function add_rise_block(array $block, array $mondrian): void {
        $type = $block['type'] ?? '';
        if ($type === 'divider') {
            // Continue buttons and numbered dividers only carry UI labels.
            return;
        }
        if ($type === 'custom' && isset($block['blockumentId'])) {
            $this->add_text($this->mondrian_text($mondrian, (string)$block['blockumentId']));
            return;
        }
        $lines = [];
        if ($type === 'chart') {
            $lines[] = self::html_to_plain((string)($block['title'] ?? ''));
            foreach ($block['items'] ?? [] as $item) {
                if (is_array($item) && isset($item['type'], $item['value']) && is_scalar($item['type'])) {
                    $lines[] = trim(self::html_to_plain((string)$item['type']), ' :') . ': ' . $item['value'];
                }
            }
        } else {
            foreach ($block['items'] ?? [] as $item) {
                $this->collect_rise_fields($item, $lines);
            }
        }
        $this->add_text(implode("\n", array_filter($lines, fn($l) => $l !== '')));
    }

    /**
     * Gather the text fields of a block item and its nested items (answers, scenario slides, flashcard sides).
     *
     * @param mixed $node
     * @param string[] $lines Collected lines, appended to.
     */
    private function collect_rise_fields($node, array &$lines): void {
        if (!is_array($node)) {
            return;
        }
        if (array_is_list($node)) {
            foreach ($node as $child) {
                $this->collect_rise_fields($child, $lines);
            }
            return;
        }
        foreach (self::RISE_TEXT_FIELDS as $field) {
            $value = $node[$field] ?? null;
            if (is_array($value)) {
                $this->collect_rise_fields($value, $lines);
            } else if (is_string($value) && $value !== '') {
                if ($field === 'code') {
                    $text = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                } else {
                    $text = self::html_to_plain($value);
                }
                if ($field === 'title' && isset($node['matchTitle']) && is_string($node['matchTitle'])) {
                    // Matching question: show each prompt next to its match.
                    $text .= ': ' . self::html_to_plain($node['matchTitle']);
                }
                if ($text !== '' && !preg_match(self::RISE_PLACEHOLDER, $text)) {
                    $lines[] = $text;
                }
            }
        }
    }

    /**
     * Text of a Rise custom block, read from its canvas items in reading order.
     *
     * @param array $mondrian
     * @param string $blockumentid
     * @return string
     */
    private function mondrian_text(array $mondrian, string $blockumentid): string {
        $blockument = $mondrian['blockuments'][$blockumentid] ?? null;
        if (!is_array($blockument)) {
            return '';
        }
        $lines = [];
        $this->collect_mondrian($mondrian['items'] ?? [], $blockument['children'] ?? [], $lines, 0);
        return implode("\n", $lines);
    }

    /**
     * Walk canvas items column by column, top to bottom, collecting their text.
     *
     * @param array $items All canvas items by id.
     * @param array $children Child references ({id: ...}) of the current group.
     * @param string[] $lines Collected lines, appended to.
     * @param int $depth Recursion guard.
     */
    private function collect_mondrian(array $items, array $children, array &$lines, int $depth): void {
        if ($depth > 20) {
            return;
        }
        $nodes = [];
        foreach ($children as $ref) {
            $item = $items[$ref['id'] ?? ''] ?? null;
            if (!is_array($item) || !empty($item['removed']) || (isset($item['initialVisible']) && !$item['initialVisible'])) {
                continue;
            }
            $state = $item['states']['default'] ?? [];
            $nodes[] = [
                'item' => $item,
                'x' => (float)($state['x'] ?? 0),
                'y' => (float)($state['y'] ?? 0),
                'w' => (float)($state['width'] ?? 0),
            ];
        }
        // Items whose horizontal extents overlap form a column; columns are read left to right.
        usort($nodes, fn($a, $b) => $a['x'] <=> $b['x']);
        $columns = [];
        foreach ($nodes as $node) {
            $placed = false;
            foreach ($columns as &$column) {
                if ($node['x'] < $column['right'] && $node['x'] + $node['w'] > $column['left']) {
                    $column['nodes'][] = $node;
                    $column['right'] = max($column['right'], $node['x'] + $node['w']);
                    $placed = true;
                    break;
                }
            }
            unset($column);
            if (!$placed) {
                $columns[] = ['left' => $node['x'], 'right' => $node['x'] + max($node['w'], 1), 'nodes' => [$node]];
            }
        }
        foreach ($columns as $column) {
            usort($column['nodes'], fn($a, $b) => [$a['y'], $a['x']] <=> [$b['y'], $b['x']]);
            foreach ($column['nodes'] as $node) {
                $doc = $node['item']['states']['default']['text']['json'] ?? null;
                if (is_array($doc)) {
                    $text = trim(preg_replace("/\n{2,}/", "\n", $this->tiptap_text($doc)));
                    if ($text !== '') {
                        $lines[] = $text;
                    }
                }
                if (!empty($node['item']['children'])) {
                    $this->collect_mondrian($items, $node['item']['children'], $lines, $depth + 1);
                }
            }
        }
    }

    /**
     * Plain text of a TipTap (ProseMirror) document node.
     *
     * @param array $node
     * @return string
     */
    private function tiptap_text(array $node): string {
        if (($node['type'] ?? '') === 'text') {
            return (string)($node['text'] ?? '');
        }
        if (($node['type'] ?? '') === 'hardBreak') {
            return "\n";
        }
        $text = '';
        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                $text .= $this->tiptap_text($child);
            }
        }
        if (in_array($node['type'] ?? '', ['paragraph', 'heading', 'listItem', 'blockquote', 'codeBlock'], true)) {
            $text .= "\n";
        }
        return $text;
    }

    /**
     * Collect the visible text of the HTML files of a generic package, in manifest order.
     *
     * @param ZipArchive $zip
     */
    private function add_manifest_html(ZipArchive $zip): void {
        $manifest = $this->read_entry($zip, 'imsmanifest.xml');
        if ($manifest === null) {
            return;
        }
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($manifest, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            return;
        }
        $resources = [];
        foreach ($dom->getElementsByTagNameNS('*', 'resource') as $resource) {
            $base = $resource->getAttribute('xml:base');
            $hrefs = [];
            if ($resource->getAttribute('href') !== '') {
                $hrefs[] = $base . $resource->getAttribute('href');
            }
            foreach ($resource->getElementsByTagNameNS('*', 'file') as $file) {
                $hrefs[] = $base . $file->getAttribute('href');
            }
            $resources[$resource->getAttribute('identifier')] = $hrefs;
        }
        // Resources launched by the organisation's items come first, in item order.
        $ordered = [];
        foreach ($dom->getElementsByTagNameNS('*', 'item') as $item) {
            $ref = $item->getAttribute('identifierref');
            if ($ref !== '' && isset($resources[$ref])) {
                $ordered[$ref] = $resources[$ref];
            }
        }
        $ordered += $resources;

        $seen = [];
        foreach ($ordered as $hrefs) {
            foreach ($hrefs as $href) {
                $path = self::normalise_path($href);
                if ($path === null || isset($seen[$path]) || !preg_match('/\.x?html?$/i', $path)) {
                    continue;
                }
                $seen[$path] = true;
                $html = $this->read_entry($zip, $path);
                if ($html !== null) {
                    $this->add_html_page($html);
                }
            }
        }
    }

    /**
     * Add a whole HTML page: its title and the text of its body.
     *
     * @param string $html
     */
    private function add_html_page(string $html): void {
        if (preg_match('~<title\b[^>]*>(.*?)</title>~is', $html, $m)) {
            $this->add_html($m[1]);
        }
        if (preg_match('~<body\b[^>]*>(.*)</body>~is', $html, $m)) {
            $html = $m[1];
        }
        $this->add_html($html);
    }

    /**
     * Add an HTML fragment as one block.
     *
     * @param string $html
     */
    private function add_html(string $html): void {
        $this->add_text(self::html_to_plain($html));
    }

    /**
     * Add one block of plain text, unless the size cap has been reached.
     *
     * @param string $text
     */
    private function add_text(string $text): void {
        $text = trim($text);
        if ($text === '' || $this->size >= self::MAX_TEXT_BYTES) {
            return;
        }
        if ($this->size + strlen($text) > self::MAX_TEXT_BYTES) {
            $text = core_text::str_max_bytes($text, self::MAX_TEXT_BYTES - $this->size);
        }
        $this->blocks[] = $text;
        $this->size += strlen($text) + 2;
    }

    /**
     * The collected text.
     *
     * @return string
     */
    private function result(): string {
        // Consecutive identical blocks (e.g. a lesson title repeated as its first heading) add nothing.
        $blocks = [];
        foreach ($this->blocks as $block) {
            if (end($blocks) !== $block) {
                $blocks[] = $block;
            }
        }
        return implode("\n\n", $blocks);
    }

    /**
     * Read a package entry, refusing oversized ones.
     *
     * @param ZipArchive $zip
     * @param string $name
     * @return string|null
     */
    private function read_entry(ZipArchive $zip, string $name): ?string {
        $stat = $zip->statName($name);
        if ($stat === false || $stat['size'] > self::MAX_ENTRY_BYTES) {
            return null;
        }
        $content = $zip->getFromName($name, self::MAX_ENTRY_BYTES);
        return $content === false ? null : $content;
    }

    /**
     * Resolve a manifest href to a package entry name.
     *
     * @param string $href
     * @return string|null Null for external or escaping links.
     */
    private static function normalise_path(string $href): ?string {
        $href = rawurldecode(preg_replace('/[?#].*$/', '', $href));
        if ($href === '' || preg_match('~^[a-z][a-z0-9+.-]*:~i', $href)) {
            return null;
        }
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $href)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if (!$parts) {
                    return null;
                }
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }
        return $parts ? implode('/', $parts) : null;
    }
}
