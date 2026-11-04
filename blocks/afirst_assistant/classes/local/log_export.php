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
 * Pseudonymised CSV export of the assistant log for analysis (Capstone Proposal 3.6, data management).
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log_export {

    /**
     * Stable pseudonym for a user, keyed to this site so it cannot be reversed elsewhere.
     *
     * The key (site identifier) stays on the server, matching the proposal's requirement that
     * identifying keys are held under controlled access.
     *
     * @param int $userid
     * @return string
     */
    public static function pseudonym(int $userid): string {
        global $CFG;
        return 'L' . substr(hash_hmac('sha256', (string)$userid, $CFG->siteidentifier), 0, 12);
    }

    /**
     * Stream the course log as CSV.
     *
     * @param int $courseid
     */
    public static function download(int $courseid): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/csvlib.class.php');

        $writer = new \csv_export_writer();
        $writer->set_filename('afirst_assistant_log_' . $courseid);
        $writer->add_data(['logid', 'learner', 'timecreated', 'question', 'answer', 'refused', 'refusalreason', 'topscore',
            'citations', 'model', 'prompttokens', 'completiontokens', 'latencyms', 'connectiontype', 'downlinkmbps',
            'escalations', 'retrievalquery']);

        $sql = "SELECT l.*, (SELECT COUNT(1) FROM {block_afirst_escalation} e WHERE e.logid = l.id) AS escalations
                  FROM {block_afirst_log} l
                 WHERE l.courseid = ?
              ORDER BY l.id";
        $rs = $DB->get_recordset_sql($sql, [$courseid]);
        foreach ($rs as $row) {
            $writer->add_data([
                $row->id, self::pseudonym($row->userid), date('c', $row->timecreated), $row->question, $row->answer,
                $row->refused, $row->refusalreason, $row->topscore, $row->citations, $row->model, $row->prompttokens,
                $row->completiontokens, $row->latencyms, $row->connectiontype, $row->downlinkmbps, $row->escalations,
                $row->retrievalquery,
            ]);
        }
        $rs->close();
        $writer->download_file();
    }
}
