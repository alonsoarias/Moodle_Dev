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
 * Modern Windows Explorer inspired experience for the folder module.
 *
 * @module     mod_folder/windows_explorer
 * @copyright  2024 The Moodle Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    const normalisePath = function(path) {
        if (!path) {
            return '';
        }
        return String(path);
    };

    const getParentPath = function(path) {
        const normalised = normalisePath(path);
        if (!normalised) {
            return '';
        }
        const segments = normalised.split('/').filter(function(segment) {
            return segment !== '';
        });
        segments.pop();
        return segments.join('/');
    };

    const decodePathSegments = function(path) {
        if (!path) {
            return [];
        }

        return path.split('/').map(function(segment) {
            try {
                return decodeURIComponent(segment);
            } catch (error) {
                return segment;
            }
        });
    };

    const decodeHtml = function(value) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = value;
        return textarea.value;
    };

    const init = function(config) {
        const containerId = config && config.containerid;
        const container = containerId ? $('#' + containerId) : $();

        if (!container.length) {
            return;
        }

        const state = {
            currentView: 'grid',
            currentPath: '',
            searchTerm: '',
            history: [''],
            historyIndex: 0,
        };

        const strings = config && config.strings ? config.strings : {};
        const rootName = decodeHtml(config && config.rootname ? config.rootname : '');

        const elements = {
            searchInput: container.find('.folder-search-input'),
            searchClear: container.find('.search-clear'),
            clearSearchButton: container.find('.clear-search-btn'),
            navBack: container.find('.nav-button-back'),
            navForward: container.find('.nav-button-forward'),
            navUp: container.find('.nav-button-up'),
            breadcrumbHost: container.find('.folder-breadcrumbs'),
            viewToggles: container.find('.view-toggle'),
            viewPanels: container.find('.folder-view'),
            gridView: container.find('.folder-view-grid'),
            listView: container.find('.folder-view-list'),
            detailsView: container.find('.folder-view-details'),
            emptyMessage: container.find('.empty-folder-message'),
            noResultsMessage: container.find('.no-results-message'),
            titleValue: container.find('.window-title .title-value'),
            folderViews: container.find('.folder-views'),
        };

        initialiseTree(container, !!(config && config.showexpanded));
        attachEventHandlers();
        setCurrentPath('');

        function attachEventHandlers() {
            elements.searchInput.on('input', handleSearchInput);
            elements.searchClear.on('click', clearSearch);
            elements.clearSearchButton.on('click', clearSearch);

            elements.viewToggles.on('click', function() {
                const button = $(this);
                const targetView = button.data('view');
                if (!targetView || targetView === state.currentView) {
                    return;
                }
                state.currentView = targetView;
                elements.viewToggles.removeClass('active');
                button.addClass('active');
                updateViewVisibility();
            });

            elements.navBack.on('click', function() {
                if (state.historyIndex <= 0) {
                    return;
                }
                state.historyIndex -= 1;
                const target = state.history[state.historyIndex];
                setCurrentPath(target, {fromHistory: true});
            });

            elements.navForward.on('click', function() {
                if (state.historyIndex >= state.history.length - 1) {
                    return;
                }
                state.historyIndex += 1;
                const target = state.history[state.historyIndex];
                setCurrentPath(target, {fromHistory: true});
            });

            elements.navUp.on('click', function() {
                if (!state.currentPath) {
                    return;
                }
                const parentPath = getParentPath(state.currentPath);
                setCurrentPath(parentPath);
            });

            container.on('click', '.folder-tree-toggle', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const node = $(this).closest('.folder-tree-node');
                const willExpand = !node.hasClass('expanded');
                node.toggleClass('expanded', willExpand).toggleClass('collapsed', !willExpand);
                $(this).attr('aria-expanded', willExpand);
            });

            container.on('click', '.folder-tree-label', function(event) {
                event.preventDefault();
                const node = $(this).closest('.folder-tree-node');
                const targetPath = node.attr('data-path') || '';
                if (node.hasClass('has-children')) {
                    expandNode(node);
                }
                setCurrentPath(targetPath);
            });

            container.on('click', '.breadcrumb-button', function(event) {
                event.preventDefault();
                const button = $(this);
                if (button.is(':disabled')) {
                    return;
                }
                const targetPath = button.attr('data-path') || '';
                setCurrentPath(targetPath);
            });

            container.on('click', '.grid-item, .list-item, .details-item', function(event) {
                handleSelection($(this), event);
            });

            container.on('dblclick', '.grid-item, .list-item, .details-item', function() {
                const item = $(this);
                const type = item.data('type');
                if (type === 'folder') {
                    const folderPath = item.data('folder-path') || '';
                    setCurrentPath(folderPath);
                    return;
                }
                const url = item.data('url');
                if (url) {
                    window.open(url, '_blank');
                }
            });
        }

        function handleSearchInput(event) {
            const value = $(event.target).val() || '';
            state.searchTerm = value.toString().toLowerCase().trim();
            toggleClearButton(state.searchTerm.length > 0);
            filterItems();
        }

        function toggleClearButton(visible) {
            if (visible) {
                elements.searchClear.removeAttr('hidden');
            } else {
                elements.searchClear.attr('hidden', true);
            }
        }

        function clearSearch() {
            elements.searchInput.val('');
            state.searchTerm = '';
            toggleClearButton(false);
            filterItems();
            elements.searchInput.trigger('focus');
        }

        function initialiseTree(root, expandAll) {
            root.find('.folder-tree-node.has-children').each(function() {
                const node = $(this);
                const expanded = expandAll || node.hasClass('expanded');
                node.toggleClass('expanded', expanded).toggleClass('collapsed', !expanded);
                const toggle = node.find('> .folder-tree-node-inner .folder-tree-toggle');
                if (toggle.length) {
                    toggle.attr('aria-expanded', expanded);
                }
            });
        }

        function expandNode(node) {
            node.addClass('expanded').removeClass('collapsed');
            const toggle = node.find('> .folder-tree-node-inner .folder-tree-toggle');
            if (toggle.length) {
                toggle.attr('aria-expanded', true);
            }
        }

        function setCurrentPath(path, options) {
            const normalised = normalisePath(path);
            const opts = options || {};

            if (opts.fromHistory !== true) {
                const currentHistoryPath = state.history[state.historyIndex];
                if (currentHistoryPath !== normalised) {
                    state.history = state.history.slice(0, state.historyIndex + 1);
                    state.history.push(normalised);
                    state.historyIndex = state.history.length - 1;
                }
            }

            state.currentPath = normalised;
            clearSelections();
            updateBreadcrumbs();
            highlightTree();
            filterItems();
            updateNavigationButtons();
        }

        function clearSelections() {
            container.find('.grid-item, .list-item, .details-item').removeClass('is-selected');
        }

        function handleSelection(item, event) {
            if (event.ctrlKey || event.metaKey) {
                item.toggleClass('is-selected');
                return;
            }

            if (event.shiftKey) {
                rangeSelect(item);
                return;
            }

            container.find('.grid-item, .list-item, .details-item').removeClass('is-selected');
            item.addClass('is-selected');
        }

        function rangeSelect(targetItem) {
            const visibleItems = container.find('.grid-item:visible, .list-item:visible, .details-item:visible');
            const firstSelected = visibleItems.filter('.is-selected').first();

            if (!firstSelected.length) {
                targetItem.addClass('is-selected');
                return;
            }

            const startIndex = visibleItems.index(firstSelected);
            const endIndex = visibleItems.index(targetItem);
            const start = Math.min(startIndex, endIndex);
            const end = Math.max(startIndex, endIndex);

            container.find('.grid-item, .list-item, .details-item').removeClass('is-selected');
            visibleItems.slice(start, end + 1).addClass('is-selected');
        }

        function filterItems() {
            const items = container.find('.grid-item, .list-item, .details-item');
            const visibleKeys = Object.create(null);
            const totalKeys = Object.create(null);

            items.each(function() {
                const item = $(this);
                const parentPath = item.attr('data-parent-path') || '';
                const keyBase = parentPath + '::' + (item.data('name') || '') + '::' + (item.data('type') || '');
                const matchesPath = parentPath === state.currentPath;

                if (matchesPath) {
                    totalKeys[keyBase] = true;
                }

                let matches = matchesPath;

                if (matches && state.searchTerm) {
                    const itemName = (item.data('name') || '').toString().toLowerCase();
                    if (itemName.indexOf(state.searchTerm) === -1) {
                        matches = false;
                    }
                }

                if (matches) {
                    item.show();
                    item.data('matches', true);
                    visibleKeys[keyBase] = true;
                } else {
                    item.hide();
                    item.removeClass('is-selected');
                    item.data('matches', false);
                }
            });

            const visibleCount = Object.keys(visibleKeys).length;
            const totalInPath = Object.keys(totalKeys).length;

            updateEmptyStates(visibleCount, totalInPath);
            updateViewVisibility();
        }

        function updateViewVisibility() {
            elements.viewPanels.removeClass('active');
            if (state.currentView === 'grid') {
                elements.gridView.addClass('active');
            } else if (state.currentView === 'list') {
                elements.listView.addClass('active');
            } else {
                elements.detailsView.addClass('active');
            }
        }

        function updateEmptyStates(visibleCount, totalCount) {
            if (totalCount === 0) {
                elements.folderViews.attr('hidden', true);
                elements.emptyMessage.removeAttr('hidden');
                elements.noResultsMessage.attr('hidden', true);
                return;
            }

            elements.folderViews.removeAttr('hidden');

            if (visibleCount === 0) {
                elements.emptyMessage.attr('hidden', true);
                elements.noResultsMessage.removeAttr('hidden');
            } else {
                elements.emptyMessage.attr('hidden', true);
                elements.noResultsMessage.attr('hidden', true);
            }
        }

        function updateBreadcrumbs() {
            const breadcrumbs = $('<nav/>', {'aria-label': strings.folderbreadcrumbs || ''});
            const list = $('<ol/>', {'class': 'folder-breadcrumb-list'});
            const segments = decodePathSegments(state.currentPath);
            const encodedSegments = state.currentPath ? state.currentPath.split('/') : [];

            list.append(createBreadcrumbItem(rootName || strings.modulename || '', '', state.currentPath === ''));

            const accumulated = [];
            segments.forEach(function(segment, index) {
                const encoded = encodedSegments[index] || '';
                accumulated.push(encoded);
                const pathValue = accumulated.join('/');
                const isLast = index === segments.length - 1;
                list.append(createBreadcrumbItem(segment, pathValue, isLast));
            });

            breadcrumbs.append(list);
            elements.breadcrumbHost.empty().append(breadcrumbs);

            const titleText = segments.length ? segments[segments.length - 1] : (rootName || strings.modulename || '');
            elements.titleValue.text(titleText);
        }

        function createBreadcrumbItem(label, pathValue, isActive) {
            const item = $('<li/>', {'class': 'folder-breadcrumb-item'});
            const button = $('<button/>', {
                'type': 'button',
                'class': 'breadcrumb-button' + (isActive ? ' is-active' : ''),
                'data-path': pathValue,
            }).text(label || (rootName || strings.modulename || ''));

            if (isActive) {
                button.attr('disabled', true);
            }

            item.append(button);
            return item;
        }

        function highlightTree() {
            const nodes = container.find('.folder-tree-node');
            nodes.removeClass('active');
            const target = nodes.filter(function() {
                return ($(this).attr('data-path') || '') === state.currentPath;
            }).first();

            if (target.length) {
                target.addClass('active');
                target.parents('.folder-tree-node').each(function() {
                    expandNode($(this));
                });
            } else {
                container.find('.folder-tree-node.is-root').first().addClass('active');
            }
        }

        function updateNavigationButtons() {
            elements.navBack.prop('disabled', state.historyIndex <= 0);
            elements.navForward.prop('disabled', state.historyIndex >= state.history.length - 1);
            elements.navUp.prop('disabled', !state.currentPath);
        }
    };

    return {
        init: init,
    };
});
