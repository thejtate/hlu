<?php
require_once("$CFG->libdir/formslib.php");

class incident_edit_form extends moodleform {

    // protected $incidentId;
    // protected $incidentUserId;
    // protected $incidentDesc;
    // protected $incidentPts;
    // public function __construct($incidentId = null, $incidentUserId = null, $incidentDesc = null, $incidentPts = null){
    //     $this->incidentId = $incidentId;
    //     $this->incidentUserId = $incidentUserId;
    //     $this->incidentDesc = $incidentDesc;
    //     $this->incidentPts = $incidentPts;
    // }

    public function definition() {
        $mform = $this->_form;
        $incident = $this->_customdata;

        $datetimeSettings=array(
            'startyear' => date('Y')-5,
            'stopyear'  => date('Y')+5,
            'timezone'  => 99,
            'step'      =>1
            );
        $mform->addElement('date_time_selector', 'expires_date', "Incident Expires Date", $datetimeSettings);
        $mform->addRule('expires_date', "Incident Expires Date is required", 'required', null, 'server');

        $mform->addElement('text', 'points', "Incindent Points", 'maxlength="2" size="5"');
        $mform->addRule('points', "Points required.", 'required', null, 'server');

        $mform->addElement('textarea', 'description', "Description", 'wrap="virtual" rows="10" cols="80"');
        $mform->addRule('description', "Description is required", 'required', null, 'server');

        $mform->addElement('hidden','incidentid','Incident ID');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true,'Save');
    }

    public function validation($data, $files) {
        $errors = array();
        if($data['submitbutton']=='Save'){
            // if($data['expires_date'] < time()){
            //     $errors['expires_date'] = "Invalid date selected. Must be in the future. ";
            // }
            if(!is_numeric($data['points'])){
                $errors['points'] = "Points must be a number.";
            }
        }
        return $errors;
    }

    public static function getBlockIncidents($incidentid) {
        global $DB;
        $data = $DB->get_records('block_incident', array('id' => $incidentid));
        return $data; 
    }
}
