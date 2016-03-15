<?php

class Fes extends Cron {

	public $current_time; //used to set time that fes export was made

	function get_export_list(){

		$output = array();

		$query = "SELECT lead_id FROM leads_pending
		WHERE sent_fes = 0
		LIMIT 60000; ";

		if ($result = mysql_query($query, $this->conn)){
			while($row = mysql_fetch_assoc($result)){
				$output[] = $row['lead_id'];
			}
		}else{
			$this->display("Query failed: $query" . mysql_error($this->conn));
		}

		return $output;
	}

	function get_capped_programs(){
		$output = array("Health Studies"); //treat HS as capped so it doesn't get sent
		$program_data = $this->get_api_result("/program/");
		foreach ($program_data as $program){
			if ( $program->program_cap == 1 ){
				$output[] = $program->program_name;
			}
		}
		return $output;
	}

	function get_gold_export_data($capped_programs){

		$output = array();

		set_time_limit(90);

		$query = "SELECT lead_id, firstname, middlename, lastname, email,
		address, city, state, zip,
		program, campus, arrival_date, appointment
		FROM leads
		WHERE fes_gold = 0
		AND DATE(update_date) = '$this->db_date'
		AND appointment != ''
		AND STR_TO_DATE(appointment, '%m/%d/%Y') > '$this->db_date'
		AND address != ''
		AND city != ''
		AND state != ''
		AND zip != ''; ";

		if ( $result = mysql_query($query, $this->conn) ){
			while ( $row = mysql_fetch_assoc($result) ){
				if (! in_array($row['program'], $capped_programs) ) $output[] = $row;
			}
		}else{
			$this->display( "Query failed: $query" . mysql_error($this->conn) );
			return false;
		}

		return $output;
	}

	function is_duplicate($lead_id){

		$lead_id = (int) $lead_id;
		$query = "SELECT * FROM leads_duplicates
		WHERE child_id = $lead_id
		LIMIT 1; ";

		if ($result = mysql_query($query, $this->conn)){
			if ( mysql_num_rows($result) ){
				return true;
			}
		}

		return false;
	}

	function get_lead_data($lead_id){

		set_time_limit(10);

		$query = "SELECT lead_id, student_num, lead_source_id,
		firstname, middlename, lastname, email, address, city, state, zip,
		phone1, grad_date, arrival_date, leads_program.program_id, leads_campus.campus_id, status,
		targus_validation, targus_score, appointment
		FROM leads
		LEFT JOIN leads_program ON leads.program = leads_program.l360_program
		LEFT JOIN leads_campus ON leads.campus = leads_campus.l360_campus_name
		WHERE lead_id = $lead_id; ";

		if ( $result = mysql_query($query, $this->conn) ){
			if ( $row = mysql_fetch_assoc($result) ){
				return $row;
			}
		}else{
			$this->display( "Query failed: $query" . mysql_error($this->conn) );
		}

		return false;
	}

	function send_updates_via_ftp($path, $filename){

		$host = 'files.mke.firstedgesolutions.com';
		$usr = 'ccollege';
		$pwd = 'pRur7spU';

		$upload_success = false;

		if ( $conn_id = ftp_connect($host, 21) ){

			if ( $login = ftp_login($conn_id, $usr, $pwd) ){

				// turn on passive mode transfers (some servers need this)
				$result = ftp_pasv ($conn_id, true);

				// perform file upload
				if ($result = ftp_put($conn_id, "/incoming/".$filename, $path.$filename, FTP_ASCII)){
					$upload_success = true;
				}

			}

		}

		return $upload_success;
	}

	function update_sent_fes($lead_id){

		$query = "UPDATE leads_pending SET sent_fes = 1 WHERE lead_id = $lead_id";
		if ( $result = mysql_query($query, $this->conn) ){
			return true;
		}

		return false;
	}

	function update_sent_fes_gold($lead_id){

		$query = "UPDATE leads SET fes_gold = 1 WHERE lead_id = $lead_id";
		if ( $result = mysql_query($query, $this->conn) ){
			return true;
		}

		return false;
	}

} //end class
?>
