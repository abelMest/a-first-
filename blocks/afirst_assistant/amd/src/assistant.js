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
 * Course assistant: floating panel or block, AI policy gate, questions with progress, cited answers, follow-ups,
 * ratings and reporting.
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

/** Exchanges kept in the browser tab, so the conversation follows the learner between pages. */
const MAX_STORED = 12;

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
 * Session storage that never throws (private windows and blocked storage just lose persistence).
 */
const store = {
    get(key, fallback) {
        try {
            const value = window.sessionStorage.getItem(key);
            return value === null ? fallback : JSON.parse(value);
        } catch (e) {
            return fallback;
        }
    },
    set(key, value) {
        try {
            window.sessionStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            // Persistence is a convenience only.
        }
    },
    remove(key) {
        try {
            window.sessionStorage.removeItem(key);
        } catch (e) {
            // Ignore.
        }
    },
};

/**
 * One assistant instance: the floating panel or a block.
 */
class Assistant {

    /**
     * @param {HTMLElement} root
     * @param {object} config {courseid, userid, cmid}
     */
    constructor(root, config) {
        this.root = root;
        this.courseId = config.courseid;
        this.userId = config.userid;
        this.cmid = config.cmid || 0;
        this.storageKey = `block_afirst_assistant:${config.courseid}:${config.userid}`;
        this.conversation = root.querySelector('[data-region="conversation"]');
        this.form = root.querySelector('[data-region="ask-form"]');
        this.textarea = root.querySelector('[data-region="question"]');
        this.button = root.querySelector('[data-action="ask"]');
        this.progress = root.querySelector('[data-region="progress"]');
        this.starters = root.querySelector('[data-region="starters"]');
        this.busy = false;
    }

