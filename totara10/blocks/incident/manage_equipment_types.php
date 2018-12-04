<?php
include_once("includes.php");

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Incident Management Menu','view.php');
$PAGE->navbar->add('Manage Equipment Types');

echo $OUTPUT->header();

$id = optional_param('id',0, PARAM_INT);
$action=optional_param('action','',PARAM_ALPHA);

//Instantiate simplehtml_form 
$mform = new manage_equipment_types_form();

switch($action){
    case 'saved':  echo html_writer::tag('div','Equipment type saved.',array('class'=>'alert alert-info'));
                            break;
    case 'delete': 
        if($id>0){
            #update the record to deleted
            $record=  new stdClass();
            $record->id=$id;
            $record->deleted=1;
            if($DB->update_record("block_incident_equip_type",$record)){
                   echo html_writer::tag('div','Equipment type deleted.',array('class'=>'alert alert-info'));
            }
            else {
            echo html_writer::tag('div','Error deleting equipment type..',array('class'=>'alert alert-error'));    
            }
        }
        break;
    case 'edit':
        $equipment_type = $DB->get_record('block_incident_equip_type', array('id' => $id));
        $mform->set_data($equipment_type);
        break;
}

if ($mform->is_cancelled()) {
     redirect('view.php');
}
elseif ($fromform = $mform->get_data()){
    $fromform->deleted=0;
    $mform->set_data($fromfrom);
    
    if($fromform->id==0){
        if (!($DB->insert_record('block_incident_equip_type', $fromform,true))){
               print_error('inserterror', 'block_incident_equipment_type');
        }     
    }
    else{
        if (!($DB->update_record('block_incident_equip_type', $fromform,true))){
               print_error('update', 'block_incident_equipment_type');
        }
    }
     redirect("manage_equipment_types.php?action=saved");
}

 $table = new html_table();
 $table->head[] ="Equipment Type";
 $table->head[]="&nbsp;";
 $records=$DB->get_records_select("block_incident_equip_type","deleted=0",null,"equipment_type");
 foreach($records as $record){
    $table->data[]=array($record->equipment_type,"<a href=\"manage_equipment_types.php?action=edit&id={$record->id}\">Edit</a> | <a onclick=\"javascript:if(confirm('Are you sure you want to deleted this equipment type?')){return true;}else{return false;}\" href=\"manage_equipment_types.php?action=delete&id={$record->id}\">Delete</a>");
 } 

$mform->display();
 echo html_writer::table($table);
 echo $OUTPUT->footer();