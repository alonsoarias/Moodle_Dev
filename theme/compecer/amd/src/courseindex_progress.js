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
 * Course index progress updater.
 *
 * Listens to completion state changes and updates progress indicators.
 *
 * @module     theme_compecer/courseindex_progress
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

/** @type {Object} Completion state constants. */
const COMPLETION = {
    INCOMPLETE: 0,
    COMPLETE: 1,
    COMPLETE_PASS: 2,
    COMPLETE_FAIL: 3,
};

/** @type {Object} Mapping of status keys to CSS classes. */
const STATUS_CLASSES = {
    completed: 'courseindex-item__status--completed',
    inprogress: 'courseindex-item__status--inprogress',
    failed: 'courseindex-item__status--failed',
};

/** @type {WeakMap<HTMLElement, Object>} Metadata cache per course index container. */
const containerMeta = new WeakMap();

/** Selector for course module nodes within the index. */
const CM_SELECTOR = '[data-for="cm"]';

/**
 * Replace tokens in a language string.
 *
 * @param {string} template Language string with {tokens}.
 * @param {Object} replacements Values to inject.
 * @return {string}
 */
const formatString = (template, replacements = {}) => {
    if (!template || typeof template !== 'string') {
        return '';
    }

    let output = template;
    Object.entries(replacements).forEach(([key, value]) => {
        const regex = new RegExp(`{${key}}`, 'g');
        output = output.replace(regex, String(value));
    });

    return output;
};

/**
 * Parse the dataset associated with the course index container.
 *
 * @param {HTMLElement} container Course index container.
 * @return {Object}
 */
const parseDataset = (container) => {
    const dataset = container.dataset.progress;
    if (!dataset) {
        containerMeta.set(container, {});
        return {};
    }

    try {
        const data = JSON.parse(dataset);
        containerMeta.set(container, data);
        return data;
    } catch (error) {
        // eslint-disable-next-line no-console
        console.error('Error parsing progress dataset:', error);
        containerMeta.set(container, {});
        return {};
    }
};

/**
 * Retrieve metadata associated with a container.
 *
 * @param {HTMLElement} container Course index container.
 * @return {Object}
 */
const getContainerData = (container) => containerMeta.get(container) ?? {};

/**
 * Parse the completion state from a completion info element.
 *
 * @param {HTMLElement|null} completionInfo Completion info node.
 * @return {number|null}
 */
const parseCompletionState = (completionInfo) => {
    if (!completionInfo) {
        return null;
    }

    if (completionInfo.classList.contains('completion_complete')) {
        return COMPLETION.COMPLETE;
    }

    if (completionInfo.classList.contains('completion_fail')) {
        return COMPLETION.COMPLETE_FAIL;
    }

    if (completionInfo.classList.contains('completion_incomplete')) {
        return COMPLETION.INCOMPLETE;
    }

    const value = parseInt(completionInfo.dataset.value, 10);
    if (Number.isNaN(value)) {
        return null;
    }

    return value;
};

/**
 * Apply section progress values to the DOM.
 *
 * @param {HTMLElement} sectionElement Section wrapper element.
 * @param {number} completed Completed activities.
 * @param {number} total Total activities.
 * @param {number} percentage Progress percentage.
 * @param {Object} strings Section language strings.
 */
const setSectionProgress = (sectionElement, completed, total, percentage, strings = {}) => {
    const progressValue = sectionElement.querySelector('[data-region="section-progress-value"]');
    const progressText = sectionElement.querySelector('[data-region="section-progress-text"]');

    if (!progressValue) {
        return;
    }

    if (total > 0) {
        progressValue.textContent = `${percentage}%`;
        if (progressText) {
            progressText.textContent = formatString(strings.aria, {completed, total, percentage}) ||
                `${completed}/${total}`;
        }
    } else {
        progressValue.textContent = '';
        if (progressText) {
            progressText.textContent = strings.nottracked ?? '';
        }
    }
};

/**
 * Apply initial dataset progress to the DOM.
 *
 * @param {HTMLElement} container Course index container.
 */
