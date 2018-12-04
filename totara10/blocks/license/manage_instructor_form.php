<?php

//moodleform is defined in formslib.php
include_once("$CFG->libdir/formslib.php");

class manage_instructor_form extends moodleform
{
     public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
        $mform->addElement('text', 'firstname', "First Name"); 
        $mform->setType('firstname',PARAM_TEXT);
        $mform->addRule('firstname','First Name is required','required');
        $mform->addElement('text', 'lastname', "Last Name"); 
        $mform->setType('lastname',PARAM_TEXT);
        $mform->addRule('lastname','Last Name is required','required');
        $mform->addElement('hidden', 'id', "Instructor ID");
        $mform->setType('id',PARAM_INT);
      
        $this->add_action_buttons(true,'Save');        
    }
  
    //Custom validation should be added here
    public function validation($data, $files) {
        return array();
    }
}
