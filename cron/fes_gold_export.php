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

	$delete_count = $o_fes->delete_old_leads();
	$o_fes->display("old records deleted: $delete_count");

	//initialize variables
	$unix_time = strtotime("yesterday");
	$o_fes->db_date = date("Y-m-d", $unix_time);
	$path = "/var/www/cache/";
	$written_lead_ids = array();

	//get an array of capped programs
	$capped_programs = $o_fes->get_capped_programs();
	//var_dump($capped_programs); exit;

	//create a file to hold the output
	$filename = "fes_gold{$o_fes->db_date}.txt";
	$o_fes->display("creating file $filename...");
	if ( $fp = fopen($path.$filename, "w" ) ){

		$heading = "LeadID\tFirstName\tMiddleName\tLastName\tEmail\tAddr1\tCity\tState\tZip\tProgram\tCampus\tArrivalDate\tAppointment\n";
		if (!$result = fwrite($fp, $heading, 1024) ){
			$o_fes->display("Write header failed: $filename");
			$status_ok = false;
		}

	}else{
		$o_fes->display("File creation failed: $filename");
		$status_ok = false;
	}

	//query local leads db
	if ($status_ok){

		$o_fes->display("getting export data...");
		$data = $o_fes->get_gold_export_data($capped_programs);

		if ( count($data) ) {

			foreach ($data as $lead){

				if ( $write = fputcsv($fp, $lead, "\t") ){
					//add to an array showing the record was added to the file
					$written_lead_ids[] = $lead['lead_id'];
				}

			}

		}else{
			$msg = "NOTICE: no golden ticket records to export to fes";
			$o_fes->display($msg);
			$status_ok = false;
		}

	}

	//close the file
	fclose($fp);

	//send file via FTP
	if ($status_ok){

		$o_fes->display("sending gold file...");
		if ( $upload = $o_fes->send_updates_via_ftp($path, $filename) ){
			$o_fes->display("NOTICE: upload complete");
		}else{
			$msg = "ERROR: fes upload failed for {$path}{$filename}";
			$o_fes->display($msg);
			$o_fes->send_error($msg);
			$status_ok = false;
		}

	}

	//set the sent_fes flag in leads table
	if ($status_ok){

		$o_fes->display("flagging records as sent...");
		set_time_limit(30);
		if ( count($written_lead_ids) ){
			foreach($written_lead_ids as $lead_id){
				$o_fes->update_sent_fes_gold($lead_id);
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
	$o_fes->update_activity("fes_gold_export");
}
$o_fes->display($status);

$end_time = time();
$seconds_elapsed = $end_time - $start_time;
$o_fes->display("execution time: $seconds_elapsed s");

?>
