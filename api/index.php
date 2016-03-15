<?php

/**
 * index.php api index
 * proposed use is display documentation for using the api
 *
 * @author Craig
 */

require_once($_SERVER['DOCUMENT_ROOT']."/libraries/class.api.php");

$api = new Api();
$methods = get_class_methods($api);
//var_dump($methods);

$list = "";
foreach ($methods as $method) {
	$list .= "<li>$method</li>";
}

?>

<h3>API Request URLS</h3>

<ul>
	<li>Leads Search <span class="url">/leads/</span></li>
	<ul>
		<li>Leads by Last Name <span class="url">/leads/lastname/smith/</span></li>
	</ul>
</ul>

<h3>API Class Methods</h3>

<ul>
	<?php echo $list ?>
</ul>
