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
define(['core/ajax', 'core/notification', 'core/str'], 
    function(Ajax, Notification, Str) {
    'use strict';
    
    const AdminSelection = function() {
        this.init();
    };
    
    AdminSelection.prototype.init = function() {
        const self = this;
        
        // Handle course checkbox changes.
        document.addEventListener('change', function(e) {
            if (e.target.matches('.course-selector input[type="checkbox"]')) {
                const courseid = e.target.dataset.courseid;
                const selected = e.target.checked;
                self.toggleCourse(courseid, selected);
            }
        });
        
        // Handle download options changes.
        document.addEventListener('change', function(e) {
            if (e.target.matches('#downloadoptions input[type="checkbox"]')) {
                self.saveOptions();
            }
        });
        
        // Category collapse/expand.
        document.addEventListener('click', function(e) {
            if (e.target.closest('.category-header')) {
                const categoryHeader = e.target.closest('.category-header');
                const categoryContainer = categoryHeader.closest('.category-container');
                const categoryCoursesDiv = categoryContainer.querySelector('.category-courses');
                const chevronIcon = categoryHeader.querySelector('.fa');
                
                if (categoryCoursesDiv) {
                    // Toggle display
                    if (categoryCoursesDiv.style.display === 'none' || !categoryCoursesDiv.style.display) {
                        categoryCoursesDiv.style.display = 'block';
                    } else {
                        categoryCoursesDiv.style.display = 'none';
                    }
                    
                    // Toggle chevron
                    if (chevronIcon) {
                        chevronIcon.classList.toggle('fa-chevron-down');
                        chevronIcon.classList.toggle('fa-chevron-up');
                    }
                }
            }
        });
        
        // Update selection count.
        this.updateSelectionCount();
    };
    
    AdminSelection.prototype.toggleCourse = function(courseid, selected) {
        const self = this;
        
        // Create XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', M.cfg.wwwroot + '/local/downloadcenter/index.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                self.updateSelectionCount();
                
                // Update visual feedback.
                const checkbox = document.querySelector('input[data-courseid="' + courseid + '"]');
                const label = checkbox ? checkbox.closest('label') : null;
                
                if (label) {
                    if (selected) {
                        label.classList.add('selected');
                    } else {
                        label.classList.remove('selected');
                    }
                }
            } else {
                Notification.exception(new Error('Failed to update selection'));
            }
        };
        
        xhr.onerror = function() {
            Notification.exception(new Error('Failed to update selection'));
        };
        
        // Send request
        const params = 'action=togglecourse&courseid=' + courseid + 
                      '&selected=' + (selected ? 1 : 0) + 
                      '&sesskey=' + M.cfg.sesskey;
        xhr.send(params);
    };
    
    AdminSelection.prototype.saveOptions = function() {
        const excludeStudentCheckbox = document.getElementById('id_excludestudent');
        const includeFilesCheckbox = document.getElementById('id_includefiles');
        const filesRealNamesCheckbox = document.getElementById('id_filesrealnames');
        const addNumberingCheckbox = document.getElementById('id_addnumbering');
        
        const options = {
            excludestudent: excludeStudentCheckbox && excludeStudentCheckbox.checked ? 1 : 0,
            includefiles: includeFilesCheckbox && includeFilesCheckbox.checked ? 1 : 0,
            filesrealnames: filesRealNamesCheckbox && filesRealNamesCheckbox.checked ? 1 : 0,
            addnumbering: addNumberingCheckbox && addNumberingCheckbox.checked ? 1 : 0
        };
        
        // Create XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', M.cfg.wwwroot + '/local/downloadcenter/index.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Show temporary success message.
                let feedback = document.getElementById('options-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.id = 'options-feedback';
                    feedback.className = 'alert alert-success';
                    feedback.textContent = 'Options saved';
                    
                    const downloadOptions = document.getElementById('downloadoptions');
                    if (downloadOptions && downloadOptions.parentNode) {
                        downloadOptions.parentNode.insertBefore(feedback, downloadOptions.nextSibling);
                    }
                } else {
                    feedback.style.display = 'block';
                }
                
                // Hide after 2 seconds
                setTimeout(function() {
                    if (feedback) {
                        feedback.style.display = 'none';
                    }
                }, 2000);
            } else {
                Notification.exception(new Error('Failed to save options'));
            }
        };
        
        xhr.onerror = function() {
            Notification.exception(new Error('Failed to save options'));
        };
        
        // Build params
        let params = 'action=updateoptions&sesskey=' + M.cfg.sesskey;
        Object.keys(options).forEach(function(key) {
            params += '&' + key + '=' + options[key];
        });
        
        xhr.send(params);
    };
    
    AdminSelection.prototype.updateSelectionCount = function() {
        const checkboxes = document.querySelectorAll('.course-selector input[type="checkbox"]:checked');
        const count = checkboxes.length;
        
        const selectionCountElements = document.querySelectorAll('.selection-count');
        selectionCountElements.forEach(function(elem) {
            elem.textContent = count;
        });
        
        // Enable/disable download button.
        const downloadButton = document.getElementById('download-selection');
        if (downloadButton) {
            downloadButton.disabled = (count === 0);
        }
    };
    
    return {
        init: function() {
            return new AdminSelection();
        }
    };
});
