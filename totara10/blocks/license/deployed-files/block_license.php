<?php

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
      $record=new stdClass();
      $record->contextid=1;
      $record->modifierid=2;
      $record->itemid=0;
      $record->sortorder=0;
      $record->roleid=$roleid;
      $record->userid=$userid;
      $record->timemodified=time();
      $id=$DB->insert_record('role_assignments', $record, true); 
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
              . " where a.deleted=0 and a.suspended=0 and totarasync=1"
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
    
    public function cron()
    {
      mtrace( "----Start Updating Employee Managers Role----" );
 
      //get staff/employee manager id to insert into role_assignments
      mtrace( "Get staff/employee manager role ids" );
      $roleid=$this->getEmployeeManagerRoleId();
      mtrace("Role ID to be used is $roleid (Employee Manager)");
      
      $results=$this->addUserToEmployeeManagerRoleProcess($roleid);
     
      //done
      mtrace("Inserted {$results['insertCount']} users to Employee Manager role");
      mtrace("{$results['failedCount']} users failed to be added Employee Manager role");
      mtrace( "----End Updating Employee Managers Role----" );
 
      return true;
   }
}
