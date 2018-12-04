<?php
include_once("classes/expireLicense.class.php");

class block_license extends block_base
{
    public function init()
    {
        $this->title = get_string('license', 'block_license');
    }
    
    private function getEmployeeManagerRoleId()
    {
        global $DB;
        $sql="select max(id) roleid from {role} where shortname='employeemanager' ";
        $record = $DB->get_record_sql($sql);
        return trim($record->roleid);
    }
    
    private function managerExists($userid,$roleid)
    {
        global $DB;
        $params['userid']=$userid;
        $params['roleid']=$roleid;
        $sql="select count(1) found "
                 . " from {role_assignments} "
                 . " where userid=:userid "
                 . " and roleid=:roleid ";                    
        $record=$DB->get_record_sql($sql,$params);
        return trim($record->found);
    }
    
    private function addUserToEmployeeManagerRole($roleid,$userid)
    {
      global $DB;
      
      #defaults for insert
      $record = new stdClass();
      $record->contextid = 1;
      $record->modifierid = 2;
      $record->itemid = 0;
      $record->sortorder = 0;
      $record->roleid = $roleid;
      $record->userid = $userid;
      $record->timemodified = time();
      $id = $DB->insert_record('role_assignments', $record, true); 
      return $id;
    }
    
    private function getManagerList($roleid)
    {
        global $DB;
        //get managers list and oop through and add the employee manager role to them all if not already in table
        mtrace( "Get manager recordset ids that do not already exist as staff/employee manager role" );
        $params['roleid']=$roleid;
        $sql="select distinct a.id userid from {user} a "
              . " join {job_assignment} b on a.id=b.managerjaid"
              . " where a.deleted=0 and a.suspended=0 and a.totarasync=1"
              . " and not exists (select * from {role_assignments} c"
              . "                          where c.userid=b.managerjaid"
              . "                          and roleid=:roleid)";
        return $DB->get_recordset_sql($sql,$params);       
    }
    
    private function addUserToEmployeeManagerRoleProcess($roleid)
    {
        $users = $this->getManagerList($roleid);
        $insertCount=0;
        $failedCount=0;
       
        mtrace("Start Loop through manager record ids");
        foreach ($users as $user) {
             //check for user already in role_assignments with the staffmanager role
             if(!$this->managerExists($user->userid, $roleid)){
                  //if not there, insert record into role_assignments
                  if($this->addUserToEmployeeManagerRole($roleid, $user->userid)){
                      $insertCount++;
                  }else{
                      $failedCount++;
                  }
             }
       }
       mtrace("End Loop through manager record ids");
       $users->close(); // Don't forget to close the recordset! 
    
       return array('insertCount'=>$insertCount,'failedCount'=>$failedCount);
    }



