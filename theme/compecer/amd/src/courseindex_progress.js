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
 * Dynamic progress manager for the course index drawer.
 *
 * @module     theme_compecer/courseindex_progress
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str'], function(Str) {
    const SELECTORS = {
        NAV: '#courseindex',
        GLOBAL_BAR: '[data-region="course-progress-bar"]',
        GLOBAL_PERCENTAGE: '[data-region="course-progress-percentage"]',
        GLOBAL_DETAILS: '[data-region="course-progress-details"]',
        SECTION_PROGRESS: '[data-region="section-progress"]',
        SECTION_LABEL: '[data-region="section-progress-label"]',
        SECTION_BAR: '[data-region="section-progress-bar"]',
        CM_COMPLETION: '[data-for="cm_completion"]',
        CM_ITEM: '[data-for="cm"]',
    };

    const COMPLETION_STATES = {
        COMPLETE: [1, 2],
        FAILED: 3,
        INCOMPLETE: 0,
    };

    const parseJSON = function(value, fallback) {
        if (!value) {
            return fallback;
        }
        try {
            return JSON.parse(value);
        } catch (ex) {
            return fallback;
        }
    };

    const getStateName = function(entry) {
        if (!entry || !entry.tracked) {
            return 'untracked';
        }
        if (entry.state === COMPLETION_STATES.FAILED) {
            return 'failed';
        }
        if (COMPLETION_STATES.COMPLETE.indexOf(entry.state) !== -1) {
            return 'completed';
        }
        if (entry.state === COMPLETION_STATES.INCOMPLETE) {
            return entry.started ? 'inprogress' : 'notstarted';
        }
        return 'untracked';
    };

    class ProgressManager {
        constructor(root, dataset, labels, strings) {
            this.root = root;
            this.strings = strings;
            this.dataset = Object.assign({
                global: {completed: 0, total: 0, percentage: 0, hasprogress: false},
                sections: {},
                modules: {},
            }, dataset);
            this.labels = Object.assign({
                notstarted: '',
                inprogress: '',
                completed: '',
                failed: '',
                untracked: '',
            }, labels);
        }

        init() {
            this.refreshAll();
            this.observe();
        }

        observe() {
            const observer = new MutationObserver(mutations => {
                let requiresRefresh = false;
                let completionUpdated = false;
                mutations.forEach(mutation => {
                    if (mutation.type === 'childList') {
                        requiresRefresh = true;
                    }
                    if (mutation.type === 'attributes' && mutation.attributeName === 'data-value') {
                        completionUpdated = true;
                        this.updateModuleFromElement(mutation.target, false);
                    }
                });
                if (requiresRefresh) {
                    this.refreshAll();
                } else if (completionUpdated) {
                    this.updateTotals();
                }
            });
            observer.observe(this.root, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ['data-value'],
            });
        }

        refreshAll() {
            const completionElements = this.root.querySelectorAll(SELECTORS.CM_COMPLETION);
            completionElements.forEach(element => this.updateModuleFromElement(element, false));
            this.updateTotals();
        }

        updateModuleFromElement(element, updateTotals) {
            const item = element.closest(SELECTORS.CM_ITEM);
            if (!item) {
                return;
            }
            const moduleId = parseInt(item.dataset.id, 10);
            if (!moduleId) {
                return;
            }

            const sectionWrapper = item.closest('[data-for="section"]');
            const sectionId = sectionWrapper ? parseInt(sectionWrapper.dataset.id, 10) : null;
            const currentEntry = Object.assign({
                sectionid: sectionId,
                tracked: false,
                state: null,
                started: false,
            }, this.dataset.modules[moduleId] || {});
            if (!sectionId && !currentEntry.sectionid) {
                return;
            }

            const rawValue = element.dataset.value;
            const stateValue = parseInt(rawValue, 10);
            if (isNaN(stateValue)) {
                currentEntry.tracked = false;
                currentEntry.state = null;
            } else {
                currentEntry.tracked = true;
                currentEntry.state = stateValue;
                if (stateValue !== COMPLETION_STATES.INCOMPLETE) {
                    currentEntry.started = true;
                }
            }
            if (!currentEntry.sectionid && sectionId) {
                currentEntry.sectionid = sectionId;
            }

            this.dataset.modules[moduleId] = currentEntry;
            this.applyVisualState(element, currentEntry);

            if (updateTotals !== false) {
                this.updateTotals();
            }
        }

        applyVisualState(element, entry) {
            const stateName = getStateName(entry);
            element.setAttribute('data-state', stateName);
            const label = this.labels[stateName] || '';
            const labelNode = element.querySelector('[data-region="completion-label"]');
            if (labelNode) {
                labelNode.textContent = label;
            }
            if (label) {
                element.setAttribute('title', label);
                element.setAttribute('aria-label', label);
            } else {
                element.removeAttribute('title');
                element.removeAttribute('aria-label');
            }
        }

        updateTotals() {
            const sections = {};
            let completed = 0;
            let total = 0;

            Object.keys(this.dataset.modules).forEach(key => {
                const entry = this.dataset.modules[key];
                if (!entry || !entry.tracked || !entry.sectionid) {
                    return;
                }
                if (!sections[entry.sectionid]) {
                    sections[entry.sectionid] = {completed: 0, total: 0};
                }
                sections[entry.sectionid].total += 1;
                total += 1;
                if (COMPLETION_STATES.COMPLETE.indexOf(entry.state) !== -1) {
                    sections[entry.sectionid].completed += 1;
                    completed += 1;
                }
            });

            this.dataset.sections = Object.assign({}, this.dataset.sections, sections);
            this.dataset.global = {
                completed: completed,
                total: total,
                percentage: total ? Math.round((completed / total) * 100) : 0,
                hasprogress: total > 0,
            };

            this.renderGlobal();
            this.renderSections();
        }

        renderGlobal() {
            const data = this.dataset.global;
            const bar = this.root.querySelector(SELECTORS.GLOBAL_BAR);
            const percentage = data.hasprogress ? data.percentage : 0;
            if (bar) {
                bar.style.width = percentage + '%';
                const parent = bar.parentElement;
                if (parent) {
                    parent.setAttribute('aria-valuenow', percentage);
                }
            }
            const percentageNode = this.root.querySelector(SELECTORS.GLOBAL_PERCENTAGE);
            if (percentageNode) {
                percentageNode.textContent = percentage + '%';
            }
            const detailsNode = this.root.querySelector(SELECTORS.GLOBAL_DETAILS);
            if (detailsNode) {
                if (data.hasprogress) {
                    detailsNode.textContent = data.completed + ' / ' + data.total + ' ' + this.strings.activities;
                } else {
                    detailsNode.textContent = this.strings.nodata;
                }
            }
        }

        renderSections() {
            const nodes = this.root.querySelectorAll(SELECTORS.SECTION_PROGRESS);
            nodes.forEach(node => {
                const sectionId = parseInt(node.dataset.sectionid, 10);
                const data = this.dataset.sections[sectionId] || {completed: 0, total: 0};
                const percentage = data.total ? Math.round((data.completed / data.total) * 100) : 0;
                const label = node.querySelector(SELECTORS.SECTION_LABEL);
                if (label) {
                    label.textContent = data.completed + '/' + data.total;
                }
                const bar = node.querySelector(SELECTORS.SECTION_BAR);
                if (bar) {
                    bar.style.width = percentage + '%';
                    const parent = bar.parentElement;
                    if (parent) {
                        parent.setAttribute('aria-valuenow', percentage);
                    }
                }
                if (data.total === 0) {
                    node.classList.add('is-empty');
                } else {
                    node.classList.remove('is-empty');
                }
            });
        }
    }

    const init = function() {
        const nav = document.querySelector(SELECTORS.NAV);
        if (!nav) {
            return;
        }
        const dataset = parseJSON(nav.dataset.progressDataset, {});
        const labels = parseJSON(nav.dataset.stateLabels, {});
        Str.get_strings([
            {key: 'courseprogressactivities', component: 'theme_compecer'},
            {key: 'courseprogressnodata', component: 'theme_compecer'},
        ]).then(function(strings) {
            const manager = new ProgressManager(nav, dataset, labels, {
                activities: strings[0],
                nodata: strings[1],
            });
            manager.init();
        }).catch(function() {
            const manager = new ProgressManager(nav, dataset, labels, {
                activities: '',
                nodata: '',
            });
            manager.init();
        });
    };

    return {init: init};
});
