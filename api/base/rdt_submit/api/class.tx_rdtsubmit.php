<?php
/** 
 * Plugin 'rdt_submit' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtsubmit extends formidable_mainrenderlet {
	
	function _render() {
		// return "<input type=\"button\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $this->oForm->_getLLLabel($this->_navConf("/label")) . "\"" . $this->_getAddInputParams() . " />";
		$sLabel = $this->getLabel();

		if(($sPath = $this->_navConf("/path")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($sPath)) {
				$sPath = $this->callRunneable($sPath);
			}

			$sPath = $this->oForm->toWebPath($sPath);

			$sHtml = "<input type=\"image\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $sLabel . "\" src=\"" . $sPath . "\"" . $this->_getAddInputParams() . " />";
		} else {
			$sHtml = "<input type=\"submit\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $sLabel . "\"" . $this->_getAddInputParams() . " />";
		}

		return $sHtml;
	}

	function getSubmitMode() {
		$sMode = $this->_navConf("/mode");
		if(tx_ameosformidable::isRunneable($sMode)) {
			$sMode = $this->callRunneable($sMode);
		}

		if(is_string($sMode)) {
			return strtolower(trim($sMode));
		}

		return "full";
	}

	function _getEventsArray() {

		$aEvents = parent::_getEventsArray();
		
		if(!array_key_exists("onclick", $aEvents)) {
			$aEvents["onclick"] = array();
		}

		$aEvents["onclick"][] = "Formidable.stopEvent(event)";

		$sMode = $this->getSubmitMode();

		$aAddPost = array(
			$this->_getElementHtmlNameWithoutFormId() => "1"		// to simulate default browser behaviour
		);
		
		/*
			$sJson = "";
			if($aAddPost !== FALSE) {
				$sJson = $this->oForm->array2json($aAddPost);
			}

		*/

		$sEvent = "";

		if($sMode == "refresh" || $this->_navConf("/refresh") !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getRefreshSubmitEvent();
		} elseif($sMode == "draft" || $this->_navConf("/draft") !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getDraftSubmitEvent();
		} elseif($sMode == "test" || $this->_navConf("/test") !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getTestSubmitEvent();
		} elseif($sMode == "clear" || $this->_navConf("/clear") !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getClearSubmitEvent();
		} elseif($sMode == "search" || $this->_navConf("/search") !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getSearchSubmitEvent();
		} else {
			$sOnclick = $this->oForm->oRenderer->_getFullSubmitEvent();
		}

		$sAddPostVars = "Formidable.f('" . $this->oForm->formid . "').addFormData(" . $this->oForm->array2json($aAddPost) . ");";

		$aEvents["onclick"][] = $sAddPostVars . $sOnclick;

		reset($aEvents);
		return $aEvents;
	}

	function _hasThrown($sEvent, $sWhen = FALSE) {

		if($sEvent === "click") {
			// handling special click server event on rdt_submit
			// special because has to work without javascript
			return $this->hasSubmitted();
		}
		return parent::_hasThrown($sEvent, $sWhen);
	}

	function _searchable() {
		return $this->defaultFalse("/searchable/");
	}

	function _renderOnly() {
		return TRUE;
	}

	function isNaturalSubmitter() {
		return TRUE;
	}
	
	function _activeListable() {
		return $this->defaultTrue("/activelistable");
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_submit/api/class.tx_rdtsubmit.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_submit/api/class.tx_rdtsubmit.php"]);
	}
?>
