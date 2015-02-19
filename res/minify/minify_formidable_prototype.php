<?php

die("DISABLED");
error_reporting(E_ALL);
require_once("minify.php");
$oMin = new Minify(TYPE_JS);

$aJs = array();
$aJs[] = file_get_contents(realpath('../jsfwk/prototype/prototype.js'));
#$aJs[] = file_get_contents(realpath('../jsfwk/prototype/addons/lowpro/lowpro.js'));
$aJs[] = file_get_contents(realpath('../jsfwk/prototype/addons/base/Base.js'));
$aJs[] = file_get_contents(realpath('../jsfwk/json/json.js'));
$aJs[] = file_get_contents(realpath('../jsfwk/formidable/formidable.prototype.js'));
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
