<?php

/**
 * inview_appt_export.php
 *
 * reads a velocify report from api, parses the xml, stores the records in a db,
 * queries the db, creates a csv file with pipe delimiter, and sends the file to
 * inview
 *
 * @author Craig
 * @version 0.1 - created 07-21-2015
 *
 **/

//error reporting
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("display_errors", "1");

//set document root and html error
if($_SERVER['DOCUMENT_ROOT'] == ""){
	$_SERVER['DOCUMENT_ROOT'] = "/var/www-cdx";
	ini_set("html_errors", "0");
}else{
	ini_set("html_errors", "1");
}

$start_time = time();
$status_ok = true;

//include common classes
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.cron.inview.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");

//instantiate objects
$o_cron = new Inview();

//set notification
$o_cron->notify[] = "cblackham@carrington.edu";
//$o_cron->send_error("test");

//set db name
$o_cron->db_name = "cdx";

if ( $db = $o_cron->db_connect() ){
	$o_cron->db = $db;

	$o_cron->display("getting appointment report...");
	if ( $xml = $o_cron->get_velocify_report("936") ){

		//var_dump($xml);
		$o_cron->display("parsing report xml...");
		if ( $data = $o_cron->parse_report_xml($xml) ){

			//var_dump($data);
			$o_cron->display("storing data into table...");
			if ( $list = $o_cron->store_report_data($data) ){

				//var_dump($list);
				$o_cron->display("creating xfer file...");
				$path = $_SERVER['DOCUMENT_ROOT']."/cache/";
				$file_time = date("ymdHi"); //YYMMDDHHMM
				$filename = "appt_hourly_{$file_time}.csv";
				if ( $fp = fopen($path.$filename, "w" ) ){

					$heading = "Pk|LogType|LogActor|Id|LogDate|Program|Campus\r\n";
					if (!$result = fwrite($fp, $heading, 1024) ){
						$o_cron->display("ERROR: write header failed: $filename");
						$status_ok = false;
					}

					//iterate through the pks in $list and add them to a file
					foreach( $list as $pk ){
						if ( $data = $o_cron->get_appointment_line($pk) ){

							//var_dump($data); exit;
							$write = fwrite($fp, $data, 1024);

						}else{
							$o_cron->display("ERROR: get_appointment_line failed for pk $pk");
						}

					}

					fclose($fp); //close the file pointer

					$o_cron->display("sending file...");
					if ( $result = $o_cron->send_file_sftp($path, $filename) ){
						$o_cron->set_file_sent($list);
					}else{
						$o_cron->display("FATAL ERROR: problem sending file");
						$status_ok = false;
					}

					$o_cron->display("deleting old files...");
					$o_cron->delete_old_files($path, "appt_hourly_");

				}else{
					$o_cron->display("FATAL ERROR: problem creating xfer file");
					$status_ok = false;
				}

			}else{
				$o_cron->display("FATAL ERROR: problem storing data");
				$status_ok = false;
			}

		}else{
			$o_cron->display("FATAL ERROR: problem parsing report xml");
			$status_ok = false;
		}

	}else{
		$o_cron->display("FATAL ERROR: problem getting appointment report");
		$status_ok = false;
	}

	//display output
	ob_flush();
	flush();

}else{
	$msg = "ERROR: could not connect to database";
	$o_cron->display($msg);
	$o_cron->send_error($msg);
	$status_ok = false;
}

$o_cron->display("updating cron log...");
$status = "FAIL";
if ($status_ok){
	$status = "OK";
	$o_cron->update_cron_log("inview_appt");
}else{
	//$o_cron->send_error("inview_appt_export");
}

$o_cron->display($status);

$end_time = time();
$seconds_elapsed = $end_time - $start_time;
$o_cron->display("execution time: $seconds_elapsed s");

?>
