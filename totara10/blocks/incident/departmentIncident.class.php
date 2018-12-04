<?php

class departmentIncident
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
            . " join {prog} p on p.certifid=cc.certifid "
            . " where a.deleted=0 and b.deleted=0  $wheres";
         
          return $sqlBody;
    }
    
    static function setDepartmentSQLCount($wheres)
    {
         $sqlBody=  departmentIncident::setSQLBody($wheres);
         $sql = "select count(distinct f.fullname) recordCount
                          $sqlBody 
                    ";
         return $sql;
    }
    
    static function setDepartmentSQL($wheres)
    {
         $sqlBody=  departmentIncident::setSQLBody($wheres);
         $sql="select distinct f.id,f.fullname department "
                 . " $sqlBody  order by f.fullname ";
         return $sql;
    }
    
    static function setEmployeeSQL($wheres,$sorting)
    {
         $sqlBody=  departmentIncident::setSQLBody($wheres);
         $sql="select bic.id, a.idnumber 'Employee ID',a.username 'Badge Number',a.firstname 'First Name',a.lastname 'Last Name','' manager,f.fullname department,p.fullname certification, "
            . " incident_datetime 'Incident Date',expires_date 'Expiry Date', ic.id as classificationid, b.points, b.description,b.user_id, p.certifid, e.managerjaid, e.id as jaid"
                 . " $sqlBody and f.id=:orgid2   order by $sorting ";
         return $sql;
    }
    
    static function getEmployeeTable($employeeList,$dir)
    {
        $table = new html_table();
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=idnumber\">Employee ID</a>";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=username\">Badge Number</a>";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=lastname\">Name</a>";
        $table->head[] = "Manager";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=incident_datetime\">Incident Date</a>";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=expires_date\">Expiry Date</a>";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=points\">Points</a>";
        $table->head[] = "<a href=\"view_incident_report.php?pager=column&dir=$dir&sort=p.fullname\">Certification</a>";
        $table->size=array("10%","10%","15%","15%","15%","10%","5%","20%");
        
        foreach($employeeList as $records){
            $records=(array) $records;
            $idnumber = $records['employee id'];
            $username = $records['badge number'];

            // managerjaid in Totara 10 now points to DB table _job_assignment id
            // see getManagerName() 
            $manager=license::getManagerName($records['managerjaid']);

            $name = '<a title="Employee detailed view" href="employee_detail_view.php?userid=' . $records['user_id'] . '">' . $records['last name'] . ', ' . $records['first name'] . '</a>';
            $id = $records['id'];
            $certification = $records['certification'];
            $description = $records['description'];

            if ($records['expiry date'] == 0 or $records['expiry date'] == '') {
                $expires_date = 'Never';
            }
            else {
                $expires_date = date('m/d/Y', $records['expiry date']);
            }

            $incident_date = date('m/d/Y h:i:sa', $records['incident date']);
            $points = $records['points'];
            $table->data[] = array($idnumber, $username, $name, $manager, $incident_date, $expires_date, $points, $certification);
            $cell1 = new html_table_cell(); 
            $cell1->text = '<div style="margin-left:50px;"><span style="font-weight:bold;">Description: </span><br>'.nl2br($description).'</div>';
            $cell1->colspan = 8; 
            $row1 = new html_table_row(); 
            $row1->cells[] = $cell1;
            $table->data[]=$row1;
        }
        
        return $table;
    }
}
