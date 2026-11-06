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

    $settings->add(new admin_setting_configtext('block_afirst_assistant/minsupport',
        new lang_string('minsupport', 'block_afirst_assistant'),
        new lang_string('minsupport_desc', 'block_afirst_assistant'),
        \block_afirst_assistant\local\support_checker::DEFAULT_MIN_SUPPORT, PARAM_FLOAT));

    $settings->add(new admin_setting_configselect('block_afirst_assistant/maxpassages',
        new lang_string('maxpassages', 'block_afirst_assistant'),
        new lang_string('maxpassages_desc', 'block_afirst_assistant'),
        answerer::DEFAULT_MAX_PASSAGES, array_combine(range(1, 5), range(1, 5))));

    $settings->add(new admin_setting_configcheckbox('block_afirst_assistant/crosslingual',
        new lang_string('crosslingual', 'block_afirst_assistant'),
        new lang_string('crosslingual_desc', 'block_afirst_assistant'), 0));

    $settings->add(new admin_setting_configtext('block_afirst_assistant/sourcelanguage',
        new lang_string('sourcelanguage', 'block_afirst_assistant'),
        new lang_string('sourcelanguage_desc', 'block_afirst_assistant'), 'English', PARAM_TEXT));

    $settings->add(new admin_setting_heading('block_afirst_assistant/launcherheading',
        new lang_string('launcherheading', 'block_afirst_assistant'), ''));

    $settings->add(new admin_setting_configcheckbox('block_afirst_assistant/launcher',
        new lang_string('launcher', 'block_afirst_assistant'),
        new lang_string('launcher_desc', 'block_afirst_assistant'), 1));

    $settings->add(new admin_setting_configtext('block_afirst_assistant/contextboost',
        new lang_string('contextboost', 'block_afirst_assistant'),
        new lang_string('contextboost_desc', 'block_afirst_assistant'),
        \block_afirst_assistant\local\retriever::DEFAULT_CONTEXT_BOOST, PARAM_FLOAT));

    $settings->add(new admin_setting_heading('block_afirst_assistant/hybridheading',
        new lang_string('hybridheading', 'block_afirst_assistant'),
        new lang_string('hybridheading_desc', 'block_afirst_assistant')));

    $settings->add(new admin_setting_configtext('block_afirst_assistant/embedendpoint',
        new lang_string('embedendpoint', 'block_afirst_assistant'),
        new lang_string('embedendpoint_desc', 'block_afirst_assistant'), '', PARAM_URL));

    $settings->add(new admin_setting_configtext('block_afirst_assistant/embedmodel',
        new lang_string('embedmodel', 'block_afirst_assistant'),
        new lang_string('embedmodel_desc', 'block_afirst_assistant'), 'nomic-embed-text', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('block_afirst_assistant/embedcpu',
        new lang_string('embedcpu', 'block_afirst_assistant'),
        new lang_string('embedcpu_desc', 'block_afirst_assistant'), 1));

    $settings->add(new admin_setting_configtext('block_afirst_assistant/minsimilarity',
        new lang_string('minsimilarity', 'block_afirst_assistant'),
        new lang_string('minsimilarity_desc', 'block_afirst_assistant'),
        \block_afirst_assistant\local\embedder::DEFAULT_MIN_SIMILARITY, PARAM_FLOAT));
}
