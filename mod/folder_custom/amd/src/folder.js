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
 * OneDrive-style folder_custom module interactions
 *
 * @module     mod_folder_custom/folder_custom
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/log'], function($, Log) {
    'use strict';

    /**
     * Initialize OneDrive-style view
     * @param {string} containerId Container ID
     * @param {object} options Configuration options
     */
    var initOneDriveView = function(containerId, options) {
        Log.debug('Initializing OneDrive view for container: ' + containerId);

        var container = $('#' + containerId);
        if (!container.length) {
            Log.error('Container not found: ' + containerId);
            return;
        }

        // Store options for later use if needed
        var settings = options || {};
        Log.debug('Settings:', settings);

        // View switcher
        container.find('.folder_custom-view-button').on('click', function(e) {
            e.preventDefault();
            var viewType = $(this).data('view');
            switchView(container, viewType);
        });

        // File/folder_custom card interactions
        container.find('.folder_custom-item-card').each(function() {
            var card = $(this);

            // Click handler
            card.on('click', function(e) {
                // Ignore clicks on checkbox
                if ($(e.target).hasClass('folder_custom-item-checkbox')) {
                    return;
                }
                e.preventDefault();
                handleItemClick(card, e);
            });

            // Double click to open
            card.on('dblclick', function(e) {
                e.preventDefault();
                openItem(card);
            });

            // Right click for context menu
            card.on('contextmenu', function(e) {
                e.preventDefault();
                showContextMenu(e, card, container);
            });

            // Checkbox selection
            card.find('.folder_custom-item-checkbox').on('change', function() {
                if ($(this).is(':checked')) {
                    card.addClass('selected');
                } else {
                    card.removeClass('selected');
                }
                updateSelectionCount(container);
            });
        });

        // Select all checkbox
        container.find('.folder_custom-select-all').on('change', function() {
            var checked = $(this).is(':checked');
            container.find('.folder_custom-item-checkbox').prop('checked', checked).trigger('change');
        });

        // Close context menu on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.folder_custom-context-menu').length) {
                container.find('.folder_custom-context-menu').hide();
            }
        });

        // Context menu actions
        container.find('.folder_custom-context-item').on('click', function() {
            var action = $(this).data('action');
            var contextMenu = $(this).closest('.folder_custom-context-menu');
            var card = contextMenu.data('target-card');

            executeContextAction(action, card);
            contextMenu.hide();
        });

        // Update item count
        updateItemCount(container);

        // Keyboard navigation
        setupKeyboardNavigation(container);

        Log.debug('OneDrive view initialized successfully');
    };

    /**
     * Switch between grid and list view
     * @param {jQuery} container The container element
     * @param {string} viewType The view type (grid or list)
     */
    var switchView = function(container, viewType) {
        container.find('.folder_custom-view-button').removeClass('active');
        container.find('.folder_custom-view-button[data-view="' + viewType + '"]').addClass('active');

        if (viewType === 'grid') {
            container.find('.folder_custom-grid-view').show();
            container.find('.folder_custom-list-view').hide();
        } else {
            container.find('.folder_custom-grid-view').hide();
            container.find('.folder_custom-list-view').show();
        }
    };

    /**
     * Handle single click on item
     * @param {jQuery} card The card element
     * @param {Event} event The click event
     */
    var handleItemClick = function(card, event) {
        // Deselect all if not holding Ctrl/Cmd
        if (!event.ctrlKey && !event.metaKey) {
            card.siblings('.folder_custom-item-card').removeClass('selected');
            card.siblings('.folder_custom-item-card').find('.folder_custom-item-checkbox').prop('checked', false);
        }

        // Toggle selection
        card.toggleClass('selected');
        var checkbox = card.find('.folder_custom-item-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked'));

        updateSelectionCount(card.closest('.folder_custom-onedrive-container'));
    };

    /**
     * Open file or folder_custom
     * @param {jQuery} card The card element
     */
    var openItem = function(card) {
        var url = card.data('url');
        var type = card.data('type');

        if (type === 'folder_custom') {
            var subdirData = card.data('subdir');
            if (subdirData) {
                Log.debug('Opening folder_custom:', card.data('name'));
            }
        } else if (url) {
            window.open(url, '_blank');
        }
    };

    /**
     * Show context menu
     * @param {Event} event The click event
     * @param {jQuery} card The card element
     * @param {jQuery} container The container element
     */
    var showContextMenu = function(event, card, container) {
        var contextMenu = container.find('.folder_custom-context-menu');

        contextMenu.css({
            left: event.pageX + 'px',
            top: event.pageY + 'px',
            display: 'block'
        });

        contextMenu.data('target-card', card);
    };

    /**
     * Execute context menu action
     * @param {string} action The action to execute
     * @param {jQuery} card The card element
     */
    var executeContextAction = function(action, card) {
        var url = card.data('url');
        var name = card.data('name');

        switch (action) {
            case 'open':
                openItem(card);
                break;
            case 'download':
                if (url) {
                    var downloadUrl = url + (url.indexOf('?') > -1 ? '&' : '?') + 'forcedownload=1';
                    window.location.href = downloadUrl;
                }
                break;
            case 'info':
                // eslint-disable-next-line no-alert
                alert('File: ' + name + '\nURL: ' + url);
                break;
        }
    };

    /**
     * Update selection count display
     * @param {jQuery} container The container element
     */
    var updateSelectionCount = function(container) {
        var selected = container.find('.folder_custom-item-card.selected').length;
        if (selected > 0) {
            container.find('.folder_custom-item-count').text(selected + ' selected');
        } else {
            updateItemCount(container);
        }
    };

    /**
     * Update total item count
     * @param {jQuery} container The container element
     */
    var updateItemCount = function(container) {
        var total = container.find('.folder_custom-item-card').length;
        var folder_customs = container.find('.folder_custom-item-card[data-type="folder_custom"]').length;
        var files = total - folder_customs;

        var text = total + ' items';
        if (folder_customs > 0 && files > 0) {
            text = folder_customs + ' folder_customs, ' + files + ' files';
        } else if (folder_customs > 0) {
            text = folder_customs + ' folder_customs';
        } else if (files > 0) {
            text = files + ' files';
        }

        container.find('.folder_custom-item-count').text(text);
    };

    /**
     * Setup keyboard navigation
     * @param {jQuery} container The container element
     */
    var setupKeyboardNavigation = function(container) {
        var keyHandler = function(e) {
            var selected = container.find('.folder_custom-item-card.selected').last();

            if (!selected.length) {
                return;
            }

            var next = null;

            switch (e.key) {
                case 'ArrowRight':
                    next = selected.next('.folder_custom-item-card');
                    break;
                case 'ArrowLeft':
                    next = selected.prev('.folder_custom-item-card');
                    break;
                case 'Enter':
                    openItem(selected);
                    return;
                case 'Escape':
                    container.find('.folder_custom-item-card').removeClass('selected');
                    container.find('.folder_custom-item-checkbox').prop('checked', false);
                    return;
            }

            if (next && next.length) {
                e.preventDefault();
                if (!e.shiftKey) {
                    container.find('.folder_custom-item-card').removeClass('selected');
                    container.find('.folder_custom-item-checkbox').prop('checked', false);
                }
                next.addClass('selected');
                next.find('.folder_custom-item-checkbox').prop('checked', true);
                next[0].scrollIntoView({behavior: 'smooth', block: 'nearest'});
            }
        };

        $(document).on('keydown', keyHandler);
    };

    /**
     * Legacy tree initialization (kept for backward compatibility)
     * @param {string} id Container ID
     * @param {string} expandAll Whether to expand all
     */
    var initTree = function(id, expandAll) {
        Log.warn('initTree is deprecated. Using OneDrive view instead.');
        initOneDriveView(id, {showexpanded: expandAll});
    };

    // Public API
    return {
        initOneDriveView: initOneDriveView,
        initTree: initTree
    };
});