<?php
include_once("includes.php");
$PAGE->set_url('/blocks/incident/manage_classifications.php', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu','view.php');
$PAGE->navbar->add('Manage Classifications');

echo $OUTPUT->header();

$id = optional_param('id',0, PARAM_INT);
$action=optional_param('action','',PARAM_ALPHA);

//Instantiate simplehtml_form 
$mform = new manage_classifications_form();

switch($action){
    case 'saved':  echo html_writer::tag('div','Classification saved.',array('class'=>'alert alert-info'));
                            break;
    case 'delete': 
        if($id>0){
            #update the record to deleted
            $record=  new stdClass();
            $record->id=$id;
            $record->deleted=1;
            if($DB->update_record("block_incident_class",$record)){
                   echo html_writer::tag('div','Classification deleted.',array('class'=>'alert alert-info'));
            }
            else {
            echo html_writer::tag('div','Error deleting classification.',array('class'=>'alert alert-error'));    
            }
        }
        break;
    case 'edit':
        $classification = $DB->get_record('block_incident_class', array('id' => $id));
        $mform->set_data($classification);
        break;
}

if ($mform->is_cancelled()) {
     redirect('view.php');
}
elseif ($fromform = $mform->get_data()){
    $fromform->deleted=0;
    $mform->set_data($fromform);
    
    if($fromform->id==0){
        if (!($DB->insert_record('block_incident_class', $fromform,true))){
               print_error('inserterror', 'block_incident_class');
        }     
    }
    else{
        if (!($DB->update_record('block_incident_class', $fromform,true))){
               print_error('update', 'block_incident_class');
        }
    }
     redirect("manage_classifications.php?action=saved");
}

 $table = new html_table();
 $table->head[] ="Classification";
 $table->head[] ="Points";
 $table->head[] ="Days To Expire";
 $table->head[]="&nbsp;";
 
 $records=$DB->get_records_select("block_incident_class","deleted=0",null,"classification");
 foreach($records as $record){
    $table->data[]=array($record->classification,$record->points,$record->expire_days,"<a href=\"manage_classifications.php?action=edit&id={$record->id}\">Edit</a> | <a onclick=\"javascript:if(confirm('Are you sure you want to deleted this classification?')){return true;}else{return false;}\" href=\"manage_classifications.php?action=delete&id={$record->id}\">Delete</a>");
 } 

$mform->display();
 echo html_writer::table($table);
 echo $OUTPUT->footer();