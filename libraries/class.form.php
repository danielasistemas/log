<?php

/**
 * Form class
 * @author Craig
 * @version 1.2
 */

class Form extends System{

	public $action;

	public $handle;

	public $maintenance_mode = false;

	public $record_key; //primary key for records

	public $values = array();

	public $rules = array();

	public $session_id;

	public $form; //html form elements

	function add_select($name, $options, $label, $rules){

		//add the rule
		$this->rules[$name] = $rules;

		//set default value
		$default = "";
		if (isset($this->values[$name])) $default = $this->values[$name];
		//echo "$name default: $default<br>";
		//set class of field based on rules for validation
		$validation = $validation = $this->get_validation($rules, $name);

		//set required asterisk if required field
		$required_indicator = "&nbsp;";
		if ( isset($rules['required']) ) $required_indicator = "*";

		$select = <<<EOD
		<select  name="{$name}" id="{$name}" style="width: 218px;" class="{$validation['class']}">
		<option value="">None</option>
EOD;
		foreach ($options as $value=>$text){
			$sel = ""; //pre-select of name OR value
			if ($text == $default || $value == $default) $sel = "selected";
			if (count($options) == 1) $sel = "selected"; //only one option, so select automatically
			$select .= "<option $sel value=\"$value\">$text</option>
			";
		}
		$select .= "</select>";

		$this->form .= <<<EOD
		<div id="{$name}div" class="cf-input">
			<label for="{$name}">{$label}<span class="required_indicator">{$required_indicator}</span></label>
			{$select}
		</div>
EOD;

	}

	function add_input($name, $label, $rules){

		$this->rules[$name] = $rules;

		//set value of input
		$default = "";
		if (isset($this->values[$name])) $default = $this->values[$name];

		//set class of field based on rules for validation
		$validation = $this->get_validation($rules, $name);

		//set required asterisk if required field
		$required_indicator = "&nbsp;";
		if ( isset($rules['required']) ) $required_indicator = "*";

		$disabled_state = '';
		if ( isset($rules['disabled']) ) $disabled_state = 'readonly="readonly"';

		$this->form .= <<<EOD
		<div id="{$name}div" class="cf-input">
			<label for="{$name}">{$label}<span class="required_indicator">{$required_indicator}</span></label>
			<input type="text" name="{$name}" id="{$name}" style="width: 210px;" class="{$validation['class']}" {$validation['inline']} value="{$default}" {$disabled_state} />
		</div>
EOD;

	}

	function add_html($string){
		$this->form .= PHP_EOL.$string.PHP_EOL;
	}

	function add_checkbox($name, $label, $value){

		//set value of input
		$is_checked = "";
		if ( isset($this->values[$name]) && $this->values[$name] == $value ) $is_checked = "checked";

		$this->form .= <<<EOD
		<div id="{$name}div" class="cf-input">
				<label for="{$name}">{$label}</label>				
				<input type="checkbox" name="{$name}" id="{$name}" $is_checked value="{$value}" />
		</div>
EOD;
	}

	function add_heading($string){

		$this->form .= <<<EOD
<h3>$string</h3>

EOD;

	}

	function add_password($name, $label, $rules){

		$this->rules[$name] = $rules;

		//set value of input
		$default = "";
		if (isset($this->values[$name])) $default = $this->values[$name];

		//set class of field based on rules for client-side validation
		$validation = "";
		if (count($rules)) $validation = $this->get_validation($rules, $name);

		$this->form .= <<<EOD
		<div id="{$name}div" class="cf-input">
			<label for="{$name}">{$label}</label>
			<input type="password" name="{$name}" id="{$name}" class="{$validation['class']}" {$validation['inline']} value="{$default}" />
		</div>
EOD;

	}

	function add_textarea($name, $label, $rules, $mce=false){

		$this->rules[$name] = $rules;

		$default = "";
		if (isset($this->values[$name])) $default = $this->values[$name];

		//set class of field based on rules for client-side validation
		$validation = $this->get_validation($rules, $name);

		//set required asterisk if required field
		$required_indicator = "&nbsp;";
		if ( isset($rules['required']) ) $required_indicator = "*";

		$tinymce = "";
		if ($mce) $tinymce = "tinymce";

		$this->form .= <<<EOD
		<div id="{$name}div" class="cf-input">
			<label for="{$name}">{$label}<span class="required_indicator">{$required_indicator}</span></label>
			<textarea name="{$name}" id="{$name}" style="width: 210px;" class="{$validation['class']} $tinymce">{$default}</textarea>
		</div>
EOD;

	}

	function add_submit($value = "Submit"){

		$this->form .= <<<EOD
		<div class="cf-input">
			<input type="submit" class="button" id="form_submit" name="form_submit" default="1" value="$value" alt="$value" />
		</div>
EOD;

	}

