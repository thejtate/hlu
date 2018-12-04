<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class set_course_schedule_form extends moodleform {
    //Add elements to form
    public function definition() {
        
        $mform = $this->_form; // Don't forget the underscore! 
        
        $instructorList=instructor::getList();
        $instructorListFormatted=instructor::formatListArray($instructorList, true);
        $mform->addElement('select', 'instructorid', "Instructor",$instructorListFormatted);
        $mform->addRule('instructorid', "Instructor is required", 'required', null, 'server');
        
        $roomList=room::getList();
        $roomListFormatted=room::formatListArray($roomList, true);
        $mform->addElement('select', 'roomid', "Room",$roomListFormatted);
        $mform->addRule('roomid', "Room is required", 'required', null, 'server');
        
        $datetimeSettings=array(
                      'startyear' => date('Y')-1, 
                      'stopyear'  => date('Y')+3,
                      'timezone'  => 99,
                      'step'      =>1
                      );
        $mform->addElement('date_time_selector', 'startdate', "Course Start Date/Time",$datetimeSettings);
        $mform->addRule('startdate', "Start Date/Time is required", 'required', null, 'server');
        
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'savebutton', 'Save');
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
    
    //Custom validation should be added here
    public function validation($data,$files) {
        $errors=array();
        
        $today=time();
        /*        if($data['startdate'] < $today){
        $errors['startdate']="Start date must be greater than or equal to now.";
        }
         */
        return $errors;
    }
}
