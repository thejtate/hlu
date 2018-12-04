<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class emp_master_form extends moodleform {
    //Add elements to form
    public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
                
        $mform->addElement('text', 'lastname', "Last Name"); 
        $mform->setType('lastname',PARAM_TEXT);
        
        $mform->addElement('text', 'firstname', "First Name"); 
        $mform->setType('firstname',PARAM_TEXT);
        
        $mform->addElement('text', 'username', "Badge Number"); 
        $mform->setType('username',PARAM_TEXT);
        
        $certificationStatusList=$this->getCertificationStatuses();
        $certificationStatuses=$this->formatCertificationStatusArray($certificationStatusList);
        $select=$mform->addElement('select', 'cert_status_id', "License Status",$certificationStatuses);
        $select->setSelected(certStatus::ACTIVE);
        $mform->addElement('select', 'orgid', "Department",department::getDepartmentList());
        $certificationList = certification::getList();
        $certifications =  certification::formatListArray($certificationList);
        $mform->addElement('select', 'certifid', "Certification", $certifications);
        //$mform->addElement('checkbox', 'terminated', "","Show terminated employees.");
           
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'searchbutton', 'Search');
        $buttonarray[] = &$mform->createElement('submit', 'pdfbutton', 'Generate PDF');
        $buttonarray[] = &$mform->createElement('submit', 'csvbutton', 'Generate CSV');
        
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    private function formatCertificationStatusArray($records)
    {
        $array=array(''=>'All Statuses');
        foreach($records as $value){ 
                $array[$value->id]=$value->cert_status;
        }
        return $array;
    }
    
    private function getCertificationStatuses()
    {
        global $DB;
        return $DB->get_records_select("block_incident_cert_status","",null,"cert_status");        
    }
    
    //Custom validation should be added here
    public function validation($data,$files) {
        $errors=array();
        return $errors;
    }
}
