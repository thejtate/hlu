<?php
require_once('includes.php');
require_once($CFG->dirroot.'\MPDF\mpdf.php');

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

// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
$cvsTimestamped = 'masteremployee-' . date('Y-m-d_is') . '.csv';
header("Content-Disposition: attachment; filename=$cvsTimestamped");
// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');
$headers=array('Certification','Active','Expired','Inactive','Revoked','Suspended');
fputcsv($output,$headers);

$records=$DB->get_records_sql($sql);
    $totalByStatus['Active']=0;
    $totalByStatus['Expired']=0;
    $totalByStatus['Inactive']=0;
    $totalByStatus['Revoked']=0;
    $totalByStatus['Suspended']=0;

foreach($records as $record){
        $csv =array($record->fullname,$record->active,$record->expired,$record->inactive,$record->revoked,$record->suspended);
        fputcsv($output,$csv);
        $totalByStatus['Active']+=$record->active;
        $totalByStatus['Expired']+=$record->expired;
        $totalByStatus['Inactive']+=$record->inactive;
        $totalByStatus['Revoked']+=$record->revoked;
        $totalByStatus['Suspended']+=$record->suspended;
}

    $csv=array("Totals",$totalByStatus['Active'],$totalByStatus['Expired'],$totalByStatus['Inactive'],$totalByStatus['Revoked'],$totalByStatus['Suspended']);

    fputcsv($output,$csv);

    fclose($output);