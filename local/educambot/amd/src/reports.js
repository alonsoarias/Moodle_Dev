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
 * Reports page JavaScript module for educambot.
 *
 * @module     local_educambot/reports
 * @copyright  2025 EducamBot Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    'use strict';

    /**
     * Initialize the reports page functionality.
     *
     * @param {Object} strings - Localized strings for the modal
     */
    var init = function(strings) {
        // Date filter conversion.
        var form = document.querySelector('form.educambot-filter-form');
        if (form) {
            form.addEventListener('submit', function() {
                var datefromInput = document.querySelector('input[name="datefrom_date"]');
                var datetoInput = document.querySelector('input[name="dateto_date"]');
                var datefromHidden = document.getElementById('datefrom');
                var datetoHidden = document.getElementById('dateto');

                if (datefromInput && datefromHidden) {
                    if (datefromInput.value) {
                        datefromHidden.value = Math.floor(new Date(datefromInput.value).getTime() / 1000);
                    } else {
                        datefromHidden.value = 0;
                    }
                }

                if (datetoInput && datetoHidden) {
                    if (datetoInput.value) {
                        datetoHidden.value = Math.floor(new Date(datetoInput.value).getTime() / 1000);
                    } else {
                        datetoHidden.value = 0;
                    }
                }
            });
        }

        // Log details modal.
        document.querySelectorAll('.view-log-details').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var question = this.dataset.question;
                var response = this.dataset.response;
                var user = this.dataset.user;
                var date = this.dataset.date;
                var confidence = this.dataset.confidence;
                var matched = this.dataset.matched;

                var statusBadge = matched === '1'
                    ? '<span class="badge bg-success badge-success">' + strings.matched + '</span>'
                    : '<span class="badge bg-warning badge-warning">' + strings.unmatched + '</span>';

                var content = '<dl class="row">' +
                    '<dt class="col-sm-3">' + strings.user + '</dt>' +
                    '<dd class="col-sm-9">' + escapeHtml(user) + '</dd>' +
                    '<dt class="col-sm-3">' + strings.date + '</dt>' +
                    '<dd class="col-sm-9">' + escapeHtml(date) + '</dd>' +
                    '<dt class="col-sm-3">' + strings.status + '</dt>' +
                    '<dd class="col-sm-9">' + statusBadge + '</dd>' +
                    '<dt class="col-sm-3">' + strings.confidence + '</dt>' +
                    '<dd class="col-sm-9">' + escapeHtml(confidence) + '</dd>' +
                    '<dt class="col-sm-3">' + strings.question + '</dt>' +
                    '<dd class="col-sm-9"><div class="alert alert-info">' + escapeHtml(question) + '</div></dd>' +
                    '<dt class="col-sm-3">' + strings.response + '</dt>' +
                    '<dd class="col-sm-9"><div class="alert alert-secondary">' + escapeHtml(response) + '</div></dd>' +
                    '</dl>';

                var modalContent = document.getElementById('modal-content-area');
                if (modalContent) {
                    modalContent.innerHTML = content;
                }

                // Show modal using Bootstrap (supports both BS4 and BS5).
                var modal = document.getElementById('logDetailsModal');
                if (typeof $ !== 'undefined' && $.fn.modal) {
                    // Bootstrap 4 with jQuery.
                    $(modal).modal('show');
                } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    // Bootstrap 5 native.
                    var bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                } else {
                    // Fallback.
                    modal.classList.add('show');
                    modal.style.display = 'block';
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                }
            });
        });

        // Close modal handler for fallback mode.
        var closeButtons = document.querySelectorAll('[data-dismiss="modal"], [data-bs-dismiss="modal"]');
        closeButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var modal = document.getElementById('logDetailsModal');
                if (modal) {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                }
            });
        });
    };

    /**
     * Escape HTML to prevent XSS.
     *
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    var escapeHtml = function(text) {
        if (!text) {
            return '';
        }
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    return {
        init: init
    };
});
