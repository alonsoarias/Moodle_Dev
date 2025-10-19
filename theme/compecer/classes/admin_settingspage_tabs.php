<?php
defined('MOODLE_INTERNAL') || die();

class theme_compecer_admin_settingspage_tabs extends theme_boost_admin_settingspage_tabs
{

    /** @var array Lista de pestañas */
    public $tabs = array();

    /**
     * Obtiene las pestañas actuales
     */
    public function get_tabs()
    {
        return $this->tabs;
    }

    /**
     * Establece las pestañas
     */
    public function set_tabs($tabs, $reset = true)
    {
        if ($reset) {
            $this->tabs = array();
        }
        if (!empty($tabs)) {
            foreach ($tabs as $tab) {
                $original_name = $tab->name;
                $tab->name = str_replace('theme_moove', 'theme_compecer', $tab->name);
                if (!empty($tab->settings)) {
                    foreach ($tab->settings as $setting) {
                        $original_setting = $setting->name;
                        $setting->name = str_replace('theme_moove', 'theme_compecer', $setting->name);
                        $this->settings->{$setting->name} = $setting;
                    }
                }
                $this->tabs[] = $tab;
            }
        }
    }

    /**
     * Inserta una pestaña al inicio
     */
    public function insert_tab(admin_settingpage $tab)
    {
        if (!empty($tab->settings)) {
            foreach ($tab->settings as $setting) {
                $this->settings->{$setting->name} = $setting;
            }
        }
        array_unshift($this->tabs, $tab);
        return true;
    }

    /**
     * Añade una pestaña al final
     */
    public function add_tab(admin_settingpage $tab)
    {
        if (!empty($tab->settings)) {
            foreach ($tab->settings as $setting) {
                $this->settings->{$setting->name} = $setting;
            }
        }
        $this->tabs[] = $tab;
        return true;
    }
}
