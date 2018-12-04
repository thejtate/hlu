<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class employee_scheduling_form extends moodleform {
    //Add elements to form
    public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
        $mform->addElement('date_selector', 'startdate', "Start Date");
        $mform->addElement('date_selector', 'enddate', "End Date");
        $mform->addElement('select', 'orgid', "Department",department::getDepartmentList());
    
        $radioarray=array();
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'All', "");
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'New', 1);
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'Recertification', 2);
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'Review', 3);
        $mform->addGroup($radioarray, 'radioar', 'Certification Path', array(' '), false);
         
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'searchbutton', 'Search');
        $buttonarray[] = &$mform->createElement('submit', 'downloadbutton', 'Generate PDF');
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
