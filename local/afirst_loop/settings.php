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
    $settings->add(new admin_setting_heading('local_afirst_loop/signals', new lang_string('signals', 'local_afirst_loop'),
        new lang_string('signals_desc', 'local_afirst_loop')));
    $settings->add(new admin_setting_configtext('local_afirst_loop/earlyquestions',
        new lang_string('earlyquestions', 'local_afirst_loop'), new lang_string('earlyquestions_desc', 'local_afirst_loop'),
        3, PARAM_INT));
    $settings->add(new admin_setting_configtext('local_afirst_loop/earlyunsupported',
        new lang_string('earlyunsupported', 'local_afirst_loop'), new lang_string('earlyunsupported_desc', 'local_afirst_loop'),
        2, PARAM_INT));
    $settings->add(new admin_setting_configtext('local_afirst_loop/earlydays',
        new lang_string('earlydays', 'local_afirst_loop'), new lang_string('earlydays_desc', 'local_afirst_loop'), 7, PARAM_INT));
    $settings->add(new admin_setting_configtext('local_afirst_loop/trustdown',
        new lang_string('trustdown', 'local_afirst_loop'), new lang_string('trustdown_desc', 'local_afirst_loop'), 2, PARAM_INT));
    $settings->add(new admin_setting_heading('local_afirst_loop/followup', new lang_string('followup', 'local_afirst_loop'),
        new lang_string('followup_desc', 'local_afirst_loop')));
    $settings->add(new admin_setting_configtext('local_afirst_loop/stalldays',
        new lang_string('stalldays', 'local_afirst_loop'), new lang_string('stalldays_desc', 'local_afirst_loop'), 14, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('local_afirst_loop/digest',
        new lang_string('digest', 'local_afirst_loop'), new lang_string('digest_desc', 'local_afirst_loop'), 1));
    $ADMIN->add('localplugins', $settings);
}
