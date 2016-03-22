<?php
//http://log.daniela.local/exception.php?server=cdx&file=hola.php&line=50


if(isset($_GET['server'])&&$_GET['server'])
{
	echo htmlspecialchars($_GET['server']);
}

if(isset($_GET['file'])&&$_GET['file'])
{
	echo htmlspecialchars($_GET['file']);
}

if(isset($_GET['line'])&&$_GET['line'])
{
	echo htmlspecialchars($_GET['line']);
}


?>