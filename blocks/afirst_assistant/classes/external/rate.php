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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * A learner's quick rating of an answer: helpful or not helpful.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'logid' => new external_value(PARAM_INT, 'Log id of the answer'),
            'rating' => new external_value(PARAM_INT, '1 helpful, -1 not helpful, 0 to clear'),
        ]);
    }

    /**
     * Store the rating on the learner's own answer.
     *
     * @param int $logid
     * @param int $rating
     * @return array
     */
    public static function execute(int $logid, int $rating): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['logid' => $logid, 'rating' => $rating]);
        // Learners may only rate answers they received.
        $log = $DB->get_record('block_afirst_log', ['id' => $params['logid'], 'userid' => $USER->id], 'id, courseid',
            MUST_EXIST);
        $context = \context_course::instance($log->courseid);
        self::validate_context($context);
        require_capability('block/afirst_assistant:use', $context);
        if (!in_array($params['rating'], [-1, 0, 1], true)) {
            throw new \invalid_parameter_exception('Rating must be -1, 0 or 1');
        }
        $DB->set_field('block_afirst_log', 'rating', $params['rating'] ?: null, ['id' => $log->id]);
        return ['rating' => $params['rating']];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'rating' => new external_value(PARAM_INT, 'Stored rating'),
        ]);
    }
}
