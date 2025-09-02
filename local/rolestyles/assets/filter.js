/**
 * Filter out participants without submissions on grading pages when a selected role is active.
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
     * Hide table rows whose status text matches any of the supplied patterns.
     *
     * @param {string} tableSelector selector for the table element
     * @param {string|null} statusSelector selector for the element containing submission status
     * @param {Array<RegExp>} patterns regular expressions indicating missing submissions
     */
    function hideRows(tableSelector, statusSelector, patterns) {
        var table = document.querySelector(tableSelector);
        if (!table) {
            return;
        }
        table.querySelectorAll('tbody tr').forEach(function(row) {
            var target = statusSelector ? row.querySelector(statusSelector) : row;
            if (!target) {
                return;
            }
            var text = target.textContent || '';
            patterns.some(function(pattern) {
                if (pattern.test(text)) {
                    row.style.display = 'none';
                    return true;
                }
                return false;
            });
        });
    }

    /**
     * Initialise filtering depending on the current activity module page.
     */
    function init() {
        if (!hasSelectedRole()) {
            return;
        }

        var url = window.location.pathname + window.location.search;

        // Assignment grading page.
        if (url.indexOf('/mod/assign/view.php') !== -1 && url.indexOf('action=grading') !== -1) {
            hideRows('#submissions', 'div.submissionstatus', [/no submission/i, /sin entrega/i]);
        }

        // Quiz reports and grading pages.
        if (url.indexOf('/mod/quiz/report.php') !== -1) {
            hideRows('#attempts, #attemptsform', null, [/no attempt/i, /not yet started/i, /sin intento/i]);
        }

        // Forum grading interface.
        if (url.indexOf('/mod/forum') !== -1 && url.indexOf('grading') !== -1) {
            hideRows('table', null, [/no posts/i, /sin participaci/i]);
        }

        // Workshop submission management.
        if (url.indexOf('/mod/workshop') !== -1 && url.indexOf('submissions') !== -1) {
            hideRows('#submissions', null, [/not submitted/i, /sin env[íi]o/i]);
        }
    }

    document.addEventListener('DOMContentLoaded', init);
})();
