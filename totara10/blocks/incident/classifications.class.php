<?php
class classifications
{
    static function getClassificationsList($records, $chooseOne=false){
        if(!$chooseOne){
           $array=array(''=>'All Classifications');
        }
        else{
           $array=array(''=>'Select Classifications');
        }  
        
        foreach($records as $value){ 
            $array[$value->id] = $value->classification;
        }
        return $array;
    }
    
    static function getList(){
        global $DB;
        return $DB->get_records_select("block_incident_class","deleted=0",null,"classification");        
    }
}
