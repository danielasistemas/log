<?php

//var_dump($url_array);

require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.api.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

$api = new Api();
//if (! $api->db_connect() ) die("No DB connection");
$api->params = $url_array;

$allowed_methods = array("leads", "text_message", "info");

if ( ! in_array($url_array[1],$allowed_methods) ) die("Invalid Method");

if ( $url_array[1] == "leads" ){

	$valid_parameters = array("lastname"=>"text", "firstname"=>"text", "phone"=>"int", "email"=>"email");

	if ( isset($valid_parameters[$url_array[2]]) ){

		//phone number search
		if ( $url_array[2] == "phone" ){
			$data = $api->get_leads_by_phone($url_array[3]);
		}
		
		//last name search
		if ( $url_array[2] == "lastname" ){
			$data = $api->get_leads_by_lastname($url_array[3]);
		}

		//First name search
		if ( $url_array[2] == "firstname" ){
			$data = $api->get_leads_by_firstname($url_array[3]);
		}

	}else{
		die("Invalid Parameter");
	}

}

if ( $url_array[1] == "info" ){
	phpinfo();
}

if ( $url_array[1] == "text_message" ){
	echo "hola<br>";

	$_POST['lead_id'] 	= "7777777";  
	$_POST['campus'] 	= "Sacramento";
	$_POST['date'] 		= date("h:i:sa");
	$_POST['sms_phone'] = "(623)219-6892";
	$_POST['phone1'] 	= "(623)219-6893";
	$_POST['phone2'] 	= "(623)219-6894";
	$_POST['phone3'] 	= "(623)219-6895";

	//set db name
	$api->db_name = "cdx";
	


	if ( $db = $api->db_connect() ){

		$api->db = $db;

		if ( count($_POST) ){

			//store the post data for backup and troubleshooting
			//$insert_id = $api->store_sms_data($_POST);
	
			//set the date and time to send the text message
			//normally this is 8 AM the day of the appointment
			//for Mondays, we want to send a Sunday reminder
			$send_schedule = "2015-11-02 08:00:00";

			//create the post
			if ( $xml = $api->create_sms_xml($_POST, $send_schedule) ){
			//Create file here
				$my_file = 'file.txt';
/*$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file); //implicitly creates file
echo $handle;
*/
$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
$data = $xml;
fwrite($handle, $data);

$handle = fopen($my_file, 'r');
$bleh = fread($handle,filesize($my_file));

echo $bleh;
				//echo $xml;				

				//send the post
				/*if ( $result = $api->send_sms_post($xml) ){
					//flag the record as sent
					$api->flag_sms_sent($insert_id);
					$data = array('sms_pk'=>$insert_id);
				}*/
			
			}
		
		}else{
			//$api->send_error("No _POST for Text Message");
			echo "No POST present";
		}

	}

}


if ( $url_array[1] == "zipcode" ){
	$data = $api->get_zip_info();
}

if (! empty($data) ){
	$data = json_encode($data);
	echo $data;
}

?>
