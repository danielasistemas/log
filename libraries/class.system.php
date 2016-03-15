<?php

class System {

	public $conn; //db connection to use for this class

	public $db_name; //name of database to connect for system functions

	public $user = false; //signed-in user (not always set)

	public $acl = array(); //access control list for signed-in user

	function db_connect(){

		try {

			$db = new PDO("mysql:host=localhost;dbname=".$this->db_name, "dbuser", "5AyehePh");
	
			if ( $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION) ){
				return $db;
			}
		
		} catch (PDOException $e) {
	    $this->display($e->getMessage());
		}

		/*
		if ( $connection_object = mysql_connect('localhost', 'dbuser', '5AyehePh') ){
			if ( mysql_select_db($this->db_name) ){
				$this->conn = $connection_object;
				return $connection_object;
			}
		}
		*/

		return false;
	}

	function clean_data($data){

		if (is_array($data)){
			foreach($data as $key=>$value){
				$data[$key] = mysql_escape_string($value);
			}
		}

		if (is_string($data)){
			$data = mysql_escape_string($data);
		}

		if (is_numeric($data)){
			$data = (int)$data;
		}

		return $data;
	}

	function display($string){

		if (isset($_SERVER['HTTP_HOST'])){
			$output = "<p>".$string."</p>";
		}else{
			$output = $string."\n";
		}

		echo $output;
	}

	function get_url_vars(){

		if (substr($_SERVER["REQUEST_URI"], -1, 1) != "/") $_SERVER["REQUEST_URI"] .= "/";
		$url_array = explode("/", $_SERVER["REQUEST_URI"]);
		array_shift($url_array);
		array_pop($url_array);

		return $url_array;
	}

	function set_error_display(){

		$debug = false;

		//setcookie( "report_token", "cvb", strtotime( '+30 days' ) ); //uncomment to set cookie

		if ($_SERVER['REMOTE_ADDR'] == "68.230.33.57") $debug = true;
		if ($_SERVER['REMOTE_ADDR'] == "24.249.161.94") $debug = true;
		if ($_SERVER['REMOTE_ADDR'] == "98.174.194.142") $debug = true;
		
		if (isset($_COOKIE['report_token']) && $_COOKIE['report_token'] == "cvb") $debug = true;

		error_reporting (E_ALL);
		ini_set("display_errors", "0");
		if($debug){
			ini_set("html_errors", "1");
			ini_set("display_errors", "1");
		}
	}

	function redirect($location){
		$redir = "http://".$_SERVER['HTTP_HOST'].$location;
		header("location: $redir");
		exit;
	}

	function sign_out(){

		// Unset the user session variable
		unset($_SESSION['user']);

		//delete the token cookie
		setcookie("report_token", "", time()-3600, "/");

		//redirect
		$redir="http://".$_SERVER['HTTP_HOST']."/";
		header("location: $redir");
		exit;

	}

	function ip_is_authorized($ip){
		//$ip = mysql_escape_string($ip);
		$ip = $this->db->quote($ip);

		$query = "SELECT * FROM auth_address WHERE auth_ip = $ip;";
		//if ($result = mysql_query($query, $this->conn)){
		if ( $result = $this->db->query($query) ){

			//if ($row = mysql_fetch_assoc($result)){
			if ( $row = $result->fetch(PDO::FETCH_ASSOC) ){
				return true;
			}
		}

		return false;
	}

	function get_menu_items(){

		$items = array("home");
		$files = scandir($_SERVER['DOCUMENT_ROOT']."/content");

		foreach($files as $file){
			if ($file != "home.php" && $file != "admin.php" && strpos($file, ".php")){
				$items[] = substr($file, 0, (strlen($file) - 4));
			}
		}

		if (in_array("/admin", $this->acl)) $items[] = "admin";
		return $items;
	}

	function get_menu($url_array){

		$main_menu = "";

		//get the menu items
		$menu_items = $this->get_menu_items();

		foreach($menu_items as $item){

			$class = "";
			$link_text = ucwords(str_replace("_", " ", $item));
			if (!empty($url_array[0]) && $url_array[0] == $item){
				$class = "active";
			}
			$main_menu .= <<<EOD
	<li class="$class"><a href='/{$item}/'>{$link_text}</a></li>
EOD;
		}

		//add the sign out item if logged in
		if (isset($_SESSION['user']['id'])){ //if logged in and session active
			$main_menu .= <<<EOD
	<li class=""><a href='/sign_out/'>Sign Out</a></li>
EOD;
		}

		return $main_menu;
	}

	function get_side_bar_nav($url_array){

		$side_nav = "";

		if (!empty($url_array[0])){

			$folder = $_SERVER['DOCUMENT_ROOT']."/content/{$url_array[0]}";

			if(file_exists($folder) && is_dir($folder)){

				$files = scandir($folder);
				foreach($files as $file){
					if (strpos($file, ".php")){
						$items[] = substr($file, 0, (strlen($file) - 4));
					}
				}

			}

			if (!empty($items)){

				foreach($items as $item){

					$class = "";
					if (!empty($url_array[1]) && $url_array[1] == $item){
						$class = "msel";
					}
					$link_text = ucwords(str_replace("_", " ", $item));
					$side_nav .= <<<EOD
		<li class="mitem $class"><a class="kl" href='/{$url_array[0]}/{$item}/'>{$link_text}</a></li>

EOD;
				}

			}

		}

		return $side_nav;
	}

	function get_page_title($url_array){

		$page_title = "";

		if (!empty($url_array[0])){
			$page_title = ucwords(str_replace("_", " ", $url_array[0]));
		}
		if (!empty($url_array[1])){
			$page_title = ucwords(str_replace("_", " ", $url_array[1]));
		}

		return $page_title;
	}

	function generate_html_table($data_set, $options=array()){
		//var_dump($options['exclude']);
		$output = "";
		$head_row = "";
		$filter_row = "";
		$data_row = "";
		$row_count = 0;
		$filter = false;
		if (isset($options['header']) && isset($options['filter']) && $options['filter'] == true){
			$filter = true;
		}
		$table_class = "data";
		if (isset($options['class']) && $options['class'] != ""){
			$table_class = $options['class'];
		}

		if(is_array($data_set)){

			//loop through data
			foreach ($data_set as $primary_key=>$row){

				$row_count++;

				if ($row_count == 1){ // first row, so get the header

					//begin the html for header row
					$head_row .= "<tr>".PHP_EOL;

					//begin the html for filter row
					if ($filter) $filter_row .= "<tr class='filter'>".PHP_EOL;

					if(isset($options['header'])){ //use headers provided in options

						foreach ($options['header'] as $column){
							$head_row .= "	<th>$column</th>".PHP_EOL;
							if ($filter) $filter_row .= "	<th><input class='search_init' name='$column' value='' /></th>".PHP_EOL;
						}

					}else{ //no header specified, so use column names

						foreach ($row as $column=>$value){
							$exclude_column = false; //do not exclude by default
							if (isset($options['exclude']) && in_array($column, $options['exclude'])){
								$exclude_column = true;
							}

							if (!$exclude_column){
								$head_row .= "	<th>$column</th>".PHP_EOL;
							}
						}

					}

					//add empty cells to header for action columns
					if (isset($options['action'])){
						foreach ($options['action'] as $action){
							$head_row .= "	<th class='action'>&nbsp;</th>".PHP_EOL;
							if ($filter) $filter_row .= "	<th class='action'>&nbsp;</th>".PHP_EOL;
						}
					}

					//terminate header row
					$head_row .= "</tr>".PHP_EOL;

					//terminate filter row
					if ($filter) $filter_row .= "</tr>".PHP_EOL;

				}

				//underline every third row for readability
				$class = "";
				if (($row_count % 3) == 0) $class = "underlined";

				//begin data row
				$data_row .= "<tr>".PHP_EOL;

				foreach ($row as $column=>$value){
					$exclude_column = false; //do not exclude by default
					if ( isset($options['exclude'][$column]) ) $exclude_column = true;

					if (!$exclude_column){

						if (isset($options['format'][$column])){

							//special formatting on a column basis
							if ($options['format'][$column] == "boolean"){
								$value = ($value)?"yes":"no";
								$data_row .= "	<td align='center'>$value</td>".PHP_EOL;
							}

						}else{

							$data_row .= "	<td>$value</td>".PHP_EOL;

						}

					}

				}

				//add cells for actions
				if (isset($options['action'])){
					foreach ($options['action'] as $action=>$link){
						if ($action == "visit"){
							$data_row .= "	<td align='center'><a href='{$link}'>$action</a></td>".PHP_EOL;
						}else{
							$data_row .= "	<td align='center'><a class='{$action}' href='{$link}{$primary_key}'>$action</a></td>".PHP_EOL;
						}
					}
				}

				//terminate data row
				$data_row .= "</tr>".PHP_EOL;

			}

		}

		//assemble the table
		if(!empty($head_row)){
			$output = "<div class='cf-table'>
			<table class='$table_class' border=0>
			<thead>
			$head_row
			$filter_row
			</thead>
			<tbody>
			$data_row
			</tbody>
			<tfoot>
			</tfoot>
			</table>
			</div>
			<p>&nbsp;</p>";
		}

		return $output;
	}

	//<img class="icon" id="icon_print" src="theme/images/icon_print.png" border=0 />
	function add_toolbar_icon($icon, $href, $title){

		$link = <<<EOD
	<li><a href="{$href}"><img id="icon_{$icon}" src="theme/images/icon_{$icon}.png" border=0 title="{$title}"/></a></li>
EOD;

		return $link;

	}

	function get_restricted_list(){

		$output = array();

		$query = "SELECT * FROM auth_content ORDER BY content_pk";
		//if ($result = mysql_query($query, $this->conn)){
		if ( $result = $this->db->query($query)){
			//while ($row = mysql_fetch_assoc($result)){
			while( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
				$output[$row['content_pk']] = $row['content_url'];
			}
		}else{
			$_SESSION['sysmsg'] = "WARNING: could not read auth_content";
		}

		return $output;
	}

	function url_is_restricted(){

		$list = $this->get_restricted_list();

		if (count($list)){
			foreach($list as $url){
				//if a restricted url appears in the current request_uri, the url is restircted
				if(strpos($_SERVER['REQUEST_URI'], $url) !== false){
					return true;
				}
			}

		}

		return false;
	}

	function get_user_by_token($token){

		if (!empty($token)){
			$token = mysql_escape_string($token);
			$query = "SELECT * FROM auth_user
			WHERE user_token = '$token' AND user_active = 1";
			if ($result = mysql_query($query, $this->conn)){
				if ($row = mysql_fetch_assoc($result)){
					return $row;
				}
			}else{
				$_SESSION['sysmsg'] = mysql_error($this->conn);
			}
		} //make sure not to get a row with an empty token

		return false;
	}

	//array of which urls the user has access to
	function get_acl(){

		$output = array();
		$user_id = (int)$this->user['user_id'];

		$query = "SELECT content_url FROM auth_content
		JOIN auth_acl ON auth_acl.content_pk = auth_content.content_pk
		WHERE auth_acl.user_id = $user_id
		AND auth_acl.acl_level = 1 ";
		//echo $query;
		if ($result = mysql_query($query, $this->conn)){
			while ($row = mysql_fetch_assoc($result)){
				$output[] = $row['content_url'];
			}
		}else{
			$_SESSION['sysmsg'] = mysql_error($this->conn);
			return false;
		}

		return $output;
	}

	function user_is_authorized(){

		//check the current URI against the acl
		if (count($this->acl)){
			foreach($this->acl as $url){
				//if an authorized url appears in the current request_uri, it's ok
				if(strpos($_SERVER['REQUEST_URI'], $url) !== false){
					return true;
				}
			}
		}

		return false;
	}

	function get_user_control(){
		if ($this->user){
			return '<p>'.$this->user['user_name'].'<br><a href="/sign_out/">Sign out</a></p>';
		}
		return '<a id="cf-signin" class="button" href="/sign_in/">Sign in</a>';
	}

	function send_error($file, $desc){

		$notify[] = "cblackham@cc.edu";

		$time = date("Y-m-d H:i:s");

		$msg = "
			<html><body>
				<table style='font-family: Arial, sans-serif; font-size: 12px;' cellpadding='6' cellspacing='0' border='0'>
				<tr>
					<td align='right'><b>Time:</b>  </td><td>{$time}</td>
				</tr>
				<tr>
					<td align='right'><b>File:</b>  </td><td>{$file}</td>
				</tr>
				<tr>
					<td align='right'><b>Exception:</b>  </td><td>{$desc}</td>
				</tr>
				</table>
			</body></html>";

		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->Host = "localhost";
		$mail->From = "noreply@cdx.carrington.edu";
		$mail->FromName = "Carrington CDX";
		foreach ($notify as $email_address){
			$mail->AddAddress($email_address);
		}
		$mail->Subject = "CDX Server Exception Notice";
		$mail->Body = $msg;
		$mail->isHTML(true);

		$mail->Send();

	}

}//end class

?>
