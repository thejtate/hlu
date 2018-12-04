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
$mpdf = new mPDF('utf-8',  // mode - default
                              'Letter',    // format - A4, for example, default ''
                                6,     // font size - default 0
                              'Helvetica',    // default font family
                              23,    // margin_left
                              23,    // margin right
                              18,     // margin top
                               5,    // margin bottom
                               8,     // margin header
                               2,     // margin footer
                               'P');  // L - landscape, P - portrait
                               $mpdf->setFooter('|Page {PAGENO} of {nb}|');
$records=$DB->get_records_sql($sql);

    

    //David Lister
    //Added Hobby Lobby University header similar to site header
    $headerhtml = '<div class="logo"><img style="max-width: 300px; display: inline-block; width: 100%;" src="../../theme/hobbylobby/pix/logo.png" alt="Hobby Lobby"/>';
    $headerhtml .= '<span style="color: #0848AB; font-size: 20px; font-family: Georgia,Arial,Helvetica;">University</span></div>';
    $mpdf->SetHTMLHeader ( $headerhtml, 'O', 'false');
    
    $html .= html_writer::tag("h1", "Certification Counts");
    $html.=html_writer::tag("div","As of: ".date('m/d/Y H:i:s'),array('style'=>'padding:5px;'));

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

    $html .= html_writer::table($table);
    $mpdf->WriteHTML($html);
    $mpdf->Output('CertificationCounts-'.date('Y-m-d_is').'.pdf','D');


