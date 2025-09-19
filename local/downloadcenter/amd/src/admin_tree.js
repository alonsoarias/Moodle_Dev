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
 * Admin category/course/resource selector logic.
 *
 * @module     local_downloadcenter/admin_tree
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    const triggerChange = function(element) {
        if (typeof window.Event === 'function') {
            element.dispatchEvent(new Event('change', {bubbles: false}));
        } else {
            const event = document.createEvent('HTMLEvents');
            event.initEvent('change', false, false);
            element.dispatchEvent(event);
        }
    };

    const updateFullCourseFlag = function(courseNode, enabled) {
        const fullCourseInput = courseNode.querySelector('.course-fullcourse-flag');
        if (fullCourseInput) {
            fullCourseInput.disabled = !enabled;
        }
    };

    const updateCourseState = function(courseNode) {
        const courseCheckbox = courseNode.querySelector('summary .course-checkbox');
        if (!courseCheckbox) {
            return;
        }

        const resourceCheckboxes = Array.from(
            courseNode.querySelectorAll('.resource-checkbox:not([data-fullcourse])')
        );
        const fallbackCheckbox = courseNode.querySelector('.resource-checkbox[data-fullcourse]');
        const hasResources = resourceCheckboxes.length > 0;

        if (!hasResources) {
            if (fallbackCheckbox) {
                courseCheckbox.checked = fallbackCheckbox.checked;
                courseCheckbox.indeterminate = false;
                updateFullCourseFlag(courseNode, fallbackCheckbox.checked);
            } else {
                courseCheckbox.checked = false;
                courseCheckbox.indeterminate = false;
                updateFullCourseFlag(courseNode, false);
            }
            return;
        }

        const checkedCount = resourceCheckboxes.filter(function(checkbox) {
            return checkbox.checked;
        }).length;

        if (checkedCount === 0) {
            courseCheckbox.checked = false;
            courseCheckbox.indeterminate = false;
            updateFullCourseFlag(courseNode, false);
        } else if (checkedCount === resourceCheckboxes.length) {
            courseCheckbox.checked = true;
            courseCheckbox.indeterminate = false;
            updateFullCourseFlag(courseNode, true);
        } else {
            courseCheckbox.checked = true;
            courseCheckbox.indeterminate = true;
            updateFullCourseFlag(courseNode, false);
        }
    };

    const updateCategoryState = function(categoryNode) {
        const categoryCheckbox = categoryNode.querySelector('summary .category-checkbox');
        if (!categoryCheckbox) {
            return;
        }
        const resourceCheckboxes = categoryNode.querySelectorAll('.resource-checkbox');
        if (!resourceCheckboxes.length) {
            categoryCheckbox.checked = false;
            categoryCheckbox.indeterminate = false;
            return;
        }

        let checkedCount = 0;
        resourceCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                checkedCount++;
            }
        });

        if (checkedCount === 0) {
            categoryCheckbox.checked = false;
            categoryCheckbox.indeterminate = false;
        } else if (checkedCount === resourceCheckboxes.length) {
            categoryCheckbox.checked = true;
            categoryCheckbox.indeterminate = false;
        } else {
            categoryCheckbox.checked = true;
            categoryCheckbox.indeterminate = true;
        }
    };

    const updateCategoryAncestors = function(categoryNode) {
        let parent = categoryNode.parentElement ?
            categoryNode.parentElement.closest('.downloadcenter-category') : null;
        while (parent) {
            updateCategoryState(parent);
            parent = parent.parentElement ? parent.parentElement.closest('.downloadcenter-category') : null;
        }
    };

    const initCourseNode = function(courseNode) {
        const courseCheckbox = courseNode.querySelector('summary .course-checkbox');
        const resourceCheckboxes = Array.from(courseNode.querySelectorAll('.resource-checkbox'));

        if (courseCheckbox) {
            courseCheckbox.addEventListener('change', function() {
                resourceCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = courseCheckbox.checked;
                    triggerChange(checkbox);
                });
                updateCourseState(courseNode);
                const categoryNode = courseNode.closest('.downloadcenter-category');
                if (categoryNode) {
                    updateCategoryState(categoryNode);
                    updateCategoryAncestors(categoryNode);
                }
            });
        }

        resourceCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateCourseState(courseNode);
                const categoryNode = courseNode.closest('.downloadcenter-category');
                if (categoryNode) {
                    updateCategoryState(categoryNode);
                    updateCategoryAncestors(categoryNode);
                }
            });
        });

        updateCourseState(courseNode);
    };

    const initCategoryNode = function(categoryNode) {
        const categoryCheckbox = categoryNode.querySelector('summary .category-checkbox');
        if (categoryCheckbox) {
            categoryCheckbox.addEventListener('change', function() {
                const container = categoryNode.querySelector('.category-children');
                if (container) {
                    container.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                        checkbox.checked = categoryCheckbox.checked;
                        triggerChange(checkbox);
                    });
                }
                updateCategoryState(categoryNode);
                updateCategoryAncestors(categoryNode);
            });
        }

        updateCategoryState(categoryNode);
        updateCategoryAncestors(categoryNode);
    };

    const init = function() {
        // Ensure DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initializeElements();
            });
        } else {
            // DOM is already loaded
            initializeElements();
        }
    };

    const initializeElements = function() {
        document.querySelectorAll('.downloadcenter-course').forEach(function(courseNode) {
            initCourseNode(courseNode);
        });

        document.querySelectorAll('.downloadcenter-category').forEach(function(categoryNode) {
            initCategoryNode(categoryNode);
        });
    };

    return {
        init: init
    };
});
