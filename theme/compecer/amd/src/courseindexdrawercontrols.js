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
 * Controls for the course index drawer in the Compecer theme.
 *
 * @module     theme_compecer/courseindexdrawercontrols
 * @copyright  2024 IngeWeb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

export default class Component extends BaseComponent {
    create() {
        this.name = 'theme_compecer_courseindexdrawercontrols';
        this.selectors = {
            COLLAPSEALL: `[data-action="collapseallcourseindexsections"]`,
            EXPANDALL: `[data-action="expandallcourseindexsections"]`,
        };
    }

    static init(target, selectors) {
        return new Component({
            element: document.getElementById(target),
            reactive: getCurrentCourseEditor(),
            selectors,
        });
    }

    stateReady() {
        const expandAll = this.getElement(this.selectors.EXPANDALL);
        if (expandAll) {
            this.addEventListener(expandAll, 'click', this.expandAllSections);
        }
        const collapseAll = this.getElement(this.selectors.COLLAPSEALL);
        if (collapseAll) {
            this.addEventListener(collapseAll, 'click', this.collapseAllSections);
        }
    }

    expandAllSections(event) {
        event.preventDefault();
        this.toggleAllSections(false);
    }

    collapseAllSections(event) {
        event.preventDefault();
        this.toggleAllSections(true);
    }

    toggleAllSections(shouldCollapse) {
        this.reactive.dispatch('allSectionsIndexCollapsed', shouldCollapse);
    }
}
