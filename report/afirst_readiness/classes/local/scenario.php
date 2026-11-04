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

namespace report_afirst_readiness\local;

/**
 * A readiness scenario file: validation and path scoring (docs/READINESS_SPEC.md sections 2 and 3).
 *
 * Pure PHP apart from string helpers, so it is fully unit-testable.
 *
 * @package    report_afirst_readiness
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scenario {

    /** @var string Next-node value that ends the scenario. */
    public const END = 'end';

    /** @var int Most options a decision point may have. */
    public const MAX_OPTIONS = 5;

    /** @var int Highest points an option may give in one domain. */
    public const MAX_POINTS = 3;

    /**
     * Check a decoded scenario.
     *
     * @param mixed $s Decoded JSON.
     * @param string[]|null $competencies Known competency ids (from the course taxonomy), or null to skip that check.
     * @return string[] Errors; empty when valid.
     */
    public static function validate($s, ?array $competencies = null): array {
        if (!is_array($s)) {
            return ['scenario is not a JSON object'];
        }
        $errors = [];
        foreach (['id', 'title', 'version', 'status', 'start'] as $key) {
            if (empty($s[$key]) || !is_string($s[$key])) {
                $errors[] = "$key must be a non-empty string";
            }
        }
        if (!empty($s['id']) && !preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,49}$/', (string)$s['id'])) {
            $errors[] = 'id may only contain letters, digits, - and _ (at most 50)';
        }
        if (!in_array($s['status'] ?? '', ['draft', 'approved', 'retired'], true)) {
            $errors[] = 'status must be draft, approved or retired';
        }

        $domains = [];
        foreach ($s['domains'] ?? [] as $i => $d) {
            if (empty($d['id']) || empty($d['title'])) {
                $errors[] = "domains[$i] needs id and title";
                continue;
            }
            if (isset($domains[$d['id']])) {
                $errors[] = "domain {$d['id']} is defined twice";
            }
            $domains[$d['id']] = true;
            foreach ($d['competencies'] ?? [] as $c) {
                if ($competencies !== null && !in_array($c, $competencies, true)) {
                    $errors[] = "domain {$d['id']} lists unknown competency $c";
                }
            }
        }
        if (!$domains) {
            $errors[] = 'at least one domain is required';
        }

        $nodes = [];
        foreach ($s['nodes'] ?? [] as $i => $n) {
            if (empty($n['id']) || !is_string($n['id'])) {
                $errors[] = "nodes[$i] needs an id";
                continue;
            }
            if ($n['id'] === self::END || isset($nodes[$n['id']])) {
                $errors[] = "node id {$n['id']} is reserved or duplicated";
                continue;
            }
            $nodes[$n['id']] = $n;
        }
        if (!$nodes) {
            $errors[] = 'at least one node is required';
            return $errors;
        }
        if (!isset($nodes[$s['start'] ?? ''])) {
            $errors[] = 'start does not name a node';
        }

        foreach ($nodes as $id => $n) {
            if (empty($n['title']) || empty($n['question'])) {
                $errors[] = "node $id needs title and question";
            }
            $options = $n['options'] ?? [];
            if (count($options) < 2 || count($options) > self::MAX_OPTIONS) {
                $errors[] = "node $id needs 2 to " . self::MAX_OPTIONS . ' options';
            }
            $optionids = [];
            foreach ($options as $j => $o) {
                $oid = $o['id'] ?? '';
                if ($oid === '' || isset($optionids[$oid])) {
                    $errors[] = "node $id option $j has a missing or duplicate id";
                }
                $optionids[$oid] = true;
                if (empty($o['text']) || empty($o['feedback'])) {
                    $errors[] = "node $id option $oid needs text and feedback";
                }
                $next = $o['next'] ?? '';
                if ($next !== self::END && !isset($nodes[$next])) {
                    $errors[] = "node $id option $oid: next '$next' is not a node or 'end'";
                }
                foreach ($o['scores'] ?? [] as $domain => $points) {
                    if (!isset($domains[$domain])) {
                        $errors[] = "node $id option $oid scores unknown domain $domain";
                    } else if (!is_int($points) || $points < 0 || $points > self::MAX_POINTS) {
                        $errors[] = "node $id option $oid: points for $domain must be an integer 0-" . self::MAX_POINTS;
                    }
                }
            }
        }
        if (!$errors) {
            $errors = array_merge($errors, self::check_paths($nodes, $s['start']));
        }
        return $errors;
    }

    /**
     * Every node must be reachable from the start, and every path must reach the end without a cycle.
     *
     * @param array $nodes Node id => node.
     * @param string $start
     * @return string[]
     */
    private static function check_paths(array $nodes, string $start): array {
        $errors = [];
        $state = []; // 1 = on the current path, 2 = done.
        $visit = function(string $id) use (&$visit, &$state, &$errors, $nodes): void {
            if (($state[$id] ?? 0) === 2) {
                return;
            }
            if (($state[$id] ?? 0) === 1) {
                $errors[] = "cycle through node $id";
                return;
            }
            $state[$id] = 1;
            foreach ($nodes[$id]['options'] as $o) {
                if ($o['next'] !== self::END) {
                    $visit($o['next']);
                }
            }
            $state[$id] = 2;
        };
        $visit($start);
        foreach (array_keys($nodes) as $id) {
            if (!isset($state[$id])) {
                $errors[] = "node $id cannot be reached from the start";
            }
        }
        return array_values(array_unique($errors));
    }

    /**
     * Score the decisions a learner made.
     *
     * @param array $s Valid scenario.
     * @param array $choices Ordered list of [node id, option id] as answered.
     * @return array{completed: bool, critical: bool, domains: array<string, float|null>, path: string[]}
     *     Domain percentages are null when the path offered no points in that domain.
     */
    public static function score(array $s, array $choices): array {
        $nodes = array_column($s['nodes'], null, 'id');
        $earned = $possible = array_fill_keys(array_column($s['domains'], 'id'), 0);
        $critical = false;
        $completed = false;
        $path = [];
        foreach ($choices as [$nodeid, $optionid]) {
            if (!isset($nodes[$nodeid])) {
                continue;
            }
            $options = array_column($nodes[$nodeid]['options'], null, 'id');
            if (!isset($options[$optionid])) {
                continue;
            }
            $path[] = $nodeid;
            foreach (array_keys($possible) as $domain) {
                $possible[$domain] += max(array_map(fn($o) => (int)($o['scores'][$domain] ?? 0), $options));
                $earned[$domain] += (int)($options[$optionid]['scores'][$domain] ?? 0);
            }
            $critical = $critical || !empty($options[$optionid]['critical']);
            $completed = $completed || $options[$optionid]['next'] === self::END;
        }
        $domains = [];
        foreach ($possible as $domain => $max) {
            $domains[$domain] = $max > 0 ? round(100 * $earned[$domain] / $max, 1) : null;
        }
        return ['completed' => $completed, 'critical' => $critical, 'domains' => $domains, 'path' => $path];
    }

    /**
     * Domain id => title.
     *
     * @param array $s
     * @return array<string, string>
     */
    public static function domain_titles(array $s): array {
        return array_column($s['domains'], 'title', 'id');
    }
}
