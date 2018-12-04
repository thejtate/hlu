<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class choose_certification_form extends moodleform {
    //Add elements to form
    public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
                
        $certificationList = certification::getList();
        $certifications = certification::formatListArray($certificationList,true);
        $mform->addElement('select', 'certifid', "Certification",$certifications);
        $mform->addRule('certifid', "Certification is required", 'required', null, 'server');
         
        $radioarray = array();
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'New', 1);
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'Recertification', 2);
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'Review', 3);
        
        $mform->addGroup($radioarray, 'radioar', 'Certification Path', array(' '), false);
        $mform->addRule('radioar', "Certification path is required", 'required', null, 'server');
        
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'searchbutton', 'Select and Continue');
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
    
    //Custom validation should be added here
    public function validation($data, $files) {
        $errors = array();
        return $errors;
    }
}
