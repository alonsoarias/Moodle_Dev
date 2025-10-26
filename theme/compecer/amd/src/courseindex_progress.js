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
import Notification from 'core/notification';

/**
 * Initialize the courseindex progress enhancement
 *
 * @param {Number} courseid - The course ID
 */
export const init = (courseid) => {
    Log.debug('CourseIndex Progress: Initializing for course ' + courseid);

    // Wait for courseindex to be ready
    waitForCourseIndex().then(() => {
        Log.debug('CourseIndex Progress: CourseIndex detected, loading progress data');
        return loadProgressData(courseid);
    }).then((data) => {
        Log.debug('CourseIndex Progress: Data loaded', data);
        injectGlobalProgress(data.global);
        injectSectionProgress(data.sections);
    }).catch((error) => {
        Log.error('CourseIndex Progress: Error', error);
        Notification.exception(error);
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
 * Inject global course progress bar into courseindex
 *
 * @param {Object} globalProgress - Global progress data
 */
const injectGlobalProgress = (globalProgress) => {
    if (!globalProgress.enabled || globalProgress.percentage === 0) {
        return;
    }

    const courseindex = document.getElementById('courseindex');
    if (!courseindex) {
        return;
    }

    // Check if already injected
    if (document.querySelector('.courseindex-progress-global')) {
        return;
    }

    // Create global progress HTML
    const progressHTML = `
        <div class="courseindex-progress-global mt-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="progress-label">${M.util.get_string('courseindexprogresslabel', 'theme_compecer')}</span>
                <span class="progress-percentage">${globalProgress.percentage}%</span>
            </div>
            <div class="progress courseindex-progress-bar">
                <div class="progress-bar bg-success"
                     role="progressbar"
                     style="width: ${globalProgress.percentage}%;"
                     aria-valuenow="${globalProgress.percentage}"
                     aria-valuemin="0"
                     aria-valuemax="100">
                    <span class="sr-only">${globalProgress.percentage}% Complete</span>
                </div>
            </div>
        </div>
    `;

    // Find insertion point (after title, before content)
    const courseindexContent = courseindex.querySelector('#courseindex-content');
    if (courseindexContent) {
        courseindexContent.insertAdjacentHTML('beforebegin', progressHTML);
    }
};

/**
 * Inject section progress bars into courseindex sections
 *
 * @param {Array} sectionsProgress - Array of section progress data
 */
const injectSectionProgress = (sectionsProgress) => {
    sectionsProgress.forEach((sectionData) => {
        if (!sectionData.enabled || sectionData.total === 0) {
            return;
        }

        // Find the section element
        const sectionElement = document.querySelector(`[data-for="section"][data-number="${sectionData.sectionnumber}"]`);
        if (!sectionElement) {
            return;
        }

        // Check if progress already injected
        if (sectionElement.querySelector('.courseindex-section-progress')) {
            return;
        }

        // Create progress text
        const progressText = `${sectionData.completed} of ${sectionData.total} activities completed`;

        // Create section progress HTML
        const progressHTML = `
            <div class="courseindex-section-progress mt-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="progress-text">${progressText}</span>
                    <span class="progress-percentage">${sectionData.percentage}%</span>
                </div>
                <div class="progress courseindex-progress-bar-sm">
                    <div class="progress-bar bg-success"
                         role="progressbar"
                         style="width: ${sectionData.percentage}%;"
                         aria-valuenow="${sectionData.percentage}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        <span class="sr-only">${sectionData.percentage}% Complete</span>
                    </div>
                </div>
            </div>
        `;

        // Find the section header
        const sectionHeader = sectionElement.querySelector('[data-for="section_item"]');
        if (sectionHeader) {
            sectionHeader.insertAdjacentHTML('beforeend', progressHTML);
        }
    });
};
