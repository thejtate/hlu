<?php

class incident 
{
    static function setUserId($userid)
    {
        if($userid<>0){
            $_SESSION['userid']=$userid;
        }
        elseif(isset($_SESSION['userid']) and is_numeric($_SESSION['userid']) and $_SESSION['userid']>0){
                $userid=$_SESSION['userid'];
        }
        else{
                redirect("user_search_view.php?action=nouser");
        }
        return $userid;
    }

    static function setIncidentId($incidentid){
        if($incidentid != 0){
            $_SESSION['incidentid'] = $incidentid; 
        } elseif(isset($_SESSION['incidentid']) && is_numberic($_SESSION['incidentid'])) {
            $incidentid = $_SESSION['incidentid'];
        } else {
            redirect("employee_detail_view.php?action=noincidentid");
        } 
        return $incidentid;
    }

    static function getCertificationList($userid)
    {
        global $DB;
        #get current certification info for user
        $params['id']=$userid;
        $params['today']=time();
        $sql="select a.id, b.fullname, timeexpires, a.status, a.timewindowopens, a.certifid, a.timecompleted "
                . " from {certif_completion} a "
                . "left join {prog} b on b.certifid=a.certifid "
                . "where userid=:id ";
        return $DB->get_records_sql($sql, $params);       
    }

    // request to filter list only to Equipment Licensing 
    public function getFilteredCertificationList($userid) {
        global $DB;
        $categories = $this->getCourseCategoryIds();

        $params['id']=$userid;
        $params['today']=time();
        $sql="select a.id,fullname,timeexpires,status,timewindowopens,a.certifid,a.timecompleted "
                . " from {certif_completion} a "
                . "left join {prog} b on b.certifid=a.certifid "
                . "where userid=:id and category in ($categories)";
        return $DB->get_records_sql($sql, $params);       
    }

    static function getCertificationId($certif_completion_id)
    {
        global $DB;
        $params['certif_completion_id']=$certif_completion_id;
        $sql="select certifid from {certif_completion} "
                . "where id=:certif_completion_id ";
        $record= $DB->get_record_sql($sql,$params);
        return $record->certifid;
    }

    static function getProgamCompletionInfo($userid,$certifid)
    {
        global $DB;
        
        $params['certifid']=$certifid;
        $params['userid']=$userid;
        $sql="select a.id as 'id',timedue from {prog_completion} a "
                . " left join {prog} b on a.programid=b.id "
                . " where userid=:userid "
                . " and certifid=:certifid ";
        return $DB->get_record_sql($sql,$params);      
    }

    static function getCurrentCertificationStatus($userid)
    {
        global $DB;
        #get current certification status
        $params['userid'] = $userid;
        $sql="select certif_completion_id,cert_status,suspension_date_ends "
                . "from {block_incident_certif_info} a "
                . " left join {block_incident_cert_status} b on a.cert_status_id=b.id "
                . "where user_id = :userid ";
        return $DB->get_records_sql($sql, $params);       
    }


    static function getIncidentList($userid)
    {
        global $DB;
        
        $sql = "SELECT a.id, p.fullname, bic.incident_id as incident_id, a.description, a.incident_datetime, a.expires_date, a.points, a.incident_type "
                . " from {block_incident} a "
                . " left join {block_incident_certif} bic on bic.incident_id = a.id "
                . " left join {certif_completion} cc on cc.id = bic.certif_completion_id "
                . " left join {prog} p on p.certifid = cc.certifid "
                . " where a.user_id = :userid "; 
        return $DB->get_records_sql($sql, array('userid' => $userid));       
    }

    static function getIncident($incidentid)
    {
        global $DB;
        
        $sql="select a.id, description, incident_datetime, expires_date, points, incident_type"
                . " from {block_incident} a  "
                . "where a.id=:incidentid";
        return $DB->get_record_sql($sql,array('incidentid'=>$incidentid));       
    }

    static function certificateIncidentExists($certif_completion_id)
    {
        global $DB;
        $sql="select id from {block_incident_certif_info} "
                . "where certif_completion_id=:certif_completion_id";
        $result=$DB->get_record_sql($sql,array('certif_completion_id'=>$certif_completion_id));
        return $result->id;
    }

    static function initialIncidentExists($incident_id)
    {
        global $DB;
        $sql="select count(1)record  from {block_incident} "
                . "where id=:incident_id";
        $result=$DB->get_record_sql($sql,array('incident_id'=>$incident_id));
        return $result->record;
    }

    static function incidentExists($incident_id)
    {
        global $DB;
        $sql="select count(1)record  from {block_incident_certif} "
                . "where incident_id=:incident_id";
        $result=$DB->get_record_sql($sql,array('incident_id'=>$incident_id));
        return $result->record;
    }

