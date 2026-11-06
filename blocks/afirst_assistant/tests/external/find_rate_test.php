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

namespace block_afirst_assistant\external;

use block_afirst_assistant\local\source_manager;
use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the find (progress) and rate web services.
 *
 * @package    block_afirst_assistant
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(find::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(rate::class)]
final class find_rate_test extends \externallib_advanced_testcase {

    public function test_find_reports_matching_modules_and_follow_ups(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $source = (object) ['courseid' => $course->id, 'title' => 'Module 1.2 Data quality', 'filename' => 'm.txt',
            'contenthash' => sha1('m'), 'status' => source_manager::STATUS_APPROVED, 'timecreated' => time(),
            'timemodified' => time()];
        $source->id = $DB->insert_record('block_afirst_source', $source);
        source_manager::index_text($source->id, 'Completeness checks whether all expected surveillance reports arrived.');
        set_config('minscore', 0.1, 'block_afirst_assistant');
        $this->setUser($student);

        $result = external_api::clean_returnvalue(find::execute_returns(),
            find::execute($course->id, 'What does completeness check?'));
        $this->assertTrue($result['grounded']);
        $this->assertFalse($result['followup']);
        $this->assertSame(['Module 1.2 Data quality'], $result['sources']);

        $followup = external_api::clean_returnvalue(find::execute_returns(),
            find::execute($course->id, 'And why?', 0, 'What does completeness check?'));
        $this->assertTrue($followup['grounded']);
        $this->assertTrue($followup['followup']);
        $this->assertSame(0, $DB->count_records('block_afirst_log'), 'find never logs');
    }

    public function test_rate_only_own_answers(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $other = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $logid = $DB->insert_record('block_afirst_log', (object) ['courseid' => $course->id, 'userid' => $student->id,
            'question' => 'Q', 'answer' => 'A', 'refused' => 0, 'timecreated' => time()]);

        $this->setUser($student);
        rate::execute($logid, 1);
        $this->assertEquals(1, $DB->get_field('block_afirst_log', 'rating', ['id' => $logid]));
        rate::execute($logid, 0);
        $this->assertNull($DB->get_field('block_afirst_log', 'rating', ['id' => $logid]));

        $this->setUser($other);
        $this->expectException(\dml_missing_record_exception::class);
        rate::execute($logid, -1);
    }

    public function test_rate_rejects_other_values(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $logid = $DB->insert_record('block_afirst_log', (object) ['courseid' => $course->id, 'userid' => $student->id,
            'question' => 'Q', 'refused' => 1, 'timecreated' => time()]);
        $this->setUser($student);
        $this->expectException(\invalid_parameter_exception::class);
        rate::execute($logid, 5);
    }
}
