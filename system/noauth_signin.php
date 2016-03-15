<?php
/**
 * noauth.php
 */

$page_title = "Sign in Required";

//$side_bar = "&nbsp;";

?>

<div style="width: 690px;">
	<img class="align-right" src="theme/images/goldie_sly.png" alt="goldie" />
	<br><br>
	You must be signed in to view this content.<br>
	<br>
	Please click <a href="/sign_in/">here</a> to sign in.
</div>

<?php

$page_content = ob_get_contents();
ob_get_clean();

?>
