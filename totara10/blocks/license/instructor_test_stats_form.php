<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class instructor_test_stats_form extends moodleform {
    
    //Add elements to form
    public function definition() {
        $mform = $this->_form; // Don't forget the underscore! 
        $mform->addElement('select', 'userid', "Instructor",$this->getInstructorList()); 
        $mform->addElement('select', 'courseid', "Course",$this->getCourseList()); 
        $this->add_action_buttons(true,'Search');
    }
    
    private function getInstructorList()
    {
        global $DB;
        
        $sql="select distinct userid,lastname+', '+firstname instructor"
                ." from {user} a"
                ." right join {role_assignments} b on b.userid=a.id "
                ." right join {role} c on c.id=b.roleid "
                ." where archetype='teacher' "
                . "and a.id is not null "        
                ." order by instructor";
        $instructors = $DB->get_records_sql($sql);
        $array=array(''=>'--All Instructors--');
        foreach($instructors as $value)
        {
            $array[$value->userid]=ucwords(strtolower($value->instructor));
        }
        return $array;
    }

    private function getCourseList()
    {
        global $DB;
        $courses=$DB->get_records_select("course",null,null,"fullname");
        
        $array=array(''=>'--All Courses--');
        foreach($courses as $value)
        {
            $array[$value->id]=ucwords(strtolower($value->fullname));
        }
        return $array;
    }
}
