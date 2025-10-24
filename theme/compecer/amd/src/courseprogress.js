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
 * Dynamic course progress updater for the Compecer course index drawer.
 *
 * @module     theme_compecer/courseprogress
 * @copyright  2024 IngeWeb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import Str from 'core/str';

export default class Component extends BaseComponent {
    create() {
        this.name = 'theme_compecer_courseprogress';
        this.selectors = {
            CONTAINER: `[data-region='course-progress']`,
            PERCENT: `[data-region='progress-percent']`,
            BAR: `[data-region='progress-bar-fill']`,
            TRACK: `[data-region='progress-track']`,
            META: `[data-region='progress-meta']`,
        };
    }

    static init(target, selectors) {
        return new Component({
            element: document.getElementById(target),
            reactive: getCurrentCourseEditor(),
            selectors,
        });
    }

    stateReady(state) {
        this.updateProgress(state);
    }

    getWatchers() {
        return [
            {watch: 'cm:created', handler: this.updateFromState},
            {watch: 'cm:deleted', handler: this.updateFromState},
            {watch: 'cm:updated', handler: this.updateFromState},
            {watch: 'cm.completionstate:updated', handler: this.updateFromState},
            {watch: 'cm.isoverallcomplete:updated', handler: this.updateFromState},
        ];
    }

    updateFromState({state}) {
        this.updateProgress(state);
    }

    updateProgress(state) {
        const container = this.getElement(this.selectors.CONTAINER);
        if (!container) {
            return;
        }

        const availability = container.dataset.available;
        const isAvailable = availability !== 'false' && availability !== '0' && availability !== '';
        if (!isAvailable) {
            return;
        }

        const stats = this.calculateProgress(state);
        this.renderProgress(stats, container);
    }

    calculateProgress(state) {
        if (!state || !state.cm || typeof state.cm.values !== 'function') {
            return {total: 0, completed: 0, percent: 0};
        }

        let total = 0;
        let completed = 0;
        for (const cm of state.cm.values()) {
            if (!cm || !cm.istrackeduser || typeof cm.completionstate === 'undefined') {
                continue;
            }
            total++;
            if (cm.isoverallcomplete || Number(cm.completionstate) !== 0) {
                completed++;
            }
        }

        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
        return {total, completed, percent};
    }

    renderProgress(stats, container) {
        const percentElement = this.getElement(this.selectors.PERCENT);
        const barElement = this.getElement(this.selectors.BAR);
        const trackElement = this.getElement(this.selectors.TRACK);
        const metaElement = this.getElement(this.selectors.META);

        if (percentElement) {
            percentElement.textContent = `${stats.percent}%`;
        }
        if (barElement) {
            barElement.style.width = `${stats.percent}%`;
        }
        if (trackElement) {
            trackElement.setAttribute('aria-valuenow', stats.percent);
        }

        if (metaElement) {
            const component = container.dataset.summaryComponent;
            const key = container.dataset.summaryKey;
            Str.get_string(key, component, {completed: stats.completed, total: stats.total})
                .then((summary) => {
                    metaElement.textContent = summary;
                    const ariaComponent = container.dataset.ariaComponent;
                    const ariaKey = container.dataset.ariaKey;
                    if (ariaComponent && ariaKey && trackElement) {
                        Str.get_string(ariaKey, ariaComponent, {
                            percent: stats.percent,
                            summary,
                        }).then((label) => trackElement.setAttribute('aria-label', label)).catch(() => {});
                    }
                })
                .catch(() => {});
        }
    }
}
