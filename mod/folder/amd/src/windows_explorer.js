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
    'use strict';

    const STRING_KEYS = [
        'folderbreadcrumbs',
        'modulename',
        'emptyfolder',
        'noresults',
        'itemcounts',
        'filterresults',
    ];

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

    const parseSortField = function(value) {
        const allowed = ['name', 'modified', 'type', 'size'];
        if (allowed.indexOf(value) !== -1) {
            return value;
        }
        return 'name';
    };

    const formatString = function(template, values) {
        if (!template) {
            return '';
        }
        let output = template;
        const data = values || {};
        if (typeof data.count !== 'undefined') {
            output = output.replace(/\{\$a\}/g, data.count);
        }
        output = output.replace(/\{\$a->(\w+)\}/g, function(match, key) {
            if (Object.prototype.hasOwnProperty.call(data, key)) {
                return data[key];
            }
            return match;
        });
        return output;
    };

    const getSortValue = function(item, field) {
        switch (field) {
            case 'modified':
                return Number(item.data('modified-timestamp')) || 0;
            case 'size':
                return Number(item.data('size-bytes')) || 0;
            case 'type': {
                const type = (item.data('type') || '').toString().toLowerCase();
                if (type === 'folder') {
                    return '0_folder';
                }
                const category = (item.data('file-category') || '').toString().toLowerCase();
                return '1_' + (category || type);
            }
            case 'name':
            default:
                return (item.data('name') || '').toString().toLowerCase();
        }
    };

    const createComparator = function(field, direction) {
        const sortField = parseSortField(field);
        const multiplier = direction === 'desc' ? -1 : 1;

        return function(a, b) {
            const itemA = $(a);
            const itemB = $(b);

            const typeA = (itemA.data('type') || '').toString().toLowerCase();
            const typeB = (itemB.data('type') || '').toString().toLowerCase();

            if (sortField !== 'type') {
                if (typeA === 'folder' && typeB !== 'folder') {
                    return -1;
                }
                if (typeA !== 'folder' && typeB === 'folder') {
                    return 1;
                }
            }

            const valueA = getSortValue(itemA, sortField);
            const valueB = getSortValue(itemB, sortField);

            if (valueA < valueB) {
                return -1 * multiplier;
            }
            if (valueA > valueB) {
                return 1 * multiplier;
            }

            const nameA = (itemA.data('name') || '').toString().toLowerCase();
            const nameB = (itemB.data('name') || '').toString().toLowerCase();

            if (nameA < nameB) {
                return -1 * multiplier;
            }
            if (nameA > nameB) {
                return 1 * multiplier;
            }

            return 0;
        };
    };

    const matchesFilter = function(item, filterValue) {
        const filter = (filterValue || 'all').toString().toLowerCase();

        if (filter === 'all') {
            return true;
        }

        const type = (item.data('type') || '').toString().toLowerCase();
        const category = (item.data('file-category') || '').toString().toLowerCase();

        if (filter === 'folders') {
            return type === 'folder';
        }

        if (filter === 'files') {
            return type !== 'folder';
        }

        if (filter === 'other') {
            return category === 'other' || (!category && type !== 'folder');
        }

        return category === filter;
    };

    const initialiseExplorer = function(container, config, rootName, strings) {
        const resolvedConfig = config || {};
        const resolvedStrings = strings || {};
        const resolvedRootName = rootName || resolvedStrings.modulename || '';

        const state = {
            currentView: 'grid',
            currentPath: '',
            searchTerm: '',
            sortField: 'name',
            sortDirection: 'asc',
            filterCategory: 'all',
            history: [''],
            historyIndex: 0,
        };

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
            folderViews: container.find('.folder-views'),
            sortField: container.find('.folder-sort-field'),
            sortDirectionButtons: container.find('.sort-direction'),
            filterSelect: container.find('.folder-filter'),
            resultSummary: container.find('.result-summary'),
            gridContainer: container.find('.folder-view-grid .grid-container'),
            listTableBody: container.find('.folder-view-list tbody'),
            detailsTableBody: container.find('.folder-view-details tbody'),
        };

        state.sortField = parseSortField(elements.sortField.val());
        state.sortDirection = elements.sortDirectionButtons.filter('.active').first().data('direction') === 'desc' ? 'desc' : 'asc';
        state.filterCategory = (elements.filterSelect.val() || 'all').toString();

        elements.sortDirectionButtons.each(function() {
            const button = $(this);
            const isActive = button.hasClass('active');
            button.attr('aria-pressed', isActive);
        });

        initialiseTree(container, !!resolvedConfig.showexpanded);
        attachEventHandlers();
        setCurrentPath('');

        function attachEventHandlers() {
            elements.searchInput.on('input', handleSearchInput);
            elements.searchClear.on('click', clearSearch);
            elements.clearSearchButton.on('click', clearSearch);

            elements.sortField.on('change', function() {
                state.sortField = parseSortField($(this).val());
                sortItems();
                filterItems();
            });

            elements.sortDirectionButtons.on('click', function() {
                const button = $(this);
                const direction = button.data('direction');
                if (!direction || direction === state.sortDirection) {
                    return;
                }
                state.sortDirection = direction === 'desc' ? 'desc' : 'asc';
                elements.sortDirectionButtons.removeClass('active').attr('aria-pressed', false);
                button.addClass('active').attr('aria-pressed', true);
                sortItems();
                filterItems();
            });

            elements.filterSelect.on('change', function() {
                state.filterCategory = ($(this).val() || 'all').toString();
                filterItems();
            });

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
                updateToggleIcon(node, willExpand);
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
                const type = (item.data('type') || '').toString();
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
                updateToggleIcon(node, expanded);
            });
        }

        function updateToggleIcon(node, expanded) {
            const toggle = node.find('> .folder-tree-node-inner .folder-tree-toggle');
            if (toggle.length) {
                toggle.attr('aria-expanded', expanded);
                const icon = toggle.find('i');
                if (icon.length) {
                    icon.toggleClass('fa-chevron-down', expanded);
                    icon.toggleClass('fa-chevron-right', !expanded);
                }
            }
        }

        function expandNode(node) {
            node.addClass('expanded').removeClass('collapsed');
            updateToggleIcon(node, true);
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
            sortItems();
            filterItems();
            updateNavigationButtons();
        }

        function clearSelections() {
            container.find('.grid-item, .list-item, .details-item').removeClass('is-selected');
            updateSelectionStyles();
        }

        function handleSelection(item, event) {
            if (event.ctrlKey || event.metaKey) {
                item.toggleClass('is-selected');
                updateSelectionStyles();
                return;
            }

            if (event.shiftKey) {
                rangeSelect(item);
                return;
            }

            container.find('.grid-item, .list-item, .details-item').removeClass('is-selected');
            item.addClass('is-selected');
            updateSelectionStyles();
        }

        function rangeSelect(targetItem) {
            const visibleItems = container.find('.grid-item:visible, .list-item:visible, .details-item:visible');
            const firstSelected = visibleItems.filter('.is-selected').first();

            if (!firstSelected.length) {
                targetItem.addClass('is-selected');
                updateSelectionStyles();
                return;
            }

            const startIndex = visibleItems.index(firstSelected);
            const endIndex = visibleItems.index(targetItem);
            const start = Math.min(startIndex, endIndex);
            const end = Math.max(startIndex, endIndex);

            container.find('.grid-item, .list-item, .details-item').removeClass('is-selected');
            visibleItems.slice(start, end + 1).addClass('is-selected');
            updateSelectionStyles();
        }

        function sortItems() {
            const comparator = createComparator(state.sortField, state.sortDirection);

            if (elements.gridContainer.length) {
                const gridItems = elements.gridContainer.children('.grid-item').get();
                gridItems.sort(comparator);
                gridItems.forEach(function(item) {
                    elements.gridContainer.append(item);
                });
            }

            if (elements.listTableBody.length) {
                const listRows = elements.listTableBody.children('.list-item').get();
                listRows.sort(comparator);
                listRows.forEach(function(row) {
                    elements.listTableBody.append(row);
                });
            }

            if (elements.detailsTableBody.length) {
                const detailRows = elements.detailsTableBody.children('.details-item').get();
                detailRows.sort(comparator);
                detailRows.forEach(function(row) {
                    elements.detailsTableBody.append(row);
                });
            }
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
                    matches = matchesFilter(item, state.filterCategory);
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

            updateSelectionStyles();

            const visibleCount = Object.keys(visibleKeys).length;
            const totalInPath = Object.keys(totalKeys).length;

            updateEmptyStates(visibleCount, totalInPath);
            updateViewVisibility();
            updateResultSummary(visibleCount, totalInPath);
        }

        function updateResultSummary(visibleCount, totalCount) {
            if (!elements.resultSummary.length) {
                return;
            }

            let message = '';

            if (totalCount === 0) {
                message = resolvedStrings.emptyfolder || '';
            } else if (visibleCount === 0) {
                message = resolvedStrings.noresults || '';
            } else if (visibleCount === totalCount) {
                message = formatString(resolvedStrings.itemcounts, {count: totalCount});
            } else {
                message = formatString(resolvedStrings.filterresults, {
                    visible: visibleCount,
                    total: totalCount,
                });
            }

            elements.resultSummary.text(message);
        }

        function updateViewVisibility() {
            elements.viewPanels.removeClass('active').attr('aria-hidden', true);
            if (state.currentView === 'grid') {
                elements.gridView.addClass('active').attr('aria-hidden', false);
            } else if (state.currentView === 'list') {
                elements.listView.addClass('active').attr('aria-hidden', false);
            } else {
                elements.detailsView.addClass('active').attr('aria-hidden', false);
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
            const breadcrumbs = $('<nav/>', {'aria-label': resolvedStrings.folderbreadcrumbs || ''});
            const list = $('<ol/>', {'class': 'breadcrumb mb-0 folder-breadcrumb-list'});
            const segments = decodePathSegments(state.currentPath);
            const encodedSegments = state.currentPath ? state.currentPath.split('/') : [];

            list.append(createBreadcrumbItem(resolvedRootName || resolvedStrings.modulename || '', '', state.currentPath === ''));

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
        }

        function createBreadcrumbItem(label, pathValue, isActive) {
            const item = $('<li/>', {'class': 'breadcrumb-item folder-breadcrumb-item'});
            const buttonClasses = ['btn', 'btn-link', 'btn-sm', 'px-0', 'breadcrumb-button'];
            if (isActive) {
                buttonClasses.push('active');
            }
            const button = $('<button/>', {
                'type': 'button',
                'class': buttonClasses.join(' '),
                'data-path': pathValue,
            }).text(label || (resolvedRootName || resolvedStrings.modulename || ''));

            if (isActive) {
                button.attr('disabled', true).attr('aria-current', 'page');
            }

            item.append(button);
            return item;
        }

        function highlightTree() {
            const nodes = container.find('.folder-tree-node');
            nodes.removeClass('active');
            nodes.find('> .folder-tree-node-inner .folder-tree-label').removeClass('font-weight-bold text-primary');
            const target = nodes.filter(function() {
                return ($(this).attr('data-path') || '') === state.currentPath;
            }).first();

            if (target.length) {
                target.addClass('active');
                target.find('> .folder-tree-node-inner .folder-tree-label').addClass('font-weight-bold text-primary');
                target.parents('.folder-tree-node').each(function() {
                    const parentNode = $(this);
                    expandNode(parentNode);
                    parentNode.find('> .folder-tree-node-inner .folder-tree-label').addClass('font-weight-bold text-primary');
                });
            } else {
                const rootNode = container.find('.folder-tree-node.is-root').first();
                rootNode.addClass('active');
                rootNode.find('> .folder-tree-node-inner .folder-tree-label').addClass('font-weight-bold text-primary');
            }
        }

        function updateNavigationButtons() {
            elements.navBack.prop('disabled', state.historyIndex <= 0);
            elements.navForward.prop('disabled', state.historyIndex >= state.history.length - 1);
            elements.navUp.prop('disabled', !state.currentPath);
        }

        function updateSelectionStyles() {
            elements.gridContainer.find('.grid-item').each(function() {
                const item = $(this);
                const card = item.find('.card').first();
                if (!card.length) {
                    return;
                }
                if (item.hasClass('is-selected')) {
                    card.addClass('border-primary');
                } else {
                    card.removeClass('border-primary');
                }
            });

            elements.listTableBody.find('.list-item').each(function() {
                const row = $(this);
                if (row.hasClass('is-selected')) {
                    row.addClass('table-active');
                } else {
                    row.removeClass('table-active');
                }
            });

            elements.detailsTableBody.find('.details-item').each(function() {
                const row = $(this);
                if (row.hasClass('is-selected')) {
                    row.addClass('table-active');
                } else {
                    row.removeClass('table-active');
                }
            });
        }
    };

    const logError = function(message, error) {
        if (typeof window === 'undefined') {
            return;
        }

        if (window.console && typeof window.console.error === 'function') {
            window.console.error(message, error);
        }
    };

    const buildFallbackStrings = function(rootName) {
        const fallback = {};

        STRING_KEYS.forEach(function(key) {
            fallback[key] = key === 'modulename' ? rootName : '';
        });

        return fallback;
    };

    const loadStrings = function(rootName) {
        const resolvedRoot = rootName || '';
        const fallbackStrings = buildFallbackStrings(resolvedRoot);

        if (typeof require !== 'function') {
            return Promise.resolve(fallbackStrings);
        }

        return new Promise(function(resolve) {
            require(['core/str'], function(Str) {
                if (!Str || typeof Str.get_strings !== 'function') {
                    logError('mod_folder/windows_explorer: core/str module unavailable');
                    resolve(fallbackStrings);
                    return;
                }

                const requests = STRING_KEYS.map(function(key) {
                    return {key: key, component: 'mod_folder'};
                });

                const requestPromise = Str.get_strings(requests);

                Promise.resolve(requestPromise).then(function(results) {
                    const strings = {};
                    const values = Array.isArray(results) ? results :
                        (typeof results === 'undefined' ? [] : [results]);

                    STRING_KEYS.forEach(function(key, index) {
                        strings[key] = values[index] || '';
                    });

                    if (!strings.modulename && resolvedRoot) {
                        strings.modulename = resolvedRoot;
                    }

                    resolve(strings);
                }).catch(function(error) {
                    logError('mod_folder/windows_explorer: Failed to load strings', error);
                    resolve(fallbackStrings);
                });
            }, function(error) {
                logError('mod_folder/windows_explorer: Failed to require core/str', error);
                resolve(fallbackStrings);
            });
        });
    };

    const init = function(config) {
        const containerId = config && config.containerid;
        const container = containerId ? $('#' + containerId) : $();

        if (!container.length) {
            return;
        }

        const resolvedConfig = config || {};
        const rootNameSource = container.attr('data-root-name') || resolvedConfig.rootname || '';
        const resolvedRootName = decodeHtml(rootNameSource);

        return loadStrings(resolvedRootName).then(function(strings) {
            initialiseExplorer(container, resolvedConfig, resolvedRootName, strings);
        });
    };

    return {
        init: init,
    };
});
