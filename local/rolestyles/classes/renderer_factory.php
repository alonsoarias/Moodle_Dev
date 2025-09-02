<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Role Styles Plugin - Renderer Factory for dynamic theme extension
 *
 * @package    local_rolestyles
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co> - aulatecnos.es - tecnoszubia.es
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rolestyles;

defined('MOODLE_INTERNAL') || die();

/**
 * Factory class for creating dynamic theme renderers
 */
class renderer_factory {
    
    /** @var array Already created renderers cache */
    private static $created_renderers = [];
    
    /**
     * Create and register a dynamic renderer for the current theme
     * 
     * @param string $theme_name Name of the current theme
     * @return bool Success status
     */
    public static function create_theme_renderer($theme_name) {
        // Check if already created
        if (isset(self::$created_renderers[$theme_name])) {
            return true;
        }
        
        try {
            // Find parent renderer class
            $parent_class = self::find_theme_renderer_class($theme_name);
            $extended_class = "local_rolestyles_{$theme_name}_core_renderer";
            
            // Check if class already exists
            if (class_exists($extended_class)) {
                self::$created_renderers[$theme_name] = true;
                return true;
            }
            
            // Create the class definition
            $class_definition = self::generate_renderer_class($extended_class, $parent_class);
            
            // Evaluate the class
            eval($class_definition);
            
            // Mark as created
            self::$created_renderers[$theme_name] = true;
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Find the appropriate parent renderer class
     * 
     * @param string $theme_name
     * @return string
     */
    private static function find_theme_renderer_class($theme_name) {
        $theme_renderer_classes = [
            "theme_{$theme_name}\\output\\core_renderer",
            "theme_{$theme_name}_core_renderer",
            "core_renderer"
        ];
        
        foreach ($theme_renderer_classes as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }
        
        return "core_renderer";
    }
    
    /**
     * Generate the class definition for the extended renderer
     * 
     * @param string $class_name Name of the class to create
     * @param string $parent_class Name of the parent class
     * @return string Class definition code
     */
    private static function generate_renderer_class($class_name, $parent_class) {
        return "
        class {$class_name} extends {$parent_class} {
            
            public function standard_head_html() {
                \$output = parent::standard_head_html();
                \$this->add_role_styles_css();
                return \$output;
            }
            
            public function standard_footer_html() {
                \$output = parent::standard_footer_html();
                \$this->add_role_styles_css();
                return \$output;
            }
            
            protected function add_role_styles_css() {
                global \$USER, \$PAGE, \$COURSE;
                
                \$enabled = get_config('local_rolestyles', 'enabled');
                \$selected_roles = get_config('local_rolestyles', 'selected_roles');
                \$custom_css = get_config('local_rolestyles', 'custom_css');
                
                if (!\$enabled || empty(\$selected_roles) || empty(\$custom_css)) {
                    return;
                }
                
                if (!isloggedin() || isguestuser()) {
                    return;
                }
                
                try {
                    \$context = null;
                    if (!empty(\$COURSE->id) && \$COURSE->id > 1) {
                        \$context = \\context_course::instance(\$COURSE->id);
                    } else if (!empty(\$PAGE->context)) {
                        \$context = \$PAGE->context;
                    }
                    
                    if (!\$context) {
                        return;
                    }
                    
                    if (!is_array(\$selected_roles)) {
                        \$selected_roles = explode(',', \$selected_roles);
                    }
                    
                    \$userroles = get_user_roles(\$context, \$USER->id);
                    \$has_selected_role = false;
                    \$applied_roles = [];
                    
                    foreach (\$userroles as \$role) {
                        if (in_array(\$role->roleid, \$selected_roles)) {
                            \$has_selected_role = true;
                            \$applied_roles[] = \$role->shortname;
                            \$PAGE->add_body_class('role-' . \$role->shortname);
                            \$PAGE->add_body_class('roleid-' . \$role->roleid);
                        }
                    }
                    
                    if (\$has_selected_role) {
                        \$css_id = 'local-rolestyles-renderer-' . implode('-', \$applied_roles);
                        \$clean_css = preg_replace('/\\s+/', ' ', trim(\$custom_css));
                        \$escaped_css = addslashes(\$clean_css);
                        \$roles_string = implode(', ', \$applied_roles);
                        
                        \$PAGE->requires->js_init_code(\"
                            (function() {
                                function injectRoleStylesCSS() {
                                    var existingStyle = document.getElementById('{\$css_id}');
                                    if (existingStyle) {
                                        existingStyle.remove();
                                    }
                                    
                                    var style = document.createElement('style');
                                    style.id = '{\$css_id}';
                                    style.type = 'text/css';
                                    style.innerHTML = '/* Role Styles - Roles: {\$roles_string} */\\\\n{\$escaped_css}';
                                    document.head.appendChild(style);
                                }
                                
                                if (document.readyState === 'loading') {
                                    document.addEventListener('DOMContentLoaded', injectRoleStylesCSS);
                                } else {
                                    injectRoleStylesCSS();
                                }
                            })();
                        \");
                    }
                    
                } catch (\\Exception \$e) {
                    return;
                }
            }
        }
        ";
    }
    
    /**
     * Check if a renderer has been created for a theme
     * 
     * @param string $theme_name Theme name
     * @return bool True if renderer exists
     */
    public static function renderer_exists($theme_name) {
        return isset(self::$created_renderers[$theme_name]);
    }
}