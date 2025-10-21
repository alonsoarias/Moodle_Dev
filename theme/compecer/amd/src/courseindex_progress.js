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

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Config from 'core/config';
import {BaseComponent} from 'core/reactive';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

const STATUS_COMPLETE = 'complete';
const STATUS_FAILED = 'failed';
const STATUS_INPROGRESS = 'inprogress';
const STATUS_NOTSTARTED = 'notstarted';
const STATUS_NOTRACKED = 'nottracked';

/**
 * Controller responsible for synchronising completion progress metrics inside the course index drawer.
 */
class CourseIndexProgress extends BaseComponent {
    /** @inheritdoc */
    create() {
        this.name = 'compecer-courseindex-progress';
        this.pending = false;
        this.queued = false;
        this.queueRefresh = this.queueRefresh.bind(this);
        this.selectors = {
            COURSE_BLOCK: "[data-region='course-progress']",
            COURSE_VALUE: "[data-region='course-progress-value']",
            COURSE_BAR: "[data-region='course-progress-bar']",
            COURSE_SUMMARY: "[data-region='course-progress-summary']",
            COURSE_A11Y: "[data-region='course-progress-a11y']",
            COURSE_DISABLED: "[data-region='course-progress-disabled']",
            SECTION_PROGRESS: "[data-region='section-progress']",
            SECTION_BAR: "[data-region='section-progress-bar']",
            SECTION_SUMMARY: "[data-region='section-progress-summary']",
            SECTION_FRACTION: "[data-region='section-progress-fraction']",
            SECTION_A11Y: "[data-region='section-progress-a11y']",
            CM_STATUS: "[data-region='cm-status']",
        };
    }

    /**
     * Initialise the component from the mustache template.
     *
     * @param {string} target Target element ID.
     * @return {CourseIndexProgress|null}
     */
    static init(target) {
        const element = document.getElementById(target);
        if (!element) {
            return null;
        }
        return new this({
            element,
            reactive: getCurrentCourseEditor(),
        });
    }

    /** @inheritdoc */
    async stateReady() {
        await this.queueRefresh();
    }

    /** @inheritdoc */
    getWatchers() {
        return [
            {watch: 'cm.completionstate:updated', handler: this.queueRefresh},
            {watch: 'cm:created', handler: this.queueRefresh},
            {watch: 'cm:deleted', handler: this.queueRefresh},
            {watch: 'section.cmlist:updated', handler: this.queueRefresh},
            {watch: 'section:created', handler: this.queueRefresh},
            {watch: 'section:deleted', handler: this.queueRefresh},
        ];
    }

    /**
     * Schedule a refresh operation.
     */
    async queueRefresh() {
        if (this.pending) {
            this.queued = true;
            return;
        }

        this.pending = true;
        try {
            const data = await this.fetchProgress();
            this.applyCourseProgress(data);
            this.applySectionProgress(data.sections);
            this.applyCmStatuses(data.cms);
        } catch (error) {
            Notification.exception(error);
        } finally {
            this.pending = false;
            if (this.queued) {
                this.queued = false;
                this.queueRefresh();
            }
        }
    }

    /**
     * Retrieve the latest progress snapshot from the server.
     *
     * @return {Promise<Object>}
     */
    async fetchProgress() {
        const courseId = this.getCourseId();
        if (!courseId) {
            return {
                completionenabled: false,
                course: {},
                sections: [],
                cms: [],
            };
        }
        const request = Ajax.call([
            {
                methodname: 'theme_compecer_courseindex_progress',
                args: {courseid: courseId},
            }
        ])[0];

        return request;
    }

    /**
     * Determine the current course identifier.
     *
     * @return {number}
     */
    getCourseId() {
        const stateCourse = this.reactive?.state?.course?.id;
        if (stateCourse) {
            return parseInt(stateCourse, 10);
        }
        if (Config.courseId) {
            return parseInt(Config.courseId, 10);
        }
        const body = document.body;
        const dataCourse = body?.dataset?.courseid;
        return dataCourse ? parseInt(dataCourse, 10) : 0;
    }

