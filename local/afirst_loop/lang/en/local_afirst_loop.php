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
 * English strings.
 *
 * @package    local_afirst_loop
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['afirst_loop:receivealerts'] = 'Receive alerts about learners who need support';
$string['courses'] = 'Courses';
$string['courses_desc'] = 'Comma-separated ids of the courses where learners are told what their results have opened. Leave empty to switch the feedback off.';
$string['digest'] = 'Weekly teacher digest';
$string['digest:assistant'] = 'Course assistant: {$a->questions} questions, {$a->declined} declined.';
$string['digest:early'] = 'Offered a review early (from assistant questions):';
$string['digest:fasttracked'] = 'Fast-tracked (diagnostic at or above the threshold):';
$string['digest:module'] = 'Module {$a->module}: {$a->questions} questions, {$a->declined} declined';
$string['digest:needsupport'] = 'Still below the pass mark after review and retake:';
$string['digest:reports'] = 'Reported answers: {$a->new} new this week, {$a->open} open in total.';
$string['digest:reviewed'] = 'Sent to a review unit (Knowledge Check below the pass mark):';
$string['digest:stalled'] = 'Stalled (no activity for the inactivity period):';
$string['digest:subject'] = '{$a->course}: A-FIRST weekly digest';
$string['digest:topmodules'] = 'Most questions:';
$string['digest_desc'] = 'Every Monday, teachers get a summary of the past week: fast-tracks, reviews, early-review offers, learners who need support or have stalled, reported answers and assistant use.';
$string['earlydays'] = 'Early review: window (days)';
$string['earlydays_desc'] = 'Only assistant questions from the last this-many days count (R5).';
$string['earlyquestions'] = 'Early review: questions';
$string['earlyquestions_desc'] = 'Offer a sub-module\'s review unit once a learner has asked the assistant this many questions about it before its Knowledge Check (R5). 0 turns this signal off.';
$string['earlyunsupported'] = 'Early review: unsupported answers';
$string['earlyunsupported_desc'] = 'Also offer it once this many of those questions could not be answered from the course text (R5). 0 turns this signal off.';
$string['followup'] = 'Follow-up';
$string['followup_desc'] = 'Rule R8 and the weekly digest of the OODA design note.';
$string['messageprovider:digest'] = 'Weekly A-FIRST digest for the course';
$string['messageprovider:needssupport'] = 'A learner is still below the pass mark after a review and a retake';
$string['messageprovider:opened'] = 'An activity has opened for you because of a result or a completed activity';
$string['msg:early:body'] = 'You have asked several questions about Module {$a->module}. Its short review is now open for you: {$a->list}. It may help before the Knowledge Check.';
$string['msg:early:subject'] = '{$a->course}: a review that may help';
$string['msg:fasttrack:body'] = 'You scored {$a->score}% on {$a->trigger}, so these are now open: {$a->list}. You can go straight to them.';
$string['msg:fasttrack:subject'] = '{$a->course}: you can skip ahead';
$string['msg:opened:body'] = 'You completed {$a->trigger}, so this is now open: {$a->list}.';
$string['msg:opened:subject'] = '{$a->course}: something new is open';
$string['msg:retake:body'] = 'Thanks for completing {$a->trigger}. This is now open: {$a->list}.';
$string['msg:retake:subject'] = '{$a->course}: your retake is open';
$string['msg:review:body'] = 'Your score on {$a->trigger} was {$a->score}%. A short review is now open: {$a->list}. After it you can retake the check.';
$string['msg:review:subject'] = '{$a->course}: a short review is ready for you';
$string['msg:stalled:body'] = 'You have not been on the course for {$a->days} days. You were last working on {$a->activity}: pick up where you left off.';
$string['msg:stalled:bodynoactivity'] = 'You have not been on the course for {$a->days} days. Pick up where you left off.';
$string['msg:stalled:subject'] = '{$a->course}: pick up where you left off';
$string['msg:support:body'] = '{$a->learner} scored {$a->score}% on {$a->trigger}, still below {$a->below}% after the review and the retake. They may need a conversation.';
$string['msg:support:subject'] = '{$a->course}: a learner may need support';
$string['msg:trust:body'] = '{$a->learner} has marked {$a->count} answers from the course assistant as not helpful. The answers are in the assistant\'s review queue.';
$string['msg:trust:subject'] = '{$a->course}: a learner found the assistant unhelpful';
$string['pluginname'] = 'A-FIRST feedback loop';
$string['privacy:metadata:sent'] = 'Which feedback messages were sent about which activity, so each change is announced only once.';
$string['privacy:metadata:sent:cmid'] = 'The activity the feedback was about.';
$string['privacy:metadata:sent:kind'] = 'The kind of feedback.';
$string['privacy:metadata:sent:timecreated'] = 'When the feedback was sent.';
$string['privacy:metadata:sent:userid'] = 'The learner the feedback was about.';
$string['reviewqueue'] = 'Assistant review queue';
$string['signals'] = 'Assistant signals';
$string['signals_desc'] = 'Rules R5 and R6 of the OODA design note. They use counts and reasons from the course assistant, never the text of questions. Early review needs the course rules built with "early_review": true.';
$string['stalldays'] = 'Inactivity nudge after (days)';
$string['stalldays_desc'] = 'A learner who has started the course and has had no activity for this many days gets one nudge (R8) and appears in the digest. 0 turns this off.';
$string['supportbelow'] = 'Support alert below (%)';
$string['supportbelow_desc'] = 'Teachers are alerted when a learner scores below this on a retake, after the review (design note rule R7).';
$string['task:inactivity_nudge'] = 'A-FIRST: nudge learners who have stalled';
$string['task:weekly_digest'] = 'A-FIRST: weekly teacher digest';
$string['trustdown'] = 'Trust check: not-helpful ratings';
$string['trustdown_desc'] = 'Tell the teachers once a learner has marked this many assistant answers as not helpful (R6). 0 turns this off.';
