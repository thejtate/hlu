<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class dept_cert_count_form extends moodleform {
    
    //Add elements to form
    public function definition() {
        $mform = $this->_form; // Don't forget the underscore! 
        $mform->addElement('select', 'orgid', "Department",department::getDepartmentList());

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submit', 'Search', 'class="form-submit"');
        $buttonarray[] = &$mform->createElement('submit', 'pdfbutton', 'Generate PDF');
        $buttonarray[] = &$mform->createElement('submit', 'csvbutton', 'Generate CSV');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

    }
}
