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
 * Course assistant: AI policy gate, questions, cited answers and reporting.
 *
 * @module     block_afirst_assistant/assistant
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Policy from 'core_ai/policy';
import {getString, getStrings} from 'core/str';

const REASONS = ['inaccurate', 'incomplete', 'misleading', 'unsafe', 'other'];

let exchangeCount = 0;

/**
 * Escape text for insertion as HTML.
 *
 * @param {string} text
 * @returns {string}
 */
const escapeHtml = (text) => {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};

/**
 * Turn an answer with [n] markers into safe HTML whose markers link to their passages.
 *
 * @param {string} answer Plain-text answer.
 * @param {string} exchangeId Id of the exchange element.
 * @returns {string}
 */
export const answerToHtml = (answer, exchangeId) => escapeHtml(answer)
    .replace(/\[(\d+)\]/g, (match, n) => `<sup><a href="#${exchangeId}-c${n}">[${n}]</a></sup>`)
    .replace(/\n/g, '<br>');

/**
 * Browser-reported connection quality, for the low-bandwidth equity analysis.
 *
 * @returns {{connectiontype: string, downlinkmbps: number}}
 */
const connectionInfo = () => {
    const connection = navigator.connection || {};
    return {
        connectiontype: connection.effectiveType || '',
        downlinkmbps: connection.downlink || 0,
    };
};

/**
 * Show the core AI usage policy and resolve once the learner accepts it.
 *
 * @param {HTMLElement} root
 * @returns {Promise<void>}
 */
const requirePolicy = async(root) => {
    const region = root.querySelector('[data-region="policy"]');
    const form = root.querySelector('[data-region="ask-form"]');
    form.hidden = true;
    region.innerHTML = await Templates.render('core_ai/policyblock', {});
    return new Promise((resolve) => {
        region.querySelector('[data-action="accept"]').addEventListener('click', async() => {
            await Policy.acceptPolicy();
            region.innerHTML = '';
            form.hidden = false;
            resolve();
        });
        region.querySelector('[data-action="decline"]').addEventListener('click', async() => {
            region.innerHTML = `<p class="small text-muted">${escapeHtml(await getString('policydeclined',
                'block_afirst_assistant'))}</p>`;
        });
    });
};

/**
 * Add the report form under an exchange and submit it.
 *
 * @param {HTMLElement} exchange
 */
const openReport = async(exchange) => {
    const region = exchange.querySelector('[data-region="report"]');
    const labels = await getStrings(REASONS.map((r) => ({key: `reason:${r}`, component: 'block_afirst_assistant'})));
    const html = await Templates.render('block_afirst_assistant/report_form', {
        id: `${exchange.id}-report`,
        reasons: REASONS.map((value, i) => ({value, label: labels[i]})),
    });
    region.innerHTML = html;
    const form = region.querySelector('form');
    form.querySelector('select').focus();

    form.querySelector('[data-action="cancel-report"]').addEventListener('click', async() => {
        region.innerHTML = await Templates.render('block_afirst_assistant/report_button', {});
    });
    form.addEventListener('submit', async(e) => {
        e.preventDefault();
        form.querySelectorAll('button, select, textarea').forEach((el) => {
            el.disabled = true;
        });
        try {
            await Ajax.call([{
                methodname: 'block_afirst_assistant_escalate',
                args: {
                    logid: parseInt(exchange.dataset.logid, 10),
                    reason: form.reason.value,
                    comment: form.comment.value,
                },
            }])[0];
            region.innerHTML = `<p class="small text-success mb-0" role="status">${escapeHtml(
                await getString('reportthanks', 'block_afirst_assistant'))}</p>`;
        } catch (error) {
            Notification.exception(error);
            form.querySelectorAll('button, select, textarea').forEach((el) => {
                el.disabled = false;
            });
        }
    });
};

/**
 * Send a question and render the outcome.
 *
 * @param {HTMLElement} root
 * @param {number} courseId
 * @param {string} question
 */
const ask = async(root, courseId, question) => {
    const conversation = root.querySelector('[data-region="conversation"]');
    const button = root.querySelector('[data-action="ask"]');
    const id = `${root.id}-msg-${++exchangeCount}`;

    const pending = document.createElement('div');
    pending.className = 'block_afirst_assistant-exchange mb-3';
    pending.innerHTML = await Templates.render('core/loading', {});
    conversation.append(pending);
    button.disabled = true;

    try {
        const result = await Ajax.call([{
            methodname: 'block_afirst_assistant_ask',
            args: {courseid: courseId, question, ...connectionInfo()},
        }])[0];
        const html = await Templates.render('block_afirst_assistant/message', {
            id,
            question,
            logid: result.logid,
            answered: result.status === 'answered',
            refused: result.status === 'refused',
            error: result.status === 'error',
            answerhtml: answerToHtml(result.answer, id),
            citations: result.citations,
            disclaimer: await getString('disclaimer', 'block_afirst_assistant'),
        });
        pending.outerHTML = html;
    } catch (error) {
        pending.remove();
        Notification.exception(error);
    } finally {
        button.disabled = false;
        conversation.lastElementChild?.scrollIntoView({block: 'nearest'});
    }
};

/**
 * Initialise every assistant on the page.
 *
 * @param {number} courseId
 * @param {number} userId
 * @param {boolean} policyAccepted Server-side AI policy status for this user.
 */
export const init = async(courseId, userId, policyAccepted) => {
    Policy.preconfigurePolicyState(userId, policyAccepted);
    document.querySelectorAll('[data-region="afirst-assistant"]:not([data-initialised])').forEach(async(root) => {
        root.dataset.initialised = '1';
        const form = root.querySelector('[data-region="ask-form"]');
        const textarea = root.querySelector('[data-region="question"]');

        root.addEventListener('click', (e) => {
            const opener = e.target.closest('[data-action="open-report"]');
            if (opener) {
                openReport(opener.closest('.block_afirst_assistant-exchange'));
            }
        });
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const question = textarea.value.trim();
            if (question) {
                textarea.value = '';
                ask(root, courseId, question);
            }
        });
        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.requestSubmit();
            }
        });

        if (!await Policy.getPolicyStatus(userId)) {
            await requirePolicy(root);
        }
    });
};
