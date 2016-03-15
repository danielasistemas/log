<?php
/**
 * noauth.php
 */

$page_title = "Not Authorized";

//$side_bar = "&nbsp;";

?>

<div style="width: 690px;">
	<img class="align-right" src="theme/images/goldie_sly.png" alt="goldie" />
	<br><br>
	Your account is not authorized to view this content.<br>
	<br>
	If you feel that this is in error, please contact an administrator.
</div>

<?php

$page_content = ob_get_contents();
ob_get_clean();

?>
