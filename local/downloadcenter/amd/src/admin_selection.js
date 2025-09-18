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
 * Admin selection JavaScript for download center
 *
 * @module     local_downloadcenter/admin_selection
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/str'], 
    function($, Ajax, Notification, Str) {
    
    const AdminSelection = function() {
        this.init();
    };
    
    AdminSelection.prototype.init = function() {
        const self = this;
        
        // Handle course checkbox changes.
        $(document).on('change', '.course-selector input[type="checkbox"]', function() {
            const courseid = $(this).data('courseid');
            const selected = $(this).is(':checked');
            self.toggleCourse(courseid, selected);
        });
        
        // Handle download options changes.
        $(document).on('change', '#downloadoptions input[type="checkbox"]', function() {
            self.saveOptions();
        });
        
        // Category collapse/expand.
        $(document).on('click', '.category-header', function() {
            const $category = $(this).closest('.category-container');
            $category.find('.category-courses').slideToggle();
            $(this).find('.fa').toggleClass('fa-chevron-down fa-chevron-up');
        });
        
        // Update selection count.
        this.updateSelectionCount();
        
        // Initialize tooltips.
        $('[data-toggle="tooltip"]').tooltip();
    };
    
    AdminSelection.prototype.toggleCourse = function(courseid, selected) {
        const self = this;
        
        $.ajax({
            url: M.cfg.wwwroot + '/local/downloadcenter/index.php',
            type: 'POST',
            data: {
                action: 'togglecourse',
                courseid: courseid,
                selected: selected ? 1 : 0,
                sesskey: M.cfg.sesskey
            },
            success: function(response) {
                self.updateSelectionCount();
                
                // Update visual feedback.
                const $checkbox = $('input[data-courseid="' + courseid + '"]');
                const $label = $checkbox.closest('label');
                
                if (selected) {
                    $label.addClass('selected');
                } else {
                    $label.removeClass('selected');
                }
            },
            error: function() {
                Notification.exception(new Error('Failed to update selection'));
            }
        });
    };
    
    AdminSelection.prototype.saveOptions = function() {
        const options = {
            excludestudent: $('#id_excludestudent').is(':checked') ? 1 : 0,
            includefiles: $('#id_includefiles').is(':checked') ? 1 : 0,
            filesrealnames: $('#id_filesrealnames').is(':checked') ? 1 : 0,
            addnumbering: $('#id_addnumbering').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: M.cfg.wwwroot + '/local/downloadcenter/index.php',
            type: 'POST',
            data: Object.assign({
                action: 'updateoptions',
                sesskey: M.cfg.sesskey
            }, options),
            success: function() {
                // Show temporary success message.
                const $feedback = $('#options-feedback');
                if ($feedback.length === 0) {
                    $('<div id="options-feedback" class="alert alert-success">' +
                      'Options saved' + '</div>')
                      .insertAfter('#downloadoptions')
                      .delay(2000)
                      .fadeOut();
                } else {
                    $feedback.show().delay(2000).fadeOut();
                }
            },
            error: function() {
                Notification.exception(new Error('Failed to save options'));
            }
        });
    };
    
    AdminSelection.prototype.updateSelectionCount = function() {
        const count = $('.course-selector input[type="checkbox"]:checked').length;
        $('.selection-count').text(count);
        
        // Enable/disable download button.
        if (count > 0) {
            $('#download-selection').prop('disabled', false);
        } else {
            $('#download-selection').prop('disabled', true);
        }
    };
    
    return {
        init: function() {
            return new AdminSelection();
        }
    };
});