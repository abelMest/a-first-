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
 * Builds a scenario into a course as a core Lesson activity (docs/READINESS_SPEC.md section 5).
 *
 * Each decision point becomes a multichoice page. Options carry custom-scoring points (their total across domains),
 * per-option feedback, and a jump to the next decision point or the end. Lesson settings make it an assessment:
 * hidden until reviewed, one attempt per page, no retakes, feedback shown, scored to the gradebook.
 *
 * @package    report_afirst_readiness
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scenario_builder {

    /**
     * Create the Lesson and record it.
     *
     * @param int $courseid
     * @param int $section Course section number to add the Lesson to.
     * @param array $scenario Valid scenario (see scenario::validate()).
     * @param array $settings thresholds, knowledge (label => cmid), supervisor_cmid.
     * @return \stdClass The report_afirst_readiness record.
     */
    public static function build(int $courseid, int $section, array $scenario, array $settings): \stdClass {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/lesson/locallib.php');
        require_once($CFG->dirroot . '/mod/lesson/pagetypes/multichoice.php');

        if ($errors = scenario::validate($scenario)) {
            throw new \invalid_parameter_exception('Invalid scenario: ' . implode('; ', $errors));
        }
        $course = get_course($courseid);
        $maxoptions = max(array_map(fn($n) => count($n['options']), $scenario['nodes']));

        $moduleinfo = (object) [
            'modulename' => 'lesson',
            'course' => $course->id,
            'section' => $section,
            'visible' => 0,
            'name' => \core_text::substr($scenario['title'], 0, 255),
            'introeditor' => ['text' => $scenario['intro'] ?? '', 'format' => FORMAT_HTML, 'itemid' => 0],
            'showdescription' => 0,
            'cmidnumber' => 'AFIRST-RDY-' . $scenario['id'],
            // Assessment behaviour.
            'practice' => 0, 'custom' => 1, 'grade' => 100, 'retake' => 0, 'usemaxgrade' => 0,
            'maxattempts' => 1, 'review' => 0, 'feedback' => 1, 'minquestions' => 0, 'modattempts' => 0,
            'maxanswers' => max(4, $maxoptions), 'nextpagedefault' => 0, 'maxpages' => 1, 'ongoing' => 0,
            // Display.
            'progressbar' => 1, 'displayleft' => 0, 'displayleftif' => 0, 'slideshow' => 0,
            'width' => 640, 'height' => 480, 'bgcolor' => '#FFFFFF', 'activitylink' => 0,
            'mediafile' => file_get_unused_draft_itemid(), 'mediawidth' => 640, 'mediaheight' => 480, 'mediaclose' => 0,
            // Access.
            'available' => 0, 'deadline' => 0, 'timelimit' => 0, 'usepassword' => 0, 'password' => '',
            'dependency' => 0, 'timespent' => 0, 'completed' => 0, 'gradebetterthan' => 0, 'allowofflineattempts' => 0,
            'completion' => $course->enablecompletion ? COMPLETION_TRACKING_AUTOMATIC : COMPLETION_TRACKING_NONE,
            'completionendreached' => $course->enablecompletion ? 1 : 0,
            'completiontimespent' => 0,
        ];

        $transaction = $DB->start_delegated_transaction();
        $moduleinfo = create_module($moduleinfo);
        $lessonrecord = $DB->get_record('lesson', ['id' => $moduleinfo->instance], '*', MUST_EXIST);
        $lessonrecord->cmid = $moduleinfo->coursemodule;
        $lesson = new \lesson($lessonrecord);
        $context = \context_module::instance($moduleinfo->coursemodule);

        // Pages in file order, start node first, each inserted after the previous one.
        $nodes = $scenario['nodes'];
        usort($nodes, fn($a, $b) => ($b['id'] === $scenario['start']) <=> ($a['id'] === $scenario['start']));
        $pageids = [];
        $previous = 0;
        foreach ($nodes as $node) {
            $record = (object) [
                'lessonid' => $lessonrecord->id,
                'pageid' => $previous,
                'qtype' => LESSON_PAGE_MULTICHOICE,
                // No qoption key at all: lesson_page::create() turns any qoption, even 0, into "multiple answers".
                'title' => \core_text::substr($node['title'], 0, 255),
                'timecreated' => time(),
                'contents_editor' => [
                    'text' => ($node['inject'] ?? '') . '<p><strong>' . s($node['question']) . '</strong></p>',
                    'format' => FORMAT_HTML, 'itemid' => 0,
                ],
            ];
            foreach (array_values($node['options']) as $i => $option) {
                $record->answer_editor[$i] = ['text' => s($option['text']), 'format' => FORMAT_HTML];
                $record->response_editor[$i] = ['text' => s($option['feedback']), 'format' => FORMAT_HTML];
                $record->jumpto[$i] = LESSON_NEXTPAGE; // Fixed below, once every page exists.
                $record->score[$i] = array_sum(array_map('intval', $option['scores'] ?? []));
            }
            $page = \lesson_page::create($record, $lesson, $context, $CFG->maxbytes);
            $pageids[$node['id']] = (int)$page->id;
            $previous = (int)$page->id;
        }

        // Point every answer at its next page, and remember which answer is which option.
        $answermap = [];
        foreach ($nodes as $node) {
            $answers = array_values($DB->get_records('lesson_answers', ['pageid' => $pageids[$node['id']]], 'id ASC'));
            foreach (array_values($node['options']) as $i => $option) {
                $jump = $option['next'] === scenario::END ? LESSON_EOL : $pageids[$option['next']];
                $DB->set_field('lesson_answers', 'jumpto', $jump, ['id' => $answers[$i]->id]);
                $answermap[(int)$answers[$i]->id] = ['node' => $node['id'], 'option' => $option['id']];
            }
        }

        $now = time();
        $record = (object) [
            'courseid' => $course->id,
            'scenarioid' => $scenario['id'],
            'version' => \core_text::substr((string)$scenario['version'], 0, 20),
            'lessoncmid' => (int)$moduleinfo->coursemodule,
            'lessonid' => (int)$lessonrecord->id,
            'scenario' => json_encode($scenario, JSON_UNESCAPED_UNICODE),
            'answermap' => json_encode($answermap),
            'settings' => json_encode([
                'thresholds' => ($settings['thresholds'] ?? []) + rules::DEFAULT_THRESHOLDS,
                'knowledge' => (object)($settings['knowledge'] ?? []),
                'supervisor_cmid' => $settings['supervisor_cmid'] ?? null,
            ]),
            'usermodified' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('report_afirst_readiness', $record);
        $transaction->allow_commit();
        rebuild_course_cache($course->id, true);
        return $record;
    }
}
