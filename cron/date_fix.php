<?php

/**
 * date_fix.php
 *
 * changes dates from yyyy-m-d to mm/dd/yyyy
 *
 * @author Craig
 * @version 1.0 - 04-01-2014
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

$broken = array(
"firecracker7677@rocketmail.com"=>"2014-4-4 9:15 AM",
"candicelatin84@yahoo.com"=>"2014-4-4 1:15 PM",
"mikieyisela@gmail.com"=>"2014-4-4 1:15 PM",
"Dulseleyva29@icloud.com"=>"2014-4-3 5:45 PM",
"julieblancas@ymail.com"=>"2014-4-4 9:15 AM",
"gaby.garcia86@hotmail.com"=>"2014-4-7 9:15 AM",
"verserinternational2000@yahoo.com"=>"2014-4-3 3:15 PM",
"campbellcrystal3@live.com"=>"2014-4-2 1:15 PM",
"reyna32195@gmail.com"=>"2014-4-7 1:15 PM",
"nicolegamezzz@gmail.com"=>"2014-4-4 11:15 AM",
"sjstone33@yahoo.com"=>"2014-4-4 9:15 AM",
"ruthowa@yahoo.com"=>"2014-4-7 11:15 AM",
"joshuacutler20@gmail.com"=>"2014-4-2 1:15 PM",
"Babygaby1313@aol.com"=>"2014-4-7 5:45 PM",
"brandihanchett8@gmail.com"=>"2014-4-1 1:15 PM",
"arleshasamuda@gmail.com"=>"2014-4-4 1:15 PM",
"mohammad-gh-750@facebook.com"=>"2014-4-1 11:15 AM",
"debbie0704@comcast.net"=>"2014-4-1 3:15 PM"
);

//include common classes
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.date_fix.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

//instantiate objects
$o_df = new DateFix();

//set notification
$o_df->notify[] = "cblackham@cc.edu";


if ($o_df->db_connect()){

	foreach ( $broken as $email=>$date ){

		if ( $status_ok ){

			set_time_limit(20); //allow 20 secs to update the lead
			$o_df->display("processing $email ...");
			$o_df->display("date: $date");
			$new_date = $o_df->get_fixed_date($date);
			$o_df->display("new date: $new_date");

			if ( $lead_id = $o_df->get_lead_id_by_email($email) ){

				$o_df->display("lead_id: $lead_id");
				if ( $result = $o_df->update_velocify_date($lead_id, $new_date) ){

					$o_df->display("$email OK");

				}else{
					$msg = "ERROR: could not update Velocify record for lead {$lead_id} date {$new_date}";
					$o_df->display($msg);
					$status_ok = false;
				}

			}else{
				$msg = "ERROR: could not find lead_id for email $email";
				$o_df->display($msg);
				$status_ok = false;
			}

		}

		//display output
		ob_flush();
		flush();

	} //end while loop

}else{
	$msg = "ERROR: could not connect to database";
	$o_df->display($msg);
	$status_ok = false;
}

$status = "FAIL";
if ($status_ok){
	$status = "OK";
}
$o_df->display($status);

$end_time = time();
$seconds_elapsed = $end_time - $start_time;
$o_df->display("execution time: $seconds_elapsed s");

?>
