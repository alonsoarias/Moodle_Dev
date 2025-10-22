// This file is part of Moodle - http://moodle.org/

/**
 * Course index progress manager - Minimal and efficient
 *
 * @module     theme_compecer/courseindex_progress
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str', 'core/log'], function($, Str, Log) {
    
    const SELECTORS = {
        drawer: '.courseindex-drawer',
        statusIcon: '.courseindex-item__status',
        statusText: '[data-region="cm-status-text"]',
        courseProgressValue: '[data-region="course-progress-value"]',
        courseProgressSummary: '[data-region="course-progress-summary"]',
        courseProgressBar: '.courseindex-progress-bar__fill',
        sectionProgressValue: '[data-region="section-progress-value"]',
        sectionProgressText: '[data-region="section-progress-text"]',
    };
    
    const STATUS_CLASSES = {
        notstarted: 'courseindex-item__status--notstarted',
        inprogress: 'courseindex-item__status--inprogress',
        completed: 'courseindex-item__status--completed',
        failed: 'courseindex-item__status--failed',
    };
    
    /**
     * Initialize progress manager
     */
    const init = (drawerId) => {
        const drawer = document.getElementById(drawerId);
        if (!drawer) {
            Log.debug('Course index drawer not found: ' + drawerId);
            return;
        }
        
        const datasetAttr = drawer.getAttribute('data-progress');
        if (!datasetAttr) {
            Log.debug('No progress data in drawer');
            return;
        }
        
        let dataset;
        try {
            dataset = JSON.parse(datasetAttr);
        } catch (e) {
            Log.error('Failed to parse progress dataset', e);
            return;
        }
        
        if (!dataset.enabled) {
            Log.debug('Completion tracking disabled');
            return;
        }
        
        updateAll(drawer, dataset);
        setupEventListeners(drawer, dataset);
        
        Log.debug('Course index progress initialized');
    };
    
    /**
     * Update all indicators
     */
    const updateAll = (drawer, dataset) => {
        updateCourseProgress(drawer, dataset.course);
        updateSectionProgress(drawer, dataset.sections);
        updateActivityStatus(drawer, dataset.activities, dataset.strings);
    };
    
    /**
     * Update course progress
     */
    const updateCourseProgress = (drawer, courseData) => {
        if (!courseData) return;
        
        const valueEl = drawer.querySelector(SELECTORS.courseProgressValue);
        const summaryEl = drawer.querySelector(SELECTORS.courseProgressSummary);
        const barEl = drawer.querySelector(SELECTORS.courseProgressBar);
        
        if (valueEl && courseData.percentageformatted) {
            valueEl.textContent = courseData.percentageformatted;
        }
        
        if (summaryEl && courseData.summary) {
            summaryEl.textContent = courseData.summary;
        }
        
        if (barEl && typeof courseData.percentage !== 'undefined') {
            barEl.style.setProperty('--progress', courseData.percentage + '%');
            barEl.setAttribute('aria-valuenow', courseData.percentage);
            if (courseData.aria) {
                barEl.setAttribute('aria-label', courseData.aria);
            }
        }
    };
    
    /**
     * Update section progress
     */
    const updateSectionProgress = (drawer, sectionsData) => {
        if (!sectionsData) return;
        
        Object.keys(sectionsData).forEach(sectionId => {
            const sectionData = sectionsData[sectionId];
            const sectionEl = drawer.querySelector(`[data-id="${sectionId}"][data-for="section"]`);
            
            if (!sectionEl) return;
            
            const valueEl = sectionEl.querySelector(SELECTORS.sectionProgressValue);
            const textEl = sectionEl.querySelector(SELECTORS.sectionProgressText);
            
            if (valueEl && sectionData.tracked > 0) {
                // Show percentage instead of "X/Y"
                valueEl.textContent = sectionData.percentage + '%';
            }
            
            if (textEl && sectionData.aria) {
                textEl.textContent = sectionData.aria;
            }
        });
    };
    
    /**
     * Update activity status icons
     */
    const updateActivityStatus = (drawer, activitiesData, strings) => {
        if (!activitiesData) return;
        
        Object.keys(activitiesData).forEach(cmId => {
            const activityData = activitiesData[cmId];
            const itemEl = drawer.querySelector(`[data-id="${cmId}"][data-for="cm"]`);
            
            if (!itemEl) return;
            
            const statusIcon = itemEl.querySelector(SELECTORS.statusIcon);
            const statusText = itemEl.querySelector(SELECTORS.statusText);
            
            if (statusIcon) {
                Object.values(STATUS_CLASSES).forEach(cls => {
                    statusIcon.classList.remove(cls);
                });
                
                const statusClass = STATUS_CLASSES[activityData.status];
                if (statusClass) {
                    statusIcon.classList.add(statusClass);
                }
            }
            
            if (statusText && activityData.label) {
                statusText.textContent = activityData.label;
            }
        });
    };
    
    /**
     * Setup event listeners for live updates
     */
    const setupEventListeners = (drawer, dataset) => {
        // Watch for completion changes
        const completionObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-value') {
                    const cmId = mutation.target.closest('[data-for="cm"]')?.getAttribute('data-id');
                    if (cmId) {
                        Log.debug('Completion changed for cm: ' + cmId);
                        // In production: fetch fresh data and update
                    }
                }
            });
        });
        
        drawer.querySelectorAll('.completioninfo').forEach(el => {
            completionObserver.observe(el, {
                attributes: true,
                attributeFilter: ['data-value']
            });
        });
    };
    
    return {
        init: init
    };
});
