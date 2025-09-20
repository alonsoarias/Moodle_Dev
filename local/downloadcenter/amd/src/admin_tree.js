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
 * Administrator tree behaviour for the download center.
 *
 * @module     local_downloadcenter/admin_tree
 * @copyright  2025 Academic Moodle Cooperation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    const SELECTORS = {
        category: '.downloadcenter-category',
        categoryChildren: '.category-children',
        categoryCheckbox: '.category-checkbox',
        course: '.downloadcenter-course',
        courseCheckbox: '.course-checkbox',
        courseFlag: '.course-fullcourse-flag',
        resourceContainer: '.course-resources',
        sectionCheckbox: '.section-checkbox',
        resourceCheckbox: '.resource-item',
        optionCheckbox: '.downloadcenter-option',
        selectionCount: '.selection-count',
        downloadButton: '#download-selection'
    };

    let config = {};
    const pendingRequests = {};
    const hasFetchSupport = typeof window.fetch === 'function';

    /**
     * Call an AJAX service using the configured endpoints.
     *
     * @param {String} serviceKey Configuration key for the service name
     * @param {Object} args Arguments to send
     * @param {AbortController} controller Optional abort controller
     * @return {Promise}
     */
    const callService = function(serviceKey, args, controller) {
        if (!config.ajaxEnabled || !config.services || !config.services[serviceKey]) {
            return Promise.reject(new Error('Service not available.'));
        }

        const methodname = config.services[serviceKey];
        const payload = JSON.stringify([{
            index: 0,
            methodname: methodname,
            args: args || {}
        }]);

        const sesskey = config.sesskey || (window.M && window.M.cfg && window.M.cfg.sesskey) || '';
        const baseurl = (window.M && window.M.cfg && window.M.cfg.wwwroot) || '';
        const info = encodeURIComponent(methodname);

        if (hasFetchSupport) {
            const options = {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: payload
            };

            if (controller && typeof controller.signal !== 'undefined') {
                options.signal = controller.signal;
            }

            return window.fetch(baseurl + '/lib/ajax/service.php?sesskey=' +
                    encodeURIComponent(sesskey) + '&info=' + info, options)
                .then((response) => {
                    if (!response.ok) {
                        const error = new Error('Request failed with status ' + response.status);
                        error.code = response.status;
                        throw error;
                    }
                    return response.json();
                }).then((data) => {
                    if (!Array.isArray(data) || !data.length) {
                        throw new Error('Invalid response from service.');
                    }
                    const first = data[0];
                    if (first.error) {
                        const exception = first.exception || {};
                        const error = new Error(exception.message || first.message || 'Unknown error');
                        error.debuginfo = exception.debuginfo || first.debuginfo;
                        error.errorcode = exception.errorcode || first.errorcode;
                        throw error;
                    }
                    return first.data;
                });
        }

        // Fallback to core/ajax if fetch is not supported
        return Ajax.call([{
            methodname: methodname,
            args: args || {}
        }])[0];
    };

    /**
     * Initialise the module.
     *
     * @param {Object} initialConfig Configuration passed from PHP
     */
    const init = function(initialConfig) {
        config = initialConfig || {};
        config.services = config.services || {};
        const hasAjaxServices = Boolean(
            config.services.categoryChildren &&
            config.services.courseResources
        );
        if (typeof config.ajaxEnabled !== 'boolean') {
            config.ajaxEnabled = hasAjaxServices;
        } else if (config.ajaxEnabled && !hasAjaxServices) {
            // Prevent inconsistent configuration when services are missing.
            config.ajaxEnabled = false;
        }

        document.querySelectorAll(SELECTORS.category).forEach(initialiseCategoryNode);
        document.querySelectorAll(SELECTORS.course).forEach(initialiseCourseNode);
        document.querySelectorAll(SELECTORS.optionCheckbox).forEach((option) => {
            option.addEventListener('change', saveOptions);
        });

        updateSelectionCount(config.selection ? Object.keys(config.selection).length : 0);
    };

    /**
     * Register listeners for a category node.
     *
     * @param {HTMLElement} categoryNode
     */
    const initialiseCategoryNode = function(categoryNode) {
        const summaryCheckbox = categoryNode.querySelector(SELECTORS.categoryCheckbox);
        if (summaryCheckbox) {
            if (summaryCheckbox.dataset.indeterminate) {
                summaryCheckbox.indeterminate = true;
            }
            summaryCheckbox.addEventListener('change', (event) => {
                handleCategoryToggle(event, categoryNode);
            });
        }

        categoryNode.addEventListener('toggle', () => {
            if (categoryNode.open) {
                ensureCategoryChildrenLoaded(categoryNode);
            }
        });
    };

    /**
     * Register listeners for a course node.
     *
     * @param {HTMLElement} courseNode
     */
    const initialiseCourseNode = function(courseNode) {
        const checkbox = courseNode.querySelector(SELECTORS.courseCheckbox);
        if (checkbox) {
            if (checkbox.dataset.indeterminate) {
                checkbox.indeterminate = true;
            }
            checkbox.addEventListener('change', (event) => {
                handleCourseToggle(event, courseNode);
            });
        }

        courseNode.addEventListener('toggle', () => {
            if (courseNode.open) {
                ensureCourseResourcesLoaded(courseNode);
            }
        });
    };

    /**
     * Load category children via AJAX if not yet loaded.
     *
     * @param {HTMLElement} categoryNode
     * @return {Promise}
     */
    const ensureCategoryChildrenLoaded = function(categoryNode) {
        if (!config.ajaxEnabled || !config.services.categoryChildren) {
            return Promise.resolve();
        }

        if (parseInt(categoryNode.dataset.loaded, 10) === 1) {
            return Promise.resolve();
        }
        if (categoryNode._loadingPromise) {
            return categoryNode._loadingPromise;
        }

        const categoryid = parseInt(categoryNode.dataset.categoryid, 10);
        const request = callService('categoryChildren', {categoryid: categoryid});

        categoryNode._loadingPromise = request;
        request.then((response) => {
            const container = categoryNode.querySelector(SELECTORS.categoryChildren);
            container.innerHTML = response.html;
            categoryNode.dataset.loaded = 1;

            container.querySelectorAll(SELECTORS.category).forEach(initialiseCategoryNode);
            container.querySelectorAll(SELECTORS.course).forEach(initialiseCourseNode);
            container.querySelectorAll(SELECTORS.sectionCheckbox).forEach(registerSectionCheckbox);
            container.querySelectorAll(SELECTORS.resourceCheckbox).forEach(registerResourceCheckbox);
            updateSelectionCount(response.selectioncount);
        }).catch((error) => {
            if (error && error.name === 'AbortError') {
                return;
            }
            Notification.exception(error);
        }).then(() => {
            if (categoryNode._loadingPromise === request) {
                delete categoryNode._loadingPromise;
            }
        });

        return request;
    };

    /**
     * Load course resources via AJAX if not yet loaded.
     *
     * @param {HTMLElement} courseNode
     * @return {Promise}
     */
    const ensureCourseResourcesLoaded = function(courseNode) {
        if (!config.ajaxEnabled || !config.services.courseResources) {
            return Promise.resolve();
        }

        if (parseInt(courseNode.dataset.loaded, 10) === 1) {
            return Promise.resolve();
        }
        if (courseNode._loadingPromise) {
            return courseNode._loadingPromise;
        }

        const courseid = parseInt(courseNode.dataset.courseid, 10);
        const request = callService('courseResources', {courseid: courseid});

        courseNode._loadingPromise = request;
        request.then((response) => {
            const container = courseNode.querySelector(SELECTORS.resourceContainer);
            container.innerHTML = response.html;
            courseNode.dataset.loaded = 1;

            container.querySelectorAll(SELECTORS.sectionCheckbox).forEach(registerSectionCheckbox);
            container.querySelectorAll(SELECTORS.resourceCheckbox).forEach(registerResourceCheckbox);
            updateCourseState(courseNode);
        }).catch((error) => {
            if (error && error.name === 'AbortError') {
                return;
            }
            Notification.exception(error);
        }).then(() => {
            if (courseNode._loadingPromise === request) {
                delete courseNode._loadingPromise;
            }
        });

        return request;
    };

    /**
     * Handle category checkbox toggle.
     *
     * @param {Event} event
     * @param {HTMLElement} categoryNode
     */
    const handleCategoryToggle = function(event, categoryNode) {
        const checked = event.target.checked;
        ensureCategoryChildrenLoaded(categoryNode).then(() => {
            categoryNode.querySelectorAll(SELECTORS.courseCheckbox).forEach((courseCheckbox) => {
                courseCheckbox.checked = checked;
                courseCheckbox.indeterminate = false;
                handleCourseToggle({target: courseCheckbox}, courseCheckbox.closest(SELECTORS.course));
            });
            updateCategoryState(categoryNode);
        });
    };

    /**
     * Handle course checkbox toggle.
     *
     * @param {Event} event
     * @param {HTMLElement} courseNode
     */
    const handleCourseToggle = function(event, courseNode) {
        const checked = event.target.checked;
        const fullFlag = courseNode.querySelector(SELECTORS.courseFlag);
        if (fullFlag) {
            fullFlag.disabled = checked ? null : 'disabled';
        }

        const toggleResources = function() {
            const resources = courseNode.querySelectorAll(SELECTORS.resourceCheckbox);
            const sections = courseNode.querySelectorAll(SELECTORS.sectionCheckbox);
            resources.forEach((resource) => {
                resource.checked = checked;
            });
            sections.forEach((section) => {
                section.checked = checked;
            });
            updateCourseState(courseNode);
            persistCourseSelection(courseNode);
        };

        if (checked) {
            // If resources are not yet loaded we can persist full course selection immediately.
            if (parseInt(courseNode.dataset.loaded, 10) === 1) {
                toggleResources();
            } else {
                persistCourseSelection(courseNode, true);
            }
        } else {
            if (parseInt(courseNode.dataset.loaded, 10) === 1) {
                toggleResources();
            } else {
                persistCourseSelection(courseNode, false);
            }
        }

        const parentCategory = courseNode.closest(SELECTORS.category);
        if (parentCategory) {
            updateCategoryState(parentCategory);
            updateCategoryAncestors(parentCategory);
        }
    };

    /**
     * Register section checkbox listeners.
     *
     * @param {HTMLElement} checkbox
     */
    const registerSectionCheckbox = function(checkbox) {
        checkbox.addEventListener('change', () => {
            const courseNode = checkbox.closest(SELECTORS.course);
            const sectionid = checkbox.dataset.sectionid;
            const resources = courseNode.querySelectorAll(
                SELECTORS.resourceCheckbox + '[data-sectionid="' + sectionid + '"]'
            );
            resources.forEach((resource) => {
                resource.checked = checkbox.checked;
            });
            updateCourseState(courseNode);
            persistCourseSelection(courseNode);
            const category = courseNode.closest(SELECTORS.category);
            if (category) {
                updateCategoryState(category);
                updateCategoryAncestors(category);
            }
        });
    };

    /**
     * Register resource checkbox listeners.
     *
     * @param {HTMLElement} checkbox
     */
    const registerResourceCheckbox = function(checkbox) {
        checkbox.addEventListener('change', () => {
            const courseNode = checkbox.closest(SELECTORS.course);
            updateSectionState(courseNode, checkbox.dataset.sectionid);
            updateCourseState(courseNode);
            persistCourseSelection(courseNode);
            const category = courseNode.closest(SELECTORS.category);
            if (category) {
                updateCategoryState(category);
                updateCategoryAncestors(category);
            }
        });
    };

    /**
     * Update the section checkbox state after resource toggles.
     *
     * @param {HTMLElement} courseNode
     * @param {String} sectionid
     */
    const updateSectionState = function(courseNode, sectionid) {
        const sectionCheckbox = courseNode.querySelector(
            SELECTORS.sectionCheckbox + '[data-sectionid="' + sectionid + '"]'
        );
        if (!sectionCheckbox) {
            return;
        }
        const resources = courseNode.querySelectorAll(
            SELECTORS.resourceCheckbox + '[data-sectionid="' + sectionid + '"]'
        );
        let checkedCount = 0;
        resources.forEach((resource) => {
            if (resource.checked) {
                checkedCount++;
            }
        });
        if (checkedCount === 0) {
            sectionCheckbox.checked = false;
            sectionCheckbox.indeterminate = false;
        } else if (checkedCount === resources.length) {
            sectionCheckbox.checked = true;
            sectionCheckbox.indeterminate = false;
        } else {
            sectionCheckbox.checked = true;
            sectionCheckbox.indeterminate = true;
        }
    };

    /**
     * Update course checkbox and hidden flag based on resource state.
     *
     * @param {HTMLElement} courseNode
     */
    const updateCourseState = function(courseNode) {
        const courseCheckbox = courseNode.querySelector(SELECTORS.courseCheckbox);
        if (!courseCheckbox) {
            return;
        }
        const fullFlag = courseNode.querySelector(SELECTORS.courseFlag);
        const resources = courseNode.querySelectorAll(SELECTORS.resourceCheckbox);
        let checkedCount = 0;
        resources.forEach((resource) => {
            if (resource.checked) {
                checkedCount++;
            }
        });

        const counter = courseNode.querySelector('.selection-counter');

        if (!resources.length) {
            courseCheckbox.indeterminate = false;
            if (fullFlag && fullFlag.disabled) {
                courseCheckbox.checked = false;
            }
            if (counter) {
                counter.textContent = (fullFlag && !fullFlag.disabled) ? '∞' : '0';
            }
            return;
        }

        if (checkedCount === 0) {
            courseCheckbox.checked = false;
            courseCheckbox.indeterminate = false;
            if (fullFlag) {
                fullFlag.disabled = 'disabled';
            }
            if (counter) {
                counter.textContent = '0';
            }
        } else if (checkedCount === resources.length) {
            courseCheckbox.checked = true;
            courseCheckbox.indeterminate = false;
            if (fullFlag) {
                fullFlag.disabled = null;
            }
            if (counter) {
                counter.textContent = checkedCount.toString();
            }
        } else {
            courseCheckbox.checked = true;
            courseCheckbox.indeterminate = true;
            if (fullFlag) {
                fullFlag.disabled = 'disabled';
            }
            if (counter) {
                counter.textContent = checkedCount.toString();
            }
        }
    };

    /**
     * Persist the course selection using the external service.
     *
     * @param {HTMLElement} courseNode
     * @param {Boolean} fullcourse Optional flag when no resources are loaded
     */
    const persistCourseSelection = function(courseNode, fullcourse) {
        const courseid = parseInt(courseNode.dataset.courseid, 10);
        const selection = collectCourseSelection(courseNode, fullcourse);
        const payload = JSON.stringify(selection);

        if (!config.ajaxEnabled || !config.services.setSelection) {
            updateSelectionCount(countSelectedCourses());
            return;
        }

        if (pendingRequests[courseid] && typeof pendingRequests[courseid].abort === 'function') {
            pendingRequests[courseid].abort();
        }

        let controller = null;
        let token = {};

        if (hasFetchSupport && typeof window.AbortController === 'function') {
            controller = new AbortController();
            token = controller;
            pendingRequests[courseid] = controller;
        } else {
            pendingRequests[courseid] = token;
        }

        const request = callService('setSelection', {
            courseid: courseid,
            selection: payload
        }, controller);

        request.then((response) => {
            updateSelectionCount(response.selectioncount);
            if (pendingRequests[courseid] === token) {
                delete pendingRequests[courseid];
            }
        }).catch((error) => {
            if (error && error.name === 'AbortError') {
                if (pendingRequests[courseid] === token) {
                    delete pendingRequests[courseid];
                }
                return;
            }
            if (pendingRequests[courseid] === token) {
                delete pendingRequests[courseid];
            }
            Notification.exception(error);
        });
    };

    /**
     * Gather the current selection for the course.
     *
     * @param {HTMLElement} courseNode
     * @param {Boolean} fullcourse
     * @return {Object}
     */
    const collectCourseSelection = function(courseNode, fullcourse) {
        const selection = {};
        const fullFlag = courseNode.querySelector(SELECTORS.courseFlag);
        if (fullcourse === true || (fullFlag && !fullFlag.disabled)) {
            selection['__fullcourse'] = 1;
        }

        courseNode.querySelectorAll(SELECTORS.sectionCheckbox).forEach((sectionCheckbox) => {
            if (sectionCheckbox.checked) {
                selection['item_topic_' + sectionCheckbox.dataset.sectionid] = 1;
            }
        });

        courseNode.querySelectorAll(SELECTORS.resourceCheckbox).forEach((resourceCheckbox) => {
            if (resourceCheckbox.checked) {
                selection[resourceCheckbox.dataset.resourcekey] = 1;
            }
        });

        return selection;
    };

    /**
     * Update category checkbox state.
     *
     * @param {HTMLElement} categoryNode
     */
    const updateCategoryState = function(categoryNode) {
        const categoryCheckbox = categoryNode.querySelector(SELECTORS.categoryCheckbox);
        if (!categoryCheckbox) {
            return;
        }
        const courseCheckboxes = categoryNode.querySelectorAll(SELECTORS.courseCheckbox);
        if (!courseCheckboxes.length) {
            categoryCheckbox.checked = false;
            categoryCheckbox.indeterminate = false;
            return;
        }
        let checkedCount = 0;
        let hasIndeterminate = false;
        courseCheckboxes.forEach((courseCheckbox) => {
            if (courseCheckbox.indeterminate) {
                hasIndeterminate = true;
            }
            if (courseCheckbox.checked) {
                checkedCount++;
            }
        });
        if (checkedCount === 0) {
            categoryCheckbox.checked = false;
            categoryCheckbox.indeterminate = false;
        } else if (checkedCount === courseCheckboxes.length && !hasIndeterminate) {
            categoryCheckbox.checked = true;
            categoryCheckbox.indeterminate = false;
        } else {
            categoryCheckbox.checked = true;
            categoryCheckbox.indeterminate = true;
        }
    };

    /**
     * Update ancestor categories of a category node.
     *
     * @param {HTMLElement} categoryNode
     */
    const updateCategoryAncestors = function(categoryNode) {
        let parent = categoryNode.parentElement ? categoryNode.parentElement.closest(SELECTORS.category) : null;
        while (parent) {
            updateCategoryState(parent);
            parent = parent.parentElement ? parent.parentElement.closest(SELECTORS.category) : null;
        }
    };

    /**
     * Update the selection count indicator.
     *
     * @param {Number} count
     */
    const updateSelectionCount = function(count) {
        const counter = document.querySelector(SELECTORS.selectionCount);
        if (counter) {
            counter.textContent = count;
        }
        const button = document.querySelector(SELECTORS.downloadButton);
        if (button) {
            button.disabled = !count;
        }
    };

    /**
     * Persist download options when changed.
     */
    const saveOptions = function() {
        const options = {};
        document.querySelectorAll(SELECTORS.optionCheckbox).forEach((checkbox) => {
            options[checkbox.dataset.option] = checkbox.checked ? 1 : 0;
        });

        if (!config.ajaxEnabled || !config.services.setOptions) {
            return;
        }

        callService('setOptions', {options: JSON.stringify(options)})
            .catch((error) => {
                if (error && error.name === 'AbortError') {
                    return;
                }
                Notification.exception(error);
            });
    };

    /**
     * Count currently selected courses in the tree.
     *
     * @return {Number}
     */
    const countSelectedCourses = function() {
        let count = 0;
        document.querySelectorAll(SELECTORS.courseCheckbox).forEach((checkbox) => {
            if (checkbox.checked) {
                count++;
            }
        });
        return count;
    };

    return {
        init: init
    };
});