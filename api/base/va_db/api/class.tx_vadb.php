<?php
/**
 * Plugin 'va_std' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_vadb extends formidable_mainvalidator {
	
	function validate(&$oRdt) {
		
		$sAbsName = $oRdt->getAbsName();
		$mValue = $oRdt->getValue();

		$aKeys = array_keys($this->_navConf("/"));
		reset($aKeys);
		while(!$oRdt->hasError() && list(, $sKey) = each($aKeys)) {
			
			/***********************************************************************
			*
			*	/unique
			*
			***********************************************************************/

			if($sKey{0} === "u" && t3lib_div::isFirstPartOfStr($sKey, "unique")) {
				// field value has to be unique in the database
				// checking this

				if(!$this->_isUnique($oRdt, $mValue)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"DB:unique",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message/")),
						$mValue
					);

					break;
				}
			}
		}

	}
	
	function _isUnique(&$oRdt, $value) {

		$sDeleted = "";

		if(($sTable = $this->_navConf("/unique/tablename")) !== FALSE) {
			if(($sField = $this->_navConf("/unique/field")) === FALSE) {
				$sField = $oRdt->getName();
			}

			$aDhConf = $this->oForm->_navConf($this->oForm->sXpathToControl . "datahandler/");
			$sKey = $aDhConf["keyname"];

		} else {
			if($oRdt->hasDataBridge() && ($oRdt->oDataBridge->oDataSource->_getType() === "DB")) {
				$sKey = $oRdt->oDataBridge->oDataSource->sKey;
				$sTable = $oRdt->oDataBridge->oDataSource->sTable;
				$sField = $oRdt->dbridged_mapPath();
			} else {
				$aDhConf = $this->oForm->_navConf($this->oForm->sXpathToControl . "datahandler/");
				$sKey = $aDhConf["keyname"];
				$sTable = $aDhConf["tablename"];
				$sField = $oRdt->getName();
			}
		}
		
		if($this->defaultFalse("/unique/deleted/") === TRUE) {
			$sDeleted = " AND deleted != 1";
		}

		$value = addslashes($value);

		if($this->oForm->oDataHandler->_edition()) {
			$sWhere = $sField . " = '" . $value . "' AND " . $sKey . " != '" . $this->oForm->oDataHandler->_currentEntryId() . "'" . $sDeleted;
		} else {
			$sWhere = $sField . " = '" . $value . "'" . $sDeleted;
		}
		
		$sSql = $GLOBALS["TYPO3_DB"]->SELECTquery(
			"count(*) as nbentries",
			$sTable,
			$sWhere
		);

		$rs = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc(
			$this->oForm->_watchOutDB(
				$GLOBALS["TYPO3_DB"]->sql_query($sSql),
				$sSql
			)
		);
		
		if($rs["nbentries"] > 0) {
			return FALSE;
		}
		
		return TRUE;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/va_db/api/class.tx_vadb.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/va_db/api/class.tx_vadb.php"]);
	}

?>
