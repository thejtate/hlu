<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class instructor_scheduling_form extends moodleform {
    //Add elements to form
    public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
        $mform->addElement('date_selector', 'startdate', "Start Date");
        $mform->addElement('date_selector', 'enddate', "End Date");
        
        
        $certificationList=  certification::getList();
        $certifications=  certification::formatListArray($certificationList,false);
        $mform->addElement('select', 'certifid', "Certification",$certifications);
          
        $radioarray=array();
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'All', "");
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'New', 1);
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'Recertification', 2);
        $radioarray[] =& $mform->createElement('radio', 'certifpath', '', 'Review', 3);
        $mform->addGroup($radioarray, 'radioar', 'Certification Path', array(' '), false);
        
        $instructorList=instructor::getList();
        $instructorListFormatted=instructor::formatListArray($instructorList, false);
        $mform->addElement('select', 'instructorid', "Instructor",$instructorListFormatted);
        
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
