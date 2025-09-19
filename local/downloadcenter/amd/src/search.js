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
 * Search functionality for download center
 *
 * @module     local_downloadcenter/search
 * @copyright  2025 Original: Academic Moodle Cooperation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    const IDENTIFIERS = {
        SEARCHINPUT: 'downloadcenter-search-input',
        FORM: '#region-main form.mform',
        FORMELEMENTS: '.fitem',
        TOPICS: '.card.block',
        CHECKBOX: 'input[type="checkbox"]',
        TITLE: 'label .itemtitle span:not(.badge)',
        RESULTSHOLDER: 'downloadcenter-search-results',
        RESULTSCOUNT: 'downloadcenter-search-results-count',
        SEARCHCLEAR: '#downloadcenter-search-clear'
    };

    const allCmsPerTopic = [];
    let resultsHolder = null;
    let resultsCount = null;
    let searchClearBtn = null;
    let searchInput = null;

    const search = function(searchValue) {
        const showAll = searchValue.length === 0;
        let resultscount = 0;
        
        allCmsPerTopic.forEach(topic => {
            let foundInTopic = false;
            topic.cms.forEach(cm => {
                if (cm.title.indexOf(searchValue) > -1 || showAll) {
                    foundInTopic = true;
                    resultscount++;
                    cm.visible = true;
                    cm.elem.classList.remove('d-none');
                } else {
                    cm.visible = false;
                    cm.elem.classList.add('d-none');
                }
            });
            
            if (foundInTopic) {
                topic.visible = true;
                topic.elem.classList.remove('d-none');
            } else {
                topic.visible = false;
                topic.elem.classList.add('d-none');
            }
        });
        
        if (!showAll) {
            resultsCount.textContent = resultscount;
            resultsHolder.classList.remove('d-none');
            searchClearBtn.classList.remove('d-none');
        } else {
            resultsHolder.classList.add('d-none');
            searchClearBtn.classList.add('d-none');
        }
    };

    const searchClear = function(e) {
        e.preventDefault();
        searchInput.value = '';
        search('');
    };

    const submitForm = function() {
        // Ensure hidden items are not submitted.
        allCmsPerTopic.forEach(topic => {
            if (!topic.visible && topic.checkbox.checked) {
                topic.checkbox.checked = false;
            }
            topic.cms.forEach(cm => {
                if (!cm.visible && cm.checkbox.checked) {
                    cm.checkbox.checked = false;
                }
            });
        });
        return true;
    };

    const init = function() {
        searchInput = document.getElementById(IDENTIFIERS.SEARCHINPUT);
        if (!searchInput) {
            return; // Search not available on this page.
        }
        
        searchInput.addEventListener('input', function(e) {
            search(e.target.value.toLowerCase());
        });
        
        const form = document.querySelector(IDENTIFIERS.FORM);
        if (!form) {
            return;
        }
        
        const topics = form.querySelectorAll(IDENTIFIERS.TOPICS);
        resultsHolder = document.getElementById(IDENTIFIERS.RESULTSHOLDER);
        resultsCount = document.getElementById(IDENTIFIERS.RESULTSCOUNT);
        searchClearBtn = document.querySelector(IDENTIFIERS.SEARCHCLEAR);
        
        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', searchClear);
        }
        
        form.addEventListener('submit', submitForm);
        
        topics.forEach(topic => {
            const elements = topic.querySelectorAll(IDENTIFIERS.FORMELEMENTS);
            const topicObj = {
                elem: topic,
                cms: [],
                visible: true,
                checkbox: topic.querySelector(IDENTIFIERS.CHECKBOX)
            };
            
            elements.forEach(element => {
                const title = element.querySelector(IDENTIFIERS.TITLE);
                if (title) {
                    const cmObj = {
                        title: title.textContent.toLowerCase(),
                        elem: element,
                        visible: true,
                        checkbox: element.querySelector(IDENTIFIERS.CHECKBOX)
                    };
                    topicObj.cms.push(cmObj);
                }
            });
            
            allCmsPerTopic.push(topicObj);
        });
    };

    return {
        init: init
    };
});
