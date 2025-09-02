/**
 * Role Styles Plugin - Enhanced CSS Editor JavaScript
 * 
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function() {
    'use strict';
    
    // Configuration
    var CONFIG = {
        textareaId: 'id_s_local_rolestyles_custom_css',
        retryAttempts: 10,
        retryDelay: 500,
        tabSize: 4
    };
    
    // State
    var cssTextarea = null;
    var syntaxOverlay = null;
    var editorContainer = null;
    var isEnhanced = false;
    
    /**
     * Initialize the enhanced CSS editor
     */
    function initializeRoleStylesEditor() {
        console.log('Role Styles: Initializing enhanced CSS editor...');
        
        cssTextarea = document.getElementById(CONFIG.textareaId);
        
        if (!cssTextarea) {
            console.log('Role Styles: CSS textarea not found, retrying...');
            return false;
        }
        
        if (isEnhanced) {
            console.log('Role Styles: Editor already enhanced');
            return true;
        }
        
        try {
            createEditorStructure();
            setupEditor();
            setupEventListeners();
            createEnhancementButtons();
            updateSyntaxHighlighting();
            
            isEnhanced = true;
            console.log('Role Styles: CSS editor enhanced successfully!');
            return true;
            
        } catch (error) {
            console.error('Role Styles: Error enhancing editor:', error);
            return false;
        }
    }
    
    /**
     * Create the editor structure with syntax overlay
     */
    function createEditorStructure() {
        editorContainer = document.createElement('div');
        editorContainer.className = 'rolestyles-editor-container';
        
        syntaxOverlay = document.createElement('div');
        syntaxOverlay.className = 'rolestyles-syntax-overlay';
        
        cssTextarea.parentNode.insertBefore(editorContainer, cssTextarea);
        editorContainer.appendChild(syntaxOverlay);
        editorContainer.appendChild(cssTextarea);
    }
    
    /**
     * Set up the editor with enhanced attributes and styling
     */
    function setupEditor() {
        cssTextarea.setAttribute('spellcheck', 'false');
        cssTextarea.setAttribute('autocomplete', 'off');
        cssTextarea.setAttribute('autocorrect', 'off');
        cssTextarea.setAttribute('autocapitalize', 'off');
        cssTextarea.setAttribute('data-rolestyles-enhanced', 'true');
        
        var styles = {
            'font-family': '"Consolas", "Monaco", "Menlo", "Ubuntu Mono", monospace',
            'font-size': '14px',
            'line-height': '1.6',
            'tab-size': '4'
        };
        
        Object.keys(styles).forEach(function(property) {
            cssTextarea.style.setProperty(property, styles[property], 'important');
        });
    }
    
    /**
     * Set up event listeners for enhanced functionality
     */
    function setupEventListeners() {
        cssTextarea.addEventListener('keydown', handleKeyDown);
        cssTextarea.addEventListener('focus', handleFocus);
        cssTextarea.addEventListener('blur', handleBlur);
        cssTextarea.addEventListener('input', handleInput);
        cssTextarea.addEventListener('scroll', syncScroll);
        
        if (window.ResizeObserver) {
            var resizeObserver = new ResizeObserver(updateSyntaxHighlighting);
            resizeObserver.observe(cssTextarea);
        }
    }
    
    /**
     * Synchronize scroll between textarea and overlay
     */
    function syncScroll() {
        if (syntaxOverlay) {
            syntaxOverlay.scrollTop = cssTextarea.scrollTop;
            syntaxOverlay.scrollLeft = cssTextarea.scrollLeft;
        }
    }
    
    /**
     * Update syntax highlighting
     */
    function updateSyntaxHighlighting() {
        if (!syntaxOverlay || !cssTextarea) {
            return;
        }
        
        var css = cssTextarea.value;
        var highlightedCSS = applySyntaxHighlighting(css);
        syntaxOverlay.innerHTML = highlightedCSS;
        syncScroll();
    }
    
    /**
     * Apply syntax highlighting to CSS text
     */
    function applySyntaxHighlighting(css) {
        if (!css) {
            return '';
        }
        
        // Escape HTML first
        var result = css
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        
        // Simple but effective highlighting
        
        // 1. Comments
        result = result.replace(/\/\*[\s\S]*?\*\//g, function(match) {
            return '<span class="css-comment">' + match + '</span>';
        });
        
        // 2. Strings
        result = result.replace(/"[^"]*"/g, function(match) {
            return '<span class="css-string">' + match + '</span>';
        });
        result = result.replace(/'[^']*'/g, function(match) {
            return '<span class="css-string">' + match + '</span>';
        });
        
        // 3. At-rules
        result = result.replace(/@[a-zA-Z-]+/g, '<span class="css-at-rule">$&</span>');
        
        // 4. !important
        result = result.replace(/!important/g, '<span class="css-important">!important</span>');
        
        // 5. Hex colors
        result = result.replace(/#([0-9a-fA-F]{3,8})\b/g, '<span class="css-color-hex">#$1</span>');
        
        // 6. Numbers with units
        result = result.replace(/(\d+(?:\.\d+)?)(px|em|rem|%|vh|vw|pt|pc|in|cm|mm|ex|ch|deg|rad|turn|s|ms|Hz|kHz|dpi|dpcm|dppx|fr)/g, 
            '<span class="css-number">$1</span><span class="css-unit">$2</span>');
        
        // 7. Plain numbers
        result = result.replace(/\b(\d+(?:\.\d+)?)\b/g, '<span class="css-number">$1</span>');
        
        // 8. Properties (simple approach)
        result = result.replace(/([a-zA-Z-]+)\s*:/g, '<span class="css-property">$1</span><span class="css-colon">:</span>');
        
        // 9. CSS Functions
        result = result.replace(/\b(rgb|rgba|hsl|hsla|url|calc|var|linear-gradient|radial-gradient)\s*\(/g, 
            '<span class="css-function">$1</span>(');
        
        // 10. Selectors (simple class and id highlighting)
        result = result.replace(/\.[a-zA-Z][a-zA-Z0-9_-]*/g, '<span class="css-selector">$&</span>');
        result = result.replace(/#[a-zA-Z][a-zA-Z0-9_-]*/g, '<span class="css-selector">$&</span>');
        result = result.replace(/:+[a-zA-Z-]+(\([^)]*\))?/g, '<span class="css-selector">$&</span>');
        
        // 11. Punctuation
        result = result.replace(/{/g, '<span class="css-brace-open">{</span>');
        result = result.replace(/}/g, '<span class="css-brace-close">}</span>');
        result = result.replace(/;/g, '<span class="css-semicolon">;</span>');
        result = result.replace(/,/g, '<span class="css-comma">,</span>');
        
        return result;
    }
    
    /**
     * Handle keydown events
     */
    function handleKeyDown(event) {
        if (event.keyCode === 9) {
            handleTabKey(event);
        } else if (event.keyCode === 13) {
            handleEnterKey(event);
        }
    }
    
    /**
     * Handle Tab key for indentation
     */
    function handleTabKey(event) {
        event.preventDefault();
        
        var start = cssTextarea.selectionStart;
        var end = cssTextarea.selectionEnd;
        var tabSpaces = '    '; // 4 spaces
        
        if (event.shiftKey) {
            var textBefore = cssTextarea.value.substring(0, start);
            var textAfter = cssTextarea.value.substring(end);
            var lastNewline = textBefore.lastIndexOf('\n');
            var lineStart = lastNewline + 1;
            var lineText = textBefore.substring(lineStart);
            
            if (lineText.startsWith(tabSpaces)) {
                cssTextarea.value = textBefore.substring(0, lineStart) + 
                                  lineText.substring(4) + 
                                  textAfter;
                cssTextarea.setSelectionRange(
                    Math.max(lineStart, start - 4), 
                    Math.max(lineStart, end - 4)
                );
            }
        } else {
            cssTextarea.value = cssTextarea.value.substring(0, start) + 
                               tabSpaces + 
                               cssTextarea.value.substring(end);
            cssTextarea.setSelectionRange(start + 4, start + 4);
        }
        
        updateSyntaxHighlighting();
    }
    
    /**
     * Handle Enter key for auto-indentation
     */
    function handleEnterKey(event) {
        var cursorPos = cssTextarea.selectionStart;
        var textBefore = cssTextarea.value.substring(0, cursorPos);
        var lines = textBefore.split('\n');
        var currentLine = lines[lines.length - 1];
        var indent = currentLine.match(/^\s*/)[0];
        
        var newIndent = indent;
        if (currentLine.trim().endsWith('{')) {
            newIndent += '    ';
        }
        
        setTimeout(function() {
            var start = cssTextarea.selectionStart;
            cssTextarea.value = cssTextarea.value.substring(0, start) + 
                               newIndent + 
                               cssTextarea.value.substring(start);
            cssTextarea.setSelectionRange(start + newIndent.length, start + newIndent.length);
            updateSyntaxHighlighting();
        }, 1);
    }
    
    /**
     * Handle focus event
     */
    function handleFocus(event) {
        console.log('Role Styles: Editor focused');
    }
    
    /**
     * Handle blur event
     */
    function handleBlur(event) {
        console.log('Role Styles: Editor blurred');
    }
    
    /**
     * Handle input event
     */
    function handleInput(event) {
        updateSyntaxHighlighting();
    }
    
    /**
     * Format CSS code
     */
    function formatCSS() {
        console.log('Role Styles: Formatting CSS...');
        
        showLoading('format');
        
        try {
            var css = cssTextarea.value;
            var formatted = css
                .replace(/\s*{\s*/g, ' {\n    ')
                .replace(/;\s*(?![^{]*})/g, ';\n    ')
                .replace(/\s*}\s*/g, '\n}\n\n')
                .replace(/,\s*(?![^{]*})/g, ',\n')
                .replace(/\n\s*\n\s*\n+/g, '\n\n')
                .replace(/[ \t]+$/gm, '')
                .trim();
            
            cssTextarea.value = formatted;
            updateSyntaxHighlighting();
            cssTextarea.focus();
            
            showMessage('CSS formatted successfully!', 'success');
            console.log('Role Styles: CSS formatted successfully');
            
        } catch (error) {
            console.error('Role Styles: Error formatting CSS:', error);
            showMessage('Error formatting CSS: ' + error.message, 'error');
        } finally {
            hideLoading('format');
        }
    }
    
    /**
     * Validate CSS syntax
     */
    function validateCSS() {
        console.log('Role Styles: Validating CSS...');
        
        showLoading('validate');
        
        try {
            var css = cssTextarea.value.trim();
            
            if (!css) {
                alert('CSS is empty.');
                return;
            }
            
            var issues = [];
            var openBraces = (css.match(/{/g) || []).length;
            var closeBraces = (css.match(/}/g) || []).length;
            
            if (openBraces !== closeBraces) {
                issues.push('Unbalanced braces { } (Found ' + openBraces + ' opening, ' + closeBraces + ' closing)');
            }
            
            var lines = css.split('\n');
            lines.forEach(function(line, index) {
                var trimmedLine = line.trim();
                var lineNumber = index + 1;
                
                if (!trimmedLine || trimmedLine.startsWith('/*') || trimmedLine.endsWith('*/')) {
                    return;
                }
                
                if (trimmedLine.indexOf(':') !== -1 && 
                    !trimmedLine.endsWith(';') && 
                    !trimmedLine.endsWith('{') && 
                    !trimmedLine.endsWith('}') && 
                    trimmedLine.indexOf('/*') === -1) {
                    issues.push('Line ' + lineNumber + ': Possible missing semicolon');
                }
            });
            
            if (issues.length === 0) {
                var stats = 'Stats:\n• Lines: ' + lines.length + '\n• Rules: ~' + openBraces + '\n• Properties: ~' + (css.match(/:/g) || []).length;
                alert('CSS syntax appears to be correct!\n\n' + stats);
                showMessage('CSS validation passed!', 'success');
            } else {
                var issueText = issues.slice(0, 10).join('\n• ');
                var moreIssues = issues.length > 10 ? '\n... and ' + (issues.length - 10) + ' more issues' : '';
                alert('Issues found:\n\n• ' + issueText + moreIssues + '\n\nTip: Use the Format CSS button to fix indentation issues.');
                showMessage('Found ' + issues.length + ' issues in CSS', 'error');
            }
            
            console.log('Role Styles: CSS validation completed');
            
        } catch (error) {
            console.error('Role Styles: Error validating CSS:', error);
            showMessage('Error validating CSS: ' + error.message, 'error');
        } finally {
            hideLoading('validate');
        }
    }
    
    /**
     * Create enhancement buttons
     */
    function createEnhancementButtons() {
        // Remove existing buttons first
        var existingButtons = document.querySelector('.rolestyles-editor-buttons');
        if (existingButtons) {
            existingButtons.remove();
        }
        
        console.log('Role Styles: Creating enhancement buttons...');
        
        var buttonContainer = document.createElement('div');
        buttonContainer.className = 'rolestyles-editor-buttons';
        buttonContainer.style.display = 'flex';
        buttonContainer.style.gap = '12px';
        buttonContainer.style.margin = '15px 0';
        buttonContainer.style.alignItems = 'center';
        buttonContainer.style.visibility = 'visible';
        buttonContainer.style.opacity = '1';
        
        // Format button
        var formatBtn = document.createElement('button');
        formatBtn.type = 'button';
        formatBtn.className = 'rolestyles-editor-btn rolestyles-format-btn';
        formatBtn.style.display = 'inline-flex';
        formatBtn.style.alignItems = 'center';
        formatBtn.style.padding = '10px 18px';
        formatBtn.style.backgroundColor = '#0e639c';
        formatBtn.style.color = 'white';
        formatBtn.style.border = 'none';
        formatBtn.style.borderRadius = '5px';
        formatBtn.style.cursor = 'pointer';
        formatBtn.style.fontSize = '14px';
        formatBtn.style.fontWeight = '600';
        formatBtn.style.visibility = 'visible';
        formatBtn.style.opacity = '1';
        
        var formatLoading = document.createElement('span');
        formatLoading.id = 'format-loading';
        formatLoading.className = 'rolestyles-loading';
        formatLoading.style.display = 'none';
        formatLoading.style.width = '16px';
        formatLoading.style.height = '16px';
        formatLoading.style.border = '2px solid #f3f3f3';
        formatLoading.style.borderTop = '2px solid #007acc';
        formatLoading.style.borderRadius = '50%';
        formatLoading.style.animation = 'rolestyles-spin 1s linear infinite';
        formatLoading.style.marginRight = '8px';
        
        formatBtn.appendChild(formatLoading);
        formatBtn.appendChild(document.createTextNode('🔧 Format CSS'));
        formatBtn.title = 'Format and beautify CSS code';
        
        formatBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Format CSS button clicked');
            formatCSS();
        });
        
        // Validate button
        var validateBtn = document.createElement('button');
        validateBtn.type = 'button';
        validateBtn.className = 'rolestyles-editor-btn rolestyles-validate-btn';
        validateBtn.style.display = 'inline-flex';
        validateBtn.style.alignItems = 'center';
        validateBtn.style.padding = '10px 18px';
        validateBtn.style.backgroundColor = '#106ebe';
        validateBtn.style.color = 'white';
        validateBtn.style.border = 'none';
        validateBtn.style.borderRadius = '5px';
        validateBtn.style.cursor = 'pointer';
        validateBtn.style.fontSize = '14px';
        validateBtn.style.fontWeight = '600';
        validateBtn.style.visibility = 'visible';
        validateBtn.style.opacity = '1';
        
        var validateLoading = document.createElement('span');
        validateLoading.id = 'validate-loading';
        validateLoading.className = 'rolestyles-loading';
        validateLoading.style.display = 'none';
        validateLoading.style.width = '16px';
        validateLoading.style.height = '16px';
        validateLoading.style.border = '2px solid #f3f3f3';
        validateLoading.style.borderTop = '2px solid #007acc';
        validateLoading.style.borderRadius = '50%';
        validateLoading.style.animation = 'rolestyles-spin 1s linear infinite';
        validateLoading.style.marginRight = '8px';
        
        validateBtn.appendChild(validateLoading);
        validateBtn.appendChild(document.createTextNode('✅ Validate CSS'));
        validateBtn.title = 'Check CSS syntax for errors';
        
        validateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Validate CSS button clicked');
            validateCSS();
        });
        
        // Info panel
        var infoPanel = document.createElement('div');
        infoPanel.className = 'rolestyles-editor-info';
        infoPanel.style.fontSize = '13px';
        infoPanel.style.color = '#666';
        infoPanel.style.fontStyle = 'italic';
        infoPanel.style.marginLeft = 'auto';
        infoPanel.style.padding = '8px 12px';
        infoPanel.style.backgroundColor = '#f8f9fa';
        infoPanel.style.borderRadius = '4px';
        infoPanel.style.borderLeft = '4px solid #007acc';
        infoPanel.style.visibility = 'visible';
        infoPanel.style.opacity = '1';
        infoPanel.innerHTML = '💡 <strong>Enhanced Editor:</strong> Tab/Shift+Tab to indent, Enter for auto-indent after {';
        
        // Assemble container
        buttonContainer.appendChild(formatBtn);
        buttonContainer.appendChild(validateBtn);
        buttonContainer.appendChild(infoPanel);
        
        // Find the best place to insert buttons
        var insertTarget = null;
        
        // Try to insert after the editor container
        if (editorContainer && editorContainer.parentNode) {
            insertTarget = editorContainer.parentNode;
            insertTarget.insertBefore(buttonContainer, editorContainer.nextSibling);
        }
        // Fallback: insert after textarea
        else if (cssTextarea && cssTextarea.parentNode) {
            insertTarget = cssTextarea.parentNode;
            insertTarget.insertBefore(buttonContainer, cssTextarea.nextSibling);
        }
        
        if (insertTarget) {
            console.log('Role Styles: Enhancement buttons created and inserted successfully');
            
            // Force visibility after insertion
            setTimeout(function() {
                buttonContainer.style.display = 'flex';
                buttonContainer.style.visibility = 'visible';
                formatBtn.style.display = 'inline-flex';
                formatBtn.style.visibility = 'visible';
                validateBtn.style.display = 'inline-flex';
                validateBtn.style.visibility = 'visible';
                infoPanel.style.display = 'block';
                infoPanel.style.visibility = 'visible';
            }, 100);
        } else {
            console.error('Role Styles: Could not find target to insert buttons');
        }
        
        return buttonContainer;
    }
    
    /**
     * Show loading indicator for a button
     */
    function showLoading(buttonType) {
        var loadingElement = document.getElementById(buttonType + '-loading');
        if (loadingElement) {
            loadingElement.style.display = 'inline-block';
        }
    }
    
    /**
     * Hide loading indicator for a button
     */
    function hideLoading(buttonType) {
        var loadingElement = document.getElementById(buttonType + '-loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }
    
    /**
     * Show temporary message
     */
    function showMessage(text, type) {
        type = type || 'info';
        
        var existingMessages = document.querySelectorAll('.rolestyles-message');
        existingMessages.forEach(function(msg) {
            msg.remove();
        });
        
        var message = document.createElement('div');
        message.className = 'rolestyles-message ' + type;
        message.textContent = text;
        
        var buttonContainer = document.querySelector('.rolestyles-editor-buttons');
        if (buttonContainer) {
            buttonContainer.parentNode.insertBefore(message, buttonContainer.nextSibling);
            
            setTimeout(function() {
                if (message.parentNode) {
                    message.remove();
                }
            }, 3000);
        }
    }
    
    /**
     * Retry mechanism for initialization
     */
    function retryInitialization() {
        var attempts = 0;
        
        function tryInit() {
            attempts++;
            
            if (initializeRoleStylesEditor()) {
                console.log('Role Styles: Successfully initialized after ' + attempts + ' attempts');
                return;
            }
            
            if (attempts < CONFIG.retryAttempts) {
                console.log('Role Styles: Attempt ' + attempts + ' failed, retrying in ' + CONFIG.retryDelay + 'ms...');
                setTimeout(tryInit, CONFIG.retryDelay);
            } else {
                console.warn('Role Styles: Failed to initialize after maximum attempts');
            }
        }
        
        tryInit();
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', retryInitialization);
    } else {
        retryInitialization();
    }
    
    // Also try after page load as additional fallback
    window.addEventListener('load', function() {
        if (!isEnhanced) {
            setTimeout(retryInitialization, 100);
        }
    });
    
    // Expose functions for debugging
    if (window.console && console.log) {
        window.roleStylesDebug = {
            init: initializeRoleStylesEditor,
            format: formatCSS,
            validate: validateCSS,
            highlight: updateSyntaxHighlighting,
            isEnhanced: function() { return isEnhanced; },
            getOverlay: function() { return syntaxOverlay; },
            getTextarea: function() { return cssTextarea; }
        };
    }
    
})();