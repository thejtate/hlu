<?php
require_once("$CFG->libdir/formslib.php");

class mod_checklist_form extends moodleform {
    public function definition(){
        $mform = $this->_form;
        
        $mform->addElement('text', 'firstname', "First Name"); 
        $mform->setType('firstname',PARAM_TEXT);

        $mform->addElement('text', 'lastname', "Last Name"); 
        $mform->setType('lastname',PARAM_TEXT);

        $mform->addElement('hidden','id');
        $mform->setType('id',PARAM_INT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'searchbutton', 'Search');
        $mform->addGroup($buttonarray, 'buttonar', array(''), false);
    }

    public function validation($data, $files){
        $errors = array();
        return $errors; 
    }
}