    /**
     * Update the course level progress block.
     *
     * @param {Object} data Progress payload returned by the server.
     */
    applyCourseProgress(data) {
        const enabled = data.completionenabled ?? false;
        const wrapper = this.element.querySelector(this.selectors.COURSE_BLOCK);
        const disabled = this.element.querySelector(this.selectors.COURSE_DISABLED);
        const course = data.course ?? {};

        if (wrapper) {
            wrapper.hidden = !enabled;
        }
        if (disabled) {
            disabled.hidden = enabled;
        }

        if (!enabled || !wrapper) {
            return;
        }

        const percent = course.percent ?? 0;
        const value = this.element.querySelector(this.selectors.COURSE_VALUE);
        if (value) {
            value.textContent = `${percent}%`;
        }
        const bar = this.element.querySelector(this.selectors.COURSE_BAR);
        if (bar) {
            bar.style.width = `${percent}%`;
            bar.setAttribute('aria-valuenow', percent);
        }
        const summary = this.element.querySelector(this.selectors.COURSE_SUMMARY);
        if (summary) {
            summary.textContent = course.fraction ?? '';
        }
        const a11y = this.element.querySelector(this.selectors.COURSE_A11Y);
        if (a11y) {
            a11y.textContent = course.a11y ?? '';
        }
    }

    /**
     * Update each section progress indicator.
     *
     * @param {Object[]} sections Section payloads.
     */
    applySectionProgress(sections) {
        const sectionMap = new Map();
        sections.forEach((section) => {
            sectionMap.set(String(section.id), section);
        });

        const containers = this.element.querySelectorAll(this.selectors.SECTION_PROGRESS);
        containers.forEach((container) => {
            const sectionId = container.dataset.sectionId;
            const data = sectionMap.get(sectionId);
            const header = container.closest('.courseindex-section-header');
            const fraction = header ? header.querySelector(this.selectors.SECTION_FRACTION) : null;
            if (!data || (data.total ?? 0) === 0) {
                container.hidden = true;
                if (fraction) {
                    fraction.textContent = data?.fraction ?? '';
                }
                return;
            }

            container.hidden = false;
            container.dataset.status = this.resolveSectionStatus(data);

            if (fraction) {
                fraction.textContent = data.fraction ?? '';
            }

            const bar = container.querySelector(this.selectors.SECTION_BAR);
            if (bar) {
                const percent = data.percent ?? 0;
                bar.style.width = `${percent}%`;
                bar.setAttribute('aria-valuenow', percent);
            }

            const summary = container.querySelector(this.selectors.SECTION_SUMMARY);
            if (summary) {
                summary.textContent = data.summary ?? '';
            }

            const a11y = container.querySelector(this.selectors.SECTION_A11Y);
            if (a11y) {
                a11y.textContent = data.a11y ?? '';
            }
        });
    }

    /**
     * Decide the CSS status class for a section.
     *
     * @param {Object} data Section payload.
     * @return {string}
     */
    resolveSectionStatus(data) {
        if ((data.total ?? 0) === 0) {
            return STATUS_NOTRACKED;
        }
        if ((data.completed ?? 0) >= (data.total ?? 0) && (data.total ?? 0) > 0) {
            return STATUS_COMPLETE;
        }
        if ((data.failed ?? 0) > 0) {
            return STATUS_FAILED;
        }
        if ((data.inprogress ?? 0) > 0) {
            return STATUS_INPROGRESS;
        }
        if ((data.notstarted ?? 0) > 0) {
            return STATUS_NOTSTARTED;
        }
        return STATUS_NOTRACKED;
    }

    /**
     * Update module level icons.
     *
     * @param {Object[]} cms CM payloads.
     */
    applyCmStatuses(cms) {
        const statusMap = new Map();
        cms.forEach((cm) => {
            statusMap.set(String(cm.id), cm);
        });

        const wrappers = this.element.querySelectorAll(this.selectors.CM_STATUS);
        wrappers.forEach((wrapper) => {
            const cmid = wrapper.dataset.cmid;
            const data = statusMap.get(cmid);
            if (!data) {
                wrapper.hidden = true;
                return;
            }

            wrapper.hidden = false;
            wrapper.dataset.status = data.status;
            wrapper.setAttribute('title', data.label);
            wrapper.setAttribute('aria-label', data.label);
        });
    }
}

export const init = (target) => {
    return CourseIndexProgress.init(target);
};

export default CourseIndexProgress;
