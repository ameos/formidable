<?php
/** 
 * Plugin 'dh_void' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_dhvoid extends formidable_maindatahandler {
	
	function _doTheMagic($bShouldProcess = TRUE) {
		
		if($bShouldProcess && $this->_allIsValid()) {	
			$this->oForm->_debug("void do nothing with data", "DATAHANDLER VOID - EXECUTION");
		}
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/dh_void/api/class.tx_dhvoid.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/dh_void/api/class.tx_dhvoid.php"]);
	}
?>