    // Checks for users that have been deleted for 90-days and removes (soft delete) all certificates they have. 
    // ie: /totara/reportbuilder/report.php 
    private function removeDeletedUserCerts(){
        global $DB;
        $failedCounter = 0; 
        $nowDate = date('Y-m-d H:i:s'); 
        $results = $DB->get_recordset_sql("SELECT c.id, c.certifid, p.fullname, c.unassigned, c.userid, u.firstname, u.lastname, u.timemodified, u.deleted
                                        FROM {certif_completion_history} c
                                        JOIN {user} u ON u.id = c.userid
                                        JOIN {prog} p ON p.certifid = c.certifid
                                        WHERE u.deleted = 1 AND c.unassigned = 1 
                                        ORDER BY u.id");
        foreach ($results as $result) {
            $nintydayExpire = $result->timemodified + 7889299; 
            $minuteExpire = $result->timemodified + 7889299; 

            if((date('Y-m-d H:i:s', $minuteExpire) < $nowDate) || ($result->timemodified < 500000000)){
                mtrace("\n------");
                mtrace("[id:$result->id]: Found (deleted:" . date('Y:m:d H:i:s', $result->timemodified) .") result [id:$result->id] $result->firstname $result->lastname with Cert [id:$result->certifid] $result->fullname.\n");               
                mtrace("------\n");

                $cert = new stdClass(); 
                $cert->id = $result->id;
                $cert->unassigned = 0;
                if($DB->update_record('certif_completion_history', $cert)){
                    mtrace("[id: $result->certifid] Cert $result->fullname removed.\n");
                } else {
                    mtrace("Error removing certificate for $result->firstname $result->$lastname.\n");
                    $failedCounter++;
                }
            }
        }
        mtrace("\n Total number of records failed to remove: $failedCounter \n ------ \n");
        $results->close();
    }
    
    // finds all deleted users and clears status for all Programs
    private function clearUserProgCompletion(){
        global $DB;
        $nowDate = date('Y-m-d H:i:s'); 
        $results = $DB->get_recordset_sql("SELECT pc.id, pc.programid, p.fullname, pc.status, pc.userid, u.firstname, u.lastname, u.timemodified, u.deleted, u.timemodified
                                            from {prog_completion} pc
                                            left join {user} u on pc.userid = u.id
                                            left join {prog} p on pc.programid = p.id
                                            where u.deleted = 1"); 

        foreach($results as $result){
            $nintydayExpire = $result->timemodified + 7889299; 
            $minuteExpire = $result->timemodified + 7889299; 

            if((date('Y-m-d H:i:s', $minuteExpire) < $nowDate) || ($result->timemodified < 500000000)){
                $prog = new stdClass();
                $prog->id = $result->id;
                $prog->status = 0; //set Program Status 0% Progress

                if($DB->update_record('prog_completion', $prog)){
                    mtrace("-- UPDATED program completion status [$result->programid] $result->fullname for [$result->userid] $result->firstname $result->lastname --\n");
                } else {
                    mtrace("-- FAILED to update program completion status [$result->programid] $result->fullname for [$result->userid] $result->firstname $result->lastname --\n");
                }
            }
        }
        $results->close();
    }

    // finds all deleted users and clears status for all Courses
    private function clearUserCourseCompletion(){
        global $DB;
        $nowDate = date('Y-m-d H:i:s'); 
        $results = $DB->get_recordset_sql("SELECT cc.id, u.id as userid, u.firstname, u.lastname, cc.course, c.fullname, cc.status, cc.timecompleted, u.timemodified, u.deleted, u.timemodified
                                            from {course_completions} cc
                                            left join {user} u on cc.userid = u.id
                                            left join {course} c on cc.course = c.id
                                            where u.deleted = 1"); 
        foreach($results as $result){
            $nintydayExpire = $result->timemodified + 7889299; 
            $minuteExpire = $result->timemodified + 7889299; 

            if((date('Y-m-d H:i:s', $minuteExpire) < $nowDate) || ($result->timemodified < 500000000)){
                $course = new stdClass();
                $course->id = $result->id;
                $course->timecompleted = NULL; 
                $course->status = 0; //set Course status to 0% progress

                if($DB->update_record('course_completions', $course)){
                    mtrace("-- UPDATED course completion status [$result->course] $result->fullname for [$result->userid] $result->firstname $result->lastname -- \n");
                } else {
                    mtrace("-- FAILED to update course completion status [$result->course] $result->fullname for [$result->userid] $result->firstname $result->lastname -- \n");
                }
            }
        }
        $results->close();
    }

    // finds all deleted users and clears status for all Modules/Lessons
    private function clearUserModuleCompletion(){
        global $DB; 
        $nowDate = date('Y-m-d H:i:s'); 
        $results = $DB->get_recordset_sql("SELECT cmc.id, cmc.coursemoduleid, u.id as userid, u.firstname, u.lastname, u.deleted, cmc.completionstate, cmc.viewed, cmc.timecompleted, u.timemodified
                                            from {course_modules_completion} cmc
                                            left join {user} u on cmc.userid = u.id
                                            where u.deleted = 1"); 
        foreach($results as $result){
            $nintydayExpire = $result->timemodified + 7889299; 
            $minuteExpire = $result->timemodified + 7889299; 

            if(date('Y-m-d H:i:s', $minuteExpire) < $nowDate){
                $module = new stdClass();
                $module->id = $result->id;
                $module->completionstate = 0;
                $module->viewed = 0;
                // Might need to zero out qattra_grades_grades also

                if($DB->update_record('course_modules_completion', $module)){
                    mtrace("-- UPDATED module completion status [$result->id] for [$result->userid] $result->firstname $result->lastname -- \n");
                } else {
                    mtrace("-- FAILED to update module completion status [$result->id] for [$result->userid] $result->firstname $result->lastname -- \n");
                }
            }
        }
        $results->close();
    }

    // finds all deleted users and clears quiz grades and attempts
    private function clearUserQuizs(){
        global $DB; 
        $nowDate = date('Y-m-d H:i:s'); 

        // Clear all quiz grades
        $results = $DB->get_recordset_sql("SELECT DISTINCT qg.id, qg.userid, quiz, q.course, q.name, u.deleted 
                                            from {quiz_grades} qg 
                                            left join {quiz} q on q.id = qg.quiz 
                                            left join {course} c on q.course = c.id 
                                            left join {user} u on u.id = qg.userid 
                                            where deleted = 1"); 
        foreach($results as $result){
            $params['id'] = $result->id;
            $sql = "DELETE {quiz_grades} "
                        . "WHERE id = :id ";
            $DB->execute($sql, $params);
        }

        // Clear all quiz attempts
        $results2 = $DB->get_recordset_sql("SELECT DISTINCT qa.id, qa.userid, quiz, q.course, q.name, u.deleted
                                            from {quiz_attempts} qa
                                            left join {quiz} q on q.id = qa.quiz
                                            left join {course} c on q.course = c.id 
                                            left join {user} u on u.id = qa.userid
                                            where deleted = 1"); 
        foreach($results2 as $result2){
            $params2['id'] = $result2->id;
            $sql2 = "DELETE {quiz_attempts} "
                        . "WHERE id = :id ";
            $DB->execute($sql2, $params2);
        }
        $results->close();
    }

    // finds all deleted users and clears checklists
    private function clearUserChecklists(){
        global $DB; 
        $nowDate = date('Y-m-d H:i:s'); 

        // Clear all checklists
        $results = $DB->get_recordset_sql("SELECT DISTINCT cc.id, cc.userid, c.course, u.deleted 
                                            from {checklist_check} cc
                                            left join {checklist_item} ci on ci.id = cc.item
                                            left join {checklist} c on c.id = ci.checklist
                                            left join {user} u on u.id = cc.userid
        where deleted = 1"); 
        foreach($results as $result){
            $params['id'] = $result->id;
            $sql = "DELETE {checklist_check} "
                        . "WHERE id = :id ";
            $DB->execute($sql, $params);
        }

        $results->close();
    }

    // finds all deleted users and clears lesson grades and attempts
    private function clearUserLessons(){
        global $DB; 
        $nowDate = date('Y-m-d H:i:s'); 

        // Clear all lesson grades
        $results = $DB->get_recordset_sql("SELECT DISTINCT lg.id, lg.userid, l.course, l.name, u.deleted
                                            from {lesson_grades} lg
                                            left join {lesson} l on l.id = lg.lessonid
                                            left join {course} c on c.id = l.course
                                            left join {user} u on u.id = lg.userid
                                            where deleted = 1"); 
        foreach($results as $result){
            $params['id'] = $result->id;
            $sql = "DELETE {lesson_grades} "
                        . "WHERE id = :id ";
            $DB->execute($sql, $params);
        }

        // Clear all lesson attempts
        $results2 = $DB->get_recordset_sql("SELECT DISTINCT la.id, la.userid, l.course, l.name, u.deleted
                                            from {lesson_attempts} la
                                            left join {lesson} l on l.id = la.lessonid
                                            left join {course} c on c.id = l.course
                                            left join {user} u on u.id = la.userid
                                            where deleted = 1"); 
        foreach($results2 as $result2){
            $params2['id'] = $result2->id;
            $sql2 = "DELETE {lesson_attempts} "
                        . "WHERE id = :id ";
            $DB->execute($sql2, $params2);
        }
        $results->close();
    }

    // Sets Licenses to Expired after Suspension period is up.
    // private function setLicenseToExpired(){
    //     global $DB;
    //     $todayDate = time();
    
    //     $results = $DB->get_recordset_sql("SELECT a.id,
    //                                         c.fullname 'Certification', cert_status 'Status',
    //                                         ISNULL(x.issuedate,d.timecompleted) 'Date Issued',
    //                                         d.timewindowopens 'Recert Date', 
    //                                         d.timeexpires 'Expr Date', 
    //                                         a.cert_status_id, 
    //                                         a.suspension_date_ends 
    //                                         from {block_incident_certif_info} a
    //                                         join {certif_completion} d on d.id=a.certif_completion_id 
    //                                         left outer join (select min(timecompleted) issuedate, userid, certifid from {certif_completion_history} group by userid,certifid) x on d.userid=x.userid and d.certifid=x.certifid 
    //                                         join {user} b on a.user_id=b.id 
    //                                         join {job_assignment} f on f.userid = b.id 
    //                                         join {org} g on f.organisationid=g.id                 
    //                                         join {prog} c on d.certifid=c.certifid 
    //                                         join {block_incident_cert_status} e on e.id=a.cert_status_id 
    //                                         where suspension_date_ends != 0
    //                                         and suspension_date_ends < $todayDate
    //                                         and cert_status_id = 3");
    
    //     foreach ($results as $result) {
    //         var_dump($result);
    //         $license = new stdClass();
    //         $license->id = $result->id;
    //         $license->cert_status_id = 5; /* sets to expired */
    
    //         //mtrace("------ Attempting to update Cert completion id [$result->id] to expired. ------\n");
    //         if($DB->update_record('block_incident_certif_info', $license)){
    //             mtrace("------ Cert completion id [$result->id] set to EXPIRED. ------\n");
    //         } else {
    //             mtrace("------ Error with setting Cert completion id [$result->id] to EXPIRED. ------\n");
    //         }
    //     }
    //     $results->close();
    // }

    // if any Certificate has cert_status_id set to NULL, change to at least INACTIVE
    private function setNullCertificateToInActive(){
        global $DB;
        $results = $DB->get_recordset_sql("SELECT * FROM {block_incident_certif_info}
                                            where certif_completion_id is null ");
        foreach ($results as $result) {
            $cert = new stdClass();
            $cert->id = $result->id;
            $cert->cert_status_id = 2; /* set to INACTIVE */

            if($DB->update_record('block_incident_certif_info', $cert)){
                mtrace("Cert completion id [$result->id] status had NULL. Fixing to INACTIVE\n");
            } 
        } 
        $results->close();
    }


    public function cron() {
        // find and remove certificates on deleted users (after 90 days)
        mtrace("----Start Updating Certificates on Deleted Users----\n");
        $deleteCerts = $this->removeDeletedUserCerts();
        mtrace("----End Updating Certificates on Deleted Users----\n");

        // find and remove course progress for deleted users. (iafter 90 days)
        mtrace("\n\n----Start Updating Course Progress on Deleted Users----\n");
        $clearUserProgCompletion = $this->clearUserProgCompletion();
        $clearUserModuleCompletion = $this->clearUserModuleCompletion();
        $clearUserCourseCompletion = $this->clearUserCourseCompletion();
        $clearUserQuizs = $this->clearUserQuizs();
        $clearUserChecklists = $this->clearUserChecklists();
        $clearUserLessons = $this->clearUserLessons();
        mtrace("\n\n----End Updating Course Progress on Deleted Users----\n");
 
        // if any Certificate has cert_status_id set to NULL, change to at least INACTIVE
        mtrace("----Start fixing certificates with status of NULL----\n");
        $setNullCertificateToInActive = $this->setNullCertificateToInActive();
        mtrace("----End fixing certificates with status of NULL----\n");
        mtrace("--- Start updated to set Suspended licenses past their suspension date to be set to Expired ---");
        mtrace("--- End expiring licenses ---");

        mtrace( "----Start Updating Employee Managers Role----\n" );
        //get staff/employee manager id to insert into role_assignments
        mtrace( "Get staff/employee manager role ids" );
        $roleid = $this->getEmployeeManagerRoleId();
        mtrace("Role ID to be used is $roleid (Employee Manager)");

        $results = $this->addUserToEmployeeManagerRoleProcess($roleid);
        
        //done
        mtrace("Inserted {$results['insertCount']} users to Employee Manager role");
        mtrace("{$results['failedCount']} users failed to be added Employee Manager role");
        mtrace( "----End Updating Employee Managers Role----" );
        return true;
    }
}
