<?php

class courseSchedule
{
    
    static function isComplete($userid,$certifid)
    {
        global $DB;
        
        $params['userid']=$userid;
        $params['certifid']=$certifid;
        $sql="SELECT count(1) record
                  FROM {block_license_emp_sched} bles
                  where bles.userid=:userid and bles.certifid=:certifid
                  and (instructorid is null or startdate is null or roomid is null) ";
        $info=$DB->get_record_sql($sql,$params);
        if($info->record > 0){
            return false;
        }
        return true;
    }
    
    static function getUserSchedule($userid,$certifid)
    {
        global $DB;
        
        $params['userid']=$userid;
        $params['certifid']=$certifid;
        $sql="SELECT bles.id,c.fullname coursename,bles.startdate,fr.building + ', ' +fr.name room, bli.firstname+' '+bli.lastname instructor,bles.certifpath,pc.sortorder,review
                  FROM {block_license_emp_sched} bles
                  join {course} c on c.id=bles.courseid
                  left outer join {facetoface_room} fr on fr.id=bles.roomid
                  left outer join {block_license_instructor} bli on bli.id=bles.instructorid
                  join  {prog_courseset_course} pcc on pcc.courseid=c.id 
                  join {prog_courseset} pc on pc.id=pcc.coursesetid and pc.certifpath=bles.certifpath
                  join {prog} p on p.certifid=bles.certifid and p.id=pc.programid
                  where bles.userid=:userid and bles.certifid=:certifid
                  and bles.deleted=0
                  order by pc.sortorder ";
        return $DB->get_records_sql($sql,$params);
    }
    
   static function createCourseScheduleTableList($courseScheduleList)
   {
       $table = new html_table();
       $table->head[] ="Course";
       $table->head[]="Type";
       $table->head[]="Date";
       $table->head[]="Room";
       $table->head[]="Instructor";
       $table->head[]="&nbsp;";       
       
       foreach ($courseScheduleList as $record) {
           if($record->startdate==0){
               $startdate='';
           }
           else{
               $startdate = date('m/d/Y h:i:sa',$record->startdate);
           }
           $coursename = $record->coursename;
           $instructor = $record->instructor;
           if($record->certifpath==1){
               $certifpath='New';
           }else{
               if($record->review==1){
                   $certifpath='Review';
               }
               else{
                   $certifpath='Recert';
               }
           }
           $room=$record->room;
           $js="if(confirm('Are you sure you want to delete this course?')){return true;}else{return false;}";
           $table->data[] = array($coursename,$certifpath,$startdate,$room,$instructor,'<a href="set_course_date.php?blesid='.$record->id.'">Edit</a> | <a onClick="'.$js.'" href="set_course_schedule.php?action=delete&blesid='.$record->id.'">Delete</a> ');    
        }
        
        return $table;
   }
   
   static function setWheres($fromform)
   {
       $wheres='';
       if($fromform->startdate!=''){
           $wheres.=" and bles.startdate >= :startdate ";
       }
       if($fromform->enddate!=''){
           $wheres.=" and bles.startdate <= :enddate ";
       }
       if($fromform->orgid!=''){
           $wheres.=" and o.id = :orgid ";
       }
       if($fromform->instructorid!=''){
           $wheres.=" and bles.instructorid=:instructorid ";
       }       
       if($fromform->certifid!=''){
           $wheres.=" and bles.certifid = :certifid ";
       }
      if($fromform->certifpath!=''){
          if($fromform->certifpath==3){
               $wheres.=" and bles.certifpath = 2 and review=1 ";
          }else  if($fromform->certifpath==2){
               $wheres.=" and bles.certifpath = :certifpath and review=0 ";
          }
          else{
              $wheres.=" and bles.certifpath = :certifpath ";
          }
       }
       return $wheres;
   }
   
   static function setParams($fromform)
   {
       $params=array();
       if($fromform->startdate!=''){
           $params['startdate']=$fromform->startdate;
       }
       if($fromform->enddate!=''){
           $params['enddate']=$fromform->enddate + ((60*60*23)+(60*59)+59);
       }
       if($fromform->orgid!=''){
           $params['orgid']=$fromform->orgid;
       }
       if($fromform->instructorid!=''){
           $params['instructorid']=$fromform->instructorid;
       }
       if($fromform->certifid!=''){
           $params['certifid']=$fromform->certifid;
       }
        if($fromform->certifpath!=''){
           if($fromform->certifpath!=3){
              $params['certifpath']=$fromform->certifpath;
           }
       }
       return $params;
   }
   
   static function getScheduleList($wheres,$params,$orderby='department')
   {
       global $DB;
       
       if($orderby=='department'){
           $orderby=" order by o.fullname,bles.startdate,u.lastname,u.firstname ";
       }
       else if($orderby=='instructor'){
           $orderby=" order by bli.lastname,bli.firstname,bles.startdate,p.fullname,u.lastname,u.firstname ";
       }
       
        $sql="SELECT bles.id,c.fullname coursename,bles.startdate,fr.building + ', ' +fr.name room, bli.firstname+' '+bli.lastname instructor,
                                 (case  when bles.review=1 then 'Review' when bles.certifpath=1 then 'New' else 'Recert' end)certifpath,
                                 u.username badge,p.fullname certification,
                                 u.lastname+', '+u.firstname employee,substring(o.idnumber,1,4) department,o.fullname departmentname,
                                 u2.firstname+' '+u2.lastname manager
                  FROM {block_license_emp_sched} bles
                  join {course} c on c.id=bles.courseid
                  left outer join {facetoface_room} fr on fr.id=bles.roomid
                  left outer join {block_license_instructor} bli on bli.id=bles.instructorid
                  join {prog} p on p.certifid=bles.certifid
                  join {user} u on u.id=bles.userid
                  join {job_assignment} pa on pa.userid=u.id
                  join {user} u2 on u2.id=pa.managerjaid
                  join {org} o on o.id=pa.organisationid
                  where bles.deleted=0
                  $wheres 
                  $orderby ";
        return $DB->get_records_sql($sql,$params);
   }
}
