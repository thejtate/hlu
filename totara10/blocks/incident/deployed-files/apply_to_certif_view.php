<?php
include_once("includes.php");
$PAGE->set_url('/blocks/incident/apply_certif_view.php', array());
if(isset( $_SESSION['incidentid']) and incident::initialIncidentExists($_SESSION['incidentid'])){
    $incidentid= $_SESSION['incidentid'];
    $userid=incident::setUserId($_SESSION['userid']);
}else{
      redirect("view.php?action=NotExists");
     exit;
}

if(incident::incidentExists($incidentid)){
    redirect("employee_detail_view.php?userid=$userid&action=Exists");
    exit;
}

echo $OUTPUT->header();

$incidentInfo=incident::getIncident($incidentid);

$mform = new apply_to_certif_form();
$certificationList=incident::getCertificationList($userid);
$mform->addCertificationCheckboxes($certificationList);
$mform->addButtons();

if(isset($_POST['delete']) and $_POST['delete']){
    $DB->delete_records_select("block_incident","id=:id",array('id'=>$incidentid));
     redirect("employee_detail_view.php?userid=$userid&action=Deleted");
    exit;
}
else if($fromform = $mform->get_data()){
    if(is_array($fromform->certif_completion_ids)){
        foreach($fromform->certif_completion_ids as $certif_completion_id=>$boolean){
            $data=  new stdClass();
            $data->certif_completion_id=$certif_completion_id;
            $data->user_id=$userid;
            $data->incident_id=$incidentid;
            $data->createby=$USER->id;
            $data->createdate=time();
            $data->deleted=0;
            if (!$DB->insert_record('block_incident_certif', $data,true)){
                   print_error('inserterror', 'block_incident_certif');
            }     
            
           $suspension_date_ends=incident::getSuspensionDateExpires($certif_completion_id);
               
            if($suspension_date_ends == -1) {
                $cert_status_id = certStatus::REVOKED;
                $suspension_date_ends = 0;
            } elseif($suspension_date_ends > 0) {
                $cert_status_id = certStatus::SUSPENDED;
            } elseif($cert_status_id == certStatus::INACTIVE) {
                $cert_status_id = certStatus::INACTIVE;
            } else {
                //$cert_status_id = certStatus::ACTIVE;
        }
            
            $block_incident_certif_info_id=incident::certificateIncidentExists($certif_completion_id);
            if($block_incident_certif_info_id){
                $data=new stdClass();
                $data->id=$block_incident_certif_info_id;
                $data->cert_status_id=$cert_status_id;
                $data->suspension_date_ends=$suspension_date_ends;
                if (!($DB->update_record('block_incident_certif_info', $data))){
                       print_error('updateerror', 'block_incident_certif_info');
                }
            }
            else{
                 $data=new stdClass();
                 $data->certif_completion_id=$certif_completion_id;
                 $data->user_id=$userid;
                 $data->cert_status_id=$cert_status_id;
                 $data->suspension_date_ends=$suspension_date_ends;               
                if (!($DB->insert_record('block_incident_certif_info', $data,true))){
                       print_error('inserterror', 'block_incident_certif_info');
                }
            }
            
            #To tie into moodle tables, certif_completion table
            #if just suspending, move timewindowopens to that date when suspension is lifted and expire cert           
            #if revoked, then set timeexpires to today and push out next window out a year
            $data=new stdClass();
            $data->id=$certif_completion_id;
            if($cert_status_id==certStatus::REVOKED){
                #revoke process
                $data->timeexpires=time();
                $data->timewindowopens=time()+(60*60*24*365);#push out one full year, not sure what else to do here
                if (!($DB->update_record('certif_completion', $data))){
                       print_error('updateerror', 'certif_completion');
                }
            }
            elseif($cert_status_id==certStatus::SUSPENDED){
                #suspension process
                $data->timewindowopens=$suspension_date_ends;           
                $data->timeexpires=time();
                if (!($DB->update_record('certif_completion', $data))){
                       print_error('updateerror', 'certif_completion');
                }
            }            
            
          
            
            #also need to update program if employee has no previous cert to get dates to show properly
            #on certification screen           
            $certifid=incident::getCertificationId($certif_completion_id);
            $progamCompletionInfo=incident::getProgamCompletionInfo($userid,$certifid);
            $data=new stdClass();
            $data->id=$progamCompletionInfo->id;
            $data->timestarted=$suspension_date_ends;
            $data->timedue=$progamCompletionInfo->timedue + $suspension_date_ends;
            if (!($DB->update_record('prog_completion', $data))){
                       print_error('updateerror', 'prog_completion');
            }
            
            #NOTE: may have to come back and unenroll also, kind of hard to do with all the intracacies. Just noting for later.
        }
    }
    unset($_SESSION['userid']);
    unset($_SESSION['incidentid']);
    redirect("employee_detail_view.php?userid=$userid&action=Saved");
}


#display the page
echo html_writer::tag('h1','Incident Management - Apply Incident To Certification (Step 2 of 2) ');


echo html_writer::div('<strong>Incident Description:</strong><br>'.$incidentInfo->description,'results');
echo html_writer::div('<strong>Points: </strong>'.$incidentInfo->points);
echo html_writer::div('<strong>Expires: </strong>'.date('m/d/Y', $incidentInfo->expires_date));

#get user info
$info=$DB->get_record('user',array('id'=>$userid));
echo html_writer::tag('div','<br>Apply incident to certifications for: <strong>'.$info->lastname.', '.$info->firstname.'</strong><br><br>');
echo html_writer::tag('div','Check the certifications below that apply to this incident and click "Confirm". <br> If you do not want to apply this incident to any certifications just click "Confirm". <br><br>');
$mform->display();
echo $OUTPUT->footer();
