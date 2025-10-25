// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Live course progress indicator for the course index drawer.
 *
 * @module     theme_compecer/courseindex
 * @copyright  2024 IngeWeb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Str from 'core/str';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import * as CourseEvents from 'core_course/events';

const SELECTORS = {
    PROGRESS: '[data-region="course-progress"]',
    PROGRESS_VALUE: '[data-region="progress-value"]',
    PROGRESS_BAR: '[data-region="progress-bar"]',
    PROGRESS_BAR_FILL: '.progress-bar',
    PROGRESS_TEXT: '[data-region="progress-text"]',
    PROGRESS_COMPLETED_VALUE: '[data-region="progress-completed-value"]',
    PROGRESS_COMPLETED_TEXT: '[data-region="progress-completed-text"]',
    PROGRESS_REMAINING_VALUE: '[data-region="progress-remaining-value"]',
    PROGRESS_REMAINING_TEXT: '[data-region="progress-remaining-text"]',
    PROGRESS_DETAILS: '[data-region="progress-details"]',
    PROGRESS_NOTICE: '[data-region="progress-notice"]',
};

const CLASSES = {
    INITIALISED: 'courseindexProgressInitialised',
    DISABLED: 'courseindex-progress--disabled',
    LOADING: 'courseindex-progress--loading',
};

const STRINGS = {
    completedTemplate: '',
    remainingTemplate: '',
    notracking: '',
    loading: '',
};

let inflightRequest = null;
let refreshTimeout = null;

/**
 * Determine the current course id using the available sources.
 *
 * @param {HTMLElement} root The drawer root element.
 * @param {number|null} configuredCourseId Optional course id coming from template.
 * @returns {number|null}
 */
const getCourseId = (root, configuredCourseId = null) => {
    const explicitId = Number(configuredCourseId || root?.dataset?.courseid);
    if (!Number.isNaN(explicitId) && explicitId > 0) {
        return explicitId;
    }

    try {
        const courseEditor = getCurrentCourseEditor();
        if (courseEditor) {
            const course = courseEditor.get('course');
            if (course && course.id) {
                return Number(course.id);
            }
        }
    } catch (error) {
        // The reactive course editor is not available in some contexts (e.g., legacy formats).
    }

    if (typeof M !== 'undefined' && M?.cfg) {
        const fallback = Number(M.cfg.courseId || M.cfg.courseid);
        if (!Number.isNaN(fallback) && fallback > 0) {
            return fallback;
        }
    }

    return null;
};

/**
 * Safely update text content.
 *
 * @param {Element|null} node Target node.
 * @param {string} value New text.
 */
const setText = (node, value) => {
    if (node) {
        node.textContent = value;
    }
};

/**
 * Prepare localised strings required by the component.
 *
 * @returns {Promise<void>}
 */
const loadStrings = async() => {
    if (STRINGS.completedTemplate) {
        return;
    }

    const [completed, remaining, notracking, loading] = await Str.get_strings([
        {key: 'courseprogresscompletedshort', component: 'theme_compecer', param: '{$a}'},
        {key: 'courseprogressremainingshort', component: 'theme_compecer', param: '{$a}'},
        {key: 'courseprogressnotracking', component: 'theme_compecer'},
        {key: 'courseprogressloading', component: 'theme_compecer'},
    ]);

    STRINGS.completedTemplate = completed;
    STRINGS.remainingTemplate = remaining;
    STRINGS.notracking = notracking;
    STRINGS.loading = loading;
};

/**
 * Update the notice banner state.
 *
 * @param {HTMLElement|null} noticeEl
 * @param {string} message
 * @param {boolean} visible
 */
const updateNotice = (noticeEl, message, visible = false) => {
    if (!noticeEl) {
        return;
    }
    setText(noticeEl, message);
    if (visible) {
        noticeEl.classList.remove('sr-only');
    } else if (!noticeEl.classList.contains('sr-only')) {
        noticeEl.classList.add('sr-only');
    }
};

/**
 * Apply progress data to the DOM with smooth animations.
 *
 * @param {HTMLElement} container The progress container element.
 * @param {Object} data Progress data from server.
 * @param {boolean} animate Whether to animate the changes (default: true).
 */
