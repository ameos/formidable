<?php
/**
 * Plugin 'tx_rdtselector' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtselector extends formidable_mainrenderlet {

	var $sMajixClass = "Selector";
	var $aLibs = array(
		"rdt_selector_class" => "res/js/selector.js"
	);

	var $bCustomIncludeScript = TRUE;

	var $oAvailable = FALSE;
	var $oSelected = FALSE;
	var $oButtonRemove = FALSE;
	var $oButtonMoveTop = FALSE;
	var $oButtonMoveUp = FALSE;
	var $oButtonMoveDown = FALSE;
	var $oButtonMoveBottom = FALSE;
	var $oCustomRenderlet = FALSE;

	function _render() {

		$this->initAvailable();
		$this->initSelected();
		$this->initButtonRemove();
		$this->initButtonMoveTop();
		$this->initButtonMoveUp();
		$this->initButtonMoveDown();
		$this->initButtonMoveBottom();
		$this->initCustomRenderlet();

		$aItems = $this->oForm->_rdtItemsToArray(
			$this->oAvailable->_getItems()
		);
		
		$aSelected = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $this->getValue());
		$aSelectedItems = array();
		
		reset($aSelected);
		while(list($sKey, $sValue) = each($aSelected)) {
			if(array_key_exists($sValue, $aItems)) {
				$aSelectedItems[$sValue] = $aItems[$sValue];
				unset($aItems[$sValue]);
			}
		}

		$this->oAvailable->forceItems(
			$this->oForm->_arrayToRdtItems(
				$aItems
			)
		);

		$this->oSelected->forceItems(
			$this->oForm->_arrayToRdtItems(
				$aSelectedItems
			)
		);

		$aAvailableHtml = $this->oForm->_renderElement($this->oAvailable);
		$aSelectedHtml = $this->oForm->_renderElement($this->oSelected);
		$aButtonRemove = $this->oForm->_renderElement($this->oButtonRemove);
		$aButtonMoveTop = $this->oForm->_renderElement($this->oButtonMoveTop);
		$aButtonMoveUp = $this->oForm->_renderElement($this->oButtonMoveUp);
		$aButtonMoveDown = $this->oForm->_renderElement($this->oButtonMoveDown);
		$aButtonMoveBottom = $this->oForm->_renderElement($this->oButtonMoveBottom);

		if($this->oCustomRenderlet !== FALSE) {
			$aCustom = $this->oCustomRenderlet->render();
			$sCustomId = $this->oCustomRenderlet->_getElementHtmlId();
		} else {
			$aCustom = array(
				"__compiled" => "",
			);
			$sCustomId = FALSE;
		}

		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts(
			array(
				"availableId" => $this->oAvailable->_getElementHtmlId(),
				"selectedId" => $this->oSelected->_getElementHtmlId(),
				"buttonRemoveId" => $this->oButtonRemove->_getElementHtmlId(),
				"buttonMoveTopId" => $this->oButtonMoveTop->_getElementHtmlId(),
				"buttonMoveUpId" => $this->oButtonMoveUp->_getElementHtmlId(),
				"buttonMoveDownId" => $this->oButtonMoveDown->_getElementHtmlId(),
				"buttonMoveBottomId" => $this->oButtonMoveBottom->_getElementHtmlId(),
				"customRenderletId" => $sCustomId,
			)
		);

		$sHidden = "<input type=\"hidden\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . htmlspecialchars($this->getValue()) . "\" />";
		
		$sLabelTag = $this->_displayLabel($this->getLabel());

		$sCompiled = <<<HTML
		
			{$sLabelTag}
			<table style='width: 100%'>
				<tr>
					<td valign="top" style='width: 47%;'>{$aSelectedHtml["__compiled"]}</td>
					<td valign="top" align="center">
						<div>{$aButtonMoveTop["__compiled"]}</div>
						<div>{$aButtonMoveUp["__compiled"]}</div>
						<div>{$aButtonMoveDown["__compiled"]}</div>
						<div>{$aButtonMoveBottom["__compiled"]}</div>
						<div>{$aButtonRemove["__compiled"]}</div>
						<div>{$aCustom["__compiled"]}</div>
					</td>
					<td valign="top" style='width: 47%;'>{$aAvailableHtml["__compiled"]}</td>
				</tr>
			</table>
			{$sHidden}

HTML;

		$aAvailableHtml["__compiled"] .= $sHidden;
		$aAvailableHtml["input"] .= $sHidden;

		return array(
			"__compiled" => $sCompiled,
			"available" => $aAvailableHtml,
			"selected" => $aSelectedHtml,
			"buttonUp" => $aButtonMoveUp,
			"buttonDown" => $aButtonMoveDown,
			"buttonTop" => $aButtonMoveTop,
			"buttonBottom" => $aButtonMoveBottom,
			"buttonRemove" => $aButtonRemove,
			"customRenderlet" => $aCustom,
		);
	}

	function initAvailable() {
		if($this->oAvailable === FALSE) {

			$sSelectorName = $this->getAbsName();
			$sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sSelectorName}"]->oAvailable->majixTransferSelectedTo(
						\$this->aORenderlets["{$sSelectorName}"]->oSelected->getAbsName()
					),
					\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
				);
PHP;

			$aConf = array(
				"onmouseup-999" => array(
					"userobj" => array(
						"php" => $sEvent
					)
				),
				"style" => "width: 100%;"	// 100% of TD
			);

			if(($aCustomConf = $this->_navConf("/available")) !== FALSE) {
				if(!is_array($aCustomConf)) { $aCustomConf = array();}

				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}
			//debug($aConf);
			$aConf["type"] = "LISTBOX";
			$aConf["name"] = $this->_getName() . "_available";
			$aConf["multiple"] = true;
			$aConf["renderonly"] = true;

			$this->oAvailable = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "available/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oAvailable->getAbsName()] =& $this->oAvailable;
		}
	}

	function initSelected() {
		if($this->oSelected === FALSE) {
			
			$sSelectorName = $this->getAbsName();
			$sEvent = <<<PHP
			
				return array(
					\$this->aORenderlets["{$sSelectorName}"]->oSelected->majixTransferSelectedTo(
						\$this->aORenderlets["{$sSelectorName}"]->oAvailable->getAbsName()
					),
					\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
				);
PHP;
			
			$aConf = array(
				"onmouseup-999" => array(
					"userobj" => array(
						"php" => $sEvent
					)
				),
				"style" => "width: 100%;"	//	100% of TD
			);
			if(($aCustomConf = $this->_navConf("/selected")) !== FALSE) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["type"] = "LISTBOX";
			$aConf["name"] = $this->_getName() . "_selected";
			$aConf["multiple"] = true;
			$aConf["renderonly"] = true;

			$this->oSelected = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "selected/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oSelected->getAbsName()] =& $this->oSelected;
		}
	}

	function initCustomRenderlet() {

		if($this->oCustomRenderlet === FALSE) {

			if(($aConf = $this->_navConf("/customrenderlet")) !== FALSE) {

				$aConf["name"] = $this->_getName() . "_customrenderlet";
				$this->oCustomRenderlet = $this->oForm->_makeRenderlet(
					$aConf,
					$this->sXPath . "customrenderlet/",
					FALSE,
					$this,
					FALSE,
					FALSE
				);

				$this->oForm->aORenderlets[$this->oCustomRenderlet->getAbsName()] =& $this->oCustomRenderlet;
			}
		}
	}

	function initButtonRemove() {
		if($this->oButtonRemove === FALSE) {
			$sSelectorName = $this->getAbsName();
			$sSourceName = $this->oSelected->getAbsName();
			$sTargetName = $this->oAvailable->getAbsName();
			$sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sSourceName}"]->majixTransferSelectedTo("{$sTargetName}"),
					\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
				);

PHP;

			$aConf = array(
				"type" => "IMAGE",
				"path" => $this->sExtPath . "res/img/remove.gif",
				"onclick-999" => array(			// 999 to avoid overruling by potential customly defined event
					"runat" => "client",
					"userobj" => array(
						"php" => $sEvent,
					),
				),
			);

			if(($aCustomConf = $this->_navConf("/buttonremove")) !== FALSE) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sSelectorName . "_btnremove";

			$this->oButtonRemove = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonremove/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oButtonRemove->getAbsName()] =& $this->oButtonRemove;
		}
	}

	function initButtonMoveTop() {
		if($this->oButtonMoveTop === FALSE) {
			$sSelectorName = $this->getAbsName();
			$sEvent = <<<PHP

			return array(
				\$this->aORenderlets["{$sSelectorName}"]->oSelected->majixMoveSelectedTop(),
				\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
			);

PHP;
			$aConf = array(
				"type" => "IMAGE",
				"path" => $this->sExtPath . "res/img/top.gif",
				"onclick-999" => array(
					"runat" => "client",
					"userobj" => array(
						"php" => $sEvent,
					),
				),
			);

			if(($aCustomConf = $this->_navConf("/buttontop")) !== FALSE) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sSelectorName . "_btntop";

			$this->oButtonMoveTop = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonmovetop/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oButtonMoveTop->getAbsName()] =& $this->oButtonMoveTop;
		}
	}

	function initButtonMoveUp() {
		if($this->oButtonMoveUp === FALSE) {
			$sSelectorName = $this->getAbsName();
			$sEvent = <<<PHP

			return array(
				\$this->aORenderlets["{$sSelectorName}"]->oSelected->majixMoveSelectedUp(),
				\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
			);

PHP;
			$aConf = array(
				"type" => "IMAGE",
				"path" => $this->sExtPath . "res/img/up.gif",
				"onclick-999" => array(
					"runat" => "client",
					"userobj" => array(
						"php" => $sEvent,
					),
				),
			);

			if(($aCustomConf = $this->_navConf("/buttonup")) !== FALSE) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sSelectorName . "_btnup";
			$this->oButtonMoveUp = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonmoveup/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);
			$this->oForm->aORenderlets[$this->oButtonMoveUp->getAbsName()] =& $this->oButtonMoveUp;
		}
	}

	function initButtonMoveDown() {
		if($this->oButtonMoveDown === FALSE) {
			$sSelectorName = $this->getAbsName();
			$sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sSelectorName}"]->oSelected->majixMoveSelectedDown(),
					\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
				);

PHP;
			$aConf = array(
				"type" => "IMAGE",
				"path" => $this->sExtPath . "res/img/down.gif",
				"onclick-999" => array(
					"runat" => "client",
					"userobj" => array(
						"php" => $sEvent,
					),
				),
			);

			if(($aCustomConf = $this->_navConf("/buttondown")) !== FALSE) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sSelectorName . "_btndown";

			$this->oButtonMoveDown = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonmovedown/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);
			$this->oForm->aORenderlets[$this->oButtonMoveDown->getAbsName()] =& $this->oButtonMoveDown;
		}
	}

	function initButtonMoveBottom() {
		if($this->oButtonMoveBottom === FALSE) {
			$sSelectorName = $this->getAbsName();
			$sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sSelectorName}"]->oSelected->majixMoveSelectedBottom(),
					\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
				);

PHP;
			$aConf = array(
				"type" => "IMAGE",
				"path" => $this->sExtPath . "res/img/bottom.gif",
				"onclick-999" => array(
					"runat" => "client",
					"userobj" => array(
						"php" => $sEvent,
					),
				),
			);

			if(($aCustomConf = $this->_navConf("/buttonbottom")) !== FALSE) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sSelectorName . "_btnbottom";

			$this->oButtonMoveBottom = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonmovebottom/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oButtonMoveBottom->getAbsName()] =& $this->oButtonMoveBottom;
		}
	}

	function majixUpdateHidden() {
		return $this->buildMajixExecuter(
			"updateHidden"
		);
	}

	function majixUnSelectAll() {
		return $this->buildMajixExecuter(
			"unSelectAll"
		);
	}

	function cleanBeforeSession() {
		unset($this->oAvailable);
		unset($this->oSelected);
		unset($this->oButtonAdd);
		unset($this->oButtonRemove);
		unset($this->oButtonMoveTop);
		unset($this->oButtonMoveUp);
		unset($this->oButtonMoveDown);
		unset($this->oButtonMoveBottom);
		$this->baseCleanBeforeSession();
	}
/*
	function _sqlSearchClause($sValue, $sFieldPrefix = "", $sFieldName = "", $bRec = TRUE) {

		$aValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $sValue);
		$aParts = array();

		if($sFieldName === "") {
			$sFieldName = $this->_getName();
		}
		
		if(sizeof($aValues) > 0) {

			$sTableName = $this->oForm->_navConf("/tablename", $this->oForm->oDataHandler->aElement);

			reset($aValues);
			while(list(, $uid) = each($aValues)) {
				//$aParts[] = "(FIND_IN_SET('" . addslashes($sValue) . "', " . $sFieldPrefix . $sFieldName . "))";
				$aParts[] = $GLOBALS["TYPO3_DB"]->listQuery($sFieldPrefix . $sFieldName, $uid, $sTableName);
			}

			if((($mMode = $this->_navConf("/search/mode")) !== FALSE) && is_string($mMode)) {
				$sSql = " ( " . implode(" " . strtoupper($mMode) . " ", $aParts) . " ) ";
			} else {
				$sSql = " ( " . implode(" OR ", $aParts) . " ) ";
			}

			if($bRec === TRUE) {
				return $this->overrideSql(
					$sValue,
					$sFieldPrefix,
					$sFieldName,
					$sSql
				);
			} else {
				return $sSql;
			}
		}

		return "";
	}
*/
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_selector/api/class.tx_rdtselector.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_selector/api/class.tx_rdtselector.php']);
}
?>
