<?php

die("DISABLED");
error_reporting(E_ALL);
require_once("minify.php");
$oMin = new Minify(TYPE_JS);

$aJs = array();

// jquery
$aJs[] = file_get_contents(realpath('../jsfwk/jquery/core+ui/development-bundle/jquery-1.5.1.js'));

// plugins
$aJs[] = file_get_contents(realpath('../jsfwk/jquery/plugins/jquery.bind.js'));
$aJs[] = file_get_contents(realpath('../jsfwk/jquery/plugins/jquery.betterjson.js'));
$aJs[] = file_get_contents(realpath('../jsfwk/jquery/plugins/jquery.cookie.js'));
$aJs[] = file_get_contents(realpath('../jsfwk/jquery/plugins/jquery.inherit.js'));

//$aJs[] = file_get_contents(realpath('../jsfwk/json/json.js'));

// framework
$aJs[] = file_get_contents(realpath('../jsfwk/formidable/formidable.jquery.js'));
$aJs[] = file_get_contents(realpath('../jsfwk/framework.js'));

header("Content-Type: text/javascript;charset=utf-8");

$sNotice =<<<NOTICE
/*
	NOTE: THIS IS MINIFIED VERSION OF FORMIDABLE JS
	For regular set typoscript: config.tx_ameosformidable.minify.enabled=0
*/
NOTICE;
if(isset($_GET) && is_array($_GET) && array_key_exists("plain", $_GET) && $_GET["plain"] == 1) {
	echo implode($aJs, "");
} else {
	echo $sNotice . $oMin->minifyJS(implode($aJs, ""));
}

exit;

?>
