<?php

class masterEmployee
{
    static function setWhereClause($fromform)
    {
        $wheres = '';
          if ($fromform->cert_status_id) {
            $wheres.=" and cert_status_id =:cert_status_id";
        }
      
        if ($fromform->orgid) {
            $wheres.=" and f.organisationid =:orgid";
        }

        if ($fromform->certifid){
            $wheres.=" and d.certifid=:certifid";
        }
      
        if ($fromform->username) {
            $wheres.=" and username =:username";
        }
        
        if ($fromform->lastname) {
            $wheres.=" and lastname like :lastname";
        }

        if ($fromform->firstname) {
            $wheres.=" and firstname like :firstname";
        }
        
        // if($fromform->terminated){
        //     $wheres.="  and b.deleted=1 ";
        // }else{
        //     $wheres.="  and b.deleted=0 ";
        // }
        
        return $wheres;
    }

    static function setParamsArray($fromform)
    {
        $params = array();
     
        if ($fromform->cert_status_id) {
            $params['cert_status_id'] = $fromform->cert_status_id;
        }
        
        if ($fromform->username) {
            $params['username'] = $fromform->username;
        }

        if ($fromform->orgid) {
            $params['orgid']=$fromform->orgid;
        }

        if ($fromform->certifid) {
            $params['certifid'] = $fromform->certifid;
        }
         
        if ($fromform->lastname) {
            $params['lastname'] = strtoupper($fromform->lastname . '%');
        }

        if ($fromform->firstname) {
            $params['firstname'] = strtoupper($fromform->firstname . '%');
        }
       
        // if($fromform->terminated){
        //     $params['deleted']=1;
        // }else{
        //     $params['deleted']=0;
        // }
        return $params;
    }

    // Way query was orignally built, returns multiple records for a user. 
    // If errors enabled, /employee_detail_view.php will have issues with pdf and excel generator. 
    // Need to come back and fix sometime. 
    static function setSQLBody($wheres)
    {
        $sqlBody = " from  {block_incident_certif_info} a "
            . " join {certif_completion} d on d.id=a.certif_completion_id " 
            . " left outer join (select min(timecompleted) issuedate,userid,certifid from {certif_completion_history} group by userid,certifid) x on d.userid=x.userid and d.certifid=x.certifid "
            . " join {user} b on a.user_id=b.id "
            . " join {job_assignment} f on f.userid = b.id "
            . " join {org} g on f.organisationid=g.id "                    
            . " join {prog} c on d.certifid=c.certifid "
            . " join {block_incident_cert_status} e on e.id=a.cert_status_id "
            . " where 1=1 "
            . $wheres;
          return $sqlBody;
    }    

    static function setEmployeeSQL($wheres,$orderby="lastname,firstname")
    {
         $sqlBody = masterEmployee::setSQLBody($wheres);
         $sql = "select distinct a.user_id, lastname+', '+firstname 'Employee Name',
                          username 'Badge Number', b.idnumber 'Employee ID',g.fullname department, lastname, firstname, d.certifid 'certifid',
                          substring(g.idnumber,1,4) departmentnumber
                $sqlBody 
               order by $orderby";
         return $sql;
    }



    static function setEmployeeSQLCount($wheres)
    {
         $sqlBody=masterEmployee::setSQLBody($wheres);
         $sql = "select count(distinct a.user_id)
                          $sqlBody 
                    ";
         return $sql;        
    }
    
    static function setCertificationSQL($wheres)
    {
         $sqlBody = masterEmployee::setSQLBody($wheres);
         $sql = "select cast(b.id as varchar) + cast(d.certifid as varchar) id,
                          c.fullname 'Certification', a.user_id, cert_status 'Status',
                          ISNULL(x.issuedate,d.timecompleted) 'Date Issued',
                          d.timewindowopens 'Recert Date',
                          d.timeexpires 'Expr Date'                         
                $sqlBody and a.user_id=:userid
               order by c.fullname ";
         return $sql;
    }
    
    static function getCertificationTable($certList)
    {
        $table = new html_table();
        $table->head[] = "Certification";
        $table->head[] = "Date Issued";
        $table->head[] = "Recert Date";
        $table->head[] = "Expr Date";
        $table->head[] = "Status";
        $table->align[0] = 'left';
        $table->align[1] = 'left';
        $table->align[2] = 'left';
        $table->align[3] = 'left';
        $table->align[4] = 'left';
        $table->size = array("40%","15%","15%","15%","15%");
        
        foreach ($certList as $records) {
           $records = (array)$records;
           $fullname = $records['certification'];
           $cert_status = $records['status'];
           if($records['expr date'] == 0){ $cert_exp_date = '---'; } else { $cert_exp_date = date('m/d/Y',$records['expr date']); };
           if($records['recert date'] == 0){ $recert_date = '---'; } else { $recert_date = date('m/d/Y',$records['recert date']); };
           if($records['date issued'] == 0){ $date_issued = '---'; } else { $date_issued = date('m/d/Y',$records['date issued']); };
           $table->data[] = array($fullname,$date_issued,$recert_date,$cert_exp_date,$cert_status);        
       }
       
       return $table;
    }
}
