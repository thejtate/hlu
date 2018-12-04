<?php
include_once("includes.php");
include_once("user_search_form.php");
unset($_SESSION['schedule']);

$action = optional_param('action','',PARAM_ALPHA);

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/license/user_search_view.php');
$PAGE->set_heading("User Search Entry");

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License/Course Schedule Management Menu',new moodle_url('view.php'));
$PAGE->navbar->add('Employee Search');

echo $OUTPUT->header();

if($action=='nouser'){
    echo html_writer::tag('div','Please select a user to continue.',array('class'=>'alert alert-danger'));
}

$sort = optional_param('sort', 'lastname', PARAM_ALPHANUM);
$dir = optional_param('dir', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);      
$pager=optional_param('pager','',PARAM_ALPHA);

if($pager=='column'){
  $dir=($dir=='ASC')?'DESC':'ASC';
}

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
    $params=array();
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
    }
    else{
        $sorting=$sort.' '.$dir;
    }
    
    $sql="select distinct a.id,username,idnumber,firstname,lastname "
            . " from {user} a "
            //. " where deleted=0 and totarasync=1  " -- old
            . " where deleted=0  "
            . " $wheres "
            . " order by $sorting";

    $availableusers = $DB->get_records_sql($sql,$params,$page*$perpage,$perpage);

    $sql2="select count(distinct a.id) "
              ." from {user} a "
             //. " where deleted=0 and totarasync=1 "
             . " where deleted=0  "
             . " $wheres ";
    
    $recordCount=$DB->count_records_sql($sql2,$params);
   
    $table = new html_table();
    $table->head[]="";
    $table->head[]="<a href=\"user_search_view.php?pager=column&page=$page&dir=$dir&sort=lastname\">Name</a>";
    $table->head[]="<a href=\"user_search_view.php?pager=column&page=$page&dir=$dir&sort=idnumber\">Employee ID</a>";
    $table->head[]="<a href=\"user_search_view.php?pager=column&page=$page&dir=$dir&sort=username\">Badge Number</a>";   
 
    foreach ($availableusers as $records) {
        $idnumber=$records->idnumber;
        $username = $records->username;
        $name = $records->lastname.', '.$records->firstname;
        $id = $records->id;
        $table->data[] = array('<input type="radio" name="userid" id="userid'.$id.'" value="'.$id.'">','<label for="userid'.$id.'">'.$name.'</label>',$idnumber, $username);
    }
    $baseurl = new moodle_url('/blocks/license/user_search_view.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
} 

//displays the form
if(isset($_SESSION['fromform'])){
  $fromform=$_SESSION['fromform'];
}
$mform->set_data($fromform);
$mform->display();

echo $OUTPUT->paging_bar($recordCount, $page, $perpage, $baseurl);
echo html_writer::start_tag("form",array('method'=>'post','action'=>"choose_certification.php"));
echo html_writer::table($table);
echo html_writer::start_tag("button",array('name'=>'button','value'=>"Continue"));
echo "Select and Continue";
echo html_writer::end_tag('form');
echo $OUTPUT->footer();