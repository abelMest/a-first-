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

use core_ai\aiactions\generate_text;
use core_ai\manager;

/**
 * Course assistant that answers only from approved course content.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_afirst_assistant extends block_base {

    /**
     * Set the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_afirst_assistant');
    }

    #[\Override]
    public function applicable_formats() {
        return ['course-view' => true, 'mod' => true, 'site' => false, 'my' => false];
    }

    #[\Override]
    public function instance_allow_multiple() {
        return false;
    }

    #[\Override]
    public function has_config() {
        return true;
    }

    #[\Override]
    public function get_content() {
        global $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = (object) ['text' => '', 'footer' => ''];

        $course = $this->page->course;
        $context = context_course::instance($course->id);
        $canuse = has_capability('block/afirst_assistant:use', $context);
        $canmanage = has_capability('block/afirst_assistant:managesources', $context);
        $canreview = has_capability('block/afirst_assistant:review', $context);
        if (!$canuse && !$canmanage && !$canreview) {
            return $this->content;
        }

        $available = \core\di::get(manager::class)->is_action_available(generate_text::class);
        if (!$available) {
            // Learners see nothing until a site admin enables an AI provider with text generation.
            if ($canmanage) {
                $this->content->text = $OUTPUT->notification(get_string('notconfigured', 'block_afirst_assistant'),
                    'warning', false);
            }
            return $this->content;
        }

        if ($canuse && \block_afirst_assistant\local\launcher::applies($this->page)) {
            // The floating panel is on this page already; the block only opens it, so there is one conversation.
            $this->content->text = html_writer::tag('p', get_string('launcherblockhint', 'block_afirst_assistant'),
                ['class' => 'small text-muted']) .
                html_writer::tag('button', get_string('launcherbutton', 'block_afirst_assistant'), [
                    'type' => 'button', 'class' => 'btn btn-primary btn-sm', 'data-action' => 'open-afirst-launcher',
                ]);
        } else if ($canuse) {
            $this->content->text = $OUTPUT->render_from_template('block_afirst_assistant/assistant', [
                'uniqid' => html_writer::random_id('afirst'),
                'disclaimer' => get_string('disclaimer', 'block_afirst_assistant'),
            ]);
            $this->page->requires->js_call_amd('block_afirst_assistant/assistant', 'init', [
                $course->id,
                $USER->id,
                manager::get_user_policy_status($USER->id),
            ]);
        }

        $links = [];
        if ($canmanage) {
            $links[] = html_writer::link(new moodle_url('/blocks/afirst_assistant/sources.php', ['courseid' => $course->id]),
                get_string('managesources', 'block_afirst_assistant'));
        }
        if ($canreview) {
            $links[] = html_writer::link(new moodle_url('/blocks/afirst_assistant/report.php', ['courseid' => $course->id]),
                get_string('reviewqueue', 'block_afirst_assistant'));
        }
        $this->content->footer = implode(' · ', $links);
        return $this->content;
    }
}
