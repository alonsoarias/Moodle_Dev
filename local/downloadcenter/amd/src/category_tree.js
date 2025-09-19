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
 * Category tree navigation for download center
 *
 * @module     local_downloadcenter/category_tree
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/templates', 'core/notification'], 
    function(Ajax, Templates, Notification) {
    'use strict';
    
    const CategoryTree = function() {
        this.init();
    };
    
    CategoryTree.prototype.init = function() {
        const self = this;
        
        // Handle category expansion.
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('category-expand')) {
                e.preventDefault();
                const expandBtn = e.target;
                const categoryid = expandBtn.dataset.categoryid;
                const categoryNode = expandBtn.closest('.category-node');
                const container = categoryNode.querySelector('.category-courses');
                
                if (container) {
                    if (container.classList.contains('loaded')) {
                        // Toggle display
                        if (container.style.display === 'none' || !container.style.display) {
                            container.style.display = 'block';
                        } else {
                            container.style.display = 'none';
                        }
                    } else {
                        self.loadCategoryCourses(categoryid, container);
                    }
                    
                    // Toggle icon
                    const icon = expandBtn.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-plus');
                        icon.classList.toggle('fa-minus');
                    }
                }
            }
        });
        
        // Handle category checkbox (select all courses).
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('category-checkbox')) {
                const checkbox = e.target;
                const checked = checkbox.checked;
                const categoryNode = checkbox.closest('.category-node');
                
                if (categoryNode) {
                    // Update all course checkboxes in this category.
                    categoryNode.querySelectorAll('.course-checkbox').forEach(function(cb) {
                        cb.checked = checked;
                        // Trigger change event
                        const event = new Event('change', { bubbles: true });
                        cb.dispatchEvent(event);
                    });
                    
                    // Update subcategory checkboxes.
                    categoryNode.querySelectorAll('.category-checkbox').forEach(function(cb) {
                        if (cb !== checkbox) {
                            cb.checked = checked;
                        }
                    });
                }
            }
        });
        
        // Initialize tri-state checkboxes.
        this.updateTriStateCheckboxes();
    };
    
    CategoryTree.prototype.loadCategoryCourses = function(categoryid, container) {
        Ajax.call([{
            methodname: 'local_downloadcenter_get_category_courses',
            args: {categoryid: categoryid},
            done: function(response) {
                Templates.render('local_downloadcenter/course_list', response)
                    .then(function(html) {
                        container.innerHTML = html;
                        container.classList.add('loaded');
                        container.style.display = 'block';
                    });
            },
            fail: function(error) {
                Notification.exception(error);
            }
        }]);
    };
    
    CategoryTree.prototype.updateTriStateCheckboxes = function() {
        document.querySelectorAll('.category-node').forEach(function(node) {
            const checkbox = node.querySelector('.category-checkbox');
            if (!checkbox) {
                return;
            }
            
            const courses = node.querySelectorAll('.course-checkbox');
            
            if (courses.length === 0) {
                return;
            }
            
            const total = courses.length;
            let checked = 0;
            
            courses.forEach(function(cb) {
                if (cb.checked) {
                    checked++;
                }
            });
            
            if (checked === 0) {
                checkbox.checked = false;
                checkbox.indeterminate = false;
            } else if (checked === total) {
                checkbox.checked = true;
                checkbox.indeterminate = false;
            } else {
                checkbox.checked = false;
                checkbox.indeterminate = true;
            }
        });
    };
    
    return {
        init: function() {
            return new CategoryTree();
        }
    };
});
