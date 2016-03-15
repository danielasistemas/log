<?php
/**
 * noauth.php
 */

$page_title = "Sign In";

$side_bar = "&nbsp;";

require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.form.php");

//process post if present
if($_POST){

	if($user = $system->get_user_by_token($_POST['token'])){

		setcookie("report_token", $_POST['token'], time()+60*60*24*10, "/");

		$redir = $_POST['referrer'];
		header("location: $redir");
		exit;
	}

	$_SESSION['sysmsg'] = "ERROR: user not found";
	$system->redirect("/sign_in/");

}


?>

<div style="width: 720px;">
	<img class="align-right" src="theme/images/blue_notice.png" alt="goldie" />
	Please enter your access token to sign in.<br>
<br/>
<br/>

<?php

//instantiate object
$o_form = new Form();

//set the form action
$o_form->action = $_SERVER['REQUEST_URI'];

$o_form->handle = "sign_in";

$o_form->add_input("token", "Access Token", array("required"=>1));

$o_form->add_submit("Submit");

echo $o_form->show_form();

?>

</div>

<script type="text/javascript">

	document.form1.token.focus();

</script>

<?php

$page_content = ob_get_contents();
ob_get_clean();

?>
