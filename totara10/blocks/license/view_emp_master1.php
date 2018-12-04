<?php
require_once('includes.php');
$PAGE->set_url('/blocks/license/view_emp_master.php', array());
include_once("../incident/certStatus.class.php");
include_once("../incident/incident.class.php");
include_once("classes/masterEmployee.class.php");
include_once("emp_master_form.php");
require_once($CFG->dirroot.'\MPDF\mpdf.php');

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);
$pager = optional_param('pager', '', PARAM_ALPHA);

$mform = new emp_master_form();

if ($mform->is_cancelled()) {
    unset($_SESSION['fromform']);
    redirect('view.php');
}elseif ($fromform = $mform->get_data() or is_numeric($page)) { 
    if ($fromform) {
        $_SESSION['fromform'] = $fromform;
    }
    else {
        $fromform = $_SESSION['fromform'];
    }
    if(is_object($fromform) and !isset($fromform->cert_status_id)){
        $fromform->cert_status_id=certStatus::ACTIVE;
    }
    $mform->set_data($fromform);
    $wheres=  masterEmployee::setWhereClause($fromform);
    $params=  masterEmployee::setParamsArray($fromform);
     if( $fromform->csvbutton){
        $sql=masterEmployee::setEmployeeSQL($wheres,"substring(g.idnumber,1,4)");
     }
     else{
        $sql=masterEmployee::setEmployeeSQL($wheres);
     }
    $sqlCount=  masterEmployee::setEmployeeSQLCount($wheres);
   
    if( $fromform->csvbutton or ($fromform->pdfbutton  and $fromform->orgid!='')){
            $employeeList = $DB->get_records_sql($sql, $params);       
    }
    else{
        $employeeList = $DB->get_records_sql($sql, $params, $page*$perpage, $perpage);
    }
    $employeeRecordCount = $DB->count_records_sql($sqlCount, $params);       

}

if($fromform->pdfbutton and $fromform->orgid!=''){
     $mpdf = new mPDF('utf-8',  // mode - default 
                               'Letter',    // format - A4, for example, default ''
                                 6,     // font size - default 0
                               'Helvetica',    // default font family
                               23,    // margin_left
                                0,    // margin right
                                3,     // margin top
                                0,    // margin bottom
                                0,     // margin header
                                0,     // margin footer
                                'P');  // L - landscape, P - portrait
     $html=html_writer::tag("h1", "Master Employee List of Licenses");
     $html.=html_writer::tag("div","As of: ".date('m/d/Y H:i:s'),array('style'=>'padding:5px;'));
     $mpdf->WriteHTML($html);
      
     foreach($employeeList as $employee){ 
        $employee=(array)$employee;
        $html=html_writer::start_tag("div",array('class'=>'col-sm-12','style'=>'background-color:#EFEFEF;padding-left:0px;'));
        $html.=html_writer::tag("div", $employee['employee name'],array('class'=>'col-sm-2','style'=>'font-size:110%;width:200px;float:left;padding-left:0px'));
        $html.=html_writer::tag("div",'<strong>Badge#:</strong> '.$employee['badge number'],array('class'=>'col-sm-2','style'=>'font-size:110%;width:100px;float:left;padding-left:0px'));
        $html.=html_writer::tag("div",$employee['department'],array('class'=>'col-sm-8','style'=>'font-size:110%;width:200px;float:left;padding-left:0px'));
        $html.=html_writer::end_tag("div");
        $html.=html_writer::tag("div","",array('style'=>'clear:both;'));

        $certSQL=  masterEmployee::setCertificationSQL($wheres);
        $params['userid']=$employee['user_id'];
        $certifications=$DB->get_records_sql($certSQL,$params);
        $certTable=  masterEmployee::getCertificationTable($certifications);
        $html.= html_writer::table($certTable);
        $mpdf->WriteHTML($html);
     }
    
      $mpdf->Output('masteremployee.pdf','D');    
}
elseif( $fromform->csvbutton){
    
    #set department list ordered
    foreach ($employeeList as $record) {
        $departmentList[$record->department] = $record->department;
    }
    
    #export CSV
    // output headers so that the file is downloaded rather than displayed
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data.csv');
    // create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    $headers=array('Department','Name','EmployeeID','Certification','Date Issued','Recert Date','Expr Date');
    fputcsv($output,$headers);
    
    $department='';    
    foreach($employeeList as $employee){
        $employee=(array)$employee;
        if($department!=$employee['departmentnumber']){
            $csv=array($employee['departmentnumber']);
            fputcsv($output,$csv);
            $department=$employee['departmentnumber'];
        }
        
        $csv=array('',$employee['employee name'],$employee['employee id']);
        fputcsv($output, $csv);
        $certSQL=  masterEmployee::setCertificationSQL($wheres);
        $params['userid']=$employee['user_id'];
        $certifications=$DB->get_records_sql($certSQL,$params);
         foreach ($certifications as $records) {
           $records=(array)$records;
           $fullname=$records['certification'];
           $cert_status=$records['status'];
           $cert_exp_date=date('m/d/Y',$records['expr date']);
           $recert_date=date('m/d/Y',$records['recert date']);
           $date_issued=date('m/d/Y',$records['date issued']);
           $csv=array('','','',$fullname,$date_issued,$recert_date,$cert_exp_date);
           fputcsv($output,$csv);
       }       
    
     }
     fclose($output);
     #save csv
}
else{
    $PAGE->navbar->ignore_active();
    $PAGE->navbar->add('License Management Menu','view.php');
    $PAGE->navbar->add('Master Employee List of Licenses');

    echo $OUTPUT->header();
    
    if($fromform->pdfbutton and $fromform->orgid==''){
        echo html_writer::tag('div','Due to memory restrictions, you will need to select a department to generate a PDF.',array('class'=>'alert alert-danger'));
    }
    
    echo html_writer::tag("h1", "Master Employee List of Licenses");

    $mform->display();

    $baseurl = new moodle_url('/blocks/license/view_emp_master.php');
    echo $OUTPUT->paging_bar($employeeRecordCount, $page, $perpage, $baseurl);

    foreach($employeeList as $employee){
        $employee=(array)$employee;
        echo html_writer::start_tag("div",array('class'=>'col-sm-12','style'=>'padding-left:0px;background-color:#EFEFEF;'));
        echo html_writer::tag("div", $employee['employee name'],array('class'=>'col-sm-2','style'=>'padding-left:0px'));
        echo html_writer::tag("div",'<strong>Badge#:</strong> '.$employee['badge number'],array('class'=>'col-sm-2','style'=>'padding-left:0px'));
        echo html_writer::tag("div",$employee['department'],array('class'=>'col-sm-8','style'=>'padding-left:0px'));
        echo html_writer::end_tag("div");

        $certSQL=  masterEmployee::setCertificationSQL($wheres);
        $params['userid']=$employee['user_id'];
        $certifications=$DB->get_records_sql($certSQL,$params);
        $certTable=  masterEmployee::getCertificationTable($certifications);
        echo html_writer::table($certTable);

    }
    echo $OUTPUT->footer();
}