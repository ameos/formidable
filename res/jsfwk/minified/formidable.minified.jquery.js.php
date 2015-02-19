<?php
	header("Content-Type: text/javascript");
	header("Content-Encoding: gzip");
	header("Cache-Control: max-age=86400, public");
	fpassthru(fopen(realpath("./formidable.minified.jquery.js.gz"), "rb"));
	exit;
?>