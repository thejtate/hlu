<?php
require_once('includes.php');
include_once("instructor_scheduling_form.php");
require_once($CFG->dirroot.'\MPDF\mpdf.php');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License Management Menu', 'view.php');
$PAGE->navbar->add('Instructor Schedule');

#set page header
$PAGE->set_url('/blocks/license/instructor_scheduling_report.php', array());
$PAGE->set_heading('Instructor Scheduling Report');

$mform = new instructor_scheduling_form();

if ($mform->is_cancelled()) {
    redirect('view.php');
}
elseif ($fromform = $mform->get_data() and ($fromform->downloadbutton or $fromform->searchbutton)) {
    $wheres = courseSchedule::setWheres($fromform);
    $params = courseSchedule::setParams($fromform);
    $scheduleList = courseSchedule::getScheduleList($wheres, $params,'instructor');
    $instructorList = array();
    foreach ($scheduleList as $record) {
        $instructorList[$record->instructor] = $record->instructor;
    }
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

    $html .= html_writer::tag("h1", "Instructor Scheduling Report");
    $html.=html_writer::tag("div", "As of: " . date('m/d/Y H:i:s'), array('style' => 'padding:5px;'));
    $instructorListCount=count($instructorList);
    $count=0;
    foreach ($instructorList as $instructor) {
        $html.= html_writer::tag("h2", $instructor);
        $startdate = '';
        foreach ($scheduleList as $record) {
            if ($record->instructor == $instructor) {
                $formattedStartDate = date('l, F d, Y', $record->startdate);
                if ($startdate != $formattedStartDate) {

                    if (isset($table)) {
                        $html.= html_writer::table($table);
                    }

                    $html.= html_writer::tag("h3", $formattedStartDate,array('style'=>'background-color:#EFEFEF'));
                    $html.= html_writer::tag("div", $record->certification,array('style'=>'font-weight:bold;background-color:#A9A9A9'));
                    
                    $startdate = $formattedStartDate;
                    $table = new html_table();
                    $table->size = array('5%', '20%', '5%', '20%', '10%', '15%', '5%','20%');
                    $table->head = array("Badge","Name","Dept","Manager","Cert Type","Test","Time","Location");
                    $table->width="100%";
                    $table->align = array('left','left','left','left','left','left','left','left');       
                    
                }
                $time = date('h:i:sa', $record->startdate);
                $table->data[] = array($record->badge, $record->employee, $record->department,$record->manager, $record->certifpath,$record->coursename,  $time, $record->room);
            }
        }
        if (isset($table)) {
            $html.=html_writer::table($table);
            unset($table);
        }
        
        $count++;
        if($count < $instructorListCount){
           $html.='<div style="page-break-after: always;">&nbsp;</div>';
           $html.= html_writer::tag("h1", "Instructor Scheduling Report");
           $html.=html_writer::tag("div", "As of: " . date('m/d/Y H:i:s'), array('style' => 'padding:5px;'));
        }
    }
    $mpdf->WriteHTML($html);

    $mpdf->Output('instructorScheduleReport.pdf', 'D');
    die;
}

echo $OUTPUT->header();
echo html_writer::tag("h1", "Instructor Scheduling Report");
$mform->display();

if(is_array($instructorList)){
foreach ($instructorList as $instructor) {
    echo html_writer::tag("h2", $instructor);

    $startdate = '';
    foreach ($scheduleList as $record) {
        if ($record->instructor == $instructor) {
            $formattedStartDate = date('l, F d, Y', $record->startdate);
            if ($startdate != $formattedStartDate) {

                if (isset($table)) {
                    echo html_writer::table($table);
                }

                echo html_writer::tag("h3", $formattedStartDate);
                echo html_writer::tag("div", $record->certification,array('style'=>'font-weight:bold;background-color:#A9A9A9'));
                $startdate = $formattedStartDate;
                $table = new html_table();
                $table->size = array('5%', '20%', '5%', '20%', '10%', '15%', '5%','20%');
                $table->head = array("Badge","Name","Dept","Manager","Cert Type","Test","Time","Location");
            }
            $time = date('h:i:sa', $record->startdate);
            $table->data[] = array($record->badge, $record->employee, $record->department,$record->manager,$record->certifpath,$record->coursename,  $time, $record->room);
        }
    }
    if (isset($table)) {
        echo html_writer::table($table);
        unset($table);
    }
  }
}
echo $OUTPUT->footer();