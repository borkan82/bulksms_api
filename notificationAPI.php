<?
/**************************************************************************
*	SMS Notifications - API for NTH requests to send Delivery Notifications
*
*	@author Boris, 2015, updated: 18.08.2016.
**************************************************************************/

header("Access-Control-Allow-Origin: *");
include 'includes/config.php';

$datum 		= Date("Y-m-d");
$timestamp	= Date("Y-m-d H:i:s");
$start		= microtime(true);
$time 		= $_POST['time']; 
$sender 	= $_POST['sender']; 
$provider 	= $_POST['provider']; 
$status 	= $_POST['status']; 
$text 		= $_POST['text']; 
$messageid 	= $_POST['messageid'];
$smsid 		= $_POST['smsid'];
$messageid 	= str_replace("?", "_", $messageid);

$odgovor = $status.", ".$text;

if ($status == 2){

	$tipOrdera = substr($messageid, 0,3);	

	//Update statusa za poruku
	$updatesms = "UPDATE `smsMessages` 
				  JOIN smsMessages_ID ON smsMessages.id = smsMessages_ID.sms_ID
				  SET `status`= 2, `response`='$odgovor' 
				  WHERE smsMessages_ID.sentID = {$smsid}";
	$db->query($updatesms,1);

	//update nove status tabele

	$selectSmsId	= "SELECT sms_ID
						FROM smsMessages_ID
						WHERE smsMessages_ID.sentID = {$smsid}";

	$selectedId	= $db->query($selectSmsId,3);
	$smsIdNum 	= $selectedId["sms_ID"];

	$sqlNewStatus = "INSERT INTO `sms_status` (`smsId`, `status`) VALUES ('$smsIdNum','$status')";
	$db->query($sqlNewStatus,1);

	if ($messageid !== "" && $tipOrdera == "sms"){

		$updatekveri 	= "UPDATE `CampManagement` 
						   SET `delivered`= delivered + 1 
						   WHERE CampaignName LIKE '$messageid'";
		$db->query($updatekveri,1);

	} else if ($messageid !== "" && $tipOrdera == "ord") {

			$korekcija 		= explode("_", $messageid);
			$order 			= substr($messageid, 6);

			//$updateorder 	= "UPDATE `costumer_notifications` 
			//				   LEFT JOIN orders on costumer_notifications.order_id = orders.order_id
			//				   SET costumer_notifications.delivery = 'Yes' 
			//				   WHERE orders.order_no = $order";

	        $updateorder 	= "UPDATE `costumer_notifications` 
							   SET costumer_notifications.delivery = 'Yes' 
							   WHERE costumer_notifications.order_id = $order";

			$db->query($updateorder,1);

	} else if ($messageid !== "" && $tipOrdera == "sub") {

			$korekcija 		= explode("_", $messageid);
			$submit 		= substr($messageid, 7);

			$updatesubmit 	= "UPDATE `sms_notifications` 
							   SET confirm = 2 
							   WHERE submitID = {$submit}";
			$db->query($updatesubmit,1);

				// PROVJERA ZA RAPID TRACK PARAMETAR I TRIGEROVANJE FUNKCIJE AKO PARAMETAR POSTOJI
				$selectQuery 	= "SELECT post_data FROM order_submits 
								   WHERE id = {$submit} 
								   ORDER BY id DESC LIMIT 1";
				$selectsubmit 	= $db->query($selectQuery,3);

				if ($selectsubmit) {

					$postData = json_decode($selectsubmit['post_data']);
					if(isset($postData->rpd_id) && !empty($postData->rpd_id)) {
						
						$rpdInt = hexdec($postData->rpd_id);
						triggerConversion($rpdInt);
					}
				} else {

					$selectQuery2 	= "SELECT rpd_id FROM orders 
									   WHERE submitId = {$submit} 
									   ORDER BY order_id DESC 
									   LIMIT 1";
					$selectsubmit2 	= $db->query($selectQuery2,3);

					if ($selectsubmit2) {

						if ($selectsubmit2['rpd_id'] > 0){
							$rpdInt	= $selectsubmit2['rpd_id'];
							triggerConversion($rpdInt);	
						}
					}
				}

	} else {}
} else if ($status == 1 && $messageid !== "" && $tipOrdera == "sub"){

	$korekcija 		= explode("_", $messageid);
	$submit 		= (int)$korekcija[1];

	$updatesubmit 	= "UPDATE `sms_notifications` 
					   SET confirm = 1 
					   WHERE submitID = {$submit}";
	$db->query($updatesubmit,1);

} else if ($status == 3 && $messageid !== "" && $tipOrdera == "sub"){

	$korekcija 		= explode("_", $messageid);
	$submit 		= (int)$korekcija[1];

	$updatesubmit 	= "UPDATE `sms_notifications` 
					   SET confirm = 3 
					   WHERE submitID = {$submit}";
	$db->query($updatesubmit,1);

} else {

		//Update statusa za poruku
		$updatesms = "UPDATE `smsMessages` 
					  JOIN smsMessages_ID ON smsMessages.id = smsMessages_ID.sms_ID
					  SET `status`= 3, `response`='$odgovor' 
					  WHERE smsMessages_ID.sentID = {$smsid}";

		$db->query($updatesms,1);

	if ($messageid !== "" && $tipOrdera == "sub") {

			$korekcija 		= explode("_", $messageid);
			$submit 		= (int)$korekcija[1];

			$updatesubmit 	= "UPDATE `sms_notifications` 
							   SET confirm = 4 
							   WHERE submitID = {$submit}";
			$db->query($updatesubmit,1);
	}
}
$db->disconnect();
$queryDuration = microtime(true) - $start;

$writeResponse = '  {
					"oprTime":"'.$time.'",
					"ourTime":"'.$timestamp.'",
					"Sender":"'.$sender.'",
					"Provider":"'.$provider.'",
					"Status":"'.$status.'",
					"Text":"'.$text.'",
					"MessageId":"'.$messageid.'",
					"smsID":"'.$smsid.'",
					"scriptTime":"'.$queryDuration.'"
				  },
				  ';

$file 	 = file_put_contents("reports/response/".$datum.".txt", $writeResponse, FILE_APPEND);


function triggerConversion($rpd_id, $value=30){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, '');
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 25);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "utm_medium={$rpd_id}&value={$value}");
	curl_exec ($ch);
	curl_close($ch);
}
?>
