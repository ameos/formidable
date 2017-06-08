<?php

	class formidable_mainvalidator extends formidable_mainobject {

		function _matchConditions($aConditions = FALSE) {
			if($aConditions === FALSE) {
				$aConditions = $this->aElement;
			}

			return $this->oForm->_matchConditions($aConditions);
		}

		function validate(&$oRdt) {

			$sAbsName = $oRdt->getAbsName();
			$mValue = $oRdt->getValue();
			$aKeys = array_keys($this->_navConf("/"));
			reset($aKeys);
			while(!$oRdt->hasError() && list(, $sKey) = each($aKeys)) {
				
				/***********************************************************************
				*
				*	/required
				*
				***********************************************************************/

				if($sKey{0} === "r" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "required")) {
					if($this->_isEmpty($oRdt, $mValue)) {
						if(($mMessage = $this->_navConf("/" . $sKey . "/message")) !== FALSE && tx_ameosformidable::isRunneable($mMessage)) {
							$mMessage = $oRdt->callRunneable($mMessage);
						}
						
						$this->oForm->_declareValidationError(
							$sAbsName,
							"STANDARD:required",
							$this->oForm->_getLLLabel($mMessage)
						);

						break;
					}
				}

				/***********************************************************************
				*
				*	/authentified
				*
				***********************************************************************/

				if($sKey{0} === "a" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "authentified")) {
					if(!$this->_isAuthentified()) {
						$message = $this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message/"));
						$this->oForm->_declareValidationError(
							$sAbsName,
							"STANDARD:authentified",
							$message
						);

						break;
					}
				}


				/***********************************************************************
				*
				*	/maxsize
				*
				***********************************************************************/

				if($sKey{0} === "m" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "maxsize")) {

					$iMaxSize = intval($this->_navConf("/" . $sKey . "/value/"));

					if($this->_isTooLong($mValue, $iMaxSize)) {
						$message = $this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message/"));
						$this->oForm->_declareValidationError(
							$sAbsName,
							"STANDARD:maxsize",
							$message,
							$mValue
						);

						break;
					}
				}




				/***********************************************************************
				*
				*	/minsize
				*
				***********************************************************************/

				if($sKey{0} === "m" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "minsize")) {

					$iMinSize = intval($this->_navConf("/" . $sKey . "/value/"));

					if($this->_isTooSmall($mValue, $iMinSize)) {
						$message = $this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message/"));
						$this->oForm->_declareValidationError(
							$sAbsName,
							"STANDARD:minsize",
							$message,
							$mValue
						);

						break;
					}
				}




				/***********************************************************************
				*
				*	/size
				*
				***********************************************************************/

				if($sKey{0} === "s" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "size")) {

					$iSize = intval($this->_navConf("/" . $sKey . "/value/"));

					if(!$this->_sizeIs($mValue, $iSize)) {
						$message = $this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message/"));
						$this->oForm->_declareValidationError(
							$sAbsName,
							"STANDARD:size",
							$message,
							$mValue
						);

						break;
					}
				}





				/***********************************************************************
				*
				*	/sameas
				*
				***********************************************************************/

				if($sKey{0} === "s" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "sameas")) {

					$sameas = trim($this->_navConf("/" . $sKey . "/value/"));

					if(array_key_exists($sameas, $this->oForm->aORenderlets)) {
						$samevalue = $this->oForm->aORenderlets[$sameas]->getValue();
						
						if(!$this->_isSameAs($mValue, $samevalue)) {
							$message = $this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message/"));
							$this->oForm->_declareValidationError(
								$sAbsName,
								"STANDARD:sameas",
								$message,
								$samevalue
							);

							break;
						}
					}
				}




				/***********************************************************************
				*
				*	/email
				*
				***********************************************************************/

				if($sKey{0} === "e" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "email")) {
					if(!$this->_isEmail($mValue)) {
						$message = $this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message/"));
						$this->oForm->_declareValidationError(
							$sAbsName,
							"STANDARD:email",
							$message,
							$mValue
						);

						break;
					}
				}




				/***********************************************************************
				*
				*	/userobj
				*	@deprecated; use custom instead
				*
				***********************************************************************/

				if($sKey{0} === "u" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "userobj")) {
					$this->oForm->mayday("[" . $oRdt->getName() . "] <b>/validator:STANDARD/userobj is deprecated.</b> Use /validator:STANDARD/custom instead.");
				}




				/***********************************************************************
				*
				*	/unique
				*
				***********************************************************************/

				if($sKey{0} === "u" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "unique")) {
					// field value has to be unique in the database
					// checking this

					if(!$this->_isUnique($oRdt, $mValue)) {
						$this->oForm->_declareValidationError(
							$sAbsName,
							"STANDARD:unique",
							$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message/")),
							$mValue
						);

						break;
					}
				}




				/***********************************************************************
				*
				*	/custom
				*
				***********************************************************************/

				if($sKey{0} === "c" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "custom")) {
					$mCustom = $this->_navconf("/" . $sKey);
					if(tx_ameosformidable::isRunneable($mCustom)) {
						
						if($this->oForm->isUserObj($mCustom)) {
							$mResult = $this->oForm->_callUserObj($mCustom, array("value" => $mValue));
						} else {
							$mResult = $this->oForm->callRunneable($mCustom, $mValue);
						}
						
						if($mResult !== TRUE) {
							if(is_string($mResult)) {
								$message = $this->oForm->_getLLLabel($mResult);
							} else {
								$message = $this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message/"));
							}

							$this->oForm->_declareValidationError(
								$sAbsName,
								"STANDARD:custom",
								$message,
								$mValue
							);

							break;
						}
					}
				}
			}
		}

		
		function _isEmpty(&$oRdt, $mValue) {
			return $oRdt->_emptyFormValue($mValue);
		}

		function _isTooLong($mValue, $maxSize) {
			
			if(is_array($mValue)) {
				return (count($mValue) > $maxSize);
			}
			
			return (strlen(trim($mValue)) > $maxSize);
		}

		function _isTooSmall($mValue, $minSize) {

			if(is_array($mValue)) {
				return (count($mValue) < $minSize);
			}
			
			return (strlen(trim($mValue)) < $minSize);
		}
		
		function _sizeIs($mValue, $iSize) {

			if(is_array($mValue)) {
				return (count($mValue) == intval($iSize));
			}
			
			return (strlen(trim($mValue)) == $iSize);
		}

		function _isSameAs($mValue1, $mValue2) {
			return ($mValue1 === $mValue2);
		}

		function _isEmail($mValue) {
			return trim($mValue) == "" || \TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($mValue);
		}

		function _isAuthentified() {
			return (is_array(($aUser = $GLOBALS["TSFE"]->fe_user->user)) && array_key_exists("uid", $aUser) && intval($aUser["uid"]) > 0);
		}

		function _isUnique(&$oRdt, $mValue) {

			$sDeleted = "";

			if(($sTable = $this->_navConf("/unique/tablename")) !== FALSE) {
				if(($sField = $this->_navConf("/unique/field")) === FALSE) {
					$sField = $oRdt->getName();
				}

				$sKey = FALSE;

			} else {
				if($oRdt->hasDataBridge() && ($oRdt->oDataBridge->oDataSource->_getType() === "DB")) {
					$sKey = $oRdt->oDataBridge->oDataSource->sKey;
					$sTable = $oRdt->oDataBridge->oDataSource->sTable;
					$sField = $oRdt->dbridged_mapPath();
				} else {
					$aDhConf = $this->oForm->_navConf($this->oForm->sXpathToControl . "datahandler/");
					$sKey = $this->oForm->oDataHandler->keyName();
					$sTable = $this->oForm->oDataHandler->tableName();
					$sField = $oRdt->getName();
				}


				if($this->defaultFalse("/unique/deleted/") === TRUE) {
					$sDeleted = " AND deleted != 1";
				}
			}

			$mValue = addslashes($mValue);

			if($oRdt->hasDataBridge()) {
				$oDset = $oRdt->dbridged_getCurrentDsetObject();
				if($oDset->isAnchored()) {
					$sWhere = $GLOBALS['TYPO3_DB']->quoteStr($sField, '') . " = '" . $GLOBALS['TYPO3_DB']->quoteStr($mValue, '') . "' AND " . $GLOBALS['TYPO3_DB']->quoteStr($sKey, '') . " != '" . $GLOBALS['TYPO3_DB']->quoteStr($oDset->getKey(), '') . "'" . $sDeleted;
				} else {
					$sWhere = $GLOBALS['TYPO3_DB']->quoteStr($sField, '') . " = '" . $GLOBALS['TYPO3_DB']->quoteStr($mValue, '') . "'" . $sDeleted;
				}
			} else {
				if($this->oForm->oDataHandler->_edition()) {
					$sWhere = $GLOBALS['TYPO3_DB']->quoteStr($sField, '') . " = '" . $GLOBALS['TYPO3_DB']->quoteStr($mValue, '') . "' AND " . $GLOBALS['TYPO3_DB']->quoteStr($sKey, '') . " != '" . $this->oForm->oDataHandler->_currentEntryId() . "'" . $sDeleted;
				} else {
					$sWhere = $GLOBALS['TYPO3_DB']->quoteStr($sField, '') . " = '" . $GLOBALS['TYPO3_DB']->quoteStr($mValue, '') . "'" . $sDeleted;
				}
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
	
	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainvalidator.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainvalidator.php"]);
	}
?>
