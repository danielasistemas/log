<?php

class MSR extends System {

	/*** properties ***/
	public $db; //database connection (should be local db)
	public $db_name;


	/*** public functions ***/
	function db_connect(){
try{
		$db = new PDO("mysql:host=localhost;dbname=".$this->db_name, "dbuser", "5AyehePh");

		if ( $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION) ){
			return $db;
		}

}
catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
		return false;
	}

	function getSearchResults($phone){
		//$phone = (int) $phone;
		$phone = $this->db->quote($phone);
		$output = array();

		$query = 
		"SELECT lead_id,
		firstname,
		lastname,
		email,
		phone1,
		phone2,
		phone3,
		program 
		FROM velocify_leads
		WHERE phone1 = $phone OR phone2 = $phone OR phone3 = $phone;";

		//echo $query;
		if ( $result = $this->db->query($query)){
			while ($row = $result->fetchObject()) {
				//var_dump($row);
				$output[$row->lead_id] = (array) $row;
			}
		}else{
			$_SESSION['sysmsg'] = "ERROR: could not get record $pk";
		}

		return $output;
	}

	function cleanPhone($phone){
		$phone = trim($phone);
		$phone = str_replace('(', '', $phone);
		$phone = str_replace(')', '', $phone);
		$phone = str_replace('-', '', $phone);
		$phone = str_replace('.', '', $phone);
		$phone = str_replace(' ', '', $phone);
		return $phone;
	}

	function getSearchResultsCRM($phone){
		//http://cdx.carrington.edu/api/leads/phone/5203022831
		
		//$url = "http://cdx.carrington.edu/api/leads/phone/$phone";
		$url = "http://cdx.daniela.local/api/leads/phone/$phone";
		//echo "<p>url: $url</p>";

		$sess = curl_init($url);
		curl_setopt($sess, CURLOPT_TIMEOUT, 20);
		curl_setopt($sess, CURLOPT_HEADER, false);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($sess);
		curl_close($sess);

		$result = str_replace("SMTP Error: Could not connect to SMTP host.", "", $result);

		
		//convert json response to array
		//var_dump($result);
		$result = (array) json_decode($result);

		return $result;
	}

	function deleteRecord($lead_id){
		$lead_id = (int) $lead_id;
		
		$query = "DELETE FROM velocify_leads WHERE lead_id = $lead_id";

		if ( $result = $this->db->query($query)){
			return true;
		}

		return false;
	}

	function deleteRecordCRM($lead_id){

		if ( isset($lead_id) && is_numeric($lead_id) ){

			$url = "http://service.leads360.com/clientservice.asmx/RemoveLead";

			$sess = curl_init($url);
			curl_setopt($sess, CURLOPT_HEADER, false);
			curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($sess, CURLOPT_POSTFIELDS, "username=webservice@carrington.edu&password=W3bS3rv1c3&leadId=$lead_id");
			$result = curl_exec($sess);
			curl_close($sess);

			if( $result ){
				return $result;
			}

		}

		return false;
	}

	function add_log($table_name){
		$table_name = (string) $table_name;
		
		$query = "INSERT INTO `activity_log` VALUES 
		('$table_name', 
		'".date('Y-m-d H:i:s')."', 
		'".$this->get_last_log_run()."');";

		if ( $result = $this->db->query($query)){
			return true;
		}

		return false;
	}

	function get_last_log_run($table_name){
		//select last_run from activity_log where table_name='leads';
	}



	function write_record($data){
		//var_dump($data);

		foreach($data as $var=>$value){
			if (!is_array($value)){
				$clean_string = trim($value);
				$clean_string = strip_tags($clean_string);
				//$_SESSION[$this->form_handle]['value'][$var] = $clean_string;
				//$this->form_values[$var] = $clean_string;
				${$var} = $this->db->quote($clean_string); //for ease - create varible of the name value eg $firstname instead of $this->form_values['firstname']
			}
		}

		//force computer name to upper case
		$computer_name = strtoupper($computer_name);

		//update record
		$query = "UPDATE incontact_licenses SET
		user_name = $user_name,
		computer_name = $computer_name,
		station_id = $station_id,
		caller_id = $caller_id,
		location = $location,
		status = $status
		WHERE pk = $record_key";
		//echo "<p>$query</p>";
		if ( $result = $this->db->query($query)){
			return true;
		}

		return false;

	}

} //end class

?>
