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
 * The readiness decision (docs/READINESS_SPEC.md section 3). Pure function of its inputs.
 *
 * @package    report_afirst_readiness
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rules {

    /** @var string */
    public const NOT_STARTED = 'not_started';
    /** @var string */
    public const IN_PROGRESS = 'in_progress';
    /** @var string */
    public const INDEPENDENT = 'independent';
    /** @var string */
    public const SUPERVISED = 'supervised';
    /** @var string */
    public const NOT_YET = 'not_yet';

    /** @var array<string, int> Defaults used when a course's settings omit a threshold. */
    public const DEFAULT_THRESHOLDS = [
        'independent_domain_min' => 80,
        'supervised_domain_min' => 60,
        'knowledge_each_min' => 80,
        'knowledge_mean_min_supervised' => 60,
        'supervisor_min' => 70,
    ];

    /**
     * Classify one learner.
     *
     * @param array $in started (bool), completed (bool), critical (bool),
     *     domains (id => percent|null), knowledge (label => percent|null; empty when none configured),
     *     supervisorconfigured (bool), supervisor (percent|null).
     * @param array $thresholds Overrides of DEFAULT_THRESHOLDS.
     * @return array{status: string, reasons: array[]} Reasons are [string id, a] pairs for get_string('reason:<id>').
     */
    public static function classify(array $in, array $thresholds = []): array {
        $t = $thresholds + self::DEFAULT_THRESHOLDS;
        if (empty($in['started'])) {
            return ['status' => self::NOT_STARTED, 'reasons' => []];
        }
        if (empty($in['completed'])) {
            return ['status' => self::IN_PROGRESS, 'reasons' => []];
        }

        // Independent: every criterion at the higher bar.
        $gaps = [];
        foreach ($in['domains'] as $domain => $value) {
            if ($value === null) {
                $gaps[] = ['domainmissing', $domain];
            } else if ($value < $t['independent_domain_min']) {
                $gaps[] = ['domain', ['domain' => $domain, 'value' => $value, 'min' => $t['independent_domain_min']]];
            }
        }
        if (!empty($in['critical'])) {
            $gaps[] = ['critical', null];
        }
        foreach ($in['knowledge'] ?? [] as $label => $value) {
            if ($value === null) {
                $gaps[] = ['knowledgemissing', (string)$label];
            } else if ($value < $t['knowledge_each_min']) {
                $gaps[] = ['knowledge', ['label' => (string)$label, 'value' => $value, 'min' => $t['knowledge_each_min']]];
            }
        }
        if (!empty($in['supervisorconfigured'])) {
            if ($in['supervisor'] === null) {
                $gaps[] = ['supervisormissing', null];
            } else if ($in['supervisor'] < $t['supervisor_min']) {
                $gaps[] = ['supervisor', ['value' => $in['supervisor'], 'min' => $t['supervisor_min']]];
            }
        }
        if (!$gaps) {
            return ['status' => self::INDEPENDENT, 'reasons' => []];
        }

        // Supervised: every domain at the lower bar and an adequate knowledge mean. A critical choice does not
        // block supervised work, because a supervisor is there to catch it.
        $low = [];
        foreach ($in['domains'] as $domain => $value) {
            if ($value === null) {
                $low[] = ['domainmissing', $domain];
            } else if ($value < $t['supervised_domain_min']) {
                $low[] = ['domain', ['domain' => $domain, 'value' => $value, 'min' => $t['supervised_domain_min']]];
            }
        }
        if (!empty($in['knowledge'])) {
            $values = array_map(fn($v) => $v ?? 0.0, $in['knowledge']);
            $mean = round(array_sum($values) / count($values), 1);
            $missing = count(array_filter($in['knowledge'], fn($v) => $v === null));
            if ($mean < $t['knowledge_mean_min_supervised']) {
                // Say when checks not taken pulled the mean down, so the teacher knows what to ask for.
                $low[] = [$missing ? 'knowledgemeanmissing' : 'knowledgemean',
                    ['value' => $mean, 'min' => $t['knowledge_mean_min_supervised'], 'missing' => $missing]];
            }
        }
        if (!$low) {
            return ['status' => self::SUPERVISED, 'reasons' => $gaps];
        }
        // A critical choice is always reported, even when low scores already decide the result.
        if (!empty($in['critical'])) {
            array_unshift($low, ['critical', null]);
        }
        return ['status' => self::NOT_YET, 'reasons' => $low];
    }
}
