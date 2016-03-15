<?php

class License extends System {

	/*** properties ***/
	public $db; //database connection (should be local db)


	/*** public functions ***/

	function get_license_list(){

		$output = array();

		$query = "SELECT *
		FROM incontact_licenses";
		if ( $result = $this->db->query($query)){
			//if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
			while ($row = $result->fetchObject()) {
				//var_dump($row);
				$output[$row->pk] = (array) $row;
			}

		}else{
			$_SESSION['sysmsg'] = "ERROR: could not get list";
		}

		return $output;

	}

	function get_license_record($pk){
		$pk = (int) $pk;
		$output = array();

		$query = "SELECT *
		FROM incontact_licenses
		WHERE pk = $pk";
		if ( $result = $this->db->query($query)){
			if ( $row = $result->fetch(PDO::FETCH_ASSOC)) {
				//var_dump($row);
				$output = $row;
			}
		}else{
			$_SESSION['sysmsg'] = "ERROR: could not get record $pk";
		}

		return $output;
	}

	function write_record($data){
		//var_dump($data);

		foreach($data as $var=>$value){
			if (!is_array($value)){
				$clean_string = trim($value);
				$clean_string = strip_tags($clean_string);
				//$_SESSION[$this->form_handle]['value'][$var] = $clean_string;
				//$this->form_values[$var] = $clean_string;
				${$var} = $this->db->quote($clean_string); //for ease - create varible of the name value eg $firstname instead of $this->form_values['firstname']
			}
		}

		//force computer name to upper case
		$computer_name = strtoupper($computer_name);

		//update record
		$query = "UPDATE incontact_licenses SET
		user_name = $user_name,
		computer_name = $computer_name,
		station_id = $station_id,
		caller_id = $caller_id,
		location = $location,
		status = $status
		WHERE pk = $record_key";
		//echo "<p>$query</p>";
		if ( $result = $this->db->query($query)){
			return true;
		}

		return false;

	}

} //end class

?>
