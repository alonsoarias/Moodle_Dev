/**
 * Manage section and item checkboxes with tri-state behaviour on the course page.
 *
 * @module     local_downloadcenter/section_tree
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core_form/changechecker', 'core/notification'], 
    function($, changechecker, Notification) {
    'use strict';

    /**
     * Update the state of a section checkbox based on its items
     * @param {number} section The section number
     */
    function updateSectionState(section) {
        var $items = $('input.item-checkbox[data-section="' + section + '"]');
        var $sectionbox = $('input.section-checkbox[data-section="' + section + '"]');
        
        if ($items.length === 0) {
            $sectionbox.prop('checked', false);
            $sectionbox.prop('indeterminate', false);
            return;
        }
        
        var checked = $items.filter(':checked').length;
        var total = $items.length;
        
        if (checked === 0) {
            // No items checked
            $sectionbox.prop('checked', false);
            $sectionbox.prop('indeterminate', false);
        } else if (checked === total) {
            // All items checked
            $sectionbox.prop('checked', true);
            $sectionbox.prop('indeterminate', false);
        } else {
            // Some items checked - indeterminate state
            $sectionbox.prop('checked', true);
            $sectionbox.prop('indeterminate', true);
        }
    }

    /**
     * Update selection summary
     */
    function updateSelectionSummary() {
        var totalItems = $('input.item-checkbox').length;
        var checkedItems = $('input.item-checkbox:checked').length;
        
        var $summary = $('#selection-summary');
        if ($summary.length === 0) {
            // Create summary element if it doesn't exist
            $summary = $('<div id="selection-summary" class="alert alert-success mb-3"></div>');
            $('.downloadcenter-toolbar').after($summary);
        }
        
        if (checkedItems > 0) {
            $summary.text(checkedItems + ' of ' + totalItems + ' items selected');
            $summary.show();
        } else {
            $summary.hide();
        }
    }

    /**
     * Save form data via AJAX
     * @param {jQuery} $form The form element
     * @return {Promise} jQuery promise for the AJAX request
     */
    function saveFormData($form) {
        var data = $form.serializeArray();
        data.push({name: 'action', value: 'savecourse'});
        
        // Get courseid from form
        var courseid = $form.find('input[name="courseid"]').val();
        if (!courseid) {
            // Try to get from URL
            var urlParams = new URLSearchParams(window.location.search);
            courseid = urlParams.get('courseid');
        }
        
        if (courseid) {
            data.push({name: 'courseid', value: courseid});
        }
        
        return $.post(M.cfg.wwwroot + '/local/downloadcenter/index.php', $.param(data))
            .done(function(response) {
                if (response.status === 'ok') {
                    // Mark form as clean to prevent "unsaved changes" warnings
                    if (changechecker && changechecker.resetFormDirtyState) {
                        changechecker.resetFormDirtyState($form[0]);
                    }
                    
                    // Update selection summary
                    updateSelectionSummary();
                    
                    // Show temporary success feedback
                    showTemporaryFeedback('Selection saved');
                    
                    // Update save button text if it exists
                    var $saveBtn = $form.find('input[name="submitbutton"]');
                    if ($saveBtn.length > 0) {
                        var checkedCount = $('input.item-checkbox:checked').length;
                        if (checkedCount > 0) {
                            $saveBtn.val(M.str.local_downloadcenter.saveselection + ' (' + checkedCount + ' items)');
                        } else {
                            $saveBtn.val(M.str.local_downloadcenter.saveselection);
                        }
                    }
                }
            })
            .fail(function() {
                Notification.exception(new Error('Failed to save selection'));
            });
    }

    /**
     * Show temporary feedback message
     * @param {string} message The message to display
     */
    function showTemporaryFeedback(message) {
        // Find or create feedback element
        var $feedback = $('#downloadcenter-feedback');
        if ($feedback.length === 0) {
            $feedback = $('<div id="downloadcenter-feedback" class="alert alert-success" ' +
                         'style="position: fixed; top: 60px; right: 20px; z-index: 1050; display: none;">' +
                         '</div>');
            $('body').append($feedback);
        }
        
        // Show feedback
        $feedback.text(message).fadeIn(200);
        
        // Hide after 2 seconds
        setTimeout(function() {
            $feedback.fadeOut(200);
        }, 2000);
    }

    /**
     * Debounce function to limit how often a function can fire
     * @param {Function} func The function to debounce
     * @param {number} wait The debounce delay in milliseconds
     * @return {Function} The debounced function
     */
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    return {
        init: function() {
            var $form = $('form').first();
            
            // Check if we're on the course-specific page
            if ($form.length === 0 || !$form.find('input.section-checkbox').length) {
                return; // Not on the course selection page
            }
            
            // Create debounced save function (wait 800ms after last change)
            var debouncedSave = debounce(function() {
                saveFormData($form);
            }, 800);
            
            // Initialize section checkboxes
            $('input.section-checkbox').each(function() {
                var $sectionbox = $(this);
                var section = $sectionbox.data('section');
                
                // Set initial indeterminate state if specified
                if ($sectionbox.data('indeterminate') === '1' || $sectionbox.data('indeterminate') === 1) {
                    $sectionbox.prop('indeterminate', true);
                }
                
                // Handle section checkbox change
                $sectionbox.on('change', function(e) {
                    e.stopPropagation();
                    
                    var checked = $sectionbox.is(':checked');
                    
                    // Clear indeterminate state when user explicitly checks/unchecks
                    $sectionbox.prop('indeterminate', false);
                    
                    // Update all items in this section
                    $('input.item-checkbox[data-section="' + section + '"]').each(function() {
                        $(this).prop('checked', checked);
                    });
                    
                    // Update selection summary immediately
                    updateSelectionSummary();
                    
                    // Auto-save after change
                    debouncedSave();
                });
            });
            
            // Initialize item checkboxes
            $('input.item-checkbox').each(function() {
                var $itembox = $(this);
                
                // Handle item checkbox change
                $itembox.on('change', function(e) {
                    e.stopPropagation();
                    
                    // Update section state
                    var section = $itembox.data('section');
                    updateSectionState(section);
                    
                    // Update selection summary immediately
                    updateSelectionSummary();
                    
                    // Auto-save after change
                    debouncedSave();
                });
            });
            
            // Calculate initial section states
            $('input.section-checkbox').each(function() {
                var section = $(this).data('section');
                updateSectionState(section);
            });
            
            // Update initial selection summary
            updateSelectionSummary();
            
            // Disable form change checker to prevent "unsaved changes" warnings
            if (changechecker && changechecker.resetFormDirtyState) {
                // Mark form as clean initially
                changechecker.resetFormDirtyState($form[0]);
                
                // Keep form marked as clean after any changes
                $form.on('change', 'input[type="checkbox"]', function() {
                    setTimeout(function() {
                        changechecker.resetFormDirtyState($form[0]);
                    }, 100);
                });
            }
            
            // Handle form submission (if user clicks Save button)
            $form.on('submit', function(e) {
                // Let the form submit normally but mark as clean
                setTimeout(function() {
                    if (changechecker && changechecker.resetFormDirtyState) {
                        changechecker.resetFormDirtyState($form[0]);
                    }
                }, 100);
            });
            
            // Add visual indicator for auto-save status
            var $statusIndicator = $('<div class="downloadcenter-status text-muted small mt-2">' +
                                    '<i class="fa fa-check-circle text-success mr-1"></i>' +
                                    'Auto-save enabled - changes are saved automatically</div>');
            $('.downloadcenter-toolbar').after($statusIndicator);
            
            // Update status indicator during save
            $form.on('ajaxStart', function() {
                $statusIndicator.html('<i class="fa fa-spinner fa-spin mr-1"></i>Saving changes...');
            });
            
            $form.on('ajaxComplete', function() {
                $statusIndicator.html('<i class="fa fa-check-circle text-success mr-1"></i>' +
                                    'Auto-save enabled - changes are saved automatically');
            });
            
            // Add strings for JavaScript
            if (!M.str.local_downloadcenter) {
                M.str.local_downloadcenter = {
                    saveselection: 'Save selection'
                };
            }
        }
    };
});