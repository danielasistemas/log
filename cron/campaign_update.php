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

//include common classes
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.campaign_update.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

//instantiate objects
$o_cu = new CampaignUpdate();

//set notification
$o_cu->notify[] = "cblackham@cc.edu";

$o_cu->source = $o_cu->get_lead_source_lookup();
$o_cu->source = array_flip($o_cu->source);

if ($o_cu->db_connect()){

	//get the records to update one-at-a-time (in case of multiple instances running in cron)
	$o_cu->display("processing leads from unrecognized_campaign table...");
	while ( $lead = $o_cu->get_next_unrecognized_lead() ){ //while/if

		if ( $status_ok ){

			if ( count($o_cu->source) ){

				set_time_limit(20); //allow 20 secs to update the lead
				$o_cu->display("processing lead {$lead['lead_id']} ...");

				if ( isset($o_cu->source[$lead['lead_source_id']]) ){

					if ( $result = $o_cu->update_velocify_campaign($lead['lead_id'], $o_cu->source[$lead['lead_source_id']]) ){

						//set status based on result
						if ($result == 'OK'){
							$o_cu->set_update_status($lead['lead_id'], 1, $result);
						}else{
							$o_cu->set_update_status($lead['lead_id'], 0, $result);
						}

					}else{
						$msg = "ERROR: could not update Velocify record for lead {$lead['lead_id']} campaign {$o_cu->source[$lead['lead_source_id']]}";
						$o_cu->display($msg);
						$status_ok = false;
					}

				}else{
					$msg = "ERROR: no source-campaign record for lead_source_id {$lead['lead_source_id']}";
					$o_cu->display($msg);
					$status_ok = false;
				}

			}else{
				$msg = "ERROR: problem with source information";
				$o_cu->display($msg);
				$status_ok = false;
			}

		}

		//display output
		ob_flush();
		flush();

	} //end while loop

}else{
	$msg = "ERROR: could not connect to database";
	$o_cu->display($msg);
	$status_ok = false;
}

$status = "FAIL";
if ($status_ok){
	$status = "OK";
}
$o_cu->display($status);

$end_time = time();
$seconds_elapsed = $end_time - $start_time;
$o_cu->display("execution time: $seconds_elapsed s");

?>
