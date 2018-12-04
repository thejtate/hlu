<?php
require_once('includes.php');
//include_once("dept_cert_count_form.php");
require_once($CFG->dirroot.'\MPDF\mpdf.php');

/*$mform = new dept_cert_count_form();

if ($mform->is_cancelled()) {
    unset($_SESSION['fromform']);
    redirect('view.php');
}elseif ($fromform = $mform->get_data()) {
    if ($fromform) {
        $_SESSION['fromform'] = $fromform;
    }
    else {
        $fromform = $_SESSION['fromform'];
    }
}
*/
$sql="select * from (
          SELECT c.certifid,d.fullname,cert_status
            FROM {block_incident_certif_info} a
            join {block_incident_cert_status} b on a.cert_status_id=b.id
            join {certif_completion} c on c.id=a.certif_completion_id
            join {prog} d on d.certifid=c.certifid
            join {job_assignment} e on e.userid=c.userid
            join {org} f on f.id=e.organisationid
            )tableDate
            pivot (
                  count(cert_status)
                  for [cert_status] in ([Active],[Expired],[Inactive],[Revoked],[Suspended])
            ) PivotTable";

$records=$DB->get_records_sql($sql);

if($fromform->pdfbutton){
    die('pdf');
}
elseif( $fromform->csvbutton){
    die('csv');
}
else{
    // normal
    $PAGE->navbar->ignore_active();
    $PAGE->navbar->add('License Management Menu','view.php');
    $PAGE->navbar->add('Certification Counts');

    #set page header
    require_capability('block/license:viewpages', context_course::instance($COURSE->id));
    $PAGE->set_url('/blocks/license/view_cert_counts.php', array());
    $PAGE->set_heading(get_string('license', 'block_license'));


    echo $OUTPUT->header();
    echo html_writer::tag("h2", "Certification Counts");
    echo html_writer::tag('div','<a href="pdf_cert_counts.php">Download PDF</a> | <a href="csv_cert_counts.php">Download CSV</a><br /><br />');
    

    $table = new html_table();
    $table->head[]="Certification";
    $table->head[]="Active";
    $table->head[]="Expired";
    $table->head[]="Inactive";
    $table->head[]="Revoked";
    $table->head[]="Suspended";


    $table->size=array("40%","12%","12%","12%","12%","12%");
    // updated - added last two joins
    
    $totalByStatus['Active']=0;
    $totalByStatus['Expired']=0;
    $totalByStatus['Inactive']=0;
    $totalByStatus['Revoked']=0;
    $totalByStatus['Suspended']=0;

    foreach($records as $record){
        $table->data[] =array($record->fullname,$record->active,$record->expired,$record->inactive,$record->revoked,$record->suspended);
        $totalByStatus['Active']+=$record->active;
        $totalByStatus['Expired']+=$record->expired;
        $totalByStatus['Inactive']+=$record->inactive;
        $totalByStatus['Revoked']+=$record->revoked;
        $totalByStatus['Suspended']+=$record->suspended;
    }

    $table->data[]=array("Totals",$totalByStatus['Active'],$totalByStatus['Expired'],$totalByStatus['Inactive'],$totalByStatus['Revoked'],$totalByStatus['Suspended']);

    $table->align[1] = 'right';
    $table->align[2] = 'right';
    $table->align[3] = 'right';
    $table->align[4] = 'right';
    $table->align[5] = 'right';

    echo html_writer::table($table);
    echo html_writer::tag('div','<a href="view.php">Back To License Management Menu</a><br /><br />');

    echo $OUTPUT->footer();





}