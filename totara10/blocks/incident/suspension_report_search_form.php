<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class suspension_report_search_form extends moodleform {
    //Add elements to form
    public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
                
        $mform->addElement('text', 'lastname', "Last Name"); 
        $mform->setType('lastname',PARAM_TEXT);
        $mform->addElement('text', 'firstname', "First Name");
        $mform->setType('firstname',PARAM_TEXT);
        
        $mform->addElement('text', 'idnumber', "Employee ID");
        $mform->setType('idnumber',PARAM_INT);
        
        $mform->addElement('text', 'username', "Badge Number");
        $mform->setType('username',PARAM_TEXT);
        
        $mform->addElement('date_selector', 'suspension_date_from', "Suspension Date From",array('optional'=>true));
        $mform->addElement('date_selector', 'suspension_date_to', "Suspension Date To",array('optional'=>true));
        $mform->addElement('select', 'orgid', "Department",department::getDepartmentList());
    
       
        $certificationList= certification::getList();
        $certifications=  certification::formatListArray($certificationList);
        $mform->addElement('select', 'certifid', "Certification",$certifications);
       
        
         $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'searchbutton', 'Search');
        $buttonarray[] = &$mform->createElement('submit', 'downloadbutton', 'Download CSV');
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
   
    //Custom validation should be added here
    public function validation($data,$files) {
        $errors=array();
        return $errors;
    }
}
