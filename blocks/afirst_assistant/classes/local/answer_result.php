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
 * Outcome of one question.
 *
 * @package    block_afirst_assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answer_result {

    /** @var string Grounded answer with citations. */
    public const STATUS_ANSWERED = 'answered';

    /** @var string The assistant declined because the approved content does not cover the question. */
    public const STATUS_REFUSED = 'refused';

    /** @var string The AI provider failed; nothing was answered. */
    public const STATUS_ERROR = 'error';

    /** @var string No passage shared any term with the question. */
    public const REASON_NO_MATCH = 'no_match';

    /** @var string Passages matched, but none cleared the grounding threshold. */
    public const REASON_LOW_SCORE = 'low_score';

    /** @var string The model judged the passages insufficient. */
    public const REASON_MODEL_DECLINED = 'model_declined';

    /** @var string The model answered but cited no supplied passage, so the answer is withheld. */
    public const REASON_UNCITED = 'uncited';

    /** @var string The answer's content is not found in the passages it cites, so it is withheld. */
    public const REASON_UNSUPPORTED = 'unsupported';

    /** @var string The provider returned an error or nothing. */
    public const REASON_ERROR = 'error';

    /** @var string The AI provider's rate limit (cost cap) was reached. */
    public const REASON_RATE_LIMIT = 'rate_limit';

    /**
     * Constructor.
     *
     * @param int $logid Row in block_afirst_log, used when the learner reports the answer.
     * @param string $status One of the STATUS_ constants.
     * @param string|null $reason One of the REASON_ constants when not answered.
     * @param string|null $answer Answer text with [n] markers, when answered.
     * @param array[] $citations Passages behind the answer: n, sourcetitle, text, cited, ...
     */
    public function __construct(
        public readonly int $logid,
        public readonly string $status,
        public readonly ?string $reason,
        public readonly ?string $answer,
        public readonly array $citations,
    ) {
    }
}
