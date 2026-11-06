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

use core_ai\aiactions\generate_text;
use core_ai\manager;

/**
 * Tests for the floating launcher's visibility rules, module context and starter questions.
 *
 * @package    block_afirst_assistant
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(launcher::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(page_context::class)]
final class launcher_test extends \advanced_testcase {

    /** @var \stdClass */
    private \stdClass $course;

    /** @var \stdClass */
    private \stdClass $student;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
    }

    /**
     * Configure an AI provider with text generation, as a site admin would.
     */
    private function enable_provider(): void {
        \core\di::get(manager::class)->create_provider_instance(
            classname: '\aiprovider_ollama\provider',
            name: 'test',
            enabled: true,
            config: ['endpoint' => 'http://localhost:11434/'],
            actionconfig: [generate_text::class => ['enabled' => true, 'settings' => ['model' => 'm',
                'systeminstruction' => 's']]],
        );
    }

    /**
     * An approved source for module 2.2 with starter questions.
     */
    private function add_source(): void {
        global $DB;
        $source = (object) ['courseid' => $this->course->id, 'title' => 'Module 2.2 Exploratory analysis',
            'filename' => 'm22.txt', 'contenthash' => sha1('m22'), 'status' => source_manager::STATUS_APPROVED,
            'timecreated' => time(), 'timemodified' => time()];
        $source->id = $DB->insert_record('block_afirst_source', $source);
        source_manager::index_text($source->id, 'Exploratory analysis summarises each variable before modelling it.');
        source_manager::set_suggestions($source, ['What is exploratory analysis?']);
    }

    /**
     * A course page for the current user.
     *
     * @param \cm_info|null $cm
     * @return \moodle_page
     */
    private function page(?\cm_info $cm = null): \moodle_page {
        $page = new \moodle_page();
        if ($cm) {
            $page->set_cm($cm);
        } else {
            $page->set_course($this->course);
            $page->set_context(\context_course::instance($this->course->id));
        }
        $page->set_pagelayout('incourse');
        return $page;
    }

    public function test_hidden_without_provider_or_sources(): void {
        $this->setUser($this->student);
        $this->add_source();
        $this->assertFalse(launcher::applies($this->page()), 'no AI provider');

        $this->enable_provider();
        $this->assertTrue(launcher::applies($this->page()));

        set_config('launcher', 0, 'block_afirst_assistant');
        $this->assertFalse(launcher::applies($this->page()), 'turned off in site settings');
    }

    public function test_learners_need_an_approved_source_but_teachers_do_not(): void {
        $this->enable_provider();
        $this->setUser($this->student);
        $this->assertFalse(launcher::applies($this->page()));

        $this->setUser($this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher'));
        $this->assertTrue(launcher::applies($this->page()));
    }

    public function test_not_for_guests_embedded_pages_or_other_courses(): void {
        $this->enable_provider();
        $this->add_source();
        $this->setGuestUser();
        $this->assertFalse(launcher::applies($this->page()));

        $this->setUser($this->student);
        $page = $this->page();
        $page->set_pagelayout('embedded');
        $this->assertFalse(launcher::applies($page));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse(launcher::applies($this->page()), 'not enrolled');
    }

    public function test_activity_page_shows_its_module_and_starters(): void {
        $this->enable_provider();
        $this->add_source();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id,
            'name' => 'Module 2.2 Knowledge Check']);
        $this->setUser($this->student);
        $cm = get_fast_modinfo($this->course)->get_cm($quiz->cmid);

        $this->assertSame(['code' => '2.2', 'name' => 'Module 2.2'], page_context::for_cm($this->course->id, $cm->id));
        $html = launcher::render($this->page($cm));
        $this->assertStringContainsString('Module 2.2', $html);
        $this->assertStringContainsString('What is exploratory analysis?', $html);
        $this->assertStringContainsString('data-action="toggle-panel"', $html);
    }
}
