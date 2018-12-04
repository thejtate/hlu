<?php
require_once('includes.php');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License Management Menu','view.php');
$PAGE->navbar->add('View Instructor Stats');

#set page header
require_capability('block/license:viewpages', context_course::instance($COURSE->id));
$PAGE->set_url('/blocks/license/view_instructor_test_stats.php', array());
$PAGE->set_heading(get_string('license', 'block_license'));

//Instantiate simplehtml_form
$mform = new instructor_test_stats_form();
$new_params = array();

if (!$mform->is_cancelled()) {
    #set form values
    $userid = optional_param('userid','', PARAM_INT);
    $courseid=optional_param('courseid','',PARAM_INT);

    $userWhere=null;
    $userParam=null;
    $courseWhere='';
    $courseParam='';
    if($userid>0){
        $userParam['userid']=$userid;
        $new_params['userid'] = $userid;
        $userWhere=" and u.id=:userid";
    }

    if($courseid>0){
        $courseParam['courseid']=$courseid;
        $new_params['courseid'] = $courseid;
        $courseWhere=" and c.id=:courseid";
    }
}
else{
    redirect('view.php');
}

echo $OUTPUT->header();
echo html_writer::tag("h2", "Instructor Testing Statistics");

$mform->display();
$infoFound=false;

$sql_new = "SELECT ROW_NUMBER() OVER(ORDER BY ins.lastname
	                  ,ins.firstname),
                    prog.fullname CertificationName
	                  ,c.fullname CourseName
	                  ,ins.firstname InstFirstName
	                  ,ins.lastname InstLastName
                      ,ins.id as instructorid
	                  ,count(1) TotalTests
	                  ,sum(gg.finalgrade)/count(1) AvgFinalGrade
	                  ,sum(case when gg.finalgrade<75 then 1 else 0 end) TotalFailedTests
	                  ,sum(case when gg.finalgrade>=75 then 1 else 0 end) TotalPassedTests
	                  ,sum(case when convert(date,dateadd(S,s.[startdate],'1970-01-01')) between getdate()-30 and getdate()
	                                    then 1 else 0 end) TotalTestLast30Days
	                  ,iif(sum(case when convert(date,dateadd(S,s.[startdate],'1970-01-01')) between getdate()-30 and getdate()
	                                    then 1 else 0 end)>0,
	                    sum(case when convert(date,dateadd(S,s.[startdate],'1970-01-01')) between getdate()-30 and getdate()
	                                    then gg.finalgrade else 0 end)/sum(case when convert(date,dateadd(S,s.[startdate],'1970-01-01')) between getdate()-30 and getdate()
	                                    then 1 else 0 end),0) AvgFinalGradeLast30Days
                  FROM {block_license_emp_sched} s
                      inner join {prog} prog on prog.certifid=s.certifid
                      inner join {course} c on c.id=s.courseid and s.deleted=0
                      inner join {block_license_instructor} ins on ins.id=s.instructorid and ins.deleted=0
                      inner join {user} u on  u.lastname=ins.lastname and u.firstname=ins.firstname and u.deleted=0
                      inner join {user} u2 on u2.id=s.userid and u2.deleted=0
                      inner join {grade_items} gi on gi.courseid=c.id
                      inner join {grade_grades_history} gg on gg.rawgrade is not null and gg.itemid=gi.id
                                        and gg.userid=u2.id
                WHERE 1=1 " .$userWhere. $courseWhere . "
                group by
	                  ins.lastname
	                  ,ins.firstname
                      ,ins.id
                      ,c.fullname
	                  ,prog.fullname
                order by
	                  ins.lastname
	                  ,ins.firstname

";
$report_results=$DB->get_records_sql($sql_new,$new_params);
if(count($report_results)>0){
    $infoFound=true;
    $fullname = "";
    $name = "1";
    $resetter = 0;
    foreach($report_results as $result)
    {
        if($fullname != $name){

            if ($resetter != 0){
                echo html_writer::table($table);
                $resetter = 0;
            }
            echo "<h3>".$result->instfirstname." ".$result->instlastname."</h3>";
            $table = new html_table();
            $table->head[]="Certification";
            $table->head[]="Course";
            $table->head[]="Total Tests";
            $table->head[]="Last 30 Days";
            $table->head[]="Avg Score";
            $table->head[]="Avg (30 Days)";
            $table->head[]="Total Passed";
            $table->head[]="Total Failed";
            $table->size=array("20%","20%","10%","10%","10%","10%","10%","10%");
            $fullname = $result->instfirstname . $result->instlastname;
        }

        $table->data[] =array(ucwords(strtolower($result->certificationname)),ucwords(strtolower($result->coursename)),$result->totaltests,number_format($result->avgfinalgrade,2),$result->totalfailedtests,$result->totalfailedtests,$result->totalpassedtests,/*$result->totaltestlast30days,*/number_format($result->avgfinalgradelast30days,2));
        $name = $result->instfirstname . $result->instlastname;
        $resetter ++;
    }
    echo html_writer::table($table);
} elseif (!$infoFound){
    echo html_writer::tag('div','No results found.',array('class' => 'no-results empty-custom-block-table'));
}

// Moved this to above to have proper instance 
 
//echo html_writer::table($table);
//$instructors = $instructor;
// if(!$infoFound){
//     echo html_writer::tag('div','No results found.');
// }

echo $OUTPUT->footer();