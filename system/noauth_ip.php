<?php
/**
 * noauth_ip.php
 */

$page_title = "Address Not Authorized";

$side_bar = "&nbsp;";

?>

<div style="width: 90%;">
	<img class="align-right" src="theme/images/goldie_sly.png" alt="goldie" />
	<b>We're sorry...</b> Your IP address (<?php echo $_SERVER['REMOTE_ADDR']; ?>)
	is not authorized to access this content or perform this action.<br>
</div>

<?php
$page_content = ob_get_contents();
ob_get_clean();
?>
