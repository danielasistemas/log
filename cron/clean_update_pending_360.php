<?php

/**
 * clean_update_pending_360.php
 *
 * updates the leads_clean_pending table
 *
 * @author Craig
 * @version 1.0 - updated 12-23-2013
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
$process_limit = 10;

//include common classes
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.clean.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

//instantiate objects
$o_cron = new Clean();

//set notification
$o_cron->notify[] = "cblackham@cc.edu";

if ($o_cron->db_connect()){

	//get lead ids to import beginning with Jan 01 2009
	//load all years into leads_clean_pending then process later
	$o_cron->display("getting lead ids since Jan 2009...");

	for ($year = 2009; $year <= 2013; $year++){  //all is too big so get one year at a time in a for loop

		//from and to must be formatted like: 2013-08-14T12:29:54
		$from = $year."-01-01T00:00:01";
		$to = ($year + 1)."-01-01T00:00:01";

		if ( $year == date("Y") ){
			$to = date("Y-m-d|H:i:s"); //time that script is running
			$to = str_replace("|", "T", $to); //can't use T in date function as it has meaning (maybe could escape?)
		}

		$o_cron->display("from: $from to: $to...");

		if ( $leads = $o_cron->get_clean_lead_ids($from, $to) ){

			if ( $count = count($leads) ){

				$o_cron->display("found $count lead ids...");
				$added = date("Y-m-d H:i:s"); //update time

				for($i = 0; $i < $count; $i ++){
					set_time_limit(10);
					if (! $o_cron->update_leads_clean_pending_table($leads[$i], $added) ){
						$msg = "FATAL ERROR: could not update leads_clean_pending {$leads[$i]}, $added";
						$o_cron->display($msg);
						$o_cron->display(mysql_error($o_cron->conn));
						$status_ok = false;
						exit;
					}

				}

			}else{
				$msg = "ERROR: lead ids count is zero";
				$o_cron->display($msg);
				$status_ok = false;
			}

			unset($leads); //free the memory taken by large list of leads

		}else{
			$msg = "ERROR: could not get pending lead ids";
			$o_cron->display($msg);
			$o_cron->send_error($msg);
			$status_ok = false;
		}

	} //end for loop (years)

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
