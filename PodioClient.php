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
 * Gets All Podio People app items ( Phone numbers ) .
 * @param   string $app_id                App id
 * @param   string $app_token             App access token
 * @param   array  $options               Podio Item filter options
 * @return  ItemsArray
 */
    
private function getAllPeopleItems($app_id,$app_token,$options){
    $this->Authenticate($app_id,$app_token);
    $people_items = PodioItem::filter($app_id,$options);
    $people_all_phones="";
    $people_data=[];
    foreach( $people_items as $itm)
     {
        $values = array_map('array_pop',$itm->fields["phone-number"]->values);
        $imploded = implode(',', $values);
        $tmp=array("name"=>$itm->fields["name-2"]->values,"phones"=>$imploded );
        array_push($people_data,$tmp );
        $people_all_phones=$people_all_phones.$imploded.',';
        }
    
    return $people_data;
    
}
    
   
/**
 * Gets All Podio Account app items ( Phone numbers ) .
 * @param   string $app_id                App id
 * @param   string $app_token             App access token
 * @param   array  $options               Podio Item filter options
 * @return  Items in array
 */
    
private function getAllAccountsItems($app_id,$app_token,$options){
    $this->Authenticate($app_id,$app_token);
    $accounts_items = PodioItem::filter($app_id,$options);
    $accounts_all_phones="";
    $accounts_data=[];
    foreach( $accounts_items as $acc_itm){

        if ($acc_itm->fields["phone"]->values){
                $values = array_map('array_pop',$acc_itm->fields["phone"]->values);
                $imploded = implode(',', $values);
                $tmp=array("name"=>$acc_itm->fields["company-name"]->values,"phones"=>$imploded );
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

private function SearchCaller($PhoneNumber,$Accounts_app_id,$Accounts_app_token,$People_app_id,$People_app_token,$options){
        
         $accounts_data=$this->getAllAccountsItems($Accounts_app_id,$Accounts_app_token,$options);
         $people_data=$this->getAllPeopleItems($People_app_id,$People_app_token,$options);
         $found=false;
         $Name="";
         foreach ($people_data as $people ) {

            if((strlen($PhoneNumber)<=strlen($people["phones"])) and strlen($PhoneNumber)>7) {
                if(strstr( $people["phones"],$PhoneNumber )){
                    $Name= $people["name"];
                    $found=true;
                    break;
                   } 

         }else
            if((strlen($PhoneNumber)>=strlen($people["phones"])) and strlen($PhoneNumber)<14) 
                if(strstr( $PhoneNumber,$people["phones"] )) {
                    $Name=$people["name"];
                    $found=true;
                    break;
                } 
        }
        
        if ($found==false){

            foreach ($accounts_data as $accounts ) {
                if((strlen($PhoneNumber)<=strlen($accounts["phones"])) and strlen($PhoneNumber)>7) {
                    if(strstr( $accounts["phones"],$PhoneNumber )){
                        $Name= $accounts["name"];
                        $found=true;
                        break;

                    } 

                }else
                    if((strlen($PhoneNumber)>=strlen($accounts["phones"])) and strlen($PhoneNumber)<14) 
                        if(strstr( $PhoneNumber,$accounts["phones"] )) {
                        $Name=$accounts["name"];
                        $found=true;
                        break;                    
                    } 
                } 
            }
       if (!$found) return false;
        else return $Name;
    
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
    
public function MissedCall($PhoneNumber ,$Accounts_app_id,$Accounts_app_token,$People_app_id,$People_app_token,$options){
    
    $Result=$this->SearchCaller($PhoneNumber,$Accounts_app_id,$Accounts_app_token,$People_app_id,$People_app_token,$options);
    if($Result) return json_encode($Result);
    else {

        $calls_app_id="19134285";
        $calls_app_token="f3bdcae3499343818c305ca48235ee1a";
        PodioItem::create( $calls_app_id,  array(  'fields' => array('phone' => array("type"=>"main","value"=>$PhoneNumber) ,  'date'=>array("start"=>date("Y-m-d H:i:s")  ) )));
        return "Missed Call Saved !";
    }
}
    
}

?>
