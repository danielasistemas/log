<?php

class Clean extends Cron {

	public $conn; //database connection object

	function time_elapsed($start_time, $process=""){
		$current_time = time();
		$seconds_elapsed = $current_time - $start_time;
		$this->display("$process elapsed time: $seconds_elapsed s");
	}

	function get_clean_lead_ids($from, $to){

		$leads = array();

		$url = "http://service.leads360.com/clientservice.asmx/GetLeadIds";
		$post_fields = "username=webservice@carrington.edu&password=W3bS3rv1c3&from=$from&to=$to";
		$sess = curl_init($url);
		curl_setopt($sess, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($sess, CURLOPT_TIMEOUT, 60);
		curl_setopt($sess, CURLOPT_HEADER, false);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sess, CURLOPT_POSTFIELDS, $post_fields);
		$result = curl_exec($sess);
		curl_close($sess);
		if ( $xml = simplexml_load_string($result) ){
			foreach ($xml->Lead as $lead) {
				$leads[] = (string) $lead['Id'];
			}
			return $leads;
		}
		$this->send_error("get_pending_lead_ids url used: ".$url."?".$post_fields);
		$this->send_error("get_pending_lead_ids result: ".$result);
		return false;
	}

	function update_leads_clean_pending_table($lead_id, $added){

		$lead_id = (int) $lead_id;
		$query = "INSERT INTO leads_clean_pending
		(lead_id, imported, added, lead_status)
		VALUES
		($lead_id, 0, '$added', '')
		ON DUPLICATE KEY UPDATE imported=imported;";

		if ($result = mysql_query($query, $this->conn)){
			return true;
		}

		$this->display($query);
		return false;
	}

	function get_next_clean_pending_lead_id(){

		$query = "SELECT lead_id FROM leads_clean_pending
		WHERE imported = 0
		AND lead_status != 'DNE'
		AND lead_status != 'XML'
		ORDER BY added, lead_id LIMIT 1; ";

		if ($result = mysql_query($query, $this->conn)) {
			if ($row = mysql_fetch_assoc($result)){
				return $row['lead_id'];
			}
		}

		return false;
	}

	function set_leads_clean_pending_status($lead_id, $value=1, $status){

		$lead_id = (int) $lead_id;
		$value = (int) $value;
		$query = "UPDATE leads_clean_pending
		SET imported = $value,
		lead_status = '$status'
		WHERE lead_id = $lead_id; ";

		if ($result = mysql_query($query, $this->conn)){
			return true;
		}

		return false;
	}

	function update_lead_clean($lead){

		//escape strings for db insertion
		foreach( $lead as $key=>$value ){
			${$key} = mysql_escape_string($value);
		}

		$query = "INSERT INTO leads_clean
		(lead_id, lead_provider_id, firstname, middlename, lastname, email,
		address, city, state, zip, phone1, phone2, phone3,
		grad_date, campaign_id, lead_source_id, adv_source, adv_campaign, adv_content,
		program, campus, student_num, spark_id, targus_validation,
		targus_score, arrival_date, status, action, update_date)
		VALUES
		($lead_id, '$lead_provider_id', '$firstname', '$middlename', '$lastname', '$email',
		'$address', '$city', '$state', '$zip', '$phone1', '$phone2', '$phone3',
		'$grad_date', $campaign_id, '$lead_source_id', '$adv_source', '$adv_campaign', '$adv_content',
		'$program', '$campus', '$student_num', '$spark_id', '$targus_validation',
		'$targus_score', '$arrival_date', '$status', '$action', NOW())
		ON DUPLICATE KEY UPDATE
		lead_provider_id = '$lead_provider_id',
		firstname = '$firstname',
		middlename='$middlename',
		lastname='$lastname',
		email='$email',
		address='$address',
		city='$city',
		state='$state',
		zip='$zip',
		phone1='$phone1',
		phone2='$phone2',
		phone3='$phone3',
		grad_date='$grad_date',
		campaign_id=$campaign_id,
		lead_source_id='$lead_source_id',
		adv_source='$adv_source',
		adv_campaign='$adv_campaign',
		adv_content='$adv_content',
		program='$program',
		campus='$campus',
		student_num='$student_num',
		spark_id='$spark_id',
		targus_validation='$targus_validation',
		targus_score='$targus_score',
		arrival_date='$arrival_date',
		status='$status',
		action='$action',
		update_date=NOW(); ";

		if ( $result = mysql_query($query, $this->conn) ){
			return true;
		}
		return false;
	}

} //end class
?>
