<?php

class expireLicense {   
    protected static $_today = 0; 

    public function __construct(){
        self::$_today = time(); 
    }
    private function duplicateRemoval($array, $keep_key_assoc = false){
        $duplicate_keys = array();
        $tmp = array();       
        foreach ($array as $key=>$val) {
            if (is_object($val)){
                $val = (array)$val; // convert object to array
            }
            if (!in_array($val, $tmp)) {
                $tmp[] = $val;
            } else {
                $duplicate_keys[] = $key; 
            }
        }
        foreach ($duplicate_keys as $key) {
            unset($array[$key]);
        }
        return $keep_key_assoc ? $array : array_values($array);
    }
    // Master SQL to find all licenses that are suspended. Will set it to expired and also wipe cert course path progress.
    private function sqlSuspendedLicenses() {
        global $DB;
        $params['today'] = self::$_today;
        $sql = "SELECT cc.id, p.id AS 'progid', cc.userid, ci.certif_completion_id, p.fullname, p.certifid, ci.cert_status_id, ci.suspension_date_ends "
                . "from {certif_completion} cc "
                . "left join {prog} p on p.certifid = cc.certifid " 
                . "left join {block_incident_certif_info} ci on cc.id = ci.certif_completion_id "
                . "where ci.suspension_date_ends > :today and ci.cert_status_id = 3";
                // #GOLIVEUPDATE
                // Change to 'ci.suspension_date_ends > :today' for dev test. 
                // All suspension dates calcuated has to be at least 1 day out. 
        $records = $DB->get_records_sql($sql, $params);
        return $records;
    }
    // SQL to set progress in COURSES (suspended) to 0%
    private function sqlCoursesProgress($userid, $courseids){
        global $DB;
        if(!empty($courseids)){ 
            $wheres = " and c.id in ($courseids) "; 
        } else {
            //return var_dump('Programming error with License Suspension->Expired issue, contact developer.');
        }

        $sql_cp = "SELECT cc.id, p.certifid, u.id AS 'userid', u.firstname, u.lastname, p.id AS 'progid', c.id AS 'courseid', c.fullname, cc.timecompleted, cc.status, u.deleted, u.timemodified "
                . "from {course_completions} cc "
                . "left join {user} u on cc.userid = u.id "
                . "left join {course} c on cc.course = c.id "
                . "left join {prog} p on p.category = c.category "
                . "where userid = $userid $wheres";
        $records_cp = $DB->get_records_sql($sql_cp);
        $records_unique_cp = $this->duplicateRemoval($records_cp); // remove duplicates since object grabbed non-unique courseid.
        return $records_unique_cp;  
    }
    // SQL to set progress in MODULES to 0
    private function sqlModulesProgress($userid, $courseids){
        global $DB;
        if(!empty($courseids)){
            $wheres = " and cm.course in ($courseids) "; 
        } else {
            return var_dump('Programming error with License Suspension->Expired issue, contact developer.');
        }
        $sql_mc = "SELECT cmc.id, p.certifid, cmc.userid, p.id as 'progid', cm.course as 'courseid', cmc.completionstate, cmc.viewed "
                    . "from {course_modules_completion} cmc "
                    . "left join {course_modules} cm on cm.id = cmc.coursemoduleid "
                    . "left join {course} c on cm.course = c.id "
                    . "left join {prog} p on p.category = c.category "
                    . "where userid = $userid $wheres"; 
        $records_mc = $DB->get_records_sql($sql_mc);
        $records_unique_mc = $this->duplicateRemoval($records_mc); // remove duplicates since object grabbed non-unique courseid.
        return $records_unique_mc; 
    }
    // SQL to grab and set certain certificate incidents points to zero 
    private function sqlCertIncidentPoints($userid, $progid){
        global $DB;
        $sql_points = "SELECT bi.id, cc.userid, cc.certifid, ic.certif_completion_id, p.id as 'progid', p.fullname, bi.points, p.category "
            . "from {certif_completion} cc "
            . "left join {block_incident_certif} ic on ic.certif_completion_id = cc.id "
            . "left join {block_incident} bi on bi.id = ic.incident_id "
            . "left join {prog} p on cc.certifid = p.certifid "
            . "where userid = $userid and p.id = $progid and bi.id is not null ";
            $records_points = $DB->get_records_sql($sql_points);
            return $records_points;
    }
    // Find all couresid's connected to that certificate course set. 
    private function findAllCertCoursesetCourseids($certifid){
        global $DB;
        $sql_courseids = "SELECT pcc.id, pcc.courseid, c.fullname coursename "
                    . "from {prog_courseset_course} pcc "
                    . "join {prog_courseset} pc on pc.id = pcc.coursesetid "
                    . "join {prog} p on p.id = pc.programid "
                    . "join {course} c on c.id = pcc.courseid "
                    . "where certifid in ($certifid) "; 
        $records_courseids = $DB->get_records_sql($sql_courseids);

        $tempCourseidsList = array(); 
        foreach($records_courseids as $record_courseids){
            $tempCourseidsList[] = $record_courseids->courseid;
        }
        $couresidsList = implode(', ', $tempCourseidsList);

        return $couresidsList;
    }
    // reset user course progress on these suspended certificates to 0%
    private function resetUserCourseProgress($records_cp){
        global $DB;
        foreach($records_cp as $record_cp){     
            $params_cc2['id'] = $record_cp->id;
            $params_cc2['status'] = 0; /* reset course progress */
            $params_cc2['timecompleted'] = 0;
            $params_cc2['timestarted'] = 0;
            $params_cc2['timeenrolled'] = self::$_today; 
            $sql_cc2 = "UPDATE {course_completions} "
                        . "SET status = :status, timecompleted = :timecompleted, timestarted = :timestarted, timeenrolled = :timeenrolled "
                        . "WHERE id = :id";
            $DB->execute($sql_cc2, $params_cc2);
            //var_dump("Removed $record_cp->fullname certifcate progress for [course completion id: $record_cp->id] $record_cp->fullname course for user $record_cp->firstname $record_cp->lastname \n");
        }
    }
    // Find all and REMOVE QUIZ GRADES for this userid and courseid
    private function removeUserQuizGrades($records_cp, $userid, $courseid){
        global $DB;
        foreach($records_cp as $record_cp){
            $sql_qg = "SELECT DISTINCT qg.id, qg.userid, quiz, q.course, q.name "
                    . "from {quiz_grades} qg "
                    . "left join {quiz} q on q.id = qg.quiz "
                    . "left join {course} c on q.course = c.id "
                    . "where userid = $userid and course in ($courseid) ";  
                    //. "where (userid = $userid and course = $courseid) or (userid = $userid and course in ($this->_sharedCourseIds)) ";  
            $records_qg = $DB->get_records_sql($sql_qg);

            foreach($records_qg as $record_qg){
                $params_cc4['id'] = $record_qg->id;
                $sql_cc4 = "DELETE {quiz_grades} "
                            . "WHERE id = :id ";
                $DB->execute($sql_cc4, $params_cc4);
            }
        }
    }
    // Find all and REMOVE QUIZ ATTEMPTS for this userid and courseid
    private function removeUserQuizAttempts($records_cp, $userid, $courseid){
        global $DB;
        foreach($records_cp as $record_cp){
            $sql_qa = "SELECT DISTINCT qa.id, qa.userid, quiz, q.course, q.name "
                    . "from {quiz_attempts} qa "
                    . "left join {quiz} q on q.id = qa.quiz "
                    . "left join {course} c on q.course = c.id "
                    . "where userid = $userid and course in ($courseid) "; 
                    //. "where (userid = $userid and course = $courseid) or (userid = $userid and course in ($this->_sharedCourseIds)) "; 
            $records_qa = $DB->get_records_sql($sql_qa);

            foreach($records_qa as $record_qa){
                $params_cc5['id'] = $record_qa->id;
                $sql_cc5 = "DELETE {quiz_attempts} "
                        . "WHERE id = :id";
                $DB->execute($sql_cc5, $params_cc5);
            }
        }
    }
    // Find all and REMOVE CHECKLIST submissions for userid and courseid
    private function removeUserChecklists($records_cp, $userid, $courseid){
        global $DB;
        foreach($records_cp as $record_cp){
            $sql_ccheck = "SELECT DISTINCT cc.id, cc.userid, c.course "
                    . "from {checklist_check} cc "
                    . "left join {checklist_item} ci on ci.id = cc.item "
                    . "left join {checklist} c on c.id = ci.checklist "
                    . "where cc.userid = $userid and course in ($courseid) ";
                    //. "where (cc.userid = $userid and course = $courseid) or (cc.userid = $userid and course in ($this->_sharedCourseIds)) ";
            $records_ccheck = $DB->get_records_sql($sql_ccheck);

            foreach($records_ccheck as $record_ccheck){
                $params_cc6['id'] = $record_ccheck->id;
                $sql_cc6 = "DELETE {checklist_check} "
                            . "WHERE id = :id ";
                $DB->execute($sql_cc6, $params_cc6);
            }
        }
    }
    // Find all and REMOVE LESSON grades for userid and courseid
    private function removeUserLessonGrades($records_cp, $userid, $courseid){
        global $DB;
        foreach($records_cp as $record_cp){
            $sql_lg = "SELECT DISTINCT lg.id, lg.userid, l.course, l.name "
                        . "from {lesson_grades} lg "
                        . "left join {lesson} l on l.id = lg.lessonid "
                        . "left join {course} c on c.id = l.course "
                        . "where lg.userid = $userid and course in ($courseid) "; 
                        //. "where (lg.userid = $userid and course = $courseid) or (lg.userid = $userid and course in ($this->_sharedCourseIds)) "; 
            $records_lg = $DB->get_records_sql($sql_lg);
         
            foreach($records_lg as $record_l){
                $params_cc7['id'] = $record_l->id;
                $sql_cc7 = "DELETE {lesson_grades} "
                            . "WHERE id = :id ";
                $DB->execute($sql_cc7, $params_cc7);
            }
        }   
    }
    // Find all and REMOVE LESSON attempts for userid and courseid
    private function removeUserLessonAttempts($records_cp, $userid, $courseid){
        global $DB;
        foreach($records_cp as $record_cp){
            $sql_la = "SELECT DISTINCT la.id, la.userid, l.course, l.name "
                        . "from {lesson_attempts} la "
                        . "left join {lesson} l on l.id = la.lessonid "
                        . "left join {course} c on c.id = l.course "
                        . "where la.userid = $userid and course in ($courseid) "; 
                        //. "where (la.userid = $userid and course = $courseid) or (la.userid = $userid and course in ($this->_sharedCourseIds)) "; 
            $records_la = $DB->get_records_sql($sql_la);

            foreach($records_la as $record_l){
                $params_cc8['id'] = $record_l->id;
                $sql_cc8 = "DELETE {lesson_attempts} "
                            . "WHERE id = :id ";
                $DB->execute($sql_cc8, $params_cc8);
            }
        }   
    }
    // Find all and REMOVE LESSON TIMER rows
    private function removeUserLessonTimer($records_cp, $userid, $courseid){
        global $DB;
        foreach($records_cp as $record_cp){
            $sql_lt = "SELECT lt.id, lt.lessonid, lt.userid, l.course, lt.starttime "
                        . "from {lesson_timer} lt "
                        . "left join {lesson} l on l.id = lt.lessonid "
                        . "left join {course} c on c.id = l.course "
                        . "where lt.userid = $userid and course in ($courseid) "; 
                        //. "where (lt.userid = $userid and course = $courseid) or (lt.userid = $userid and course in ($this->_sharedCourseIds)) "; 
            $records_lt = $DB->get_records_sql($sql_lt);

            foreach($records_lt as $record_lt){
                $params_cc9['id'] = $record_lt->id;
                $sql_cc9 = "DELETE {lesson_timer} "
                            . "WHERE id = :id ";
                $DB->execute($sql_cc9, $params_cc9);
            }
        }
    }
    // Find all and remove LESSON BRANCH rows
    private function removeUserLessonBranchs($records_cp, $userid, $courseid){
        global $DB;
        foreach($records_cp as $record_cp){
            $sql_lb = "SELECT lb.id, lb.lessonid, lb.userid, l.course "
                    . "from {lesson_branch} lb "
                    . "left join {lesson} l on l.id = lb.lessonid  "
                    . "left join {course} c on c.id = l.course "
                    . "where lb.userid = $userid and l.course in ($courseid) ";
                    //. "where lb.userid = $userid and (l.course = $courseid or l.course = 100) ";
            $records_lb = $DB->get_records_sql($sql_lb);

            foreach($records_lb as $record_lb){
                $params_cc10['id'] = $record_lb->id;
                $sql_cc10 = "DELETE {lesson_branch} "
                            . "WHERE id = :id ";
                $DB->execute($sql_cc10, $params_cc10);
            }
        }
    }
    // Find all and reset (was removal) Grades Items
    private function resetUserGradeGrades($records_cp, $userid, $courseid){
        global $DB;
        $today = self::$_today; 

        foreach($records_cp as $record_cp){
            $sql_gg = "SELECT gg.id, gg.itemid, gg.userid, gi.courseid, gi.categoryid, gi.itemname, gi.itemtype "
                        . " from {grade_grades} gg "
                        . " left join {grade_items} gi on gi.id = gg.itemid "
                        . " where userid = $userid and courseid in ($courseid) ";
            $records_gg = $DB->get_records_sql($sql_gg);

            foreach($records_gg as $record_gg){
                $params_cc2['id'] = $record_gg->id;
                $params_cc2['rawgrade'] = NULL;
                $params_cc2['finalgrade'] = NULL;
                $params_cc2['timemodified'] = NULL;
                $params_cc2['timecreated'] = self::$_today;
                $sql_cc2 = "UPDATE {grade_grades} "
                            . " SET rawgrade = :rawgrade, finalgrade = :finalgrade, timecreated = :timecreated, timemodified = :timemodified "
                            . " WHERE id = :id ";
                $DB->execute($sql_cc2, $params_cc2);
            }
        }
    }
    // Find all and remove Grades History 
    private function removeUserGradesHistory($records_cp, $userid, $courseid){
        global $DB; 
        foreach($records_cp as $record_cp){
            $sql_ggh = "SELECT ggh.id, ggh.itemid, ggh.userid, gi.courseid, gi.categoryid, gi.itemname, gi.itemtype "
                        . "from {grade_grades_history} ggh "
                        . "left join {grade_items} gi on gi.id = ggh.itemid "
                        . "where userid = $userid and courseid in ($courseid) ";
            $records_ggh = $DB->get_records_sql($sql_ggh);

            foreach($records_ggh as $record_ggh){
                $params_cc16['id'] = $record_ggh->id;
                $sql_cc16 = "DELETE {grade_grades_history} "
                          . "WHERE id = :id ";
                $DB->execute($sql_cc16, $params_cc16);
            }
        }
    }
    // DELETE all user course completion crit completion
    private function removeUserCourseCompletionCritCompl($records_cp, $userid, $courseid){
        global $DB; 
        foreach($records_cp as $record_cp){
            $sql_ggh = "SELECT ccc.id, ccc.userid, ccc.course "
                        . "from {course_completion_crit_compl} ccc " 
                        . "where ccc.userid = $userid and ccc.course in ($courseid) ";
            $records_ggh = $DB->get_records_sql($sql_ggh);

            foreach($records_ggh as $record_ggh){
                $params_cc16['id'] = $record_ggh->id;
                $sql_cc16 = "DELETE {course_completion_crit_compl} "
                          . "WHERE id = :id ";
                $DB->execute($sql_cc16, $params_cc16);
            }
        }
    }   
    // DELETE all program user assignemnts, forces re-enrollment through LMS.
    private function removeUserProgramAssignments($records_cp, $userid, $progid){
        global $DB;
        foreach($records_cp as $record_cp){
            $sql_ua = "SELECT ua.id, ua.programid, ua.userid, ua.assignmentid "
                    . " FROM {prog_user_assignment} ua "
                    . " WHERE userid = $userid and programid in ($progid)";
            $records_ua = $DB->get_records_sql($sql_ua);

            $progAssignmentids = []; /* collect and remove the connect prog_assignment db entries */
            foreach($records_ua as $record_ua){
                $progAssignmentids[] = $record_ua->assignmentid;
                $params_cc26['id'] = $record_ua->id; 
                $sql_cc26 = "DELETE {prog_user_assignment} "
                            . " WHERE id= :id ";
                $DB->execute($sql_cc26, $params_cc26);
            }
            foreach($progAssignmentids as $progAssignmentid){
                $params_cc27['id'] = $progAssignmentid;
                $sql_cc27 = "DELETE {prog_assignment} "
                            . "WHERE id = :id ";
                $DB->execute($sql_cc27, $params_cc27);
            }
        }
    }

