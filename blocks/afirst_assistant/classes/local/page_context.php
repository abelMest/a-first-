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

namespace block_afirst_assistant\local;

/**
 * Works out which course module ("Module 2.2") the learner is looking at.
 *
 * Course activities are named like "Module 2.2 Exploratory Data Analysis" or "Module 4.1 Knowledge Check"; when the
 * activity name has no code, its section name is used ("Module 2 – …" gives no code, so nothing is boosted).
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_context {

    /**
     * Module code and a display name for an activity of a course.
     *
     * @param int $courseid
     * @param int $cmid
     * @return array{code: string|null, name: string|null} Nulls when the activity is unknown or has no module code.
     */
    public static function for_cm(int $courseid, int $cmid): array {
        if ($cmid <= 0) {
            return ['code' => null, 'name' => null];
        }
        $modinfo = get_fast_modinfo($courseid);
        $cm = $modinfo->get_cms()[$cmid] ?? null;
        if ($cm === null || !$cm->uservisible) {
            return ['code' => null, 'name' => null];
        }
        $name = $cm->get_formatted_name();
        $code = retriever::module_of($name);
        if ($code === null) {
            $section = $modinfo->get_section_info($cm->sectionnum);
            $code = $section ? retriever::module_of(get_section_name($courseid, $section)) : null;
        }
        return ['code' => $code, 'name' => $code !== null ? get_string('modulecode', 'block_afirst_assistant', $code) : null];
    }
}
