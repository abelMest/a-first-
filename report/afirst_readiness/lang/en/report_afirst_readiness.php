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
$string['afirst_readiness:viewown'] = 'See your own readiness result';
$string['messageprovider:result'] = 'Your result when you finish a readiness scenario';
$string['mine'] = 'My readiness';
$string['mine:bars'] = 'Ready for independent work needs {$a->independent}% in every area; ready with supervision needs {$a->supervised}%.';
$string['mine:continue'] = 'Continue the scenario';
$string['mine:critical'] = 'One of your choices would cause serious harm in real work, for example sharing data that identifies patients. This always limits the result, however well the rest went. Your supervisor will go through it with you.';
$string['mine:disclaimer'] = 'This result supports, and does not replace, your supervisor\'s judgement.';
$string['mine:domain'] = 'Area of work';
$string['mine:draft'] = 'The scenario is still a draft awaiting expert review, so the result is for practice only.';
$string['mine:knowledge'] = 'Knowledge Checks below {$a}%';
$string['mine:none'] = 'No readiness scenario is open in this course yet.';
$string['mine:notassessed'] = 'not reached on your path';
$string['mine:nottaken'] = 'not taken';
$string['mine:revisit'] = 'To revisit';
$string['mine:scenario'] = 'Based on {$a}, your Knowledge Checks and, where set up, your supervisor\'s rating.';
$string['mine:score'] = 'Your score';
$string['mine:start'] = 'Start the scenario';
$string['mine:status:in_progress'] = 'You have started the scenario but not finished it.';
$string['mine:status:independent'] = 'You made sound decisions in every area of the scenario and your Knowledge Checks are strong.';
$string['mine:status:not_started'] = 'You have not taken the readiness scenario yet.';
$string['mine:status:not_yet'] = 'Some areas need more work before you are ready. The modules to revisit are listed below; talk to your supervisor about what comes next.';
$string['mine:status:supervised'] = 'You can do this work with a supervisor. The areas marked below need more practice before you work independently.';
$string['mine:supervisorpending'] = 'Your supervisor has not rated you yet.';
$string['msg:result:body'] = 'You finished the readiness scenario. Your result: {$a->status}. See what it means and what to work on next in My readiness.';
$string['msg:result:subject'] = '{$a->course}: your readiness result';
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
