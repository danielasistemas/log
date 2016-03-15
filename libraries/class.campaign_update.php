<?php

class CampaignUpdate {

	public $conn; //database connection object

	public $notify; //array of people to notify of errors

	public $last_error; //container for last error message

	function db_connect(){

		$conn = mysql_connect('localhost', 'cronuser', '5AyehePh', true);

		if ($conn){
			$result = mysql_select_db("inquiry", $conn);
			if ($result){
				$this->conn = $conn;
				return true;
			}
		}
		return false;
	}

	function display($string){

		if (isset($_SERVER['HTTP_HOST'])){
			$output = "<p>".$string."</p>";
		}else{
			$output = $string."\n";
		}

		echo $output;
	}

	function get_next_unrecognized_lead(){

		$query = "SELECT * FROM unrecognized_campaign
		WHERE updated = 0
		AND update_status != 'DNE'
		ORDER BY lead_id LIMIT 1; ";

		if ($result = mysql_query($query, $this->conn)) {
			if ($row = mysql_fetch_assoc($result)){
				return $row;
			}
		}

		return false;
	}

	function set_update_status($lead_id, $value=1, $status){

		$lead_id = (int) $lead_id;
		$value = (int) $value;
		$query = "UPDATE unrecognized_campaign
		SET updated = $value,
		update_status = '$status'
		WHERE lead_id = $lead_id; ";

		if ($result = mysql_query($query, $this->conn)){
			return true;
		}

		return false;
	}

	function get_lead_source_lookup(){

		$source = array();

		$url = "http://service.leads360.com/clientservice.asmx/GetReportResultsWithoutFilters";

		$sess = curl_init($url);
		curl_setopt($sess, CURLOPT_HEADER, false);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sess, CURLOPT_POSTFIELDS, "username=webservice@carrington.edu&password=W3bS3rv1c3&reportId=195");
		$result = curl_exec($sess);
		curl_close($sess);

		$xml = simplexml_load_string($result);

		foreach ($xml as $data) {
			$data = (array) $data;
			//strip out whitespace
			$data['CampaignAlternateTitle'] = trim($data['CampaignAlternateTitle']);
			if ( strpos($data['CampaignTitle'], 'Oldsource') === false ){
				$source[$data['CampaignId']] = (int) $data['CampaignAlternateTitle'];
			}
		}

		if ( count($source) ) return $source;

		return false;
	}

	function update_velocify_campaign($lead_id, $campaign_id){

		$lead_id = (int) $lead_id;
		$campaign_id = (int) $campaign_id;

		$url = "http://service.leads360.com/clientservice.asmx/ModifyLeadCampaign";

		$sess = curl_init($url);
		curl_setopt($sess, CURLOPT_HEADER, false);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sess, CURLOPT_POSTFIELDS, "username=webservice@carrington.edu&password=W3bS3rv1c3&leadId=$lead_id&campaignId=$campaign_id");
		$result = curl_exec($sess);
		curl_close($sess);

		if ( strpos($result, "No update needed") ){
			return "OK";
		}

		if ( strpos($result, "Success") ){
			return "OK";
		}

	//$o_cu->set_campaign_update_leads_pending_status($lead_id, 0, 'DNE'); //Does Not Exist

		var_dump($result);
		return false;
	}

} //end class
?>
