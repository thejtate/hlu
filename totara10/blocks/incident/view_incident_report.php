<?php
include_once("includes.php");
include_once($CFG->dirroot.'\blocks\license\classes\downloadCSV.class.php');
include_once("departmentIncident.class.php");
include_once("classifications.class.php");
$PAGE->set_url('/blocks/incident/view_incident_report.php', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu', 'view.php');
$PAGE->navbar->add('Incident Report');

$sort = optional_param('sort', 'lastname', PARAM_CLEAN);
$dir = optional_param('dir', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 8, PARAM_INT);
$pager = optional_param('pager', '', PARAM_ALPHA);

if ($pager == 'column') {
    $dir = ($dir == 'ASC') ? 'DESC' : 'ASC';
}

$mform = new incident_report_search_form();

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    unset($_SESSION['fromform']);
    unset($_SESSION['dir']);
    redirect('view.php');
}

elseif ($fromform = $mform->get_data() or is_numeric($page)) {

    if ($fromform) {
        $_SESSION['fromform'] = $fromform;
    } else if(isset($_SESSION['fromform'])) {
        $fromform = $_SESSION['fromform'];
    }

    $mform->set_data($fromform);
    $wheres = departmentIncident::setWhereClause($fromform);
    $params = departmentIncident::setParamsArray($fromform);
    $sorting = departmentIncident::setSorting($sort, $dir);
    $sql = departmentIncident::setDepartmentSQL($wheres);
    $sqlCount = departmentIncident::setDepartmentSQLCount($wheres);
    
    if (isset($_POST['downloadbutton']) and $_POST['downloadbutton']) {
       $departmentList = $DB->get_records_sql($sql, $params);
    } else {
       $departmentList = $DB->get_records_sql($sql, $params, $page*$perpage, $perpage);
    }
    
    $departmentRecordCount = $DB->count_records_sql($sqlCount, $params);       
     
    if (isset($_POST['downloadbutton']) and $_POST['downloadbutton']) {
        
        $mergedList = array();
        foreach ($departmentList as $records) {
            $records = (array) $records;
            $employeeSQL = departmentIncident::setEmployeeSQL($wheres, $sorting);
            $params['orgid2'] = $records['id'];
            $employeeList = $DB->get_records_sql($employeeSQL, $params);
            $mergedList = array_merge($mergedList, $employeeList);
        }   

        downloadCSV::generate($mergedList, 3);
        die;
    }

    echo $OUTPUT->header();
    echo html_writer::tag("h1", "Incident Management - View Incident Report");

    if(isset($_SESSION['fromform'])){
       $fromform = $_SESSION['fromform'];
    }

    $mform->set_data($fromform);
    $mform->display();
    $baseurl = new moodle_url('/blocks/incident/view_incident_report.php', array('sort' => $sort, 'dir' => $dir));
    echo $OUTPUT->paging_bar($departmentRecordCount, $page, $perpage, $baseurl);

    foreach ($departmentList as $records) {
        $records = (array) $records;
        echo html_writer::tag("h3", $records['department']);
        $employeeSQL = departmentIncident::setEmployeeSQL($wheres, $sorting);
        $params['orgid2'] = $records['id'];
        $employeeList = $DB->get_records_sql($employeeSQL, $params);
        $table = departmentIncident::getEmployeeTable($employeeList, $dir);
        echo html_writer::table($table);
    }   
}
echo $OUTPUT->footer();