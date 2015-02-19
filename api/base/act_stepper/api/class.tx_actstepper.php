<?php
/** 
 * Plugin 'act_stepper' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_actstepper extends formidable_mainactionlet {
	
	function _doTheMagic($aRendered, $sForm) {
		
		$sUrl = null;
		
		if($this->oForm->oDataHandler->_allIsValid()) {

			$iStep = $this->oForm->_getStep();

			switch($this->aElement["step"]) {
				case "next": {
					
					$iStepToGo = $this->oForm->_getNextInArray(
						$iStep,
						$this->oForm->aSteps,
						FALSE,	// cycle ?
						TRUE	// key only ?
					);

					break;
				}
				case "previous": {
					
					$iStepToGo = $this->oForm->_getPrevInArray(
						$iStep,
						$this->oForm->aSteps,
						FALSE,
						TRUE
					);

					break;
				}
				default: {
					$iStepToGo = $iStep;
				}
			}

			$sUid = "";

			if(array_key_exists("uid", $this->aElement)) {

				switch($this->aElement["uid"]) {
					case "follow" : {
						$sUid = $this->oForm->oDataHandler->_currentEntryId();
						break;
					}
					default : {
						$sUid = $this->aElement["uid"];
					}
				}
			}

/*			$sStep = $iStepToGo . (($sUid != "") ? "-" . $sUid : "");

			$aParams = array(
				"AMEOSFORMIDABLE_STEP"		=> $sStep,
				"AMEOSFORMIDABLE_STEP_HASH"	=> ($this->oForm->_getSafeLock($sStep)),
			);

			$aGet = t3lib_div::_GET();
			if(array_key_exists("id", $aGet)) {
				unset($aGet["id"]);
			}

			$sUrl = $this->oForm->_oParent->pi_getPageLink(
				$GLOBALS["TSFE"]->id,
				"_self",
				t3lib_div::array_merge_recursive_overrule(
					$aGet,
					$aParams
				)
			);
			*/

//			debug($GLOBALS["_SESSION"]);



			/*

			"<input type='hidden' name='AMEOSFORMIDABLE_STEP' id='AMEOSFORMIDABLE_STEP' value='" . $this->oForm->_getStep() . "' />" .
			"<input type='hidden' name='AMEOSFORMIDABLE_STEP_HASH' id='AMEOSFORMIDABLE_STEP_HASH' value='" . $this->oForm->_getSafeLock($this->oForm->_getStep()) . "' />" .
			"<input type='hidden' name='AMEOSFORMIDABLE_STEPPER' id='AMEOSFORMIDABLE_STEPPER' value='" . $sStepperId . "' />"

			*/

			$sStepperId = $this->oForm->_getStepperId();
			
			if(!array_key_exists("ameos_formidable", $GLOBALS["_SESSION"])) {
				$GLOBALS["_SESSION"]["ameos_formidable"] = array();
			}

			if(!array_key_exists("stepper", $GLOBALS["_SESSION"]["ameos_formidable"])) {
				$GLOBALS["_SESSION"]["ameos_formidable"]["stepper"] = array();
			}

			$GLOBALS["_SESSION"]["ameos_formidable"]["stepper"][$sStepperId] = array(
				"AMEOSFORMIDABLE_STEP" => $iStepToGo,
				"AMEOSFORMIDABLE_STEP_UID" => $sUid,
				"AMEOSFORMIDABLE_STEP_HASH" => $this->oForm->_getSafeLock($iStepToGo . $sUid)
			);

			$sUrl = t3lib_div::getIndpEnv("TYPO3_REQUEST_URL");

			if(!is_null($sUrl)) {
				header("Location: " . $sUrl);
				die();
			}
		}
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/act_stepper/api/class.tx_actstepper.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/act_stepper/api/class.tx_actstepper.php"]);
	}

?>