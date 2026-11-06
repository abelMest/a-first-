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

use core\hook\output\before_footer_html_generation;
use core_ai\aiactions\generate_text;
use core_ai\manager;

/**
 * The floating "Ask the course" button and its chat panel, on every page of a course that has approved sources.
 *
 * No block has to be added: the panel appears for anyone who may use the assistant once a teacher has approved at
 * least one source. Staff who manage sources also see it before then, so they can find the source and review pages.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class launcher {

    /** @var string[] Page layouts that never get the panel (iframes, printing, maintenance, redirects, popups). */
    private const EXCLUDED_LAYOUTS = ['maintenance', 'print', 'redirect', 'embedded', 'popup', 'frametop', 'secure'];

    /**
     * Hook callback: add the launcher before the page footer.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;
        try {
            $html = self::render($PAGE);
        } catch (\Throwable $e) {
            // The assistant must never break a course page.
            debugging('Course assistant launcher failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return;
        }
        if ($html !== '') {
            $hook->add_html($html);
        }
    }

    /**
     * Whether the launcher should be on this page for the current user.
     *
     * @param \moodle_page $page
     * @return bool
     */
    public static function applies(\moodle_page $page): bool {
        if (during_initial_install() || !get_config('block_afirst_assistant', 'version')) {
            return false;
        }
        if (!get_config('block_afirst_assistant', 'launcher')) {
            return false;
        }
        if (in_array($page->pagelayout, self::EXCLUDED_LAYOUTS, true)) {
            return false;
        }
        $course = $page->course;
        if (empty($course->id) || $course->id == SITEID || !isloggedin() || isguestuser()) {
            return false;
        }
        $context = \context_course::instance($course->id);
        if (!has_capability('block/afirst_assistant:use', $context)) {
            return false;
        }
        if (!\core\di::get(manager::class)->is_action_available(generate_text::class)) {
            return false;
        }
        return retriever::has_sources($course->id) || has_capability('block/afirst_assistant:managesources', $context);
    }

    /**
     * The launcher markup, with its JavaScript queued; empty when it does not apply.
     *
     * @param \moodle_page $page
     * @return string
     */
    public static function render(\moodle_page $page): string {
        global $OUTPUT, $USER;
        if (!self::applies($page)) {
            return '';
        }
        $courseid = (int)$page->course->id;
        $context = \context_course::instance($courseid);
        $cmid = $page->cm ? (int)$page->cm->id : 0;
        $module = page_context::for_cm($courseid, $cmid);

        $suggestions = retriever::suggestions($courseid);
        $starters = $suggestions[$module['code'] ?? ''] ?? null;
        if ($starters === null) {
            // Not inside a module: one starter from each of the first modules, so learners see the range.
            $starters = array_slice(array_filter(array_map(fn(array $q) => $q[0] ?? null, $suggestions)), 0, 4);
        }

        $links = [];
        if (has_capability('block/afirst_assistant:managesources', $context)) {
            $links[] = ['url' => (new \moodle_url('/blocks/afirst_assistant/sources.php', ['courseid' => $courseid]))->out(false),
                'label' => get_string('managesources', 'block_afirst_assistant')];
        }
        if (has_capability('block/afirst_assistant:review', $context)) {
            $links[] = ['url' => (new \moodle_url('/blocks/afirst_assistant/report.php', ['courseid' => $courseid]))->out(false),
                'label' => get_string('reviewqueue', 'block_afirst_assistant')];
        }

        $uniqid = \html_writer::random_id('afirstlauncher');
        $page->requires->js_call_amd('block_afirst_assistant/assistant', 'initLauncher', [[
            'rootid' => $uniqid,
            'courseid' => $courseid,
            'userid' => (int)$USER->id,
            'policyaccepted' => manager::get_user_policy_status($USER->id),
            'cmid' => $cmid,
        ]]);
        return $OUTPUT->render_from_template('block_afirst_assistant/launcher', [
            'uniqid' => $uniqid,
            'modulename' => $module['name'],
            'starters' => array_map(fn(string $q) => ['question' => $q], array_values($starters)),
            'hasstarters' => !empty($starters),
            'nosources' => !retriever::has_sources($courseid),
            'links' => $links,
            'haslinks' => !empty($links),
            'disclaimer' => get_string('disclaimer', 'block_afirst_assistant'),
        ]);
    }
}
