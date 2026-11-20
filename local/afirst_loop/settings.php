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
 * Settings.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_afirst_loop', new lang_string('pluginname', 'local_afirst_loop'));
    $settings->add(new admin_setting_configtext('local_afirst_loop/courses', new lang_string('courses', 'local_afirst_loop'),
        new lang_string('courses_desc', 'local_afirst_loop'), '', PARAM_SEQUENCE));
    $settings->add(new admin_setting_configtext('local_afirst_loop/supportbelow',
        new lang_string('supportbelow', 'local_afirst_loop'), new lang_string('supportbelow_desc', 'local_afirst_loop'),
        70, PARAM_INT));
    $ADMIN->add('localplugins', $settings);
}
