<?php
/** 
 * Plugin 'rdr_void' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdrvoid extends formidable_mainrenderer {

	function _render($aRendered) {

		$this->oForm->_debug($aRendered, "RENDERER VOID - rendered elements array which are not displayed");
		return $this->_wrapIntoForm("");
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdr_void/api/class.tx_rdrvoid.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdr_void/api/class.tx_rdrvoid.php"]);
	}

?>