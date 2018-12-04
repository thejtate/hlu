<?php

class license
{

    static function setDirection($pager,$dir)
    {
        if ($pager == 'column') {
            $dir = ($dir == 'ASC') ? 'DESC' : 'ASC';
        }
        return $dir;
    }
    
    static function setSort($sort,$dir)
    {
        $sorting='';
        if ($sort == 'lastname') {
           $sorting = $sort . ' ' . $dir . ',firstname';
        }
        else {
           $sorting = $sort . ' ' . $dir;
        }
        return $sorting;
    }
    
    static function setWhereClause($fromform)
    {
        $wheres = '';
        if (isset($fromform->cert_status_id) and $fromform->cert_status_id) {
            $wheres.=" and cert_status_id =:cert_status_id";
        }
        else {
            #only show inactive or active ones
            $wheres.=" and cert_status_id in (:active,:inactive) ";
        }

        if (isset($fromform->username) and $fromform->username) {
            $wheres.=" and username =:username";
        }

        if (isset($fromform->lastname) and $fromform->lastname) {
            $wheres.=" and lastname like :lastname";
        }

        if (isset($fromform->firstname) and $fromform->firstname) {
            $wheres.=" and firstname like :firstname";
        }
        if (isset($fromform->certifid) and $fromform->certifid) {
            $wheres.=" and d.certifid=:certifid";
        }
        if(isset($fromform->orgid) and $fromform->orgid){
            $wheres.=" and g.id=:orgid ";
        }
        
        if(isset($fromform->recert_in_days) and $fromform->recert_in_days>0){
            $wheres.=" and d.timewindowopens between :today and :todayplus ";
        }
        
        return $wheres;
    }

    static function setParamsArray($fromform)
    {
        $params = array();
        if (isset($fromform->cert_status_id) and $fromform->cert_status_id) {
            $params['cert_status_id'] = $fromform->cert_status_id;
        }
        else {
            #only show inactive or active ones
            $params['active'] = certStatus::ACTIVE;
            $params['inactive'] = certStatus::INACTIVE;
        }

        if (isset($fromform->username) and $fromform->username) {
            $params['username'] = $fromform->username;
        }

        if (isset($fromform->lastname) and $fromform->lastname) {
            $params['lastname'] = strtoupper($fromform->lastname . '%');
        }

        if (isset($fromform->firstname) and $fromform->firstname) {
            $params['firstname'] = strtoupper($fromform->firstname . '%');
        }
        if (isset($fromform->certifid) and $fromform->certifid) {
            $params['certifid'] = trim($fromform->certifid);
        }
        if(isset($fromform->orgid) and $fromform->orgid){
            $params['orgid']=$fromform->orgid;
        }
       if(isset($fromform->recert_in_days) and $fromform->recert_in_days>0){
           $params['today']=time();
           $params['todayplus']=time()+(60*60*24*$fromform->recert_in_days);           
       }
       
        return $params;
    }
    
    static function getManagerName($managerid)
    {
        global $DB;
        $manager = $DB->get_record('user', array('id'=>$managerid));
        return $manager->lastname.', '.$manager->firstname;
    }
    
    static function setPrintCheckbox($cert_status,$userid, $employee_name)
    {
        
         $print="";
         if($cert_status=='Active'){
             // Original code   
             // $print='<input type="checkbox" class="case" id="userid'.$userid.'" name="userid['.$userid.']" value="1"> ';
             $print='<input type="checkbox" class="case" id="userid'.$userid.'" data-ckr ="'.$userid.'" name="userid['.$userid;
             $print .= ']" value="1" onclick="AddToSession(\''.$userid.'\',\''.$employee_name.'\');" ';
             if(key_exists($userid, $_SESSION['print_users'])){
                 $print .= 'checked ';
             }
             $print .= '/> ';
         }
         return $print;
    }
    
