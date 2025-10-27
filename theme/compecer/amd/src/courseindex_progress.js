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
 * Course index progress loader
 *
 * @module     theme_compecer/courseindex_progress
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    /**
     * Initialize the course index progress
     *
     * @param {Number} courseId The course ID
     */
    var init = function(courseId) {

        // Wait for the courseindex to be fully loaded
        var checkInterval = setInterval(function() {
            var sections = $('.course-index-section');
            if (sections.length > 0) {
                clearInterval(checkInterval);
                loadAllSectionsProgress(courseId);
            }
        }, 500);

        // Also listen for courseindex updates
        $(document).on('state-changed', function() {
            loadAllSectionsProgress(courseId);
        });
    };

    /**
     * Load progress for all sections
     *
     * @param {Number} courseId The course ID
     */
    var loadAllSectionsProgress = function(courseId) {
        $('.course-index-section-progress').each(function() {
            var $progressContainer = $(this);
            var sectionId = $progressContainer.data('section-id');

            if (sectionId && !$progressContainer.data('loaded')) {
                loadSectionProgress(courseId, sectionId, $progressContainer);
            }
        });
    };

    /**
     * Load progress for a specific section
     *
     * @param {Number} courseId The course ID
     * @param {Number} sectionId The section ID
     * @param {jQuery} $container The progress container element
     */
    var loadSectionProgress = function(courseId, sectionId, $container) {

        // Call the web service
        var promises = Ajax.call([{
            methodname: 'theme_compecer_get_section_progress',
            args: {
                sectionid: sectionId,
                courseid: courseId
            }
        }]);

        promises[0]
            .then(function(response) {
                // Mark as loaded to prevent repeated attempts
                $container.data('loaded', true);

                if (response && response.hasprogress) {
                    updateProgressDisplay($container, response);
                }
                return;
            })
            .catch(function(error) {
                // Mark as loaded even on error to prevent repeated attempts
                $container.data('loaded', true);

                // Only log errors that are not permission-related
                if (error && error.errorcode !== 'nopermissions') {
                    window.console && console.log('Error loading section progress:', error);
                }
            });
    };

    /**
     * Update the progress display
     *
     * @param {jQuery} $container The progress container element
     * @param {Object} data Progress data
     */
    var updateProgressDisplay = function($container, data) {
        // Verify container still exists in DOM
        if (!$container || $container.length === 0) {
            return;
        }

        var percentage = data.percentage || 0;
        var complete = data.complete || 0;
        var total = data.total || 0;

        // Update progress bar
        var $progressBar = $container.find('.progress-bar');
        if ($progressBar.length > 0) {
            $progressBar
                .css('width', percentage + '%')
                .attr('aria-valuenow', percentage);

            // Color code based on progress
            $progressBar.removeClass('bg-success bg-info bg-warning bg-danger');

            if (percentage === 100) {
                $progressBar.addClass('bg-success');
            } else if (percentage >= 70) {
                $progressBar.addClass('bg-info');
            } else if (percentage >= 40) {
                $progressBar.addClass('bg-warning');
            } else if (percentage > 0) {
                $progressBar.addClass('bg-danger');
            }
        }

        // Update progress text
        var $progressCount = $container.find('.progress-count');
        if ($progressCount.length > 0) {
            $progressCount.text(complete + ' / ' + total);
        }

        var $progressPercentage = $container.find('.progress-percentage');
        if ($progressPercentage.length > 0) {
            $progressPercentage.text(percentage + '%');
        }

        // Show the progress container with animation
        $container.slideDown(300);
    };

    return {
        init: init
    };
});
