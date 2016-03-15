<?
/**
 * notfound.php
 */
$page_title = "Page Not Found";

$side_bar = "&nbsp;";

//strip the .php (if any) from the not found resource
$not_found = str_replace(".php", "", $not_found);

//turn on output buffer so content can be retrieved with ob_get_contents
ob_start();

?>

<img class="align-right" src="theme/images/squirrel.png" alt="squirrel" />
<p style="width: 600px;">
	<b>We're sorry...</b> The content you are trying to load (<?=$not_found?>) can not be found.<br>
	<br>
	If you feel that this is in error, please contact an administrator.
</p>

<?php
$page_content = ob_get_contents();
ob_get_clean();
?>