const applyDatasetProgress = (container) => {
    const data = getContainerData(container);
    const courseStrings = data.strings?.course ?? {};
    const sectionStrings = data.strings?.section ?? {};
    const progressValue = container.querySelector('[data-region="course-progress-value"]');
    const progressSummary = container.querySelector('[data-region="course-progress-summary"]');
    const progressBar = container.querySelector('.courseindex-progress-bar__fill');

    if (progressValue && data.course) {
        const percentage = data.course.percentage ?? 0;
        const completed = data.course.completed ?? 0;
        const total = data.course.total ?? 0;

        progressValue.textContent = `${percentage}%`;

        if (progressSummary) {
            progressSummary.textContent =
                formatString(courseStrings.summary, {completed, total, percentage}) ||
                `${completed}/${total}`;
        }

        if (progressBar) {
            progressBar.style.setProperty('--progress', `${percentage}%`);
            if (courseStrings.aria) {
                progressBar.setAttribute('aria-label', formatString(courseStrings.aria, {completed, total, percentage}));
            }
            progressBar.classList.toggle('is-disabled', data.enabled === false);
        }
    }

    if (Array.isArray(data.sections)) {
        data.sections.forEach(sectionData => {
            const sectionElement = container.querySelector(`[data-for="section"][data-id="${sectionData.id}"]`);
            if (!sectionElement) {
                return;
            }

            const completed = sectionData.completed ?? 0;
            const total = sectionData.total ?? 0;
            const percentage = sectionData.percentage ?? 0;
            setSectionProgress(sectionElement, completed, total, percentage, sectionStrings);
        });
    }
};

/**
 * Update the visual status indicator for a course module.
 *
 * @param {HTMLElement} item Course module element.
 * @param {Object} strings Language strings metadata.
 */
const updateItemStatus = (item, strings = {}) => {
    const statusElement = item.querySelector('[data-region="cm-status"]');
    const srStatus = item.querySelector('[data-region="cm-status-text"]');

    if (!statusElement || !srStatus) {
        return;
    }

    Object.values(STATUS_CLASSES).forEach(className => statusElement.classList.remove(className));

    let statusKey = 'notstarted';
    const completionInfo = item.querySelector('.completioninfo');
    const completionState = parseCompletionState(completionInfo);

    if (completionInfo) {
        if (completionInfo.classList.contains('completion_complete')) {
            statusKey = 'completed';
        } else if (completionInfo.classList.contains('completion_fail')) {
            statusKey = 'failed';
        } else if (completionInfo.classList.contains('completion_incomplete')) {
            statusKey = 'inprogress';
        } else if (completionInfo.classList.contains('completion_none')) {
            statusKey = 'notstarted';
        } else if (completionState !== null) {
            if (completionState === COMPLETION.COMPLETE || completionState === COMPLETION.COMPLETE_PASS) {
                statusKey = 'completed';
            } else if (completionState === COMPLETION.COMPLETE_FAIL) {
                statusKey = 'failed';
            } else if (completionState === COMPLETION.INCOMPLETE) {
                statusKey = 'inprogress';
            }
        }
    }

    const mappedClass = STATUS_CLASSES[statusKey];
    if (mappedClass) {
        statusElement.classList.add(mappedClass);
    }

    const label = strings.status?.[statusKey] ?? '';
    srStatus.textContent = label;
};

/**
 * Update the visual status for all course modules under a root element.
 *
 * @param {HTMLElement} root Root element to search within.
 * @param {Object} strings Language strings metadata.
 */
const updateItemStatuses = (root, strings = {}) => {
    const items = root.querySelectorAll(CM_SELECTOR);
    items.forEach(item => updateItemStatus(item, strings));
};

/**
 * Update section progress metrics based on current DOM state.
 *
 * @param {HTMLElement} sectionElement Section element.
 */
const updateSectionProgress = (sectionElement) => {
    const container = sectionElement.closest('#courseindex');
    if (!container) {
        return;
    }

    const data = getContainerData(container);
    const sectionStrings = data.strings?.section ?? {};
    const items = sectionElement.querySelectorAll(CM_SELECTOR);
    let completed = 0;
    let total = 0;

    items.forEach(item => {
        const completionInfo = item.querySelector('.completioninfo');
        const state = parseCompletionState(completionInfo);
        if (state === null) {
            return;
        }

        total++;
        if (state === COMPLETION.COMPLETE || state === COMPLETION.COMPLETE_PASS) {
            completed++;
        }
    });

    const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
    setSectionProgress(sectionElement, completed, total, percentage, sectionStrings);

    if (Array.isArray(data.sections)) {
        const sectionId = parseInt(sectionElement.dataset.id, 10);
        const metaSection = data.sections.find(section => parseInt(section.id, 10) === sectionId);
        if (metaSection) {
            metaSection.completed = completed;
            metaSection.total = total;
            metaSection.percentage = percentage;
        }
    }
};

