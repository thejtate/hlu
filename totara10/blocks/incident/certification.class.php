<?php
class certification
{
    static function formatListArray($records,$chooseOne=false)
    {
        if(!$chooseOne){
           $array=array(''=>'All  Certifications');
        }
        else{
           $array=array(''=>'Select Certification');
        }  
        
        foreach($records as $value){ 
            $array[$value->certifid]=$value->fullname;
        }
        return $array;
    }
    
    static function getList()
    {
        global $DB;
        return $DB->get_records_select("prog","",null,"fullname");        
    }
    
    static function getCourseList($certifid, $certifpath = 1)
    {
        global $DB;
        $params['certifid'] = $certifid;

        $params['certifpath'] = ($certifpath == 3) ? 2 : $certifpath;

        $sql="select pcc.id, pcc.courseid,c.fullname coursename "
                . " from {prog_courseset_course} pcc "
                . " join {prog_courseset} pc on pc.id=pcc.coursesetid "
                . " join {prog} p on p.id = pc.programid "
                . " join {course} c on c.id = pcc.courseid"
                . " where certifid = :certifid and pc.certifpath = :certifpath"
                . " order by pc.sortorder ";
        return $DB->get_records_sql($sql, $params);        
    }
    
}
