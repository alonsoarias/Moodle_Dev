/**
 * Manage section and item checkboxes with tri-state behaviour on the course page.
 *
 * @module     local_downloadcenter/section_tree
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 define(['jquery'], function($) {
     'use strict';
 
     function updateSectionState(section) {
         var $items = $('input.item-checkbox[data-section="' + section + '"]');
         var $sectionbox = $('input.section-checkbox[data-section="' + section + '"]');
         var checked = $items.filter(':checked').length;
         var total = $items.length;
         $sectionbox.prop('checked', checked > 0);
         $sectionbox.prop('indeterminate', checked > 0 && checked < total);
         if (checked > 0 && checked < total) {
             $sectionbox.prop('checked', true);
         }
     }
 
     return {
         init: function() {
             $('input.section-checkbox').each(function() {
                 var section = $(this).data('section');
                 if ($(this).data('indeterminate')) {
                     $(this).prop('indeterminate', true);
                 }
                 $(this).on('change', function() {
                     var checked = $(this).is(':checked');
                     $('input.item-checkbox[data-section="' + section + '"]').prop({
                         checked: checked
                     }).trigger('change');
                     $(this).prop('indeterminate', false);
                 });
             });
             $('input.item-checkbox').on('change', function() {
                 updateSectionState($(this).data('section'));
             });
         }
     };
 });
