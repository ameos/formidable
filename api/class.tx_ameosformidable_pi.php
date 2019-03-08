<?php

	class tx_ameosformidable_pi extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
		
		var $extKey = 'ameos_formidable';
		var $oForm = FALSE;
		var $aFormConf = FALSE;
		var $sXmlPath = FALSE;

		function main($content, $conf) {
			$this->conf = $conf;
			$this->pi_setPiVarDefaults();
			$this->pi_loadLL();

			$sConfPath = trim($this->pi_getFFvalue(
				$this->cObj->data['pi_flexform'],
				'tspath'
			));

			require_once(PATH_formidableapi);
			$this->oForm = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_ameosformidable");

			if($sConfPath !== "") {

				$aCurZone =& $GLOBALS["TSFE"]->tmpl->setup;

				$aPath = explode(".", $sConfPath);
				reset($aPath);
				while(list(, $sSegment) = each($aPath)) {
					if(array_key_exists($sSegment . ".", $aCurZone)) {
						$aCurZone =& $aCurZone[$sSegment . "."];
					} else {
						return "<strong>Formidable: TS path not found in template</strong>";
					}
				}
				
				$this->aFormConf = $aCurZone;
			} else {

				$sConfPath = trim($this->pi_getFFvalue(
					$this->cObj->data['pi_flexform'],
					'xmlpath'
				));

				if($sConfPath !== "") {
					$this->sXmlPath = $this->oForm->toServerPath($sConfPath);
				} else {
					if(array_key_exists("xmlpath", $this->conf)) {
						$this->sXmlPath = $this->oForm->toServerPath($this->conf["xmlpath"]);
					} else {
						return "<strong>Formidable: TS or XML pathes not defined</strong>";
					}
				}
			}

			return TRUE;
		}

		function render() {
			
			if($this->sXmlPath === FALSE) {
				$this->oForm->initFromTs(
					$this,
					$this->aFormConf
				);
			} else {
				$this->oForm->init(
					$this,
					$this->sXmlPath
				);
			}
			
			return $this->pi_wrapInBaseClass($this->oForm->render());
		}
	}

	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.tx_ameosformidable_pi.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.tx_ameosformidable_pi.php']);
	}
?>