<?php
/**
 * Plugin 'dh_db' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

class tx_dhdb extends formidable_maindatahandler {

	var $__aStoredI18NParent = FALSE;
	var $__aExtractedMMData = array();
	var $bHasCreated = FALSE;

	function _doTheMagic($bShouldProcess = TRUE) {

		$tablename	= $this->tableName();
		$keyname	= $this->keyName();

		if($tablename != "" && $keyname != "") {

			if($this->i18n() && ($aNewI18n = $this->newI18nRequested()) !== FALSE) {

				// first check that parent exists
				if(($aParent = $this->__getDbData($tablename, $keyname, $aNewI18n["i18n_parent"])) === FALSE) {
					$this->oForm->mayday("DATAHANDLER DB cannot create requested i18n for non existing parent:" . $aNewI18n["i18n_parent"]);
				}

				//then check that no i18n record exists for requested sys_language_uid on this parent record

				$sSql = $GLOBALS["TYPO3_DB"]->SELECTquery(
					$keyname,
					$tablename,
					"l18n_parent='" . $aNewI18n["i18n_parent"] . "' AND sys_language_uid='" . $aNewI18n["sys_language_uid"] . "'"
				);

				$rSql = $this->oForm->_watchOutDB(
					$GLOBALS["TYPO3_DB"]->sql_query($sSql),
					$sSql
				);

				if($GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql) !== FALSE) {
					$this->oForm->mayday("DATAHANDLER DB cannot create requested i18n for parent:" . $aNewI18n["i18n_parent"] . " with sys_language_uid:" . $aNewI18n["sys_language_uid"] . " ; this version already exists");
				}

				//debug("creation of child");

	/*				// everything's ok, creating child
				$aChild = $this->i18n_getStoredParent($aParent);
				unset($aChild[$keyname]);
				$aChild["sys_language_uid"] = $aNewI18n["sys_language_uid"];
				$aChild["l18n_parent"] = $aNewI18n["i18n_parent"];	// notice difference between i and l

				$rSql = $this->oForm->_watchOutDB(
					$GLOBALS["TYPO3_DB"]->exec_INSERTquery(
						$tablename,
						$aChild
					)
				);*/

				$aChild = array();
				$aChild["sys_language_uid"] = $aNewI18n["sys_language_uid"];
				$aChild["l18n_parent"] = $aNewI18n["i18n_parent"];	// notice difference between i and l
				$aChild["crdate"] = time();
				$aChild["tstamp"] = time();
				$aChild["cruser_id"] = $GLOBALS["TSFE"]->fe_user->user["uid"];
				$aChild["pid"] = $aParent["pid"];

				$sSql = $GLOBALS["TYPO3_DB"]->INSERTquery(
					$tablename,
					$aChild
				);

				$rSql = $this->oForm->_watchOutDB(
					$GLOBALS["TYPO3_DB"]->sql_query($sSql),
					$sSql
				);

				$this->newEntryId = $GLOBALS["TYPO3_DB"]->sql_insert_id();
				$this->bHasCreated = TRUE;
				$this->refreshAllData();
				
				$aLink = array();
				$aLink['parameter'] = $GLOBALS['TSFE']->id;
				$aLink['additionalParams'] = '&L=' . $GLOBALS['TSFE']->sys_language_uid . 
						'&' . $this->oForm->formid . '[action]=requestEdition' .
						'&' . $this->oForm->formid . '[tablename]=' . $this->tablename() .
						'&' . $this->oForm->formid . '[recorduid]=' . $this->newEntryId .
						'&' . $this->oForm->formid . '[hash]=' . $this->oForm->_getSafeLock("requestEdition" . ":" . $this->tablename() . ":" . $this->newEntryId);
				$sLink = $this->oForm->cObj->typolink_URL($aLink);
				$this->oForm->sendToPage($this->oForm->toWebPath($sLink));
			}

			if($bShouldProcess && $this->_allIsValid()) {

				// il n'y a aucune erreur de validation
				// on peut traiter les donnes
				// on met a jour / insere l'enregistrement dans la base de donnees



				$aRs = array();

				$aFormData = $this->_processBeforeInsertion(
					$this->getDataPreparedForDB()
				);

				if(count($aFormData) > 0) {

					$editEntry = $this->_currentEntryId();

					if($editEntry) {

						$aFormData = $this->_processBeforeEdition($aFormData);

						if($this->i18n() && $this->i18n_updateChildsOnSave() && $this->i18n_currentRecordUsesDefaultLang()) {

							// updating non translatable child data

							$aUpdateData = array();

							$this->oForm->_debug("", "DB update, taking care of sys_language_uid " . $this->i18n_getSysLanguageUid());
							//$aFormData["sys_language_uid"]  = $this->i18n_getSysLanguageUid();

							/*reset($this->oForm->aORenderlets);
							while(list($sName, ) = each($this->oForm->aORenderlets)) {
								$oRdt =& $this->oForm->aORenderlets[$sName];
								if(array_key_exists($sName, $aFormData) && !$oRdt->_translatable()) {
									$aUpdateData[$sName] = $aFormData[$sName];
								}
							}*/

							reset($aFormData);
							while(list($sName, ) = each($aFormData)) {
								if(
									!array_key_exists($sName, $this->oForm->aORenderlets) ||
									!$this->oForm->aORenderlets[$sName]->_translatable()
								) {
									$aUpdateData[$sName] = $aFormData[$sName];
								}

							}

							if(!empty($aUpdateData)) {

								$this->oForm->_debug($aUpdateData, "EXECUTION OF DATAHANDLER DB - EDITION MODE in " . $tablename . "[" . $keyname . "=" . $editEntry . "] - UPDATING NON TRANSLATED I18N CHILDS");

								$sSql = $GLOBALS["TYPO3_DB"]->UPDATEquery(
									$tablename,
									"l18n_parent = '" . $editEntry . "'",
									$aUpdateData
								);

								$this->oForm->_watchOutDB(
									$GLOBALS["TYPO3_DB"]->sql_query($sSql),
									$sSql
								);

								$this->handleExtractedMMData();
							}
						}

						if($this->fillStandardTYPO3fields()) {
							if(!array_key_exists("tstamp", $aFormData)) {
								$aFormData['tstamp'] = time();
							}
						}

						$this->oForm->_debug($aFormData, "EXECUTION OF DATAHANDLER DB - EDITION MODE in " . $tablename . "[" . $keyname . "=" . $editEntry . "]");

						$sSql = $GLOBALS["TYPO3_DB"]->UPDATEquery(
							$tablename,
							$keyname . "='" . $this->oForm->db_quoteStr($editEntry) . "'",
							$aFormData
						);

						$this->oForm->_watchOutDB(
							$GLOBALS["TYPO3_DB"]->sql_query($sSql),
							$sSql
						);

						$this->oForm->_debug($GLOBALS["TYPO3_DB"]->debug_lastBuiltQuery, "DATAHANDLER DB - SQL EXECUTED");

						$this->handleExtractedMMData();

						// updating stored data
						$this->__aStoredData = array_merge($this->__aStoredData, $aFormData);
						$this->bHasEdited = TRUE;
						$this->_processAfterEdition($this->_getStoredData());

					} else {

						// creating data

						$aFormData = $this->_processBeforeCreation($aFormData);
						if(is_array($aFormData) && count($aFormData) !== 0) {
							if($this->i18n()) {
								$this->oForm->_debug("", "DB insert, taking care of sys_language_uid " . $this->i18n_getSysLanguageUid());
								$aFormData["sys_language_uid"] = $this->i18n_getSysLanguageUid();
							}

							if($this->fillStandardTYPO3fields()) {
								if(!array_key_exists("pid", $aFormData)) {
									$aFormData['pid'] = $GLOBALS['TSFE']->id;
								}

								if(!array_key_exists("cruser_id", $aFormData)) {
									$aFormData['cruser_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
								}

								if(!array_key_exists("crdate", $aFormData)) {
									$aFormData['crdate'] = time();
								}

								if(!array_key_exists("tstamp", $aFormData)) {
									$aFormData['tstamp'] = time();
								}
							}

							$this->oForm->_debug($aFormData, "EXECUTION OF DATAHANDLER DB - INSERTION MODE in " . $tablename);

							$sSql = $GLOBALS["TYPO3_DB"]->INSERTquery(
								$tablename,
								$aFormData
							);

							$this->oForm->_watchOutDB(
								$GLOBALS["TYPO3_DB"]->sql_query($sSql),
								$sSql
							);

							$this->oForm->_debug($GLOBALS["TYPO3_DB"]->debug_lastBuiltQuery, "DATAHANDLER DB - SQL EXECUTED");

							$this->newEntryId = $GLOBALS["TYPO3_DB"]->sql_insert_id();
							$this->oForm->_debug("", "NEW ENTRY ID [" . $keyname . "=" . $this->newEntryId . "]");

							$this->bHasCreated = TRUE;

							// handling MM relations on fields
							$this->handleExtractedMMData();

							// updating stored data
							$this->__aStoredData = array();
							$this->_getStoredData();
						} else {
							$this->newEntryId = FALSE;
							$this->oForm->_debug("", "NOTHING CREATED IN DB");

							// updating stored data
							$this->__aStoredData = array();
						}

						$this->_processAfterCreation($this->_getStoredData());
					}
				} else {
					$this->oForm->_debug("", "EXECUTION OF DATAHANDLER DB - NOTHING TO DO - SKIPPING PROCESS " . $tablename);
				}

				/*   /process/afterinsertion */
				$this->_processAfterInsertion($this->_getStoredData());

			} else {
				/* nothing to do */
			}
		} else {
			$this->oForm->mayday("DATAHANDLER configuration isn't correct : check /tablename AND /keyname in your datahandler conf");
		}
	}

	function hasCreated() {
		return $this->bHasCreated;
	}

	function handleExtractedMMData() {
		#debug($this->__aExtractedMMData, "handleExtractedMMData");
		reset($this->__aExtractedMMData);

		$iCurrentId = $this->currentId();

		while(list($sAbsName,) = each($this->__aExtractedMMData)) {
			$oRdt =& $this->oForm->aORenderlets[$sAbsName];
			$sMMTable = $oRdt->getRelationMMTable();

			// wiping all previous relations
			$GLOBALS["TYPO3_DB"]->exec_DELETEquery(
				$sMMTable,
				"uid_local='" . $this->oForm->db_quoteStr($iCurrentId) . "'"
			);

			// and create them all again
			reset($this->__aExtractedMMData[$sAbsName]);
			while(list(, $iUidForeign) = each($this->__aExtractedMMData[$sAbsName])) {
				$GLOBALS["TYPO3_DB"]->exec_INSERTquery(
					$sMMTable,
					array(
						"uid_local" => $this->oForm->db_quoteStr($iCurrentId),
						"uid_foreign" => $this->oForm->db_quoteStr($iUidForeign)
					)
				);
			}
		}
	}

	function getDataPreparedForDB() {
		$aRes = array();
		$this->__aExtractedMMData = array();
		$aKeys = array_keys($this->oForm->aORenderlets);
		reset($aKeys);

		while(list(, $sAbsName) = each($aKeys)) {
			if(
				!$this->oForm->aORenderlets[$sAbsName]->_renderOnly() &&
				(
					!$this->oForm->aORenderlets[$sAbsName]->maySubmit() ||
					$this->oForm->aORenderlets[$sAbsName]->hasBeenDeeplySubmitted()
				)
			) {
				$sFlatName = $this->oForm->aORenderlets[$sAbsName]->getName();
				$mValue = $this->oForm->aORenderlets[$sAbsName]->getValue();

				if($this->oForm->aORenderlets[$sAbsName]->hasRelationMM()) {

					if(!is_array($mValue)) {
						$mValue = array($mValue);	// making an array of the value, for MM-processing !
					}

					$aRes[$sFlatName] = count($mValue);	// mm field in local table stores the number of relations
					$this->__aExtractedMMData[$sAbsName] = $mValue;
				} else {
					$aRes[$sFlatName] = $this->oForm->aORenderlets[$sAbsName]->_flatten(
						$mValue
					);
				}
			}
		}

		reset($aRes);
		return $aRes;
	}

	function _processBeforeInsertion($aData) {

		if(($aUserObj = $this->_navConf("/process/beforeinsertion/")) !== FALSE) {

			if(tx_ameosformidable::isRunneable($aUserObj)) {
				$aData = $this->oForm->callRunneable(
					$aUserObj,
					$aData
				);
			}

			if(!is_array($aData)) {
				$aData = array();
			}
		}

		reset($aData);
		return $aData;
	}

	function _processAfterInsertion($aData) {
		if(($aUserObj = $this->_navConf("/process/afterinsertion/")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($aUserObj)) {
				$this->oForm->callRunneable(
					$aUserObj,
					$aData
				);
			}
		}
	}

	function _processBeforeCreation($aData) {

		if(($aUserObj = $this->_navConf("/process/beforecreation/")) !== FALSE) {

			if(tx_ameosformidable::isRunneable($aUserObj)) {
				$aData = $this->oForm->callRunneable(
					$aUserObj,
					$aData
				);
			}

			if(!is_array($aData)) {
				$aData = array();
			}
		}

		reset($aData);
		return $aData;
	}

	function _processAfterCreation($aData) {
		if(($aUserObj = $this->_navConf("/process/aftercreation/")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($aUserObj)) {
				$this->oForm->callRunneable(
					$aUserObj,
					$aData
				);
			}
		}
	}

	function _processBeforeEdition($aData) {

		if(($aUserObj = $this->_navConf("/process/beforeedition/")) !== FALSE) {

			if(tx_ameosformidable::isRunneable($aUserObj)) {
				$aData = $this->oForm->callRunneable(
					$aUserObj,
					$aData
				);
			}

			if(!is_array($aData)) {
				$aData = array();
			}
		}

		reset($aData);
		return $aData;
	}

	function _processAfterEdition($aData) {
		if(($aUserObj = $this->_navConf("/process/afteredition/")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($aUserObj)) {
				$this->oForm->callRunneable(
					$aUserObj,
					$aData
				);
			}
		}
	}

	function _edition() {

		if($this->_isClearSubmitted()) {
			// clearsubmitted should display a blank-data page
				// except if edition or new i18n requested

			if($this->oForm->editionRequested() || $this->newI18nRequested()) {
				return TRUE;
			}

			return FALSE;
		}

		return ($this->_currentEntryId() !== FALSE);
	}

	function _getThisStoredData($sName) {
		return $this->_getStoredData($sName);
	}

	function __getDbData($sTablename, $sKeyname, $iUid, $sFields = "*") {

		$aRes = array();

		$sSql = $GLOBALS["TYPO3_DB"]->SELECTquery(
			$sFields,
			$sTablename,
			$sKeyname . " = '" . $iUid . "'"
		);

		$rSql = $this->oForm->_watchOutDB(
			$GLOBALS["TYPO3_DB"]->sql_query($sSql),
			$sSql
		);

		if(($aRes = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
			reset($aRes);
			return $aRes;
		}

		return FALSE;
	}

	function _getStoredData($sName = FALSE) {

		if(empty($this->__aStoredData)) {

			$this->__aStoredData = array();

			$tablename	= $this->tableName();
			$keyname	= $this->keyName();

			$editid = $this->_currentEntryId();

			if($editid !== FALSE) {

				if(($this->__aStoredData = $this->__getDbData($tablename, $keyname, $editid)) !== FALSE) {
					$this->__aStoredI18NParent = FALSE;
					// on va rechercher la configuration du champ en question

					reset($this->oForm->aORenderlets);
					$aRdts = array_keys($this->oForm->aORenderlets);
					while(list(, $sName) = each($aRdts)) {
						if(($sConfirm = $this->oForm->aORenderlets[$sName]->_navConf("/confirm")) !== FALSE) {
							$this->__aStoredData[$sName] = $this->__aStoredData[$sConfirm];
						}
					}
				} else {
					$this->oForm->mayday("DATAHANDLER DB : EDITION OF ENTRY " . $editid . " FAILED");
				}
			}
		}

		if(is_array($this->__aStoredData)) {

			if($sName !== FALSE) {
				if(array_key_exists($sName, $this->__aStoredData)) {
					return $this->__aStoredData[$sName];
				} else {
					return "";
				}
			}

			reset($this->__aStoredData);
			return $this->__aStoredData;
		}

		if($sName !== FALSE) {
			return "";
		} else {
			return array();
		}
	}

	function newI18nRequested() {

		if($this->oForm->aAddVars !== FALSE) {
			reset($this->oForm->aAddVars);
			while(list($sKey, ) = each($this->oForm->aAddVars)) {
				if(is_array($this->oForm->aAddVars[$sKey]) && array_key_exists("action", $this->oForm->aAddVars[$sKey]) && $this->oForm->aAddVars[$sKey]["action"] === "requestNewI18n") {
					if($this->tablename() === $this->oForm->aAddVars[$sKey]["params"]["tablename"] && $this->tablename()) {
						$sOurSafeLock = $this->oForm->_getSafeLock(
							"requestNewI18n" . ":" . $this->oForm->aAddVars[$sKey]["params"]["tablename"] . ":" . $this->oForm->aAddVars[$sKey]["params"]["recorduid"] . ":" . $this->oForm->aAddVars[$sKey]["params"]["languid"]
						);
						$sTheirSafeLock = $this->oForm->aAddVars[$sKey]["params"]["hash"];
						if($sOurSafeLock === $sTheirSafeLock) {
							return array(
								"i18n_parent" => $this->oForm->aAddVars[$sKey]["params"]["recorduid"],
								"sys_language_uid" => $this->oForm->aAddVars[$sKey]["params"]["languid"]
							);
						}
					}
					//return TRUE;
				}
			}
		}

		return FALSE;

		/*if(($aReqNewi18n = $this->oForm->_navConf("/formidable_i18n/new/", $GLOBALS)) !== FALSE) {

			$iParentUid = $aReqNewi18n["parentuid"];
			$iSysLangUid = $aReqNewi18n["languid"];

			return array(
				"i18n_parent" => $iParentUid,
				"sys_language_uid" => $iSysLangUid
			);
		}*/

		return FALSE;
	}

	function i18n_updateChildsOnSave() {
		return $this->defaultFalse("/i18n/update_childs_on_save/");
	}

	function i18n_currentRecordUsesDefaultLang() {
		return (intval($this->_getStoredData("sys_language_uid")) === 0);
	}

	function i18n_currentRecordUsesLang() {		// receives unknown number of arguments like 0, -1

		$aParams = func_get_args();
		return in_array(
			intval($this->_getStoredData("sys_language_uid")),
			$aParams
		);

	}

	function i18n_getStoredParent($bStrict = FALSE) {

		if($this->i18n() && $this->_edition()) {
			if($this->__aStoredI18NParent === FALSE) {

				$aData = $this->_getStoredData();

				if($this->i18n_currentRecordUsesDefaultLang()) {

					if($bStrict === TRUE) {
						return FALSE;
					}

					$this->__aStoredI18NParent = $aData;
				} else {
					$iParent = intval($aData["l18n_parent"]);
					if(($aParent = $this->__getDbData($this->tablename(),  $this->keyname(), $iParent)) !== FALSE) {
						$this->__aStoredI18NParent = $aParent;
					}
				}
			}
		} else {
			$this->__aStoredI18NParent = array();
		}

		return $this->__aStoredI18NParent;
	}

	function i18n_getThisStoredParent($sField, $bStrict = FALSE) {

		$aParent = $this->i18n_getStoredParent($bStrict);
		if(array_key_exists($sField, $aParent)) {
			return $aParent[$sField];
		}

		return FALSE;
	}

	function fillStandardTYPO3fields() {
		return $this->isTrue("/fillstandardtypo3fields");
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/dh_db/api/class.tx_dhdb.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/dh_db/api/class.tx_dhdb.php"]);
	}
?>
