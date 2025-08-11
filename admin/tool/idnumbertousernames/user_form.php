<?php
// This file part of a custom tool devoled by www.promwebsoft.com

/**
 * Plugin version info
 *
 * @package    tool
 * @subpackage idnumbertousernames
 * @copyright  2016 Pablo A Pico @pabloapico
 * @license    Para uso exclusivo de www.promwebsoft.com y sus clientes
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';
require_once($CFG->dirroot . '/user/editlib.php');

/**
 * Upload a file CVS file with user information.
 * By @pabloapico - Basado en Petr Skoda
 */
class admin_idnumbertousernames_form1 extends moodleform {
    function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $mform->addElement('filepicker', 'userfile', get_string('file'));
        $mform->addRule('userfile', null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = array('10'=>10, '20'=>20, '100'=>100, '1000'=>1000, '100000'=>100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(false, get_string('uploadusers', 'tool_uploaduser'));
    }
}


/**
 * Specify user upload details
 * By @pabloapico - Basado en  Petr Skoda
 */
class admin_idnumbertousernamesr_form2 extends moodleform {
    function definition () {
        global $CFG, $USER;

        $mform   = $this->_form;
        $columns = $this->_customdata['columns'];
        $data    = $this->_customdata['data'];

        // I am the template user, why should it be the administrator? we have roles now, other ppl may use this script ;-)
        $templateuser = $USER;

        // hidden fields
        $mform->addElement('hidden', 'iid');
        $mform->setType('iid', PARAM_INT);

        $mform->addElement('hidden', 'previewrows');
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(true, get_string('continue', 'tool_idnumbertousernames'));

        $this->set_data($data);
    }

    /**
     * Form tweaks that depend on current data.
     */
    function definition_after_data() {
        $mform   = $this->_form;
        $columns = $this->_customdata['columns'];

        foreach ($columns as $column) {
            if ($mform->elementExists($column)) {
                $mform->removeElement($column);
            }
        }

    }

    /**
     * Server side validation.
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $columns = $this->_customdata['columns'];

        // mirar lo requerido

        $requiredusernames = array('username', 'idnumber');
        $missing = array();
        foreach ($requiredusernames as $requiredusername) {
            if (!in_array($requiredusername, $columns)) {
                $missing[] = get_string('missingfield', 'error', $requiredusername);;
            }
        }
        if ($missing) {
            $errors['uutype'] = implode('<br />',  $missing);
        }
        if (!in_array('idnumber', $columns) and empty($data['idnumber'])) {
            $errors['missingfield'] = get_string('requiredidnumber', 'tool_idnumbertousernames');
        }

        return $errors;
    }

    /**
     * Used to reformat the data from the editor component
     *
     * @return stdClass
     */
    function get_data() {
        $data = parent::get_data();

        if ($data !== null and isset($data->description)) {
            $data->descriptionformat = $data->description['format'];
            $data->description = $data->description['text'];
        }

        return $data;
    }
}


class admin_idnumbertousernames_form3 extends moodleform {
    function definition () {
        global $CFG, $USER;

        $mform   = $this->_form;
        $columns = $this->_customdata['columns'];
        $data    = $this->_customdata['data'];

        // hidden fields
        $mform->addElement('hidden', 'iid');
        $mform->setType('iid', PARAM_INT);

        $mform->addElement('hidden', 'previewrows');
        $mform->setType('previewrows', PARAM_INT);

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('download', 'tool_idnumbertousernames'));
        //parece ser que con esto ya moodle lo asume como un cancel button...
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', get_string('finish', 'tool_idnumbertousernames'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        //$this->add_action_buttons(false, get_string('download', 'tool_idnumbertousernames'));
        //$this->add_action_buttons(false, get_string('finish', 'tool_idnumbertousernames'));

        $this->set_data($data);
    }
}
