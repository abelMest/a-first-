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

namespace block_afirst_assistant\external;

use block_afirst_assistant\local\answerer;
use block_afirst_assistant\local\page_context;
use block_afirst_assistant\local\passage;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Quick first step of a question: which approved modules match, before the slower answer call.
 *
 * Lets the interface say "Found in Module 4.1, writing the answer…" instead of a silent wait. Nothing is logged here;
 * the answer call logs the outcome.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class find extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'question' => new external_value(PARAM_TEXT, 'Learner question'),
            'cmid' => new external_value(PARAM_INT, 'Activity the learner is on', VALUE_DEFAULT, 0),
            'previousquestion' => new external_value(PARAM_TEXT, 'Previous question, for follow-ups', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Locate passages.
     *
     * @param int $courseid
     * @param string $question
     * @param int $cmid
     * @param string $previousquestion
     * @return array
     */
    public static function execute(int $courseid, string $question, int $cmid = 0, string $previousquestion = ''): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'question' => $question,
            'cmid' => $cmid,
            'previousquestion' => $previousquestion,
        ]);
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/afirst_assistant:use', $context);

        $question = trim(\core_text::substr($params['question'], 0, ask::MAX_QUESTION_LENGTH));
        if ($question === '') {
            throw new \invalid_parameter_exception('Empty question');
        }
        $located = answerer::locate($params['courseid'], $question,
            page_context::for_cm($params['courseid'], $params['cmid'])['code'],
            \core_text::substr(trim($params['previousquestion']), 0, ask::MAX_QUESTION_LENGTH) ?: null);

        return [
            'grounded' => (bool)$located['grounded'],
            'followup' => $located['followup'],
            'sources' => array_values(array_unique(array_map(fn(passage $p) => $p->sourcetitle, $located['grounded']))),
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'grounded' => new external_value(PARAM_BOOL, 'Whether approved passages were found'),
            'followup' => new external_value(PARAM_BOOL, 'Whether the previous question was needed to find them'),
            'sources' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Matching source title')),
        ]);
    }
}
