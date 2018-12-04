<?php
require_once('includes.php');
$PAGE->set_url('/blocks/license/view.php', array());
$PAGE->set_title('License/Course Schedule Management');
unset($_SESSION['fromform']);
unset($_SESSION['camefrom']);
unset($_SESSION['schedule']);

#set page header
require_capability('block/license:viewpages', context_course::instance($COURSE->id));
$PAGE->set_url('/blocks/license/view.php', array());
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('license', 'block_license'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License/Course Schedule Management');

$pagelinks[]= new ArrayObject();
$pagelinks[0]->title="View/Print License";
$pagelinks[0]->url="view_license.php";
$pagelinks[]= new ArrayObject();
$pagelinks[1]->title="View Certification Counts";
$pagelinks[1]->url="view_cert_counts.php";
$pagelinks[]= new ArrayObject();
$pagelinks[2]->title="View Certifications by Department";
$pagelinks[2]->url="view_dept_cert_counts.php";
$pagelinks[]= new ArrayObject();
$pagelinks[3]->title="Employee Master List of Licenses";
$pagelinks[3]->url="view_emp_master.php";
$pagelinks[]= new ArrayObject();
$pagelinks[4]->title="View Instructor Test Stats";
$pagelinks[4]->url="view_instructor_test_stats.php";


$schedulinglinks[]= new ArrayObject();
$schedulinglinks[0]->title="Manage Employee Course Schedule";
$schedulinglinks[0]->url="user_search_view.php";
$schedulinglinks[0]->attributes=array();
$schedulinglinks[]= new ArrayObject();
$schedulinglinks[1]->title="Manage Instructor List";
$schedulinglinks[1]->url="manage_instructor_list.php";
$schedulinglinks[1]->attributes=array();
$schedulinglinks[]= new ArrayObject();
$schedulinglinks[2]->title="Manage Rooms";
$schedulinglinks[2]->url="../../mod/facetoface/room/manage.php";
$schedulinglinks[2]->attributes=array('target'=>'_new');
$schedulinglinks[]= new ArrayObject();
$schedulinglinks[3]->title="Employee Schedule Report";
$schedulinglinks[3]->url="employee_scheduling_report.php";
$schedulinglinks[3]->attributes=array();
$schedulinglinks[]= new ArrayObject();
$schedulinglinks[4]->title="Instructor Schedule Report";
$schedulinglinks[4]->url="instructor_scheduling_report.php";
$schedulinglinks[4]->attributes=array();

echo $OUTPUT->header();

echo html_writer::tag('h1','License/Course Schedule Management Menu');

echo html_writer::tag('h3','License/Certification');
echo html_writer::start_tag('ul', array('class'=>'bullet-list-fix')); //added a class to the ul tags on this page to make it easier to apply styles to them - Mike M.
foreach($pagelinks as $link){
    echo html_writer::start_tag('li');
    echo html_writer::link($link->url, $link->title);
    echo html_writer::end_tag('li');
}
echo html_writer::end_tag('ul');

echo html_writer::tag('h3','Course Schedule');
echo html_writer::start_tag('ul', array('class'=>'bullet-list-fix'));
foreach($schedulinglinks as $link){
    echo html_writer::start_tag('li');
    echo html_writer::link($link->url, $link->title,$link->attributes);
    echo html_writer::end_tag('li');
}
echo html_writer::end_tag('ul');

echo $OUTPUT->footer();