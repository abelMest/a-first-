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
 * Scheduled tasks.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    // R8: once a day, early morning.
    ['classname' => '\\local_afirst_loop\\task\\inactivity_nudge', 'blocking' => 0, 'minute' => '15', 'hour' => '6',
        'day' => '*', 'month' => '*', 'dayofweek' => '*'],
    // Teacher digest: Monday morning.
    ['classname' => '\\local_afirst_loop\\task\\weekly_digest', 'blocking' => 0, 'minute' => '30', 'hour' => '6',
        'day' => '*', 'month' => '*', 'dayofweek' => '1'],
];
