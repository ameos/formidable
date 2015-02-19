<?php
/** 
 * Plugin 'rdt_checksingle' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtchecksingle extends formidable_mainrenderlet {
	
	var $sMajixClass = "CheckSingle";
	var $aLibs = array(
		"rdt_checksingle_class" => "res/js/checksingle.js",
	);
	var $sDefaultLabelClass = "formidable-rdrstd-label-inline";

	function _render() {

		$aHtml = array();
		$sChecked = "";

		$iValue = $this->getValue();

		if($iValue === 1) {
			$sChecked = " checked=\"checked\" ";
		}
		
		$sInput = "<input type=\"checkbox\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" " . $sChecked . $this->_getAddInputParams() . " value=\"1\" />";

		$sLabelFor = $this->_displayLabel(
			$this->getLabel()
		);

		$aHtmlBag = array(
			"__compiled"		=> $sInput . $sLabelFor,
			"input"				=> $sInput,
			"checked"			=> $sChecked,
			"value" => $iValue,
			"value." => array(
				"humanreadable" => $this->_getHumanReadableValue($iValue)
			),
		);

		return $aHtmlBag;
	}
	
	function _renderReadOnly() {
		if($this->_navConf('/readonlymode') !== 'disable') {
			return parent::_renderReadOnly();
		}
			
		$iValue = $this->getValue();

		if($iValue === 1) {
			$sChecked = " checked=\"checked\" ";
		}
		
		$sInput = "<input type=\"checkbox\" disabled=\"disabled\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" " . $sChecked . $this->_getAddInputParams() . " value=\"1\" />";

		$sLabelFor = $this->_displayLabel(
			$this->getLabel()
		);

		$aHtmlBag = array(
			"__compiled"		=> $sInput . $sLabelFor,
			"input"				=> $sInput,
			"checked"			=> $sChecked,
			"value" => $iValue,
			"value." => array(
				"humanreadable" => $this->_getHumanReadableValue($iValue)
			),
		);

		return $aHtmlBag;
	}	

	/*
		internationalization of checked labels thanks to Manuel Rego Casanovas
		http://lists.netfielders.de/pipermail/typo3-project-formidable/2007-May/000343.html
	*/
	
	function _getCheckedLabel() {
		$mCheckedLabel = $this->_navConf("/labels/checked/");
		return ($mCheckedLabel) ? $this->oForm->_getLLLabel($mCheckedLabel) : "Y";
	}
	
	function _getNonCheckedLabel() {
		$mNonCheckedLabel = $this->_navConf("/labels/nonchecked/");
		return  ($mNonCheckedLabel) ? $this->oForm->_getLLLabel($mNonCheckedLabel) : "N";
	}
	
	function _getHumanReadableValue($data) {
		
		if(intval($data) === 1) {
			return $this->_getCheckedLabel();
		}

		return $this->_getNonCheckedLabel();
	}

	/*
		END internationalization of checked labels
	*/

	function majixCheck() {
		return $this->buildMajixExecuter(
			"check"
		);
	}

	function majixUnCheck() {
		return $this->buildMajixExecuter(
			"unCheck"
		);
	}

	function getValue() {
		return intval(parent::getValue());
	}
	
	function isChecked() {
		return $this->getValue() === 1;
	}
	
	function check() {
		$this->setValue(1);
	}
	
	function unCheck() {
		$this->setValue(0);
	}
	
	function hasBeenPosted() {
		//return $this->bHasBeenPosted;
		// problem here: checkbox don't post anything if not checked
		// to determine if checkbox has been checked, we have to look around then
		return $this->_isSubmitted();
	}

	function _emptyFormValue($iValue) {
		return (intval($iValue) === 0);
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_checksingle/api/class.tx_rdtchecksingle.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_checksingle/api/class.tx_rdtchecksingle.php"]);
	}

?>
