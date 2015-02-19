<?php
/**
 * Plugin 'rdt_i18n' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdti18n extends formidable_mainrenderlet {

	var $aOButtons = array();

	function _render() {

		if(!$this->oForm->oDataHandler->i18n()) {
			$this->oForm->mayday("renderlet:I18N <b>'" . $this->_getName() . "'</b>: Datahandler has to declare <b>/i18n/use=true</b> for renderlet:I18N to work");
		}

		$aHtmlBag = array();

		$aCurData = $this->oForm->oDataHandler->_getListData();

		if(empty($aCurData)) {
			$aCurData = $this->oForm->oDataHandler->i18n_getStoredParent();
		}

		if(!empty($aCurData)) {
			$aIgnoreLang = array();
			if(($mIgnoreLang = $this->_navConf('/listlangtoignore')) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mIgnoreLang)) {
					$mIgnoreLang = $this->oForm->callRunneable($mIgnoreLang);
				}

				$aIgnoreLang = (is_array($mIgnoreLang)) ? array_map('trim', $mIgnoreLang) : array_map('trim', explode(',', $mIgnoreLang));
			}
			$aLangs = $this->oForm->oDataHandler->getT3Languages($aIgnoreLang);


			if(($mUidField = $this->_navConf("/uidfield")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mUidField)) {
					$mUidField = $this->oForm->callRunneable($mUidField);
				}
			} else {
				$mUidField = 'uid';
			}

			$iUid = $aCurData[$mUidField];
			$aChildRecords = $this->oForm->oDataHandler->i18n_getChildRecords($iUid);
			$aChildLanguages = array_keys($aChildRecords);

			switch($this->_navConf("/mode")) {
				case 'link':
				case 'redirect':
					$aHtmlBag = $this->linkMode($aLangs, $aCurData, $iUid, $aChildRecords, $aChildLanguages);
					break;

				case 'menu':
					$aHtmlBag = $this->menuMode($aLangs, $aCurData, $iUid, $aChildRecords, $aChildLanguages);
					break;

				default:
					$aHtmlBag = $this->btnMode($aLangs, $aCurData, $iUid, $aChildRecords, $aChildLanguages);
					break;
			}
		}

		return $aHtmlBag;
	}

	function linkMode($aLangs, $aCurData, $iUid, $aChildRecords, $aChildLanguages) {
		$aHtmlBag = array();
		reset($aLangs);
		$aKeys = array_keys($aLangs);
		while(list(, $iLangUid) = each($aKeys)) {

			if($iLangUid != $this->oForm->oDataHandler->i18n_getDefLangUid()) {

				if($this->oForm->__getEnvExecMode() !== "BE" || $GLOBALS["BE_USER"]->checkLanguageAccess($iLangUid)) {
					$aLang = $aLangs[$iLangUid];

					if(in_array($iLangUid, $aChildLanguages)) {
						$bExists = TRUE;
					} else {
						$bExists = FALSE;
					}

					$aConf = array(
						"type" => "LINK",
						"label" => $aLang["title"] . ($bExists ? "" : " [NEW]"),
						"href" => $this->getEditionLink($aChildRecords, $aCurData, $iLangUid, $aChildLanguages)
					);

					if(($aCustomConf = $this->_navConf("/stdbutton")) !== FALSE) {
						if(isset($aCustomConf["onclick-default"]) || isset($aCustomConf["onclick"])) {
							unset($aConf["onclick-default"]);
						}

						$aConf = t3lib_div::array_merge_recursive_overrule(
							$aConf,
							$aCustomConf
						);
					}

					$sName = $this->_getName() . "-record-" . $iUid . "-lang-" . $iLangUid;
					$aConf["name"] = $sName;

					$iIndex = $this->oForm->pushForcedUserObjParam(
						array(
							"translation_exists" => $bExists,
							"sys_language_uid" => $iLangUid,
							"childrecords" => $aChildRecords,
							"childlanguages" => $aChildLanguages,
							"record" => $aCurData,
							"lang" => $aLang,
							"langs" => $aLangs,
						)
					);
					$this->oForm->pullForcedUserObjParam($iIndex);

					foreach($aConf as $sKey => $mValue) {
						if(tx_ameosformidable::isRunneable($mValue)) {
							$aConf[$sKey] = $this->oForm->callRunneable($mValue);
						}
					}
					$sOtherAttr = '';
					if(!empty($aConf['class'])) {
						$sOtherAttr.= ' class="' . $aConf['class'] . '"';
					}

					if(!empty($aConf['custom'])) {
						$sOtherAttr.= ' ' . $aConf['custom'];
					}

					if($aConf['visible'] === FALSE) {
						$sOtherAttr.= ' style="display:none;"';
					}

					$aHtmlBag[] = '<a href="' . $aConf['href'] . '" ' . $sOtherAttr . '>' . $aConf['label'] . '</a>';
				}
			}
		}

		return implode("", $aHtmlBag);
	}

	function btnMode($aLangs, $aCurData, $iUid, $aChildRecords, $aChildLanguages) {
		$aHtmlBag = array();
		reset($aLangs);
		$aKeys = array_keys($aLangs);
		while(list(, $iLangUid) = each($aKeys)) {

			if($iLangUid != $this->oForm->oDataHandler->i18n_getDefLangUid()) {

				if($this->oForm->__getEnvExecMode() !== "BE" || $GLOBALS["BE_USER"]->checkLanguageAccess($iLangUid)) {
					$aLang = $aLangs[$iLangUid];

					if(in_array($iLangUid, $aChildLanguages)) {
						$bExists = TRUE;
					} else {
						$bExists = FALSE;
					}

					if($bExists) {
						$sEvent = <<<EVENT

						\$aParams = \$this->getUserObjParams();

						return \$this->majixRequestEdition(
							\$aParams["childrecords"][\$aParams["sys_language_uid"]]["uid"],
							\$this->oDataHandler->tablename()
						);
EVENT;
					} else {
						$sEvent = <<<EVENT

						\$aParams = \$this->getUserObjParams();

						return \$this->majixRequestNewI18n(
							\$this->oDataHandler->tablename(),
							\$aParams["record"]["uid"],
							\$aParams["sys_language_uid"]
						);
EVENT;
					}

					$aConf = array(
						"type" => "BUTTON",
						"label" => $aLang["title"] . ($bExists ? "" : " [NEW]"),
						"onclick-default" => array(
							"runat" => "client",
							"userobj" => array(
								"php" => $sEvent,
							)
						)
					);


					if(($aCustomConf = $this->_navConf("/stdbutton")) !== FALSE) {
						if(isset($aCustomConf["onclick-default"]) || isset($aCustomConf["onclick"])) {
							unset($aConf["onclick-default"]);
						}

						$aConf = t3lib_div::array_merge_recursive_overrule(
							$aConf,
							$aCustomConf
						);
					}

					$sName = $this->_getName() . "-record-" . $iUid . "-lang-" . $iLangUid;
					$aConf["name"] = $sName;

					$iIndex = $this->oForm->pushForcedUserObjParam(
						array(
							"translation_exists" => $bExists,
							"sys_language_uid" => $iLangUid,
							"childrecords" => $aChildRecords,
							"childlanguages" => $aChildLanguages,
							"record" => $aCurData,
							"lang" => $aLang,
							"langs" => $aLangs,
						)
					);
					$this->oForm->pullForcedUserObjParam($iIndex);

					$oButton = $this->oForm->_makeRenderlet(
						$aConf,
						$this->sXPath . $sName. "/",
						FALSE,
						$this,
						FALSE,
						FALSE
					);
					$this->oForm->aORenderlets[$sName] =& $oButton;
					$aRendered = $oButton->render();

					$aHtmlBag[] = $oButton->wrap($aRendered["__compiled"]);
					unset($oButton);
					unset($aRendered);
				}
			}
		}

		return implode("", $aHtmlBag);
	}

	function menuMode($aLangs, $aCurData, $iUid, $aChildRecords, $aChildLanguages) {
		$aHtmlBag = array();
		reset($aLangs);
		$aKeys = array_keys($aLangs);

		$aOptionsData = array();
		$sJsevent = 'var aLink = new Array();';
		foreach($aLangs as $iLangUid => $aLang) {
			if($iLangUid != $this->oForm->oDataHandler->i18n_getDefLangUid()) {
				if($this->oForm->__getEnvExecMode() !== "BE" || $GLOBALS["BE_USER"]->checkLanguageAccess($iLangUid)) {
					$bExists = $this->recordAlreadyCreated($iLangUid, $aChildLanguages);
					$iIndex = $this->oForm->pushForcedUserObjParam(
						array(
							"translation_exists" => $bExists,
							"sys_language_uid" => $iLangUid,
							"childrecords" => $aChildRecords,
							"childlanguages" => $aChildLanguages,
							"record" => $aCurData,
							"lang" => $aLang,
							"langs" => $aLangs,
						)
					);
					$this->oForm->pullForcedUserObjParam($iIndex);

					$aOptionsData[] = array(
						'caption' => $aLang['title'] . ($bExists ? '' : ' [NEW]'),
						'value' => $iLangUid
					);

					$sJsevent.= 'aLink[' . $iLangUid . '] = "' . $this->getEditionLink($aChildRecords, $aCurData, $iLangUid, $aChildLanguages) . '";';
				}
			}
		}
		$sJsevent.= '
			oListbox = Formidable.getElementById("' . $this->oForm->formid . '.' . $this->_getName() . '-' . $iUid . '");
			this.sendToPage(aLink[$F(oListbox)])';
		
		$aConf = array(
			'type' => 'LISTBOX',
			'addblank' => TRUE,
			'name' => $this->_getName() . '-' . $iUid,
			'label' => '',
			'data' => array(
				'items' => $aOptionsData
			),
			'onchange' => array(
				'runat' => 'client',
				'userobj' => array(
					'js' => $sJsevent,
				)
			)
		);

		if(($aCustomConf = $this->_navConf("/stdbutton")) !== FALSE) {
			$aConf = t3lib_div::array_merge_recursive_overrule(
				$aConf,
				$aCustomConf
			);
		}

		$oMenu = $this->oForm->_makeRenderlet(
			$aConf,
			$this->sXPath . $this->_getName(). "/",
			FALSE,
			$this,
			FALSE,
			FALSE
		);
		$this->oForm->aORenderlets[$sName] =& $oMenu;
		$aHtmlBag = $oMenu->render();
		unset($oMenu);

		return $aHtmlBag;
	}

	function getEditionLink($aChildRecords, $aCurData, $iLangUid, $aChildLanguages) {
		if(($mFormid = $this->_navConf("/formid")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($mFormid)) {
				$mFormid = $this->oForm->callRunneable($mFormid);
			}
		} else {
			$mFormid = $this->oForm->formid;
		}
				
		if(($mPage = $this->_navConf("/pageid")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($mPage)) {
				$mPage = $this->oForm->callRunneable($mPage);
			}
		} else {
			$this->oForm->mayDay("renderlet:I18N <b>'" . $this->_getName() . "'</b>: on redirect mode, you must set a page id");
		}

		$aLink = array();
		$aLink['parameter'] = $mPage;
		$sTableName = $this->oForm->oDataHandler->tablename();
		$bExists = $this->recordAlreadyCreated($iLangUid, $aChildLanguages);
		if($bExists) {
			$aLink['additionalParams'] = '&' . $mFormid . '[action]=requestEdition' .
				'&' . $mFormid . '[tablename]=' . $this->oForm->oDataHandler->tablename() .
				'&' . $mFormid . '[recorduid]=' . $aChildRecords[$iLangUid]['uid'] .
				'&' . $mFormid . '[hash]=' . $this->oForm->_getSafeLock("requestEdition" . ":" . $sTableName . ":" . $aChildRecords[$iLangUid]['uid']);

		} else {
			$aLink['additionalParams'] = '&' . $mFormid . '[action]=requestNewI18n' .
				'&' . $mFormid . '[tablename]=' . $this->oForm->oDataHandler->tablename() .
				'&' . $mFormid . '[recorduid]=' . $aCurData['uid'] .
				'&' . $mFormid . '[languid]=' . $iLangUid .
				'&' . $mFormid . '[hash]=' . $this->oForm->_getSafeLock("requestNewI18n" . ":" . $sTableName . ":" . $aCurData['uid'] . ":" . $iLangUid);
		}

		return $this->oForm->cObj->typolink_URL($aLink);
	}

	function recordAlreadyCreated($iLangUid, $aChildLanguages) {
		if(in_array($iLangUid, $aChildLanguages)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function _getFlag($sPath, $bExists, $aLang) {

		if(($aFlags = $this->_navConf("/flags")) !== FALSE) {

			$aDefinition = FALSE;

			reset($aFlags);
			while(list(, $aFlag) = each($aFlags)) {
				if($aFlag["uid"] == $aLang["uid"]) {
					$aDefinition = $aFlag;
					break;
				}
			}

			if($aDefinition !== FALSE) {

				if($bExists === TRUE) {
					$aDefinition = $aDefinition["exists"];
				} else {
					$aDefinition = $aDefinition["dontexist"];
				}

				if(array_key_exists("path", $aDefinition)) {
					// on renvoie l'image

					if(tx_ameosformidable::isRunneable($aDefinition["path"])) {
						$aDefinition["path"] = $this->callRunneable($aDefinition["path"]);
					}

					return array(
						"type" => "image",
						"value" => $this->oForm->toWebPath($aDefinition["path"])
					);
				} elseif(array_key_exists("label", $aDefinition)) {
					// on renvoie le label

					if(tx_ameosformidable::isRunneable($aDefinition["label"])) {
						$aDefinition["label"] = $this->callRunneable($aDefinition["label"]);
					}

					return array(
						"type" => "text",
						"value" => $this->oForm->_getLLLabel($aDefinition["label"])
					);
				} else {
					/* on renvoie le flag par defaut */
				}
			} else {
				/* on renvoie le flag par defaut */
			}
		}

		if($bExists === TRUE) {

			$sTypoScript =<<<TYPOSCRIPT

	file = GIFBUILDER
	file {
		XY = [10.w], [10.h]

		10 = IMAGE
		10.file = {$sPath}
	}

TYPOSCRIPT;

		} else {

			$sTypoScript =<<<TYPOSCRIPT

	file = GIFBUILDER
	file {
		XY = [10.w], [10.h]

		10 = IMAGE
		10.file = {$sPath}

		15 = EFFECT
		15.value = gamma=4
	}

TYPOSCRIPT;

		}

		$this->callRunneable(
			array(
				"userobj" => array(
					"ts" => $sTypoScript
				)
			)
		);

		return array(
			"type" => "image",
			"value" => $this->oForm->toWebPath(
				$this->oForm->cObj->IMG_RESOURCE(
					$this->oForm->aLastTs
				)
			)
		);
	}

	function _listable() {
		return $this->oForm->defaultTrue("/listable/", $this->aElement) && $this->oForm->oDataHandler->i18n();
	}

	function _activeListable() {		// listable as an active HTML FORM field or not in the lister
		return $this->oForm->defaultTrue("/activelistable/", $this->aElement);
	}

	function _renderonly() {
		return TRUE;
	}

	function cleanBeforeSession() {
		$this->aOButtons = array();
		$this->baseCleanBeforeSession();
	}

}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_i18n/api/class.tx_rdti18n.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_i18n/api/class.tx_rdti18n.php"]);
	}
?>
