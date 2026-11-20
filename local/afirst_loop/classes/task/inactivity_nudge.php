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

namespace local_afirst_loop\task;

use local_afirst_loop\local\digest;

/**
 * Daily: nudge learners who have stalled (rule R8).
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inactivity_nudge extends \core\task\scheduled_task {

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:inactivity_nudge', 'local_afirst_loop');
    }

    /**
     * Run for every course the loop is switched on for.
     */
    public function execute() {
        foreach (digest::courses() as $courseid) {
            $result = digest::nudge_stalled($courseid);
            mtrace("course $courseid: " . (is_array($result) ? count($result) : $result));
        }
    }
}
