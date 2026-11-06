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
use core_ai\manager;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Ask the assistant a question about the course.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ask extends external_api {

    /** @var int Longest question accepted, in characters. */
    public const MAX_QUESTION_LENGTH = 1000;

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'question' => new external_value(PARAM_TEXT, 'Learner question'),
            'connectiontype' => new external_value(PARAM_ALPHANUMEXT, 'Effective connection type (slow-2g, 2g, 3g, 4g)',
                VALUE_DEFAULT, ''),
            'downlinkmbps' => new external_value(PARAM_FLOAT, 'Estimated downlink in Mbps', VALUE_DEFAULT, 0),
            'cmid' => new external_value(PARAM_INT, 'Activity the learner is on, 0 for the course page', VALUE_DEFAULT, 0),
            'previousquestion' => new external_value(PARAM_TEXT, 'The learner\'s previous question, for follow-ups',
                VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Answer or refuse.
     *
     * @param int $courseid
     * @param string $question
     * @param string $connectiontype
     * @param float $downlinkmbps
     * @param int $cmid
     * @param string $previousquestion
     * @return array
     */
    public static function execute(int $courseid, string $question, string $connectiontype = '',
            float $downlinkmbps = 0, int $cmid = 0, string $previousquestion = ''): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'question' => $question,
            'connectiontype' => $connectiontype,
            'downlinkmbps' => $downlinkmbps,
            'cmid' => $cmid,
            'previousquestion' => $previousquestion,
        ]);
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/afirst_assistant:use', $context);

        if (!manager::get_user_policy_status($USER->id)) {
            throw new \moodle_exception('error:policynotaccepted', 'block_afirst_assistant');
        }
        $question = trim(\core_text::substr($params['question'], 0, self::MAX_QUESTION_LENGTH));
        if ($question === '') {
            throw new \invalid_parameter_exception('Empty question');
        }

        $result = (new answerer(\core\di::get(manager::class)))->answer(
            $params['courseid'],
            $USER->id,
            $question,
            $params['connectiontype'] !== '' ? \core_text::substr($params['connectiontype'], 0, 20) : null,
            $params['downlinkmbps'] > 0 ? round($params['downlinkmbps'], 2) : null,
            page_context::for_cm($params['courseid'], $params['cmid'])['code'],
            \core_text::substr(trim($params['previousquestion']), 0, self::MAX_QUESTION_LENGTH) ?: null,
        );

        return [
            'logid' => $result->logid,
            'status' => $result->status,
            'reason' => $result->reason ?? '',
            'answer' => $result->answer ?? '',
            'citations' => array_map(fn(array $c) => [
                'n' => $c['n'],
                'sourcetitle' => $c['sourcetitle'],
                'text' => $c['text'],
                'cited' => $c['cited'],
            ], $result->citations),
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'logid' => new external_value(PARAM_INT, 'Log id, used to report the answer'),
            'status' => new external_value(PARAM_ALPHA, 'answered, refused or error'),
            'reason' => new external_value(PARAM_ALPHAEXT, 'Why it was not answered, if so'),
            'answer' => new external_value(PARAM_TEXT, 'Answer with [n] citation markers'),
            'citations' => new external_multiple_structure(
                new external_single_structure([
                    'n' => new external_value(PARAM_INT, 'Citation number used in the answer'),
                    'sourcetitle' => new external_value(PARAM_TEXT, 'Approved source name'),
                    'text' => new external_value(PARAM_TEXT, 'Passage text'),
                    'cited' => new external_value(PARAM_BOOL, 'Whether the answer cites this passage'),
                ])
            ),
        ]);
    }
}
