<?php
include_once("includes.php");
unset($_SESSION['camefrom']);

#set page header
require_capability('block/incident:viewpages', context_course::instance($COURSE->id));
$PAGE->set_url('/blocks/incident/view.php', array());
$PAGE->set_title('Incident Management Menu');
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('incident', 'block_incident'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu');
$action=optional_param('action','',PARAM_ALPHA);


$pagelinks[]= new ArrayObject();
$pagelinks[0]->title="Search/Add Incident";
$pagelinks[0]->url="user_search_view.php";

$reportlinks[]= new ArrayObject();
$reportlinks[0]->title="View Incident Report";
$reportlinks[0]->url="view_incident_report.php";
$reportlinks[] = new ArrayObject();
$reportlinks[1]->title="View Incident Summary by Operator";
$reportlinks[1]->url="view_incident_operator_report.php";
$reportlinks[]= new ArrayObject();
$reportlinks[2]->title="View Suspension Report";
$reportlinks[2]->url="view_suspension_report.php";

$maintlinks[]= new ArrayObject();
$maintlinks[0]->title="Manage Classifications";
$maintlinks[0]->url="manage_classifications.php";
$maintlinks[]= new ArrayObject();
$maintlinks[1]->title="Manage Point Thresholds";
$maintlinks[1]->url="manage_threshold.php";


echo $OUTPUT->header();
switch($action){
    case 'NotExists': echo html_writer::tag('div','Incident does not exist.',array('class'=>'alert alert-danger'));
                            break;
}

echo html_writer::tag('h1','Incident Management Menu');

echo html_writer::tag('h3','Incidents');
echo html_writer::start_tag('ul', array('class'=>'bullet-list-fix')); //added a class to the ul tags on this page to make it easier to apply styles to them - Mike M.
foreach($pagelinks as $link){
    echo html_writer::start_tag('li');
    echo html_writer::link($link->url, $link->title);
    echo html_writer::end_tag('li');
}
echo html_writer::end_tag('ul');

echo html_writer::tag('h3','Maintenance Pages');
echo html_writer::start_tag('ul', array('class'=>'bullet-list-fix'));
foreach($maintlinks as $link){
    echo html_writer::start_tag('li');
    echo html_writer::link($link->url, $link->title);
    echo html_writer::end_tag('li');
}
echo html_writer::end_tag('ul');

echo html_writer::tag('h3','Reports');
echo html_writer::start_tag('ul', array('class'=>'bullet-list-fix'));
foreach($reportlinks as $link){
    echo html_writer::start_tag('li');
    echo html_writer::link($link->url, $link->title);
    echo html_writer::end_tag('li');
}
echo html_writer::end_tag('ul');

echo $OUTPUT->footer();