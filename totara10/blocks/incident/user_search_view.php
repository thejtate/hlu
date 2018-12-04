<?php
include_once("includes.php");
include ("../../blocks/license/classes/expireLicense.class.php");

$action= optional_param('action','',PARAM_ALPHA);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/incident/user_search_view.php');
$PAGE->set_heading("User Search Entry");

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu',new moodle_url('view.php'));
$PAGE->navbar->add('Employee Search');

echo $OUTPUT->header();

if($action == 'noincident'){
    echo html_writer::tag('div','No incident selected.',array('class'=>'alert alert-info'));
}

$sort         = optional_param('sort', 'lastname', PARAM_ALPHANUM);
$dir          = optional_param('dir', '', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 25, PARAM_INT);      
$pager=optional_param('pager','',PARAM_ALPHA);

if($pager=='column'){
  $dir=($dir=='ASC')?'DESC':'ASC';
}

// Find all suspended licenses and expire them on expire date. 
// + clear out all Cert, Program, Course, Quiz/Lesson progress.
// + set incident points to zero.
$expireLicense = new expireLicense();
$expireLicense->setSuspendedLicensesToExpired();

//Instantiate simplehtml_form 
$mform = new user_search_form();
    
//Form processing and displaying is done here
 if ($mform->is_cancelled()) {
     unset($_SESSION['fromform']);
     unset($_SESSION['dir']);
     redirect('view.php');
}
 elseif ($fromform = $mform->get_data() or is_numeric($page)) {
     if($fromform){ 
         $_SESSION['fromform']=$fromform;
     }
     else if(isset($_SESSION['fromform'])){
         $fromform=$_SESSION['fromform'];    
     }
     
    $mform->set_data($fromform);
   
    $wheres='';
    if(isset($fromform->username) and $fromform->username){
          $wheres.=" and username=:username";
          $params['username']=$fromform->username;
    }

   if(isset($fromform->lastname) and $fromform->lastname){ 
          $wheres.=" and lastname=:lastname";
          $params['lastname']=  strtoupper($fromform->lastname);
    }
    
    if(isset($fromform->firstname) and $fromform->firstname){
          $wheres.=" and firstname=:firstname";
          $params['firstname']=strtoupper($fromform->firstname);
    }
    if(isset($fromform->idnumber) and $fromform->idnumber){
          $wheres.=" and idnumber=:idnumber";
          $params['idnumber']=trim($fromform->idnumber);
    }
    
    if($sort=='lastname'){
        $sorting=$sort.' '.$dir.',firstname';
    }else{
        $sorting=$sort.' '.$dir;
    }
    
    $params['cert_status_id'] = certStatus::ACTIVE;
    $sql="select distinct a.id,username,idnumber,firstname,lastname "
            . " from {user} a "
            . " left join {block_incident_certif_info} b on a.id=b.user_id "
            . " where deleted=0 and totarasync=1 and (b.cert_status_id = 1 OR b.cert_status_id = 4 OR b.cert_status_id = 5)"
            . " $wheres "
            . " order by $sorting";

    $availableusers = $DB->get_records_sql($sql,$params,$page*$perpage,$perpage);

    $sql2="select count(distinct a.id) "
              ." from {user} a "
             . " left join {block_incident_certif_info} b on a.id=b.user_id "
             . " where deleted=0 and totarasync=1 and b.cert_status_id = :cert_status_id "
             . " $wheres ";
    
    $recordCount=$DB->count_records_sql($sql2,$params);
   
    $table = new html_table();
    $table->head[] ="<a href=\"user_search_view.php?pager=column&page=$page&dir=$dir&sort=idnumber\">Employee ID</a>";
    $table->head[]="<a href=\"user_search_view.php?pager=column&page=$page&dir=$dir&sort=username\">Badge Number</a>";
    $table->head[]="<a href=\"user_search_view.php?pager=column&page=$page&dir=$dir&sort=lastname\">Name</a>";
    $table->head[]="Points";
    $table->head[]='';
    foreach ($availableusers as $records) {
        $idnumber=$records->idnumber;
        $username = $records->username;
        $name = $records->lastname.', '.$records->firstname;
        $id = $records->id;
        $points=incident::userIncidentPoints($records->id);
        $addUrl='';
        $addUrl=' | <a href="employee_detail_view.php?userid='.$id.'">View Detailed</a>';
        $table->data[] = array($idnumber, $username, $name,$points,'<a href="incident_view.php?userid='.$id.'">Add Incident</a>'.$addUrl);
    }
    $baseurl = new moodle_url('/blocks/incident/user_search_view.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
} 

//displays the form
if(isset($_SESSION['fromform'])){
  $fromform=$_SESSION['fromform'];
}
$mform->set_data($fromform);
$mform->display();

echo $OUTPUT->paging_bar($recordCount, $page, $perpage, $baseurl);
echo html_writer::table($table);
echo $OUTPUT->footer();