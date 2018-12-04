<?php
#should be in global php include area
include_once("Smarty/libs/Smarty.class.php");

class printLicense extends Smarty
{
    
    public function getLicenseHeader()
    {
        $path=$_SERVER['REQUEST_URI'];
        $lastPosition=strrpos($path,'/');
        $path='http://'.$_SERVER['HTTP_HOST'].substr($path,0,$lastPosition);
        $this->assign('urlPath',$path);
        return $this->fetch("printLicenseHeader.tpl");
    }
    
    public function getLicenseFooter()
    {
        return $this->fetch("printLicenseFooter.tpl");
    }
   
    public function getLicenseBack()
    {
        return $this->fetch("printBackLicenseBody.tpl");
    }
    
    public function getLicenseBody($employeeName,$certList)
    {
        $this->assign('employeeName',$employeeName);
       $this->assign('certList',(array)$certList);
        return $this->fetch("printLicenseBody.tpl");
    }
    
    public function formatDates($certList)
    {
        foreach($certList as $key=>$value){
            $certList[$key]->firstcompleted=date('m/d/Y',$value->firstcompleted);
            $certList[$key]->lastcompleted=date('m/d/Y',$value->lastcompleted);            
        }
        return $certList;
    }
    
    public function generateLicenses($userids)
    {
        global $DB;

        $this->setCompileDir('templates_c/');
         
        $html=$this->getLicenseHeader();
        $pageCount=0;
        $recordsProcessed=0;
        
        $totalRecords=count($userids);
        foreach($userids as $userid=>$junk){
            $user = $DB->get_record('user', array('id'=>$userid));
            $employeeName=$user->firstname.' '.$user->lastname;
         
            $certList=license::getAllActiveLicensesForUser($userid);
          
            $certListFormatted=$this->formatDates($certList);
            $html.=$this->getLicenseBody($employeeName, $certListFormatted);
            
            $pageCount++;
            $recordsProcessed++;
            
            if($pageCount==3){
                 $html.='<div style="height:100px;clear:both;">&nbsp;</div>';
            }
            
            if($pageCount==6){
                $html.='<div style="page-break-after: always;">&nbsp;</div>';
                for($x=1;$x<=6;$x++){
                    $html.=$this->getLicenseBack();
                     if($x==3){
                        $html.='<div style="height:100px;clear:both;">&nbsp;</div>';
                     }
                }
                $pageCount=0;
                if($recordsProcessed!=$totalRecords){
                  $html.='<div style="page-break-after: always;">&nbsp;</div>';
                }
            }            
        }
        
        if($pageCount>0 and $pageCount<6){
            $html.='<div style="page-break-after: always;">&nbsp;</div>';
             for($x=1;$x<=6;$x++){
                  $html.=$this->getLicenseBack();
                     if($x==3){
                        $html.='<div style="height:100px;clear:both;">&nbsp;</div>';
                 }
                }
             
        }
        
        $html.=$this->getLicenseFooter();
        return $html;
    }
}