const renderProgress = (container, data, animate = true) => {
    const progressValue = container.querySelector(SELECTORS.PROGRESS_VALUE);
    const progressBar = container.querySelector(SELECTORS.PROGRESS_BAR);
    const progressBarFill = progressBar ? progressBar.querySelector(SELECTORS.PROGRESS_BAR_FILL) : null;
    const progressText = container.querySelector(SELECTORS.PROGRESS_TEXT);
    const completedValue = container.querySelector(SELECTORS.PROGRESS_COMPLETED_VALUE);
    const completedText = container.querySelector(SELECTORS.PROGRESS_COMPLETED_TEXT);
    const remainingValue = container.querySelector(SELECTORS.PROGRESS_REMAINING_VALUE);
    const remainingText = container.querySelector(SELECTORS.PROGRESS_REMAINING_TEXT);
    const details = container.querySelector(SELECTORS.PROGRESS_DETAILS);
    const notice = container.querySelector(SELECTORS.PROGRESS_NOTICE);

    const hasTracking = !!data.hascompletion && data.total > 0;
    container.classList.toggle(CLASSES.DISABLED, !hasTracking);

    if (!hasTracking) {
        container.dataset.progressEnabled = '0';
        delete container.dataset.progressPercentage;
        delete container.dataset.progressCompleted;
        delete container.dataset.progressIncomplete;
        delete container.dataset.progressTotal;
        setText(progressValue, '--');
        if (progressBar) {
            progressBar.setAttribute('aria-valuenow', '0');
        }
        if (progressBarFill) {
            progressBarFill.style.width = '0%';
        }
        if (progressText) {
            progressText.setAttribute('hidden', 'hidden');
        }
        if (details) {
            details.setAttribute('hidden', 'hidden');
        }
        updateNotice(notice, STRINGS.notracking, true);
        return;
    }

    // Show progress elements if hidden
    if (progressText && progressText.hasAttribute('hidden')) {
        progressText.removeAttribute('hidden');
    }
    if (details && details.hasAttribute('hidden')) {
        details.removeAttribute('hidden');
    }

    updateNotice(notice, '', false);

    const percentage = Math.max(0, Math.min(Number(data.percentage) || 0, 100));
    const completed = Math.max(0, Number(data.completed) || 0);
    const remaining = Math.max(0, Number(data.incomplete) || 0);
    const total = Math.max(0, Number(data.total) || 0);

    // Update dataset attributes
    container.dataset.progressEnabled = '1';
    container.dataset.progressPercentage = String(percentage);
    container.dataset.progressCompleted = String(completed);
    container.dataset.progressIncomplete = String(remaining);
    container.dataset.progressTotal = String(total);

    // Update percentage display
    setText(progressValue, `${percentage}%`);

    // Update progress bar with animation
    if (progressBar) {
        progressBar.setAttribute('aria-valuenow', String(percentage));
    }
    if (progressBarFill) {
        if (!animate) {
            // Disable transition temporarily for immediate update
            const transition = progressBarFill.style.transition;
            progressBarFill.style.transition = 'none';
            progressBarFill.style.width = `${percentage}%`;
            // Force reflow to apply the change
            void progressBarFill.offsetWidth;
            progressBarFill.style.transition = transition;
        } else {
            progressBarFill.style.width = `${percentage}%`;
        }
    }

    // Update progress text (human-readable)
    if (progressText && data.progresstext) {
        setText(progressText, data.progresstext);
    }

    // Update completed/remaining statistics
    if (completedValue) {
        setText(completedValue, String(completed));
    }
    if (completedText) {
        setText(completedText, STRINGS.completedTemplate.replace('{$a}', String(completed)));
    }
    if (remainingValue) {
        setText(remainingValue, String(remaining));
    }
    if (remainingText) {
        setText(remainingText, STRINGS.remainingTemplate.replace('{$a}', String(remaining)));
    }

    // Trigger aria-live announcement for screen readers
    if (progressBar && animate) {
        progressBar.setAttribute('aria-live', 'polite');
        progressBar.setAttribute('aria-atomic', 'true');
    }
};

/**
 * Request progress information from the server and refresh the UI.
 *
 * @param {HTMLElement} container The progress container element.
 * @param {number} courseId The current course identifier.
 */
const refreshProgress = (container, courseId) => {
    if (inflightRequest) {
        return;
    }

    container.classList.add(CLASSES.LOADING);
    updateNotice(container.querySelector(SELECTORS.PROGRESS_NOTICE), STRINGS.loading, false);

    inflightRequest = Ajax.call([{
        methodname: 'theme_compecer_get_course_progress',
        args: {courseid: courseId},
    }])[0];

    inflightRequest.then((response) => {
        renderProgress(container, response);
    }).catch(Notification.exception).finally(() => {
        container.classList.remove(CLASSES.LOADING);
        inflightRequest = null;
    });
};

/**
 * Schedule a progress refresh to debounce rapid updates.
 *
 * @param {HTMLElement} container The progress container element.
 * @param {number} courseId The current course identifier.
 */
const scheduleRefresh = (container, courseId) => {
    if (refreshTimeout) {
        clearTimeout(refreshTimeout);
    }
    refreshTimeout = setTimeout(() => {
        refreshProgress(container, courseId);
    }, 300);
};

/**
 * Initialise the progress component.
 *
 * @param {Object} config Optional configuration.
 * @param {string} config.rootId The drawer root id.
 * @param {number} [config.courseId] Optional course id override.
 */
export const init = async(config = {}) => {
    const rootId = config.rootId || 'courseindex';
    const root = document.getElementById(rootId);
    if (!root || root.dataset[CLASSES.INITIALISED]) {
        return;
    }

    const progressContainer = root.querySelector(SELECTORS.PROGRESS);
    if (!progressContainer) {
        return;
    }

    const courseId = getCourseId(root, config.courseId);

    try {
        await loadStrings();
    } catch (error) {
        Notification.exception(error);
        return;
    }

    const enabled = progressContainer.dataset.progressEnabled !== '0';

    if (!enabled) {
        renderProgress(progressContainer, {
            hascompletion: false,
            percentage: 0,
            total: 0,
            completed: 0,
            incomplete: 0,
        });
    } else if (progressContainer.dataset.progressPercentage !== undefined) {
        renderProgress(progressContainer, {
            hascompletion: true,
            percentage: Number(progressContainer.dataset.progressPercentage),
            total: Number(progressContainer.dataset.progressTotal || 0),
            completed: Number(progressContainer.dataset.progressCompleted || 0),
            incomplete: Number(progressContainer.dataset.progressIncomplete || 0),
        });
    }

    root.dataset[CLASSES.INITIALISED] = '1';

    if (!enabled || !courseId) {
        return;
    }

    const handler = () => scheduleRefresh(progressContainer, courseId);

    document.addEventListener(CourseEvents.manualCompletionToggled, handler);
    document.addEventListener(CourseEvents.sectionRefreshed, handler);

    handler();
};

export default {init};
