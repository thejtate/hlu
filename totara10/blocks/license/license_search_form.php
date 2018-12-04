<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class license_search_form extends moodleform {
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
        $mform->addElement('select', 'cert_status_id', "License Status",$certificationStatuses);
       
        $certificationList=$this->getCertifications();
        $certifications=$this->formatCertificationListArray($certificationList);
        $mform->addElement('select', 'certifid', "Certification",$certifications);
        $mform->addElement('select', 'orgid', "Department",department::getDepartmentList());
       
        $mform->addElement('text', 'recert_in_days', "Show licenses expiring in next (days)");
        $mform->setType('recert_in_days',PARAM_INT);
        
       
        
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'searchbutton', 'Search');
        $buttonarray[] = &$mform->createElement('submit', 'downloadbutton', 'Download CSV');
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    private function formatCertificationStatusArray($records)
    {
        $array=array(''=>'All Active/Inactive Statuses');
        foreach($records as $value){ 
            if($value->id == certStatus::ACTIVE or $value->id==certStatus::INACTIVE or $value->id==certStatus::SUSPENDED or $value->id==certStatus::EXPIRED){
                $array[$value->id]=$value->cert_status;
            }
        }
        return $array;
    }
    
    private function getCertificationStatuses()
    {
        global $DB;
        return $DB->get_records_select("block_incident_cert_status","",null,"cert_status");        
    }
    
    private function formatCertificationListArray($records)
    {
        $array=array(''=>'All  Certifications');
        foreach($records as $value){ 
            $array[$value->certifid]=$value->fullname;
        }
        return $array;
    }
    
    private function getCertifications()
    {
        global $DB;
        return $DB->get_records_select("prog","",null,"fullname");
        
    }
    
    //Custom validation should be added here
    public function validation($data,$files) {
        $errors=array();
        return $errors;
    }
}
