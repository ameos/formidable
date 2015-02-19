<?php
/** 
 * Plugin 'rdt_autocomplete' for the 'ameos_formidable' extension.
 *
 * @author	Loredana Zeca <typo3dev@ameos.com>
 */


class tx_rdtautocomplete extends formidable_mainrenderlet {

	var $aLibs = array(
		"rdt_autocomplete_class" => "res/js/autocomplete.js",
	);

	var $sMajixClass = "Autocomplete";

	var $bCustomIncludeScript = TRUE;

	var $sTemplate = FALSE;
	var $aRowsSubpart = FALSE;

	var $oDataStream = FALSE;
	var $aDatasource = FALSE;

	var $aConfig = FALSE;
	var $aLimitAndSort = FALSE;
	var $aFilters = FALSE;

	
	function _render() {

		$this->oForm->bStoreFormInSession = TRUE;	// instanciate the Typo3 and formidable context 

		$this->_checkRequiredProperties();		// check if all the required fields are specified into XML

		$this->sTemplate = $this->_getTemplate();
		$this->aRowsSubpart = $this->_getRowsSubpart($this->sTemplate);


		if(($sTimeObserver = $this->_navConf("/timeobserver")) === FALSE) {
			$sTimeObserver = "0.75";
		}

		if(($sSearchType = $this->_navConf("/searchtype")) === FALSE) {
			$sSearchType = "inside";
		}

		if(($sSearchOnFields = $this->_navConf("/searchonfields")) === FALSE) {
			$this->oForm->mayday("RENDERLET AUTOCOMPLETE <b>" . $this->_getName() . "</b> requires the /searchonfields to be set. Please check your XML configuration!");
		}

		if(($sItemClass = $this->_navConf("/itemclass")) === FALSE) {
			$sItemClass = "ameosformidable-rdtautocomplete-item";
		}

		if(($sSelectedItemClass = $this->_navConf("/selecteditemclass")) === FALSE) {
			$sSelectedItemClass = "selected";
		}


		$sHtmlId = $this->_getElementHtmlId();
		$sObject = "rdt_autocomplete";
		$sServiceKey = "lister";
		$sFormId = $this->oForm->formid;
		$sSafeLock = $this->_getSessionDataHashKey();
		$sThrower = $sHtmlId;

		$sSearchUrl = $this->oForm->_removeEndingSlash(t3lib_div::getIndpEnv("TYPO3_SITE_URL")) . "/index.php?eID=tx_ameosformidable&object=" . $sObject . "&servicekey=" . $sServiceKey . "&formid=" . $sFormId . "&safelock=" . $sSafeLock . "&thrower=" . $sThrower;

		$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"][$sObject][$sServiceKey][$sSafeLock] = array(
			"requester" => array(
				"name" => $this->_getName(),
				"xpath" => $this->sXPath,
			),
		);


		$sLabel = $this->oForm->_getLLLabel($this->_navConf("/label"));
		$sValue = $this->getValue();
		$sValueForHtml = $this->getValueForHtml($sValue);

		$sInput = '<input type="text" name="' .$this->_getElementHtmlName(). '" id="' .$this->_getElementHtmlId(). '" value="' . $sValueForHtml . '" ' .$this->_getAddInputParams(). ' />';

		$sChilds = '<div name="' .$this->_getElementHtmlName(). '[list]" id="' .$this->_getElementHtmlId(). '.list"></div>';

		$aHtmlBag = array(
			"__compiled" => $this->_displayLabel($sLabel) . $sInput . $sChilds,
			"label"		=> $sLabel,
			"name"		=> $this->_getElementHtmlName(),
			"id"		=> $this->_getElementHtmlId(),
			"value"		=> htmlspecialchars($sValue),
			"input" => $sInput,
			"childs" => $sChilds,
			"html" => $sInput . $sChilds,
			"addparams"	=> $this->_getAddInputParams(),
		);

		// allowed because of $bCustomIncludeScript = TRUE
		$this->aConfig = array(
			"timeObserver" => $sTimeObserver,
			"searchType" => $sSearchType,
			"searchFields" => $sSearchOnFields,
			"searchUrl" => $sSearchUrl,
			"item" => array(
				"width" => $this->_navConf("/itemwidth"),
				"height" => $this->_navConf("/itemheight"),
				"style" => $this->_navConf("/itemstyle"),
				"class" => $sItemClass,
			),
			"selectedItemClass" => $sSelectedItemClass,
		);
		$this->includeScripts($this->aConfig);

