<?php

class Admin extends System {

	public $conn;

	/*** ip address methods ***/
	function get_ip_addresses(){

		$output = array();

		$query = "SELECT * FROM auth_address";
		if ($result = mysql_query($query, $this->conn)){
			while ($row = mysql_fetch_assoc($result)){
				$output[$row['auth_pk']] = $row;
			}
			return $output;
		}else{
			$_SESSION['sysmsg'] = mysql_error($this->conn);
		}

		return false;
	}

	function get_ip_address($auth_pk){

		$auth_pk = (int)$auth_pk;
		$output = array();

		$query = "SELECT * FROM auth_address WHERE auth_pk = $auth_pk";
		if ($result = mysql_query($query, $this->conn)){
			while ($row = mysql_fetch_assoc($result)){
				$output[$row['auth_pk']] = $row;
			}
		}else{
			$_SESSION['sysmsg'] = mysql_error($this->conn);
		}

		return $output;
	}

	function update_ip_address($data){

		$data = $this->clean_data($data);
		$query = "UPDATE auth_address SET
		auth_ip = '{$data['auth_ip']}',
		auth_note = '{$data['auth_note']}'
		WHERE auth_pk = {$data['record_key']}";
		if ($result = mysql_query($query, $this->conn)){
			return true;
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}

	function insert_ip_address($data){

		$data = $this->clean_data($data);

		$query = "INSERT INTO auth_address
		(auth_ip, auth_note)
		VALUES
		('{$data['auth_ip']}', '{$data['auth_note']}')";
		if ($result = mysql_query($query, $this->conn)){
			return mysql_insert_id($this->conn);
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}

	function delete_ip_address($auth_pk){

		$auth_pk = (int)$auth_pk;
		$query = "DELETE FROM auth_address WHERE auth_pk = $auth_pk";
		if ($result = mysql_query($query, $this->conn)){
			return true;
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}

	/*** content methods ***/
	function get_contents(){

		$output = array();

		$query = "SELECT * FROM auth_content ORDER BY content_pk";
		if ($result = mysql_query($query, $this->conn)){
			while ($row = mysql_fetch_assoc($result)){
				$output[$row['content_pk']] = $row;
			}
			return $output;
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}

	function get_content($content_pk){

		$content_pk = (int)$content_pk;
		$output = array();

		$query = "SELECT * FROM auth_content WHERE content_pk = $content_pk";
		if ($result = mysql_query($query, $this->conn)){
			while ($row = mysql_fetch_assoc($result)){
				$output[$row['content_pk']] = $row;
			}
		}else{
			$_SESSION['sysmsg'] = mysql_error($this->conn);
		}

		return $output;
	}

	function update_content($data){

		$data = $this->clean_data($data);
		$query = "UPDATE auth_content SET
		content_label = '{$data['content_label']}'
		content_url = '{$data['content_url']}'
		WHERE content_pk = {$data['record_key']}";
		if ($result = mysql_query($query, $this->conn)){
			return true;
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}

	function insert_content($data){

		$data = $this->clean_data($data);
		$query = "INSERT INTO auth_content
		(content_label, content_url)
		VALUES
		('{$data['content_label']}', '{$data['content_url']}')";
		if ($result = mysql_query($query, $this->conn)){
			return mysql_insert_id($this->conn);
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}

	function delete_content($content_pk){

		$content_pk = (int)$content_pk;
		$query = "DELETE FROM auth_content WHERE content_pk = $content_pk";
		if ($result = mysql_query($query, $this->conn)){
			$query = "DELETE FROM auth_acl WHERE content_pk = $content_pk";
			if ($result = mysql_query($query, $this->conn)){
				return true;
			}
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}


	/*** user methods ***/
	function get_users(){

		$output = array();

		$query = "SELECT * FROM auth_user";
		if ($result = mysql_query($query, $this->conn)){
			while ($row = mysql_fetch_assoc($result)){
				$output[$row['user_id']] = $row;
			}
			return $output;
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}

	function get_user($user_id){

		$user_id = (int)$user_id;
		$output = array();

		$query = "SELECT * FROM auth_user WHERE user_id = $user_id";
		if ($result = mysql_query($query, $this->conn)){
			if ($row = mysql_fetch_assoc($result)){
				$row['user_acl'] = $this->get_user_acl($row['user_id']);
				$output[$row['user_id']] = $row;
			}
		}else{
			$_SESSION['sysmsg'] = mysql_error($this->conn);
		}

		return $output;
	}

	function update_user($data){

		$data = $this->clean_data($data);

		//this is a checkbox value so it won't be set if not checked
		if(!isset($data['user_active'])) $data['user_active'] = 0;

		$query = "UPDATE auth_user SET
		user_name = '{$data['user_name']}',
		user_token = '{$data['user_token']}',
		user_active = {$data['user_active']}
		WHERE user_id = {$data['record_key']}";
		if ($result = mysql_query($query, $this->conn)){
			return true;
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}

	function insert_user($data){

		$data = $this->clean_data($data);

		//this is a checkbox value so it won't be set if not checked
		if(!isset($data['user_active'])) $data['user_active'] = 0;

		$query = "INSERT INTO auth_user
		(user_name, user_token, user_active)
		VALUES
		('{$data['user_name']}', '{$data['user_token']}', {$data['user_active']})";
		if ($result = mysql_query($query, $this->conn)){
			return mysql_insert_id($this->conn);
		}

		$_SESSION['sysmsg'] = mysql_error($this->conn);
		return false;
	}

	/*** ACL methods ***/
	function get_user_acl($user_id){

		$output = array();
		$user_id = (int)$user_id;

		$query = "SELECT auth_content.content_pk, IFNULL(auth_acl.acl_level, '0') AS acl_level
		FROM auth_content
		LEFT JOIN auth_acl ON auth_acl.content_pk = auth_content.content_pk
		AND auth_acl.user_id = $user_id ";
		//echo $query;
		if ($result = mysql_query($query, $this->conn)){
			while ($row = mysql_fetch_assoc($result)){
				$output[$row['content_pk']] = $row['acl_level'];
			}
		}else{
			$_SESSION['sysmsg'] = mysql_error($this->conn);
			return false;
		}

		return $output;
	}

	function set_user_acl($user_id, $data, $roles){

		$user_id = (int)$user_id;
		$acl_result = true;

		//roles contains all content
		foreach ($roles as $role){

			$content_pk = $role['content_pk'];

			$acl_level = 0; //this is a checkbox value so it won't be set if not checked
			if(isset($data['content_pk_'.$content_pk])) $acl_level = $data['content_pk_'.$content_pk];

			$acl_data = array("user_id"=>$user_id, "content_pk"=>$content_pk, "acl_level"=>$acl_level);

			if ($acl_pk = $this->acl_exists($user_id, $content_pk)){
				$acl_data['acl_pk'] = $acl_pk;
				if (!$this->update_acl($acl_data)) $acl_result = false;
			}else{
				if (!$this->insert_acl($acl_data)) $acl_result = false;
			}

		}

		return $acl_result;
	}

	function acl_exists($user_id, $content_pk){
		$user_id = (int)$user_id;
		$content_pk = (int)$content_pk;

		$query = "SELECT acl_pk FROM auth_acl
		WHERE user_id = $user_id
		AND content_pk = $content_pk; ";

		if ($result = mysql_query($query, $this->conn)){
			if ($row = mysql_fetch_assoc($result)){
				return $row['acl_pk'];
			}
		}

		return false;
	}

	function update_acl($data){

		$query = "UPDATE auth_acl SET
		user_id = {$data['user_id']},
		content_pk = {$data['content_pk']},
		acl_level = {$data['acl_level']}
		WHERE acl_pk = {$data['acl_pk']}";
		//echo "<p>query: $query</p>";
		if ($result = mysql_query($query, $this->conn)){
			return true;
		}

		echo mysql_error($this->conn);
		echo "<p>query: $query</p>";
		return false;
	}

	function insert_acl($data){

		$query = "INSERT INTO auth_acl
		(user_id, content_pk, acl_level)
		VALUES
		({$data['user_id']}, {$data['content_pk']}, {$data['acl_level']})";
		//echo "<p>query: $query</p>";
		if ($result = mysql_query($query, $this->conn)){
			return mysql_insert_id($this->conn);
		}

		echo mysql_error($this->conn);
		echo "<p>query: $query</p>";
		return false;
	}

	//this is an idea in case we want to get more sophisticated with tokens
	function generate_token(){

	}

} //end class

?>
