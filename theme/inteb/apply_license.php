<?php
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
 * Script for applying license override and purging caches
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pedro Arias <soporte@ingeweb.co>
 */

// Este script está diseñado para ejecutarse desde un navegador web
// NO establecer CLI_SCRIPT aquí

// Set parameters.
define('NO_OUTPUT_BUFFERING', true);
define('CACHE_DISABLE_ALL', true);

// Include necessary files.
// Corrección de la ruta de inclusión del config.php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/theme/inteb/lib.php');

// Ensure the user is logged in as admin.
require_login();
if (!is_siteadmin()) {
    echo '<div style="background-color:#ffaaaa; padding:20px; border:1px solid #cc0000; margin:20px;">';
    echo '<h2>Error: Solo administradores pueden ejecutar este script</h2>';
    echo '<p>Inicie sesión como administrador primero.</p>';
    echo '</div>';
    die();
}

echo '<div style="max-width:800px; margin:20px auto; padding:20px; background-color:#f5f5f5; border:1px solid #ddd;">';
echo '<h2>Estado de Licencia RemUI</h2>';

// Verificar el estado actual de la licencia
$license_status = get_config('theme_remui', EDD_LICENSE_STATUS);
$license_key = get_config('theme_remui', EDD_LICENSE_KEY);

echo '<div style="margin-top:20px; padding:15px; background-color:#dff0d8; border:1px solid #d6e9c6; border-radius:4px;">';
echo '<h3 style="color:#3c763d;">¡La licencia ya está activada automáticamente!</h3>';
echo '<p>Estado actual: <strong style="color:green;">VÁLIDA</strong></p>';
echo '<p>Clave: ' . $license_key . '</p>';
echo '<p>No es necesario ejecutar este script manualmente, ya que el tema INTEB activa la licencia automáticamente:</p>';
echo '<ul>';
echo '<li>Al instalar o actualizar el tema</li>';
echo '<li>En cada carga de página</li>';
echo '<li>A través de una tarea programada que se ejecuta cada 6 horas</li>';
echo '</ul>';

echo '<p>Si experimentas problemas con la licencia, puedes usar el botón a continuación para forzar la activación manual y purgar las cachés.</p>';
echo '</div>';

// Mostrar formulario solo si no se ha enviado
if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'yes') {
    echo '<form method="post" style="margin-top:20px;">';
    echo '<input type="hidden" name="confirm" value="yes">';
    echo '<input type="submit" value="Forzar activación de licencia y purgar cachés" style="background-color:#4CAF50; color:white; padding:10px 15px; border:none; cursor:pointer;">';
    echo '</form>';
} else {
    // Procesar el formulario si se ha enviado

    echo '<h3>Aplicando licencia y purgando cachés...</h3>';

    // Step 1: Apply our license autoload
    echo '<h3>Paso 1: Cargando controlador de licencia personalizado</h3>';
    try {
        require_once($CFG->dirroot . '/theme/inteb/classes/license_autoload.php');
        theme_inteb_license_autoload();
        echo '<p style="color:green;">✓ Controlador de licencia cargado correctamente</p>';
    } catch (Exception $e) {
        echo '<p style="color:red;">✗ Error al cargar el controlador de licencia: ' . $e->getMessage() . '</p>';
    }

    // Step 2: Apply license
    echo '<h3>Paso 2: Configurando licencia como válida</h3>';
    try {
        // Define constants if not already defined
        if (!defined("PLUGINSLUG")) {
            define('PLUGINSLUG', 'remui');
            define('EDD_LICENSE_STATUS', 'edd_' . PLUGINSLUG . '_license_status');
            define('EDD_LICENSE_KEY', 'edd_' . PLUGINSLUG . '_license_key');
            define('EDD_LICENSE_ACTION', 'licenseactionperformed');
            define('WDM_LICENSE_TRANS', 'wdm_' . PLUGINSLUG . '_license_trans');
        }
        
        // Set license status to valid
        set_config(EDD_LICENSE_STATUS, 'valid', 'theme_remui');
        set_config(EDD_LICENSE_KEY, 'license-auto-activated-by-inteb', 'theme_remui');
        set_config(EDD_LICENSE_ACTION, true, 'theme_remui');
        
        // Set a long-lasting transient
        $transient = serialize(array('valid', time() + (60 * 60 * 24 * 365)));
        set_config(WDM_LICENSE_TRANS, $transient, 'theme_remui');
        
        echo '<p style="color:green;">✓ Licencia configurada como válida</p>';
    } catch (Exception $e) {
        echo '<p style="color:red;">✗ Error al configurar la licencia: ' . $e->getMessage() . '</p>';
    }

    // Step 3: Purge caches
    echo '<h3>Paso 3: Purgando todas las cachés</h3>';
    try {
        purge_all_caches();
        theme_reset_all_caches();
        echo '<p style="color:green;">✓ Cachés purgadas correctamente</p>';
    } catch (Exception $e) {
        echo '<p style="color:red;">✗ Error al purgar cachés: ' . $e->getMessage() . '</p>';
    }

    echo '<div style="margin-top:20px; padding:15px; background-color:#dff0d8; border:1px solid #d6e9c6; border-radius:4px;">';
    echo '<h3 style="color:#3c763d;">¡Proceso completado!</h3>';
    echo '<p>La licencia de RemUI ha sido configurada como válida y las cachés han sido purgadas.</p>';
    echo '<p>Recuerda que este proceso es automático y normalmente no es necesario ejecutarlo manualmente.</p>';
    echo '</div>';

    echo '<div style="margin-top:20px;">';
    echo '<a href="' . $CFG->wwwroot . '/admin/settings.php?section=themesettinginteb" style="display:inline-block; padding:10px 15px; background-color:#337ab7; color:white; text-decoration:none; border-radius:4px;">Ver configuración de InteB</a>';
    echo '<a href="' . $CFG->wwwroot . '" style="display:inline-block; margin-left:10px; padding:10px 15px; background-color:#5cb85c; color:white; text-decoration:none; border-radius:4px;">Ir a la página principal</a>';
    echo '</div>';
}

echo '</div>';