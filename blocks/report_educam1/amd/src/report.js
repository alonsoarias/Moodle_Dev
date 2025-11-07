/**
 * JavaScript for the report_educam1 report
 *
 * @module     block_report_educam1/report
 * @copyright  2025 IngeWeb - Soluciones para triunfar en Internet
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    /**
     * Initialize the module
     */
    function init() {
        // Alphabet filter functionality
        $('.alphabet-filter a').on('click', function(e) {
            e.preventDefault();
            var letter = $(this).data('letter');
            var target = $(this).data('target');

            // Update the hidden input with the selected letter
            $('#' + target).val(letter);

            // Update the UI to show active letter
            $(this).closest('.alphabet-filter').find('a').removeClass('active');
            $(this).addClass('active');

            // Submit the form
            $('#filter-form').submit();
        });
    }

    return {
        init: init
    };
});
