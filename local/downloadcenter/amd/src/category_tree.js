/**
 * Manage tri-state category checkboxes on the download center page.
 *
 * @module     local_downloadcenter/category_tree
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core_form/changechecker'], function($, changechecker) {
    'use strict';

    function updateCategoryState($checkbox) {
        var $container = $checkbox.closest('.downloadcenter-category');
        var $courseboxes = $container.find('> .collapse > .card-body > form .course-checkbox');
        var $childcategories = $container.find('> .collapse > .card-body > .downloadcenter-category');
        var $subcatboxes = $childcategories.find('> .card-header input.downloadcenter-category-checkbox');
        var total = $courseboxes.length + $subcatboxes.length;
        var checked = $courseboxes.filter(':checked').length + $subcatboxes.filter(':checked').length;
        var indeterminateChildren = $subcatboxes.filter(function() { return this.indeterminate; }).length;
        $checkbox.prop('checked', total > 0 && checked === total);
        $checkbox.prop('indeterminate', (checked > 0 && checked < total) || indeterminateChildren > 0);
    }

    function propagateToChildren($checkbox, checked) {
        var $container = $checkbox.closest('.downloadcenter-category');
        $container.find('.course-checkbox, .downloadcenter-category-checkbox').not($checkbox).each(function() {
            this.checked = checked;
            this.indeterminate = false;
            $(this).trigger('change');
        });
    }

    return {
        init: function() {
            $('.downloadcenter-category').each(function() {
                var $cat = $(this);
                var $catbox = $cat.find('> .card-header input.downloadcenter-category-checkbox');
                if ($catbox.data('indeterminate')) {
                    $catbox.prop('indeterminate', true);
                }
                $catbox.on('change', function() {
                    propagateToChildren($catbox, $catbox.is(':checked'));
                    var $current = $catbox.closest('.downloadcenter-category').parents('.downloadcenter-category').first();
                    while ($current.length) {
                        var $parentbox = $current.find('> .card-header input.downloadcenter-category-checkbox');
                        updateCategoryState($parentbox);
                        $current = $current.parents('.downloadcenter-category').first();
                    }
                });
                $cat.find('.course-checkbox').each(function() {
                    if ($(this).data('indeterminate')) {
                        $(this).prop('indeterminate', true);
                    }
                }).on('change', function() {
                    var $checkbox = $(this);
                    var $current = $checkbox.closest('.downloadcenter-category');
                    while ($current.length) {
                        var $parentbox = $current.find('> .card-header input.downloadcenter-category-checkbox');
                        updateCategoryState($parentbox);
                        $current = $current.parents('.downloadcenter-category').first();
                    }
                    $.post(M.cfg.wwwroot + '/local/downloadcenter/index.php', {
                        action: 'togglecourse',
                        courseid: $checkbox.data('courseid'),
                        checked: $checkbox.is(':checked') ? 1 : 0,
                        sesskey: M.cfg.sesskey
                    }).done(function() {
                        changechecker.resetFormDirtyState($checkbox.closest('form')[0]);
                    });
                });
            });
        }
    };
});
