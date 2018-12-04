<?php
class downloadCSV {
    static function popColumns($record,$columnsToPop) {
             $row=(array)$record;
             for($x=1;$x<=$columnsToPop;$x++){
                array_pop($row);
             }             
             #pop first column id
             $rowReversed=array_reverse($row);
             array_pop($rowReversed);
             $rowReversedBack=array_reverse($rowReversed);
             return $rowReversedBack;
    }
    
    static function formatDate($dateColumn, $includeTime = false) {
        if($dateColumn != 0){
            if($includeTime){
                return date('m/d/Y h:i:sa', $dateColumn);
            } else {
                return date('m/d/Y', $dateColumn);
            } 
        } else {
            return '';
        }        
    }
    
    static function generate($data,$columnsToPop) {
        // output headers so that the file is downloaded rather than displayed
        $cvsTimestamped = 'masteremployee-' . date('Y-m-d_is') . '.csv';
        header('Content-Type: text/csv; charset=utf-8; http-equiv="Refresh"');
        header("Content-Disposition: attachment; filename=$cvsTimestamped.csv");
        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        // loop over the rows, outputting them
        $headers = true;
       
        foreach($data as $record){
            $record = downloadCSV::popColumns($record, $columnsToPop);
             
            if($headers){
                $keys = array_keys($record);

                foreach($keys as $key=>$value){
                    $keys[$key] = ucwords($value);
                }
                fputcsv($output, $keys);
                $headers = false;
            }
            
            foreach($record as $key=>$value){
                if(preg_match("/date/",$key)){
                    $record[$key] = downloadCSV::formatDate($value);
                }
                if(preg_match("/incident date/",$key)){
                    $record[$key] = downloadCSV::formatDate($value,true);
                }
            }
            fputcsv($output, $record);
        }
        fclose($output);
        die;
    }
}