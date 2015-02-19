<?php
/** 
 * Plugin 'act_userobj' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

class tx_actuserobj extends formidable_mainactionlet {
	
	function _doTheMagic($aRendered, $sForm) {
		
		if($this->oForm->oDataHandler->_allIsValid()) {
			$this->callRunneable($this->aElement);
		}
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/act_userobj/api/class.tx_actuserobj.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/act_userobj/api/class.tx_actuserobj.php"]);
	}

?>