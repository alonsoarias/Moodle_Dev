/**
 * Manage tri-state category checkboxes on the download center page.
 *
 * @module     local_downloadcenter/category_tree
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core_form/changechecker', 'core/ajax', 'core/notification'], 
    function($, changechecker, Ajax, Notification) {
    'use strict';

    /**
     * Update the state of a category checkbox based on its children
     * @param {jQuery} $checkbox The category checkbox
     */
    function updateCategoryState($checkbox) {
        var $container = $checkbox.closest('.downloadcenter-category');
        
        // Find direct course checkboxes
        var $courseboxes = $container.find('> .collapse > .card-body .course-checkbox');
        
        // Find child category checkboxes
        var $childcategories = $container.find('> .collapse > .card-body > .downloadcenter-category');
        var $subcatboxes = $childcategories.find('> .card-header input.downloadcenter-category-checkbox');
        
        var total = $courseboxes.length + $subcatboxes.length;
        var checked = $courseboxes.filter(':checked').length + $subcatboxes.filter(':checked').length;
        var indeterminateCourses = $courseboxes.filter(function() { 
            return this.indeterminate || $(this).data('indeterminate') === '1'; 
        }).length;
        var indeterminateChildren = $subcatboxes.filter(function() { 
            return this.indeterminate; 
        }).length;
        
        // Update checkbox state
        if (total === 0) {
            $checkbox.prop('checked', false);
            $checkbox.prop('indeterminate', false);
        } else if (checked === total && indeterminateCourses === 0 && indeterminateChildren === 0) {
            // All children are fully checked
            $checkbox.prop('checked', true);
            $checkbox.prop('indeterminate', false);
        } else if (checked > 0 || indeterminateCourses > 0 || indeterminateChildren > 0) {
            // Some children are checked or indeterminate
            $checkbox.prop('checked', true);
            $checkbox.prop('indeterminate', true);
        } else {
            // No children are checked
            $checkbox.prop('checked', false);
            $checkbox.prop('indeterminate', false);
        }
    }

    /**
     * Propagate selection state to all children
     * @param {jQuery} $checkbox The category checkbox
     * @param {boolean} checked The checked state to propagate
     */
    function propagateToChildren($checkbox, checked) {
        var $container = $checkbox.closest('.downloadcenter-category');
        
        // Update all descendant checkboxes
        $container.find('.course-checkbox, .downloadcenter-category-checkbox').not($checkbox).each(function() {
            var $this = $(this);
            
            // Only update if state is different
            if ($this.is(':checked') !== checked || this.indeterminate) {
                $this.prop('checked', checked);
                $this.prop('indeterminate', false);
                $this.removeAttr('data-indeterminate');
                
                // Trigger change event for course checkboxes to save via AJAX
                if ($this.hasClass('course-checkbox')) {
                    $this.trigger('change.autosave');
                }
            }
        });
    }

    /**
     * Save course selection via AJAX
     * @param {number} courseid The course ID
     * @param {boolean} checked Whether the course is selected
     * @return {Promise} jQuery promise for the AJAX request
     */
    function saveCourseSelection(courseid, checked) {
        return $.post(M.cfg.wwwroot + '/local/downloadcenter/index.php', {
            action: 'togglecourse',
            courseid: courseid,
            checked: checked ? 1 : 0,
            sesskey: M.cfg.sesskey
        });
    }

    /**
     * Save category selection via AJAX
     * @param {number} categoryid The category ID
     * @param {string} courseids Comma-separated course IDs
     * @param {boolean} checked Whether the category is selected
     * @return {Promise} jQuery promise for the AJAX request
     */
    function saveCategorySelection(categoryid, courseids, checked) {
        return $.post(M.cfg.wwwroot + '/local/downloadcenter/index.php', {
            action: 'togglecategory',
            categoryid: categoryid,
            courseids: courseids,
            checked: checked ? 1 : 0,
            sesskey: M.cfg.sesskey
        });
    }

    /**
     * Update visual feedback for a course checkbox
     * @param {jQuery} $checkbox The course checkbox
     * @param {boolean} checked Whether the course is selected
     * @param {boolean} partial Whether the selection is partial
     */
    function updateCourseVisualFeedback($checkbox, checked, partial) {
        var $label = $checkbox.siblings('label');
        
        // Remove existing badges
        $label.find('.badge').remove();
        
        // Add appropriate badge
        if (checked && !partial) {
            $label.append(' <span class="badge badge-success">' + 
                         M.str.local_downloadcenter.selected + '</span>');
        } else if (partial) {
            $label.append(' <span class="badge badge-info">' + 
                         M.str.local_downloadcenter.selected + ' (partial)</span>');
        }
    }

    return {
        init: function() {
            // Initialize all category checkboxes
            $('.downloadcenter-category').each(function() {
                var $cat = $(this);
                var $catbox = $cat.find('> .card-header input.downloadcenter-category-checkbox');
                
                // Set initial indeterminate state if specified
                if ($catbox.data('indeterminate') === '1' || $catbox.data('indeterminate') === 1) {
                    $catbox.prop('indeterminate', true);
                }
                
                // Handle category checkbox change
                $catbox.on('change', function(e) {
                    // Prevent event from bubbling to parent categories
                    e.stopPropagation();
                    
                    var checked = $catbox.is(':checked');
                    
                    // Clear indeterminate state when user explicitly checks/unchecks
                    $catbox.prop('indeterminate', false);
                    
                    // Propagate to all children
                    propagateToChildren($catbox, checked);
                    
                    // Update parent categories
                    var $current = $catbox.closest('.downloadcenter-category').parents('.downloadcenter-category').first();
                    while ($current.length) {
                        var $parentbox = $current.find('> .card-header input.downloadcenter-category-checkbox');
                        updateCategoryState($parentbox);
                        $current = $current.parents('.downloadcenter-category').first();
                    }
                    
                    // Save category selection via AJAX if it has course IDs
                    var courseids = $catbox.data('courseids');
                    if (courseids) {
                        var categoryid = $catbox.data('categoryid');
                        saveCategorySelection(categoryid, courseids, checked)
                            .fail(function() {
                                Notification.exception(new Error('Failed to save category selection'));
                                // Revert the checkbox state on failure
                                $catbox.prop('checked', !checked);
                            });
                    }
                });
            });
            
            // Initialize course checkboxes
            $('.course-checkbox').each(function() {
                var $checkbox = $(this);
                
                // Set initial indeterminate state if specified
                if ($checkbox.data('indeterminate') === '1' || $checkbox.data('indeterminate') === 1) {
                    $checkbox.prop('indeterminate', true);
                }
                
                // Handle course checkbox change
                $checkbox.on('change.autosave', function(e) {
                    e.stopPropagation();
                    
                    var courseid = $checkbox.data('courseid');
                    var checked = $checkbox.is(':checked');
                    
                    // Clear indeterminate state when user explicitly checks/unchecks
                    $checkbox.prop('indeterminate', false);
                    $checkbox.removeAttr('data-indeterminate');
                    
                    // Save via AJAX
                    saveCourseSelection(courseid, checked)
                        .done(function(response) {
                            if (response.status === 'ok') {
                                // Update visual feedback
                                updateCourseVisualFeedback($checkbox, checked, false);
                                
                                // Update parent category states
                                var $current = $checkbox.closest('.downloadcenter-category');
                                while ($current.length) {
                                    var $parentbox = $current.find('> .card-header input.downloadcenter-category-checkbox');
                                    updateCategoryState($parentbox);
                                    $current = $current.parents('.downloadcenter-category').first();
                                }
                            }
                        })
                        .fail(function() {
                            Notification.exception(new Error('Failed to save course selection'));
                            // Revert the checkbox state on failure
                            $checkbox.prop('checked', !checked);
                        });
                });
                
                // Also handle regular change events (without auto-save)
                $checkbox.on('change', function(e) {
                    if (!e.namespace || e.namespace !== 'autosave') {
                        // This is a manual change, trigger auto-save
                        $checkbox.trigger('change.autosave');
                    }
                });
            });
            
            // Initial state calculation for all categories
            $('.downloadcenter-category').each(function() {
                var $cat = $(this);
                var $catbox = $cat.find('> .card-header input.downloadcenter-category-checkbox');
                updateCategoryState($catbox);
            });
            
            // Disable form change checker for course selection forms
            $('.downloadcenter-course-form').each(function() {
                var form = this;
                // Prevent "unsaved changes" warnings
                if (typeof changechecker !== 'undefined' && changechecker.resetFormDirtyState) {
                    // Mark form as not dirty whenever a checkbox changes
                    $(form).on('change', 'input[type="checkbox"]', function() {
                        setTimeout(function() {
                            changechecker.resetFormDirtyState(form);
                        }, 100);
                    });
                    
                    // Initially mark as clean
                    changechecker.resetFormDirtyState(form);
                }
            });
            
            // Handle collapse/expand of categories
            $('.downloadcenter-category .collapse').on('shown.bs.collapse hidden.bs.collapse', function() {
                // Recalculate states when categories are expanded/collapsed
                var $cat = $(this).closest('.downloadcenter-category');
                var $catbox = $cat.find('> .card-header input.downloadcenter-category-checkbox');
                updateCategoryState($catbox);
            });
        }
    };
});