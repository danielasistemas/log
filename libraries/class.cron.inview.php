<?php

class Inview extends Cron {

	public $db; //database object

	function parse_report_xml($xml){

		if ( isset($xml) && strlen($xml) ){

			if ( $obj = simplexml_load_string($xml) ){

				//var_dump($obj->Result);
				foreach ($obj->Result as $key=>$value) {
					//$data = (array) $value;
					$data_array[] = $value;
				}

				if ( count($data_array) ) return $data_array;

			}

		}

		return false;
	}

	function record_not_found($lead_id, $log_date){
		//don't re-quote this data or the query will fail every time
		//$lead_id = (int) $lead_id;
		//$log_date = $this->db->quote($log_date);
		
		//this is where you can write some logic so that an appointment found within a minute or so
		//of another appointment for the same lead id does not get counted

		if ($lead_id){

			$query = "SELECT * FROM cdx_velocify_appointment WHERE lead_id = $lead_id AND log_date = $log_date";
			//$this->display($query);
			if ( $result = $this->db->query($query)){
				if ( $row = $result->fetch(PDO::FETCH_NUM) ) {
					return false;
				}
			}

		}

		return true;
	}

	function store_report_data($data){

		if ( count($data) ){

			foreach( $data as $obj ){
				//var_dump($obj);
				//convert and escape the data
				$log_type = $this->db->quote($obj->LogType);
				$log_actor = $this->db->quote($obj->LogActor);
				$lead_id = (int) $obj->Id;
				$log_date = $this->db->quote(str_replace('T', ' ', $obj->LogDate));
				$program = $this->db->quote($obj->ProgramofInterest);
				$campus = $this->db->quote($obj->College_x002F_CampusofInterest);

				//check to see if the record exists
				if ( $this->record_not_found($lead_id, $log_date) ){

					//write to database
					$query = "INSERT INTO cdx_velocify_appointment
					(log_type, log_actor, lead_id, log_date, program, campus)
					VALUES
					($log_type, $log_actor, $lead_id, $log_date, $program, $campus);";
	
					//$this->display($query);
					$result = $this->db->query($query);
					$id_list[] = $this->db->lastInsertId();

				}

			}

			if( count($id_list) ) return $id_list;
		}

		return false;
	}

	function set_file_sent($list){

		if ( count($list) ){
			foreach( $list as $pk ){
				if ( $pk = (int) $pk ){
					$query = "UPDATE cdx_velocify_appointment SET file_sent = 1 WHERE pk = $pk";
					//$this->display($query);
					$result = $this->db->query($query);
				}
			}
		}

		return true;
	}

	function get_appointment_line($pk){
		$pk = (int) $pk;
		if ($pk){

			$query = "SELECT pk, log_type, log_actor, lead_id, log_date, program, campus
			FROM cdx_velocify_appointment WHERE pk = $pk";
			if ( $result = $this->db->query($query)){
				if ( $row = $result->fetch(PDO::FETCH_NUM) ) {
					$row = implode('|', $row);
					return $row."\r\n";
				}
			}

		}
		return false;
	}

	function send_file_sftp($local_path, $filename){

		$srcFile = $local_path.$filename;
		$dstFile = '/CarringtonCollege/Velocify/'.$filename;
		//echo "<p>source: '$srcFile'";
		//echo "<p>destination: '$dstFile'";

		$conn = ssh2_connect('sftp.clearviewportal.com', 22);

		ssh2_auth_password($conn, 'Carrington', 'C@rrington$FTPF!les');

		$sftp = ssh2_sftp($conn);

		//open file stream
		$sftpStream = @fopen('ssh2.sftp://'.$sftp.$dstFile, 'w');
		//var_dump($sftpStream);

		//load data to send
		$data = @file_get_contents($srcFile);

		//write the file
		$result = @fwrite($sftpStream, $data);
		//var_dump($result);

    //close the stream
		fclose($sftpStream);

		if( $result ) return true;

		return false;
	}

} //end class
?>
