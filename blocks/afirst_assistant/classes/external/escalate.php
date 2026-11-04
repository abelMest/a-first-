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

use block_afirst_assistant\local\escalation_manager;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Report an assistant answer for human review.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class escalate extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'logid' => new external_value(PARAM_INT, 'Log id of the answer'),
            'reason' => new external_value(PARAM_ALPHA, 'inaccurate, incomplete, misleading, unsafe or other'),
            'comment' => new external_value(PARAM_TEXT, 'Learner comment', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * File the report and notify reviewers.
     *
     * @param int $logid
     * @param string $reason
     * @param string $comment
     * @return array
     */
    public static function execute(int $logid, string $reason, string $comment = ''): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'logid' => $logid,
            'reason' => $reason,
            'comment' => $comment,
        ]);

        // Learners may only report answers they received.
        $log = $DB->get_record('block_afirst_log', ['id' => $params['logid'], 'userid' => $USER->id], '*', MUST_EXIST);
        $context = \context_course::instance($log->courseid);
        self::validate_context($context);
        require_capability('block/afirst_assistant:use', $context);

        if (!in_array($params['reason'], escalation_manager::REASONS, true)) {
            throw new \invalid_parameter_exception('Unknown reason');
        }
        $id = escalation_manager::create($log, $params['reason'], \core_text::substr(trim($params['comment']), 0, 2000));

        return ['escalationid' => $id];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'escalationid' => new external_value(PARAM_INT, 'Escalation id'),
        ]);
    }
}
