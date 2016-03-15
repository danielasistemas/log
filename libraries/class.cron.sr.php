<?php

class Sparkroom extends Cron {

	public $current_time; //used to set time that fes export was made

	public $filter;

	function get_online_export_list($db_date){

		$output = array();
		$start_date = date("Y-m-d", strtotime("yesterday"));
		$end_date = date("Y-m-d");

		$query = "SELECT lead_id FROM leads
		WHERE update_date BETWEEN '$start_date' AND '$end_date'
		AND arrival_date >= '2011-07-01'
		AND spark_id != ''
		ORDER BY update_date
		LIMIT 50000; ";
		if ($result = mysql_query($query, $this->conn)){
			while($row = mysql_fetch_assoc($result)){
				$output[] = $row['lead_id'];
			}
		}else{
			$this->display("Query failed: $query" . mysql_error($this->conn));
		}

		return $output;
	}

	//instead of giving 99999 on every lead_source_id, if the id is one of these, it should be used instead
	function get_offline_filter(){

		$output = array();

		$query = "SELECT * FROM sparkroom_filter ORDER BY filter_id";
		if ($result = mysql_query($query, $this->conn)){
			while($row = mysql_fetch_assoc($result)){
				$output[] = (int) $row['filter_id'];
			}
		}else{
			$this->display("Query failed: $query" . mysql_error($this->conn));
		}

		return $output;
	}

	function get_offline_export_list(){

		$output = array();
		$start_date = date("Y-m-d", strtotime("yesterday"));
		$end_date = date("Y-m-d");

		$query = "SELECT lead_id FROM leads
		WHERE update_date BETWEEN '$start_date' AND '$end_date'
		AND arrival_date >= '2011-07-01'
		AND spark_id = ''
		ORDER BY update_date
		LIMIT 50000; ";

		if ($result = mysql_query($query, $this->conn)){
			while($row = mysql_fetch_assoc($result)){
				$output[] = $row['lead_id'];
			}
		}else{
			$this->display("Query failed: $query" . mysql_error($this->conn));
			return false;
		}

		return $output;
	}

	function get_online_lead_data($lead_id){

		set_time_limit(10);

		$query = "SELECT lead_id, spark_id, program_id, campus_id, student_num, status,
		REPLACE(firstname, ',', '') AS firstname, REPLACE(lastname, ',', '') AS lastname,
		email, update_date, arrival_date
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

	function get_offline_lead_data($lead_id){

		set_time_limit(10);

		$query = "SELECT lead_id, spark_id, program_id, campus_id, student_num, status,
		firstname, lastname, email, address, city, state, zip, phone1, phone2, phone3,
		campaign_id, lead_source_id, adv_source, adv_campaign, adv_content,
		targus_score, DATE(update_date) AS update_date, arrival_date
		FROM leads
		LEFT JOIN leads_program ON leads.program = leads_program.l360_program
		LEFT JOIN leads_campus ON leads.campus = leads_campus.l360_campus_name
		WHERE lead_id = $lead_id; ";

		if ( $result = mysql_query($query, $this->conn) ){
			if ( $row = mysql_fetch_assoc($result) ){
				//change the lead_source_id for lead source not in the list
				//if (! isset($this->filter[(int)$row['lead_source_id']])){
				//	$row['adv_source'] = $row['lead_source_id'];
				//	$row['lead_source_id'] = 99999;
				//}
				return $row;
			}
		}else{
			$this->display( "Query failed: $query" . mysql_error($this->conn) );
		}

		return false;
	}

	function send_updates_via_ftp($path, $filename){

		$host = 'ftp.carrington.edu';
		$usr = 'sparkroom';
		$pwd = 'frE2ug7T';

		$upload_success = false;

		if ( $conn_id = ftp_connect($host, 21) ){

			if ( $login = ftp_login($conn_id, $usr, $pwd) ){

				// turn on passive mode transfers (some servers need this)
				$result = ftp_pasv ($conn_id, true);

				// perform file upload
				if ($result = ftp_put($conn_id, $filename, $path.$filename, FTP_ASCII)){
					$upload_success = true;
				}

			}

		}

		return $upload_success;
	}

	function update_sent_sparkroom($lead_id){

		$query = "UPDATE leads SET sent_sparkroom = 1 WHERE lead_id = $lead_id";
		if ( $result = mysql_query($query, $this->conn) ){
			return true;
		}

		return false;
	}

} //end class
?>
