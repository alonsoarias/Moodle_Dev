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
 * Module filter for download center
 *
 * @module     local_downloadcenter/modfilter
 * @copyright  2025 Original: Academic Moodle Cooperation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str', 'core/url'], function(Str, Url) {
    'use strict';

    const ModFilter = function(modnames) {
        const instance = this;
        this.modnames = modnames;
        this.strings = {};
        this.formid = null;
        this.currentlyshown = false;
        this.modlist = null;

        // Load strings.
        Str.get_strings([
            {key: 'all', component: 'moodle'},
            {key: 'none', component: 'moodle'},
            {key: 'select', component: 'moodle'},
            {key: 'showtypes', component: 'backup'},
            {key: 'hidetypes', component: 'backup'}
        ]).done(function(strs) {
            instance.strings.all = strs[0];
            instance.strings.none = strs[1];
            instance.strings.select = strs[2];
            instance.strings.showtypes = strs[3];
            instance.strings.hidetypes = strs[4];

            instance.init();
        });
    };

    ModFilter.prototype.init = function() {
        const instance = this;
        const firstsection = document.querySelector('div[role="main"] > form .card.block');
        
        if (!firstsection) {
            return;
        }
        
        instance.formid = firstsection.closest('form').id;

        // Add global select all/none options.
        const showTypeOptionsLink = '<span class="font-weight-bold ml-3 text-nowrap">' +
            '(<a id="downloadcenter-bytype" href="#">' + instance.strings.showtypes + '</a>)</span>';
        
        let html = instance.htmlGenerator('included', instance.strings.select);
        let links = document.createElement('div');
        links.className = 'grouped_settings section_level block card';
        links.innerHTML = html;
        links.querySelector('.downloadcenter_selector .col-md-9').insertAdjacentHTML('beforeend', showTypeOptionsLink);
        firstsection.parentNode.insertBefore(links, firstsection);

        // For each module type, add hidden select all/none options.
        instance.modlist = document.createElement('div');
        instance.modlist.id = 'mod_select_links';
        instance.modlist.className = 'm-l-2';
        instance.modlist.style.display = 'none';
        links.appendChild(instance.modlist);

        for (let mod in instance.modnames) {
            if (!instance.modnames.hasOwnProperty(mod)) {
                continue;
            }

            const img = '<img src="' + Url.imageUrl('icon', 'mod_' + mod) + '" class="activityicon" />';
            html = instance.htmlGenerator('mod_' + mod, img + instance.modnames[mod]);
            const modlinks = document.createElement('div');
            modlinks.className = 'grouped_settings section_level';
            modlinks.innerHTML = html;
            instance.modlist.appendChild(modlinks);
            instance.initlinks(modlinks, mod);
        }

        // Attach events.
        const allIncluded = document.getElementById('downloadcenter-all-included');
        if (allIncluded) {
            allIncluded.addEventListener('click', function(e) {
                instance.helper(e, true, 'item_');
            });
        }
        
        const noneIncluded = document.getElementById('downloadcenter-none-included');
        if (noneIncluded) {
            noneIncluded.addEventListener('click', function(e) {
                instance.helper(e, false, 'item_');
            });
        }
        
        const byType = document.getElementById('downloadcenter-bytype');
        if (byType) {
            byType.addEventListener('click', function(e) {
                e.preventDefault();
                instance.toggletypes();
            });
        }
        
        // Attach event to checkboxes.
        document.querySelectorAll('input.form-check-input').forEach(function(checkbox) {
            checkbox.addEventListener('click', function() {
                instance.checkboxhandler(this);
                instance.updateFormState();
            });
        });
    };

    ModFilter.prototype.checkboxhandler = function(checkbox) {
        const prefix = 'item_topic';
        const shortprefix = 'item_';
        const name = checkbox.name;
        const checked = checkbox.checked;
        
        if (name.substring(0, shortprefix.length) === shortprefix) {
            let parent = checkbox.closest('.card');
            
            if (name.substring(0, prefix.length) === prefix) {
                parent.querySelectorAll('input.form-check-input').forEach(function(input) {
                    input.checked = checked;
                });
            } else {
                if (checked) {
                    const topicCheckbox = parent.querySelector('input.form-check-input[name^="item_topic"]');
                    if (topicCheckbox) {
                        topicCheckbox.checked = true;
                    }
                }
            }
        }
    };

    ModFilter.prototype.updateFormState = function() {
        if (this.formid && M.form && M.form.updateFormState) {
            M.form.updateFormState(this.formid);
        }
    };

    ModFilter.prototype.toggletypes = function() {
        const link = document.getElementById('downloadcenter-bytype');
        if (this.currentlyshown) {
            link.textContent = this.strings.showtypes;
            this.modlist.style.display = 'none';
        } else {
            link.textContent = this.strings.hidetypes;
            this.modlist.style.display = 'block';
        }
        this.currentlyshown = !this.currentlyshown;
    };

    ModFilter.prototype.initlinks = function(links, mod) {
        const instance = this;
        const allModLink = document.getElementById('downloadcenter-all-mod_' + mod);
        if (allModLink) {
            allModLink.addEventListener('click', function(e) {
                instance.helper(e, true, 'item_', mod);
            });
        }
        
        const noneModLink = document.getElementById('downloadcenter-none-mod_' + mod);
        if (noneModLink) {
            noneModLink.addEventListener('click', function(e) {
                instance.helper(e, false, 'item_', mod);
            });
        }
    };

    ModFilter.prototype.helper = function(e, check, type, mod) {
        e.preventDefault();
        let prefix = '';
        if (typeof mod !== 'undefined') {
            prefix = 'item_' + mod + '_';
        }

        const len = type.length;

        document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
            const name = checkbox.name;

            if (prefix && name.substring(0, prefix.length) !== prefix) {
                return;
            }
            if (name.substring(0, len) === type) {
                checkbox.checked = check;
            }
            if (check) {
                const firstCheckbox = checkbox.closest('.card.block').querySelector('.fitem:first-child input[type="checkbox"]');
                if (firstCheckbox) {
                    firstCheckbox.checked = check;
                }
            }
        });

        this.updateFormState();
    };

    ModFilter.prototype.htmlGenerator = function(idtype, heading) {
        let links = '<a id="downloadcenter-all-' + idtype + '" href="#">' + this.strings.all + '</a> / ';
        links += '<a id="downloadcenter-none-' + idtype + '" href="#">' + this.strings.none + '</a>';
        return this.rowGenerator(heading, links);
    };

    ModFilter.prototype.rowGenerator = function(heading, content) {
        let ret = '<div class="form-group row fitem downloadcenter_selector">';
        ret += '<div class="col-md-3"></div>';
        ret += '<div class="col-md-9">';
        ret += '<label><span class="itemtitle">' + heading + '</span></label>';
        ret += '<span class="text-nowrap">' + content + '</span>';
        ret += '</div>';
        ret += '</div>';
        return ret;
    };

    return {
        init: function(modnames) {
            return new ModFilter(modnames);
        }
    };
});
