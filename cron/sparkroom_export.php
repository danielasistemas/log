<?php
/**
 * sparkroom export
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

//include common classes
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.sr.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

//instantiate objects
$o_sr = new Sparkroom();

//set notification email
$o_sr->notify[] = "cblackham@cc.edu";

if ($o_sr->db_connect()){

	$path = "/var/www/cache/";
	$db_date = date("Y-m-d", strtotime("yesterday"));

	/*** online files ***/

	//create a file to hold the output
	$filename = "spark_online{$db_date}.csv";
	$o_sr->display("creating file $filename...");
	if ( $fp = fopen($path.$filename, "w" ) ){

		$heading = "lead_id,spark_id,program,campus,student_num,status,firstname,lastname,email,update_date,arrival_date\n";
		if (!$result = fwrite($fp, $heading, 1024) ){
			$o_sr->display("Write header failed: $filename");
			$status_ok = false;
		}

	}else{
		$o_sr->display("File creation failed: $filename");
		$status_ok = false;
	}

	if ($status_ok){

		$o_sr->display("getting online export list...");
		if ( $lead_ids = $o_sr->get_online_export_list($db_date) ){
		//var_dump($lead_ids);

			if ( count($lead_ids) ) {

				foreach ($lead_ids as $lead_id){
					if ( $data = $o_sr->get_online_lead_data($lead_id) ){

						$write = fputcsv($fp, $data, ",", "\"");

					}else{
						$msg = "ERROR: could not get data from leads for $lead_id";
						$o_sr->display($msg);
						$o_sr->send_error($msg);
						$status_ok = false;
					}
				}

			}else{
				$msg = "NOTICE: no lead ids to export to sparkroom";
				$o_sr->display($msg);
			}

		}else{
			$msg = "ERROR: could not get sparkroom online export list";
			$o_sr->display($msg);
			$o_sr->send_error($msg);
			$status_ok = false;
		}

	}

	//close the file
	fclose($fp);

	if ($status_ok){

		// perform file upload
		$o_sr->display("sending online updates...");
		if ( $upload = $o_sr->send_updates_via_ftp($path, $filename) ){
			$o_sr->display("NOTICE: upload complete");
		}else{
			$msg = "ERROR: sparkroom upload failed for {$path}{$filename}";
			$o_sr->display($msg);
			$o_sr->send_error($msg);
			$status_ok = false;
		}

	}

	/*** offline files ***/

	//create a file to hold the output
	$filename = "spark_offline{$db_date}.csv";
	$o_sr->display("creating file $filename...");
	if ( $fp = fopen($path.$filename, "w" ) ){

		$heading = "lead_id,spark_id,program,campus,student_num,status,firstname,lastname,email,address,city,state,zip,phone1,phone2,phone3,campaign_id,lead_source_id,adv_source,adv_campaign,adv_content,targus_score,update_date,arrival_date\n";

		if (!$result = fwrite($fp, $heading, 1024) ){
			$o_sr->display("Write header failed: $filename");
			$status_ok = false;
		}

	}else{
		$o_sr->display("File creation failed: $filename");
		$status_ok = false;
	}

	if ($status_ok){

		$o_sr->display("getting offline export list...");
		$lead_ids = $o_sr->get_offline_export_list();

		if ( is_array($lead_ids) ){

			if ( count($lead_ids) ) {

				$o_sr->filter = $o_sr->get_offline_filter();

				foreach ($lead_ids as $lead_id){
					if ( $data = $o_sr->get_offline_lead_data($lead_id) ){

						$write = fputcsv($fp, $data, ",", "\"");

					}else{
						$msg = "ERROR: could not get offline data from leads for $lead_id";
						$o_sr->display($msg);
						$o_sr->send_error($msg);
						$status_ok = false;
					}
				}

			}else{
				$msg = "NOTICE: no offline lead ids to export to sparkroom";
				$o_sr->display($msg);
			}

		}else{
			$msg = "ERROR: could not get sparkroom offline export list";
			$o_sr->display($msg);
			$o_sr->send_error($msg);
			$status_ok = false;
		}

	}

	//close the file
	fclose($fp);

	if ( $status_ok && count($lead_ids) ){

		// perform file upload
		$o_sr->display("sending offline updates...");
		if ( $upload = $o_sr->send_updates_via_ftp($path, $filename) ){
			$o_sr->display("NOTICE: upload complete");
		}else{
			$msg = "ERROR: sparkroom upload failed for {$path}{$filename}";
			$o_sr->display($msg);
			$o_sr->send_error($msg);
			$status_ok = false;
		}

	}

}else{
	$msg = "ERROR: could not connect to database";
	$o_sr->display($msg);
	$o_sr->send_error($msg);
	$status_ok = false;
}

$status = "FAIL";
if ($status_ok){
	$status = "OK";
	$o_sr->update_activity("sparkroom_export");
}
$o_sr->display($status);

$end_time = time();
$seconds_elapsed = $end_time - $start_time;
$o_sr->display("execution time: $seconds_elapsed s");

?>
