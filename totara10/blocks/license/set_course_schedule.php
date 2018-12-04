<?php
include_once("includes.php");
include_once("classes/courseSchedule.class.php");
unset($_SESSION['schedule']['blesid']);
 
if(isset($_POST['button']) and ($_POST['button']=='Choose Another Employee' or $_POST['button']=='Back To License Management Menu')){
      if($_POST['button']=='Choose Another Employee'){
         redirect('user_search_view.php');
      }
      else{
         redirect('view.php');
      }
}

$action= optional_param('action','',PARAM_ALPHA);
$blesid=optional_param('blesid',0,PARAM_INT);

if($action='delete' and $blesid>0){
    $data=  new stdClass();
    $data->instructorid=null;
    $data->roomid=null;
    $data->startdate=null;
    $data->id=$blesid;
    if (!$DB->update_record('block_license_emp_sched', $data,true)){
        print_error('updateerror', 'block_license_emp_sched');
    }
    redirect('set_course_schedule.php');
}

#shouldn't be here
if(!isset($_SESSION['schedule']) or $_SESSION['schedule']['userid']==0 or !is_numeric($_SESSION['schedule']['certifid'])){
     redirect('user_search_view.php?action=nouser');
}

$PAGE->set_url('/license/set_course_schedule.php');
$PAGE->set_heading("Set Course Schedule");

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License/Course Schedule Management Menu',new moodle_url('view.php'));
$PAGE->navbar->add('Employee Search',new moodle_url('user_search_view.php'));
$PAGE->navbar->add('Choose Certification',new moodle_url('choose_certification.php'));
$PAGE->navbar->add('Set Course Schedule');

echo $OUTPUT->header();

if(isset($error) and $error!=''){
      echo html_writer::tag('div',$error,array('class'=>'alert alert-danger'));
}

echo html_writer::tag('h1','Employee Course Schedule - Set Course Schedule (Step 3 of 3)');
$info=$DB->get_record('user',array('id'=>$_SESSION['schedule']['userid']));
echo html_writer::tag('div','<strong>Employee: </strong> '.$info->firstname.' '.$info->lastname);
$certInfo=$DB->get_record('prog',array('certifid'=>$_SESSION['schedule']['certifid']));
echo html_writer::tag('div','<strong>Certification: </strong> '.$certInfo->fullname);
echo html_writer::tag('div','&nbsp;');

$courseSchedule=  courseSchedule::getUserSchedule($_SESSION['schedule']['userid'],$_SESSION['schedule']['certifid']);
$table=  courseSchedule::createCourseScheduleTableList($courseSchedule);
echo html_writer::table($table);

echo html_writer::start_tag("form",array('method'=>'post','action'=>"set_course_schedule.php"));
echo html_writer::start_tag("button",array('name'=>'button','value'=>"Choose Another Employee"));
echo "Choose Another Employee";
echo html_writer::start_tag("button",array('name'=>'button','value'=>"Back To License Management Menu"));
echo "Back To License Management Menu";

echo html_writer::end_tag('form');
echo $OUTPUT->footer();
