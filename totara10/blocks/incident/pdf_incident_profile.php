<?php
require_once('includes.php');
require_once('employeeCourse.class.php');
require_once($CFG->dirroot.'\blocks\incident\certification.class.php');
require_once($CFG->dirroot.'\blocks\incident\incident.class.php');
require_once($CFG->dirroot.'\MPDF\mpdf.php');
            
$mpdf = new mPDF('utf-8',  // mode - default
                    'Letter',    // format - A4, for example, default ''
                    6,     // font size - default 0
                    'Helvetica',    // default font family
                    25,    // margin_left
                    25,    // margin right
                    19,     // margin top
                    25,    // margin bottom
                    8,     // margin header
                    2,     // margin footer
                    'P');  // L - landscape, P - portrait
                    $mpdf->setFooter('|Page {PAGENO} of {nb}|');

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

if($userData){    
    //David Lister
    //Added Hobby Lobby University header similar to site header
    $headerhtml = '<div class="logo"><img style="max-width: 300px; display: inline-block; width: 100%;" src="../../theme/hobbylobby/pix/logo.png" alt="Hobby Lobby"/>';
    $headerhtml .= '<span style="color: #0848AB; font-size: 20px; font-family: Georgia,Arial,Helvetica;">University</span></div>';
    $mpdf->SetHTMLHeader ( $headerhtml, 'O', 'false');
   
    $html .= html_writer::tag("h1", "Incident Management - Employee Detail Report");

    $certificationList = incident::getCertificationList($userid);
    $certificationStatusList = incident::getCurrentCertificationStatus($userid);
    $incidentList = incident::getIncidentList($userid);

    $html .= html_writer::tag('div','<strong>Name: </strong>'.$lastname.', '.$firstname.'</a>');
    $html .= html_writer::tag('div','<strong>Badge#: </strong>'.$username);
    $html .= html_writer::tag('div','<strong>Emp ID#: </strong>'.$idnumber);
    $html .= html_writer::tag('div','<strong>Vision Res: </strong>'.$vision);
    $html .= html_writer::tag('div','<strong>Other Res: </strong>'.$other);

    $html .= html_writer::tag('div','<strong>Current Points: </strong>'.$currentpoints);
    $html .= html_writer::tag('hr','');
    $html .= html_writer::tag('h3','Current Certifications');

    $certificationTable = incident::createCertificationTableList($certificationList, $certificationStatusList);
    $html .= html_writer::table($certificationTable);

    $html .= html_writer::tag('hr','');   
    $html .= html_writer::tag('h3','Incident History');
    $incidentTable = incident::createIncidentTableList($incidentList);
    $html .= html_writer::table($incidentTable);

    // echo $html;
    $mpdf->WriteHTML($html);
    $mpdf->Output('UserIncidientProfile-'.date('Y-m-d_is').'.pdf','D');
}