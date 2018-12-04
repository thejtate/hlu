<?php

class employeeCourse
{
    static function getMinimumPassingGradeBoundary($contextid)
    {
        global $DB;
        $params['contextid']=$contextid;
        $sql="select contextid,max(lowerboundary)passboundary "
               . " from {grade_letters} "
               . " where contextid=:contextid"
               . " group by contextid";
        $record=$DB->get_record_sql($sql,$params);
        $minGradeToPassCourse='0';
        if($record){
           $minGradeToPassCourse=$record->passboundary;      
        }  
        return $minGradeToPassCourse;
    }
 
    static function setPassed($grade)
    {
        //$minGradeToPassCourse = employeeCourse::getMinimumPassingGradeBoundary($contextid);

        // Manually set to 75 for quick fix of missing contextid in new Totara 10. 
        if($grade >= 75){
            return 'Y';
        }
        return 'N';
    }
    
    static function getCourseInstructor($courseid)
    {
        global $DB;
        $params['courseid']=$courseid;
        $sql="select b.id as id, a.id,a.lastname,a.firstname,b.contextid "
                ." from {user} a"
                ." join {role_assignments} b on b.userid=a.id "
                ." join {role} c on c.id=b.roleid "
                ." join {context} d on b.contextid=d.id "
                ." right  join {course} e on e.id=d.instanceid "
                ." where archetype='teacher' "
                ." and e.id=:courseid ";
        return $DB->get_record_sql($sql,$params);
    }
    
    static function getEmployeeAddlInfo($userid)
    {
        global $DB;
        $params['userid']=$userid;
        $sql="select uif.shortname fieldname,uid.data "
                . " from {user_info_data} uid "
                . " join {user_info_field} uif on uif.id=uid.fieldid "
                . " where userid=:userid";
        return $DB->get_records_sql($sql,$params);
    }
    
    static function getGradeHistory($userid, $courseid)
    {
        // Added grade check to makes sure the course has been attempted before being shown in reports. 
        global $DB;
        $params['userid'] = $userid;
        $params['courseid'] = $courseid;
        $sql = "SELECT gg.id, gg.finalgrade, gg.timemodified
                    from {grade_grades} gg
                    right join {grade_items} gi on gg.itemid = gi.id
                    where gi.itemtype = 'course'
                    and gg.userid = :userid
                    and gi.courseid = :courseid
                    and gg.finalgrade is not null
                    order by gg.timemodified desc ";
         return $DB->get_records_sql($sql, $params);
    }
    
    static function getCertificationHistory($certifid, $userid)
    {
        $certCourseHistoryList = array();
        $courseList = certification::getCourseList($certifid);

        foreach($courseList as $course){
            $instructor = employeeCourse::getCourseInstructor($course->courseid);
            $gradeHistory = employeeCourse::getGradeHistory($userid, $course->courseid);

            foreach($gradeHistory as $grade){
                $passed = employeeCourse::setPassed($grade->finalgrade);

                if($grade->timemodified != 0 || $grade->timemodified != NULL){
                    $completeDate = date('m/d/Y H:i', $grade->timemodified);
                } else {
                    $completeDate = '';
                }
                
                if($instructor) { 
                    $instructor = $instructor->firstname . ' ' . $instructor->lastname;
                } else { 
                    $instructor = ''; 
                }
                $certCourseHistoryList[] = array('Course' => $course->coursename,
                                                 'Completed' => $completeDate, 
                                                 'Instructor' => $instructor,
                                                 'Grade' => (int)$grade->finalgrade,
                                                 'Pass' => $passed
                                                );
            }
        }
        return $certCourseHistoryList;
    }
   
    static function createHistoryTable($certCourseHistory)
    {
          $table = new html_table();
          $table->head[]="Course";
          $table->head[]="Completed";
          $table->head[]="Instructor";
          $table->head[]="Grade";
          $table->head[]="Pass";
          $table->size=array("40%","20%","20%","10%","10%");
          foreach($certCourseHistory as $history)
          {
             $table->data[] = array($history['Course'],$history['Completed'],$history['Instructor'],$history['Grade'],$history['Pass']);     
          }
          return $table;
    }
}