    static function userIncidentPoints($user_id)
    {
        global $DB;
        $sql="select sum(points) total_points from {block_incident}"
                . " where user_id=:user_id "
                . " and deleted=0 "
                . " and (expires_date = 0 or expires_date = -1 or expires_date > :today)";
        $result=$DB->get_record_sql($sql,array('user_id'=>$user_id,'today'=>time()));
        
        if($result->total_points==''){
            return 0;
        }else{
            return $result->total_points;
        }
    }

    static function certificationIncidentPoints($certif_completion_id)
    {
        global $DB;
        $sql="select sum(points) total_points from {block_incident_certif} a"
                . " left join {block_incident} b on a.incident_id=b.id "
                . "where certif_completion_id=:certif_completion_id "
                . " and a.deleted=0 and b.deleted=0 "
                . " and (expires_date = 0 or expires_date = -1 or expires_date > :today)";
        $result=$DB->get_record_sql($sql,array('certif_completion_id'=>$certif_completion_id,'today'=>time()));
        if($result->total_points==''){
            return 0;
        }else{
            return $result->total_points;
        }
    }

    static function getPointThreshold($cert_status_id)
    {
        global $DB;
        $sql="select point_threshold from {block_incident_thresh}"
                . " where cert_status_id=:cert_status_id ";
        $result=$DB->get_record_sql($sql,array('cert_status_id'=>$cert_status_id));
        return $result->point_threshold;       
    }

    static function getMaximumExpireDate($certif_completion_id)
    {
        global $DB;
        
        $sql="select max(expires_date) max_expire_date from {block_incident_certif} a"
                . " left join {block_incident} b on a.incident_id=b.id "
                . "where certif_completion_id=:certif_completion_id "
                . " and a.deleted=0 and b.deleted=0 "
                . " and (expires_date=0 or expires_date > :today)";
        $result=$DB->get_record_sql($sql,array('certif_completion_id'=>$certif_completion_id,'today'=>time()));
        return $result->max_expire_date;       
    }

    static function calculateSuspensionDate($certif_completion_id)
    {
        global $DB;
        $today=time();
        $maxExpireDate=incident::getMaximumExpireDate($certif_completion_id);
        $suspensionPointThreshold=incident::getPointThreshold(certStatus::SUSPENDED);
        
        for($x=$today;$x<$maxExpireDate;$x+=(60*60*24)){
            $sql="select sum(b.points) total_points from {block_incident_certif} a"
                    . " join {block_incident} b on a.incident_id=b.id "
                    . " where certif_completion_id=:certif_completion_id "
                    . " and a.deleted=0 and b.deleted=0 "
                    . " and :incremented_day between incident_datetime and expires_date";
            $result=$DB->get_record_sql($sql,array('certif_completion_id'=>$certif_completion_id,'incremented_day'=>$x));
            if($result->total_points < $suspensionPointThreshold){
                return $x; 
            }
        }       
        return $maxExpireDate;       
    }

    static function getSuspensionDateExpires($certif_completion_id) {
        $totalPoints = incident::certificationIncidentPoints($certif_completion_id);
        $suspensionPointThreshold = incident::getPointThreshold(certStatus::SUSPENDED);
        $revokePointThreshold = incident::getPointThreshold(certStatus::REVOKED);
        
        if($totalPoints >= $revokePointThreshold) {
            return -1; #revoke license 
        } elseif($totalPoints >= $suspensionPointThreshold and $totalPoints < $revokePointThreshold) {
            return incident::calculateSuspensionDate($certif_completion_id);           
        } else {
            return 0;
        }        
    }

    static function createIncidentTableList($incidentRecords)
    {
        $incidentTable = new html_table();
        $incidentTable->head[] ="Incident Date/Time";
        $incidentTable->head[]="Points";
        $incidentTable->head[]="Date When Removed From Record";
        $incidentTable->head[]="Type (when first assigned)";
        $incidentTable->head[]="License";
        $incidentTable->head[]="Description";
        $incidentTable->head[]="";
        
        foreach ($incidentRecords as $records) {
            $datetime = date('m/d/Y h:i:sa',$records->incident_datetime);
            $description = $records->description;
            $edit='<a href="incident_edit.php?incidentid='. $records->id.'">Edit</a>';

            if($records->expires_date == 0) {
                $expires_date = "Never";
            } elseif($records->expires_date == -1) {
                $expires_date = "Never";
            } else {
                $expires_date = date('m/d/Y', $records->expires_date);
            }
            if(!empty($records->incident_type)){
                $incident_type = $records->incident_type;
            } else{
                $incident_type = "N/A";
            }

            if(!empty($records->fullname)){
                $license = $records->fullname;
            } else {
                $license = "N/A"; 
            }

            $points=$records->points;
            
            $incidentTable->data[] = array($datetime, $points, $expires_date, $incident_type, $license, $description, $edit);    
        }
        
        return $incidentTable;
    }

    

