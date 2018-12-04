<?php
class certStatus
{
    const ACTIVE = 1;
    const INACTIVE = 2;
    const SUSPENDED = 3;
    const REVOKED = 4;
    const EXPIRED = 5;
    
    static function getCertStatusList()
    {
        global $DB;        
        return  $DB->get_records_sql("select * from {block_incident_cert_status}", array());
    }
}
