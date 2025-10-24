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
    notstarted: 'courseindex-item__status--notstarted',
};

/** Selector for course module nodes within the index. */
const CM_SELECTOR = '[data-for="cm"]';

/** CSS class applied to the container when completion tracking is disabled. */
const NO_TRACKING_CLASS = 'courseindex--no-tracking';

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
 * Normalise the parsed dataset ensuring defaults are present.
 *
 * @param {Object} data Parsed data object.
 * @returns {Object}
 */
function ensureModuleIndex(data) {
    if (!data.modulesIndex) {
        data.modulesIndex = {};
        if (Array.isArray(data.modules)) {
            data.modules.forEach((module) => {
                if (!module || typeof module.id === 'undefined') {
                    return;
                }
                data.modulesIndex[String(module.id)] = module;
            });
        }
    }

    return data.modulesIndex;
}

const prepareParsedData = (data = {}) => {
    const normalised = data && typeof data === 'object' ? data : {};
    if (!Array.isArray(normalised.sections)) {
        normalised.sections = [];
    }
    if (!Array.isArray(normalised.modules)) {
        normalised.modules = [];
    }
    if (!normalised.strings || typeof normalised.strings !== 'object') {
        normalised.strings = {};
    }
    if (!normalised.user || typeof normalised.user !== 'object') {
        normalised.user = {};
    }
    ensureModuleIndex(normalised);
    return normalised;
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
        containerMeta.set(container, prepareParsedData({}));
        return {};
    }

    try {
        const data = prepareParsedData(JSON.parse(dataset));
        containerMeta.set(container, data);
        return data;
    } catch (error) {
        // eslint-disable-next-line no-console
        console.error('Error parsing progress dataset:', error);
        const fallback = prepareParsedData({});
        containerMeta.set(container, fallback);
        return fallback;
    }
};

/**
 * Retrieve metadata associated with a container.
 *
 * @param {HTMLElement} container Course index container.
 * @return {Object}
 */
const getContainerData = (container) => containerMeta.get(container) ?? prepareParsedData({});

/**
 * Persist metadata for a specific course module.
 *
 * @param {HTMLElement} container Course index container.
 * @param {Object} module Module metadata to merge.
 * @returns {Object} The merged module metadata.
 */
const setModuleMeta = (container, module) => {
    if (!module || typeof module.id === 'undefined') {
        return module;
    }

    const data = getContainerData(container);
    const index = ensureModuleIndex(data);
    const key = String(module.id);
    const existing = index[key] ?? {};
    const merged = Object.assign({}, existing, module);
    index[key] = merged;

    if (!Array.isArray(data.modules)) {
        data.modules = [];
    }
    const currentIndex = data.modules.findIndex((entry) => String(entry?.id) === key);
    if (currentIndex === -1) {
        data.modules.push(merged);
    } else {
        data.modules[currentIndex] = merged;
    }

    return merged;
};

/**
 * Retrieve metadata for a course module by id.
 *
 * @param {HTMLElement} container Course index container.
 * @param {number|string} cmId Course module id.
 * @returns {Object|null}
 */
const getModuleMeta = (container, cmId) => {
    if (!cmId) {
        return null;
    }

    const data = getContainerData(container);
    const index = ensureModuleIndex(data);
    return index[String(cmId)] ?? null;
};

/**
 * Retrieve stored metadata for a section id if available.
 *
 * @param {HTMLElement} container Course index container.
 * @param {number} sectionId Section id.
 * @returns {Object|null}
 */
const getSectionMeta = (container, sectionId) => {
    const data = getContainerData(container);
    if (!Array.isArray(data.sections)) {
        return null;
    }

    return data.sections.find((section) => Number(section?.id) === Number(sectionId)) ?? null;
};

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
 * Map a completion state value to a visual status key.
 *
 * @param {number|null} state Completion state.
 * @param {Object} moduleData Existing module metadata.
 * @returns {string}
 */
