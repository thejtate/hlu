<?php
class classification {
    static function getPoints($classification_id) {
        global $DB;
        $params['id'] = $classification_id;
        $record = $DB->get_record_sql("select points from {block_incident_class} where id=:id", $params);
        return $record->points;
   }

    static function getType($classification_id){
        global $DB;
        $params['id'] = $classification_id;
        $record = $DB->get_record_sql("select classification from {block_incident_class} where id=:id", $params);
        return $record->classification;
    }

    static function getDaysToExpire($classification_id) {
        global $DB;
        $params['id'] = $classification_id;
        $record = $DB->get_record_sql("select expire_days from {block_incident_class} where id=:id", $params);
        return $record->expire_days;
   }
}