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

/** Selector for course module nodes within the index. */
const CM_SELECTOR = '[data-for="cm"]';

/** CSS class applied to the container when completion tracking is disabled. */
const NO_TRACKING_CLASS = 'courseindex--no-tracking';

/** Default number of characters used to represent progress bars. */
const PROGRESS_BAR_LENGTH = 20;

/** @type {WeakMap<HTMLElement, Object>} Metadata cache per course index container. */
const containerMeta = new WeakMap();

/** @type {WeakMap<HTMLElement, number>} Debounce timers per container. */
const courseProgressTimeouts = new WeakMap();

/**
 * Clamp a number between a minimum and maximum value.
 *
 * @param {number} value Value to clamp.
 * @param {number} min Minimum value.
 * @param {number} max Maximum value.
 * @returns {number}
 */
const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

/**
 * Normalise and convert a value to a finite number.
 *
 * @param {*} value Potential numeric value.
 * @returns {number}
 */
const toNumber = (value) => {
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

/**
 * Render a textual progress bar using block characters.
 *
 * @param {number} percentage Progress percentage.
 * @param {number} length Number of characters.
 * @returns {string}
 */
const createProgressBar = (percentage, length = PROGRESS_BAR_LENGTH) => {
    const safePercentage = clamp(Math.round(toNumber(percentage)), 0, 100);
    const blocks = clamp(Math.round((safePercentage / 100) * length), 0, length);
    return `${'█'.repeat(blocks)}${'░'.repeat(length - blocks)}`;
};

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
 * Update dataset information for a section entry.
 *
 * @param {HTMLElement} sectionElement Section element.
 * @param {Object} values Values to merge.
 */
const updateSectionMeta = (sectionElement, values) => {
    const container = sectionElement.closest('#courseindex');
    if (!container) {
        return;
    }
    const data = getContainerData(container);
    if (!Array.isArray(data.sections)) {
        data.sections = [];
    }
    const sectionId = parseInt(sectionElement.dataset.id, 10);
    let metaSection = data.sections.find((section) => parseInt(section.id, 10) === sectionId);
    if (!metaSection) {
        metaSection = {id: sectionId};
        data.sections.push(metaSection);
    }
    Object.assign(metaSection, values);
};

/**
 * Apply course progress values to the DOM.
 *
 * @param {HTMLElement} container Course index container.
 * @param {number} completed Completed activities.
 * @param {number} total Total activities.
 * @param {number} percentage Progress percentage.
 * @param {Object} strings Course language strings.
 * @param {Object} options Additional options.
 */
const setCourseProgress = (container, completed, total, percentage, strings = {}, options = {}) => {
    const {
        enabled = true,
        summaryOverride = null,
        ariaOverride = null,
        barOverride = null,
        valueOverride = null,
    } = options;

    const data = getContainerData(container);
    if (!data.course) {
        data.course = {};
    }

    const safePercentage = clamp(Math.round(toNumber(percentage)), 0, 100);
    const progressValue = container.querySelector('[data-region="course-progress-value"]');
    if (progressValue) {
        const displayValue = valueOverride ?? `${safePercentage}%`;
        progressValue.textContent = displayValue;
    }

    const computedSummary = formatString(strings.summary, {
        completed,
        total,
        percentage: safePercentage,
    }) || `${completed}/${total}`;
    const summaryText = summaryOverride ?? computedSummary;

    const progressSummary = container.querySelector('[data-region="course-progress-summary"]');
    if (progressSummary) {
        progressSummary.textContent = summaryText;
    }

    const progressMessage = container.querySelector('[data-region="course-progress-message"]');
    if (progressMessage) {
        progressMessage.textContent = summaryText;
    }

    const computedAria = formatString(strings.aria, {
        completed,
        total,
        percentage: safePercentage,
    }) || summaryText;
    const ariaText = ariaOverride ?? computedAria;

    const progressText = container.querySelector('[data-region="course-progress-text"]');
    if (progressText) {
        progressText.textContent = ariaText;
    }

    const barText = barOverride ?? createProgressBar(safePercentage);
    const progressBar = container.querySelector('[data-region="course-progress-bar"]');
    if (progressBar) {
        progressBar.textContent = barText;
    }

    const progressBarContainer = container.querySelector('.courseindex-progress-bar');
    if (progressBarContainer) {
        progressBarContainer.setAttribute('aria-valuenow', safePercentage);
        if (ariaText) {
            progressBarContainer.setAttribute('aria-label', ariaText);
        } else {
            progressBarContainer.removeAttribute('aria-label');
        }
        progressBarContainer.classList.toggle('is-disabled', !enabled);
    }

    data.course.completed = completed;
    data.course.total = total;
    data.course.percentage = safePercentage;
    data.course.percentageformatted = valueOverride ?? `${safePercentage}%`;
    data.course.bar = barText;
    data.course.summary = summaryText;
    data.course.aria = ariaText;
};

/**
 * Apply section progress values to the DOM.
 *
 * @param {HTMLElement} sectionElement Section wrapper element.
 * @param {number} completed Completed activities.
 * @param {number} total Total activities.
 * @param {number} percentage Progress percentage.
 * @param {Object} strings Section language strings.
 * @param {Object} options Additional options.
 */
const setSectionProgress = (sectionElement, completed, total, percentage, strings = {}, options = {}) => {
    const {
        summaryOverride = null,
        ariaOverride = null,
        barOverride = null,
    } = options;

    const safePercentage = clamp(Math.round(toNumber(percentage)), 0, 100);
    const progressValue = sectionElement.querySelector('[data-region="section-progress-value"]');
    if (progressValue) {
        progressValue.textContent = `${safePercentage}%`;
    }

    const summaryText = summaryOverride ?? (
        total > 0
            ? (formatString(strings.summary, {completed, total, percentage: safePercentage}) || `${completed}/${total}`)
            : (strings.nottracked ?? '')
    );
    const progressSummary = sectionElement.querySelector('[data-region="section-progress-summary"]');
    if (progressSummary) {
        progressSummary.textContent = summaryText;
    }

    const barText = barOverride ?? createProgressBar(total > 0 ? safePercentage : 0);
    const progressBar = sectionElement.querySelector('[data-region="section-progress-bar"]');
    if (progressBar) {
        progressBar.textContent = barText;
    }

    const ariaText = ariaOverride ?? (
        total > 0
            ? (formatString(strings.aria, {completed, total, percentage: safePercentage}) || summaryText)
            : (strings.nottracked ?? summaryText)
    );
    const srText = sectionElement.querySelector('[data-region="section-progress-text"]');
    if (srText) {
        srText.textContent = ariaText;
    }

    updateSectionMeta(sectionElement, {
        completed,
        total,
        percentage: safePercentage,
        bar: barText,
        summary: summaryText,
        aria: ariaText,
    });
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

    if (data.course) {
        setCourseProgress(
            container,
            data.course.completed ?? 0,
            data.course.total ?? 0,
            data.course.percentage ?? 0,
            courseStrings,
            {
                enabled: data.enabled !== false,
                summaryOverride: data.course.summary ?? null,
                ariaOverride: data.course.aria ?? null,
                barOverride: data.course.bar ?? null,
                valueOverride: data.course.percentageformatted ?? null,
            }
        );
    }

    if (Array.isArray(data.sections)) {
        data.sections.forEach((sectionData) => {
            const sectionElement = container.querySelector(`[data-for="section"][data-id="${sectionData.id}"]`);
            if (!sectionElement) {
                return;
            }
            setSectionProgress(
                sectionElement,
                sectionData.completed ?? 0,
                sectionData.total ?? 0,
                sectionData.percentage ?? 0,
                sectionStrings,
                {
                    summaryOverride: sectionData.summary ?? null,
                    ariaOverride: sectionData.aria ?? null,
                    barOverride: sectionData.bar ?? null,
                }
            );
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

    Object.values(STATUS_CLASSES).forEach((className) => statusElement.classList.remove(className));

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
    items.forEach((item) => updateItemStatus(item, strings));
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

    items.forEach((item) => {
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
};

/**
 * Update all section progress indicators in the container.
 *
 * @param {HTMLElement} container Course index container.
 */
const updateSectionProgressDisplay = (container) => {
    const sections = container.querySelectorAll('.courseindex-section');
    sections.forEach((section) => {
        updateSectionProgress(section);
    });
};

/**
 * Update the course level progress metrics.
 *
 * @param {HTMLElement} container Course index container.
 */
const updateCourseProgress = (container) => {
    const data = getContainerData(container);
    const courseStrings = data.strings?.course ?? {};

    if (data.enabled === false) {
        setCourseProgress(
            container,
            data.course?.completed ?? 0,
            data.course?.total ?? 0,
            data.course?.percentage ?? 0,
            courseStrings,
            {
                enabled: false,
                summaryOverride: data.course?.summary ?? null,
                ariaOverride: data.course?.aria ?? null,
                barOverride: data.course?.bar ?? null,
                valueOverride: data.course?.percentageformatted ?? null,
            }
        );
        return;
    }

    const items = container.querySelectorAll(CM_SELECTOR);
    let completed = 0;
    let total = 0;

    items.forEach((item) => {
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
    setCourseProgress(container, completed, total, percentage, courseStrings, {
        enabled: true,
    });
};

/**
 * Debounce updates to the overall course progress.
 *
 * @param {HTMLElement} container Course index container.
 */
const debounceUpdateCourseProgress = (container) => {
    const existingTimeout = courseProgressTimeouts.get(container);
    if (existingTimeout) {
        clearTimeout(existingTimeout);
    }

    const timeout = setTimeout(() => {
        courseProgressTimeouts.delete(container);
        updateCourseProgress(container);
    }, 300);

    courseProgressTimeouts.set(container, timeout);
};

/**
 * Update progress data after a completion change event.
 *
 * @param {HTMLElement} container Course index container.
 * @param {Object} detail Event detail payload.
 */
const updateProgressForDetail = (container, detail) => {
    if (!detail || !detail.element) {
        return;
    }

    const data = getContainerData(container);
    const cmId = detail.element.id ?? detail.element.cmid ?? null;
    if (!cmId) {
        debounceUpdateCourseProgress(container);
        return;
    }

    const item = container.querySelector(`${CM_SELECTOR}[data-id="${cmId}"]`);
    if (item) {
        updateItemStatus(item, data.strings);
        const sectionElement = item.closest('.courseindex-section');
        if (sectionElement) {
            updateSectionProgress(sectionElement);
        }
    }

    debounceUpdateCourseProgress(container);
};

/**
 * Initialise the progress display for a container.
 *
 * @param {HTMLElement} container Course index container.
 */
const initProgressDisplay = (container) => {
    parseDataset(container);
    applyDatasetProgress(container);
    const data = getContainerData(container);
    if (data.enabled === false) {
        container.classList.add(NO_TRACKING_CLASS);
        return;
    }
    container.classList.remove(NO_TRACKING_CLASS);
    updateSectionProgressDisplay(container);
    updateItemStatuses(container, data.strings);
    updateCourseProgress(container);
};

/**
 * Initialise the progress updater component.
 *
 * @param {string} courseIndexId Course index container ID.
 */
export const init = (courseIndexId, courseEditor = null) => {
    const editor = courseEditor ?? getCurrentCourseEditor();
    if (!editor) {
        return;
    }

    const container = document.getElementById(courseIndexId);
    if (!container) {
        return;
    }

    initProgressDisplay(container);

    const target = editor.target ?? document;
    target.addEventListener('cm.completionstate:updated', ({detail}) => {
        const data = getContainerData(container);
        if (data.enabled === false) {
            return;
        }
        updateProgressForDetail(container, detail);
    });
    target.addEventListener('transaction:end', () => {
        const data = getContainerData(container);
        if (data.enabled === false) {
            return;
        }
        updateItemStatuses(container, data.strings);
        updateSectionProgressDisplay(container);
        updateCourseProgress(container);
    });
};