    // DELETE all program completions (cause enrollment issues if not done)
    private function removeUserProgCompletions($records_cp, $userid, $progid){
        global $DB; 
        foreach($records_cp as $record_cp){
            $sql_pc = "SELECT pc.id, pc.programid, pc.userid, pc.coursesetid, pc.status "
                     . "from {prog_completion} pc "
                     . "where userid = $userid and programid in ($progid) ";
            $records_pc = $DB->get_records_sql($sql_pc);

            foreach($records_pc as $record_pc){
                $params_cc23['id'] = $record_pc->id;
                $sql_cc23 = "DELETE {prog_completion} "
                         . " WHERE id = :id";
                $DB->execute($sql_cc23, $params_cc23);
            }
        }
    }
    // RESET user MODULE progress on these suspended certificates to 0%
    private function resetUserModuleProgress($records_mc){
        global $DB;
        foreach($records_mc as $record_mc){     
            $params_cc3['id'] = $record_mc->id;
            $params_cc3['completionstate'] = 0;
            $params_cc3['viewed'] = 0;
            $params_cc3['timecompleted'] = 0;
            $params_cc3['timemodified'] = 0;
            $sql_cc3 = "UPDATE {course_modules_completion} "
                        . "SET completionstate = :completionstate, viewed = :viewed, timecompleted = :timecompleted, timemodified = :timemodified " 
                        . "WHERE id = :id ";
            $DB->execute($sql_cc3, $params_cc3);
        }
    }
    // RESET POINTS to 0 for incident for that specific recently expired license 
    private function resetUserIncidentPoints($records_points){
        global $DB;
        foreach($records_points as $record_points){
            $params_cc11['id'] = $record_points->id;
            $sql_cc11 = "UPDATE {block_incident} "
                        . "SET points = 0 "
                        . "WHERE id = :id ";
            $DB->execute($sql_cc11, $params_cc11); 
        }
    }
    // set the INCIDENT CERTIF INFO (the users earned license) status to Expired and suspension date ends to 0
    private function resetUserIncidentCertifInfo($id){
        global $DB;
        $params_ci['id'] = $id; 
        $sql_ci = "UPDATE {block_incident_certif_info} "
                . "SET cert_status_id = 5, suspension_date_ends = 0 "
                . "WHERE certif_completion_id = :id";
        if($DB->execute($sql_ci, $params_ci)){} else { var_dump('failed resetUserIncidentCertifInfo');}
    }
    // set CERT COMPLETION timewindowopens and timeexpires to today and renewalstatus to 'due'.
    private function resetUserCertCompletion($id){
        global $DB;
        $params_cc['id'] = $id; 
        $params_cc['timewindowopens'] = time();
        $params_cc['timemodified'] = time();
        $params_cc['timeexpires'] = time() + 94670778;
        $params_cc['timecompleted'] = 0; 
        $params_cc['renewalstatus'] = 1; 
        $params_cc['certifpath'] = 2;
        $sql_cc = "UPDATE {certif_completion} "
                . "SET timewindowopens = :timewindowopens, timeexpires = :timeexpires, timecompleted = :timecompleted, timemodified = :timemodified, renewalstatus = :renewalstatus, certifpath = :certifpath "
                . "WHERE id = :id";
        $sql_cch = "UPDATE {certif_completion_history} "
                . "SET timewindowopens = :timewindowopens, timeexpires = :timeexpires, timecompleted = :timecompleted, timemodified = :timemodified, renewalstatus = :renewalstatus, certifpath = :certifpath "
                . "WHERE id = :id";
        if($DB->execute($sql_cc, $params_cc)){} else { var_dump('failed resetUserCertCompletion');}
        if($DB->execute($sql_cch, $params_cc)){} else { var_dump('failed resetUserCertCompletion');}
    }
    // Look for any assigned programs with messed up Due Dates and Start Dates and fix.
    private function fixProgramDueDates(){
        global $DB; 
        $today = self::$_today;
        $sql_pc45 = "SELECT id, programid, userid, timestarted, timedue, timecompleted, timecreated "
                    . " from {prog_completion} "
                    . " where (timecompleted = 0 and (timestarted = 0 or timestarted <= $today)) and (timecreated > ($today-(60*60*5))) ";
        $records_pc = $DB->get_records_sql($sql_pc45);

        foreach($records_pc as $record_pc) {
            $params_pc2['id'] = $record_pc->id;
            $sql_pc2 = "UPDATE {prog_completion} "
                        . " SET timedue = ($today + 94670778), timestarted = $today "
                        . " WHERE id = :id";
            $DB->execute($sql_pc2, $params_pc2);
        }
    }
    public function setSuspendedLicensesToExpired() {
        $params['today'] = self::$_today;

        // Only pull licenses that are curently SUSPENDED and TIME EXPIRED is greater than todays date.
        // Whatever this sql finds is what will be be cleaned out.  
        $records = $this->sqlSuspendedLicenses(); 

        foreach($records as $record) {
            $id = $record->id;
            $userid = $record->userid; 
            $progid = $record->progid;
            $certifid = $record->certifid;
            $allCourseids = $this->findAllCertCoursesetCourseids($certifid);

            // SQL for mass reset of user progress records
            $records_cp = $this->sqlCoursesProgress($userid, $allCourseids);
            $records_mc = $this->sqlModulesProgress($userid, $allCourseids);

            // SQL for incidents to reset points for
            $records_points = $this->sqlCertIncidentPoints($userid, $progid);

            // Mass reset of user progress records
            $this->resetUserCourseProgress($records_cp);

            // Further wipe of users certif progress
            foreach($records_cp as $record_cp){
                $this->removeUserQuizGrades($records_cp, $userid, $allCourseids); 
                $this->removeUserQuizAttempts($records_cp, $userid, $allCourseids);
                $this->removeUserChecklists($records_cp, $userid, $allCourseids);
                $this->removeUserLessonGrades($records_cp, $userid, $allCourseids);
                $this->removeUserLessonAttempts($records_cp, $userid, $allCourseids);
                $this->removeUserLessonTimer($records_cp, $userid, $allCourseids);
                $this->removeUserLessonBranchs($records_cp, $userid, $allCourseids);
                $this->resetUserGradeGrades($records_cp, $userid, $allCourseids);
                $this->removeUserGradesHistory($records_cp, $userid, $allCourseids);
                $this->removeUserCourseCompletionCritCompl($records_cp, $userid, $allCourseids);
                $this->removeUserProgramAssignments($records_cp, $userid, $progid); 
                $this->removeUserProgCompletions($records_cp, $userid, $progid);
            }
            $this->resetUserModuleProgress($records_mc);
            $this->resetUserIncidentPoints($records_points);
            $this->resetUserIncidentCertifInfo($id);
            $this->resetUserCertCompletion($id);
        }

        // Quick fix to assigned programs with messed up Due Dates and Start Dates.
        $this->fixProgramDueDates();
    }
}
?>