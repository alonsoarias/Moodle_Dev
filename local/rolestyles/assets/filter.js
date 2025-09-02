/**
 * Filter out participants without submissions or with graded submissions on grading pages when a selected role is active.
 * Provides a visual indicator with counters for visible and hidden participants.
 *
 * @package    local_rolestyles
 */
(function() {
    'use strict';

    /**
     * Check if any selected role class is present on the body tag.
     * @return {boolean}
     */
    function hasSelectedRole() {
        return Array.prototype.some.call(document.body.classList, function(cls) {
            return cls.indexOf('roleid-') === 0;
        });
    }

    /**
     * Hide table rows according to submission and grading patterns.
     *
     * @param {string} tableSelector selector for the table element
     * @param {string|null} statusSelector selector for the element containing submission status
     * @param {Array<RegExp>} statusPatterns regular expressions indicating missing submissions
     * @param {string|null} gradeSelector selector for the element containing grade information
     * @param {Array<RegExp>} gradePatterns regular expressions indicating graded submissions
     * @return {Object} summary with total/visible/hidden counts
     */
    function hideRows(tableSelector, statusSelector, statusPatterns, gradeSelector, gradePatterns) {
        var table = document.querySelector(tableSelector);
        if (!table) {
            return {total: 0, visible: 0, hidden: 0};
        }
        var total = 0, visible = 0, hidden = 0;
        table.querySelectorAll('tbody tr').forEach(function(row) {
            total++;
            var hide = false;
            if (statusSelector) {
                var statusNode = row.querySelector(statusSelector);
                var statusText = statusNode ? statusNode.textContent : '';
                statusPatterns.some(function(pattern) {
                    if (pattern.test(statusText)) {
                        hide = true;
                        return true;
                    }
                    return false;
                });
            }
            if (!hide && gradeSelector) {
                var gradeNode = row.querySelector(gradeSelector);
                var gradeText = gradeNode ? gradeNode.textContent : '';
                gradePatterns.some(function(pattern) {
                    if (pattern.test(gradeText)) {
                        hide = true;
                        return true;
                    }
                    return false;
                });
            }
            if (hide) {
                row.style.display = 'none';
                hidden++;
            } else {
                visible++;
            }
        });
        return {total: total, visible: visible, hidden: hidden};
    }

    /**
     * Display a visual indicator summarising the filtering results.
     *
     * @param {Object} summary counts object
     */
    function showIndicator(summary) {
        if (!summary || summary.hidden === 0) {
            return;
        }
        var container = document.querySelector('div[role="main"]') || document.body;
        var message = M.util.get_string('filterindicator', 'local_rolestyles', summary);
        var indicator = document.createElement('div');
        indicator.className = 'alert alert-info rolestyles-filter-indicator';
        indicator.textContent = message;
        container.insertBefore(indicator, container.firstChild);
    }

    /**
     * Initialise filtering depending on the current activity module page.
     */
    function init() {
        if (!hasSelectedRole()) {
            return;
        }
        var url = window.location.pathname + window.location.search;
        var summary = {total: 0, visible: 0, hidden: 0};
        var result;

        // Assignment grading page.
        if (url.indexOf('/mod/assign/view.php') !== -1 && url.indexOf('action=grading') !== -1) {
            result = hideRows('#submissions', 'div.submissionstatus', [/no submission/i, /sin entrega/i],
                'div.gradingstatus', [/graded/i, /calificado/i]);
            summary.total += result.total; summary.visible += result.visible; summary.hidden += result.hidden;
        }

        // Quiz reports and grading pages.
        if (url.indexOf('/mod/quiz/report.php') !== -1) {
            result = hideRows('#attempts, #attemptsform', null, [/no attempt/i, /not yet started/i, /sin intento/i],
                'td.grade, td.lastcol', [/\d/, /graded/i, /calificado/i]);
            summary.total += result.total; summary.visible += result.visible; summary.hidden += result.hidden;
        }

        // Forum grading interface.
        if (url.indexOf('/mod/forum') !== -1 && url.indexOf('grading') !== -1) {
            result = hideRows('table', null, [/no posts/i, /sin participaci/i],
                'td.score, td.lastcol', [/\d/, /graded/i, /calificado/i]);
            summary.total += result.total; summary.visible += result.visible; summary.hidden += result.hidden;
        }

        // Workshop submission management.
        if (url.indexOf('/mod/workshop') !== -1 && url.indexOf('submissions') !== -1) {
            result = hideRows('#submissions', null, [/not submitted/i, /sin env[íi]o/i],
                'td.grade, td.lastcol', [/\d/, /graded/i, /calificado/i]);
            summary.total += result.total; summary.visible += result.visible; summary.hidden += result.hidden;
        }

        showIndicator(summary);
    }

    document.addEventListener('DOMContentLoaded', init);
})();
