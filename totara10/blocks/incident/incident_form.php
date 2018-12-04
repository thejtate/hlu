<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class incident_form extends moodleform {
    
    //Add elements to form
    public function definition() {
      
        $mform = $this->_form; // Don't forget the underscore! 
       
       $datetimeSettings=array(
                      'startyear' => date('Y')-5, 
                      'stopyear'  => date('Y')+5,
                      'timezone'  => 99,
                      'step'      =>1
                      );
        $mform->addElement('date_time_selector', 'incident_datetime', "Incident Date/Time",$datetimeSettings);
        $mform->addRule('incident_datetime', "Incident Date/Time is required", 'required', null, 'server');
        $classificationList=$this->getClassifications(); 
        $classifications=$this->formatClassificationArray($classificationList);
        $mform->addElement('select', 'classification_id', "Classification",$classifications);
        $mform->addRule('classification_id', "Classification is required", 'required', null, 'server');
        $mform->addElement('textarea', 'description', "Description", 'wrap="virtual" rows="10" cols="80"');       
        $mform->addRule('description', "Description is required", 'required', null, 'server');
        $mform->addElement('hidden','id','Incident ID');
        $mform->setType('id',PARAM_INT);
        $mform->addElement('html', '<div><strong>NOTE: Clicking Continue will create this incident prior to assignment to a certification.</strong></div><br>');
        $this->add_action_buttons(true,'Continue');
    }
    
  
    private function formatClassificationArray($records)
    {
        $array=array(''=>'Select Classification');
        foreach($records as $value)
        { 
            $array[$value->id]=$value->classification.'  (Points: '.$value->points.')';
        }
        return $array;
    }
    
    private function getClassifications()
    {
        global $DB;        
        return $DB->get_records_select("block_incident_class","deleted=0",null,"classification");
    }
    
    //Custom validation should be added here
    public function validation($data,$files) {
      
        $errors=array();
        if($data['submitbutton']=='Continue'){
            
            $classificationList=$this->getClassifications();
            $classifications=$this->formatClassificationArray($classificationList);
            $classificationKeys=array_keys($classifications);
            if(!in_array($data['classification_id'],$classificationKeys)){
                $errors['classification_id']="Invalid classification selected";                      
            }
            
            if($data['incident_datetime']>time()){
                $errors['incident_datetime']="Incident Date/Time must be in the past";
            }
        }
        return $errors;
    }
}
