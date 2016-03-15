<?

//parser class

class Parser extends System{

	function date_to_iso($date){

		if ( $timestamp = strtotime($date) ){
			if ( $iso_date = date("Y-m-d", $timestamp) ){
				return $iso_date;
			}
		}

		return false;
	}

	function datetime_to_iso($date){

		if ( $timestamp = strtotime($date) ){
			if ( $iso_date = date("Y-m-d H:i:s", $timestamp) ){
				return $iso_date;
			}
		}

		return false;
	}

	function program_is_core($program_id){

		$program_id = (int) $program_id;

		$core_array = array(1,11,25,26,27,30,31,32,33,34,36,39,41,43,48,52,53,65,66,68,69,70,71,73,74,75);

		if ( in_array($program_id, $core_array) ) return true;

		return false;
	}

} //end class
?>
