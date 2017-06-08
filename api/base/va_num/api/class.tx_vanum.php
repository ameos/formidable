<?php
/** 
 * Plugin 'va_num' for the 'ameos_formidable' extension.
 *
 * @author	Luc Muller <typo3dev@ameos.com>
 */


class tx_vanum extends formidable_mainvalidator {
	
	function validate(&$oRdt) {

		$sAbsName = $oRdt->getAbsName();
		$mNum = $oRdt->getValue();

		if($mNum === "") {
			// never evaluate if value is empty
			// as this is left to STANDARD:required
			return;
		}

		$aKeys = array_keys($this->_navConf("/"));
		reset($aKeys);
		while(!$oRdt->hasError() && list(, $sKey) = each($aKeys)) {

			/***********************************************************************
			*
			*	/isnum
			*
			***********************************************************************/

			if($sKey{0} === "i" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "isnum")) {
				if(!$this->_checkIsNum($mNum)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"NUM:isnum",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$mNum
					);

					break;
				}
			}




			/***********************************************************************
			*
			*	/isbetween
			*
			***********************************************************************/

			if($sKey{0} === "i" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "isbetween")) {
				$aBoundaries = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
					",",
					$this->_navConf("/" . $sKey . "/value")
				);

				if(!$this->_checkIsIn($mNum, $aBoundaries)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"NUM:isbetween",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$mNum
					);

					break;
				}
			}




			/***********************************************************************
			*
			*	/islower
			*
			***********************************************************************/

			if($sKey{0} === "i" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "islower")) {
				$aBoundaries = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
					",",
					$this->_navConf("/" . $sKey . "/value")
				);

				if(!$this->_checkIsLow($mNum, $aBoundaries)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"NUM:islower",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$mNum
					);

					break;
				}
			}




			/***********************************************************************
			*
			*	/ishigher
			*
			***********************************************************************/

			if($sKey{0} === "i" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "ishigher")) {
				$aBoundaries = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
					",",
					$this->_navConf("/" . $sKey . "/value")
				);

				if(!$this->_checkIsHigh($mNum, $aBoundaries)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"NUM:ishigher",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$mNum
					);

					break;
				}
			}




			/***********************************************************************
			*
			*	/isfloat
			*
			***********************************************************************/

			if($sKey{0} === "i" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "isfloat")) {
				if(!$this->_checkIsFloat($mNum)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"NUM:isfloat",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$mNum
					);

					break;
				}
			}




			/***********************************************************************
			*
			*	/isinteger
			*
			***********************************************************************/

			if($sKey{0} === "i" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "isinteger")) {
				if(!$this->_checkIsInteger($mNum)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"NUM:isinteger",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$mNum
					);

					break;
				}
			}
		}
	}

	function _checkIsNum($mNum) {
		return is_numeric($mNum);
	}

	function _checkIsInteger($mNum) {
		return ctype_digit($mNum) && intval($mNum) == $mNum;
	}

	function _checkIsIn($mNum,$aValues) {

		if($this->_checkIsNum($mNum)) {
			return (($mNum >= min($aValues)) && ($mNum <= max($aValues)));
		}
		
		return FALSE;
	}

	function _checkIsLow($mNum,$aValues) {

		if($this->_checkIsNum($mNum)) {
			return ($mNum < min($aValues));
		}
		
		return FALSE;
	}

	function _checkIsHigh($mNum, $aValues) {

		if($this->_checkIsNum($mNum)) {
			return ($mNum > max($aValues));
		}
		
		return FALSE;
	}

	function _checkIsFloat($mNum) {

		$split = split('\.', $mNum);

		if(count($split) == 2) {
			if(ctype_digit($split[0]) && ctype_digit($split[1])) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/va_num/api/class.tx_vanum.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/va_num/api/class.tx_vanum.php"]);
	}
?>
