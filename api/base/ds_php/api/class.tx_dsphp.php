<?php
/**
 * Plugin 'ds_php' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

class tx_dsphp extends formidable_maindatasource {

	var $sKey = FALSE;
/*
	function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = FALSE) {
		parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);
	}
*/
	function writable() {
		return ($this->_navConf("/set") !== FALSE);
	}

	function initDataSet($sKey) {
		$sSignature = FALSE;
		$oDataSet = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("formidable_maindataset");

		if($sKey === "new") {
			// new record to create
			$oDataSet->initFloating($this);
		} else {
			// existing record to grab

			if($this->_navConf("/get") === FALSE) {
				$oDataSet->initAnchored(
					$this,
					array(),
					$sKey
				);
			} else {
				if(($aDataSet = $this->getSyncData($sKey)) !== FALSE) {
					$oDataSet->initAnchored(
						$this,
						$aDataSet,
						$sKey
					);

				} else {
					$this->oForm->mayday("datasource:PHP[name='" . $this->getName() . "'] No dataset matching key '" . $sKey . "' was found.");
				}
			}
		}

		$sSignature = $oDataSet->getSignature();
		$this->aODataSets[$sSignature] =& $oDataSet;

		return $sSignature;
	}

	function getSyncData($sKey) {
		if(($aGet = $this->_navConf("/get")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($aGet)) {
				$aGet = $this->callRunneable(
					$aGet,
					array("key" => $sKey)
				);
			} else {
				$this->oForm->mayday("datasource:PHP[name='" . $this->getName() . "'] /get has to be runnable (userobj, or reference to a code-behind).");
			}
		} else {
			$this->oForm->mayday("datasource:PHP[name='" . $this->getName() . "'] You have to provide a runnable on <b>/get</b>.");
		}

		return $aGet;
	}

	function setSyncData($sSignature, $sKey, $aData) {
		if(($aSet = $this->_navConf("/set")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($aSet)) {
				$aSet = $this->callRunneable(
					$aSet,
					$this->aODataSets[$sSignature]->getDataSet()
				);
			} else {
				$this->oForm->mayday("datasource:PHP[name='" . $this->getName() . "'] /set has to be runnable (userobj, or reference to a code-behind).");
			}
		}

		return $aSet;
	}

	function &_fetchData($aConfig = array(), $aFilters = array()) {

		$aResults = array();
		$iNumRows = 0;

		if(($aGet = $this->_navConf("/get")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($aGet)) {
				$aResults = $this->callRunneable($aGet);
				if(is_array($aResults)) {
					$iNumRows = count($aResults);
				} else {
					$aResults = array();
				}
			} else {
				$this->oForm->mayday("datasource:PHP[name='" . $this->getName() . "'] /get has to be runnable (userobj, or reference to a code-behind).");
			}
		} else {
			$this->oForm->mayday("datasource:PHP[name='" . $this->getName() . "'] You have to provide a runnable on <b>/get</b>.");
		}

		return array(
			"numrows" => $iNumRows,
			"results" => &$aResults,
		);
	}

	function dset_alwaysNeedsToBeWritten() {
		return TRUE;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/ds_php/api/class.tx_dsphp.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/ds_php/api/class.tx_dsphp.php"]);
	}
?>
