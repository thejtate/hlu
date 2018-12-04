<?php

class classRoomLocation
{
    static function getList()
    {
        global $DB;
        return $DB->get_records_sql("select id,name,building from {facetoface_room} where custom=0", array());
    }
}
