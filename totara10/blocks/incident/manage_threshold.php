<?php
include_once("includes.php");
$PAGE->set_url('/blocks/incident/manage_threshold.php', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu','view.php');
$PAGE->navbar->add('Manage Point Threshold');

echo $OUTPUT->header();

//Instantiate simplehtml_form 
$mform = new manage_threshold_form();

if ($mform->is_cancelled()) {
     redirect('view.php');
}
elseif ($fromform = $mform->get_data()){
    $mform->set_data($fromform);
  
    #two update statements for each cert status;
    $params=array();
    $params['cert_status_id']=  certStatus::SUSPENDED;
    $params['point_threshold']=$fromform->susp_point_threshold;
    $sql="update {block_incident_thresh} set point_threshold=:point_threshold where cert_status_id=:cert_status_id";
    $DB->execute($sql,$params);
 
    $params=array();
    $params['cert_status_id']=  certStatus::REVOKED;
    $params['point_threshold']=$fromform->revoke_point_threshold;
    $sql="update {block_incident_thresh} set point_threshold=:point_threshold where cert_status_id=:cert_status_id";
    $DB->execute($sql,$params);    
    
   echo html_writer::tag('div','Point Threshold updated.',array('class'=>'alert alert-info'));
     
}
else{
    $sql="select cert_status_id,point_threshold from {block_incident_thresh}";
    $currentThresholds = $DB->get_records_sql($sql);
    #reformat for set data
    $thresholdRefactoredToForm=new stdClass();
    $thresholdRefactoredToForm->susp_point_threshold=$currentThresholds[certStatus::SUSPENDED]->point_threshold;
    $thresholdRefactoredToForm->revoke_point_threshold=$currentThresholds[certStatus::REVOKED]->point_threshold;        
    $mform->set_data($thresholdRefactoredToForm);
}
$mform->display();
 echo $OUTPUT->footer();