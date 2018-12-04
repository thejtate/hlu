<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class apply_to_certif_form extends moodleform {
    
    //Add elements to form
    public function definition() {
    }

    
    public function addButtons()
    {
        $mform=$this->_form;
         $buttonarray=array();
         $buttonarray[] =& $mform->createElement('submit', 'submitbutton', 'Confirm');
         $buttonarray[] =& $mform->createElement('submit', 'delete', 'Delete Incident');
         $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
    
  public function addCertificationCheckboxes($certificationList)
  {  
        $mform = $this->_form; // Don't forget the underscore! 
        if(is_array($certificationList) and count($certificationList)>0){
            $checkboxarray=array();
            foreach($certificationList as $record){
                $id=$record->id;
                $fullname=$record->fullname;
                
                if($record->timeexpires > 0){
                   $expires='Expires: '.date('m/d/Y',$record->timeexpires);
                }else{
                    switch($record->status){
                        case '0': $status='Unset';
                                      break;
                        case '1': $status='Assigned';
                                      break;
                        case '2': $status='In Progress';
                                      break;
                        case '4': $status='Expired';
                                      break;
                    }
                    $expires="Status: $status";
                }
              //  $checkboxarray[] =& $mform->createElement('checkbox', 'certif_completion_ids['.$id.']', '', $fullname." ($expires)", 1);
                $mform->addElement('checkbox', 'certif_completion_ids['.$id.']', '', $fullname." ($expires)", 1);
            }             
            //$mform->addGroup($checkboxarray, 'checkboxar', '', array(' '), false);
        }
        else{
         $mform->addElement('html','<div>No current certifications</div><br>');   
        }          
    }
    
    //Custom validation should be added here
    public function validation($data,$files) {
        $errors=array();
        if($data['submitbutton']=='Confirm'){
        }
        return $errors;
    }
}
