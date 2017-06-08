<?php
/** 
 * Plugin 'rdt_checkbox' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtcheckbox extends formidable_mainrenderlet {
	
	var $sMajixClass = "CheckBox";
	var $aLibs = array(
		"rdt_checkbox_class" => "res/js/checkbox.js",
	);

	var $bCustomIncludeScript = TRUE;

	function _render() {

		$sParentId = $this->_getElementHtmlId();
		$aHtml = array();
		$aHtmlBag = array();
		
		$sPostFlag = $this->getPostFlag();

		$aItems = $this->_getItems();
		$aChecked = $this->getValue();

		$aSubRdts = array();

		reset($aItems);
		while(list($index, $aItem) = each($aItems)) {
			$value = $aItem["value"];
			$caption = $this->oForm->_getLLLabel($aItem["caption"]);

			// on cree le nom du controle
			$name = $this->_getElementHtmlName() . "[" . $index . "]";
			$sId = $this->_getElementHtmlId() . "_" . $index;
			$aSubRdts[] = $sId;
			$this->sCustomElementId = $sId;
			$this->includeScripts(
				array(
					"bParentObj" => FALSE,
					"parentid" => $sParentId,
				)
			);

			$checked = "";

			if(is_array($aChecked)) {
				if(in_array($value, $aChecked)) {
					$checked = " checked=\"checked\" ";
				}
			}

			$sInput = "<input type=\"checkbox\" name=\"" . $name . "\" id=\"" . $sId . "\" value=\"" . $this->getValueForHtml($value) . "\" " . $checked . $this->_getAddInputParams($sId) . " ";

			if(array_key_exists("custom", $aItem)) {
				$sInput .= $aItem["custom"];
			}

			$sInput .= "/>";

			$sLabelStart = "<label for=\"" . $sId . "\" ";
			if(array_key_exists("labelcustom", $aItem)) {
				$sLabelStart .= $aItem["labelcustom"];
			}

			$sLabelStart .= ">";
			$sLabelEnd = "</label>";

			$aHtmlBag[$value . "."] = array(
				"input" => $sInput,
				"caption" => $caption,
				"value." => array(
					"htmlspecialchars" => htmlspecialchars($value),
				),
				"label" => $sLabelStart . $caption . $sLabelEnd,
				"label." => array(
					"tag" => $sLabelStart . $caption . $sLabelEnd,
					"tag." => array(
						"wrap" => $sLabelStart . "|" . $sLabelEnd,
					),
					"for." => array(
						"start" => $sLabelStart,
						"end" => $sLabelEnd,
					)
				)
			);

			$htmlCode = $sInput . $sLabelStart . $caption . $sLabelEnd;
			if (array_key_exists('wrapitem', $aItem)) {
				$htmlCode = str_replace('|', $htmlCode, $aItem['wrapitem']);
			}

			$aHtml[] = (($checked !== "") ? $this->_wrapSelected($htmlCode) : $this->_wrapItem($htmlCode));

			$this->sCustomElementId = FALSE;
		}

		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts(
			array(
				"checkboxes" => $aSubRdts,
				"bParentObj" => TRUE,
			)
		);
		
		$sInput = $this->_implodeElements($aHtml) . $sPostFlag;

		$aHtmlBag["__compiled"] = $this->_displayLabel(
			$this->getLabel()
		) . $sInput;
		
		$aHtmlBag["input"] = $sInput;
		$aHtmlBag["postflag"] = $sPostFlag;

		return $aHtmlBag;
	}

	function _flatten($mData) {

		if(is_array($mData)) {
			if(!$this->_emptyFormValue($mData)) {
				return implode(",", $mData);
			}

			return "";
		}

		return $mData;
	}

	function _unFlatten($sData) {
		
		if(!$this->_emptyFormValue($sData)) {
			return \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $sData);
		}

		return array();
	}
	
	function _getHumanReadableValue($data) {

		if(!is_array($data)) {
			$data = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $data);
		}

		$aLabels = array();
		$aItems = $this->_getItems();
		
		reset($data);
		while(list(, $selectedItemValue) = each($data)) {

			reset($aItems);
			while(list(, $aItem) = each($aItems)) {

				if($aItem["value"] == $selectedItemValue) {

					$aLabels[] = $this->oForm->_getLLLabel($aItem["caption"]);
					break;
				}
			}
		}
		
		return implode(", ", $aLabels);
	}
	
	function _sqlSearchClause($sValues, $sFieldPrefix = "", $sFieldName = '', $bRec = true) {

		$aParts = array();
		$aValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $sValues);
		$sSql = '';
		if(sizeof($aValues) > 0) {

			reset($aValues);
			
			$sFieldName = $this->_navConf("/name");
			$sTableName = $this->oForm->_navConf("/tablename", $this->oForm->oDataHandler->aElement);
			$aConf = $this->_navConf("/search");
			
			if(!is_array($aConf)) {
				$aConf = array();
			}
						
			while(list(, $sValue) = each($aValues)) {
				
				if(array_key_exists("onfields", $aConf)) {

					if(tx_ameosformidable::isRunneable($aConf["onfields"])) {
						$sOnFields = $this->callRunneable($aConf["onfields"]);
					} else {
						$sOnFields = $aConf["onfields"];
					}

					$aFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $sOnFields);
					reset($aFields);
				} else {
					$aFields = array($this->_getName());
				}

				if(array_key_exists("overridesql", $aConf)) {

					if(tx_ameosformidable::isRunneable($aConf["overridesql"])) {
						$aSql = array();
						reset($aFields);
						while(list(, $sField) = each($aFields)) {

							$aParts[] = $this->callRunneable(
								$aConf["overridesql"],
								array(
									"name"		=> $sField,
									"table"		=> $sTable,
									"value"		=> $sValue,
									"prefix"	=> $sFieldPrefix,
									"defaultclause" => "FIND_IN_SET('" . $this->oForm->db_quoteStr($sValue) . "', " . $sFieldPrefix . $sField . ")",
								)
							);
						}

					} else {
						$aParts[] = $aConf["overridesql"];
					}

				} else {
					reset($aFields);
					while(list(, $sField) = each($aFields)) {
						$aParts[] = "FIND_IN_SET('" . $this->oForm->db_quoteStr($sValue) . "', " . $sFieldPrefix . $sField . ")";
					}
				}
			}


			if(!empty($aParts)) {
				$sSql = " (" . implode(" OR ", $aParts) . ") ";
			}			

			return $sSql;
		}

		return "";
	}

	function majixCheckAll() {
		return $this->buildMajixExecuter(
			"checkAll"
		);
	}
	
	function majixUncheckAll() {
		return $this->buildMajixExecuter(
				"uncheckAll"
		);
	}

	function majixCheckNone() {
		return $this->buildMajixExecuter(
			"checkNone"
		);
	}

	function majixCheckItem($sValue) {
		return $this->buildMajixExecuter(
			"checkItem",
			$sValue
		);
	}

	function majixUnCheckItem($sValue) {
		return $this->buildMajixExecuter(
			"unCheckItem",
			$sValue
		);
	}


	function _getSeparator() {

		if(($mSep = $this->_navConf("/separator")) === FALSE) {
			$mSep = "<br />\n";
		} else {
			if(tx_ameosformidable::isRunneable($mSep)) {
				$mSep = $this->callRunneable($mSep);
			}
		}

		return $mSep;
	}
	
	function _implodeElements($aHtml) {

		return implode(
			$this->_getSeparator(),
			$aHtml
		);
	}

	function _wrapSelected($sHtml) {

		if(($mWrap = $this->_navConf("/wrapselected")) !== FALSE) {
			
			if(tx_ameosformidable::isRunneable($mWrap)) {
				$mWrap = $this->callRunneable($mWrap);
			}

			$sHtml = str_replace("|", $sHtml, $mWrap);

		} else {
			$sHtml = $this->_wrapItem($sHtml);
		}

		return $sHtml;
	}

	function _wrapItem($sHtml) {
		
		if(($mWrap = $this->_navConf("/wrapitem")) !== FALSE) {
			
			if(tx_ameosformidable::isRunneable($mWrap)) {
				$mWrap = $this->callRunneable($mWrap);
			}

			$sHtml = str_replace("|", $sHtml, $mWrap);
		}

		return $sHtml;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_checkbox/api/class.tx_rdtcheckbox.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_checkbox/api/class.tx_rdtcheckbox.php"]);
	}

?>