    static function createCertificationTableList($certificationList, $certificationStatusList)
    {
        $certTable = new html_table();
        $certTable->head[] ="Certification";      
        $certTable->head[]="Expiration Date";
        $certTable->head[]="Completion Window Opens";     
        $certTable->head[]="Completion Process Status";     
        $certTable->head[]="Current Points";
        $certTable->head[]="Current Status";
        $certTable->head[]="Suspension Ends";
        
        foreach ($certificationList as $records) {
            $certification=$records->fullname;
            
            if($records->timewindowopens==0){
                $timeWindowOpens='N/A';
            }
            else{
                $timeWindowOpens=date('m/d/Y',$records->timewindowopens);
            }
            
            if($records->status==3 and $records->timeexpires>0){
                $certExpiration = date('m/d/Y',$records->timeexpires);
                $certificationProcessStatus="Completed";
            }else{
                
                switch($records->status){
                    case '1':$certificationProcessStatus='Assigned';
                                $certExpiration='N/A';
                                break;
                    case '2': $certificationProcessStatus='In Progress';
                                    $certExpiration='N/A';
                                break;
                    case '4': $certificationProcessStatus="Expired";
                                    $certExpiration = date('m/d/Y',$records->timeexpires);
                                break;
                }
            }
            
            if(isset($certificationStatusList[$records->id]->cert_status)){
                $currentCertificationStatus=$certificationStatusList[$records->id]->cert_status;
            }
            else{
                $currentCertificationStatus='N/A';
            }
            
            if($currentCertificationStatus=='Suspended'){
                $suspensionDateEnds=date('m/d/Y',$certificationStatusList[$records->id]->suspension_date_ends);
            }
            else{
                $suspensionDateEnds='N/A';
            }
            
            $points=incident::certificationIncidentPoints($records->id);
            
            $certTable->data[] = array($certification,$certExpiration,$timeWindowOpens,$certificationProcessStatus,$points,$currentCertificationStatus,$suspensionDateEnds);    
        }
        
        return $certTable;
    }

    /* Revoke Lincense Controller */
    /* ******* */
    private function epochToday(){
        return time();
    }

    /* get point threshold */
    private function getPtThreshold($certstatusid){
        global $DB;
        $sql = $DB->get_record('block_incident_thresh', array('cert_status_id'=> $certstatusid),'point_threshold');
        return (int)$sql->{'point_threshold'}; 
    }

