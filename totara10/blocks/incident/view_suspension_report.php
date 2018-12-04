<?php
include_once("includes.php");
$PAGE->set_url('/blocks/incident/view_suspension_report.php', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu','view.php');
$PAGE->navbar->add('Suspension Report');

$sort = optional_param('sort', 'lastname', PARAM_CLEAN);
$dir = optional_param('dir', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);
$pager = optional_param('pager', '', PARAM_ALPHA);
if ($pager == 'column') {
    $dir = ($dir == 'ASC') ? 'DESC' : 'ASC';
}

// Updated to set Suspended licenses past their suspension date to be set to Expired.
// Removed this since new suspension->expire script will handle it
// $setSuspendedLicensesToExpired = incident::setSuspendedLicensesToExpired();

$mform = new suspension_report_search_form();

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    unset($_SESSION['fromform']);
    unset($_SESSION['dir']);
    redirect('view.php');
}
elseif ($fromform = $mform->get_data() or is_numeric($page)) {
    if ($fromform) {
        $_SESSION['fromform'] = $fromform;
    }
    else if(isset($_SESSION['fromform'])) {
        $fromform = $_SESSION['fromform'];
    }
   $mform->set_data($fromform);
 
    $wheres = ' and cert_status_id=:cert_status_id';
    $params['cert_status_id']=  certStatus::SUSPENDED;
    
    if ($fromform->username) {
        $wheres.=" and username =:username";
        $params['username'] = $fromform->username;
    }

    if ($fromform->lastname) {
        $wheres.=" and lastname like :lastname";
        $params['lastname'] = strtoupper($fromform->lastname.'%');
    }

    if ($fromform->firstname) {
        $wheres.=" and firstname like :firstname";
        $params['firstname'] = strtoupper($fromform->firstname.'%');
    }
    if ($fromform->idnumber) {
        $wheres.=" and b.idnumber=:idnumber";
        $params['idnumber'] = trim($fromform->idnumber);
    }
    if ($fromform->certifid) {
        $wheres.=" and d.certifid=:certifid";
        $params['certifid'] = trim($fromform->certifid);
    }
    if (isset($fromform->fullname) and $fromform->fullname) {
        $wheres.=" and c.fullname=:fullname";
        $params['fullname'] = trim($fromform->fullname);
    }
    if(isset($fromform->suspension_date_from) and $fromform->suspension_date_from){
        $wheres.=" and suspension_date_ends >=:suspension_date_from";
        $params['suspension_date_from'] = $fromform->suspension_date_from;
    }
    if(isset($fromform->suspension_date_to) and $fromform->suspension_date_to){
        $wheres.=" and suspension_date_ends <=:suspension_date_to";
        $params['suspension_date_to'] = $fromform->suspension_date_to;
    }
    if($fromform->orgid){
        $wheres.=" and f.id =:orgid";
        $params['orgid'] = $fromform->orgid;
    }
    
    if ($sort == 'lastname') {
        $sorting = $sort . ' ' . $dir . ',firstname';
    }
    else {
        $sorting = $sort . ' ' . $dir;
    }

    $sql = "select a.id,b.idnumber 'Employee ID',username 'Badge Number',lastname + ', ' + firstname 'Employee Name',f.fullname department,"
            . " '' manager, c.fullname 'Certification',"
            . " max(incident_datetime) 'Last Incident Date', suspension_date_ends 'Suspension End Date',e.managerjaid,a.user_id "
            . " from {block_incident_certif_info} a  "
            . " join {user} b on a.user_id=b.id "
            . " join {block_incident_certif} bic on bic.user_id=a.user_id and bic.certif_completion_id=a.certif_completion_id "
            . " join {block_incident} bi on bi.id=bic.incident_id "
            . " join {certif_completion} d on d.id=a.certif_completion_id "            
            . " join {prog} c on d.certifid=c.certifid "
            . " join {job_assignment} e on e.userid=b.id  "
            . " join {org} f on f.id=e.organisationid "
            . " where b.deleted=0  "
            . " $wheres "
            . " group by a.id,b.idnumber,username,firstname,lastname,f.fullname,c.fullname,suspension_date_ends,a.user_id,e.managerjaid " 
            . " order by $sorting";

    
      if(isset($_POST['downloadbutton']) and $_POST['downloadbutton']){
         $availableusers = $DB->get_records_sql($sql, $params);    
         foreach($availableusers as $key=>$record){
           $availableusers[$key]->manager=license::getManagerName($record->managerjaid);
          }
         downloadCSV::generate($availableusers, 2);       
    }
    
    
    $availableusers = $DB->get_records_sql($sql, $params, $page, $perpage);

    $sql2 = "select count(distinct a.user_id) " 
            . " from {block_incident_certif_info} a  "
           . " join {user} b on a.user_id=b.id "
             . " join {block_incident_certif} bic on bic.user_id=a.user_id and bic.certif_completion_id=a.certif_completion_id "
            . " join {block_incident} bi on bi.id=bic.incident_id "
            . " join {certif_completion} d on d.id=a.certif_completion_id "            
            . " join {prog} c on d.certifid=c.certifid "
            . " join {job_assignment} e on e.userid=b.id  "
            . " join {org} f on f.id=e.organisationid "
            . " where  b.deleted=0  $wheres";
    $recordCount = $DB->count_records_sql($sql2, $params);

    echo $OUTPUT->header();
    echo html_writer::tag("h1", "Incident Management - View Suspension Report");

    $table = new html_table();
    $table->head[] = "<a href=\"view_suspension_report.php?pager=column&page=$page&dir=$dir&sort=idnumber\">Employee ID</a>";
    $table->head[] = "<a href=\"view_suspension_report.php?pager=column&page=$page&dir=$dir&sort=username\">Badge Number</a>";
    $table->head[] = "<a href=\"view_suspension_report.php?pager=column&page=$page&dir=$dir&sort=lastname\">Name</a>";
    $table->head[] = "<a href=\"view_suspension_report.php?pager=column&page=$page&dir=$dir&sort=f.fullname\">Department</a>";
    $table->head[]= "Manager";
    $table->head[] = "<a href=\"view_suspension_report.php?pager=column&page=$page&dir=$dir&sort=c.fullname\">Certification</a>";
    $table->head[] = "<a href=\"view_suspension_report.php?pager=column&page=$page&dir=$dir&sort=incident_datetime\">Last Incident Date</a>";
    $table->head[] = "<a href=\"view_suspension_report.php?pager=column&page=$page&dir=$dir&sort=suspension_date_ends\">Suspension Ends</a>";
    

    foreach ($availableusers as $records) {
        $records=(array)$records;
        $idnumber = $records['employee id'];
        $username = $records['badge number'];
        $fullname=$records['certification'];
        $department=$records['department'];
        $manager=license::getManagerName($records['managerjaid']);
        $name = '<a title="Employee detailed view" href="employee_detail_view.php?userid='.$records['user_id'].'">'.$records['employee name'].'</a>';
        $suspension_date_ends = date('m/d/Y', $records['suspension end date']);
        $last_incident_date=date('m/d/Y',$records['last incident date']);
        $table->data[] = array($idnumber, $username, $name, $department,$manager,$fullname,$last_incident_date,$suspension_date_ends);
    }
    $baseurl = new moodle_url('/blocks/incident/view_incident_report.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
}

//displays the form
$fromform = $_SESSION['fromform'];
$mform->set_data($fromform);
$mform->display();
echo $OUTPUT->paging_bar($recordCount, $page, $perpage, $baseurl);
echo html_writer::table($table);
echo $OUTPUT->footer();