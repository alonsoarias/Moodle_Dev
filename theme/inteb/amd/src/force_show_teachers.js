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
 * Force show teachers in course header
 *
 * This module prevents any JavaScript from hiding the instructor-info section
 * in the course header after it has been rendered.
 *
 * @module     theme_inteb/force_show_teachers
 * @copyright  2025 Claude Code
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    /**
     * Make instructor-info section always visible
     */
    var forceShowTeachers = function() {

        // Function to ensure instructor-info is visible
        var ensureVisible = function() {
            var $instructorInfo = $('.instructor-info.stat-container');

            if ($instructorInfo.length > 0) {
                // Force display
                $instructorInfo.css({
                    'display': 'flex !important',
                    'visibility': 'visible !important',
                    'opacity': '1 !important'
                });

                // Add a data attribute to mark it as protected
                $instructorInfo.attr('data-inteb-protected', 'true');

                console.log('INTEB: Instructor info made visible:', $instructorInfo.length, 'elements');
            }
        };

        // Run immediately
        ensureVisible();

        // Run after a short delay (in case something tries to hide it)
        setTimeout(ensureVisible, 100);
        setTimeout(ensureVisible, 500);
        setTimeout(ensureVisible, 1000);
        setTimeout(ensureVisible, 2000);

        // Use MutationObserver to watch for any changes
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes') {
                    var $target = $(mutation.target);
                    if ($target.hasClass('instructor-info') ||
                        $target.closest('.instructor-info').length > 0) {
                        // Something tried to modify the instructor-info, re-apply our styles
                        ensureVisible();
                    }
                }
            });
        });

        // Observe changes to the entire page header
        var $header = $('#page-header');
        if ($header.length > 0) {
            observer.observe($header[0], {
                attributes: true,
                attributeFilter: ['style', 'class'],
                subtree: true
            });
        }

        // Also watch for the instructor-info specifically
        $('.instructor-info.stat-container').each(function() {
            observer.observe(this, {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        });

        console.log('INTEB: Force show teachers module initialized');
    };

    return {
        init: function() {
            $(document).ready(function() {
                forceShowTeachers();
            });
        }
    };
});
