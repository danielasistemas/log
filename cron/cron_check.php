<?php

//error reporting
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("display_errors", "1");

//set document root and html error
if($_SERVER['DOCUMENT_ROOT'] == ""){
	$_SERVER['DOCUMENT_ROOT'] = "/var/www";
}else{
	ini_set("html_errors", "1");
}

set_time_limit(120);

$start_time = time();
$status_ok = true;

$foo = date("Y-m-d H:i:s", $start_time);

//include common classes
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

//instantiate objects
$o_cron = new Cron();
$o_cron->display("server time: $foo");

//set notification
$o_cron->notify[] = "cblackham@cc.edu";
//$notify[] = "dmcmurtry@cc.edu";
//$notify[] = "cfong@cc.edu";

if ($o_cron->db_connect()){

	//array of scripts to check and frequency of succesful cron script in minutes
	$checklist = array();
	$checklist['leads'] = 120;
	$checklist['leads_duplicates'] = 90;
	$checklist['leads_source_lookup'] = 90;
	$checklist['fes_export'] = 90;
	$checklist['sparkroom_export'] = 1480;

	$o_cron->display("checking activity_log table...");

	foreach($checklist as $table=>$time_limit){

		if ( $last_run = $o_cron->get_activity($table) ){

			if ( $o_cron->activity_time_ok($time_limit, $last_run) ){
				$o_cron->display("OK: $table");
			}else{
				$msg = "ERROR: activity exceeds time limit for $table";
				$o_cron->display($msg);
				$o_cron->send_error($msg);
			}

		}else{
			$msg = "ERROR: unable to get activity_log record for $table";
			$o_cron->display($msg);
			$o_cron->send_error($msg);
		}

	}

} //end if db connect

$end_time = time();
$seconds_elapsed = $end_time - $start_time;
$o_cron->display("execution time: $seconds_elapsed s");

?>
