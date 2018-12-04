<?php
include_once("includes.php");
include_once("set_course_schedule_form.php");
//Instantiate simplehtml_form 
$mform = new set_course_schedule_form();
#shouldn't be here
if(!isset($_SESSION['schedule']) or $_SESSION['schedule']['userid']==0 or !is_numeric($_SESSION['schedule']['certifid'])){
     redirect('employee_scheduling_report.php');
}

if(!isset($_SESSION['schedule']['blesid'])){
   $blesid= optional_param('blesid',0,PARAM_INT);
   $_SESSION['schedule']['blesid']=$blesid;
    $sql="select startdate,instructorid,roomid from {block_license_emp_sched} where id=:id";
    $data = $DB->get_record_sql($sql,array('id'=>$blesid));
    $mform->set_data($data);
}


#shouldn't be here
if($_SESSION['schedule']['blesid']==0){
     redirect('edit_course_schedule.php?action=nouser');
}


//Form processing and displaying is done here
 if ($mform->is_cancelled()) {
     unset($_SESSION['schedule']['blesid']);
     redirect('edit_course_schedule.php');
}
elseif ($fromform = $mform->get_data()) {
     $data=  new stdClass();
     $data->instructorid=$fromform->instructorid;
     $data->roomid=$fromform->roomid;
     $data->startdate=$fromform->startdate;
     $data->id=$_SESSION['schedule']['blesid'];
     if (!$DB->update_record('block_license_emp_sched', $data,true)){
              print_error('updateerror', 'block_license_emp_sched');
     }     
      unset($_SESSION['schedule']['blesid']);
      redirect('edit_course_schedule.php?uid='.$_SESSION['schedule']['userid']."&cid=".$_SESSION['schedule']['certifid']);
}


$PAGE->set_url('/license/set_course_date.php');
$PAGE->set_heading("Set Course Date");

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License/Course Schedule Management Menu',new moodle_url('view.php'));
$PAGE->navbar->add('Set Course Schedule',new moodle_url('set_course_schedule.php'));
$PAGE->navbar->add('Set Course Date');

echo $OUTPUT->header();
echo html_writer::tag('h1','Employee Course Schedule - Set Course Date');
$mform->display();
echo $OUTPUT->footer();