const mapCompletionStateToStatus = (state, moduleData = {}) => {
    if (state === COMPLETION.COMPLETE || state === COMPLETION.COMPLETE_PASS) {
        return 'completed';
    }

    if (state === COMPLETION.COMPLETE_FAIL) {
        return 'failed';
    }

    if (state === COMPLETION.INCOMPLETE) {
        if (moduleData.status === 'inprogress' || moduleData.viewed) {
            return 'inprogress';
        }
        return 'notstarted';
    }

    if (typeof state === 'number') {
        return moduleData.status ?? 'notstarted';
    }

    return moduleData.status ?? 'notstarted';
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
 * Apply module metadata to a DOM element.
 *
 * @param {HTMLElement} item Course module element.
 * @param {Object} moduleData Module metadata.
 * @param {Object} strings Language strings metadata.
 */
const setItemStatusFromModule = (item, moduleData, strings = {}) => {
    if (!item || !moduleData) {
        return;
    }

    const completionInfo = item.querySelector('.completioninfo');
    if (completionInfo) {
        const classMap = {
            completed: 'completion_complete',
            failed: 'completion_fail',
            inprogress: 'completion_incomplete',
            notstarted: 'completion_none',
        };
        completionInfo.classList.remove('completion_complete', 'completion_fail', 'completion_incomplete', 'completion_none');
        const status = moduleData.status ?? 'notstarted';
        const completionClass = classMap[status] ?? 'completion_none';
        completionInfo.classList.add(completionClass);
        if (Number.isFinite(moduleData.state)) {
            completionInfo.dataset.value = moduleData.state;
        } else {
            completionInfo.dataset.value = 'NaN';
        }
    }

    updateItemStatus(item, strings, moduleData.status ?? 'notstarted');
};

/**
 * Apply stored module metadata to the DOM tree.
 *
 * @param {HTMLElement} container Course index container.
 */
const applyModuleStatuses = (container) => {
    const data = getContainerData(container);
    if (!Array.isArray(data.modules) || data.modules.length === 0) {
        return;
    }

    data.modules.forEach((module) => {
        if (!module || typeof module.id === 'undefined') {
            return;
        }
        const item = container.querySelector(`${CM_SELECTOR}[data-id="${module.id}"]`);
        if (!item) {
            return;
        }
        setItemStatusFromModule(item, module, data.strings ?? {});
    });
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
        progressMessage.textContent = enabled ? '' : summaryText;
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

    const progressFill = container.querySelector('[data-region="course-progress-bar"]');
    if (progressFill) {
        progressFill.style.width = `${safePercentage}%`;
        progressFill.classList.toggle('is-empty', safePercentage === 0);
    }

    const progressBarContainer = container.querySelector('.courseindex-progress-bar');
    if (progressBarContainer) {
        progressBarContainer.setAttribute('aria-valuenow', safePercentage);
        if (ariaText) {
            progressBarContainer.setAttribute('aria-label', ariaText);
        } else {
            progressBarContainer.removeAttribute('aria-label');
        }
        progressBarContainer.classList.toggle('is-disabled', !enabled || total === 0);
    }

    data.course.completed = completed;
    data.course.total = total;
    data.course.percentage = safePercentage;
    data.course.percentageformatted = valueOverride ?? `${safePercentage}%`;
    data.course.summary = summaryText;
    data.course.summarydisplay = summaryOverride ?? summaryText;
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

    const progressBar = sectionElement.querySelector('[data-region="section-progress-bar"]');
    if (progressBar) {
        const width = total > 0 ? safePercentage : 0;
        progressBar.style.width = `${width}%`;
        progressBar.classList.toggle('is-empty', width === 0);
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
        summary: summaryText,
        summarydisplay: summaryOverride ?? summaryText,
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
                summaryOverride: data.course.summarydisplay ?? data.course.summary ?? null,
                ariaOverride: data.course.aria ?? null,
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
                    summaryOverride: sectionData.summarydisplay ?? sectionData.summary ?? null,
                    ariaOverride: sectionData.aria ?? null,
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
const updateItemStatus = (item, strings = {}, statusOverride = null) => {
    const statusElement = item.querySelector('[data-region="cm-status"]');
    const srStatus = item.querySelector('[data-region="cm-status-text"]');

    if (!statusElement || !srStatus) {
        return;
    }

    Object.values(STATUS_CLASSES).forEach((className) => {
        if (className) {
            statusElement.classList.remove(className);
        }
    });

    let statusKey = statusOverride;

    if (!statusKey) {
        statusKey = 'notstarted';
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
    }

    statusKey = statusKey || 'notstarted';
    const mappedClass = STATUS_CLASSES[statusKey];
    if (mappedClass) {
        statusElement.classList.add(mappedClass);
    }

    const icons = {
        notstarted: '○',
        inprogress: '◐',
        completed: '✓',
        failed: '✕',
    };
    statusElement.textContent = icons[statusKey] ?? icons.notstarted;

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
    const sectionId = Number(sectionElement.dataset.id);
    const modulesIndex = ensureModuleIndex(data);
    const modules = Object.values(modulesIndex).filter((module) => {
        if (!module || module.tracked === false) {
            return false;
        }
        return Number(module.sectionid) === sectionId;
    });

    if (modules.length) {
        const completed = modules.filter((module) => module.status === 'completed' || module.status === 'failed').length;
        const total = modules.length;
        const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
        setSectionProgress(sectionElement, completed, total, percentage, sectionStrings);
        return;
    }

    const sectionMeta = getSectionMeta(container, sectionId);
    if (sectionMeta) {
        setSectionProgress(
            sectionElement,
            sectionMeta.completed ?? 0,
            sectionMeta.total ?? 0,
            sectionMeta.percentage ?? 0,
            sectionStrings,
            {
                summaryOverride: sectionMeta.summarydisplay ?? sectionMeta.summary ?? null,
                ariaOverride: sectionMeta.aria ?? null,
            }
        );
        return;
    }

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

    if (data.enabled === false || data.user?.istracked === false) {
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

    const modulesIndex = ensureModuleIndex(data);
    const modules = Object.values(modulesIndex).filter((module) => module && module.tracked !== false);
    if (modules.length) {
        const completed = modules.filter((module) => module.status === 'completed' || module.status === 'failed').length;
        const total = modules.length;
        const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
        setCourseProgress(container, completed, total, percentage, courseStrings, {
            enabled: true,
        });
        return;
    }

    if (data.course) {
        setCourseProgress(
            container,
            data.course.completed ?? 0,
            data.course.total ?? 0,
            data.course.percentage ?? 0,
            courseStrings,
            {
                enabled: true,
                summaryOverride: data.course.summarydisplay ?? data.course.summary ?? null,
                ariaOverride: data.course.aria ?? null,
                valueOverride: data.course.percentageformatted ?? null,
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
    const cmId = detail.element.id ?? detail.element.cmid ?? detail.element.cmidnumber ?? null;
    if (!cmId) {
        debounceUpdateCourseProgress(container);
        return;
    }

    const item = container.querySelector(`${CM_SELECTOR}[data-id="${cmId}"]`);
    const existing = getModuleMeta(container, cmId) ?? {};
    const sectionElement = item ? item.closest('.courseindex-section') : null;
    const sectionId = sectionElement
        ? Number(sectionElement.dataset.id)
        : existing.sectionid ?? detail.element.sectionid ?? null;
    const rawState = typeof detail.element.completionstate === 'number'
        ? detail.element.completionstate
        : (item ? parseCompletionState(item.querySelector('.completioninfo')) : null);
    const status = mapCompletionStateToStatus(rawState, existing);
    const tracked = detail.element.istrackeduser !== false && (data.user?.istracked ?? true);

    const merged = setModuleMeta(container, Object.assign({}, existing, {
        id: Number(cmId),
        sectionid: sectionId,
        tracked,
        state: typeof rawState === 'number' ? rawState : null,
        status,
    }));

    if (item) {
        setItemStatusFromModule(item, merged, data.strings ?? {});
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
    applyModuleStatuses(container);
    const data = getContainerData(container);
    updateItemStatuses(container, data.strings ?? {});
    if (data.enabled === false || data.user?.istracked === false) {
        container.classList.add(NO_TRACKING_CLASS);
        return;
    }
    container.classList.remove(NO_TRACKING_CLASS);
    updateSectionProgressDisplay(container);
    updateCourseProgress(container);
};

/**
 * Initialise the progress updater component.
 *
 * @param {string} courseIndexId Course index container ID.
 */
export const init = (courseIndexId, courseEditor = null) => {
    const container = document.getElementById(courseIndexId);
    if (!container) {
        return;
    }

    initProgressDisplay(container);

    const editor = courseEditor ?? getCurrentCourseEditor();
    const target = editor?.target ?? document;

    const handleCompletionUpdate = ({detail}) => {
        const data = getContainerData(container);
        if (data.enabled === false || data.user?.istracked === false) {
            return;
        }
        updateProgressForDetail(container, detail);
    };

    const handleTransactionEnd = () => {
        const data = getContainerData(container);
        if (data.enabled === false || data.user?.istracked === false) {
            return;
        }
        applyModuleStatuses(container);
        updateItemStatuses(container, data.strings ?? {});
        updateSectionProgressDisplay(container);
        updateCourseProgress(container);
    };

    target.addEventListener('cm.completionstate:updated', handleCompletionUpdate);
    target.addEventListener('transaction:end', handleTransactionEnd);
};
