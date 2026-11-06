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
 * External functions.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_afirst_assistant_ask' => [
        'classname' => \block_afirst_assistant\external\ask::class,
        'description' => 'Ask the course assistant a question; answers only from approved course content.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/afirst_assistant:use',
    ],
    'block_afirst_assistant_find' => [
        'classname' => \block_afirst_assistant\external\find::class,
        'description' => 'Find which approved course modules match a question (progress step before the answer).',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/afirst_assistant:use',
    ],
    'block_afirst_assistant_rate' => [
        'classname' => \block_afirst_assistant\external\rate::class,
        'description' => 'Rate an assistant answer as helpful or not helpful.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/afirst_assistant:use',
    ],
    'block_afirst_assistant_escalate' => [
        'classname' => \block_afirst_assistant\external\escalate::class,
        'description' => 'Report an assistant answer for human review.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/afirst_assistant:use',
    ],
];
