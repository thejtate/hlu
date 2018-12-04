<?php

include_once("incident.class.php");
include_once("certStatus.class.php");
class block_incident extends block_base
{

    public function init()
    {
        $this->title = get_string('incident', 'block_incident');
    }

    public function instance_delete()
    {
        global $DB;
        $DB->delete_records('block_incident', array('blockid' => $this->instance->id));
    }

    #sets the contents for the block page
    public function get_content()
    {
        global $COURSE, $DB;

        // print_r($DB);die;
        if ($this->content !== null)
        {
            return $this->content;
        }

        $this->content = new stdClass;

        if (!empty($this->config->footer))
        {
            $this->content->footer = $this->config->footer;
        }

        // Display data entered in database from incident form
        $context = context_course::instance($COURSE->id);
        // Check to see if we are in editing mode and that we can manage pages.
        $canmanage = has_capability('block/incident:managepages', $context);

        $canview = has_capability('block/incident:viewpages', $context);

    }

    #hide or show from Add a block drop down list in these areas
    public function applicable_formats()
    {
        return array(
            'admin' => true,
            'site' => true,
            'course' => true);
    }

    public function allowCertificationUpdate($certif_completion_id)
    {
        global $DB;            

        mtrace("Check to allow cert update: $certif_completion_id");

        $params['certif_completion_id'] = $certif_completion_id;
        $sql = "select cert_status_id from {block_incident_certif_info} "
                . "where certif_completion_id = :certif_completion_id";
        $record = $DB->get_record_sql($sql,$params);

        mtrace("Current status of cert $certif_completion_id is {$record->cert_status_id}");

        if($record->cert_status_id == certStatus::EXPIRED) {
            #if license is expired, then allow update
            return true;

        } elseif ($record->cert_status_id == certStatus::INACTIVE) {
            #if license is inactive, then don't update
            return false; 

        } elseif ($record->cert_status_id != certStatus::SUSPENDED) {
            #if active,inactive or revoked don't update anything
            return false;

        } elseif ($record->cert_status_id == certStatus::SUSPENDED){

            #check for suspension end date, if passed then allow else do not allow
            $suspension_date_ends=incident::getSuspensionDateExpires($certif_completion_id);
            $params['certif_completion_id']=$certif_completion_id;
            $sql="select timecompleted from {certif_completion} "
                . "where id=:certif_completion_id";
            $record = $DB->get_record_sql($sql,$params);

            if($record->timecompleted >= $suspension_date_ends){
                #means recompleted after or on end of suspension date so treat as new and reset to active
                mtrace("Allow Update to certification: $certif_completion_id - timecompleted: {$record->timecompleted}, suspension ends: $suspension_date_ends");
                return true;
            } else{
                #not after suspension date so is previous so don't update, leave suspension there.
                mtrace("Do Not Allow Update to certification: $certif_completion_id - timecompleted: {$record->timecompleted}, suspension ends: $suspension_date_ends");
                return false;
            }
        }
        mtrace("New Entry Allow Update to certification: $certif_completion_id - timecompleted: {$record->timecompleted}, suspension ends: $suspension_date_ends");
        return true; #if not there yet, then it's okay to enter it
    }
    
    // Grabs all certifications people have earned - _certif_completion
    // and creates the license - _block_incident_certif_info
    public function getNewCertificationList()
    {
        global $DB;
        mtrace( "Get new certifications last week" );
        $params['startdate']=time()-(60*60*24*21);
        $params['enddate']=time();
        $params['enddate2']=time();
        $sql="select * from {certif_completion} where status=3 and renewalstatus=0 "
                . " and timecompleted between :startdate and :enddate "
                . " and timeexpires > :enddate2 ";
        return $DB->get_recordset_sql($sql,$params);       
    }
    
    public function incidentCertInfoRecordId($certif_completion_id)
    {
        global $DB;            
        mtrace("Obtain existing record id for certif_completion_id: $certif_completion_id");
        $params['certif_completion_id']=$certif_completion_id;
        $sql="select id from {block_incident_certif_info} "
                . "where certif_completion_id=:certif_completion_id";
        $record = $DB->get_record_sql($sql,$params);
        if($record){ 
            return trim($record->id);
        }else{ 
            return false;
        }
    }
    
