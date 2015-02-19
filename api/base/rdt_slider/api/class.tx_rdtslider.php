<?php
/** 
 * Plugin 'rdt_slider' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtslider extends formidable_mainrenderlet {
	
	var $aLibs = array(
		"rdt_slider_class" => "res/js/rdt_slider.js",
	);
	
	var $sMajixClass = "Slider";
	var $bCustomIncludeScript = TRUE;
	
	var $aPossibleCustomEvents = array(
		"onslidestart",		# This event is triggered when the user starts sliding
		"onslide",			# This event is triggered on every mouse move during slide
		"onslidechange",	# This event is triggered on slide stop, or if the value is changed programmatically (by the value method)
		"onslidestop",		# This event is triggered when the user stops sliding
	);
	
	function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = FALSE) {
		parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);
		if(!$this->oForm->oJs->_mayLoadJQuery()) {
			$this->oForm->mayday("renderlet SLIDER: may be used only when Formidable is configured to use jQuery JS-API.");
		}
	}
		
	function _render() {

		$sValue = $this->getValue();
		$sLabel = $this->getLabel();
		$aItems = $this->_getItems();
		
		$sHtmlName = $this->_getElementHtmlName();
		$sHtmlId = $this->_getElementHtmlId();
		$sHtmlIdPlaceholder = $sHtmlId . "_placeholder";
		
		$aJsConf = array(
			"options" => array(
				"value" => $sValue,
				"orientation" => $this->defaultMixed("/orientation", "horizontal"),
				"animate" => $this->defaultFalseMixed("/animate"),
				"distance" => $this->defaultMixed("/distance", 0),
				"max" => $this->defaultMixed("/max", 100),
				"min" => $this->defaultMixed("/min", 0),
				"range" => $this->defaultFalseMixed("/range"),
				"step" => $this->defaultMixed("/step", 1),
				"value" => $sValue,
				"values" => null,
			),
			"placeholderid" => $sHtmlIdPlaceholder,
		);
		
		if(!empty($aItems)) {
			# we have to handle a set of values
			$aJsConf["mode"] = "items";
			
			# converting associative array to array
			$aJsItems = array();
			
			reset($aItems);
			while(list($sKey,) = each($aItems)) {
				$sValue = $aItems[$sKey]["value"];
				$sCaption = $aItems[$sKey]["caption"];
				
				$aJsItems[$sValue] = array(
					"caption" => $sCaption,
					"value" => $sValue,
				);
			}
			
			$aJsConf["items"] = array_values($aJsItems);
			
		} else {
			# numeric mode
			$aJsConf["mode"] = "numeric";
		}
		
		$sValue = $this->getValue();
		
		$sInputHidden = '<input type="hidden" name="' . $sHtmlName . '" id="' . $sHtmlId . '" value="' . htmlspecialchars($this->getValue()) . '">';
		$sInput = $sInputHidden . "<div id=\"" . $sHtmlIdPlaceholder . "\" " . $this->_getAddInputParams() . "></div>";
		
		$this->includeScripts($aJsConf);
		
		# including system stylesheet		
		$this->oForm->additionalHeaderDataLocalStylesheet(
			$this->oForm->oJs->sJQueryUIPath . "development-bundle/themes/ui-lightness/jquery.ui.slider.css",
			"tx_ameosformidable_rdtslider_css",
			$bFirstPos = FALSE,
			$sBefore = FALSE,
			$sAfter = FALSE
		);
		
		return array(
			"__compiled" => $this->_displayLabel($sLabel) . $sInput,
			"input" => $sInput,
			"label" => $sLabel,
			"value" => $sValue,
		);
	}
	
	function getValue() {
		if(($mValue = trim(parent::getValue())) === "") {
			return 0;
		}
		
		return $mValue;
	}
	
	function includeScripts($aConf = array()) {
		$this->oForm->oJs->jquery_loadUiPlugin("slider");
		parent::includeScripts($aConf);
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_slider/api/class.tx_rdtslider.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_slider/api/class.tx_rdtslider.php"]);
	}
?>