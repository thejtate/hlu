<?php
include_once("includes.php");
include_once("manage_instructor_form.php");
$PAGE->set_url('/blocks/license/manage_instructor_list.php', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License/Course Schedule Management Menu','view.php');
$PAGE->navbar->add('Manage Instructors');

echo $OUTPUT->header();

$id = optional_param('id',0, PARAM_INT);
$action=optional_param('action','',PARAM_ALPHA);

//Instantiate simplehtml_form 
$mform = new manage_instructor_form();

switch($action){
    case 'saved':  echo html_writer::tag('div','Instructor saved.',array('class'=>'alert alert-info'));
                            break;
    case 'delete': 
        if($id>0){
            #update the record to deleted
            $record=  new stdClass();
            $record->id=$id;
            $record->deleted=1;
            if($DB->update_record("block_license_instructor",$record)){
                   echo html_writer::tag('div','Instructor deleted.',array('class'=>'alert alert-info'));
            }
            else {
            echo html_writer::tag('div','Error deleting instructor.',array('class'=>'alert alert-error'));    
            }
        }
        break;
    case 'edit':
        $instructor = $DB->get_record('block_license_instructor', array('id' => $id));
        $mform->set_data($instructor);
        break;
}

if ($mform->is_cancelled()) {
     redirect('view.php');
}
elseif ($fromform = $mform->get_data()){
    $fromform->deleted=0;
    $mform->set_data($fromform);
    
    $fromform->firstname=strtoupper($fromform->firstname);
    $fromform->lastname=strtoupper($fromform->lastname);
    
    if($fromform->id==0){
        if (!($DB->insert_record('block_license_instructor', $fromform,true))){
               print_error('inserterror', 'block_license_instructor');
        }     
    }
    else{
        if (!($DB->update_record('block_license_instructor', $fromform,true))){
               print_error('update', 'block_license_instructor');
        }
    }
     redirect("manage_instructor_list.php?action=saved");
}

 $table = new html_table();
 $table->head[] ="Instructor";
 $table->head[]="&nbsp;";
 
 $records=$DB->get_records_select("block_license_instructor","deleted=0",null,"lastname");
 foreach($records as $record){
    $table->data[]=array($record->lastname.', '.$record->firstname,"<a href=\"manage_instructor_list.php?action=edit&id={$record->id}\">Edit</a> | <a onclick=\"javascript:if(confirm('Are you sure you want to deleted this instructor?')){return true;}else{return false;}\" href=\"manage_instructor_list.php?action=delete&id={$record->id}\">Delete</a>");
 } 

$mform->display();
 echo html_writer::table($table);
 echo $OUTPUT->footer();

