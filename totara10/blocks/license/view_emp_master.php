<?php
require_once('includes.php');
$PAGE->set_url('/blocks/license/view_emp_master.php', array());
$PAGE->set_title('Master Employee List of Licenses');
include_once("../incident/certStatus.class.php");
include_once("../incident/incident.class.php");
include_once("classes/masterEmployee.class.php");
include_once("emp_master_form.php");
require_once($CFG->dirroot.'\MPDF\mpdf.php');

$page = optional_param('page', 0, PARAM_INT);
$perpage = 15; 
 
// Updated to set Suspended licenses past their suspension date to be set to Expired
// Removed this since new suspension->expire script will handle it
// $setSuspendedLicensesToExpired = incident::setSuspendedLicensesToExpired();

$mform = new emp_master_form();

if ($mform->is_cancelled()) {
    unset($_SESSION['fromform']);
    redirect('view.php');

} elseif ($fromform = $mform->get_data() or is_numeric($page)) { 
    if ($fromform) {
        $_SESSION['fromform'] = $fromform;
    } else {
        $fromform = $_SESSION['fromform'];
    }
    if(is_object($fromform) and !isset($fromform->cert_status_id)){
        $fromform->cert_status_id = certStatus::ACTIVE;
    }

    $mform->set_data($fromform);
    $wheres = masterEmployee::setWhereClause($fromform);
    $params = masterEmployee::setParamsArray($fromform);

    if( $fromform->csvbutton){
        $sql = masterEmployee::setEmployeeSQL($wheres,"substring(g.idnumber,1,4)");
    } else {
        $sql = masterEmployee::setEmployeeSQL($wheres);
    }
    $sqlCount = masterEmployee::setEmployeeSQLCount($wheres);
   
    if( $fromform->csvbutton || $fromform->pdfbutton){
        // pdf and csv sql 
        $employeeList = $DB->get_records_sql($sql, $params);     
    } elseif($fromform->orgid == '' && $fromform->certifid == '') {
        $employeeList = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
    } else {
        // html page sql 
        $employeeList = $DB->get_records_sql($sql, $params);
    }
    $employeeRecordCount = $DB->count_records_sql($sqlCount, $params);       
}