/**
 * Update all section progress indicators in the container.
 *
 * @param {HTMLElement} container Course index container.
 */
const updateSectionProgressDisplay = (container) => {
    const sections = container.querySelectorAll('.courseindex-section');
    sections.forEach(section => {
        updateSectionProgress(section);
    });
};

let courseProgressTimeout = null;

/**
 * Debounce updates to the overall course progress.
 *
 * @param {HTMLElement} container Course index container.
 */
const debounceUpdateCourseProgress = (container) => {
    if (courseProgressTimeout) {
        clearTimeout(courseProgressTimeout);
    }

    courseProgressTimeout = setTimeout(() => {
        updateCourseProgress(container);
    }, 300);
};

/**
 * Update the course level progress metrics.
 *
 * @param {HTMLElement} container Course index container.
 */
const updateCourseProgress = (container) => {
    const progressValue = container.querySelector('[data-region="course-progress-value"]');
    const progressSummary = container.querySelector('[data-region="course-progress-summary"]');
    const progressBar = container.querySelector('.courseindex-progress-bar__fill');

    if (!progressValue || !progressBar) {
        return;
    }

    const data = getContainerData(container);
    const courseStrings = data.strings?.course ?? {};
    const items = container.querySelectorAll(CM_SELECTOR);
    let completed = 0;
    let total = 0;

    items.forEach(item => {
        const completionInfo = item.querySelector('.completioninfo');
        const state = parseCompletionState(completionInfo);
        if (state === null) {
            return;
        }

        total++;
        if (state === COMPLETION.COMPLETE || state === COMPLETION.COMPLETE_PASS) {
            completed++;
        }
    });

    const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;

    progressValue.textContent = `${percentage}%`;
    progressBar.style.setProperty('--progress', `${percentage}%`);

    if (progressSummary) {
        progressSummary.textContent =
            formatString(courseStrings.summary, {completed, total, percentage}) ||
            `${completed}/${total}`;
    }

    if (courseStrings.aria) {
        progressBar.setAttribute('aria-label', formatString(courseStrings.aria, {completed, total, percentage}));
    }

    if (!data.course) {
        data.course = {};
    }
    data.course.completed = completed;
    data.course.total = total;
    data.course.percentage = percentage;
};

/**
 * Update progress data after a completion change event.
 *
 * @param {HTMLElement} container Course index container.
 * @param {Object} detail Event detail payload.
 */
const updateProgress = (container, detail) => {
    if (!detail || !detail.element) {
        return;
    }

    const data = getContainerData(container);
    const sectionElement = detail.element.closest('.courseindex-section');

    if (sectionElement) {
        updateSectionProgress(sectionElement);
        updateItemStatuses(sectionElement, data.strings);
    }

    updateItemStatuses(container, data.strings);
    debounceUpdateCourseProgress(container);
};

/**
 * Update all item statuses within the container.
 *
 * @param {HTMLElement} container Course index container.
 */
const updateAllItemStatuses = (container) => {
    const data = getContainerData(container);
    updateItemStatuses(container, data.strings);
};

/**
 * Initialise the progress display for a container.
 *
 * @param {HTMLElement} container Course index container.
 */
const initProgressDisplay = (container) => {
    parseDataset(container);
    applyDatasetProgress(container);
    updateSectionProgressDisplay(container);
    updateAllItemStatuses(container);
    updateCourseProgress(container);
};

/**
 * Initialise the progress updater component.
 *
 * @param {string} courseIndexId Course index container ID.
 */
export const init = (courseIndexId) => {
    const courseEditor = getCurrentCourseEditor();
    if (!courseEditor) {
        return;
    }

    const container = document.getElementById(courseIndexId);
    if (!container) {
        return;
    }

    courseEditor.addEventListener(
        'cmCompletion',
        (event) => {
            updateProgress(container, event.detail);
        }
    );

    initProgressDisplay(container);
};
