<?php

namespace theme_inteb\util;

use theme_config;

/**
 * Utility class for theme settings specifically for handling footer settings and personal area header.
 */
class settings {
    /**
     * @var stdClass The theme configuration object.
     */
    protected $theme;

    /**
     * Constructor that loads the current theme configuration.
     */
    public function __construct() {
        $this->theme = theme_config::load('inteb');
    }

    /**
     * Retrieves footer settings for the theme.
     *
     * This method gathers the 'my_credit' and 'abouttext' settings from the theme configuration
     * and prepares them for use in the footer template.
     *
     * @return array Context for the footer template with settings data.
     */
    public function footer() {
        $templatecontext = [];
        
        // Retrieve 'my_credit' from the theme settings
        $templatecontext['my_credit'] = get_string('credit', 'theme_inteb');
        
        // Comprobar y cargar correctamente abouttitle
        $templatecontext['abouttitle'] = $this->theme->settings->ib_abouttitle
            ? $this->theme->settings->ib_abouttitle 
            : get_string('abouttitle_default', 'theme_inteb'); 
        
        // Comprobar y cargar correctamente abouttext
        $templatecontext['abouttext'] = $this->theme->settings->ib_abouttext
            ? $this->theme->settings->ib_abouttext 
            : get_string('abouttext_default', 'theme_inteb');
        
        return $templatecontext;
    }

    /**
     * Retrieves personal area header settings for the theme.
     *
     * This method gathers the 'personalareaheader' setting from the theme configuration
     * and prepares it for use in the personal area header template.
     *
     * @return array Context for the personal area header template with settings data.
     */
    public function personal_area_header() {
        $templatecontext = [];

        // Retrieve 'personalareaheader' from the theme settings or use a default value if not set.
        $personalareaheader = $this->theme->setting_file_url('ib_personalareaheader', 'ib_personalareaheader');
        if (!empty($personalareaheader)) {
            $templatecontext['headerimage'] = [
                'url' => $personalareaheader,
                'title' => get_string('personalareaheader', 'theme_inteb')
            ];
        } else {
            $templatecontext['headerimage'] = [
                'url' => '',
                'title' => get_string('defaultheader', 'theme_inteb')
            ];
        }

        return $templatecontext;
    }

    /**
     * Retrieves my courses header settings for the theme.
     *
     * This method gathers the 'mycoursesheader' setting from the theme configuration
     * and prepares it for use in the my courses header template.
     *
     * @return array Context for the my courses header template with settings data.
     */
    public function my_courses_header() {
        $templatecontext = [];

        // Retrieve 'mycoursesheader' from the theme settings or use a default value if not set.
        $mycoursesheader = $this->theme->setting_file_url('ib_mycoursesheader', 'ib_mycoursesheader');
        if (!empty($mycoursesheader)) {
            $templatecontext['headerimage'] = [
                'url' => $mycoursesheader,
                'title' => get_string('mycoursesheader', 'theme_inteb')
            ];
        } else {
            $templatecontext['headerimage'] = [
                'url' => '',
                'title' => get_string('defaultheader', 'theme_inteb')
            ];
        }

        return $templatecontext;
    }
}