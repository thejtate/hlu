<?php
include_once("includes.php");

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/incident/incident_view.php', array());
$PAGE->set_heading(get_string('incident', 'block_incident'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu','view.php');
$PAGE->navbar->add('User Search','user_search_view.php');
$PAGE->navbar->add('Add Incident');


// Next look for optional variables.
$userid = optional_param('userid',0, PARAM_INT);
$action = optional_param('action','',PARAM_ALPHA);
$incidentid = optional_param('incidentid',0,PARAM_INT);

//Instantiate simplehtml_form 
$mform = new incident_form();

switch($action){
    case 'saved':  echo html_writer::tag('div','Incident saved.',array('class'=>'alert alert-info'));
                            break;    
}
    
#set to session or get from session, thus the assignment part
$userid= incident::setUserId($userid);

if ($mform->is_cancelled()) {
     unset($_SESSION['fromform']);
     unset($_SESSION['userid']);
     redirect('employee_detail_view.php?userid='.$userid);
}
elseif ($fromform = $mform->get_data()){
    #get points (from classification chosen)
    $fromform->points = classification::getPoints($fromform->classification_id);

    #get type of incident 
    $fromform->incident_type = classification::getType($fromform->classification_id);

    #get incident name

   
    #createdate + days, if A, then put zero for expires_date as it never comes off
    $expiredaysToExpire = classification::getDaysToExpire($fromform->classification_id);
    if($expiredaysToExpire == 0) {
        $fromform->expires_date = 0; # never expires (permanent record) Class A case
    } elseif($expiredaysToExpire == -1) {
        $fromform->expires_date = -1; #never expires (permanent record) Special Class AR case
    } else {
        $fromform->expires_date = time() + (60*60*24*$expiredaysToExpire); /* calculate days till expire */
    }
    
    $fromform->createdate = time();
    $fromform->user_id = $userid;#employee id (user id)
    $fromform->createby = $USER->id;     #logged in user id   
    $fromform->deleted = 0;

    $mform->set_data($fromform);
    if (!($newId=$DB->insert_record('block_incident', $fromform,true))){
        print_error('inserterror', 'block_incident');
    }else{     
        $_SESSION['incidentid']=$newId;
        $_SESSION['userid']=$userid;
        redirect("apply_to_certif_view.php");
    }
}

 echo $OUTPUT->header();
#display the page
echo html_writer::tag('h1','Incident Management - Add an Incident (Step 1 of 2)');
#get user info
$info=$DB->get_record('user',array('id'=>$userid));
echo html_writer::tag('div','File an incident against: <strong> <a target="_new" title="Employee detailed" href="employee_detail_view.php?userid='.$userid.'">'.$info->lastname.', '.$info->firstname.'</a></strong><br><br>');

$mform->display();
echo $OUTPUT->footer();