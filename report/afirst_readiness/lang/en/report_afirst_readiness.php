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

/**
 * Strings.
 *
 * @package    report_afirst_readiness
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['afirst_readiness:view'] = 'View learner readiness results';
$string['critical'] = 'Critical choice';
$string['disclaimer'] = 'Readiness results combine knowledge checks, the scenario and any supervisor rating using the thresholds below. They support, and do not replace, a supervisor\'s judgement. Scenario status: {$a}.';
$string['download'] = 'Download (pseudonymised CSV)';
$string['knowledge'] = 'Knowledge checks';
$string['learner'] = 'Learner';
$string['noscenarios'] = 'No readiness scenario has been built for this course yet.';
$string['pluginname'] = 'Readiness';
$string['privacy:metadata'] = 'The readiness report stores no personal data. It reads Lesson attempts and grades, which their own components store.';
$string['reason:critical'] = 'made a critical choice';
$string['reason:domain'] = '{$a->domain} {$a->value}% (needs {$a->min}%)';
$string['reason:domainmissing'] = '{$a} not assessed on this path';
$string['reason:knowledge'] = 'knowledge check {$a->label} {$a->value}% (needs {$a->min}%)';
$string['reason:knowledgemean'] = 'knowledge mean {$a->value}% (needs {$a->min}%)';
$string['reason:knowledgemeanmissing'] = 'knowledge mean {$a->value}% (needs {$a->min}%; {$a->missing} not taken, counted as 0%)';
$string['reason:knowledgemissing'] = 'knowledge check {$a} not taken';
$string['reason:supervisor'] = 'supervisor rating {$a->value}% (needs {$a->min}%)';
$string['reason:supervisormissing'] = 'no supervisor rating yet';
$string['reasons'] = 'What is missing';
$string['scenario'] = 'Scenario';
$string['status'] = 'Readiness';
$string['status:in_progress'] = 'In progress';
$string['status:independent'] = 'Ready for independent work';
$string['status:not_started'] = 'Not started';
$string['status:not_yet'] = 'Not yet ready';
$string['status:supervised'] = 'Ready with supervision';
$string['summary'] = 'Summary';
$string['supervisor'] = 'Supervisor';
$string['thresholds'] = 'Thresholds: independent ≥ {$a->independent_domain_min}% in every domain, every knowledge check ≥ {$a->knowledge_each_min}%, no critical choice; supervised ≥ {$a->supervised_domain_min}% in every domain and knowledge mean ≥ {$a->knowledge_mean_min_supervised}%.';
