<?php
/**
 * Plugin 'rdt_listbox' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtlistbox extends formidable_mainrenderlet {

	var $mSavedDefaultValue = FALSE;

	var $sMajixClass = "ListBox";
	var $aLibs = array(
		"rdt_listbox_class" => "res/js/listbox.js"
	);

	function _render() {
		$aItems = $this->_getItems();

		$sLabel = $this->getLabel();
		$sValue = $this->getValue($aItems);
		if($this->_isMultiple()) {
			if(!is_array($sValue)) {
				$sValue = t3lib_div::trimExplode(",", $sValue);
			}
		}


		$sPostFlag = $this->getPostFlag();

		$sOptionsList = "";

		
		$sAddStyle = "";

		if($this->defaultFalse("/hideifempty") === TRUE) {
			if($this->isDataEmpty()) {
				$sAddStyle = "display: none;";
			}
		}

		$aSelectedCaptions = array();
		$bSelected = FALSE;

		if(count($aItems) > 0) {

			$aHtml = array();

			reset($aItems);
			while(list($sType, $aItem) = each($aItems)) {

				if(strpos($sType, 'optgroup') !== FALSE || array_key_exists('items', $aItem)) {
					$aItem['label'] = $this->oForm->_getLLLabel((string)$aItem['label']);
					$aHtml[] = '<optgroup label="' . $aItem['label'] . '" class="' . $aItem['class'] . '">';					
					foreach($aItem['items'] as $sKey => $aSubItem) {
						$sSelected = "";
						$value = $aSubItem["value"];
						$sCaption = $this->oForm->_getLLLabel(
							"" . $aSubItem["caption"]	// cast if integer
						);

						if($this->_isMultiple()) {
							if(is_array($sValue)) {
								if(in_array($value, $sValue)) {
									$sSelected = " selected=\"selected\" ";
									$aSelectedCaptions[] = $sCaption;
								}
							}
						} else {
							if($bSelected === FALSE && $aSubItem["value"] == $sValue) {
								$bSelected = TRUE;
								$sSelected = " selected=\"selected\" ";
								$aSelectedCaptions[] = $sCaption;
							}
						}
						
						$sDisabled = $aSubItem['disabled'] == 'true' ? ' disabled="disabled" ' : '';
						$sCustom = $this->_getCustom($aSubItem);
						$sClass = $this->_getClasses($aSubItem, FALSE);
						$sStyle = $this->_getStyle($aSubItem);

						$aHtml[] = "<option value=\"" . $aSubItem["value"] . "\" " . $sSelected . $sClass . $sCustom . $sDisabled . ">" . $sCaption . "</option>";						
					}
					$aHtml[] = '</optgroup>';
					
				} else {
				
					$sSelected = "";
					$value = $aItem["value"];
					$sCaption = $this->oForm->_getLLLabel(
						"" . $aItem["caption"]	// cast if integer
					);

					if($this->_isMultiple()) {
						if(is_array($sValue)) {
							if(in_array($value, $sValue)) {
								$sSelected = " selected=\"selected\" ";
								$aSelectedCaptions[] = $sCaption;
							}
						}
					} else {
						if($bSelected === FALSE && $aItem["value"] == $sValue) {
							$bSelected = TRUE;
							$sSelected = " selected=\"selected\" ";
							$aSelectedCaptions[] = $sCaption;
						}
					}
					
					$sDisabled = $aItem['disabled'] == 'true' ? ' disabled="disabled" ' : '';
					$sCustom = $this->_getCustom($aItem);
					$sClass = $this->_getClasses($aItem, FALSE);
					$sStyle = $this->_getStyle($aItem);

					#$aHtml[] = "<option value=\"" . $aItem["value"] . "\" " . $sSelected . $sClass . $sStyle . $sCustom . ">" . $sCaption . "</option>";
					$aHtml[] = "<option value=\"" . $aItem["value"] . "\" " . $sSelected . $sClass . $sCustom . $sDisabled . ">" . $sCaption . "</option>";
				}
			}

			reset($aHtml);
			$sOptionsList = implode("", $aHtml);
		}

		if($this->_isMultiple()) {
			$sBrackets = "[]";
			$sMultiple = " multiple=\"multiple\" ";
			if(($mSize = $this->_navConf("/size")) != '') {
				$sMultiple.= ' size="' . $mSize . '" ';
			}
		} else {
			$sBrackets = "";
			$sMultiple = "";
		}

		$sInput = "<select name=\"" . $this->_getElementHtmlName() . $sBrackets . "\" " . $sMultiple . " id=\"" . $this->_getElementHtmlId() . "\"" . $this->_getAddInputParams(array("style" => $sAddStyle)) . ">" . $sOptionsList . "</select>" . $sPostFlag;

		$aHtmlBag = array(
			"__compiled" => $this->_displayLabel($sLabel) . $sInput,
			"value" => $sValue,
			"caption" => implode(", ", $aSelectedCaptions),
			"input" => $sInput,
			"postflag" => $sPostFlag,
		);

		return $aHtmlBag;
	}

	function _getHumanReadableValue($data = FALSE) {

		if($data === FALSE) {
			$data = $this->getValue();
		}

		if($this->_isMultiple() && !is_array($data)) {
			$data = t3lib_div::trimExplode(",", $data);
		}

		if(is_array($data)) {

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

		} else {

			$aItems = $this->_getItems();

			reset($aItems);
			while(list(, $aItem) = each($aItems)) {

				if(isset($aItem["items"]) && is_array($aItem["items"])) {
					foreach($aItem["items"] as $aSubItems) {
						if($aSubItems["value"] == $data) {
							return $this->oForm->_getLLLabel($aSubItems["caption"]);
						}
					}
				} else {
					if($aItem["value"] == $data) {
						return $this->oForm->_getLLLabel($aItem["caption"]);
					}
				}
			}

			return $data;
		}

		return "";
	}

	function _sqlSearchClause($sValue, $sFieldPrefix = "", $sFieldName = "", $bRec = TRUE) {

		$aValues = t3lib_div::trimExplode(",", $sValue);
		$aParts = array();

		if($sFieldName === "") {
			$sFieldName = $this->_getName();
		}

		if(sizeof($aValues) > 0) {

			$sTableName = $this->oForm->_navConf("/tablename", $this->oForm->oDataHandler->aElement);

			reset($aValues);
			while(list(, $uid) = each($aValues)) {
				$aParts[] = $this->oForm->db_listQuery($sFieldPrefix . $sFieldName, $uid);
			}

			$sSql = " ( " . implode(" OR ", $aParts) . " ) ";

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

	function __getDefaultValue() {

		if(
			$this->defaultFalse("/data/defaultvalue/first/")
			||
			($this->_navConf("/data/defaultvalue/first/") === "")	// slick tag <first />
		) {
			// on renvoie la valeur du premier item
			if(($sFirstValue = $this->getFirstItemValue()) !== FALSE) {
				return $sFirstValue;
			}

			return "";
		}

		return parent::__getDefaultValue();
	}

	function getFirstItemValue() {
		$aItems = $this->_getItems();

		if(!empty($aItems)) {

			$aFirst = array_shift($aItems);
			return $this->_substituteConstants($aFirst["value"]);
		}

		return FALSE;
	}

	function majixReplaceData($aData) {

		$iKey = array_shift(array_keys($aData));

		if(is_array($aData[$iKey])) {

			// it's an array like array(
			//	0 => array("caption" => "", "value" => "")
			//	1 => array("caption" => "", "value" => "")
			//	)
			$aOldData = $aData;
			$aData = array();

			reset($aOldData);
			while(list(, $aItem) = each($aOldData)) {
				$aData[$aItem["value"]] = $this->oForm->_getLLLabel($aItem["caption"]);
			}
		}

		return $this->buildMajixExecuter(
			"replaceData",
			$aData
		);
	}

	function majixSetSelected($sData) {
		return $this->buildMajixExecuter(
			"setSelected",
			$sData
		);
	}

	function majixSetAllSelected() {
		return $this->buildMajixExecuter(
			"setAllSelected"
		);
	}

	function majixTransferSelectedTo($sRdtId, $bRemoveFromSource = TRUE) {
		return $this->buildMajixExecuter(
			"transferSelectedTo",
			array(
				"list" => $sRdtId,
				"removeFromSource" => $bRemoveFromSource,
			)
		);
	}

	function majixMoveSelectedTop() {
		return $this->buildMajixExecuter(
			"moveSelectedTop"
		);
	}

	function majixMoveSelectedUp() {
		return $this->buildMajixExecuter(
			"moveSelectedUp"
		);
	}

	function majixMoveSelectedDown() {
		return $this->buildMajixExecuter(
			"moveSelectedDown"
		);
	}

	function majixMoveSelectedBottom() {
		return $this->buildMajixExecuter(
			"moveSelectedBottom"
		);
	}

	function majixAddItem($sCaption, $sValue) {
		return $this->buildMajixExecuter(
			"addItem",
			array(
				"caption" => $sCaption,
				"value" => $sValue
			)
		);
	}

	function majixModifyItem($sCaption, $sValue) {
		return $this->buildMajixExecuter(
			"modifyItem",
			array(
				"caption" => $sCaption,
				"value" => $sValue
			)
		);
	}

	function _isMultiple() {
		return ($this->oForm->defaultFalse("/multiple/", $this->aElement));
	}

	function _flatten($mData) {
		if($this->_isMultiple()) {
			if(is_array($mData) && !$this->_emptyFormValue($mData)) {
				return implode(",", $mData);
			}

			return "";
		} else {
			return $mData;
		}
	}

	function _unFlatten($sData) {
		if($this->_isMultiple()) {
			if(!$this->_emptyFormValue($sData)) {
				return t3lib_div::trimExplode(",", $sData);
			} else {
				return array();
			}
		} else {
			return $sData;
		}
	}

	function getValue($aItems = FALSE) {
		$sSetValue = parent::getValue();
		if(is_array($sSetValue)) {
			$sSetValue = array_diff($sSetValue, array(''));
		}
		
		if(!is_array($sSetValue) && trim($sSetValue) !== "") {
			return $sSetValue;
		} elseif(is_array($sSetValue) && !empty($sSetValue) && !(sizeof($sSetValue) == 1 && trim($sSetValue[0]) == '')) {
			return $sSetValue;
		}

		if($aItems == FALSE) {
			$aItems = $this->_getItems();
		}
		if(is_array($aItems) && count($aItems) > 0) {
			# only one item
			$aFirst = array_shift($aItems);
			return $this->_substituteConstants($aFirst["value"]);
		}

		return "";
	}

	function isDataEmpty() {
		$aItems = $this->_getItems();
		return (count($aItems) === 0 || (count($aItems) === 1 && trim($aItems[array_shift(array_keys($aItems))]["value"]) === ""));
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_listbox/api/class.tx_rdtlistbox.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_listbox/api/class.tx_rdtlistbox.php"]);
	}

?>
