/**
 * Enhance course cards with custom fields and all instructors.
 *
 * This module intercepts course cards after they are rendered and enhances them with:
 * - RemUI custom fields (duration, skill level, etc.)
 * - Complete list of ALL instructors (not just editing teachers)
 *
 * @module     theme_inteb/enhance_course_cards
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates'], function($, Ajax, Templates) {
    'use strict';

    /**
     * Get enhanced course data from inteb's webservice.
     *
     * @param {Array} courseIds Array of course IDs
     * @return {Promise} Promise resolving to enhanced course data
     */
    var getEnhancedCourseData = function(courseIds) {
        var request = {
            methodname: 'theme_inteb_get_enhanced_courses',
            args: {courseids: courseIds}
        };

        return Ajax.call([request])[0];
    };

    /**
     * Extract course ID from a course card element.
     *
     * @param {jQuery} $card Card element
     * @return {Number} Course ID
     */
    var getCourseIdFromCard = function($card) {
        // Try data attribute first
        var courseId = $card.data('course-id');
        if (courseId) {
            return parseInt(courseId, 10);
        }

        // Try extracting from course URL
        var courseUrl = $card.find('.coursename, .view-course-url').first().attr('href');
        if (courseUrl) {
            var match = courseUrl.match(/[?&]id=(\d+)/);
            if (match) {
                return parseInt(match[1], 10);
            }
        }

        return null;
    };

    /**
     * Update a single course card with enhanced data.
     *
     * @param {jQuery} $card Card element
     * @param {Object} courseData Enhanced course data
     */
    var updateCourseCard = function($card, courseData) {
        // Update instructors section
        if (courseData.instructors && courseData.instructors.length > 0) {
            var $instructorsSection = $card.find('.instructors-section');
            if ($instructorsSection.length) {
                // Clear existing instructors
                $instructorsSection.empty();

                // Add all instructors
                courseData.instructors.forEach(function(instructor) {
                    var instructorHtml = '<div class="d-flex flex-row flex-gap-0d5 instructor-item">' +
                        '<div class="d-flex flex-row instructor-info-wrapper">' +
                        '<a href="' + instructor.url + '" class="d-flex align-items-center flex-gap-d5 instructor-img"' +
                        ' aria-label="Instructor is ' + instructor.name + '" title="' + instructor.name + '">' +
                        instructor.picture +
                        '<h6 class="h-regular-6 course-instructors m-0 break-word ellipsis ellipsis-2" title="' +
                        instructor.name + '">' + instructor.name + '</h6>' +
                        '</a>' +
                        '</div>' +
                        '</div>';
                    $instructorsSection.append(instructorHtml);
                });
            }
        }

        // Update custom fields section in edw-card-design-ft
        // La sección .remui-custom-fields-wrapper debe existir en el template
        var $customFieldsWrapper = $card.find('.remui-custom-fields-wrapper');

        if ($customFieldsWrapper.length > 0) {
            // Clear existing content
            $customFieldsWrapper.empty();

            // Add course duration if available
            if (courseData.hascourseduration && courseData.courseduration) {
                var durationHtml = '<div class="custom-field course-duration d-flex align-items-center flex-gap-d5">' +
                    '<span class="edw-icon edw-icon-Time field-icon" aria-hidden="true"></span>' +
                    '<span class="field-value small-info-semibold">' + courseData.courseduration + '</span>' +
                    '</div>';
                $customFieldsWrapper.append(durationHtml);
            }

            // Add skill level if available
            if (courseData.hascourseskilllevel && courseData.courseskilllevel) {
                var skillClass = courseData.courseskillevelclass || '';
                var skillHtml = '<div class="custom-field skill-level d-flex align-items-center flex-gap-d5">' +
                    '<span class="edw-icon edw-icon-Trophy field-icon" aria-hidden="true"></span>' +
                    '<span class="field-value small-info-semibold skill-badge skill-' + skillClass + '">' +
                    courseData.courseskilllevel + '</span>' +
                    '</div>';
                $customFieldsWrapper.append(skillHtml);
            }
        }
    };

    /**
     * Enhance all visible course cards on the page.
     */
    var enhanceCourseCards = function() {
        // Find all course cards including those in recently accessed courses block
        var $cards = $('.course_card-0, .dashboard-card, [data-region="recentlyaccessedcourses-view"] .course_card-0');

        if ($cards.length === 0) {
            return;
        }

        // Extract course IDs from cards
        var courseIds = [];
        var cardsByCourseId = {};

        $cards.each(function() {
            var $card = $(this);
            var courseId = getCourseIdFromCard($card);

            if (courseId && !cardsByCourseId[courseId]) {
                courseIds.push(courseId);
                cardsByCourseId[courseId] = [];
            }

            if (courseId) {
                cardsByCourseId[courseId].push($card);
            }
        });

        if (courseIds.length === 0) {
            return;
        }

        // Fetch enhanced data
        getEnhancedCourseData(courseIds)
            .then(function(response) {
                if (response.courses) {
                    response.courses.forEach(function(courseData) {
                        var $cardsForCourse = cardsByCourseId[courseData.courseid];
                        if ($cardsForCourse) {
                            // Update all instances of this course card
                            $cardsForCourse.forEach(function($card) {
                                updateCourseCard($card, courseData);
                            });
                        }
                    });
                }
                return true;
            })
            .catch(function(error) {
                // Log error but don't break the page
                if (window.console && window.console.error) {
                    window.console.error('Error enhancing course cards:', error);
                }
            });
    };

    /**
     * Initialize the course card enhancement.
     */
    var init = function() {
        // Wait for DOM to be ready
        $(document).ready(function() {
            // Initial enhancement
            enhanceCourseCards();

            // Re-enhance when new cards are loaded (for pagination, filtering, etc.)
            // Watch for changes in the course view regions
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        // Check if course cards were added
                        var $addedCards = $(mutation.addedNodes).find('.course_card-0, .dashboard-card');
                        if ($addedCards.length > 0) {
                            enhanceCourseCards();
                        }
                    }
                });
            });

            // Observe multiple course view containers
            var regionsToObserve = [
                '[data-region="courses-view"]',
                '[data-region="recentlyaccessedcourses-view"]'
            ];

            regionsToObserve.forEach(function(selector) {
                var $region = $(selector);
                if ($region.length) {
                    observer.observe($region[0], {
                        childList: true,
                        subtree: true
                    });
                }
            });
        });
    };

    return {
        init: init
    };
});
