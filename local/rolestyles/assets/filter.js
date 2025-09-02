/**
 * Filter out participants without submissions on grading pages when a selected role is active.
 *
 * @package    local_rolestyles
 */
(function() {
    'use strict';

    function hasSelectedRole() {
        return Array.prototype.some.call(document.body.classList, function(cls) {
            return cls.indexOf('roleid-') === 0;
        });
    }

    function filterAssignTable() {
        var table = document.querySelector('#submissions');
        if (!table) {
            return;
        }
        table.querySelectorAll('tbody tr').forEach(function(row) {
            var status = row.querySelector('div.submissionstatus');
            if (status && /no submission/i.test(status.textContent)) {
                row.style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (!hasSelectedRole()) {
            return;
        }
        var url = window.location.pathname + window.location.search;
        if (url.indexOf('/mod/assign/view.php') !== -1 && url.indexOf('action=grading') !== -1) {
            filterAssignTable();
        }
    });
})();

