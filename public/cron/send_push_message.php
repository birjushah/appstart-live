<?php
require_once("../cron.php");
//echo phpinfo();die;
$pushmessageDetailMapper = new PushMessage_Model_Mapper_PushMessageDetail();
$date_time = Standard_Functions::getCurrentDateTime ();
$currentDate = strtotime($date_time);
$futureDate = $currentDate+(60);
$futureDate = date("Y-m-d H:i:s", $futureDate);
$pastDate = $currentDate-(60*5);
$pastDate = date("Y-m-d H:i:s", $pastDate);

$pushmessagedetails = $pushmessageDetailMapper->fetchAll("message_date BETWEEN '{$pastDate}' AND '{$futureDate}'");
$message = array();
echo date("Y-m-d H:i:s")."<br/>";
//var_dump($pushmessagedetails);die;
if(is_array($pushmessagedetails)){
    foreach ($pushmessagedetails as $pushmessagedetail) {
    $psuh_message_id = $pushmessagedetail->getPushMessageId();
    $pushmessageMapper = new PushMessage_Model_Mapper_PushMessage();
    $pushmessage = $pushmessageMapper->fetchAll("push_message_id =".$psuh_message_id);
    
	$customer_id = $pushmessage[0]->getCustomerId();
    $customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage();
    $customerlanguage = $customerLanguageMapper->fetchAll("customer_id = '{$customer_id}' AND is_default = 1");
    $customerlanguage = $customerlanguage[0]->getLanguageId();
    $allmessagedetails = $pushmessageDetailMapper->fetchAll("language_id = '{$customerlanguage}' AND push_message_id = '{$psuh_message_id}'");
    $message['title'] = $allmessagedetails[0]->getTitle();
    $clouduserMapper = new Default_Model_Mapper_CloudUser();
    $clouduserDetails = $clouduserMapper->getDbTable()->fetchAll("customer_id =".$customer_id)->toArray();
        if($clouduserDetails){
            $regIds = array();
            $i = 0;
            foreach ($clouduserDetails as $clouduserDetail) {
                $regIds[$i] = $clouduserDetail['reg_id'];
                $i++;
            }
        } 
    }
	
	
    if(is_array($regIds) && isset($message)){
        _sendMessageToAndroid($regIds,$message);
    }
}

function _sendMessageToAndroid($regids,$message){
	    //define("GOOGLE_API_KEY", "AIzaSyD0gczBUI3hOQQ4PAyToRQ2VMcGhRim_3Q");
		define("GOOGLE_API_KEY", "AIzaSyDUQaySHP0BPxoPIPCWpIK0Z48QqKNGsDQ");
		
        $url = 'https://android.googleapis.com/gcm/send';
		$fields = array(
            'registration_ids' => $regids,
            'data' => $message,
        );
         $headers = array(
            'Authorization: key=' . GOOGLE_API_KEY,
            'Content-Type: application/json'
        );
        // Open connection
        $ch = curl_init();
 
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        // Execute post
        $result = curl_exec($ch);
		var_dump($result);
        //return true;
        if ($result === FALSE) {
			return $result;
            die('Curl failed: ' . curl_error($ch));
        }else{
			return $result;
        }
        curl_close($ch);
        // Close connection
        //echo $result;	
}