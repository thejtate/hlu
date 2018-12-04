<?php
include_once("includes.php");

$incidentid = optional_param('incidentid', 0, PARAM_INT);
$data = incident_edit_form::getBlockIncidents($incidentid); 
$incident = array(
    'incidentid' => $data[$incidentid]->id,
    'user_id' => $data[$incidentid]->user_id,
    'incident_datetime' => $data[$incidentid]->incident_datetime,
    'description' => $data[$incidentid]->description,
    'points' => $data[$incidentid]->points,
    'expires_date' => $data[$incidentid]->expires_date,
);
$userid = $incident['user_id'];

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/incident/incident_edit.php');
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu',new moodle_url('view.php'));
$PAGE->navbar->add('Employee Search',new moodle_url('user_search_view.php'));
$PAGE->navbar->add('Employee Details', new moodle_url('employee_detail_view.php', array('userid'=>$userid)));
$PAGE->navbar->add('Edit Incident');
//$action = optional_param('action', '', PARAM_ALPHA);

$mform = new incident_edit_form(null, $incident);
$mform->set_data($incident);

echo $OUTPUT->header();
echo html_writer::tag('h1','Edit Incident');

if ($mform->is_cancelled()) {
    unset($_SESSION['incidentid']);
    redirect('employee_detail_view.php?userid='.$userid);

} elseif ($fromform = $mform->get_data()) {
    $mform->set_data($fromform); 

    $params = array();
    $params['id'] = $fromform->incidentid;
    $params['description'] = $fromform->description;
    $params['points'] = $fromform->points;
    $params['expires_date'] = $fromform->expires_date;
    $sql = "UPDATE {block_incident} SET description = :description, points = :points, expires_date = :expires_date WHERE id = :id"; 
    $DB->execute($sql, $params);
    
    redirect('employee_detail_view.php?userid='.$userid.'&action=Updated');
} else {
    $mform->set_data($incident);
    $mform->display();
}
echo $OUTPUT->footer();