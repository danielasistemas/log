<?

/**
 * home.php
 */
$page_title = "Welcome";

ob_start(); //turn on output buffer so content can be retrieved with ob_get_contents

?>

<div style="width: 90%;">

<img class="align-right" style="padding-top: 45px;" src="theme/images/goldie.png" alt="goldie" />

<h3>Carrington Data Exchange CDX</h3>

<p>
This is the data exchange server for Carrington College.
<br><br>
Most of this server is dedicated to automated maintenance jobs.
<br><br>
</p>
</div>

<?php
$page_content = ob_get_contents();
ob_get_clean();
?>
