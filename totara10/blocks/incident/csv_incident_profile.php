<?php
require_once('includes.php');
require_once('employeeCourse.class.php');
require_once($CFG->dirroot.'\blocks\incident\certification.class.php');
require_once($CFG->dirroot.'\blocks\incident\incident.class.php');
require_once($CFG->dirroot.'\MPDF\mpdf.php');

// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
$cvsTimestamped = 'masteremployee-' . date('Y-m-d_is') . '.csv';
header("Content-Disposition: attachment; filename=$cvsTimestamped");
// create a file pointer connected to the output stream

// pull basic user info via http build query
$userData = $_GET['userInfo'];
$userid = $userData['userid'];
$firstname = $userData['firstname'];
$lastname = $userData['lastname'];
$idnumber = $userData['idnumber'];
$username = $userData['username'];
$currentpoints = $userData['currentpoints'];
$vision = $userData['vision'];
$other = $userData['other'];

$output = fopen('php://output', 'w');

$mainTitleHeader = array('Incident Management - Employee Detail Report');
fputcsv($output, $mainTitleHeader);

$headers = array('Test');
fputcsv($output, $headers);
fputcsv($output, array());

// user info
$userName = array('Name:', $lastname, $firstname);
$userBadge = array('Badge:', $idnumber);
$userEmployeeNum = array('Employee:', $username);
$userVision = array('Vision Res:', $vision);
$userOther = array('Other Res:', $other);
$userTotalPoints = array('Current Points:', $currentpoints);
fputcsv($output, $userName);
fputcsv($output, $userBadge);
fputcsv($output, $userEmployeeNum);
fputcsv($output, $userVision);
fputcsv($output, $userOther);
fputcsv($output, $userTotalPoints);
fputcsv($output, array());


$certificationList = incident::getCertificationList($userid);
$certificationStatusList = incident::getCurrentCertificationStatus($userid);
$incidentList = incident::getIncidentList($userid);

// Current Certifications section
////
$mainCertificateHeader = array('Current Certifications');
$mainCertHeaders = array('Certification','Expiration Date','Completion Window Opens','Completion Process Status','Current Points','Current Stats','Suspension Ends');
fputcsv($output, $mainCertificateHeader);
fputcsv($output, $mainCertHeaders);
foreach ($certificationList as $records) {

    $certification=$records->fullname;

    if($records->timewindowopens==0){
        $timeWindowOpens='N/A';
    }
    else{
        $timeWindowOpens=date('m/d/Y',$records->timewindowopens);
    }
    
    if($records->status==3 and $records->timeexpires>0){
        $certExpiration = date('m/d/Y',$records->timeexpires);
        $certificationProcessStatus="Completed";
    }else{
        
        switch($records->status){
            case '1':$certificationProcessStatus='Assigned';
                        $certExpiration='N/A';
                        break;
            case '2': $certificationProcessStatus='In Progress';
                            $certExpiration='N/A';
                        break;
            case '4': $certificationProcessStatus="Expired";
                            $certExpiration = date('m/d/Y',$records->timeexpires);
                        break;
        }
    }
    
    if(isset($certificationStatusList[$records->id]->cert_status)){
        $currentCertificationStatus=$certificationStatusList[$records->id]->cert_status;
    }
    else{
        $currentCertificationStatus='N/A';
    }
    
    if($currentCertificationStatus=='Suspended'){
        $suspensionDateEnds=date('m/d/Y',$certificationStatusList[$records->id]->suspension_date_ends);
    }
    else{
        $suspensionDateEnds='N/A';
    }
    
    $points=incident::certificationIncidentPoints($records->id);
    $certHistory = array($certification,$certExpiration,$timeWindowOpens,$certificationProcessStatus,$points,$currentCertificationStatus,$suspensionDateEnds);    
    fputcsv($output, $certHistory);
}
fputcsv($output, array());


// Incident History section
////
$mainIncidentHeader = array('Incident History');
$mainIncHeaders = array('Incident Date-Time', 'Points', 'Date When Removed From Record', 'Description');
fputcsv($output, $mainIncidentHeader);
fputcsv($output, $mainIncHeaders);

foreach ($incidentList as $records) {
    $datetime = date('m/d/Y h:i:sa',$records->incident_datetime);
    $description = $records->description;

    if($records->expires_date==0){
        $expires_date='Never';
    } else {
        $expires_date = date('m/d/Y',$records->expires_date);
    }

    $points = $records->points;
    $incidentTable = array($datetime, $points, $expires_date, $description);   
    fputcsv($output, $incidentTable); 
}