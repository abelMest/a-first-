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
 * Navigation and course-module deletion hooks.
 *
 * @package    report_afirst_readiness
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add the report to the course Reports menu for those who may see it.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function report_afirst_readiness_extend_navigation_course($navigation, $course, $context) {
    global $DB;
    if (has_capability('report/afirst_readiness:view', $context)
            && $DB->record_exists('report_afirst_readiness', ['courseid' => $course->id])) {
        $url = new moodle_url('/report/afirst_readiness/index.php', ['id' => $course->id]);
        $navigation->add(get_string('pluginname', 'report_afirst_readiness'), $url, navigation_node::TYPE_SETTING,
            null, 'afirstreadiness', new pix_icon('i/report', ''));
    }
}

/**
 * Forget a scenario when its Lesson is deleted.
 *
 * @param stdClass $cm
 */
function report_afirst_readiness_pre_course_module_delete($cm) {
    global $DB;
    $DB->delete_records('report_afirst_readiness', ['lessoncmid' => $cm->id]);
}
