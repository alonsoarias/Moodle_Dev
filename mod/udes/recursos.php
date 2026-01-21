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
 * Manage educational resources.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = required_param('id', PARAM_INT);
$action = optional_param('action', 'list', PARAM_ALPHA);
$recursoid = optional_param('recursoid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('udes', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('udes', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/udes/recursos.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Check permissions.
$canmanage = has_capability('mod/udes:expertodisciplinar', $modulecontext) ||
             has_capability('mod/udes:asesormetodologico', $modulecontext);

if (!$canmanage) {
    throw new moodle_exception('error_no_permission', 'mod_udes');
}

// Process form submissions.
if ($action === 'save' && confirm_sesskey()) {
    $unidad = required_param('unidad', PARAM_INT);
    $tema = required_param('tema', PARAM_TEXT);
    $nombre_unidad = required_param('nombre_unidad', PARAM_TEXT);
    $nombre_tema = required_param('nombre_tema', PARAM_TEXT);
    $tipo_recurso = required_param('tipo_recurso', PARAM_TEXT);
    $recurso = required_param('recurso', PARAM_TEXT);

    $data = new stdClass();
    $data->unidad = $unidad;
    $data->tema = $tema;
    $data->nombre_unidad = $nombre_unidad;
    $data->nombre_tema = $nombre_tema;
    $data->tipo_recurso = $tipo_recurso;
    $data->recurso = $recurso;

    // Collect dynamic form fields.
    $contenido = array();
    $fields = \mod_udes\recurso_manager::get_resource_form_fields($tipo_recurso, $recurso);
    foreach ($fields as $fieldname => $fielddef) {
        $value = optional_param($fieldname, '', PARAM_RAW);
        if (!empty($value)) {
            $contenido[$fieldname] = $value;
        }
    }
    $data->contenido = $contenido;

    if ($recursoid > 0) {
        // Update existing resource.
        \mod_udes\recurso_manager::update_recurso($recursoid, $data);
        $message = get_string('success_saved', 'mod_udes');
    } else {
        // Create new resource.
        \mod_udes\recurso_manager::create_recurso($moduleinstance->id, $data, $USER->id);
        $message = get_string('success_saved', 'mod_udes');
    }

    redirect(new moodle_url('/mod/udes/recursos.php', array('id' => $cm->id)), $message);
}

if ($action === 'delete' && $recursoid > 0 && confirm_sesskey()) {
    \mod_udes\recurso_manager::delete_recurso($recursoid);
    redirect(new moodle_url('/mod/udes/recursos.php', array('id' => $cm->id)),
        get_string('success_saved', 'mod_udes'));
}

// Output starts here.
echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('recursos', 'mod_udes'));

// Back link.
echo html_writer::link(
    new moodle_url('/mod/udes/view.php', array('id' => $cm->id)),
    get_string('volver', 'mod_udes'),
    array('class' => 'btn btn-secondary mb-3')
);