    /* get all none-expired Incidents */
    private function userNonExpiredIncidents($userid){
        global $DB;
        $sql = $DB->get_recordset_sql("SELECT i.id AS pid, i.user_id AS userid, i.points, i.expires_date, c.certif_completion_id, c.cert_status_id, s.cert_status
                                        FROM {block_incident} i
                                        LEFT JOIN {block_incident_certif_info} c on i.user_id = c.user_id
                                        LEFT JOIN {block_incident_cert_status} s on c.cert_status_id = s.id
                                        WHERE i.user_id = $userid");
        return $sql;
    }

    /* get user incident points */
    public function getUserIncidentPoints($user_id)
    {
        global $DB;
        $sql="select sum(points) total_points from {block_incident}"
                . " where user_id=:user_id "
                . " and deleted=0 "
                . " and (expires_date=0 or expires_date = -1 or expires_date > :today)";
        $result=$DB->get_record_sql($sql,array('user_id'=>$user_id,'today'=>time()));
        
        if($result->total_points==''){
            return 0;
        }else{
            return $result->total_points;
        }
    }

    // get array specific categories, all below (Equipment Management - id:46) 
    public function getCourseCategoryIds() {
        global $DB;
        $sql = $DB->get_recordset_sql("SELECT distinct s.value
                                        FROM {course_categories} cc
                                        CROSS APPLY utilDB.dbo.udt_Split (substring(path, 5, 100), '/') s
                                        WHERE cc.path like '/46/%'
                                        ");
        $queryItems = [];
        foreach($sql as $row){
            array_push($queryItems, (int)$row->value);
        }
        return join(', ', $queryItems);
    }

    /* returns array of cid's filtered to specific categories */
    public function getFilteredCertIds($userid){
        global $DB;

        $courseCategories = $this->getCourseCategoryIds();
        if(isset($courseCategories)){
            $wheres = "AND p.category IN ($courseCategories)";
        } else {
            $wheres = '';
        }

        $sql = $DB->get_recordset_sql("SELECT c.id AS cid, c.certifid AS certifid, p.fullname, p.category AS categoryid, c.userid, u.firstname, u.lastname, u.deleted, u.timemodified
                                    FROM {certif_completion} c
                                    JOIN {user} u ON u.id = c.userid
                                    JOIN {prog} p ON p.certifid = c.certifid
                                    WHERE c.userid = $userid $wheres
                                    ORDER BY u.id
                                    ");
        $queryItems = [];
        foreach($sql as $result){
            array_push($queryItems, (int)$result->cid);
        }
        return join(', ', $queryItems);
    }

    /* MAIN REVOKER */
    /* returns array of all points added: 1)below class a and 2)not-expired*/
    public function checkLicensesPtTotal($userid, $totalPoints){
        global $DB;
        $now = $this->epochToday();
        $getRevokePointThreshold = $this->getPointThreshold(4);
        $userNonExpiredIncidents = $this->userNonExpiredIncidents($userid);

        foreach($userNonExpiredIncidents as $result){
            if(($totalPoints >= $getRevokePointThreshold)){
                if(( (int)$result->expires_date != 0 || (int)$result->expires_date == -1) || ((int)$result->expires_date <= $now)){
                    return true; //revoke license
                }
            }
        }
        return false; 
    }

    /* returns array of 'Class A' incidents */
    public function checkLicenseClassA($userid){
        global $DB;
        $results = $DB->get_recordset_sql("SELECT id, classification, points, expire_days from {block_incident_class} where id = 1"); /* return Class A - 10 pts*/
        foreach($results as $result){
            $getSuspensionPointThreshold = $result->points; 
            $getSuspensionDate = $result->expire_days;  
        }
        $userNonExpiredIncidents = $this->userNonExpiredIncidents($userid);
        foreach($userNonExpiredIncidents as $incident){
            if(isset($getSuspensionPointThreshold)) { 
                if(($incident->points >= $getSuspensionPointThreshold) && ($incident->expires_date == $getSuspensionDate)){
                    return true; //revoke license
                }
            } 
        }
        return false; 
    }

     /* check and revoke user certificates if checker passes */
    public function revokeCertifications($userid, $filteredCertIds, $checkLicensesPtTotal, $checkLicenseClassA){
        global $DB;

        if($filteredCertIds){
            $certifs = "AND i.certif_completion_id IN ($filteredCertIds)";
        } else {
            $certifs = '';
        }
        
        if($checkLicensesPtTotal || $checkLicenseClassA){
            $sql = $DB->get_recordset_sql("SELECT i.id AS id, i.user_id, i.certif_completion_id AS certif_completion_id, i.cert_status_id
                                                FROM {block_incident_certif_info} i
                                                WHERE i.user_id = $userid $certifs");
            $message = '';
            foreach ($sql as $result) {
                $cert = new stdClass();
                $cert->id = $result->id;
                $cert->cert_status_id = certStatus::REVOKED;
                if($DB->update_record('block_incident_certif_info', $cert)){
                    $message .="Revoked certificate [$result->id].\n";
                } else {
                    $message .= "Error revoking certificate [$result->id].\n";
                }
            }
            return $message;
        } 
    }
    
    // Updated to set Suspended licenses past their suspension date to be set to Expired
    public static function setSuspendedLicensesToExpired() {
        global $DB;
        $params['today'] = time();
        $sql ="select cc.id, ci.certif_completion_id, cc.timecompleted, ci.cert_status_id, ci.suspension_date_ends from {certif_completion} cc left join {block_incident_certif_info} ci on cc.id = ci.certif_completion_id where timeexpires > :today and cert_status_id = 3";
        $records = $DB->get_records_sql($sql,$params);
        
        foreach($records as $record){
            $params_ci['id'] = $record->id;
            $sql_ci = "UPDATE {block_incident_certif_info} "
                    . "SET cert_status_id = 5, suspension_date_ends = 0 "
                    . "WHERE certif_completion_id = :id";
            $DB->execute($sql_ci, $params_ci);
            
            $params_cc['id'] = $record->id;
            $params_cc['today'] = time();
            $params_cc['today2'] = time();
            $sql_cc = "UPDATE {certif_completion} "
                    . "SET timewindowopens = :today, timeexpires = :today2, timecompleted = 0 "
                    . "WHERE id = :id";
            $DB->execute($sql_cc, $params_cc);
        }
    }
}
