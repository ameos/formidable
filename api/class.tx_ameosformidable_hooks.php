<?php

class tx_ameosformidable_hooks {
	function contentPostProc_output() {
		if(isset($GLOBALS["tx_ameosformidable"]) && isset($GLOBALS["tx_ameosformidable"]["headerinjection"])) {
			
			reset($GLOBALS["tx_ameosformidable"]["headerinjection"]);
			while(list(, $aHeaderSet) = each($GLOBALS["tx_ameosformidable"]["headerinjection"])) {
				$GLOBALS["TSFE"]->content = str_replace(
					$aHeaderSet["marker"],
					implode("\n", $aHeaderSet["headers"]) . "\n" . $aHeaderSet["marker"],
					$GLOBALS["TSFE"]->content
				);
			}
		}
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.tx_ameosformidable_hooks.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.tx_ameosformidable_hooks.php"]);
}

?>