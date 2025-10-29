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
 * Override format_remuiformat teacher display to show BOTH editing and non-editing teachers
 *
 * This module intercepts AFTER format_remuiformat/headerreplaces has done its work
 * and replaces the teacher list with ALL teachers (including non-editing teachers)
 *
 * @module     theme_inteb/format_remuiformat_teacher_fix
 * @copyright  2025 INTEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/log'], function($, Ajax, Log) {

    /**
     * Replace teachers list with complete data including non-editing teachers
     *
     * @param {number} courseId Course ID
     */
    var replaceTeachersList = function(courseId) {
        Log.debug('[INTEB] format_remuiformat_teacher_fix: Starting teacher replacement for course ' + courseId);

        // Call our web service to get ALL teachers
        var request = {
            methodname: 'theme_inteb_get_course_teachers',
            args: {
                courseid: courseId
            }
        };

        Ajax.call([request])[0]
            .done(function(response) {
                Log.debug('[INTEB] Got teachers data:', response);

                // Find the instructor-info container
                var $instructorInfo = $('.instructor-info.stat-container');

                if ($instructorInfo.length === 0) {
                    Log.debug('[INTEB] instructor-info container not found, creating it');

                    // Create the container if it doesn't exist
                    var $ratingWrapper = $('.rating-instructor-wrapper');
                    if ($ratingWrapper.length > 0) {
                        $ratingWrapper.append('<div class="instructor-info stat-container position-relative"></div>');
                        $instructorInfo = $('.instructor-info.stat-container');
                    } else {
                        Log.warn('[INTEB] rating-instructor-wrapper not found, cannot inject teachers');
                        return;
                    }
                }

                // Clear existing content
                $instructorInfo.empty();

                // Add each teacher
                if (response.teachers && response.teachers.length > 0) {
                    response.teachers.forEach(function(teacher, index) {
                        var teacherHtml = '<div class="position-relative inteb-teacher-item" data-teacher-id="' + teacher.id + '">' +
                            '<a class="view-user-profile-link" href="' + teacher.profileurl + '" title="' + teacher.name + '"></a>' +
                            teacher.avatar +
                            '<span class="titles h-regular-6">' + teacher.name + '</span>' +
                            '</div>';
                        $instructorInfo.append(teacherHtml);
                    });

                    // Add "view all" link if there are more teachers
                    if (response.hasmore) {
                        var viewAllHtml = '<a class="view-all-instructorslink para-semibold-2" href="' + response.participantsurl + '">' +
                            'View all (' + response.totalcount + ')' +
                            '</a>';
                        $instructorInfo.append(viewAllHtml);
                    }

                    // Ensure the container is visible
                    $instructorInfo.show().css({
                        'display': 'flex',
                        'visibility': 'visible',
                        'opacity': '1'
                    });

                    Log.debug('[INTEB] Successfully replaced teachers list with ' + response.teachers.length + ' teachers');
                } else {
                    Log.debug('[INTEB] No teachers found for course ' + courseId);
                }
            })
            .fail(function(error) {
                Log.error('[INTEB] Failed to get teachers:', error);
            });
    };

    /**
     * Initialize the teacher replacement
     *
     * @param {number} courseId Course ID
     */
    var init = function(courseId) {
        Log.debug('[INTEB] format_remuiformat_teacher_fix: Initializing for course ' + courseId);

        // Wait for headerreplaces.js to complete its work
        // We use multiple strategies to ensure we run AFTER the header is replaced

        // Strategy 1: Wait for DOM ready
        $(document).ready(function() {
            // Wait a bit more to ensure headerreplaces has run
            setTimeout(function() {
                replaceTeachersList(courseId);
            }, 500);
        });

        // Strategy 2: Wait for window load
        $(window).on('load', function() {
            setTimeout(function() {
                replaceTeachersList(courseId);
            }, 500);
        });

        // Strategy 3: Use MutationObserver to detect when header is replaced
        if (window.MutationObserver) {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        var $target = $(mutation.target);
                        // Check if this is the header being replaced
                        if ($target.is('#page-header') || $target.find('#page-header').length > 0) {
                            Log.debug('[INTEB] Detected header replacement, updating teachers');
                            setTimeout(function() {
                                replaceTeachersList(courseId);
                            }, 100);
                        }
                    }
                });
            });

            // Observe the page for changes
            var targetNode = document.querySelector('#page');
            if (targetNode) {
                observer.observe(targetNode, {
                    childList: true,
                    subtree: true
                });
            }
        }
    };

    return {
        init: init
    };
});