if ($action === 'list') {
    // Display list of resources.
    $recursos = \mod_udes\recurso_manager::get_recursos($moduleinstance->id);

    if (empty($recursos)) {
        echo html_writer::tag('p', get_string('error_no_recursos', 'mod_udes'), array('class' => 'alert alert-info'));
    } else {
        // Group by unidad.
        $grouped = array();
        foreach ($recursos as $recurso) {
            if (!isset($grouped[$recurso->unidad])) {
                $grouped[$recurso->unidad] = array();
            }
            $grouped[$recurso->unidad][] = $recurso;
        }

        // Display table.
        echo html_writer::start_tag('table', array('class' => 'table table-striped'));
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('unidad', 'mod_udes'));
        echo html_writer::tag('th', get_string('tema', 'mod_udes'));
        echo html_writer::tag('th', get_string('tipo_recurso', 'mod_udes'));
        echo html_writer::tag('th', get_string('recurso', 'mod_udes'));
        echo html_writer::tag('th', 'Acciones');
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        foreach ($grouped as $unidad => $unidadrecursos) {
            foreach ($unidadrecursos as $recurso) {
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', $recurso->unidad . ' - ' . $recurso->nombre_unidad);
                echo html_writer::tag('td', $recurso->tema . ' - ' . $recurso->nombre_tema);
                echo html_writer::tag('td', udes_get_category_name($recurso->tipo_recurso));
                echo html_writer::tag('td', $recurso->recurso);

                // Actions.
                echo html_writer::start_tag('td');
                echo html_writer::link(
                    new moodle_url('/mod/udes/recursos.php', array(
                        'id' => $cm->id,
                        'action' => 'edit',
                        'recursoid' => $recurso->id
                    )),
                    get_string('editar_recurso', 'mod_udes'),
                    array('class' => 'btn btn-sm btn-primary')
                );
                echo ' ';
                echo html_writer::link(
                    new moodle_url('/mod/udes/recursos.php', array(
                        'id' => $cm->id,
                        'action' => 'delete',
                        'recursoid' => $recurso->id,
                        'sesskey' => sesskey()
                    )),
                    get_string('eliminar_recurso', 'mod_udes'),
                    array('class' => 'btn btn-sm btn-danger', 'onclick' => 'return confirm("' .
                        get_string('confirm_delete', 'mod_udes') . '");')
                );
                echo html_writer::end_tag('td');

                echo html_writer::end_tag('tr');
            }
        }

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    }

    // Add resource button.
    echo html_writer::link(
        new moodle_url('/mod/udes/recursos.php', array('id' => $cm->id, 'action' => 'add')),
        get_string('agregar_recurso', 'mod_udes'),
        array('class' => 'btn btn-primary')
    );

    // Display summary statistics.
    echo html_writer::tag('h4', get_string('resumen', 'mod_udes'), array('class' => 'mt-4'));
    $counts = \mod_udes\recurso_manager::get_resource_count_by_category($moduleinstance->id);
    $total = \mod_udes\recurso_manager::get_total_resource_count($moduleinstance->id);

    echo html_writer::tag('p', get_string('total_recursos', 'mod_udes') . ': ' . $total);

    if (!empty($counts)) {
        echo html_writer::start_tag('ul');
        foreach ($counts as $category => $count) {
            echo html_writer::tag('li', udes_get_category_name($category) . ': ' . $count);
        }
        echo html_writer::end_tag('ul');
    }

} else if ($action === 'add' || $action === 'edit') {
    // Display form to add/edit resource.
    $recurso = null;
    if ($recursoid > 0) {
        $recurso = \mod_udes\recurso_manager::get_recurso($recursoid);
        echo html_writer::tag('h3', get_string('editar_recurso', 'mod_udes'));
    } else {
        echo html_writer::tag('h3', get_string('agregar_recurso', 'mod_udes'));
    }

    // Form.
    echo html_writer::start_tag('form', array(
        'method' => 'post',
        'action' => new moodle_url('/mod/udes/recursos.php'),
        'class' => 'mform'
    ));

    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $cm->id));
    echo html_writer->empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'save'));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    if ($recursoid > 0) {
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'recursoid', 'value' => $recursoid));
    }

    // Unidad.
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('unidad', 'mod_udes'));
    echo html_writer::select(
        array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5),
        'unidad',
        $recurso ? $recurso->unidad : 1,
        false,
        array('class' => 'form-control')
    );
    echo html_writer::end_div();

    // Nombre unidad.
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('nombre_unidad', 'mod_udes'));
    echo html_writer::empty_tag('input', array(
        'type' => 'text',
        'name' => 'nombre_unidad',
        'class' => 'form-control',
        'value' => $recurso ? $recurso->nombre_unidad : '',
        'required' => 'required'
    ));
    echo html_writer::end_div();

    // Tema.
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('tema', 'mod_udes'));
    echo html_writer::empty_tag('input', array(
        'type' => 'text',
        'name' => 'tema',
        'class' => 'form-control',
        'value' => $recurso ? $recurso->tema : '',
        'placeholder' => '1.1, 1.2, etc.',
        'required' => 'required'
    ));
    echo html_writer::end_div();

    // Nombre tema.
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('nombre_tema', 'mod_udes'));
    echo html_writer::empty_tag('input', array(
        'type' => 'text',
        'name' => 'nombre_tema',
        'class' => 'form-control',
        'value' => $recurso ? $recurso->nombre_tema : '',
        'required' => 'required'
    ));
    echo html_writer::end_div();

    // Tipo de recurso.
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('tipo_recurso', 'mod_udes'));
    $resourcetypes = udes_get_resource_types();
    $typeoptions = array();
    foreach ($resourcetypes as $category => $types) {
        $typeoptions[$category] = udes_get_category_name($category);
    }
    echo html_writer::select(
        $typeoptions,
        'tipo_recurso',
        $recurso ? $recurso->tipo_recurso : '',
        array('' => get_string('choose')),
        array('class' => 'form-control', 'required' => 'required', 'id' => 'id_tipo_recurso')
    );
    echo html_writer::end_div();

    // Recurso específico.
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('recurso', 'mod_udes'));
    echo html_writer::select(
        array(),
        'recurso',
        $recurso ? $recurso->recurso : '',
        array('' => get_string('choose')),
        array('class' => 'form-control', 'required' => 'required', 'id' => 'id_recurso')
    );
    echo html_writer::end_div();

    // Dynamic fields container.
    echo html_writer::start_div('', array('id' => 'dynamic_fields'));
    echo html_writer::end_div();

    // Submit buttons.
    echo html_writer::start_div('form-group');
    echo html_writer::empty_tag('input', array(
        'type' => 'submit',
        'class' => 'btn btn-primary',
        'value' => get_string('guardar', 'mod_udes')
    ));
    echo ' ';
    echo html_writer::link(
        new moodle_url('/mod/udes/recursos.php', array('id' => $cm->id)),
        get_string('cancelar', 'mod_udes'),
        array('class' => 'btn btn-secondary')
    );
    echo html_writer::end_div();

    echo html_writer::end_tag('form');

    // Initialize AMD module for dynamic form.
    $PAGE->requires->js_call_amd('mod_udes/recursos', 'init');
}

// Finish the page.
echo $OUTPUT->footer();
