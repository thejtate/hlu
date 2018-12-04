<?php
require_once('includes.php');
require_once($CFG->dirroot.'/MPDF/mpdf.php');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('License Management Menu','view.php');
$PAGE->navbar->add('Department Certification Counts');

#set page header
require_capability('block/license:viewpages', context_course::instance($COURSE->id));
$PAGE->set_url('/blocks/license/view_dept_cert_counts.php', array());
$PAGE->set_heading(get_string('license', 'block_license'));


//Instantiate simplehtml_form
$mform = new dept_cert_count_form();
$fromform = $mform->get_data();

// allow the PDF and CSV to be created, and allow page to be created in HTML
if (!$fromform->pdfbutton && !$fromform->csvbutton)
{
    echo $OUTPUT->header();
    $mform->display();
}

if (!$mform->is_cancelled()) {
    #set form values
    $orgid = optional_param('orgid','', PARAM_INT);
    $params=array();
    $wheres="";
    if($orgid>0){
        $params['orgid']=$orgid;
        $wheres=" where f.id=:orgid";
    }

    $sql="select * from (
                SELECT cast(f.id as varchar)+cast(c.certifid as varchar) keys,f.idnumber prefixid,c.certifid,d.fullname,f.fullname department,cert_status
                  FROM {block_incident_certif_info} a
                  join {block_incident_cert_status} b on a.cert_status_id=b.id
                  join {certif_completion} c on c.id=a.certif_completion_id
                  join {prog} d on d.certifid=c.certifid
                  join {job_assignment} e on e.userid=c.userid
                  join {org} f on f.id=e.organisationid
                  $wheres
                  )tableDate
                  pivot (
                        count(cert_status)
                        for [cert_status] in ([Active],[Expired],[Inactive],[Revoked],[Suspended])
                  ) PivotTable
                  order by department";

    $records=$DB->get_records_sql($sql,$params);

    /// Add code here to check what type of output
    /// All output is determined by the results of the code above

    if($fromform->pdfbutton)
    {
//        echo html_writer::tag("h2", "PDF");
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
                               
        //David Lister
        //Added Hobby Lobby University header similar to site header   
        $headerhtml = '<div class="logo"><img style="max-width: 300px; display: inline-block; width: 100%;" src="../../theme/hobbylobby/pix/logo.png" alt="Hobby Lobby"/>';
        $headerhtml .= '<span style="color: #0848AB; font-size: 20px; font-family: Georgia,Arial,Helvetica;">University</span></div>';
   
        $mpdf->SetHTMLHeader ( $headerhtml, 'O', 'false');
        
        $html.= html_writer::tag("h1", "Department Certification Counts");
        $html.=html_writer::tag("div","As of: ".date('m/d/Y H:i:s'),array('style'=>'padding:5px;'));
        
        foreach($records as $record){
            #new header
            if($department!=$record->department){

                #should skip first entry here
                if(isset($table)){
                    $table->data[]=array("Totals",$totalByStatus['Active'],$totalByStatus['Expired'],$totalByStatus['Inactive'],$totalByStatus['Revoked'],$totalByStatus['Suspended']);
                    $html .= html_writer::table($table);
                }

                $departmentTitle = substr($record->prefixid, 0, 4) . ' - ' . $record->department;
                $html .= html_writer::tag("h3",$departmentTitle);
                $department=$record->department;
                $table = new html_table();
                $table->size=array("40%","12%","12%","12%","12%","12%");
                $table->align[1] = 'right';
                $table->align[2] = 'right';
                $table->align[3] = 'right';
                $table->align[4] = 'right';
                $table->align[5] = 'right';
                $table->head[]="Certification";
                $table->head[]="Active";
                $table->head[]="Expired";
                $table->head[]="Inactive";
                $table->head[]="Revoked";
                $table->head[]="Suspended";

                #reset totals for department
                $totalByStatus['Active']=0;
                $totalByStatus['Expired']=0;
                $totalByStatus['Inactive']=0;
                $totalByStatus['Revoked']=0;
                $totalByStatus['Suspended']=0;
            }

            $table->data[] =array($record->fullname,$record->active,$record->expired,$record->inactive,$record->revoked,$record->suspended);
            $totalByStatus['Active']+=$record->active;
            $totalByStatus['Expired']+=$record->expired;
            $totalByStatus['Inactive']+=$record->inactive;
            $totalByStatus['Revoked']+=$record->revoked;
            $totalByStatus['Suspended']+=$record->suspended;

        }

        #show last table data set
        if(isset($table)){
            $table->data[]=array("Totals",$totalByStatus['Active'],$totalByStatus['Expired'],$totalByStatus['Inactive'],$totalByStatus['Revoked'],$totalByStatus['Suspended']);
            $html .= html_writer::table($table);
        }

        $mpdf->WriteHTML($html);
        $mpdf->Output('DeptCertificationCounts-'.date('Y-m-d_is').'.pdf','D');
    }
    elseif($fromform->csvbutton)
    {
        //echo html_writer::tag("h2", "CSV");
        header('Content-Type: text/csv; charset=utf-8');
        $cvsTimestamped = 'masteremployee-' . date('Y-m-d_is') . '.csv';
        header("Content-Disposition: attachment; filename=$cvsTimestamped");
        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        
        $department='';
        foreach($records as $record){
            #new header
            if($department!=$record->department){

                #should skip first entry here
                if(isset($table)){
                    $csv=array("Totals",$totalByStatus['Active'],$totalByStatus['Expired'],$totalByStatus['Inactive'],$totalByStatus['Revoked'],$totalByStatus['Suspended']);
                    echo html_writer::table($table);
                    fputcsv($output,$csv);
                    fputcsv($output,array());
                }

                $departmentTitle = substr($record->prefixid, 0, 4) . ' - ' . $record->department;
                $csv = array($departmentTitle);
                fputcsv($output,$csv);
                $department=$record->department;
                $table = new html_table();
                $csv=array('Certification','Active','Expired','Inactive','Revoked','Suspended');
                fputcsv($output,$csv);

                #reset totals for department
                $totalByStatus['Active']=0;
                $totalByStatus['Expired']=0;
                $totalByStatus['Inactive']=0;
                $totalByStatus['Revoked']=0;
                $totalByStatus['Suspended']=0;
            }

            $csv = array($record->fullname,$record->active,$record->expired,$record->inactive,$record->revoked,$record->suspended);
            fputcsv($output,$csv);
            $totalByStatus['Active']+=$record->active;
            $totalByStatus['Expired']+=$record->expired;
            $totalByStatus['Inactive']+=$record->inactive;
            $totalByStatus['Revoked']+=$record->revoked;
            $totalByStatus['Suspended']+=$record->suspended;

        }

        #show last table data set
        if(isset($table)){
            $csv=array("Totals",$totalByStatus['Active'],$totalByStatus['Expired'],$totalByStatus['Inactive'],$totalByStatus['Revoked'],$totalByStatus['Suspended']);
            fputcsv($output,$csv);
            fputcsv($output,array());
        }
        fclose($output);
    }
    else
    {
        echo html_writer::tag("h2", "Department Certification Counts");


        $department='';
        foreach($records as $record){
            #new header
            if($department!=$record->department){

                #should skip first entry here
                if(isset($table)){
                    $table->data[]=array("Totals",$totalByStatus['Active'],$totalByStatus['Expired'],$totalByStatus['Inactive'],$totalByStatus['Revoked'],$totalByStatus['Suspended']);
                    echo html_writer::table($table);
                }

                $departmentTitle = substr($record->prefixid, 0, 4) . ' - ' . $record->department;
                echo html_writer::tag("h3",$departmentTitle);
                $department=$record->department;
                $table = new html_table();
                $table->size=array("40%","12%","12%","12%","12%","12%");
                $table->align[1] = 'right';
                $table->align[2] = 'right';
                $table->align[3] = 'right';
                $table->align[4] = 'right';
                $table->align[5] = 'right';
                $table->head[]="Certification";
                $table->head[]="Active";
                $table->head[]="Expired";
                $table->head[]="Inactive";
                $table->head[]="Revoked";
                $table->head[]="Suspended";

                #reset totals for department
                $totalByStatus['Active']=0;
                $totalByStatus['Expired']=0;
                $totalByStatus['Inactive']=0;
                $totalByStatus['Revoked']=0;
                $totalByStatus['Suspended']=0;
            }

            $table->data[] =array($record->fullname,$record->active,$record->expired,$record->inactive,$record->revoked,$record->suspended);
            $totalByStatus['Active']+=$record->active;
            $totalByStatus['Expired']+=$record->expired;
            $totalByStatus['Inactive']+=$record->inactive;
            $totalByStatus['Revoked']+=$record->revoked;
            $totalByStatus['Suspended']+=$record->suspended;

        }

        #show last table data set
        if(isset($table)){
            $table->data[]=array("Totals",$totalByStatus['Active'],$totalByStatus['Expired'],$totalByStatus['Inactive'],$totalByStatus['Revoked'],$totalByStatus['Suspended']);
            echo html_writer::table($table);
        }
    }
}
else{
    redirect('view.php');
}

if (!$fromform->pdfbutton && !$fromform->csvbutton)
{
    echo $OUTPUT->footer();
}