    static function getSearchResultsTable($page,$dir,$searchResultList,$camefrom='')
    {
        $table = new html_table();
        $table->head[]='<input type="checkbox" id="selectall"><label for="selectall">Print</label>';
        $table->head[] = "<a href=\"view_license.php?pager=column&page=$page&dir=$dir&sort=lastname\">Employee</a>";
        $table->head[] = "<a href=\"view_license.php?pager=column&page=$page&dir=$dir&sort=username\">Badge Number</a>";
        $table->head[] = "<a href=\"view_license.php?pager=column&page=$page&dir=$dir&sort=b.idnumber\">Employee ID</a>";
        
        $table->head[]="Manager";
        $table->head[] = "<a href=\"view_license.php?pager=column&page=$page&dir=$dir&sort=g.fullname\">Department</a>";
        $table->head[] = "<a href=\"view_license.php?pager=column&page=$page&dir=$dir&sort=c.fullname\">Certification</a>";
        $table->head[] = "<a href=\"view_license.php?pager=column&page=$page&dir=$dir&sort=issuedate\">Date Issued</a>";
        
        $table->head[] = "<a href=\"view_license.php?pager=column&page=$page&dir=$dir&sort=timewindowopens\">Recert Date</a>";
        
        $table->head[] = "<a href=\"view_license.php?pager=column&page=$page&dir=$dir&sort=timeexpires\">Expr Date</a>";
        $table->head[] = "<a href=\"view_license.php?pager=column&page=$page&dir=$dir&sort=cert_status\">Status</a>";
        
        // Added 2/24/16 - Chad
        $same_name = "";

        foreach ($searchResultList as $records) {
           $records=(array)$records;
           $username ='<label for="userid'.$records['user_id'].'">'.$records['badge number'].'</label>';
           $fullname=$records['certification'];
           $name = '<a title="Employee detailed view" href="../incident/employee_detail_view.php?camefrom='.$camefrom.'&userid='.$records['user_id'].'">'.$records['employee name'].'</a>';
           $print=license::setPrintCheckbox($records['status'], $records['user_id'], $records['employee name']);
           $department=$records['department'];
           $managerName=license::getManagerName($records['managerjaid']);
           $new_cert_status=($records['status']=='Active')?certStatus::INACTIVE:certStatus::ACTIVE;
           if($records['status']=='Suspended'){
               $cert_status=$records['status'];
           }
           else{
               $cert_status='<a title="Toggle Status" href="view_license.php?action=toggle&new_cert_status='.$new_cert_status.'&id='.$records['block_certif_info_id'].'">'.$records['status'].'</a>';
           }
           $cert_exp_date=date('m/d/Y',$records['expr date']);
           $recert_date=date('m/d/Y',$records['recert date']);
           $date_issued=date('m/d/Y',$records['date issued']);
           $employee_id=$records['employee id'];

           // Added conditional statement 2/24/16 - Chad
           if($records['employee name'] != $same_name){
               // original statement
               $table->data[] = array($print,$name,$username, $employee_id,$managerName,$department,$fullname,$date_issued,$recert_date,$cert_exp_date,$cert_status);        
           // Added next 4 lines 2/24/16 - Chad
           }else{
               $table->data[] = array("","","", "","","",$fullname,$date_issued,$recert_date,$cert_exp_date,$cert_status);        
           }
           $same_name = $records['employee name'];
       }
       
       return $table;
    }

    static function getInstructorName($certifid)
    {
        global $DB;
        
        $params['certifid']=$certifid;

        $sql="select u.firstname,u.lastname
                    from {certif_completion} cc
                    join {prog} p on p.certifid=cc.certifid
                    join {prog_courseset} pc on pc.programid=p.id
                    join {prog_courseset_course} pcc on pc.id=pcc.coursesetid
                    join {context} c on c.instanceid=pcc.courseid
                    join {role_assignments} ra on ra.contextid=c.id
                    join {role} r on r.id=ra.roleid
                    join {user} u on u.id=ra.userid
                    where r.archetype='teacher'
                    and cc.certifid=:certifid
                    and cc.status=3 and cc.renewalstatus=0";
      
          $records=$DB->get_record_sql($sql,$params);
          
          return $records->lastname.', '.$records->firstname;
    }
    
    /**
     * Returns list of active licenses for printed license sheet
     * Should get all cert name, date completed, expire date and instructor
     * NOTE: Will only use last instructor in list for instructor name on printout
     * 
     * @global obj $DB
     * @param int $userid
     * @return array
     */
    static function getAllActiveLicensesForUser($userid)
    {
        global $DB;
        
        $params['id']=$userid;
        /*
         * $sql="SELECT convert(varchar(10),a.userid)+convert(varchar(10),a.certifid) rid, a.certifid,d.shortname,ISNULL(min(b.timecompleted),a.timecompleted) firstcompleted,a.timecompleted lastcompleted
                  FROM {certif_completion} a
                  left outer join {certif_completion_history} b on b.certifid=a.certifid and b.userid=a.userid
                  join {block_incident_certif_info} c on c.certif_completion_id=a.id and c.user_id=a.userid
                  join {prog} d on d.certifid=a.certifid
                  and c.cert_status_id=1 and a.userid=:id
                  group by a.userid,a.certifid,a.timecompleted,d.shortname
                  order by a.timecompleted";
*/
        $sql="SELECT a.certifid,d.shortname,ISNULL(x.issuedate,a.timecompleted) firstcompleted,a.timecompleted lastcompleted
                  FROM {certif_completion} a
                  left outer join (
                        select min(timecompleted) issuedate,userid,certifid 
                        from {certif_completion_history}
                        group by userid,certifid) x on a.userid=x.userid and a.certifid=x.certifid
                  join {block_incident_certif_info} c on c.certif_completion_id=a.id
                  join {prog} d on d.certifid=a.certifid and a.userid=:id and c.cert_status_id=1
                  join {course_categories} cc on cc.name = d.shortname
                  where cc.parent = 46
                  group by a.certifid,a.timecompleted,d.shortname, x.issuedate
                  order by a.timecompleted";

        $certList=$DB->get_records_sql($sql,$params);

        foreach($certList as $key=>$cert){
           $certList[$key]->instructor=license::getInstructorName($cert->certifid);
        }
        return $certList;
    }
}
