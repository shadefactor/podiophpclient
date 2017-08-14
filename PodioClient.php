<?php

/**
 * Basic Class for dealing with PODIO apps/items
 * Author : ABDRABAH Rafik
 * Wizz it 2017
 */

require_once 'PodioAPI.php';

class PODIOManeger
{
    
private $client_id;
private $client_secret;

    
public function __construct($client_id,$client_secret) {
    Podio::set_debug(true);
    $this->client_id = $client_id;
    $this->client_secret = $client_secret;
  
  }
    
    
/**
 * Authenticate handler for PODIO app authentication flow.
 * @param  string $app_token             App access token
 * @param  string $app_id                App id
 * @return boolean
 */
    
private function Authenticate($app_id,$app_token){
    
Podio::setup($this->client_id, $this->client_secret);
Podio::authenticate_with_app($app_id, $app_token);
    
}
  
    
   
/**
 * Gets All Podio  app items ( Phone numbers and names ) .
 * @param   string    $NameItem_externalID   Name item's external id
 * @param   string    $PhoneItem_externalID  Phone number item's external id
 * @param   string    $app_id                App id
 * @param   string    $app_token             App access token
 * @param   array     $options               Podio Item filter options
 * @return  Items in array
 */
    
private function getAllItems($NameItem_externalID,$PhoneItem_externalID,$app_id,$app_token,$options){
    $this->Authenticate($app_id,$app_token);
    $accounts_items = PodioItem::filter($app_id,$options);
    $accounts_all_phones="";
    $accounts_data=[];
    foreach( $accounts_items as $acc_itm){

        if ($acc_itm->fields[$PhoneItem_externalID]->values){
                $values = array_map('array_pop',$acc_itm->fields[$PhoneItem_externalID]->values);
                $imploded = implode(',', $values);
      
                $tmp=array("name"=>$acc_itm->fields[$NameItem_externalID]->values,"phones"=>$imploded,"testphone"=>$acc_itm->fields[$PhoneItem_externalID]->values,"id"=>$acc_itm->item_id);
                array_push($accounts_data,$tmp );
                $accounts_all_phones=$accounts_all_phones.$imploded.',';
        
          }
     }   
    return $accounts_data;
}
    
    
 
/**
 * Searchs for Caller's name providing his phone number .
 * @param   string $PhoneNumber           Phone Number
 * @param   string $Accounts_app_id       Companies App id
 * @param   string $Accounts_app_token    Companies App access token
 * @param   string $People_app_id         People App id
 * @param   string $People_app_token      People App access token
 * @param   array  $options               Podio Item filter options
 * @return  Callers name if found , false if not
 */

private function SearchCaller($PhoneNumber,$A_NameItem_externalID,$A_PhoneItem_externalID,$P_NameItem_externalID,$P_PhoneItem_externalID,$Accounts_app_id,$Accounts_app_token,$People_app_id,$People_app_token,$options){
        
        
         $people_data=$this->getAllItems($P_NameItem_externalID,$P_PhoneItem_externalID,$People_app_id,$People_app_token,$options);
         $found=false;
         $Name="";
        $k="";
         foreach ($people_data as $people ) {

            if((strlen($PhoneNumber)<=strlen($people["phones"])) and strlen($PhoneNumber)>7) {
                if(strstr( $people["phones"],$PhoneNumber )){
                    foreach($people["testphone"] as $ph) {
                        if ($ph["value"]==$PhoneNumber) $k=$ph['type'];
                    } 
                    $result=array("name"=>$people["name"]." (".$k.")","id"=>$people['id']);
                    $Name= $people["name"]." (".$k.")";
                    $found=true;
                    break;
                   } 

         }else
            if((strlen($PhoneNumber)>=strlen($people["phones"])) and strlen($PhoneNumber)<14) 
                if(strstr( $PhoneNumber,$people["phones"] )) {
                     foreach($people["testphone"] as $ph) {
                        if ($ph["value"]==$PhoneNumber) $k=$ph['type'];
                    } 
                    $result=array("name"=>$people["name"]." (".$k.")","id"=>$people['id']);
                    $Name=$people["name"]." (".$k.")";
                    $found=true;
                    break;
                } 
        }
        
        if ($found==false){
            $accounts_data=$this->getAllItems($A_NameItem_externalID,$A_PhoneItem_externalID,$Accounts_app_id,$Accounts_app_token,$options);
            
            foreach ($accounts_data as $accounts ) {
                if((strlen($PhoneNumber)<=strlen($accounts["phones"])) and strlen($PhoneNumber)>7) {
                    if(strstr( $accounts["phones"],$PhoneNumber )){
                         foreach($accounts["testphone"] as $ph) {
                        if ($ph["value"]==$PhoneNumber) $k=$ph['type'];
                    }
                         $result=array("name"=>$accounts["name"]." (".$k.")","id"=>$accounts['id']);
                        $Name= $accounts["name"]." (".$k.")";
                        $found=true;
                        break;

                    } 

                }else
                    if((strlen($PhoneNumber)>=strlen($accounts["phones"])) and strlen($PhoneNumber)<14) 
                        if(strstr( $PhoneNumber,$accounts["phones"] )) {
                             foreach($accounts["testphone"] as $ph) {
                        if ($ph["value"]==$PhoneNumber) $k=$ph['type'];
                    }
                        $result=array("name"=>$accounts["name"]." (".$k.")","id"=>$accounts['id']);
                        
                        $found=true;
                        break;                    
                    } 
                } 
            }
       if (!$found) return false;
        else return $result;
    
  }

     
/**
 * Saves a missed call in PODIO CRM if the phone number doesn't exist , and returns a Json encodage of the name if it's found .
 * @param   string $PhoneNumber           Phone Number
 * @param   string $Accounts_app_id       Companies App id
 * @param   string $Accounts_app_token    Companies App access token
 * @param   string $People_app_id         People App id
 * @param   string $People_app_token      People App access token
 * @param   array  $options               Podio Item filter options
 * @return  Callers name if found , false if not
 */
    
public function Call($Use_NumVerifyAPI,$PhoneNumber ,$A_NameItem_externalID,$A_PhoneItem_externalID,$P_NameItem_externalID,$P_PhoneItem_externalID,$calls_app_id,$calls_app_token,$Accounts_app_id,$Accounts_app_token,$People_app_id,$People_app_token,$options){
        $PhoneNumber = $this->CleanPhoneNumber($PhoneNumber);
    
    $Result=$this->SearchCaller($PhoneNumber,$A_NameItem_externalID,$A_PhoneItem_externalID,$P_NameItem_externalID,$P_PhoneItem_externalID,$Accounts_app_id,$Accounts_app_token,$People_app_id,$People_app_token,$options);
    if($Result) {
        $calls_items = PodioItem::filter($calls_app_id,$options);
        foreach( $calls_items as $itm){
            if($itm->fields["phone"]->values[0]["value"]==$PhoneNumber)
            
    PodioItem::update( $itm->item_id,array(  'fields' => array('relationship' => $Result["id"] )), $options  );
  
    }return json_encode($Result["name"]);}
    else {

        if($Use_NumVerifyAPI){
            
            require_once 'numverifyAPI.php';
            
           (VerifyPhone("+".$PhoneNumber)["line_type"]=="landline")? $line_type="work" : $line_type="mobile";
             PodioItem::create( $calls_app_id,  array(  'fields' => array('phone' => array("type"=>$line_type,"value"=>$PhoneNumber) ,  'date'=>array("start"=>date("Y-m-d H:i:s")  ) )));
        return " Call Saved !";
            
        }else{
             PodioItem::create( $calls_app_id,  array(  'fields' => array('phone' => array("type"=>"other","value"=>$PhoneNumber) ,  'date'=>array("start"=>date("Y-m-d H:i:s")  ) )));
        return "Call Saved !";
        }
       
    }
}
    
      
/**
 * Cleans the phone nuber format .
 * @param   string $PhoneNumber           Phone Number
 * @return  String  clean phone number  !
 */
    
    
private function CleanPhoneNumber($PhoneNumber){

        

            if ($PhoneNumber[0]=='+' ){
                $data= substr($PhoneNumber, 1, strlen($PhoneNumber));

            }   
            else
            if(substr($PhoneNumber, 0, 2)=="00"){

                    $PhoneNumber=substr($PhoneNumber, 2, strlen($PhoneNumber));
                }   
       return $PhoneNumber;
    
    }
 
}

?>
