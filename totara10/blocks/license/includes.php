<?php
require_once('../../config.php');
require_once('dept_cert_count_form.php');
require_once('instructor_test_stats_form.php');
require_once('classes/license.class.php');
require_once('license_search_form.php');
include_once("classes/department.class.php");
include_once("classes/printLicense.class.php");
include_once("../incident/certStatus.class.php");
include_once("../incident/certification.class.php");
include_once("classes/instructor.class.php");
include_once("classes/room.class.php");
include_once("classes/downloadCSV.class.php");
include_once("classes/courseSchedule.class.php");


date_default_timezone_set('America/Chicago');
require_login();

$context = context_course::instance($COURSE->id);
$PAGE->set_context($context);
// Check to see if we are in editing mode and that we can manage pages.
$canmanage = has_capability('block/license:managepages', $context);
if (!$canmanage) {
    redirect('../../index.php');
}