    public function setExpiredLicenses() {
        global $DB;
        $params['today'] = time();
        $sql = "select id from {certif_completion} where timeexpires < :today";
        $records = $DB->get_records_sql($sql,$params);
        
        foreach($records as $record) {           
            $suspension_date_ends = incident::getSuspensionDateExpires($record->id);
            mtrace("getSuspensionDateExpires() returned $suspension_date_ends certif_completion_id: {$record->id}. \n");
            
            if($suspension_date_ends == 0){
                mtrace("Expire license certif_completion_id: {$record->id}. \n");
                $params2['id'] = $record->id;
                $sql2 = "update {block_incident_certif_info} "
                        . "set cert_status_id = 5, suspension_date_ends = 0 "
                        . "where certif_completion_id=:id ";
                $DB->execute($sql2, $params2);
            } else {
                mtrace("Do not Expire license certif_completion_id: {$record->id}. \n");
            }
        }
    }
    
    public function removeDeletedCertifications() {
        global $DB;
        
        $sql = "delete from {block_incident_certif_info} "
                ." where certif_completion_id not in (select id from {certif_completion})  ";
        $DB->execute($sql,array());
        
        $sql = "delete from {block_incident_certif} "
                ." where certif_completion_id not in (select id from {certif_completion})  ";
        $DB->execute($sql,array());
    }
    
    #if you need the CRON to run something then you need a function like this here
    public function cron()
    { 
        date_default_timezone_set('America/Chicago');
        //get new certifications completed from certif_completion table in the last week
        //add them to the block_incident_certif_info table with status of Active
        //be sure to check for existence of record already and only update status and suspension_date_ends to 0
        include_once("certStatus.class.php");
        global $DB; // Global database object - you can get the global db object here.
        
        mtrace( "----Update expired certifications----" );
        $setExpiredLicenses = $this->setExpiredLicenses();
        
        mtrace("----Remove deleted certifications----");
        $removeDeletedCertifications = $this->removeDeletedCertifications();
        
        mtrace( "----Start Copy New Employee Certification to block_incident_certif_info for use with status and incident process----" );

        $insertCount = 0;
        $failedCount = 0;
        $updateCount = 0;
        
        $newCertificationList = $this->getNewCertificationList();
        
        foreach($newCertificationList as $newCertification){
            $incidentCertifInfoRecordId = $this->incidentCertInfoRecordId($newCertification->id);
            
            mtrace("Setup up insert/update record for block_incident_certif_info table");
            $record = new stdClass();
            
            if($incidentCertifInfoRecordId){
                #update existing
                mtrace("Updating existing record.");
                $record->id = $incidentCertifInfoRecordId;
            } else {
                #new record
                mtrace("Adding new record.");
                $record->certif_completion_id = $newCertification->id;
                $record->user_id = $newCertification->userid;
                
            }

            #set status
            $record->cert_status_id = certStatus::ACTIVE;
            $record->suspension_date_ends = 0;
            
            if(!$incidentCertifInfoRecordId){
                if($DB->insert_record('block_incident_certif_info', $record, true)){
                    mtrace("record inserted");
                    $insertCount++;
                } else {
                    mtrace("record failed to insert");
                    $failedCount++;
                }
            } else {
                if($this->allowCertificationUpdate($newCertification->id)){
                    if($DB->update_record('block_incident_certif_info', $record)){
                        mtrace("record updated");
                        $updateCount++;
                    } else {
                        mtrace("record failed to update");
                        $failedCount++;
                    }   
                }
            }
        }          
        
        mtrace("Results: Inserted: $insertCount, Updated: $updateCount, Failed: $failedCount ");
        mtrace("-----END Copy New Employee Certification to block_incident_certif_info (license table) for use with status and incident process ----");
        return true;
    }

    #allows for multiple instances of block on same page I think.
    public function instance_allow_multiple()
    {
        return false;
    }

    function has_config()
    {
        return true;
    }

    #used to override the title of block since it is set earlier.
    public function specialization()
    {
        if (!empty($this->config->title))
        {
            $this->title = $this->config->title;
        }
        else
        {
            $this->config->title = 'Default title ...';
        }
    }

    public function instance_config_save($data,$nolongerused = false)
    {
        #using the settings.php setup , you can do some validation on fields or cleanup as they do below
        if (get_config('incident', 'Allow_HTML') == '1')
        {
            $data->header = strip_tags($data->header);
            $data->footer = strip_tags($data->footer);
        }

        // And now forward to the default implementation defined in the parent class
        return parent::instance_config_save($data);
    }

}