		return $aHtmlBag;
	}

	function &_getRowsSubpart($sTemplate) {

		$aRowsTmpl = array();
		if (($sAltRows = $this->_navConf("/template/alternaterows")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($sAltRows)) {
				$sAltRows = tx_ameosformidable::isRunneable($sAltRows);
			}
			if(!is_string($sAltRows)) {
				$sAltRows = FALSE;
			} else {
				$sAltRows = trim($sAltRows);
			}
		}

		$aAltList = t3lib_div::trimExplode(",", $sAltRows);
		if(sizeof($aAltList) > 0) {
			$sRowsPart = t3lib_parsehtml::getSubpart($sTemplate, "###ROWS###");

			reset($aAltList);
			while(list(, $sAltSubpart) = each($aAltList)) {
				$sHtml = t3lib_parsehtml::getSubpart($sRowsPart, $sAltSubpart);
				if(empty($sHtml)) {
					$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template with subpart marquer <b>'" . $sAltSubpart . "'</b> returned an empty string - Please check your template!");
				}
				$aRowsTmpl[] = $sHtml;
			}
		}

		return $aRowsTmpl;
	}

	function &_getTemplate() {

		if(($aTemplate = $this->_navConf("/template")) !== FALSE) {

			$sPath = t3lib_div::getFileAbsFileName($this->oForm->_navConf("/path", $aTemplate));
			if(!file_exists($sPath)) {
				$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) doesn't exists.");
			} elseif(is_dir($sPath)) {
				$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) is a directory, and should be a file.");
			} elseif(!is_readable($sPath)) {
				$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path exists but is not readable.");
			}

			if(($sSubpart = $this->oForm->_navConf("/subpart", $aTemplate)) === FALSE) {
				$sSubpart = $this->getName();
			}

			$sHtml = t3lib_parsehtml::getSubpart(
				t3lib_div::getUrl($sPath),
				$sSubpart
			);

			if(trim($sHtml) == "") {
				$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template (<b>'" . $sPath . "'</b> with subpart marquer <b>'" . $sSubpart . "'</b>) <b>returned an empty string</b> - Check your template!");
			}
			
			return $sHtml;
			/*return $this->oForm->_parseTemplateCode(
				$sHtml,
				$aChildsBag,
				array(),
				FALSE
			);*/
		} else {
			$this->oForm->mayday("The renderlet:autocomplete <b>" . $this->_getName() . "</b> requires /template to be properly set. Please check your XML configuration");
		}
	}

	function handleAjaxRequest($oRequest) {
		$this->aConfig['searchType'] = t3lib_div::_GP('searchType');
		$this->aConfig['searchText'] = t3lib_div::_GP('searchText');
		$this->aConfig['searchCounter'] = (int)t3lib_div::_GP('searchCounter');

		$this->_renderList($aParts, $aRowsHtml);

		return array(
			"counter" => $this->aConfig['searchCounter'],
			"html" => array(
				"before" => trim($aParts[0]),
				"after" => trim($aParts[1]),
				"childs" => $aRowsHtml,
			),
			"results" => count($aRowsHtml),
		);
	}

	function _renderList(&$aParts, &$aRowsHtml) {

		$this->_initLimitAndSort();
		$this->_initFilters();
		$this->_initDatasource();

		if ($this->aDatasource && $this->aDatasource["numrows"]) {		// if there is some items to render

			$iNbAlt = count($this->aRowsSubpart);

			$aRows = $this->aDatasource["results"];
			
			foreach($aRows as $i => $aRow) {
				$aCurRow = $this->_refineRow($aRow);
				$sRowHtml = $this->oForm->_parseTemplateCode(
					$this->aRowsSubpart[$i % $iNbAlt],		// alternate rows
					$aCurRow
				);
				$aRowsHtml[] = trim($sRowHtml);
			}

			$sHtml = t3lib_parsehtml::substituteSubpart(
				trim($this->sTemplate),
				"###ROWS###",
				"###ROWS###",
				FALSE,
				FALSE
			);

			$sHtml = str_replace(
				array(
					"{autocomplete_search.numrows}",
				),
				array(
					count($aRows),
				),
				$sHtml
			);
			$aParts = explode("###ROWS###", $sHtml);
		}
	}

	function &_refineRow($aData) {

		switch($this->aConfig['searchType']) {
		case "begin":
			$sPattern = "/^" .$this->aConfig['searchText']. "/ui";
			break;
		case "inside":
			$sPattern = "/" .$this->aConfig['searchText']. "/ui";
			break;
		case "end":
			$sPattern = "/" .$this->aConfig['searchText']. "$/ui";
			break;
		}
		$bReplaced = false;

		array_push($this->oForm->oDataHandler->__aListData, $aData);
		foreach($this->aChilds as $sName => $oChild) {
			if ($bReplaced) {
				$sValue = $aData[$sName];
			} else {
				$sValue = preg_replace_callback(
					$sPattern,
					array(
						$this,
						"highlightSearch",
					),
					$aData[$sName],
					1		// replace only the first occurence
				);
				if ($sValue != $aData[$sName]) {
					$bReplaced = true;
				}
			}
			$this->aChilds[$sName]->setValue($sValue);
		}
		$aCurRow = $this->renderChildsBag();
		array_pop($this->oForm->oDataHandler->__aListData);
		return $aCurRow;
	}

	function highlightSearch($aMatches) {
		return "<strong>" .$aMatches[0]. "</strong>";
	}

	function _initDatasource() {
		if(($sDsToUse = $this->_navConf("/datasource/use")) !== FALSE) {
			if(!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
				$this->oForm->mayday("RENDERLET AUTOCOMPLETE <b>" . $this->_getName() . "</b> - refers to undefined datasource '" . $sDsToUse . "'. Check your XML conf.");
			} else {
				$this->oDataStream =& $this->oForm->aODataSources[$sDsToUse];
				$this->aDatasource = $this->oDataStream->_fetchData($this->aLimitAndSort, $this->aFilters);
			}
		}
	}

	function _initLimitAndSort() {

		if(($sLimit = $this->_navConf("/datasource/limit")) === FALSE) {
			$sLimit = "5";
		}

		if(($sSortBy = $this->_navConf("/datasource/orderby")) === FALSE) {
			$sSortBy = "tstamp";
		}

		if(($sSortDir = $this->_navConf("/datasource/orderdir")) === FALSE) {
			$sSortDir = "DESC";
		}

		$this->aLimitAndSort = array(
			"perpage" => $sLimit,
			"sortcolumn" => $sSortBy,
			"sortdirection" => $sSortDir,
		);
	}

	function _initFilters() {

		$this->aFilters = array();

		$aFields = explode(",", $this->aConfig['searchFields']);

		foreach($aFields as $sField) {

			switch($this->aConfig['searchType']) {
			case "begin":
				$aFilter[] = "( ".$sField." LIKE '".$GLOBALS['TYPO3_DB']->quoteStr($this->aConfig['searchText'], '')."%' )";
				break;
			case "inside":
				$aFilter[] = "( ".$sField." LIKE '%".$GLOBALS['TYPO3_DB']->quoteStr($this->aConfig['searchText'], '')."%' )";
				break;
			case "end":
				$aFilter[] = "( ".$sField." LIKE '%".$GLOBALS['TYPO3_DB']->quoteStr($this->aConfig['searchText'], '')."' )";
				break;
			}
		}

		$this->aFilters[] = "( " . implode(" OR ", $aFilter) . " )";
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function _getElementHtmlName($sName = FALSE) {
		
		$sRes = parent::_getElementHtmlName($sName);
		$aData =& $this->oForm->oDataHandler->_getListData();

		if(!empty($aData)) {
			$sRes .= "[" . $aData["uid"] . "]";
		}

		return $sRes;
	}
	
	function _getElementHtmlNameWithoutFormId($sName = FALSE) {
		$sRes = parent::_getElementHtmlNameWithoutFormId($sName);
		$aData =& $this->oForm->oDataHandler->_getListData();

		if(!empty($aData)) {
			$sRes .= "[" . $aData["uid"] . "]";
		}

		return $sRes;
	}

	function _getElementHtmlId($sId = FALSE) {
		
		$sRes = parent::_getElementHtmlId($sId);

		$aData =& $this->oForm->oDataHandler->_getListData();
		if(!empty($aData)) {
			$sRes .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $aData["uid"] . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
		}

		return $sRes;
	}

	function _checkRequiredProperties() {

		if($this->_navConf("/datasource/use") === FALSE) {
			$this->oForm->mayday("The renderlet:autocomplete <b>" . $this->_getName() . "</b> requires /datasource/use to be properly set. Please check your XML configuration");
		}
		if($this->_navConf("/template/path") === FALSE) {
			$this->oForm->mayday("The renderlet:autocomplete <b>" . $this->_getName() . "</b> requires /template/path to be properly set. Please check your XML configuration");
		}
		if($this->_navConf("/template/subpart") === FALSE) {
			$this->oForm->mayday("The renderlet:autocomplete <b>" . $this->_getName() . "</b> requires /template/subpart to be properly set. Please check your XML configuration");
		}
		if($this->_navConf("/template/alternaterows") === FALSE) {
			$this->oForm->mayday("The renderlet:autocomplete <b>" . $this->_getName() . "</b> requires /template/alternaterows to be properly set. Please check your XML configuration");
		}
		if (($aChilds = $this->_navConf("/childs")) === FALSE ){
			$this->oForm->mayday("The renderlet:autocomplete <b>" . $this->_getName() . "</b> requires /childs to be properly set. Please check your XML configuration");
		} elseif (!is_array($aChilds)) {
			$this->oForm->mayday("The renderlet:autocomplete <b>" . $this->_getName() . "</b> requires at least one child to be properly set. Please check your XML configuration: define a renderlet:* as child.");
		}

	}

}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_autocomplete/api/class.tx_rdtautocomplete.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_autocomplete/api/class.tx_rdtautocomplete.php"]);
}

?>