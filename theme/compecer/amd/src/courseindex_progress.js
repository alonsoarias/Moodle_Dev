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
import {eventTypes} from 'core_course/events';

/**
 * Initialize the progress updater.
 *
 * @param {string} courseIndexId The course index container ID
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

    // Listen to completion state changes.
    courseEditor.addEventListener(
        eventTypes.cmCompletion,
        (event) => {
            updateProgress(container, event.detail);
        }
    );

    // Initialize progress display.
    initProgressDisplay(container);
};

/**
 * Initialize progress display from dataset.
 *
 * @param {HTMLElement} container Course index container
 */
const initProgressDisplay = (container) => {
    const dataset = container.dataset.progress;
    if (!dataset) {
        return;
    }

    try {
        const data = JSON.parse(dataset);
        if (!data.enabled) {
            return;
        }

        // Progress is already rendered in the template,
        // this just ensures the display is correct on load.
        updateSectionProgressDisplay(container);
    } catch (error) {
        // eslint-disable-next-line no-console
        console.error('Error parsing progress dataset:', error);
    }
};

/**
 * Update progress indicators after completion change.
 *
 * @param {HTMLElement} container Course index container
 * @param {Object} detail Event detail with completion data
 */
const updateProgress = (container, detail) => {
    if (!detail || !detail.element) {
        return;
    }

    // Update section progress for the affected section.
    const sectionElement = detail.element.closest('.courseindex-section');
    if (sectionElement) {
        updateSectionProgress(sectionElement);
    }

    // Update course progress (debounced).
    debounceUpdateCourseProgress(container);
};

/**
 * Update progress display for a section.
 *
 * @param {HTMLElement} sectionElement Section element
 */
const updateSectionProgress = (sectionElement) => {
    const progressValue = sectionElement.querySelector('[data-region="section-progress-value"]');
    const progressText = sectionElement.querySelector('[data-region="section-progress-text"]');

    if (!progressValue) {
        return;
    }

    // Count completion states in this section.
    const items = sectionElement.querySelectorAll('.courseindex-item');
    let completed = 0;
    let total = 0;

    items.forEach(item => {
        const completionInfo = item.querySelector('.completioninfo');
        if (!completionInfo) {
            return;
        }

        total++;
        const status = item.querySelector('[data-region="cm-status"]');
        if (status && status.classList.contains('completion-enabled')) {
            // Check if item shows as complete.
            if (item.classList.contains('completion-complete')) {
                completed++;
            }
        }
    });

    if (total > 0) {
        const percentage = Math.round((completed / total) * 100);
        progressValue.textContent = `${completed}/${total}`;

        if (progressText) {
            progressText.textContent = `${percentage}% completado`;
        }
    } else {
        progressValue.textContent = '';
        if (progressText) {
            progressText.textContent = '';
        }
    }
};

/**
 * Update progress display for all sections in container.
 *
 * @param {HTMLElement} container Course index container
 */
const updateSectionProgressDisplay = (container) => {
    const sections = container.querySelectorAll('.courseindex-section');
    sections.forEach(section => {
        updateSectionProgress(section);
    });
};

let courseProgressTimeout = null;

/**
 * Debounced update of course progress.
 *
 * @param {HTMLElement} container Course index container
 */
const debounceUpdateCourseProgress = (container) => {
    if (courseProgressTimeout) {
        clearTimeout(courseProgressTimeout);
    }

    courseProgressTimeout = setTimeout(() => {
        updateCourseProgress(container);
    }, 500);
};

/**
 * Update course-level progress indicators.
 *
 * @param {HTMLElement} container Course index container
 */
const updateCourseProgress = (container) => {
    const progressValue = container.querySelector('[data-region="course-progress-value"]');
    const progressSummary = container.querySelector('[data-region="course-progress-summary"]');
    const progressBar = container.querySelector('.courseindex-progress-bar__fill');

    if (!progressValue || !progressBar) {
        return;
    }

    // Count all completion states in course.
    const items = container.querySelectorAll('.courseindex-item');
    let completed = 0;
    let total = 0;

    items.forEach(item => {
        const completionInfo = item.querySelector('.completioninfo');
        if (!completionInfo) {
            return;
        }

        total++;
        const status = item.querySelector('[data-region="cm-status"]');
        if (status && status.classList.contains('completion-enabled')) {
            if (item.classList.contains('completion-complete')) {
                completed++;
            }
        }
    });

    if (total > 0) {
        const percentage = Math.round((completed / total) * 100);
        progressValue.textContent = `${percentage}%`;
        progressBar.style.setProperty('--progress', `${percentage}%`);

        if (progressSummary) {
            progressSummary.textContent = `${completed} de ${total} actividades completadas`;
        }
    }
};