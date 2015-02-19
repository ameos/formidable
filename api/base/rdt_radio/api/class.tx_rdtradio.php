<?php
/** 
 * Plugin 'rdt_radio' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtradio extends formidable_mainrenderlet {
	
	var $sMajixClass = "Radio";
	var $aLibs = array(
		"rdt_radio_class" => "res/js/radio.js",
	);

	var $bCustomIncludeScript = TRUE;

	function _render() {

		$aHtmlBag = array();
		$sCurValue = $this->getValue();
		$sRadioGroup = "";
		
		$sPostFlag = $this->getPostFlag();

		// on construit la liste des éléments du groupe radio
		
		$optionsList = "";
		$aItems = $this->_getItems();
		$aSubRdts = array();
		
		if(count($aItems) > 0) {

			$aHtml = array();
			
			reset($aItems);
			while(list($itemindex, $aItem) = each($aItems)) {

				$selected = "";
				
				if($aItem["value"] == $sCurValue) {
					$selected = " checked=\"checked\" ";
				}

				$sCaption = $this->oForm->_getLLLabel($aItem["caption"]);

				$sId = $this->_getElementHtmlId() . "_" . $itemindex;
				$aSubRdts[] = $sId;
				$this->sCustomElementId = $sId;
				$this->includeScripts();

				$sValue = $aItem["value"];
				
				if(($sName = $this->_navConf("/forcedname")) === FALSE) {
					$sName = $this->_getElementHtmlName();
				}
				
				$sInput = "<input type=\"radio\" name=\"" . $sName . "\" id=\"" . $sId . "\" value=\"" . htmlspecialchars($aItem["value"]) . "\" " . $selected . $this->_getAddInputParams() . " ";
				if(array_key_exists("custom", $aItem)) {
					$sInput .= $aItem["custom"];
				}

				$sInput .= "/>";
				$sLabelStart = "<label for=\"" . $sId . "\" ";
				if(array_key_exists("labelcustom", $aItem)) {
					$sLabelStart .= $aItem["labelcustom"];
				}

				$sLabelStart .= ">";
				$sLabelEnd = "</label>";
				$sLabel =  $sLabelStart . $sCaption . $sLabelEnd;
				
				$aHtmlBag[$sValue . "."] = array(
					"input" => $sInput,
					"caption" => $sCaption,
					"value" => $sValue,
					"label" => $sLabel,
					"label." => array(
						"tag" => $sLabelStart . $sCaption . $sLabelEnd,
						"tag." => array(
							"wrap" => $sLabelStart . "|" . $sLabelEnd,
						),
						"for." => array(
							"start" => $sLabelStart,
							"end" => $sLabelEnd,
						)
					),
				);

				$htmlCode = $sInput . $sLabelStart . $sCaption . $sLabelEnd;
				if (array_key_exists('wrapitem', $aItem)) {
					$htmlCode = str_replace('|', $htmlCode, $aItem['wrapitem']);
				}
			
				$aHtml[] = (($selected !== "") ? $this->_wrapSelected($htmlCode) : $this->_wrapItem($htmlCode));
				$this->sCustomElementId = FALSE;
			}
			
			reset($aHtml);
			$sRadioGroup = $this->_implodeElements($aHtml);
		}
		
		$sRadioGroup .= $sPostFlag;

		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts(
			array(
				"name" => $this->_getElementHtmlName(),
				"radiobuttons" => $aSubRdts,
				"bParentObj" => TRUE,
			)
		);

		$sLabel = $this->getLabel();
		$sInput = $this->_implodeElements($aHtml) . $sPostFlag;
		
		$aHtmlBag["input"] = $sInput;
		$aHtmlBag["postflag"] = $sPostFlag;
		$aHtmlBag["value"] = $sCurValue;
		$aHtmlBag["__compiled"] = $this->_displayLabel($sLabel) . $sRadioGroup;

		reset($aHtmlBag);
		return $aHtmlBag;
	}
	
	function _getHumanReadableValue($data) {

		$aItems = $this->_getItems();
		
		reset($aItems);
		while(list(, $aItem) = each($aItems)) {

			if($aItem["value"] == $data) {
				return $this->oForm->_getLLLabel($aItem["caption"]);
			}
		}

		return $data;
	}




	function _getSeparator() {

		if(($mSep = $this->_navConf("/separator")) === FALSE) {
			$mSep = "\n";
		} else {
			if(tx_ameosformidable::isRunneable($mSep)) {
				$mSep = $this->callRunneable($mSep);
			}
		}

		return $mSep;
	}
	
	function _implodeElements($aHtml) {

		if(!is_array($aHtml)) {
			$aHtml = array();
		}

		return implode(
			$this->_getSeparator(),
			$aHtml
		);
	}

	function _wrapSelected($sHtml) {

		if(($mWrap = $this->_navConf("/wrapselected")) !== FALSE) {
			
			if(tx_ameosformidable::isRunneable($mWrap)) {
				$mWrap = $this->callRunneable($mWrap);
			}

			$sHtml = str_replace("|", $sHtml, $mWrap);

		} else {
			$sHtml = $this->_wrapItem($sHtml);
		}

		return $sHtml;
	}

	function _wrapItem($sHtml) {
		
		if(($mWrap = $this->_navConf("/wrapitem")) !== FALSE) {
			
			if(tx_ameosformidable::isRunneable($mWrap)) {
				$mWrap = $this->callRunneable($mWrap);
			}

			$sHtml = str_replace("|", $sHtml, $mWrap);
		}

		return $sHtml;
	}

	function _displayLabel($sLabel) {
		$sId = $this->_getElementHtmlId() . "_label";
		return ($this->oForm->oRenderer->bDisplayLabels && (trim($sLabel) != "")) ? "<label id='" . $sId . "' class='formidable-rdrstd-label " . $sId . "'>" . $sLabel . "</label>\n" : "";
	}

	function _activeListable() {		// listable as an active HTML FORM field or not in the lister
		return $this->defaultTrue("/activelistable/");
	}

	function majixUncheck() {
		return $this->buildMajixExecuter(
			"uncheck"
		);
	}
	
	function majixCheck($sValue) {
		return $this->buildMajixExecuter(
			"check",
			$sValue
		);
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_radio/api/class.tx_rdtradio.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_radio/api/class.tx_rdtradio.php"]);
	}

?>
