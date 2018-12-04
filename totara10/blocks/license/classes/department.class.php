<?php
class department
{
    static function getDepartmentList()
    {
        global $DB;
        
       $sql="select distinct a.id, a.idnumber, a.fullname "
              . " from {org} a "
              . " right join {job_assignment} b on a.id=b.organisationid "
              . " right join {block_incident_certif_info} c on c.user_id=b.userid "
               . " where a.id is not null "
              . " order by a.fullname ";
        $depts = $DB->get_records_sql($sql);
        $array=array(''=>'--All Departments--');
        foreach($depts as $value)
        { 
            $combineDepartment = substr($value->idnumber, 0, 4) . ' - ' . $value->fullname; 
            $array[$value->id] = $combineDepartment;
        }
        return $array;
    }
}
