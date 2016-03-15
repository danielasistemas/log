<?php

/**
 * layout.php the html frame that all content goes into
 *
 **/

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html lang="en">
<head>
	<title>Carrington Data Exchange <?=$page_title?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<meta http-equiv="cache-control" content="max-age=0">
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="Expires" content="Tue, 01 Jan 1995 12:12:12 GMT">
	<meta http-equiv="Pragma" content="no-cache">
	<base href="http://<?=$_SERVER['HTTP_HOST']?>">
	<link rel="shortcut icon" type="image/ico" href="http://<?=$_SERVER['HTTP_HOST']?>/favicon.ico">
	<link rel="stylesheet" type="text/css" href="theme/css/main.css">
	<link rel="stylesheet" type="text/css" media="print" href="theme/css/print.css">
	<link rel="stylesheet" type="text/css" href="theme/css/jquery-ui.css">
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/jquery-ui.js"></script>
	<script type="text/javascript" src="js/jquery.validate.js"></script>
	<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
	<script type="text/javascript" src="js/autoload.js"></script>
</head>
<body>

<div id="cf-header">
	<a href="http://<?=$_SERVER['HTTP_HOST']?>/"><img id="logo" src="theme/images/home-page-logo.png" border="0"/></a>
	<span id="cf-user">
	 &nbsp;
	</span>
</div>

<div id="cf-topnav">
	<div>
		<ul>
			<?=$main_menu?>
		</ul>
	</div>
</div>

<div id="cf-appbar">
	<h1> <?=$page_title?> </h1>
	<span class="message"><?=$system_message?></span>
	<ul>
	 <?=$toolbar_icons?>
	 <li><img id="icon_print" src="theme/images/icon_print.png" border=0 /></li>
	</ul>
</div>

<div id="cf-main">

	<div id="cf-sidebar">
		<?=$side_bar?>
	</div> <!-- cf-sidebar -->

	<div id="cf-content">
		<?=$page_content?>
	</div> <!-- cf-content -->

	<div id="cf-footer">
		<p>Copyright &copy; <?=date("Y")?> Carrington Colleges Group, Inc.</p>
	</div> <!-- cf-footer -->

</div> <!-- cf-main -->

</body>
</html>
