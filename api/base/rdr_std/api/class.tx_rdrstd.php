<?php
/**
 * Plugin 'rdr_std' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdrstd extends formidable_mainrenderer {

	var $sDefaultLabelClass = 'formidable-rdrstd-label';

	function _render($aRendered) {

		//$aRendered = $this->displayOnlyIfJs($aRendered);

		$this->oForm->_debug($aRendered, "RENDERER STANDARD - rendered elements array");
		$sForm = $this->_collate($aRendered);

		if(!$this->oForm->oDataHandler->_allIsValid()) {
			$sValidationErrors = "<div class='errors'><div class='error'>" . implode("</div><div class='error'>", $this->oForm->_aValidationErrorsByHtmlId) . "</div></div><hr class='separator' />";
		}


		if($this->defaultTrue("/defaultcss")) {
			$this->oForm->additionalHeaderDataLocalStylesheet(
				$this->sExtPath . "res/css/style.css",
				"tx_rdrstd_default_style"
			);
		}

		return $this->_wrapIntoForm($sValidationErrors . $sForm);
	}

	function _collate($aHtml) {

		$sHtml = "";

		if(is_array($aHtml) && count($aHtml) > 0) {
			reset($aHtml);

			while(list($sName, $aChannels) = each($aHtml)) {
				if(array_key_exists($sName, $this->oForm->aORenderlets)) {
					if($this->oForm->aORenderlets[$sName]->defaultWrap()) {
						$sHtml .= "\n<div class='formidable-rdrstd-rdtwrap'>" . str_replace("{PARENTPATH}", $this->oForm->_getParentExtSitePath(), $aChannels["__compiled"]) . "</div>\n";
					} else {
						$sHtml .= "\n" . str_replace("{PARENTPATH}", $this->oForm->_getParentExtSitePath(), $aChannels["__compiled"]) . "\n";
					}
				}
			}
		}

		return $sHtml;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdr_std/api/class.tx_rdrstd.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdr_std/api/class.tx_rdrstd.php"]);
	}

?>
