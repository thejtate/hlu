<?php
require_once('../../config.php');
include_once("../license/classes/department.class.php");
include_once("../license/classes/downloadCSV.class.php");
include_once("../license/classes/license.class.php");

include_once("certification.class.php");
include_once("suspension_report_search_form.php");
include_once("certStatus.class.php");
require_once('incident_form.php');
require_once('incident_edit_form.php');
include_once("incident_report_search_form.php");
require_once("incident.class.php");
include_once("user_search_form.php");
include_once("manage_threshold_form.php");
include_once("manage_classifications_form.php");
require_once("classification.class.php");
require_once('apply_to_certif_form.php');

date_default_timezone_set('America/Chicago');

require_login();
$context = context_course::instance($COURSE->id);
 $PAGE->set_context($context);
// Check to see if we are in editing mode and that we can manage pages.
$canmanage = has_capability('block/incident:managepages', $context);

if (!$canmanage) {
    redirect('../../index.php');
}