	public function show_form(){

		$timestamp = time();
		$this->session_id = session_id();

		if ($this->maintenance_mode){

			$output = <<<EOD
			<p>
			<img class="align-right" src="theme/images/goldie.png" alt="goldie" />
			<b>We're sorry...</b> This resource is temporarily undergoing maintenance.
			<br>Please try back in a few minutes.
			</p>
EOD;

		}else{

			//set the rules for the form in a cookie
			setcookie($this->handle."[rules]", serialize($this->rules), time()+3600);

			//set the rules as a hidden variable that gets posted
			$rules = serialize($this->rules);
			//replace double quotes with single
			$rules = str_replace("\"","'",$rules);

			$custom_javascript = '';
			if (file_exists($_SERVER['DOCUMENT_ROOT']."/js/".$this->handle.".js")){
				$custom_javascript = '<script src="/js/'.$this->handle.'.js" type="text/javascript"></script>';
			}

			//pre-populate the high_school id if set in a cookie
			$high_school_id = "";
			if ( isset($this->values['high_school_id']) ) $high_school_id = $this->values['high_school_id'];

			$output = <<<EOD
			$custom_javascript
			<form action="{$this->action}" name="form1" id="form1" class="{$this->handle}" method="post">
			<div id="script_div">
			<p>You must enable client-side scripting (JavaScript) for this form.</p>
			<p>For instructions on how to enable scripting, click <a target="_blank" href="http://support.microsoft.com/gp/howtoscript">here</a>.
			</div>

			<div id="form_div" style="display: none;">

				<input type="hidden" name="timestamp" value="$timestamp" />
				<input type="hidden" name="session" id="session" value="$this->session_id" />
				<input type="hidden" name="form_handle" value="$this->handle" />
				<input type="hidden" name="record_key" value="$this->record_key" />
				<input type="hidden" name="rules" value="$rules" />

				$this->form

			</div>

			</form>
EOD;

		}

		return $output;

	}

	//gets validation for the jquery validate library
	function get_validation($rules, $field){

		$validation['class'] = "";
		$validation['inline'] = "";

		if (isset($rules['required'])) $validation['class'] .= "required ";
		if (isset($rules['digits'])) $validation['class'] .= "digits ";
		if (isset($rules['email'])) $validation['class'] .= "email ";
		if (isset($rules['noemail'])) $validation['class'] .= "noemail ";
		if (isset($rules['date'])) $validation['class'] .= "datepicker ";
		if (isset($rules['disabled'])) $validation['class'] .= "disabled ";

		if (isset($_SESSION[$this->handle]['error'][$field])) $validation['class'] .= "error ";

		if (isset($rules['min'])) $validation['inline'] .= 'minlength="'.$rules['min'].'" ';
		if (isset($rules["max"])) $validation['inline'] .= 'maxlength="'.$rules['max'].'" ';

		$validation['class'] = trim($validation['class']);

		return $validation;
	}

	function get_states(){
		$states_arr = array('AL'=>"Alabama",'AK'=>"Alaska",'AZ'=>"Arizona",'AR'=>"Arkansas",'CA'=>"California",
		'CO'=>"Colorado",'CT'=>"Connecticut",'DE'=>"Delaware",'DC'=>"District Of Columbia",'FL'=>"Florida",
		'GA'=>"Georgia",'HI'=>"Hawaii",'ID'=>"Idaho",'IL'=>"Illinois", 'IN'=>"Indiana", 'IA'=>"Iowa",
		'KS'=>"Kansas",'KY'=>"Kentucky",'LA'=>"Louisiana",'ME'=>"Maine",'MD'=>"Maryland", 'MA'=>"Massachusetts",
		'MI'=>"Michigan",'MN'=>"Minnesota",'MS'=>"Mississippi",'MO'=>"Missouri",'MT'=>"Montana",'NE'=>"Nebraska",
		'NV'=>"Nevada",'NH'=>"New Hampshire",'NJ'=>"New Jersey",'NM'=>"New Mexico",'NY'=>"New York",
		'NC'=>"North Carolina",'ND'=>"North Dakota",'OH'=>"Ohio",'OK'=>"Oklahoma", 'OR'=>"Oregon",'PA'=>"Pennsylvania",
		'RI'=>"Rhode Island",'SC'=>"South Carolina",'SD'=>"South Dakota",'TN'=>"Tennessee",'TX'=>"Texas",'UT'=>"Utah",
		'VT'=>"Vermont",'VA'=>"Virginia",'WA'=>"Washington",'WV'=>"West Virginia",'WI'=>"Wisconsin",'WY'=>"Wyoming");

		return $states_arr;
	}

	function get_years(){

		$output = array();
		$current_year = date("Y");

		for ( $year = ($current_year + 4); $year >= ($current_year - 60); $year-- ){
			$output[$year] = $year;
		}

		return $output;
	}

	function get_months(){

		$output = array(1=>'January', 2=>'February', 3=>'March', 4=>'April',
		5=>'May', 6=>'June', 7=>'July', 8=>'August', 9=>'September',
		10=>'October', 11=>'November', 12=>'December');

		return $output;
	}

	function simplify_array($array, $index){

		$output = array();

		foreach($array as $key=>$sub_array){
			$output[$key] = $sub_array[$index];
		}

		return $output;
	}



}//end class

?>
