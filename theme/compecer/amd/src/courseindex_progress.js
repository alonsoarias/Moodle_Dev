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
 * CourseIndex Progress Enhancement
 *
 * This module adds global course progress and section progress bars
 * to the courseindex drawer.
 *
 * @module     theme_compecer/courseindex_progress
 * @copyright  2025 IngeWeb https://www.ingeweb.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Log from 'core/log';
import Str from 'core/str';

/**
 * Initialize the courseindex progress enhancement
 *
 * @param {Number} courseid - The course ID
 */
export const init = (courseid) => {
    const resolvedCourseId = parseInt(courseid, 10);
    if (!resolvedCourseId) {
        Log.debug('CourseIndex Progress: Course id not provided, aborting');
        return;
    }

    Log.debug('CourseIndex Progress: Initializing for course ' + resolvedCourseId);

    waitForCourseIndex().then(() => {
        Log.debug('CourseIndex Progress: CourseIndex detected, loading progress data');
        return loadProgressData(resolvedCourseId);
    }).then((data) => {
        Log.debug('CourseIndex Progress: Data loaded', data);
        updateGlobalProgress(data.global);
        updateSectionProgress(data.sections);
    }).catch((error) => {
        Log.debug('CourseIndex Progress: Unable to load progress data', error);
    });
};

/**
 * Wait for the courseindex element to be present in the DOM
 *
 * @return {Promise}
 */
const waitForCourseIndex = () => {
    return new Promise((resolve) => {
        const checkInterval = setInterval(() => {
            const courseindex = document.getElementById('courseindex');
            if (courseindex) {
                clearInterval(checkInterval);
                // Wait a bit more for courseindex content to be rendered
                setTimeout(() => resolve(), 500);
            }
        }, 100);

        // Timeout after 10 seconds
        setTimeout(() => {
            clearInterval(checkInterval);
            resolve();
        }, 10000);
    });
};

/**
 * Load progress data from webservice
 *
 * @param {Number} courseid - Course ID
 * @return {Promise}
 */
const loadProgressData = (courseid) => {
    return Ajax.call([{
        methodname: 'theme_compecer_get_course_progress',
        args: {courseid: courseid}
    }])[0];
};

/**
 * Update the global course progress bar with fresh data
 *
 * @param {Object} globalProgress - Global progress data
 */
const updateGlobalProgress = (globalProgress) => {
    const container = document.querySelector('[data-region="course-progress"]');
    if (!container) {
        return;
    }

    if (!globalProgress || !globalProgress.enabled || !globalProgress.total) {
        container.classList.add('d-none');
        return;
    }

    container.classList.remove('d-none');
    container.dataset.progressPercentage = globalProgress.percentage;
    container.dataset.progressCompleted = globalProgress.completed;
    container.dataset.progressTotal = globalProgress.total;

    const percentageElement = container.querySelector('.progress-percentage');
    if (percentageElement) {
        percentageElement.textContent = `${globalProgress.percentage}%`;
    }

    const summaryElement = container.querySelector('.progress-summary');
    if (summaryElement && globalProgress.summary) {
        summaryElement.textContent = globalProgress.summary;
    }

    const progressBar = container.querySelector('.progress-bar');
    if (!progressBar) {
        return;
    }

    progressBar.style.width = `${globalProgress.percentage}%`;
    progressBar.setAttribute('aria-valuenow', globalProgress.percentage);

    const srText = progressBar.querySelector('.sr-only');
    if (srText) {
        setCompletePercentString(srText, globalProgress.percentage);
    }
};

/**
 * Inject section progress bars into courseindex sections
 *
 * @param {Array} sectionsProgress - Array of section progress data
 */
const updateSectionProgress = (sectionsProgress) => {
    if (!Array.isArray(sectionsProgress)) {
        return;
    }

    sectionsProgress.forEach((sectionData) => {
        const sectionContainer = document.querySelector(
            `[data-region="section-progress"][data-section-number="${sectionData.sectionnumber}"]`
        );

        if (!sectionContainer) {
            return;
        }

        if (!sectionData.enabled || !sectionData.total) {
            sectionContainer.classList.add('d-none');
            return;
        }

        sectionContainer.classList.remove('d-none');
        sectionContainer.dataset.progressPercentage = sectionData.percentage;
        sectionContainer.dataset.progressCompleted = sectionData.completed;
        sectionContainer.dataset.progressTotal = sectionData.total;

        const percentageElement = sectionContainer.querySelector('.progress-percentage');
        if (percentageElement) {
            percentageElement.textContent = `${sectionData.percentage}%`;
        }

        const summaryElement = sectionContainer.querySelector('.progress-text');
        if (summaryElement && sectionData.summary) {
            summaryElement.textContent = sectionData.summary;
        }

        const progressBar = sectionContainer.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = `${sectionData.percentage}%`;
            progressBar.setAttribute('aria-valuenow', sectionData.percentage);

            const srText = progressBar.querySelector('.sr-only');
            if (srText) {
                setCompletePercentString(srText, sectionData.percentage);
            }
        }
    });
};

/**
 * Cache for formatted percentage strings.
 *
 * @type {Object.<number, string>}
 */
const percentStringCache = {};

/**
 * Update the "sr-only" text with the localised complete percent string.
 *
 * @param {HTMLElement} element - Element to update
 * @param {number} percentage - Percentage value
 */
const setCompletePercentString = (element, percentage) => {
    if (percentStringCache[percentage]) {
        element.textContent = percentStringCache[percentage];
        return;
    }

    Str.get_string('completepercent', 'theme_compecer', percentage)
        .then((value) => {
            percentStringCache[percentage] = value;
            element.textContent = value;
        })
        .catch((error) => {
            Log.debug('CourseIndex Progress: Failed to load string completepercent', error);
        });
};
