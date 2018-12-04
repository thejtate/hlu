<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class manage_threshold_form extends moodleform {
    //Add elements to form
    public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
       
        $mform->addElement('html', '<h1>Incident Management - Manage Point Threshold</h1>');
                
        $mform->addElement('text', 'susp_point_threshold', "Suspension Point Threshold");
        $mform->setType('susp_point_threshold',PARAM_INT);
        $mform->addRule('susp_point_threshold', "Suspension Point max length exceeded (2 char max)", 'maxlength',2, 'server');
        $mform->addRule('susp_point_threshold', "Suspension Point must be numeric", 'numeric');
        $mform->addRule('susp_point_threshold','Suspension Points is required','required');
        $mform->addElement('text', 'revoke_point_threshold', "Revoke Point Threshold");
         $mform->setType('revoke_point_threshold',PARAM_INT);
        $mform->addRule('revoke_point_threshold', "Revoke Point max length exceeded (2 char max)", 'maxlength',2, 'server');
         $mform->addRule('revoke_point_threshold', "Revoke Point must be numeric", 'numeric');
          $mform->addRule('revoke_point_threshold','Revoke Points is required','required');
        $this->add_action_buttons(true,'Save');        
    }
  
    //Custom validation should be added here
    public function validation($data,$files) {
        $errors=array();
        if($data['susp_point_threshold']>$data['revoke_point_threshold']){
            $errors['susp_point_threshold']="Suspended Point Threshold cannot exceed Revoke Point Threshold";
        }
        return $errors;
    }
}