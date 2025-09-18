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
define(['jquery', 'core/ajax', 'core/templates'], function($, Ajax, Templates) {
    
    const CategoryTree = function() {
        this.init();
    };
    
    CategoryTree.prototype.init = function() {
        const self = this;
        
        // Handle category expansion.
        $('.category-expand').on('click', function(e) {
            e.preventDefault();
            const $this = $(this);
            const categoryid = $this.data('categoryid');
            const $container = $this.closest('.category-node').find('.category-courses').first();
            
            if ($container.hasClass('loaded')) {
                $container.slideToggle();
            } else {
                self.loadCategoryCourses(categoryid, $container);
            }
            
            $this.find('i').toggleClass('fa-plus fa-minus');
        });
        
        // Handle category checkbox (select all courses).
        $('.category-checkbox').on('change', function() {
            const $this = $(this);
            const checked = $this.is(':checked');
            const $container = $this.closest('.category-node');
            
            // Update all course checkboxes in this category.
            $container.find('.course-checkbox').prop('checked', checked).trigger('change');
            
            // Update subcategory checkboxes.
            $container.find('.category-checkbox').not(this).prop('checked', checked);
        });
        
        // Initialize tri-state checkboxes.
        this.updateTriStateCheckboxes();
    };
    
    CategoryTree.prototype.loadCategoryCourses = function(categoryid, $container) {
        Ajax.call([{
            methodname: 'local_downloadcenter_get_category_courses',
            args: {categoryid: categoryid},
            done: function(response) {
                Templates.render('local_downloadcenter/course_list', response)
                    .then(function(html) {
                        $container.html(html).addClass('loaded').slideDown();
                    });
            },
            fail: function(error) {
                Notification.exception(error);
            }
        }]);
    };
    
    CategoryTree.prototype.updateTriStateCheckboxes = function() {
        $('.category-node').each(function() {
            const $node = $(this);
            const $checkbox = $node.find('.category-checkbox').first();
            const $courses = $node.find('.course-checkbox');
            
            if ($courses.length === 0) {
                return;
            }
            
            const total = $courses.length;
            const checked = $courses.filter(':checked').length;
            
            if (checked === 0) {
                $checkbox.prop('checked', false);
                $checkbox.prop('indeterminate', false);
            } else if (checked === total) {
                $checkbox.prop('checked', true);
                $checkbox.prop('indeterminate', false);
            } else {
                $checkbox.prop('checked', false);
                $checkbox.prop('indeterminate', true);
            }
        });
    };
    
    return {
        init: function() {
            return new CategoryTree();
        }
    };
});