if($fromform->pdfbutton /* and $fromform->orgid!='' */) {

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

     $html = html_writer::tag("h1", "Master Employee List of Licenses");
     $html .= html_writer::tag("div","As of: ".date('m/d/Y H:i:s'),array('style'=>'padding:5px;'));
     $mpdf->WriteHTML($html);
      
     foreach($employeeList as $employee){ 
        $employee=(array)$employee;
        $combinedDepartment = $employee['departmentnumber'] . ' - ' . $employee['department'];
        $html=html_writer::start_tag("div",array('class'=>'col-sm-12 emp-master-headers','style'=>''));
        $html.=html_writer::tag("div", '<strong>'.$employee['employee name'].'</strong>',array('class'=>'col-sm-2 emp-master-name','style'=>'font-size:110%;float:left;padding-left:0px;'));
        $html.=html_writer::tag("div",'<strong>Badge#:</strong> '.$employee['badge number'],array('class'=>'col-sm-2 emp-master-badge','style'=>'font-size:110%;float:left;padding-left:0px'));
        $html.=html_writer::tag("div",$combinedDepartment,array('class'=>'col-sm-8 emp-master-dept','style'=>'font-size:110%;float:left;padding-left:0px'));
        $html.=html_writer::end_tag("div");
        $html.=html_writer::tag("div","",array('style'=>'clear:both;'));

        $certSQL=  masterEmployee::setCertificationSQL($wheres);
        $params['userid'] = $employee['user_id'];
        $certifications = $DB->get_records_sql($certSQL,$params);
        $certTable = masterEmployee::getCertificationTable($certifications);
        $html .= html_writer::table($certTable);
        $mpdf->WriteHTML($html);
     }
    
      $pdfTimestamped = 'masteremployee-' . date('Y-m-d_is') . '.pdf';
      $mpdf->Output($pdfTimestamped,'D');    
      unset($_SESSION['fromform']);

} elseif($fromform->csvbutton) {

    #set department list ordered
    foreach ($employeeList as $record) {
        $departmentList[$record->department] = $record->department;
    }
    
    #export CSV
    // output headers so that the file is downloaded rather than displayed
    $cvsTimestamped = 'masteremployee-' . date('Y-m-d_is') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=$cvsTimestamped");
    // create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    $headers=array('Department','Name','Badge Number','Certification','Date Issued','Recert Date','Expr Date');
    fputcsv($output,$headers);
    
    $department='';    
    
    foreach($employeeList as $employee){
        $employee=(array)$employee;

        if($department!=$employee['departmentnumber']){
            $csv=array($employee['departmentnumber'] . ' - ' . $employee['department']);
            fputcsv($output,$csv);
            $department=$employee['departmentnumber'];
        }

        $csv=array('',$employee['employee name'],$employee['badge number']);
        fputcsv($output, $csv);
        $certSQL=  masterEmployee::setCertificationSQL($wheres);
        $params['userid']=$employee['user_id'];
        $certifications=$DB->get_records_sql($certSQL,$params);

        foreach ($certifications as $records) {
            $records=(array)$records;
            $fullname=$records['certification'];
            $cert_status=$records['cert_status_id'];
            $cert_exp_date=date('m/d/Y',$records['expr date']);
            $recert_date=date('m/d/Y',$records['recert date']);
            $date_issued=date('m/d/Y',$records['date issued']);
            $csv=array('','','',$fullname,$date_issued,$recert_date,$cert_exp_date);
            fputcsv($output,$csv);
       }       
    
     }
     fclose($output);
     unset($_SESSION['fromform']);
     #save csv
} else {
    $PAGE->navbar->ignore_active();
    $PAGE->navbar->add('License Management Menu','view.php');
    $PAGE->navbar->add('Master Employee List of Licenses');
    echo $OUTPUT->header();
    
    // if($fromform->pdfbutton and $fromform->orgid==''){
    //     echo html_writer::tag('div','Due to memory restrictions, you will need to select a department to generate a PDF.',array('class'=>'alert alert-danger'));
    // }

    echo html_writer::tag("h1", "Master Employee List of Licenses");
    $mform->display();
    
    if($fromform->orgid == '' && $fromform->certifid == ''){
        echo $warningMessage = "<div class='alert alert-warning'>Due to memory restrictions, you will need to select a department and/or certification to generate a PDF.</div>";
    }

    $baseurl = new moodle_url('/blocks/license/view_emp_master.php');

    if($fromform->orgid == '' && $fromform->certifid == ''){
        echo $OUTPUT->paging_bar($employeeRecordCount, $page, $perpage, $baseurl);
    } 

    foreach($employeeList as $employee){
        $employee = (array)$employee;
        $combinedDepartment = $employee['departmentnumber'] . ' - ' . $employee['department'];
        
        echo html_writer::start_tag("div",array('class'=>'col-sm-12 emp-master-headers','style'=>''));
        echo html_writer::tag("div", $employee['employee name'],array('class'=>'col-sm-2 emp-master-name','style'=>'padding-left:0px'));
        echo html_writer::tag("div",'<strong>Badge#:</strong> '.$employee['badge number'],array('class'=>'col-sm-2 emp-master-badge','style'=>'padding-left:0px'));
        echo html_writer::tag("div",$combinedDepartment,array('class'=>'col-sm-8 emp-master-dept','style'=>'padding-left:0px'));
        echo html_writer::end_tag("div");

        $certSQL = masterEmployee::setCertificationSQL($wheres);
        $params['userid'] = $employee['user_id'];
        $certifications = $DB->get_records_sql($certSQL,$params);
        $certTable = masterEmployee::getCertificationTable($certifications);
        echo html_writer::table($certTable);
    }
    ?>
    
    <script type="text/javascript">
        var pdfButton = $('#id_pdfbutton');
        var pdfMessage = $('.alert-warning');
        var formSessionOrgid = "<?php echo $fromform->orgid ?>";
        var formSessionCertifid = "<?php echo $fromform->certifid?>";

        if(formSessionOrgid != '' || formSessionCertifid != ''){
            pdfButton.attr('enabled','enabled');
        } else {
            pdfButton.attr('disabled','disabled');
        }

        // $('#id_orgid').chosen().change( function(){
        //     if($(this).val() != 0){
        //         pdfButton.removeAttr('disabled');
        //         pdfMessage.css({'display': 'none'});
        //     } else {
        //         pdfButton.attr('disabled','disabled');
        //         pdfMessage.css({'display': 'block'});
        //     }
        // });
        // $('#id_certifid').chosen().change( function(){
        //     if($(this).val() != 0){
        //         pdfButton.removeAttr('disabled');
        //         pdfMessage.css({'display': 'none'});
        //     } else {
        //         pdfButton.attr('disabled','disabled');
        //         pdfMessage.css({'display': 'block'});
        //     }
        // });
    </script>

    <?php
    echo $OUTPUT->footer();
}