<?php 

	class formidable_maindatahandler extends formidable_mainobject {

		var $entryId		= null;
		var $forcedId		= null;		// wether $entryId id forced by the PHP, or not
		var $newEntryId		= null;

		var $__aStoredData		= array();	// internal use only
		var $__aFormData		= array();	// internal use only
		var $__aFormDataManaged	= array();	// internal use only

		var $__aCols = array();				// columns associated to an existing renderlet
		var $__aListData = array();			// contextual data, containing the current list record
		var $__aParentListData = array();			// contextual data, containing the current list record

		var $aT3Languages = FALSE;

		var $bHasCreated = FALSE;
		var $bHasEdited = FALSE;
		var $aProcessBeforeRenderData = FALSE;

		var $sSubmittedValue = FALSE;


		function _init(&$oForm, $aElement, $aObjectType, $sXPath) {
			parent::_init($oForm, $aElement, $aObjectType, $sXPath);
			if($this->i18n()) {
				if(($this->i18n_getDefLangUid() === FALSE)) {
					$this->oForm->mayday("DATAHANDLER: <b>/i18n/use</b> is active but no <b>/i18n/defLangUid</b> given");
				}
			}
		}

		/**
		 * Processes data returned by the HTML Form after validation, and only if validated
		 * Note that this is only the 'abstract' definition of this function
		 *  as it must be overloaded in the specialized DataHandlers
		 *
		 * @return	void
		 */
		function _doTheMagic($bShouldProcess = TRUE) {
		}


		/**
		 * Returns the slashstripped GET vars array
		 *
		 * @return	array		GET vars array
		 * @see	formidable_maindatahandler::_GP()
		 */
		function _G() {
			return $this->oForm->_getRawGet();
		}

		/**
		 * Returns the slashstripped POST vars array
		 *  merged with the _FILES vars array
		 *
		 * @return	array		POST vars array
		 * @see	formidable_maindatahandler::_GP()
		 */
		function _P($sName = FALSE) {
			$aRawPost = $this->oForm->_getRawPost();
			if($sName !== FALSE) {
				if(array_key_exists($sName, $aRawPost)) {
					return $aRawPost[$sName];
				} else {
					return "";
				}
			}

			return $aRawPost;
		}

		/**
		 * Returns the slashstripped _FILES vars array
		 *
		 * @return	array		_FILES vars array
		 * @see	formidable_maindatahandler::_P()
		 */
		function _F() {
			return  $this->oForm->_getRawFile();
		}

		function groupFileInfoByVariable(&$top, $info, $attr) {
			return $this->oForm->groupFileInfoByVariable($top, $info, $attr);
		}

		function reinitWithId($iUid) {
			$this->entryId = $iUid;
			$this->refreshAllData();
		}

		/**
		 * Returns the merged GET and POST arrays
		 *  using the formidable_maindatahandler::_G() and formidable_maindatahandler::_P() functions
		 *  and therefore not slashstripped
		 *
		 *	POST overrides GET
		 *
		 * @return	array		GET and POST vars array
		 */
		function _GP() {

			return t3lib_div::array_merge_recursive_overrule(
				$this->_G(),
				$this->_P()
			);
		}

		/**
		 * Returns the merged GET and POST arrays
		 *  using the formidable_maindatahandler::_G() and formidable_maindatahandler::_P() functions
		 *  and therefore not slashstripped
		 *
		 *	GET overrides POST
		 *
		 * @return	array		GET and POST vars array
		 */
		function _PG() {
			return t3lib_div::array_merge_recursive_overrule(
				$this->_P(),
				$this->_G()
			);
		}

		function forceSubmittedValue($sSubmittedValue) {
			$this->sSubmittedValue = $sSubmittedValue;
		}
		
		/**
		 * Determines if the FORM is submitted
		 *  using the AMEOSFORMIDABLE_SUBMITTED constant for naming the POSTED variable
		 *
		 * @return	boolean
		 */

		function _getSubmittedValue($sFormId = FALSE) {
			if($this->sSubmittedValue !== FALSE) {
				return $this->sSubmittedValue; 
			}
			
			$aP = $this->oForm->_getRawPost($sFormId);

			if(array_key_exists("AMEOSFORMIDABLE_SUBMITTED", $aP) && (trim($aP["AMEOSFORMIDABLE_SUBMITTED"]) !== "")) {
				return trim($aP["AMEOSFORMIDABLE_SUBMITTED"]);
			}

			return FALSE;
		}

		function _isSubmitted($sFormId = FALSE) {

			/*return in_array(
				$this->_getSubmittedValue(),
				array(
					AMEOSFORMIDABLE_EVENT_SUBMIT_FULL,		// full submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH,	// refresh submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_TEST,		// test submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT,		// draft submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR,		// clear submit
					AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH,	// clear submit
				)
			);*/


			return (
				$this->_isFullySubmitted($sFormId) ||
				$this->_isRefreshSubmitted($sFormId) ||
				$this->_isTestSubmitted($sFormId) ||
				$this->_isDraftSubmitted($sFormId) ||
				$this->_isClearSubmitted($sFormId) ||
				$this->_isSearchSubmitted($sFormId)
			);
		}

		function _isFullySubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_FULL);
		}

		function _isRefreshSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH);
		}

		function _isTestSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_TEST);
		}

		function _isDraftSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT);
		}

		function _isClearSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR);
		}

		function _isSearchSubmitted($sFormId = FALSE) {
			return ($this->_getSubmittedValue($sFormId) == AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH);
		}

		function isFullySubmitted($sFormId = FALSE) {
			return $this->_isFullySubmitted($sFormId);
		}

		function isRefreshSubmitted($sFormId = FALSE) {
			return $this->_isRefreshSubmitted($sFormId);
		}

		function isTestSubmitted($sFormId = FALSE) {
			return $this->_isTestSubmitted($sFormId);
		}

		function isDraftSubmitted($sFormId = FALSE) {
			return $this->_isDraftSubmitted($sFormId);
		}

		function isClearSubmitted($sFormId = FALSE) {
			return $this->_isClearSubmitted($sFormId);
		}

		function isSearchSubmitted($sFormId = FALSE) {
			return $this->_isSearchSubmitted($sFormId);
		}

		function getSubmitter($sFormId = FALSE) {
			$aP = $this->oForm->_getRawPost($sFormId);

			if(array_key_exists("AMEOSFORMIDABLE_SUBMITTER", $aP) && (trim($aP["AMEOSFORMIDABLE_SUBMITTER"]) !== "")) {
				$sSubmitter = $aP["AMEOSFORMIDABLE_SUBMITTER"];
				return $sSubmitter;
			}

			return FALSE;
		}

		function getFormData() {
			reset($this->__aFormData);
			return $this->__aFormData;
		}

		function _getFormData() {
			return $this->getFormData();
		}

		function getThisFormData($sName) {
			$oRdt = $this->oForm->rdt($sName);
			$sAbsName = $oRdt->getAbsName();
			$sAbsPath = str_replace(".", "/", $sAbsName);
			return $this->oForm->navDeepData($sAbsPath, $this->__aFormData);
		}

		function _getThisFormData($sAbsName) {
			return $this->getThisFormData($sAbsName);
		}

		function _processBeforeRender($aData) {

			if(($mRunneable = $this->_navConf("/process/beforerender/")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mRunneable)) {
					$aData = $this->callRunneable(
						$mRunneable,
						$aData
					);


					if(!is_array($aData)) {
						$aData = array();
					}

					reset($aData);
					return $aData;
				}
			}

			return FALSE;
		}

		function getFormDataManaged() {
			$this->oForm->mayday("getFormDataManaged() is deprecated");
			return $this->_getFormDataManaged();
		}

		function _getFormDataManaged() {
			$this->oForm->mayday("_getFormDataManaged() is deprecated");
			if(empty($this->__aFormDataManaged)) {

				$this->__aFormDataManaged = array();
				$aKeys = array_keys($this->oForm->aORenderlets);

				reset($aKeys);
				while(list(, $sAbsName) = each($aKeys)) {
					if(!$this->oForm->aORenderlets[$sAbsName]->_renderOnly() && !$this->oForm->aORenderlets[$sAbsName]->_readOnly() && $this->oForm->aORenderlets[$sAbsName]->hasBeenDeeplySubmitted()) {
						$this->__aFormDataManaged[$sAbsName] = $this->oForm->aORenderlets[$sAbsName]->getValue();
					}
				}
			}

			reset($this->__aFormDataManaged);
			return $this->__aFormDataManaged;
		}

		function _getFlatFormData() {
			$this->oForm->mayday("_getFlatFormData() is deprecated");
			$aFormData = $this->_getFormData();
			$aRes = array();
			reset($aFormData);
			while(list($sName, $mData) = each($aFormData)) {
				if(array_key_exists($sName, $this->oForm->aORenderlets)) {
					$aRes[$sName] = $this->oForm->aORenderlets[$sName]->_flatten($mData);
				}
			}

			reset($aRes);
			return $aRes;
		}

		function _getFlatFormDataManaged() {
			$this->oForm->mayday("_getFlatFormDataManaged() is deprecated");
			$aFormData = $this->_getFormDataManaged();

			$aFlatFormDataManaged = array();
			reset($aFormData);
			while(list($sAbsName, $mData) = each($aFormData)) {
				if(array_key_exists($sAbsName, $this->oForm->aORenderlets)) {

					if($this->oForm->useNewDataStructure()) {
						$this->oForm->mayday("not implemented yet:" . __FILE__ . ":" . __LINE__);
						// data will be stored under abs name
						$sNewName = $sAbsName;
					} else {
						if(!$this->oForm->aORenderlets[$sAbsName]->_renderOnly() && !$this->oForm->aORenderlets[$sAbsName]->_readOnly()) {
							// FormDataManaged strips readonly fields
								// whereas since revision 200, FormData don't

							$sNewName = $this->oForm->aORenderlets[$sAbsName]->getName();
							$aFlatFormDataManaged[$sNewName] = $this->oForm->aORenderlets[$sAbsName]->_flatten($mData);
						}
					}
				}
			}

			reset($aFlatFormDataManaged);
			return $aFlatFormDataManaged;
		}

		/**
		 * Determines if something was not validated during the validation process
		 *
		 * @return	boolean	TRUE if everything is valid, FALSE if not
		 */
		function _allIsValid() {
			return (count($this->oForm->_aValidationErrors) == 0);
		}

		function _isValid($sAbsName) {

			if(is_array($this->oForm->_aValidationErrors) && array_key_exists($sAbsName, $this->oForm->_aValidationErrors)) {
				$sElementHtmlId = $this->oForm->aORenderlets[$sAbsName]->_getElementHtmlId();
				if(array_key_exists($sElementHtmlId, $this->oForm->_aValidationErrorsByHtmlId)) {
					return FALSE;
				}
			}

			return TRUE;
		}

		function edition() {
			return $this->_edition();
		}

		function creation() {
			return $this->_creation();
		}

		/**
		 * Determines if the DataHandler should work in 'edition' mode
		 * Note that this is only the 'abstract' definition of this function
		 *  in the simple case where your DataHandler should never have to edit data
		 *
		 * @return	boolean	TRUE if edition mode, FALSE if not
		 */
		function _edition() {
			return FALSE;
		}

		function _creation() {
			return !$this->_edition();
		}

		/**
		 * Gets the data previously stored by the DataHandler
		 * for edition
		 * Note that this is only the 'abstract' definition of this function
		 *  in the simple case where your DataHandler should never have to edit data
		 *
		 * @return	boolean	TRUE if edition mode, FALSE if not
		 * @see	formidable_maindatahandler::_edition()
		 */
		function _getStoredData($sName = FALSE) {

			if($sName !== FALSE) {
				return "";
			}

			return array();
		}

		function getStoredData($sName = FALSE) {
			return $this->_getStoredData($sName);
		}

		function refreshStoredData() {
			$this->__aStoredData = array();
			$this->_getStoredData();
		}

		function refreshFormData() {
			$this->__aFormData = array();
			$aKeys = array_keys($this->oForm->aORenderlets);
			reset($aKeys);
			while(list(, $sAbsName) = each($aKeys)) {
				if(!$this->oForm->aORenderlets[$sAbsName]->hasParent()) {
					$sAbsPath = str_replace(".", "/", $sAbsName);
					$this->oForm->setDeepData(
						$sAbsPath,
						$this->__aFormData,
						$this->oForm->aORenderlets[$sAbsName]->getValue()
					);
				}
			}

			$this->oForm->checkPoint(
				array(
					"after-fetching-formdata",
				)
			);

			$this->aProcessBeforeRenderData = FALSE;

			if(($aNewData = $this->_processBeforeRender($this->__aFormData)) !== FALSE) {
				$aDiff = $this->oForm->array_diff_recursive($aNewData, $this->__aFormData);
				if(count($aDiff) > 0) {
					$this->aProcessBeforeRenderData = $aDiff;
				}
			}
		}

		function alterVirginData($aData) {
			if(($mRun = $this->_navConf("/altervirgindata")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mRun)) {
					return $this->callRunneable($mRun, $aData);
				}
			}

			return $aData;
		}

		function alterSubmittedData($aData) {
			if(($mRun = $this->_navConf("/altersubmitteddata")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mRun)) {
					return $this->callRunneable($mRun, $aData);
				}
			}

			return $aData;
		}

		function refreshAllData() {
			$this->refreshStoredData();
			$this->refreshFormData();
		}

		function currentId() {
			return $this->_currentEntryId();
		}

		function currentEntryId() {
			return $this->_currentEntryId();
		}

		function _currentEntryId() {

			if(!is_null($this->newEntryId)) {
				return $this->newEntryId;
			}

			if(!is_null($this->entryId)) {
				return $this->entryId;
			}

			if($this->_isSubmitted() && !$this->_isClearSubmitted()) {

				$form_id = $this->oForm->formid;

				$aPost = t3lib_div::_POST();

				$aPost	= is_array($aPost[$form_id]) ? $aPost[$form_id] : array();
				$aFiles	= is_array($GLOBALS["_FILES"][$form_id]) ? $GLOBALS["_FILES"][$form_id] : array();
				$aP = t3lib_div::array_merge_recursive_overrule($aPost, $aFiles);

				t3lib_div::stripSlashesOnArray($aP);

				if(array_key_exists("AMEOSFORMIDABLE_ENTRYID", $aP) && trim($aP["AMEOSFORMIDABLE_ENTRYID"]) !== "") {
					return intval($aP["AMEOSFORMIDABLE_ENTRYID"]);
				}
			}

			return FALSE;
		}

		function currentParentEntryId() {
			if($this->i18n()) {
				return $this->i18n_getThisStoredParent(
					$this->keyName()
				);
			} else {
				return $this->_currentEntryId();
			}
		}

		function getHumanFormData() {
			return $this->_getHumanFormData();
		}

		function _getHumanFormData() {

			$aFormData = $this->_getFormData();

			$aValues = array();
			$aLabels = array();

			reset($aFormData);
			while(list($elementname, $value) = each($aFormData)) {

				if(array_key_exists($elementname, $this->oForm->aORenderlets)) {
					$aValues[$elementname] = $this->oForm->aORenderlets[$elementname]->_getHumanReadableValue($value);
					$aLabels[$elementname] = $this->oForm->_getLLLabel($this->oForm->aORenderlets[$elementname]->aElement["label"]);
				}
			}

			reset($aValues);
			reset($aLabels);

			return array(
				"labels"	=> $aLabels,
				"values"	=> $aValues
			);
		}

		function _initCols() {
			$this->__aCols = array();
		}

		function getListData($sKey = FALSE) {
			return $this->_getListData($sKey);
		}

		function isIterating() {
			return $this->__aListData !== FALSE;
		}

		function _getListData($sKey = FALSE) {
			if($this->__aListData === FALSE) {
				return FALSE;
			}

			$iLastListData = (count($this->__aListData) - 1);
			if($iLastListData < 0) {
				return FALSE;
			}

			if($sKey !== FALSE) {

				if(array_key_exists($sKey, $this->__aListData[$iLastListData])) {
					return $this->__aListData[$iLastListData][$sKey];
				} else {
					return FALSE;
				}
			} else {
				if(!empty($this->__aListData)) {
					return $this->__aListData[$iLastListData];
				}
			}

			return array();
		}

		function _getParentListData($sKey = FALSE) {
			if($sKey !== FALSE) {

				if(array_key_exists($sKey, $this->__aParentListData)) {

					reset($this->__aParentListData);
					return $this->__aParentListData[$sKey];
				} else {

					return FALSE;
				}
			} else {

				reset($this->__aParentListData);
				return $this->__aParentListData;
			}
		}

		function i18n() {
			return $this->defaultFalse("/i18n/use");
		}

		function i18n_getSysLanguageUid() {
			// http://lists.netfielders.de/pipermail/typo3-at/2005-November/007373.html

			if($this->oForm->rdt("sys_language_uid") !== FALSE) {
				$oRdt = $this->oForm->rdt("sys_language_uid");
				return $oRdt->getValue();
			} else {
				return $GLOBALS["TSFE"]->tmpl->setup["config."]["sys_language_uid"];
			}
		}

		function i18n_getChildRecords($iParentUid) {

			if(($sTableName = $this->tableName())!== FALSE) {

				$aRecords = array();

				$rSql = $GLOBALS["TYPO3_DB"]->exec_SELECTquery(
					"*",
					$sTableName,
					"l18n_parent='" . $iParentUid . "'"
				);

				while(($aRs = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
					$aRecords[$aRs["sys_language_uid"]] = $aRs;
				}

				if(!empty($aRecords)) {
					reset($aRecords);
					return $aRecords;
				}
			}

			return array();
		}

		function i18n_getDefLangUid() {
			return $this->_navConf("/i18n/deflanguid");
		}

		function getT3Languages($aIgnoreLang = array()) {

			if($this->aT3Languages === FALSE) {

				$this->aT3Languages = array();

				$rSql = $GLOBALS["TYPO3_DB"]->exec_SELECTquery(
					"*",
					"sys_language",
					"1=1" . $this->oForm->cObj->enableFields("sys_language")//"hidden=0"
				);

				while(($aRs = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
					if(!in_array($aRs['uid'], $aIgnoreLang)) {
						$this->aT3Languages[$aRs["uid"]] = $aRs;
					}
				}
			}

			reset($this->aT3Languages);
			return $this->aT3Languages;
		}

		function i18n_currentRecordUsesDefaultLang() {
			return FALSE;
		}

		function tableName() {
			return $this->oForm->_navConf($this->oForm->sXpathToControl . "datahandler/tablename");
		}

		function keyName() {
			if(($sKey = $this->oForm->_navConf($this->oForm->sXpathToControl . "datahandler/keyname")) === FALSE) {
				return "uid";
			}

			return $sKey;
		}

		function newI18nRequested() {
			return FALSE;
		}

		function i18n_getValueDefaultLang() {
			if($this->i18n()) {

			}
		}

		function i18n_getStoredParent($bStrict = TRUE) {
			return FALSE;
		}

		function i18n_getThisStoredParent($sField, $bStrict = TRUE) {
			if(($aStoredParent = $this->i18n_getStoredParent($bStrict)) !== FALSE) {
				if(array_key_exists($sField, $aStoredParent)) {
					return $aStoredParent[$sField];
				}
			}

			return FALSE;
		}

		function getRdtValue($sAbsName) {

			if(!array_key_exists($sAbsName, $this->oForm->aORenderlets)) {
				return "";
			}

			if($this->oForm->aORenderlets[$sAbsName]->bForcedValue === TRUE) {
				return $this->oForm->aORenderlets[$sAbsName]->mForcedValue;
			}

			if($this->oForm->aORenderlets[$sAbsName]->i18n_shouldNotTranslate()) {

				if(($aStoredI18NParent = $this->i18n_getStoredParent(TRUE)) !== FALSE) {
					// TODO: do a better mapping between rdt name and the data structure
						// like databridges, see $this->getRdtValue_noSubmit_edit()

					$sLocalName = $this->oForm->aORenderlets[$sAbsName]->getName();
					if(array_key_exists($sLocalName, $aStoredI18NParent)) {
						return $this->oForm->aORenderlets[$sAbsName]->_unFlatten(
							$aStoredI18NParent[$sLocalName]
						);
					}
				}
			} elseif($this->oForm->aORenderlets[$sAbsName]->_isClearSubmitted()) {

				if($this->oForm->aORenderlets[$sAbsName]->_edition()) {
					return $this->getRdtValue_noSubmit_edit($sAbsName);
				} else {
					return $this->getRdtValue_noSubmit_noEdit($sAbsName);
				}

			} elseif($this->oForm->aORenderlets[$sAbsName]->_isSubmitted()) {

				if($this->oForm->iForcedEntryId !== FALSE) {
					// we have to use a fresh new record from database
						// so let noSubmit_edit do the job (meaning: don't consider values from submitted POST, but only those from DB)

					return $this->getRdtValue_noSubmit_edit($sAbsName);
				} else {

					if(($mValue = $this->oForm->aORenderlets[$sAbsName]->__getValue()) !== FALSE) {
						return $mValue;
					} else {
						if($this->oForm->aORenderlets[$sAbsName]->_readOnly()) {
							if($this->oForm->aORenderlets[$sAbsName]->_edition()) {
								return $this->getRdtValue_submit_readonly_edition($sAbsName);
							} else {
								return $this->getRdtValue_submit_readonly_noEdition($sAbsName);
							}
						} else {
							if($this->oForm->aORenderlets[$sAbsName]->_edition()) {
								return $this->getRdtValue_submit_edition($sAbsName);
							} else {
								return $this->getRdtValue_submit_noEdition($sAbsName);
							}
						}
					}
				}
			} else {
				if($this->oForm->aORenderlets[$sAbsName]->_edition()) {
					return $this->getRdtValue_noSubmit_edit($sAbsName);
				} else {
					return $this->getRdtValue_noSubmit_noEdit($sAbsName);
				}
			}
		}

		function getRdtValue_submit_edition($sAbsName) {

			$sPath = str_replace(".", "/", $sAbsName);
			$sRelPath = $this->oForm->aORenderlets[$sAbsName]->getName();

			if(
				$this->aProcessBeforeRenderData !== FALSE && (
					($mValue = $this->oForm->navDeepData($sPath, $this->aProcessBeforeRenderData)) !== FALSE ||
					($mValue = $this->oForm->navDeepData($sRelPath, $this->aProcessBeforeRenderData)) !== FALSE
				)
			) {
				return $this->oForm->aORenderlets[$sAbsName]->_unFlatten($mValue);
			}



			$aGP = $this->_GP();
			if(array_key_exists($sAbsName, $aGP)) {
				return $aGP[$sAbsName];
			}

			if(array_key_exists($sAbsName, $this->oForm->aORenderlets)) {
				// converting abs name to htmlid to introduce lister rowuids in the path
				$sHtmlId = $this->oForm->aORenderlets[$sAbsName]->_getElementHtmlId();

				// removing the formid. prefix
				$sHtmlId = substr($sHtmlId, strlen($this->oForm->formid . "."));

				// converting id to data path
				$sAbsPath = str_replace(".", "/", $sHtmlId);
				if(($aRes = $this->oForm->navDeepData($sAbsPath, $aGP)) !== FALSE) {
					return $aRes;
				}
			}

			return "";
		}

		function getRdtValue_submit_noEdition($sName) {
			return $this->getRdtValue_submit_edition($sName);
		}

		function getRdtValue_submit_readonly_edition($sName) {
			# there is a bug here, as renderlet:BOX is readonly
			# and so nothing in a box might be submitted ?!
			if($this->oForm->aORenderlets[$sName]->hasChilds()) {
				# EDIT: bug might be solved with this hasChilds() test
				return $this->getRdtValue_submit_noEdition($sName);
			} else {
				return $this->getRdtValue_noSubmit_edit($sName);
			}
		}

		function getRdtValue_submit_readonly_noEdition($sName) {

			$aGP = $this->_GP();

			$sPath = str_replace(".", "/", $sName);

			if(($mValue = $this->oForm->aORenderlets[$sName]->__getValue()) !== FALSE) {			// value is prioritary if submitted
				return $this->oForm->aORenderlets[$sName]->_unFlatten($mValue);
			} elseif(($mValue = $this->oForm->aORenderlets[$sName]->__getDefaultValue()) !== FALSE) {
				return $this->oForm->aORenderlets[$sName]->_unFlatten($mValue);
			} elseif(($mValue = $this->oForm->navDeepData($sPath, $aGP)) !== FALSE) {

				// if rdt has no childs, do not use the posted data, as it will contain the post-flag "1"
				if($this->oForm->aORenderlets[$sName]->hasChilds()) {
					// this is needed as refreshFormData() only works on root-renderlets (no parents)
						// thus the renderlets have to fetch the data of their descendants themselves
						// this is, for instance, the case for renderlet:BOX
					return $this->oForm->aORenderlets[$sName]->_unFlatten($mValue);
				}
			}

			return "";
		}

		function getRdtValue_noSubmit_noEdit($sName) {

			if(array_key_exists($sName, $this->oForm->aORenderlets)) {

				if($this->_isClearSubmitted()) {
					$aGP = $this->_G();
				} else {
					$aGP = $this->_GP();
				}

				$sPath = str_replace(".", "/", $sName);
				$sRelPath = $this->oForm->aORenderlets[$sName]->getName();

				if(($mValue = $this->oForm->aORenderlets[$sName]->__getValue()) !== FALSE) {
					return $this->oForm->aORenderlets[$sName]->_unFlatten($mValue);
				} elseif(
					$this->aProcessBeforeRenderData !== FALSE && (
						($mValue = $this->oForm->navDeepData($sPath, $this->aProcessBeforeRenderData)) !== FALSE ||
						($mValue = $this->oForm->navDeepData($sRelPath, $this->aProcessBeforeRenderData)) !== FALSE
					)
				) {
					return $this->oForm->aORenderlets[$sName]->_unFlatten($mValue);
				} elseif(($mValue = $this->oForm->navDeepData($sPath, $aGP)) !== FALSE) {
					return $this->oForm->aORenderlets[$sName]->_unFlatten($mValue);
				} elseif(($mValue = $this->oForm->aORenderlets[$sName]->__getDefaultValue()) !== FALSE) {
					return $this->oForm->aORenderlets[$sName]->_unFlatten($mValue);
				}
			}

			return "";
		}

		function getRdtValue_noSubmit_edit($sAbsName) {

			if(array_key_exists($sAbsName, $this->oForm->aORenderlets)) {

				if(($mValue = $this->oForm->aORenderlets[$sAbsName]->__getValue()) !== FALSE) {	// value a toujours le dessus
					return $this->oForm->aORenderlets[$sAbsName]->_unFlatten($mValue);
				} else {

					$mRes = null;

					if($this->oForm->aORenderlets[$sAbsName]->hasRelationMM()) {
						$sMMTable = $this->oForm->aORenderlets[$sAbsName]->getRelationMMTable();

						// building mm-request
						$rSql = $GLOBALS["TYPO3_DB"]->exec_SELECTquery(
							"uid_foreign",
							$sMMTable,
							"uid_local='" . $this->oForm->db_quoteStr($this->currentId()) . "'",
							"",
							"sorting asc"
						);

						$aForeignUids = array();
						while(($aRs = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
							$aForeignUids[] = $aRs["uid_foreign"];
						}

						$mRes =& $aForeignUids;
						reset($mRes);

					} elseif($this->oForm->aORenderlets[$sAbsName]->hasDataBridge()) {

						$oDataSet =& $this->oForm->aORenderlets[$sAbsName]->dbridged_getCurrentDsetObject();

						// sure that dataset is anchored, as we already tested it to be in noSubmit_edit
						$aData = t3lib_div::array_merge_recursive_overrule(		// allowing GET to set values
							$oDataSet->getData(),
							$this->_G()
						);

						if(($sMappedPath = $this->oForm->aORenderlets[$sAbsName]->dbridged_mapPath()) !== FALSE) {
							#debug($sMappedPath, $sAbsName . " mapped path");
							if(($mData = $this->oForm->navDeepData($sMappedPath, $aData)) !== FALSE) {
								$mRes = $mData;
							}
						} else {
							#debug($sAbsName, "no path mapped!!!");
						}
					} else {

						$sPath = str_replace(".", "/", $sAbsName);
						$sRelPath = $this->oForm->aORenderlets[$sAbsName]->getName();

						if(
							$this->aProcessBeforeRenderData !== FALSE && (
								($mValue = $this->oForm->navDeepData($sPath, $this->aProcessBeforeRenderData)) !== FALSE ||
								($mValue = $this->oForm->navDeepData($sRelPath, $this->aProcessBeforeRenderData)) !== FALSE
							)
						) {
							return $this->oForm->aORenderlets[$sAbsName]->_unFlatten($mValue);
						}


						$aStored = $this->_getStoredData();
						$aData = t3lib_div::array_merge_recursive_overrule(		// allowing GET to set values
							$aStored,
							$this->_G()
						);

						$sNewName = $this->oForm->aORenderlets[$sAbsName]->getName();

						if(array_key_exists($sNewName, $aData)) {
							$mRes = $this->oForm->aORenderlets[$sAbsName]->_unFlatten($aData[$sNewName]);
						}
					}

					return $mRes;
				}
			}

			return "";
		}
	}

	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.maindatahandler.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.maindatahandler.php"]);
	}
?>