    /**
     * Wire events, restore the conversation and apply the AI policy gate.
     */
    async start() {
        this.root.addEventListener('click', (e) => this.onClick(e));
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            const question = this.textarea.value.trim();
            if (question && !this.busy) {
                this.textarea.value = '';
                this.ask(question);
            }
        });
        this.textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.form.requestSubmit();
            }
        });
        await this.restore();
        if (!await Policy.getPolicyStatus(this.userId)) {
            await this.requirePolicy();
        }
    }

    /**
     * Delegated clicks inside the assistant.
     *
     * @param {Event} e
     */
    onClick(e) {
        const target = e.target.closest('[data-action]');
        if (!target || !this.root.contains(target)) {
            return;
        }
        const action = target.dataset.action;
        if (action === 'open-report') {
            this.openReport(target.closest('.block_afirst_assistant-exchange'));
        } else if (action === 'rate') {
            this.rate(target);
        } else if (action === 'starter' && !this.busy) {
            this.ask(target.textContent.trim());
        } else if (action === 'new-conversation') {
            store.remove(this.storageKey);
            this.conversation.innerHTML = '';
            this.starters?.removeAttribute('hidden');
            this.textarea.focus();
        }
    }

    /**
     * Stored exchanges for this course in this browser tab.
     *
     * @returns {Array}
     */
    stored() {
        return store.get(this.storageKey, []);
    }

    /**
     * Re-render the stored conversation.
     */
    async restore() {
        const items = this.stored();
        for (const item of items) {
            this.conversation.insertAdjacentHTML('beforeend', await this.renderExchange(item.question, item.result));
            if (item.rating) {
                this.conversation.lastElementChild.querySelectorAll('[data-action="rate"]').forEach((b) => {
                    b.setAttribute('aria-pressed', String(parseInt(b.dataset.rating, 10) === item.rating));
                });
            }
        }
        if (items.length) {
            this.starters?.setAttribute('hidden', '');
            this.conversation.lastElementChild?.scrollIntoView({block: 'nearest'});
        }
    }

    /**
     * Render one exchange.
     *
     * @param {string} question
     * @param {object} result Web service result.
     * @returns {Promise<string>}
     */
    async renderExchange(question, result) {
        const id = `${this.root.id}-msg-${++exchangeCount}`;
        return Templates.render('block_afirst_assistant/message', {
            id,
            question,
            logid: result.logid,
            answered: result.status === 'answered',
            refused: result.status === 'refused',
            error: result.status === 'error',
            ratelimited: result.reason === 'rate_limit',
            answerhtml: answerToHtml(result.answer || '', id),
            citations: result.citations || [],
            disclaimer: await getString('disclaimer', 'block_afirst_assistant'),
        });
    }

    /**
     * Show a progress line, with elapsed seconds while waiting for the model.
     *
     * @param {string} text Empty to clear.
     * @param {boolean} timed
     */
    setProgress(text, timed = false) {
        window.clearInterval(this.timer);
        if (!this.progress) {
            return;
        }
        this.progress.textContent = text;
        if (timed && text) {
            const started = Date.now();
            this.timer = window.setInterval(() => {
                this.progress.textContent = `${text} (${Math.round((Date.now() - started) / 1000)} s)`;
            }, 1000);
        }
    }

    /**
     * Ask a question: find matching modules first (progress), then get the answer.
     *
     * @param {string} question
     */
    async ask(question) {
        this.busy = true;
        this.button.disabled = true;
        this.starters?.setAttribute('hidden', '');
        const items = this.stored();
        const previousquestion = items.length ? items[items.length - 1].question : '';

        const echo = document.createElement('div');
        echo.className = 'block_afirst_assistant-exchange mb-3';
        echo.innerHTML = `<div class="block_afirst_assistant-question ms-4 mb-2 p-2 rounded bg-primary text-white">${
            escapeHtml(question)}</div>`;
        this.conversation.append(echo);
        echo.scrollIntoView({block: 'nearest'});

        const [searching, writing, nomatch] = await getStrings([
            {key: 'progresssearching', component: 'block_afirst_assistant'},
            {key: 'progresswriting', component: 'block_afirst_assistant'},
            {key: 'progressnomatch', component: 'block_afirst_assistant'},
        ]);
        try {
            this.setProgress(searching);
            const args = {courseid: this.courseId, question, cmid: this.cmid, previousquestion};
            const found = await Ajax.call([{methodname: 'block_afirst_assistant_find', args}])[0];
            if (found.grounded) {
                this.setProgress(writing.replace('{$a}', found.sources.join(', ')), true);
            } else {
                this.setProgress(nomatch);
            }
            const result = await Ajax.call([{
                methodname: 'block_afirst_assistant_ask',
                args: {...args, ...connectionInfo()},
            }])[0];
            echo.outerHTML = await this.renderExchange(question, result);
            items.push({question, result, rating: 0});
            store.set(this.storageKey, items.slice(-MAX_STORED));
        } catch (error) {
            echo.remove();
            Notification.exception(error);
        } finally {
            this.setProgress('');
            this.busy = false;
            this.button.disabled = false;
            this.conversation.lastElementChild?.scrollIntoView({block: 'nearest'});
        }
    }

    /**
     * Rate an answer helpful or not; pressing the same button again clears the rating.
     *
     * @param {HTMLElement} button
     */
    async rate(button) {
        const exchange = button.closest('.block_afirst_assistant-exchange');
        const logid = parseInt(exchange.dataset.logid, 10);
        const pressed = button.getAttribute('aria-pressed') === 'true';
        const rating = pressed ? 0 : parseInt(button.dataset.rating, 10);
        try {
            await Ajax.call([{methodname: 'block_afirst_assistant_rate', args: {logid, rating}}])[0];
            exchange.querySelectorAll('[data-action="rate"]').forEach((b) => {
                b.setAttribute('aria-pressed', String(parseInt(b.dataset.rating, 10) === rating));
            });
            const items = this.stored();
            const item = items.find((i) => i.result.logid === logid);
            if (item) {
                item.rating = rating;
                store.set(this.storageKey, items);
            }
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Show the core AI usage policy and resolve once the learner accepts it.
     *
     * @returns {Promise<void>}
     */
    async requirePolicy() {
        const region = this.root.querySelector('[data-region="policy"]');
        this.form.hidden = true;
        region.innerHTML = await Templates.render('core_ai/policyblock', {});
        return new Promise((resolve) => {
            region.querySelector('[data-action="accept"]').addEventListener('click', async() => {
                await Policy.acceptPolicy();
                region.innerHTML = '';
                this.form.hidden = false;
                this.textarea.focus();
                resolve();
            });
            region.querySelector('[data-action="decline"]').addEventListener('click', async() => {
                region.innerHTML = `<p class="small text-muted">${escapeHtml(await getString('policydeclined',
                    'block_afirst_assistant'))}</p>`;
            });
        });
    }

    /**
     * Add the report form under an exchange and submit it.
     *
     * @param {HTMLElement} exchange
     */
    async openReport(exchange) {
        const region = exchange.querySelector('[data-region="report"]');
        const labels = await getStrings(REASONS.map((r) => ({key: `reason:${r}`, component: 'block_afirst_assistant'})));
        region.innerHTML = await Templates.render('block_afirst_assistant/report_form', {
            id: `${exchange.id}-report`,
            reasons: REASONS.map((value, i) => ({value, label: labels[i]})),
        });
        const form = region.querySelector('form');
        form.querySelector('select').focus();

        form.querySelector('[data-action="cancel-report"]').addEventListener('click', async(e) => {
            e.stopPropagation();
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
    }
}

/**
 * Initialise the floating launcher (button and panel).
 *
 * @param {object} config {rootid, courseid, userid, policyaccepted, cmid}
 */
export const initLauncher = async(config) => {
    const root = document.getElementById(config.rootid);
    if (!root || root.dataset.initialised) {
        return;
    }
    root.dataset.initialised = '1';
    Policy.preconfigurePolicyState(config.userid, config.policyaccepted);

    const fab = root.querySelector('[data-action="toggle-panel"]');
    const panel = root.querySelector('.block_afirst_assistant-panel');
    const openKey = `block_afirst_assistant:${config.courseid}:${config.userid}:open`;
    const assistant = new Assistant(root, config);

    const setOpen = (open, focus = true) => {
        panel.hidden = !open;
        fab.setAttribute('aria-expanded', String(open));
        root.classList.toggle('is-open', open);
        store.set(openKey, open);
        if (open && focus) {
            (assistant.form.hidden ? panel.querySelector('[data-action="accept"]') : assistant.textarea)?.focus();
        } else if (!open && focus) {
            fab.focus();
        }
    };
    fab.addEventListener('click', () => setOpen(panel.hidden));
    root.querySelector('[data-action="close-panel"]').addEventListener('click', () => setOpen(false));
    panel.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            setOpen(false);
        }
    });
    // The block, where a teacher still has one, opens this panel instead of showing a second chat.
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="open-afirst-launcher"]')) {
            e.preventDefault();
            setOpen(true);
        }
    });

    await assistant.start();
    if (store.get(openKey, false)) {
        setOpen(true, false);
    }
};

/**
 * Initialise every block-mode assistant on the page (used when the floating launcher is turned off).
 *
 * @param {number} courseId
 * @param {number} userId
 * @param {boolean} policyAccepted Server-side AI policy status for this user.
 */
export const init = async(courseId, userId, policyAccepted) => {
    Policy.preconfigurePolicyState(userId, policyAccepted);
    document.querySelectorAll('[data-region="afirst-assistant"][data-mode="block"]:not([data-initialised])')
        .forEach((root) => {
            root.dataset.initialised = '1';
            new Assistant(root, {courseid: courseId, userid: userId, cmid: 0}).start();
        });
};
