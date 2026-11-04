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
 * Site settings.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_afirst_assistant\local\answerer;

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_afirst_assistant/minscore',
        new lang_string('minscore', 'block_afirst_assistant'),
        new lang_string('minscore_desc', 'block_afirst_assistant'),
        answerer::DEFAULT_MIN_SCORE, PARAM_FLOAT));

    $settings->add(new admin_setting_configselect('block_afirst_assistant/maxpassages',
        new lang_string('maxpassages', 'block_afirst_assistant'),
        new lang_string('maxpassages_desc', 'block_afirst_assistant'),
        answerer::DEFAULT_MAX_PASSAGES, array_combine(range(1, 5), range(1, 5))));
}
