<?php

/**
 * import_360.php
 *
 * imports leads from Leads 360
 *
 * @author Craig
 * @version 1.1 - updated 05-10-2013
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
$sent_time_warning = false;

//include common classes
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

//instantiate objects
$o_cron = new Cron();

//set notification
$o_cron->notify[] = "cblackham@cc.edu";
//$notify[] = "dmcmurtry@cc.edu";
//$notify[] = "cfong@cc.edu";

if ($o_cron->db_connect()){

	//import the leads_source_lookup to convert L360 campaign_id to lead_source_id
	$o_cron->display("updating leads_source_lookup table...");

	if ( $o_cron->source = $o_cron->get_lead_source_lookup() ){

		if ( $result = $o_cron->update_lead_source_lookup() ){
			$o_cron->update_activity("leads_source_lookup");
		}else{
			$msg = "ERROR: could not update leads_source_lookup";
			$o_cron->display($msg);
			$o_cron->send_error($msg);
		}

	}else{
		$o_cron->source = $o_cron->load_lead_source_lookup(); //get the lookup from local table
		$msg = "NOTICE: could not get leads_source_lookup from L360";
		$o_cron->display($msg);
		$o_cron->send_error($msg);
	}

	//warn if there are duplicates in the leads_source_lookup table
	if ( $o_cron->lead_source_lookup_has_duplicates() ){
		$msg = "WARNING: there are duplicates in the leads_source_lookup table";
		$o_cron->send_error($msg);
	}

	//update the leads_duplicates table
	$o_cron->display("updating leads_duplicates table...");

	if ( $result = $o_cron->update_lead_duplicates() ){
		$o_cron->update_activity("leads_duplicates");
	}else{
		$msg = "ERROR: could not update leads_duplicates";
		$o_cron->display($msg);
		$o_cron->send_error($msg);
	}

	//put all outstanding lead ids to change in the pending table as a buffer
	$o_cron->display("getting time since import...");
	if ($from = $o_cron->get_time_of_last_import()){

		$o_cron->display("getting lead ids since $from...");
		//from and to must be formatted like: 2013-08-14T12:29:54
		$to = date("Y-m-d|H:i:s"); //time that script is running
		$to = str_replace("|", "T", $to);
		if ($leads = $o_cron->get_pending_lead_ids($from, $to) ){

			if ($count = count($leads)){

				if ( $count > 2500 ){
					$msg = "NOTICE: pending count of $count exceeds threshold of 2500".PHP_EOL;
					$o_cron->display($msg);
					$msg .= implode("\r\n",$leads);
					$o_cron->send_error($msg);
				}

				$added = date("Y-m-d H:i:s"); //update time

				for($i = 0; $i < $count; $i ++){

					if (! $o_cron->update_leads_pending_table($leads[$i], $added) ){
						$msg = "ERROR: could not update leads_pending {$leads[$i]}, $added";
						$o_cron->display($msg);
						$o_cron->send_error($msg);
						$status_ok = false;
					}

				}

			}else{
				$msg = "NOTICE: no updates to add to leads_pending";
				$o_cron->display($msg);
			}

		}else{
			$msg = "ERROR: could not get pending lead ids";
			$o_cron->display($msg);
			$o_cron->send_error($msg);
			$status_ok = false;
		}

	}else{
		$msg = "ERROR: could not get minutes since last import";
		$o_cron->display($msg);
		$o_cron->send_error($msg);
		$status_ok = false;
	}

	if ($status_ok){

		//get the records to update one-at-a-time (in case of multiple instances running in cron)
		$o_cron->display("processing leads from leads_pending table...");
		while ($lead_id = $o_cron->get_next_pending_lead_id()){

			if ( count($o_cron->source) ){

				$o_cron->display("processing lead $lead_id ...");

				if ($lead_xml = $o_cron->get_lead_xml($lead_id)){
					//echo "<textarea>$lead_xml</textarea>";

					//parse the lead
					$data = $o_cron->parse_lead_xml($lead_xml);
					//echo '<p>$data</p>'; var_dump($data);

					if ( is_array($data) ){

						set_time_limit(20); //allow 20 secs to update the lead

						//update/insert record in leads table
						if ( $o_cron->lead_exists($lead_id) ) {

							if ( $o_cron->update_lead($data) ) { //update lead
								$o_cron->set_leads_pending_status($lead_id, 1, 'OK');
							}else{
								$msg = "ERROR: could not update leads table for lead $lead_id";
								$o_cron->display($msg);
								$o_cron->send_error($msg);
								$o_cron->set_leads_pending_status($lead_id, 0, 'UPDATE');
								$status_ok = false;
							}

						}else{

							if ( $o_cron->insert_lead($data) ) { //insert lead
								$o_cron->set_leads_pending_status($lead_id, 1, 'OK');
							}else{
								$msg = "ERROR: could not insert lead $lead_id into leads table. {$o_cron->last_error}";
								$o_cron->display($msg);
								$o_cron->send_error($msg);
								$o_cron->set_leads_pending_status($lead_id, 0, 'INSERT');
								$status_ok = false;
							}
						}

					}else{
						$o_cron->set_leads_pending_status($lead_id, 0, 'XML'); //XML - problem with XML
					}

				}else{
					$o_cron->set_leads_pending_status($lead_id, 0, 'DNE'); //Does Not Exist
				}

			}else{
				$msg = "ERROR: problem with source information";
				$o_cron->display($msg);
				$o_cron->send_error($msg);
				$status_ok = false;
			}

			//display output
			ob_flush();
			flush();

			//send a warning if this is running for over an hour and break out of while
			$proc_time = time();
			$time_diff = $proc_time - $start_time;
			if ($time_diff > 3500 && $sent_time_warning == false){
				$sent_time_warning = true;
				$msg = "WRNING: import has been processing for an hour";
				$o_cron->display($msg);
				//$o_cron->send_error($msg);
				//break;
			}

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
