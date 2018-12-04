<?php
class instructor
{
    static function formatListArray($records,$chooseOne=false)
    {
        if(!$chooseOne){
           $array=array(''=>'All  Instructors');
        }
        else{
           $array=array(''=>'Select Instructor');
        }  
        
        foreach($records as $value){ 
            $array[$value->id]=$value->lastname.', '.$value->firstname;
        }
        return $array;
    }
    
    static function getList()
    {
        global $DB;
        return $DB->get_records_select("block_license_instructor"," deleted=0 ",null,"lastname");        
    }
}
