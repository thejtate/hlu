<?php
require_once('includes.php');
require_once($CFG->dirroot.'\MPDF\mpdf.php');

#create the pdf of selected licenses
$printLicense=new printLicense();

//original line
//$html=$printLicense->generateLicenses($_POST['userid']);
// chad updated 2/24/16 
//$queue = $_SESSION['print_users'];

$html = $printLicense->generateLicenses($_SESSION['print_users']);

$mpdf = new mPDF('utf-8',  // mode - default 
                'Letter',    // format - A4, for example, default ''
                6,     // font size - default 0
                'Helvetica',    // default font family
                10,    // margin_left
                0,    // margin right
                20,     // margin top
                0,    // margin bottom
                0,     // margin header
                0,     // margin footer
                'P');  // L - landscape, P - portrait

$mpdf->WriteHTML($html);
$mpdf->Output('license.pdf','D');
unset($_SESSION['print_users']);
