<?php

class Cron {

	public $conn; //database connection object

	public $notify; //array of people to notify of errors

	public $last_error; //container for last error message

	function db_connect(){

		$db = new PDO("mysql:host=localhost;dbname=".$this->db_name, "cronuser", "5AyehePh");

		if ( $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION) ){
			return $db;
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

	function send_error($message){

		$time = date("m-d-Y H:i:s");
		$trace = debug_backtrace();
		$trace_output = "file = {$trace[0]['file']}<br>\n";
		$trace_output .= "line = {$trace[0]['line']}<br>\n";
		$trace_output .= "class = {$trace[0]['class']}<br>\n";

		$msg = "
			<html><body>
				<table style='font-family: Arial, sans-serif; font-size: 12px;' cellpadding='6' cellspacing='0' border='0'>
				<tr>
					<td align='right'><b>Time:</b>  </td><td>{$time}</td>
				</tr>
				<tr>
					<td align='right'><b>Exception:</b>  </td><td>{$message}</td>
				</tr>
				<tr>
					<td align='right'><b>Trace:</b>  </td><td>$trace_output</td>
				</tr>
				</table>
			</body></html>";

		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->Host = "localhost";
		$mail->From = "noreply@cdx.carrington.edu";
		$mail->FromName = "Carrington CDX";
		foreach ($this->notify as $email_address){
			$mail->AddAddress($email_address);
		}
		$mail->Subject = "CDX Server Notice";
		$mail->Body = $msg;
		$mail->isHTML(true);

		$mail->Send();

	}

	function delete_old_files($path, $filename_pattern){

		if ( !empty($path) && !empty($filename_pattern) ){

			$current_time = time(); //set current time
			$files = scandir($path); //get all files in path
			foreach( $files as $file ){
				if ( is_file($path.$file) ){ //only check files - not dirs including . and ..
					if ( strpos($file, $filename_pattern) !== FALSE ){
						if ( $current_time - filemtime($path.$file) >= 30*24*60*60 ){ // 30 days
							unlink($path.$file);
						}
					}
				}
			}
			return true;
		}

		return false;
	}

	function get_velocify_report($report_id){

		if ( isset($report_id) && is_numeric($report_id) ){

			$url = "http://service.leads360.com/clientservice.asmx/GetReportResultsWithoutFilters";

			$sess = curl_init($url);
			curl_setopt($sess, CURLOPT_HEADER, false);
			curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($sess, CURLOPT_POSTFIELDS, "username=webservice@carrington.edu&password=W3bS3rv1c3&reportId=$report_id");
			$result = curl_exec($sess);
			curl_close($sess);

			if( $result ){
				return $result;
			}

		}

		return false;
	}

	function get_cron_log($table){

		$query = "SELECT last_run FROM cron_log
		WHERE table_name = '$table'";
		if ($result = mysql_query($query, $this->conn)){
			if ($row = mysql_fetch_assoc($result)){
				return $row['last_run'];
			}
		}

		return false;
	}

	function activity_time_ok($time_limit, $last_run){

		$difference = floor((time() - strtotime($last_run)) / 60 );
		//echo '<p>$difference</p>'; var_dump($difference);
		if ( (int) $difference <= (int) $time_limit ){
			return true;
		}

		return false;
	}

	function update_cron_log($name){

		$name = $this->db->quote($name);
		$query = "UPDATE cron_log SET
		previous_run = last_run,
		last_run = NOW()
		WHERE table_name = $name";
		if ( $result = $this->db->query($query) ){
			return true;
		}

		return false;
	}

	function get_api_result($params){
		$api_base_url = 'http://report.carrington.edu/api';
		$url = $api_base_url.$params;
		//echo "<p>url: $url</p>";
		$sess = curl_init($url);
		curl_setopt($sess, CURLOPT_TIMEOUT, 24);
		curl_setopt($sess, CURLOPT_HEADER, false);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($sess);
		curl_close($sess);

		//convert json response to array
		$result = (array) json_decode($result);

		return $result;
	}

} //end class
?>
