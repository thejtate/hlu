<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class user_search_form extends moodleform {
    //Add elements to form
    public function definition() {
        $mform = $this->_form; // Don't forget the underscore! 
        $mform->addElement('html', '<h1>Incident Management - User Search</h1>');    
        $mform->addElement('text', 'lastname', "Last Name"); 
        $mform->setType('lastname',PARAM_TEXT);
        $mform->addElement('text', 'firstname', "First Name"); 
        $mform->setType('firstname',PARAM_TEXT);
        $mform->addElement('text', 'idnumber', "Employee ID");
        $mform->setType('idnumber',PARAM_TEXT);
        $mform->addElement('text', 'username', "Badge Number"); 
        $mform->setType('username',PARAM_TEXT);
        $this->add_action_buttons(true,'Search');
    }
  
    //Custom validation should be added here
    public function validation($data, $files) {
        return array();
    }
}
