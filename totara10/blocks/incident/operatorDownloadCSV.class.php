<?php
class operatorDownloadCSV {
    static function popColumns($record,$columnsToPop){
             $row=(array)$record;
             for($x=1;$x<=$columnsToPop;$x++){
                array_pop($row);
             }             
             #pop first column id
             $rowReversed = array_reverse($row);
             array_pop($rowReversed);
             $rowReversedBack = array_reverse($rowReversed);
             return $rowReversedBack;
    }
    
    static function formatDate($dateColumn, $includeTime=false){
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

    static function formatStatus($statusColumn){
        switch($statusColumn) {
            case certStatus::ACTIVE:
                return 'Active';
                break;
            case certStatus::INACTIVE: 
                return 'Inactive';
                break;
            case certStatus::SUSPENDED:
                return 'Suspended';
                break;
            case certStatus::REVOKED:
                return 'Revoked';
                break;
            case certStatus::EXPIRED:
                return 'Expired';
                break;
        }
    }
    
    static function generate($data, $columnsToPop){
        // output headers so that the file is downloaded rather than displayed
        $cvsTimestamped = 'masteremployee-' . date('Y-m-d_is') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=$cvsTimestamped.csv");
        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        // loop over the rows, outputting them
        $headers=true;
       
        foreach($data as $record){
            $record = operatorDownloadCSV::popColumns($record, $columnsToPop);
             
            if($headers){
                $keys=array_keys($record);
                foreach($keys as $key=>$value){
                    $keys[$key]=  ucwords($value);
                }
                fputcsv($output, $keys);
                $headers=false;
            }
            foreach($record as $key=>$value){
                if(preg_match("/date/", $key)){
                    $record[$key] = operatorDownloadCSV::formatDate($value);
                }
                if(preg_match("/incident date/", $key)){
                    $record[$key] = operatorDownloadCSV::formatDate($value, true);
                }
                if(preg_match("/suspension ends/", $key)){
                    $record[$key] = operatorDownloadCSV::formatDate($value);
                }
                if(preg_match("/status/", $key)){
                    $record[$key] = operatorDownloadCSV::formatStatus($value);
                }
            }
            fputcsv($output,$record);
        }
        fclose($output);
        die;
    }
}