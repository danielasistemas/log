<?php

class DateFix extends Cron {

	function get_fixed_date($date) {

		$appt_parts = explode(" ", $date);
		$date_parts = explode("-", $appt_parts[0]);
		if ( strlen($date_parts[1]) == 1 ) $date_parts[1] = "0".$date_parts[1];
		if ( strlen($date_parts[2]) == 1 ) $date_parts[2] = "0".$date_parts[2];
		$appt_parts[0] = $date_parts[1]."/".$date_parts[2]."/".$date_parts[0];

		$new_date = join(" ",$appt_parts);
		return $new_date;
	}

	function get_lead_id_by_email($email){
		$email = mysql_real_escape_string($email);

		$query = "SELECT lead_id FROM leads WHERE email = '$email' ORDER BY lead_id DESC LIMIT 1";

		if ($result = mysql_query($query, $this->conn)) {

			if ($row = mysql_fetch_assoc($result)){
				return $row['lead_id'];
			}

		}

		return false;
	}

	//field id 255
	function update_velocify_date($lead_id, $new_date){

		$url = "http://service.leads360.com/clientservice.asmx/ModifyLeadField";

		$new_date = urlencode($new_date);

		$sess = curl_init($url);
		curl_setopt($sess, CURLOPT_HEADER, false);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sess, CURLOPT_POSTFIELDS, "username=webservice@carrington.edu&password=W3bS3rv1c3&leadId=$lead_id&fieldId=255&newValue=$new_date");
		$result = curl_exec($sess);
		curl_close($sess);

		if ( strpos($result, "Lead does not exist") ){
			return false;
		}
		return $result;
	}

}

?>
