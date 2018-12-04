<?php
include_once("includes.php");
include_once("choose_certification_form.php");

if(!isset($_SESSION['schedule']['userid'])){
    if(isset($_GET['userid'])){
        $_SESSION['schedule']['userid'] = $_GET['userid'];
        $userid = $_GET['userid'];
    } else {
        $userid = optional_param('userid', 0, PARAM_INT);
        $_SESSION['schedule']['userid'] = $userid;
    }
} 

#shouldn't be here
if(($_SESSION['schedule']['userid'] == 0) && ($_GET['user_id'] == 0)){
    redirect('user_search_view.php?action=nouser');
}

//Instantiate simplehtml_form 
$mform = new choose_certification_form();
//Form processing and displaying is done here
 if ($mform->is_cancelled()) {
     unset($_SESSION['schedule']);
     redirect('user_search_view.php');
}
elseif (!isset($_SESSION['schedule']['certifid']) and $fromform = $mform->get_data()) {
    
     #delete previous cert entry
     $params['userid'] = $_SESSION['schedule']['userid'];
     $params['certifid'] = $fromform->certifid;
     $sql="delete from {block_license_emp_sched} where userid=:userid and certifid=:certifid";
     $DB->execute($sql,$params);
     
     $data = new stdClass();
     $data->userid = $_SESSION['schedule']['userid'];
     $data->certifid = $fromform->certifid;
     $data->certifpath = ($fromform->certifpath==3)?2:$fromform->certifpath;
     
     if($fromform->certifpath==3){
       $data->review=1;
     }
     else{
         $data->review=0;
     }
     $data->deleted=0;

     #course list
     $courseList = certification::getCourseList($data->certifid, $data->certifpath);
     
     foreach($courseList as $course){
        $data->courseid=$course->courseid;
         if (!$DB->insert_record('block_license_emp_sched', $data,true)){
              print_error('inserterror', 'block_license_emp_sched');
        }     
     }     
     $_SESSION['schedule']['certifid']=$fromform->certifid;
     redirect('set_course_schedule.php');
}
elseif(isset($_SESSION['schedule']['certifid'])){
    redirect('user_search_view.php');
}

$PAGE->set_url('/license/choose_certification.php');
$PAGE->set_heading("Choose Certification");

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License/Course Schedule Management Menu', new moodle_url('view.php'));
$PAGE->navbar->add('Employee Search', new moodle_url('user_search_view.php'));
$PAGE->navbar->add('Choose Certification');

echo $OUTPUT->header();
echo html_writer::tag('h1','Employee Course Schedule - Choose Certification (Step 2 of 3)');
$info = $DB->get_record('user', array('id'=>$_SESSION['schedule']['userid']));
echo html_writer::tag('div','<strong>Employee: </strong> '.$info->firstname.' '.$info->lastname);
echo html_writer::tag('div','&nbsp;');
$mform->display();

echo $OUTPUT->footer();

// Pull certifid from _prog db and use id to autoselect dropdown
if(isset($_GET["certifid"])) {
    $certifid = $_GET["certifid"];
}
if (isset($certifid)){
    echo "
        <script type=\"text/javascript\">
            $('.chzn-select').val($certifid).trigger('chosen:updated');
            $('#id_certifpath_2').prop('checked', true);
        </script>
    ";
}