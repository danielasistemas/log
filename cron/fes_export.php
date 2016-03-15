<?php
/**
 * fes_export (first edge solutions)
 *
 * @author Craig
 **/

//error reporting
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("display_errors", "1");

//set document root and html error
if($_SERVER['DOCUMENT_ROOT'] == ""){
	$_SERVER['DOCUMENT_ROOT'] = "/var/www";
}else{
	ini_set("html_errors", "1");
}

set_time_limit(90);

$start_time = time();
$status_ok = true;

require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.fes.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

//instantiate objects
$o_fes = new Fes();

//set notification email
$o_fes->notify[] = "cblackham@cc.edu";

if ($o_fes->db_connect()){

	//initialize variables for time functions
	$current_time = time();
	$o_fes->current_time = date("Y-m-d H:i:s", $current_time);
	$db_date = date("Y-m-d", $current_time);
	$file_time = date("His", $current_time);
	$path = "/var/www/cache/";

	//1. clean up old files
	$o_fes->display("cleaning up old files...");
	$files = scandir($path);
	foreach($files as $file){
		if (is_file($path.$file)){
			if ( $current_time - filemtime($path.$file) >= 90*24*60*60) { // 90 days
				unlink($path.$file);
			}
		}
	}

	//2. try to create a file to hold the output
	$filename = "fes_leads{$db_date}-{$file_time}.txt";
	$o_fes->display("creating file $filename...");
	if ( $fp = fopen($path.$filename, "w" ) ){

		$heading = "LeadID\tStuNum\tLeadSource\tFirstName\tMiddleName\tLastName\tEmail\tAddr1\tCity\tState\tZip\tPhone\tGradDate\tLeadDate\tProgram\tCampus\tStatus\tTargusValidation\tTargusScore\tAppointment\n";
		if (!$result = fwrite($fp, $heading, 1024) ){
			$o_fes->display("Write header failed: $filename");
			$status_ok = false;
		}

	}else{
		$o_fes->display("File creation failed: $filename");
		$status_ok = false;
	}

	//3. query local leads db
	if ($status_ok){

		$o_fes->display("getting export list...");
		if ( $lead_ids = $o_fes->get_export_list() ){
		//var_dump($lead_ids);

			if ( count($lead_ids) ) {

				$written_lead_ids = array();

				foreach ($lead_ids as $lead_id){

					if (! $o_fes->is_duplicate($lead_id) ){

						if ( $data = $o_fes->get_lead_data($lead_id) ){

							if ( $write = fputcsv($fp, $data, "\t") ){
								//add to an array showing the record was added to the file
								$written_lead_ids[] = $lead_id;
							}

						}else{
							$msg = "ERROR: could not get data from leads for $lead_id";
							$o_fes->display($msg);
							//$o_fes->send_error($msg);
							//$status_ok = false;
						}

					} //end if not duplicate

				}

			}else{
				$msg = "NOTICE: no lead ids to export to fes";
				$o_fes->display($msg);
			}

		}else{
			$msg = "ERROR: could not get fes export list";
			$o_fes->display($msg);
			$o_fes->send_error($msg);
			$status_ok = false;
		}

	}

	//4. close the file
	fclose($fp);


	//3. send file via FTP
	if ($status_ok){

		$o_fes->display("sending updates...");
		if ( $upload = $o_fes->send_updates_via_ftp($path, $filename) ){
			$o_fes->display("NOTICE: upload complete");
		}else{
			$msg = "ERROR: fes upload failed for {$path}{$filename}";
			$o_fes->display($msg);
			$o_fes->send_error($msg);
			$status_ok = false;
		}

	}

	//4. set the sent_fes flag in leads table
	if ($status_ok){

		$o_fes->display("flagging records as sent...");
		set_time_limit(30);
		if ( count($written_lead_ids) ){
			foreach($written_lead_ids as $lead_id){
				$o_fes->update_sent_fes($lead_id);
			}
		}

	}

}else{
	$msg = "ERROR: could not connect to database";
	$o_fes->display($msg);
	$o_fes->send_error($msg);
	$status_ok = false;
}

$status = "FAIL";
if ($status_ok){
	$status = "OK";
	$o_fes->update_activity("fes_export");
}
$o_fes->display($status);

$end_time = time();
$seconds_elapsed = $end_time - $start_time;
$o_fes->display("execution time: $seconds_elapsed s");

?>
