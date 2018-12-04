<?php
class room
{
    static function formatListArray($records,$chooseOne=false)
    {
        if(!$chooseOne){
           $array=array(''=>'All  Rooms');
        }
        else{
           $array=array(''=>'Select Room');
        }  
        
        foreach($records as $value){ 
            $array[$value->id]=$value->building.', '.$value->name;
        }
        return $array;
    }
    
    static function getList()
    {
        global $DB;
        return $DB->get_records_select("facetoface_room"," custom=0 ",null,"building,name");        
    }
}
