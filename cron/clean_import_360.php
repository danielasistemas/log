<?php

/**
 * clean_import_360.php
 *
 * gets a fresh import from Leads 360
 *
 * @author Craig
 * @version 0.9 - updated 05-10-2013
 *
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

$start_time = time();
$status_ok = true;
$process_limit = 1200;

//include common classes
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.clean.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

//instantiate objects
$o_cron = new Clean();

//set notification
$o_cron->notify[] = "cblackham@cc.edu";

if ($o_cron->db_connect()){

	//load the source lookup table
	$o_cron->display("loading source lookup table...");
	if ( $o_cron->source = $o_cron->load_lead_source_lookup() ){
		$o_cron->display("sources loaded: ".count($o_cron->source) );
		//$o_cron->time_elapsed($start_time, "load_lead_source_lookup");
}else{
		$o_cron->display("FATAL ERROR: could not load source data");
		$status_ok = false;
		exit;
	}

	//display output
	ob_flush();
	flush();

	//read through all pending leads and update them in the leads_clean table
	if ($status_ok){

		$process_count = 0;

		//double check that source data is available
		if (! count($o_cron->source) ){
			$msg = "FATAL ERROR: problem with source information";
			$o_cron->display($msg);
			$status_ok = false;
			exit;
		}

		//get the records to update one-at-a-time (in case of multiple instances running in cron)
		$o_cron->display("processing leads from leads_clean_pending table...");
		while ($lead_id = $o_cron->get_next_clean_pending_lead_id()){

			//$o_cron->time_elapsed($start_time, "get_next_clean_pending");

			//invoke limits
			if ( $process_count <= $process_limit ){
				//$o_cron->display("processing lead $lead_id");
				$process_count++;
			}else{
				$o_cron->display("NOTICE: process limit of $process_limit reached");
				break;
			}
			//$o_cron->time_elapsed($start_time, "check process limit");

			if ($lead_xml = $o_cron->get_lead_xml($lead_id)){
				//echo "<textarea>$lead_xml</textarea>";
				//$o_cron->time_elapsed($start_time, "get_lead_xml");

				//parse the lead
				$data = $o_cron->parse_lead_xml($lead_xml);
				//echo '<p>$data</p>'; var_dump($data);
				//$o_cron->time_elapsed($start_time, "parse_lead_xml");

				if ( is_array($data) ){

					set_time_limit(20); //allow 20 secs to update the lead

					//update/insert record in leads table
					if ( $o_cron->update_lead_clean($data) ) { //update lead
				//$o_cron->time_elapsed($start_time, "update_lead_clean");
						$o_cron->set_leads_clean_pending_status($lead_id, 1, 'OK');
				//$o_cron->time_elapsed($start_time, "set_leads_clean_pending_status");
					}else{
						$msg = "ERROR: could not update leads_clean table for lead $lead_id";
						$o_cron->display($msg);
						$o_cron->display(mysql_error($o_cron->conn));
						$o_cron->set_leads_clean_pending_status($lead_id, 0, 'UPDATE');
						$status_ok = false;
					}

				}else{
					$o_cron->set_leads_clean_pending_status($lead_id, 0, 'XML'); //XML - problem with XML
				}

			}else{
				$o_cron->set_leads_clean_pending_status($lead_id, 0, 'DNE'); //Does Not Exist
			}

			//display output
			//ob_flush();
			//flush();

			//delay so we don't overload processor/mem
			//sleep(1);

			//$o_cron->time_elapsed($start_time, "end while loop");

		} //end while loop

	} //end if status_ok

}else{
	$msg = "ERROR: could not connect to database";
	$o_cron->display($msg);
	$o_cron->send_error($msg);
	$status_ok = false;
}

$status = "FAIL";
if ($status_ok){
	$status = "OK";
	$o_cron->update_activity("leads");
}
$o_cron->display($status);

$end_time = time();
$seconds_elapsed = $end_time - $start_time;
$o_cron->display("execution time: $seconds_elapsed s");

?>
