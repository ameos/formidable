<?php

	require_once(PATH_formidable . "api/class.tx_ameosformidable_pi.php");
	
	class tx_ameosformidable_pi1 extends tx_ameosformidable_pi {
		
		var $prefixId = 'tx_ameosformidable_pi1';
		var $scriptRelPath = 'pi1/class.tx_ameosformidable_pi1.php';

		function main($content,$conf) {
			
			$this->pi_initPIflexForm();

			if(($sMessage = parent::main($content, $conf)) !== TRUE) {
				return $sMessage;
			}

			$this->pi_USER_INT_obj=0;
			return $this->render();
		}
	}

	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/pi1/class.tx_ameosformidable_pi1.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/pi1/class.tx_ameosformidable_pi1.php']);
	}
?>