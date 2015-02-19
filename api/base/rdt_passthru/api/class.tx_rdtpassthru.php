<?php
/** 
 * Plugin 'rdt_passthru' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtpassthru extends formidable_mainrenderlet {
	
	function _renderReadOnly() {
		return $this->getPostFlag();
	}
	
	function _readonly() {
		return TRUE;
	}

	function _sqlSearchClause($value, $fieldprefix = "") {
		return $fieldprefix . $this->_navConf("/name") . " = '" . $GLOBALS['TYPO3_DB']->quoteStr($value, '') . "'";
	}

	function _listable() {
		return FALSE;
	}

	function maySubmit() {
		return FALSE;
	}
	
	function getValue() {
		
		$mValue = $this->_navConf("/data/value");
		
		if(tx_ameosformidable::isRunneable($mValue)) {
			$mValue = $this->oForm->callRunneable($mValue);
		}
		
		return $mValue;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_passthru/api/class.tx_rdtpassthru.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_passthru/api/class.tx_rdtpassthru.php"]);
	}

?>