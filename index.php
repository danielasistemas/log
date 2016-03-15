<?php
/**
 * index.php main file
 *
 * @author Craig
 */

session_start();
ob_start();

//set document root
if($_SERVER['DOCUMENT_ROOT'] == "") $_SERVER['DOCUMENT_ROOT'] = "/var/www-cdx";

//include common classes
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.phpmailer.php");
require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.system.php");

//instantiate the system object
$system = new System();

//set error display
$system->set_error_display();

//connect database
$system->db_name = "cdx";
if ( !$conn = $system->db_connect() ){
	$_SESSION['sysmsg'] = "ERROR: database {$system->db_name} not connected";
}
$system->db = $conn;

//see if cookie has been set for persistent sign-in
$cookie_token = "no cookie";
if (isset($_COOKIE['carrington_token'])) $cookie_token = $_COOKIE['carrington_token'];

//parse the url for system variables
$url_array = $system->get_url_vars();

//check for api request and exit
if (isset($url_array[0]) && $url_array[0] == "api"){
	require_once("api/api_controller.php");
	if ($system->conn) mysql_close($system->conn);
	exit;
}

//if nothing is in the url - redirect to home
if (!isset($url_array[0])) $system->redirect("/home/");

//sign out request
if (isset($url_array[0]) && $url_array[0] == "sign_out") $system->sign_out();

//get the signed in user
if (isset($_COOKIE['report_token'])) $system->user = $system->get_user_by_token($_COOKIE['report_token']);

//set the acl for signed in users
if ($system->user) $system->acl = $system->get_acl(); //var_dump($system->acl);

//determine content file to load
$content_file = "content/home.php"; //default content
if ( count($url_array) ){
	$content_file = "content/{$url_array[0]}.php";
	$actions = array("edit", "delete", "search");
	if ( !empty($url_array[1]) && ! in_array($url_array[1], $actions) ){
		$content_file = "content/{$url_array[0]}/{$url_array[1]}.php";
	}
}

/*** override the content for specific situations: invalid ip, no auth, page not found, etc. ***/

//check for restricted page access
if ($system->url_is_restricted()){  //check for page restriction
	if ($system->user){
		if (!$system->user_is_authorized()){
			$content_file = "system/noauth.php"; //user not authorized
		}
	}else{
		$content_file = "system/noauth_signin.php";
	}
}

//check for sign-in or add_ip
if (isset($url_array[0]) && $url_array[0] == "sign_in") $content_file = "system/sign_in.php";
if (isset($url_array[0]) && $url_array[0] == "add_ip") $content_file = "system/add_ip.php";

//check for file exists
if (!file_exists($_SERVER['DOCUMENT_ROOT']."/".$content_file)){
	$not_found = $content_file;
	$content_file = "system/notfound.php";
}

//check ip is authorized unless making an add_ip request
if ( isset($url_array[0]) && $url_array[0] != "add_ip" ){
if (!$system->ip_is_authorized($_SERVER['REMOTE_ADDR'])) $content_file = "system/noauth_ip.php";
}

//force content_file for testing
//$content_file = "system/noauth_ip.php";

//set page title - can be overwritten in content file
$page_title = $system->get_page_title($url_array);

//set up the top nav (main menu)
$main_menu = $system->get_menu($url_array);

//set up side bar - can be overwritten or added to in content file
$side_bar = $system->get_side_bar_nav($url_array);

//set up toolbar - can be overwritten or added to in content file
$toolbar_icons = "";

//page content should be overwritten in content file
$page_content = "<p>MISSING: content</p>";

//load the content file
require_once("$content_file");

//set the system message
$system_message = "&nbsp;";
//$system_message = print_r($system->db, true); //test for db object
if (!empty($_SESSION['sysmsg'])) $system_message = $_SESSION['sysmsg'];

//show the content with html layout
require_once("theme/layout.php");

//unset session records so they don't persist across pages
unset($_SESSION['last_update'], $_SESSION['sysmsg']);

?>
