<?php
/** 
 * Plugin 'rdt_txtarea' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdttxtarea extends formidable_mainrenderlet {
	
	function _render() {
		
		$sValue = $this->getValue();
		$sLabel = $this->getLabel();
		$sValue = $this->oForm->_getLLLabel($sValue);
		
		$sAddInputParams = $this->_getAddInputParams();
		

		/* adaptation for XHTML1.1 strict validation */
		
		if(strpos($sAddInputParams, "rows") === FALSE) {
			$sAddInputParams = " rows=\"2\" " . $sAddInputParams;
		}

		if(strpos($sAddInputParams, "cols") === FALSE) {
			$sAddInputParams = " cols=\"20\" " . $sAddInputParams;
		}

		/* */
		
		$sValueForHtml = $this->getValueForHtml($sValue);
		$sInput = "<textarea name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\"" . $sAddInputParams . ">" . $sValueForHtml . "</textarea>";
		
		return array(
			"__compiled" => $this->_displayLabel($sLabel) . $sInput,
			"input" => $sInput,
			"label" => $sLabel,
			"value" => $sValue,
		);
	}

	function getValue() {
		$sValue = parent::getValue();
		if($this->defaultFalse("/convertfromrte/")){
			$aParseFunc["parseFunc."] = $GLOBALS["TSFE"]->tmpl->setup["lib."]["parseFunc_RTE."];
			$sValue = $this->oForm->cObj->stdWrap($sValue, $aParseFunc);
		}
		return $sValue;
	}
	
	function _getHumanReadableValue($sValue) {
		return nl2br(htmlspecialchars($sValue));
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_txtarea/api/class.tx_rdttxtarea.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_txtarea/api/class.tx_rdttxtarea.php"]);
	}

?>