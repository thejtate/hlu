<?php
include_once("includes.php");
include_once("classes/courseSchedule.class.php");
unset($_SESSION['schedule']['blesid']);

if(isset($_POST['button']) and ($_POST['button']=='Back to Results' or $_POST['button']=='Back To License Management Menu')){

   // if(courseSchedule::isComplete($_SESSION['schedule']['userid'],$_SESSION['schedule']['certifid']))
   // {
        if($_POST['button']=='Back to Results'){
            redirect('employee_scheduling_report.php');
        }
        else{
            redirect('view.php');
        }
   /* }
    else
    {
        $error="Please enter all dates, instructors and rooms for each course";
    }*/
}

$action= optional_param('action','',PARAM_ALPHA);
$blesid=optional_param('blesid',0,PARAM_INT);

if(isset($_GET['uid']) && isset($_GET['cid'])){
    $_SESSION['schedule'] = array();
    $_SESSION['schedule']['userid'] = optional_param('uid', 0, PARAM_INT);
    $_SESSION['schedule']['certifid'] = optional_param('cid', 0, PARAM_INT);
}

if($action='delete' and $blesid>0){
  //  $DB->delete_records_select("block_license_emp_sched","id=:id",array('id'=>$blesid));

    $data=  new stdClass();
    $data->instructorid=null;
    $data->roomid=null;
    $data->startdate=null;
    $data->id=$blesid;
    if (!$DB->update_record('block_license_emp_sched', $data,true)){
        print_error('updateerror', 'block_license_emp_sched');
    }
    redirect('edit_course_schedule.php?uid='.$_SESSION['schedule']['userid']."&cid=".$_SESSION['schedule']['certifid']);
}

//#shouldn't be here
if(!isset($_SESSION['schedule']) or $_SESSION['schedule']['userid']==0 or !is_numeric($_SESSION['schedule']['certifid'])){
    redirect('employee_scheduling_report.php?sbh=1');
}

$PAGE->set_url('/license/edit_course_schedule.php');
$PAGE->set_heading("Edit Course Schedule");

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License Management Menu',new moodle_url('view.php'));
$PAGE->navbar->add('Employee Schedule',new moodle_url('employee_scheduling_report.php'));
$PAGE->navbar->add('Edit Course Schedule');

echo $OUTPUT->header();

if(isset($error) and $error!=''){
	echo html_writer::tag('div',$error,array('class'=>'alert alert-danger'));
}

echo html_writer::tag('h1','Employee Course Schedule - Edit Course Schedule');

// Get UserID from DB with Badge Number
$info=$DB->get_record('user',array('id'=>$_SESSION['schedule']['userid']));
echo html_writer::tag('div','<strong>Employee: </strong> '.$info->firstname.' '.$info->lastname);
$certInfo=$DB->get_record('prog',array('certifid'=>$_SESSION['schedule']['certifid']));
echo html_writer::tag('div','<strong>Certification: </strong> '.$certInfo->fullname);
echo html_writer::tag('div','&nbsp;');

$courseSchedule=  courseSchedule::getUserSchedule($_SESSION['schedule']['userid'],$_SESSION['schedule']['certifid']);
$table=  courseSchedule::createEditableCourseScheduleTableList($courseSchedule);
echo html_writer::table($table);

echo html_writer::start_tag("form",array('method'=>'post','action'=>"edit_course_schedule.php"));
echo html_writer::start_tag("button",array('name'=>'button','value'=>"Back to Results"));
echo "Back to Results";
echo html_writer::start_tag("button",array('name'=>'button','value'=>"Back To License Management Menu"));
echo "Back To License Management Menu";

echo html_writer::end_tag('form');
echo $OUTPUT->footer();
