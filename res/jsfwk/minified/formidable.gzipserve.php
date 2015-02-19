<?php
	header("Content-Type: text/javascript");
	header("Content-Encoding: gzip");
	header("Cache-Control: max-age=86400, public");
	$sFingerPrint = trim($_GET["fingerprint"]);
	$sFingerPrint = str_replace(array(".", "/", "\\", "%", "`"), "", $sFingerPrint);
	fpassthru(fopen(realpath("./" . $sFingerPrint . ".js.gz"), "rb"));
	exit;
?>