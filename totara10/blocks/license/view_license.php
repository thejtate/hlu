<?php
require_once('includes.php');
$PAGE->set_url('/blocks/license/view_license.php', array());
include_once("../incident/certStatus.class.php");
include_once("../incident/incident.class.php");
unset($_SESSION['camefrom']);

$action = optional_param('action','',PARAM_ALPHA);
$blockIncidentCertifInfoId = optional_param('id','',PARAM_INT);
$new_cert_status = optional_param('new_cert_status','',PARAM_INT);

if($action =='toggle' and is_numeric($blockIncidentCertifInfoId) and in_array($new_cert_status,array(certStatus::ACTIVE,certStatus::INACTIVE))){
     $record = new stdClass();
     $record->id = $blockIncidentCertifInfoId;
     $record->cert_status_id = $new_cert_status;
     if($DB->update_record("block_incident_certif_info",$record)){
         $recordUpdated = true;
     }
 }

$sort = optional_param('sort', 'lastname', PARAM_CLEAN);
$dir = optional_param('dir', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 150, PARAM_INT);
$pager = optional_param('pager', '', PARAM_ALPHA);
$dir = license::setDirection($pager, $dir);
$mform = new license_search_form();

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    unset($_SESSION['fromform']);
    unset($_SESSION['dir']);
    redirect('view.php');

} elseif ($fromform = $mform->get_data() or is_numeric($page)) {

    if ($fromform) {
        $_SESSION['fromform'] = $fromform;
    } else if(isset($_SESSION['fromform'])) {
        $fromform = $_SESSION['fromform'];
    }

    $mform->set_data($fromform);
    $wheres = license::setWhereClause($fromform);
    $params = license::setParamsArray($fromform);
    $sorting = license::setSort($sort, $dir);


    $sqlBody=" from  {block_incident_certif_info} a "
            . " join {certif_completion} d on d.id=a.certif_completion_id "    
            . " left outer join (select min(timecompleted) issuedate,userid,certifid from {certif_completion_history} group by userid,certifid) x on d.userid=x.userid and d.certifid=x.certifid "
            . " join {user} b on a.user_id=b.id "
            . " left outer join {job_assignment} f on f.userid = b.id "
            . " left outer join {org} g on f.organisationid=g.id "                    
            . " left outer join {prog} c on d.certifid=c.certifid "
            . " join {block_incident_cert_status} e on e.id=a.cert_status_id "
            . " left join {course_categories} cc on category = cc.id "
            . " where b.deleted=0"
            . $wheres;

    $sql = "select cast(b.id as varchar) + cast(d.certifid as varchar) id,lastname+', '+firstname 'Employee Name',
            username 'Badge Number', b.idnumber 'Employee ID',g.fullname department, d.certifid 'Cert ID', 
            '' manager,c.fullname 'Certification',cert_status 'Status',
            ISNULL(x.issuedate,d.timecompleted) 'Date Issued',
            d.timewindowopens 'Recert Date', d.timeexpires 'Expr Date', 
            f.managerjaid,a.user_id,a.id block_certif_info_id, cc.printability 
            $sqlBody 
            order by $sorting";

    #for CSV download
    if(isset($_POST['downloadbutton']) and $_POST['downloadbutton']){
        $searchResultList = $DB->get_records_sql($sql, $params);
       foreach($searchResultList as $key=>$record){
           $searchResultList[$key]->manager=license::getManagerName($record->managerjaid);
        }
      
        downloadCSV::generate($searchResultList, 3); 
        die;
    }

    ?>
    <?php

    $searchResultList = $DB->get_records_sql($sql, $params, $page*$perpage, $perpage);
    $sql2 = "select count(*) $sqlBody "; 
    $recordCount = $DB->count_records_sql($sql2, $params);
    $table = license::getSearchResultsTable($page, $dir, $searchResultList, 'license');
    $baseurl = new moodle_url('/blocks/license/view_license.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
}

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License Management Menu','view.php');
$PAGE->navbar->add('View/Print License');
echo $OUTPUT->header();

if(isset($recordUpdated) and $recordUpdated){
    echo html_writer::tag('div','License status updated.',array('class'=>'alert alert-info'));
}
echo html_writer::tag("h1", "License Management");

// Show who is on the print que 
if($_SESSION['print_users']){
    echo '<div class="print-que">';
    echo '<b>Users licenses that will be printed:</b>';
    echo '<ul>';
    foreach($_SESSION['print_users'] as $key => $value){
        echo '<li>'.$value.'</li>';
    }
    echo '</ul>';
    echo '<a href="clear_print_session.php">Clear Print Queue</a>';
    echo '<form method="post" action="print_licenses.php"><button name="button" value="Generate Licenses PDF" id="print_sumbit_button">Generate Licenses PDF</button></form>';
    echo '</div>';
}
 
if(isset($_SESSION['fromform'])){
   $fromform = $_SESSION['fromform'];
}

$mform->set_data($fromform);
$mform->display();
echo $OUTPUT->paging_bar($recordCount, $page, $perpage, $baseurl);
echo html_writer::start_tag("form",array('method'=>'post','action'=>"print_licenses.php"));
echo html_writer::table($table);
echo html_writer::start_tag("button",array('name'=>'button','value'=>"Generate Licenses PDF", 'id'=>'print_sumbit_button'));
echo "Generate Licenses PDF";
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
?>

<div class="modal"></div>

<script>
    // ajax loader
    $body = $("body");
    $(document).on({
        ajaxStart: function() { $body.addClass("loading"); },
        ajaxStop: function() { $body.removeClass("loading"); } 
    });
    $.mockjax({ url: "/mockjax", responseTime: 5000 });

    // print button actions  
    function AddToSession(userid, employee) {
        $.post("add_to_print_queue.php", { 
            user: userid, 
            employee: employee, 
        });
        $.get("/mockjax");  
        setTimeout(() => {
            location.reload();
        }, 600);
    };

    // correctly clear out checkboxes once form submited. 
    $('#print_sumbit_button').click(
        function(){
            $(".case").prop("checked", false);
        }
    );
</script>