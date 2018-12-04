<?php
include_once("includes.php");
include_once("operatorDownloadCSV.class.php");
include_once("operatorIncident.class.php");
include_once("classifications.class.php");
include_once("certStatus.class.php");
include_once($CFG->dirroot . '\blocks\incident\incident_user_report_search_form.php');

$PAGE->set_url('/blocks/incident/view_incident_operator_report.php', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu', 'view.php');
$PAGE->navbar->add('Incident Summary by Operator');

$sort = optional_param('sort', 'lastname', PARAM_CLEAN);
$dir = optional_param('dir', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 15, PARAM_INT);
$pager = optional_param('pager', '', PARAM_ALPHA);

if ($pager == 'column') {
    $dir = ($dir == 'ASC') ? 'DESC' : 'ASC';
}

$mform = new incident_user_report_search_form();

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
 
    $wheres =  operatorIncident::setWhereClause($fromform);
    $params = operatorIncident::setParamsArray($fromform);
    $sorting = operatorIncident::setSorting($sort, $dir);
    $sql =  operatorIncident::setUserSQL($wheres);
    $sqlCount = operatorIncident::setUserSQLCount($wheres);
    
    if (isset($_POST['downloadbutton']) and $_POST['downloadbutton']) {
        $userList = $DB->get_records_sql($sql, $params);
    } else { 
        $userList = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
    }

    $userRecordCount = $DB->count_records_sql($sqlCount, $params);       
     
    if (isset($_POST['downloadbutton']) and $_POST['downloadbutton']) {
        $employeeSQL = operatorIncident::setEmployeeSQL($wheres, $sorting);
        $mergedList = array();

        foreach ($userList as $records) {
            $records = (array) $records;
            $params['userid'] = $records['id'];
            $employeeList = $DB->get_records_sql($employeeSQL, $params);
            $mergedList = array_merge($mergedList,$employeeList);
        }   
        
        operatorDownloadCSV::generate($mergedList, 3);
        die;
    }

    echo $OUTPUT->header();
    echo html_writer::tag("h1", "Incident Management - Incident Summary by Operator");

    if(isset($_SESSION['fromform'])){
       $fromform = $_SESSION['fromform'];
    }

    $mform->set_data($fromform);
    $mform->display();
    $baseurl = new moodle_url('/blocks/incident/view_incident_operator_report.php', array('sort' => $sort, 'dir' => $dir));
    echo $OUTPUT->paging_bar($userRecordCount, $page, $perpage, $baseurl);

    foreach ($userList as $records) {
        $records = (array) $records;
        echo html_writer::start_tag("div", array('class'=>'col-sm-12 emp-master-headers','style'=>''));
        echo html_writer::tag("div", $records['lastname'] . ', ' . $records['firstname'], array('class'=>'col-sm-2 emp-master-name','style'=>'padding-left:0px'));
        echo html_writer::tag("div",'<strong>Badge#:</strong> ' . $records['username'], array('class'=>'col-sm-2 emp-master-badge','style'=>'padding-left:0px'));
        echo html_writer::tag("div",'<strong>Employee#:</strong> ' . $records['idnumber'], array('class'=>'col-sm-8 emp-master-dept','style'=>'padding-left:0px'));
        echo html_writer::end_tag("div");

        $employeeSQL = operatorIncident::setEmployeeSQL($wheres, $sorting);
        $params['userid'] = $records['id'];
        $employeeList = $DB->get_records_sql($employeeSQL, $params);
        $table = operatorIncident::getEmployeeTable($employeeList, $dir);
        echo html_writer::table($table);
    }
}
echo $OUTPUT->footer();