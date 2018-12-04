<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class manage_classifications_form extends moodleform {
    //Add elements to form
    public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
       
        $mform->addElement('html', '<h1>Incident Management - Manage Classifications</h1>');
                
        $mform->addElement('text', 'classification', "Classification");
        $mform->setType('classification',PARAM_TEXT);
        $mform->addRule('classification', "Classification max length exceeded (2 char max)", 'maxlength',2, 'server');
        $mform->addRule('classification','Classification is required','required');
        $mform->addElement('text', 'points', "Points"); 
        $mform->addRule('points',"Points max digits exceeded (max digits=3)",'maxlength',3,'server');
        $mform->addRule('points',"Points must be numeric",'numeric',null,'server');        
        $mform->setType('points', PARAM_INT);
         $mform->addRule('points','Points is required','required');
        $mform->addElement('text', 'expire_days', "Days until expired"); 
        $mform->addRule('expire_days',"Expire days max digits exceeded (max digits=3)",'maxlength',3,'server');
        $mform->addRule('expire_days',"Expire days must be numeric",'numeric',null,'server');
        $mform->setType('expire_days', PARAM_INT);
         $mform->addRule('expire_days','Expire Days is required','required');
        $mform->addElement('hidden', 'id', "Classification ID");
        $mform->setType('id',PARAM_INT);
        $this->add_action_buttons(true,'Save');        
    }
  
    //Custom validation should be added here
    public function validation($data, $files) {
        return array();
    }
}
