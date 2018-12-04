<?php

class operatorIncident
{
    static function setSorting($sort,$dir)
    {
          if ($sort == 'lastname') {
                $sorting =  $sort . ' ' . $dir . ',firstname';
          }
          else {
                $sorting =  $sort . ' ' . $dir;
          }
          return $sorting;
    }
    
    static function setWhereClause($fromform)
    {
         $wheres = '';
         if ($fromform->username) {
             $wheres.=" and username=:username";
         }

         if ($fromform->lastname) {
             $wheres.=" and lastname like :lastname";
         }

         if ($fromform->firstname) {
            $wheres.=" and firstname like :firstname";
        }
        
        if ($fromform->idnumber) {
            $wheres.=" and a.idnumber=:idnumber";
        }
    
        if ($fromform->certifid) {
           $wheres.=" and p.certifid=:certifid ";
        }
    
        if ($fromform->incident_datetime_from) {
           $wheres.=" and incident_datetime>=:incident_datetime_from";
        }
        
        if ($fromform->incident_datetime_to) {
           $wheres.=" and incident_datetime<=:incident_datetime_to";
        }

        if ($fromform->classificationid) {
           $wheres.=" and ic.id = :classificationid";
        }
        
        if (isset($fromform->orgid) and $fromform->orgid > 0) {
           $wheres.=" and f.id =:orgid";
        }
        
        return $wheres;
    }

    static function setParamsArray($fromform)
    {
        $params = array();
        if (isset($fromform->username) and $fromform->username) {
           $params['username'] = $fromform->username;
        }

        if (isset($fromform->lastname) and $fromform->lastname) {
           $params['lastname'] = strtoupper($fromform->lastname . '%');
        }

        if (isset($fromform->firstname) and $fromform->firstname) {
           $params['firstname'] = strtoupper($fromform->firstname . '%');
        }
        
        if (isset($fromform->idnumber) and $fromform->idnumber) {
           $params['idnumber'] = trim($fromform->idnumber);
        }
        
        if (isset($fromform->certifid) and $fromform->certifid) {
           $params['certifid'] = trim($fromform->certifid);
        }

        if (isset($fromform->classificationid) and $fromform->classificationid) {
            $params['classificationid'] = $fromform->classificationid;
        }
        
        if (isset($fromform->incident_datetime_from) and $fromform->incident_datetime_from) {
           $params['incident_datetime_from'] = $fromform->incident_datetime_from;
        }
        
        if (isset($fromform->incident_datetime_to) and $fromform->incident_datetime_to) {
           $params['incident_datetime_to'] = $fromform->incident_datetime_to;
        }
    
        if (isset($fromform->orgid) and $fromform->orgid > 0) {
           $params['orgid'] = $fromform->orgid;
        }
        
        return $params;
    }
    
    static function setSQLBody($wheres)
    {
          $sqlBody= " from {block_incident} b  "
            . " join {block_incident_class} ic on b.points = ic.points "
            . " join {user} a on a.id=b.user_id "
            . " join {job_assignment} e on e.userid=b.user_id  "
            . " join {org} f on f.id=e.organisationid "
            . " join {block_incident_certif} bic on bic.incident_id=b.id "
            . " join {certif_completion} cc on cc.id=bic.certif_completion_id "
            . " join {block_incident_certif_info} ci on ci.certif_completion_id = cc.id"
            . " join {prog} p on p.certifid=cc.certifid "
            . " where a.deleted=0 and b.deleted=0  $wheres";
         
          return $sqlBody;
    }

    static function setUserSQL($wheres) {
         $sqlBody = operatorIncident::setSQLBody($wheres);
         $sql="select distinct a.id, a.firstname, a.lastname, a.username, a.idnumber $sqlBody order by a.lastname ";
         return $sql;
    }

    static function setUserSQLCount($wheres) {
         $sqlBody = operatorIncident::setSQLBody($wheres);
         $sql = "select count(distinct a.id) recordCount $sqlBody ";
         return $sql;
    }
    
    static function setEmployeeSQL($wheres, $sorting)
    {
         $sqlBody = operatorIncident::setSQLBody($wheres);
         $sql="select bic.id, a.idnumber 'Employee ID',a.username 'Badge Number',a.firstname 'First Name',a.lastname 'Last Name','' manager,f.fullname department,p.fullname certification, ci.cert_status_id as status, "
            . " incident_datetime 'Incident Date',expires_date 'Expiry Date', suspension_date_ends 'Suspension Ends', ic.id as classificationid, b.points, b.description, b.user_id 'User Id', p.certifid, e.managerjaid, e.id as jaid"
            . " $sqlBody and a.id = :userid order by $sorting ";
         return $sql;
    }
    
    static function getEmployeeTable($employeeList, $dir)
    {
        $table = new html_table();
        $table->head[] = "Manager";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=incident_datetime\">Incident Date</a>";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=expires_date\">Expiry Date</a>";
        $table->head[] = "Suspension Ends";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=points\">Points</a>";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=p.fullname\">Certification</a>";
        $table->head[] = "Status";
        $table->size = array("15%","15%","10%","15%","5%","20%","5%");
        
        foreach($employeeList as $records){
            $records = (array) $records;
            $idnumber = $records['employee id'];
            $username = $records['badge number'];

            // managerjaid in Totara 10 now points to DB table _job_assignment id
            // see getManagerName() 

            if(license::getManagerName($records['managerjaid'])){
                $manager = license::getManagerName($records['managerjaid']);
            } else {
                $manager = 'n/a';
            }

            $name = '<a title="Employee detailed view" href="employee_detail_view.php?userid=' . $records['user id'] . '">' . $records['last name'] . ', ' . $records['first name'] . '</a>';
            $id = $records['id'];
            $certification = $records['certification'];

            switch($records['status']) {
                case certStatus::ACTIVE: 
                    $status = 'Active';
                    break; 
                case certStatus::INACTIVE: 
                    $status = 'Inactive';
                    break;
                case certStatus::SUSPENDED: 
                    $status = 'Suspended';
                    break;
                case certStatus::REVOKED: 
                    $status = 'Revoked';
                    break;
                case certStatus::EXPIRED: 
                    $status = 'Expired';
                    break;
            }

            $description = $records['description'];
            
            if($records['suspension ends'] == 0){
                $suspension_end = 'n/a';
            } else {
                $suspension_end = date('m/d/Y', $records['suspension ends']);
            }

            if ($records['expiry date'] == 0 or $records['expiry date'] == '') {
                $expires_date = 'Never';
            }
            else {
                $expires_date = date('m/d/Y', $records['expiry date']);
            }

            $incident_date = date('m/d/Y h:i:sa', $records['incident date']);
            $points = $records['points'];
            $table->data[] = array($manager, $incident_date, $expires_date, $suspension_end, $points, $certification, $status);
            $cell1 = new html_table_cell(); 
            $cell1->text = '<div style="margin-left:50px;"><span style="font-weight:bold;">Description: </span><br>'.nl2br($description).'</div>';
            $cell1->colspan = 8; 
            $row1 = new html_table_row(); 
            $row1->cells[] = $cell1;
            $table->data[] = $row1;
        }
        
        return $table;
    }
}
