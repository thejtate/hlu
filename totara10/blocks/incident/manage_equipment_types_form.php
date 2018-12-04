<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class manage_equipment_types_form extends moodleform {
    //Add elements to form
    public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
       
        $mform->addElement('html', '<h1>Incident Management - Manage Equipment Types</h1>');
                
        $mform->addElement('text', 'equipment_type', "Equipment Type Name");
         $mform->addRule('equipment_type', "Equipment Type max length exceeded (50 char max)", 'maxlength',50, 'server');
        $mform->addElement('hidden', 'id', "Equipment Type ID");
        $this->add_action_buttons(true,'Save');        
    }
  
    //Custom validation should be added here
    public function validation($data, $files) {
        return array();
    }
}
