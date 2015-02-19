<?php
/** 
 * Plugin 'rdt_text' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdttext extends formidable_mainrenderlet {
	
	function _render() {

		$sValue = $this->getValue();
		$sLabel = $this->getLabel();
		$sInput = "<input type=\"text\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $this->getValueForHtml($sValue) . "\" " . $this->_getAddInputParams() . " />";

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
	
	function mayHtmlAutocomplete() {
		return TRUE;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_text/api/class.tx_rdttext.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_text/api/class.tx_rdttext.php"]);
	}
?>