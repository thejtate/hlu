<?php

require_once('includes.php');
include_once("employee_scheduling_form.php");
require_once($CFG->dirroot.'\MPDF\mpdf.php');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License Management Menu', 'view.php');
$PAGE->navbar->add('Employee Schedule');

#set page header
$PAGE->set_url('/blocks/license/employee_scheduling_report.php', array());
$PAGE->set_heading('Employee Scheduling Report');

$mform = new employee_scheduling_form();

if ($mform->is_cancelled()) {
    redirect('view.php');
    
} elseif ($fromform = $mform->get_data() and ($fromform->downloadbutton or $fromform->searchbutton)) {
    $wheres = courseSchedule::setWheres($fromform);
    $params = courseSchedule::setParams($fromform);
    $_SESSION['scheduling']['wheres'] = $wheres;
    $_SESSION['scheduling']['params'] = $params;

    $scheduleList = courseSchedule::getScheduleList($wheres, $params);

    $department = array();
    foreach ($scheduleList as $record) {
        $departmentInfo = new stdClass();
        $departmentInfo->idnumber = $record->department;
        $departmentInfo->departmentname = $record->departmentname;
        $department[] = $departmentInfo; 
    }
    $departmentList = array_unique($department, SORT_REGULAR);

} elseif(isset($_SESSION['scheduling']['params']) && isset($_SESSION['scheduling']['wheres'])){

    $scheduleList = courseSchedule::getScheduleList($_SESSION['scheduling']['wheres'], $_SESSION['scheduling']['params']);

    $department = array();
    foreach ($scheduleList as $record) {
        $departmentInfo = new stdClass();
        $departmentInfo->idnumber = $record->department;
        $departmentInfo->departmentname = $record->departmentname;
        $department[] = $departmentInfo; 
    }
    $departmentList = array_unique($department, SORT_REGULAR);
}

if ($fromform->downloadbutton) {
    #generate pdf
    $mpdf = new mPDF('utf-8', // mode - default 
            'Letter', // format - A4, for example, default ''
            6, // font size - default 0
            'Helvetica', // default font family
            5, // margin_left
            5, // margin right
            18, // margin top
            5, // margin bottom
            8, // margin header
            2, // margin footer
            'P');  // L - landscape, P - portrait
            $mpdf->setFooter('|Page {PAGENO} of {nb}|');
    
    //David Lister
    //Added Hobby Lobby University header similar to site header
    $headerhtml = '<div class="logo"><img style="max-width: 300px; display: inline-block; width: 100%;" src="../../theme/hobbylobby/pix/logo.png" alt="Hobby Lobby"/>';
    $headerhtml .= '<span style="color: #0848AB; font-size: 20px; font-family: Georgia,Arial,Helvetica;">University</span></div>';

    $mpdf->SetHTMLHeader ( $headerhtml, 'O', 'false');
    $html .= html_writer::tag("h1", "Employee Scheduling Report");
    $html .= html_writer::tag("div", "As of: " . date('m/d/Y H:i:s'), array('style' => 'padding:5px;'));
    $departmentListCount = count($departmentList);
    $count = 0; 

    foreach ($departmentList as $department) {
        $html.= html_writer::tag("h2", $department->idnumber.' '.$department->departmentname);
        $startdate = '';

        foreach ($scheduleList as $record) {

            if ($record->department == $department->idnumber) {
                $formattedStartDate = date('l, F d, Y', $record->startdate);

                if ($startdate != $formattedStartDate) {

                    if (isset($table)) {
                        $html.= html_writer::table($table);
                    }

                    $html.= html_writer::tag("h3", $formattedStartDate,array('style'=>'background-color:#EFEFEF'));
                    $startdate = $formattedStartDate;
                    $table = new html_table();
                    $table->size = array('5%', '17%', '17%', '18%', '10%','13%', '7%', '13%');
                    $table->head = array("Badge","Name","Certification","Course", "Cert Type", "Instructor", "Time","Location");
                    $table->width="100%";
                    $table->align = array('left','left','left','left','left','left','left','left');    
                }
                $time = date('h:i:sa', $record->startdate);
                $table->data[] = array($record->badge, $record->employee, $record->certification, $record->coursename, $record->certifpath,$record->instructor, $time, $record->room);
            }
        }

        if (isset($table)) {
            $html.=html_writer::table($table);
            unset($table);
        }

        $count++;
        if($count<$departmentListCount){
           $html.='<div style="page-break-after: always;">&nbsp;</div>';
           $html.= html_writer::tag("h1", "Employee Scheduling Report");
           $html.=html_writer::tag("div", "As of: " . date('m/d/Y H:i:s'), array('style' => 'padding:5px;'));
        }
    }
 
    $mpdf->WriteHTML($html);

    $mpdf->Output('employeeScheduleReport.pdf', 'D');
    die;
}

echo $OUTPUT->header();
echo html_writer::tag("h1", "Employee Scheduling Report");
$mform->display();

if(is_array($departmentList)){

    foreach ($departmentList as $department) {
        echo html_writer::tag("h2", $department->idnumber.' '.$department->departmentname);

        $startdate = '';
        foreach ($scheduleList as $record) {

            if ($record->department == $department->idnumber) {
                $formattedStartDate = date('l, F d, Y', $record->startdate);
                
                if ($startdate != $formattedStartDate) {

                    if (isset($table)) {
                        echo html_writer::table($table);
                    }

                    echo html_writer::tag("h3", $formattedStartDate);
                    $startdate = $formattedStartDate;
                    $table = new html_table();
                    $table->size = array('5%', '17%', '17%', '18%', '5%','13%', '12%', '13%');
                    $table->head = array("Badge","Name","Certification","Course", "Cert Type", "Instructor", "Time","Location");
                }
                $time = date('h:i:sa', $record->startdate);
                $edit = vsprintf("<a href='edit_course_schedule.php?uid=%s&cid=%s' title='Edit Schedule'>%s</a>", array($record->userid,  $record->certifid,$record->badge));
                $table->data[] = array($edit, $record->employee, $record->certification, $record->coursename, $record->certifpath,$record->instructor, $time, $record->room);
            }
        }
        if (isset($table)) {
            echo html_writer::table($table);
            unset($table);
        }
    }
}

echo $OUTPUT->footer();