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
 * Event observers: a graded quiz or a completed activity may open other activities (design note, Act step).
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // After the transaction commits, so the new grade and completion state are visible.
    ['eventname' => '\mod_quiz\event\attempt_graded', 'callback' => '\local_afirst_loop\observer::attempt_graded',
        'internal' => false],
    ['eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\local_afirst_loop\observer::completion_updated', 'internal' => false],
    // Assistant signals (step 2).
    ['eventname' => '\block_afirst_assistant\event\question_asked',
        'callback' => '\local_afirst_loop\observer::question_asked', 'internal' => false],
    ['eventname' => '\block_afirst_assistant\event\answer_rated',
        'callback' => '\local_afirst_loop\observer::answer_rated', 'internal' => false],
];
