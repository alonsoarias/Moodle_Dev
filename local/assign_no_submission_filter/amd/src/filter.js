/**
 * JavaScript module for assignment submission filter and UI modifications
 *
 * @module     local_assign_no_submission_filter/filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str'], function($, Str) {
    
    var initialized = false;
    var userHasRole = false;
    
    /**
     * Initialize the filter and UI modifications
     */
    var init = function(hasRole) {
        if (initialized) {
            return;
        }
        initialized = true;
        
        // Store role status
        userHasRole = hasRole || false;
        
        // Only proceed if user has the required role
        if (!userHasRole) {
            return;
        }
        
        // Wait for DOM ready
        $(document).ready(function() {
            // Apply filtering for grading tables
            applyGradingTableFilter();
            
            // Hide participant count in summary table
            hideParticipantCount();
        });
    };
    
    /**
     * Apply filtering to grading tables (silently)
     */
    var applyGradingTableFilter = function() {
        // Only apply if user has role
        if (!userHasRole) {
            return;
        }
        
        // Only apply to grading tables
        var tables = $('.gradingtable, #submissions');
        if (!tables.length) {
            return;
        }
        
        // Mark table as filtered
        tables.addClass('filtered');
        
        // Find all table rows and hide those without submissions
        $('.gradingtable tbody tr, #submissions tbody tr').each(function() {
            var $row = $(this);
            var hasSubmission = false;
            
            // Check each cell for submission status
            $row.find('td').each(function() {
                var text = $(this).text().trim();
                var $cell = $(this);
                
                // Check for various submission statuses
                if (text.indexOf('Submitted') !== -1 || 
                    text.indexOf('Enviado') !== -1 ||
                    text.indexOf('Draft') !== -1 ||
                    text.indexOf('Borrador') !== -1 ||
                    text.indexOf('Graded') !== -1 ||
                    text.indexOf('Calificado') !== -1 ||
                    text.indexOf('Reenviado') !== -1 ||
                    text.indexOf('Resubmitted') !== -1) {
                    hasSubmission = true;
                    return false; // Break the loop
                }
                
                // Check for grade inputs
                if ($cell.find('input[name^="quickgrade_"]').length > 0) {
                    var gradeValue = $cell.find('input[name^="quickgrade_"]').val();
                    if (gradeValue && gradeValue !== '' && gradeValue !== '-' && gradeValue !== '-1') {
                        hasSubmission = true;
                        return false;
                    }
                }
                
                // Check for grade selects
                if ($cell.find('select[name^="quickgrade_"]').length > 0) {
                    var selectedGrade = $cell.find('select[name^="quickgrade_"]').val();
                    if (selectedGrade && selectedGrade !== '-1') {
                        hasSubmission = true;
                        return false;
                    }
                }
            });
            
            // Hide row if no submission
            if (!hasSubmission) {
                $row.addClass('no-submission-hidden');
            }
        });
    };
    
    /**
     * Hide participant count row in assignment summary table
     */
    var hideParticipantCount = function() {
        // Only apply if user has role
        if (!userHasRole) {
            return;
        }
        
        // Get language strings for both English and Spanish
        Str.get_strings([
            {key: 'numberofparticipants', component: 'assign'},
            {key: 'participants', component: 'moodle'}
        ]).done(function(strings) {
            hideParticipantRowByText(strings);
        }).fail(function() {
            // Fallback with hardcoded strings
            hideParticipantRowByText(['Participants', 'Participantes']);
        });
    };
    
    /**
     * Hide participant row by text content
     */
    var hideParticipantRowByText = function(searchTexts) {
        // Find all general tables (assignment summary tables)
        $('.path-mod-assign .generaltable').each(function() {
            var $table = $(this);
            
            // Skip if this is a grading table
            if ($table.hasClass('gradingtable') || $table.attr('id') === 'submissions') {
                return;
            }
            
            // Find and hide participant row
            $table.find('tr').each(function() {
                var $row = $(this);
                var $th = $row.find('th.cell.c0');
                
                if ($th.length > 0) {
                    var thText = $th.text().trim();
                    
                    // Check against all possible participant strings
                    var shouldHide = false;
                    
                    // Check hardcoded strings
                    if (thText === 'Participants' || 
                        thText === 'Participantes' ||
                        thText === 'Number of participants' ||
                        thText === 'Número de participantes') {
                        shouldHide = true;
                    }
                    
                    // Check against loaded strings
                    if (Array.isArray(searchTexts)) {
                        searchTexts.forEach(function(text) {
                            if (thText.indexOf(text) !== -1) {
                                shouldHide = true;
                            }
                        });
                    }
                    
                    if (shouldHide) {
                        $row.addClass('participant-count-row');
                        $row.hide();
                    }
                }
            });
        });
    };
    
    return {
        init: init
    };
});