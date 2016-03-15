<?php

class Api {

	/*** properties ***/
	public $db_name; //database name

	public $params; //parameters passed on the url as an array

	/*** public functions ***/
	function db_connect(){

		$db = new PDO("mysql:host=localhost;dbname=".$this->db_name, "cronuser", "5AyehePh");

		if ( $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION) ){
			return $db;
		}

		return false;
	}

	function send_error($description){

		$notify[] = "cblackham@cc.edu";
		$notify[] = "dschneider@cc.edu";

		$time = date("Y-m-d H:i:s");

		$msg = "
			<html><body>
				<table style='font-family: Arial, sans-serif; font-size: 12px;' cellpadding='6' cellspacing='0' border='0'>
				<tr>
					<td align='right'><b>Time:</b>  </td><td>{$time}</td>
				</tr>
				<tr>
					<td align='right'><b>IP:</b>  </td><td>{$_SERVER['REMOTE_ADDR']}</td>
				</tr>
				<tr>
					<td align='right'><b>Exception:</b>  </td><td>{$description}</td>
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

		$result = $mail->Send();
		return $result;

	}

	function xml_to_html($xml){
		$xml = str_replace("<", "&lt;", $xml);
		$xml = str_replace(">", "&gt;", $xml);
		return $xml;		
	}

	function get_velocify_report_results($xml){

		$url = 'http://service.velocify.com/ClientService.asmx';

		$headers = array(
			"Content-type: text/xml;charset=utf-8",
			"Content-Length: ".strlen($xml),
			"SOAPAction: https://service.leads360.com/GetReportResults", 
		);		

		//echo "xml sent: <textarea>$xml</textarea><br>";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		curl_close($ch);

		if( $result ){
			return $result;
		}

		$dump = $this->xml_to_html($xml);
		$this->send_error("No result for '$dump'");
		return false;
	}

	function parse_xml_response($xml){

		//die("raw response: <br><textarea cols=90 rows=30>$xml</textarea>");
		$output = array();

		//grab the result out of the response
		if ( $start = strpos($xml, "<ReportResults") ){
			$result_index = 0; //initialize the index
			$end = strpos($xml, "</ReportResults>") + strlen("</ReportResults>"); //add for length of string
			$length = $end - $start;
			$sub_xml = substr($xml, $start, $length);

			$obj = simplexml_load_string($sub_xml);
			//var_dump($obj);
			foreach ($obj->Result as $result){
				//create a node for multi-dimensional array to accomodate multiple search results
				$output[$result_index] = array();
				//var_dump($result);
				$result_array = (array) $result;
				foreach ($result_array as $index=>$value){
					//convert data to ideal index/value pairs
					if ( $index == "ImportantNote" ) $value = (string) $value;
					if ( $index == "DateAdded" ) $value = str_replace("T", " ", $value);
					if ( $index == "College_x002F_CampusofInterest" ) $index = "Campus";
					if ( $index == "ProgramofInterest" ) $index = "Program";
					if ( $index == "Id" ) $index = "LeadID";
					if ( strpos($index, "hone") ) $value = substr($value,0,10); //for Phone1, 2, 3

					$output[$result_index][$index] = trim($value);
				}
				$result_index++ ; //increment the index
			}

			//var_dump($output);
			if ( count($output) )	return $output;
		}
		
		$dump = $this->xml_to_html($xml);
		$this->send_error("Could not parse xml '$dump'");
		return false;
	}

	function create_velocify_report_xml($report_id, $field_title, $search_value){
		//971 - report for phone #
		//77866 - filteritemid for phone #
		//4692162190 - sample phone #
		$report_id = (int) $report_id;
		$filter_item_id = (int) $filter_item_id;
		$search_value = trim($search_value);
	
		$xml = <<<EOD
<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:ser="https://service.leads360.com">
   <soap:Header/>
   <soap:Body>
      <ser:GetReportResults>
         <ser:username>webservice@carrington.edu</ser:username>
         <ser:password>W3bS3rv1c3</ser:password>
         <ser:reportId>{$report_id}</ser:reportId>
         <ser:templateValues><ser:FilterItems xmlns="http://service.leads360.com"><ser:FilterItem FieldTitle="{$field_title}" Operator="Contains">{$search_value}
         </ser:FilterItem></ser:FilterItems></ser:templateValues>
      </ser:GetReportResults>
   </soap:Body>
</soap:Envelope>
EOD;

		return $xml;
	}

	function get_leads_by_phone($phone){
		$output = array();

		//phone should be integer so strip out all the other stuff
		$phone = trim($phone);
		$phone = str_replace('(', '', $phone);
		$phone = str_replace(')', '', $phone);
		$phone = str_replace('-', '', $phone);
		$phone = str_replace('.', '', $phone);
		$phone = str_replace(' ', '', $phone);
		//$phone = (int) $phone;
	
		//$report_id = 971; //report for phone1
		//$filter_item_id = 78456; //filteritemid
	
		//$report_id = 972; //report for phone2
		//$filter_item_id = 78404; //filteritemid 

		//$report_id = 973; //report for phone3
		//$filter_item_id = 78405; //filteritemid

		$arr = array(971=>'Phone 1',972=>'Phone 2',973=>'Phone 3');
		foreach ($arr as $report_id=>$field_title){
			//get the post xml
			if( $xml = $this->create_velocify_report_xml($report_id, $field_title, $phone) ){
				//send the xml to velocify and get the response
				if ( $response = $this->get_velocify_report_results($xml) ){
					//convert the response to an array
					//die("<textarea cols=90 rows=30>$response</textarea>");
					if ( $result = $this->parse_xml_response($response) ){
						if ( count($result) ){
							//return $result;
							$output[] = $result;
						}
					}
				}
			}
		} //end foreach

		return $output;
	}

	function get_leads_by_lastname($lastname){

		//$foo = $this->get_velocify_report_list();
		//die("<textarea cols=90 rows=30>$foo</textarea>");

		$report_id = 970; //report for lastname
		$filter_item_id = 78410; //filteritemid for lastname
	
		//get the post xml
		if( $xml = $this->create_velocify_report_xml($report_id, $filter_item_id, $lastname) ){
			//send the xml to velocify and get the response
			if ( $response = $this->get_velocify_report_results($xml) ){
				//convert the response to an array
				//die("<textarea cols=90 rows=30>$response</textarea>");
				if ( $result = $this->parse_xml_response($response) ){
					if ( count($result) ){
						return $result;
					}
				}
			}
		}
	
		return false;
	}

	function store_sms_data($data){

		if ( count($data) ){

			//var_dump($data);
			//convert and escape the data
			$lead_id = (int) $data['lead_id'];
			$campus = $this->db->quote($data['campus']);
			$appointment = $this->db->quote( date("Y-m-d H:i:s", strtotime($data['date'])) );
			$sms_phone = $this->db->quote($data['sms_phone']);
			$phone1 = $this->db->quote($data['phone1']);
			$phone2 = $this->db->quote($data['phone2']);
			$phone3 = $this->db->quote($data['phone3']);

			//write to database
			$query = "INSERT INTO cdx_sms_log
			(lead_id, campus_name, appointment, sms_phone, phone1, phone2, phone3)
			VALUES
			($lead_id, $campus, $appointment, $sms_phone, $phone1, $phone2, $phone3);";

			//$this->display($query);
			if ($result = $this->db->query($query) ){
				return $this->db->lastInsertId();
			}

		}

		return false;
	}

	function flag_sms_sent($insert_id){

		$insert_id = (int) $insert_id;

		$query = "UPDATE cdx_sms_log SET sent = 1 WHERE pk = $insert_id;";
		if ($result = $this->db->query($query) ){
			return true;
		}

		return false;
	}

	function get_api_result($params){

		$api_base_url = 'http://report.carrington.edu/api';
		$url = $api_base_url.$params;
		//echo "<p>url: $url</p>";
		$sess = curl_init($url);
		curl_setopt($sess, CURLOPT_TIMEOUT, 24);
		curl_setopt($sess, CURLOPT_HEADER, false);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($sess);
		curl_close($sess);

		//convert json response to array
		$result = (array) json_decode($result);

		return $result;
	}

	function get_campus_by_name($campus_name){

		$campus_name = $this->db->quote($campus_name);

		$query = "SELECT campus_name, phone, street, city, state, zip
		FROM campus WHERE campus_name = $campus_name";
		if ( $result = $this->db->query($query) ){
			if ( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
				return $row;
			}else{
				$this->send_error("Campus Not found for '$campus_name'");
			}
		}

		return false;
	}

	function create_sms_xml($data, $send_schedule){

		if ( $campus = $this->get_campus_by_name($data['campus']) ){

			$lead_id = (int) $data['lead_id'];
	
			//set the phone number to text
			$data['sms_phone'] = "(602) 688-4058"; //for testing
			//$data['phone1'] = "(602) 688-4058"; //for testing
			$now = date("m.d H:i:s");
			$message = 'Appt Reminder '.$data['date'].' Carrington College '.$campus['street'].' '.$campus['city'].', '.$campus['state'].' '.$campus['zip'].' '.$campus['phone'].' Reply STOP to stop or HELP for help';
			

			$xml = '<?xml version="1.0" encoding="utf-8"?>
			<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
			<soap:Body>
				<ScheduleMessageToFirstValid xmlns="http://api.textlane.com/">
					<ApiKey>0f87b192-72a9-4659-984d-ecb220952f13</ApiKey>
					<SubAccountID>9620f4c3-a41f-4f19-b7cb-79a266e1a6c0</SubAccountID>
					<Destination>
						<string>'.$data['sms_phone'].'</string>
						<string>'.$data['phone1'].'</string>
						<string>'.$data['phone2'].'</string>
						<string>'.$data['phone3'].'</string>
					</Destination>
					<Message>'.$message.'</Message>
					<ScheduledDate>'.$send_schedule.'</ScheduledDate>
					<ResponderID>c4e06b08-104f-4282-8d9d-ebf01b5eb15e</ResponderID>
					<ExternalID>'.$lead_id.'</ExternalID>
				</ScheduleMessageToFirstValid>
			</soap:Body>
			</soap:Envelope>';
	
			return $xml;
		}

		return false;
	}

	function send_sms_post($xml){

		//note: scheduled send can always be used since a schedule in the past will be sent immediately
		if ( strlen($xml) ){

			$headers = array(
				"Content-type: text/xml;charset=utf-8",
				"Content-Length: ".strlen($xml),
				"SOAPAction: http://api.textlane.com/ScheduleMessageToFirstValid", 
			);		
		
			$url = "https://api.shortcoderouter.com/services/api.asmx";

			$sess = curl_init($url);
			curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($sess, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($sess, CURLOPT_TIMEOUT, 20);
			curl_setopt($sess, CURLOPT_POST, true);
			curl_setopt($sess, CURLOPT_HTTPHEADER, $headers);
			$result = curl_exec($sess);
			curl_close($sess);

			if( $result ){
				return $result;
			}

		}

		return false;
		
	}

	function get_velocify_report($report_id){

		if ( isset($report_id) && is_numeric($report_id) ){

			$url = "http://service.leads360.com/clientservice.asmx/GetReportResultsWithoutFilters";

			$sess = curl_init($url);
			curl_setopt($sess, CURLOPT_HEADER, false);
			curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($sess, CURLOPT_POSTFIELDS, "username=webservice@carrington.edu&password=W3bS3rv1c3&reportId=$report_id");
			$result = curl_exec($sess);
			curl_close($sess);

			if( $result ){
				return $result;
			}

		}

		return false;
	}

	function get_velocify_report_list(){

		$url = "http://service.leads360.com/clientservice.asmx/GetReports";

		$sess = curl_init($url);
		curl_setopt($sess, CURLOPT_HEADER, false);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sess, CURLOPT_POSTFIELDS, "username=webservice@carrington.edu&password=W3bS3rv1c3");
		$result = curl_exec($sess);
		curl_close($sess);

		if( $result ){
			return $result;
		}

		return false;
	}

	function get_lead_by_id($lead_id){

		$url = "http://service.leads360.com/clientservice.asmx/GetLead";

		$sess = curl_init($url);
		curl_setopt($sess, CURLOPT_HEADER, false);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sess, CURLOPT_POSTFIELDS, "username=webservice@carrington.edu&password=W3bS3rv1c3&leadId=$lead_id");
		$result = curl_exec($sess);
		curl_close($sess);

		return $result;

	}


} //end class

?>
