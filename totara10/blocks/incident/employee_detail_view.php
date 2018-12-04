<?php
include_once("includes.php");
include_once("employeeCourse.class.php");
$PAGE->set_url('/blocks/incident/employee_detail_view.php', array());
$camefrom=optional_param('camefrom',"",PARAM_ALPHA);

if($camefrom!=''){
    $_SESSION['camefrom']=$camefrom;
}

$PAGE->set_pagelayout('standard');
$PAGE->navbar->ignore_active();

if(isset($_SESSION['camefrom']) and $_SESSION['camefrom']=='license'){
    $PAGE->navbar->add('License Management Menu',new moodle_url('../license/view.php'));
    $PAGE->navbar->add('View/Print License',new moodle_url('../license/view_license.php'));
    $PAGE->navbar->add('Employee Details');    
}else{
    $PAGE->navbar->add('Incident Management Menu',new moodle_url('view.php'));
    $PAGE->navbar->add('Employee Search',new moodle_url('user_search_view.php'));
    $PAGE->navbar->add('Employee Details');
}

$userid = optional_param('userid', '0', PARAM_INT);
$action = optional_param('action','',PARAM_ALPHA);

echo $OUTPUT->header();
switch($action){
    case 'Saved':  echo html_writer::tag('div', 'Incident has been saved.', array('class'=>'alert alert-info'));
                            break;
    case 'Exists': echo html_writer::tag('div', 'Incident already exists.', array('class'=>'alert alert-danger'));
                            break;
    case 'Deleted': echo html_writer::tag('div', 'Incident has been deleted.', array('class'=>'alert alert-info'));
                            break;
    case 'Updated': echo html_writer::tag('div', 'Incident has been updated.', array('class'=>'alert alert-info'));
}

$info = $DB->get_record('user',array('id'=>$userid));

/* See if user has a max total points till recoke OR has single class-A (10pt) violation */
/* Revoke all licenses if ether checks pass as true. */
$incident = new incident(); 
$totalPoints = $incident->getUserIncidentPoints($userid);
$filteredCertIds = $incident->getFilteredCertIds($userid);

//if returns true, then revoke
$checkLicensesPtTotal = $incident->checkLicensesPtTotal($userid, $totalPoints);
//if returns true, then revoke
$checkLicenseClassA = $incident->checkLicenseClassA($userid);
//revoke licenses
$runRevoker = $incident->revokeCertifications($userid, $filteredCertIds, $checkLicensesPtTotal, $checkLicenseClassA);

if($info) {

    $vision = 'No';
    $other = 'No';
    $addlInfo = employeeCourse::getEmployeeAddlInfo($userid);

    foreach($addlInfo as $data){
        switch ($data->fieldname){
            case 'Vision': $vision=$data->data==0?'No':'Yes';
            break;
            case 'OtherRestriction': 
            if($data->data==1) {
                $other=$addlInfo['OtherRestrictionInfo']->data;
            } else {
                $other='No';
            }
            break;
        }
    }     
}

$userInfo = array(
    'userid' => $userid,
    'firstname' => $info->firstname,
    'lastname' => $info->lastname,
    'idnumber' => $info->idnumber,
    'username' => $info->username,
    'currentpoints' => $totalPoints,
    'vision' => $vision,
    'other' => $other,
);
$passQuery = http_build_query(array('userInfo'=>$userInfo));

if($info){
    echo html_writer::tag("h1", "Incident Management - Employee Detail Report");

    $certificationList = incident::getCertificationList($userid);
    $certificationStatusList = incident::getCurrentCertificationStatus($userid);
    $incidentList = incident::getIncidentList($userid);
    
    echo html_writer::tag('div','<strong>Name: </strong><a title="Employee profile" target="_new" href="../../user/profile.php?id='.$info->id.'">'.$info->lastname.', '.$info->firstname.'</a>');
    echo html_writer::tag('div','<strong>Badge#: </strong>'.$info->username);
    echo html_writer::tag('div','<strong>Emp ID#: </strong>'.$info->idnumber);
    echo html_writer::tag('div','<strong>Vision Res: </strong>'.$vision);
    echo html_writer::tag('div','<strong>Other Res: </strong>'.$other);
    
    echo html_writer::tag('div','<strong>Current Points: </strong>'.$totalPoints);
    echo html_writer::tag('hr','');
    echo html_writer::tag('div','<a href="pdf_incident_profile.php?'.$passQuery.'">Download PDF</a> | <a href="csv_incident_profile.php?'.$passQuery.'">Download CSV</a>');
    echo html_writer::tag('hr','');
    echo html_writer::tag('h3','Current Certifications');
    $certificationTable=incident::createCertificationTableList($certificationList, $certificationStatusList);
    echo html_writer::table($certificationTable);
    echo html_writer::tag('hr','');   
    
    echo html_writer::tag('h3','Certification History');
    foreach($certificationList as $certification) {
        echo html_writer::tag('h4',$certification->fullname,array('style'=>'font-color:font-weight:bold;'));
        $certCourseHistory = employeeCourse::getCertificationHistory($certification->certifid, $userid);
        $historyTable = employeeCourse::createHistoryTable($certCourseHistory);
        echo html_writer::table($historyTable);
    }
    
    echo html_writer::tag('hr','');   
    echo html_writer::tag('h3','Incident History');
    echo html_writer::tag('div','<a href="incident_view.php?userid='.$userid.'">Add New Incident</a>');
    $incidentTable = incident::createIncidentTableList($incidentList);
    echo html_writer::table($incidentTable);

    echo $OUTPUT->footer();
} else {
    redirect("view.php");
}
?>

<script>
    console.log("<?php echo "License Point Total revoker returned: ". ($checkLicensesPtTotal == 1 ? "true":"false") ?>");
    console.log("<?php echo "License Class A revoked returned: " . ($checkLicenseClassA == 1 ? "true":"false") ?>");
</script>