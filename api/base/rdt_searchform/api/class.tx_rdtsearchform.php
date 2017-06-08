<?php
/**
 * Plugin 'rdt_searchform' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtsearchform extends formidable_mainrenderlet {

	var $oDataSource = FALSE;
	var $aCriterias = FALSE;
	var $aFilters = FALSE;
	var $aDescendants = FALSE;
	var $bAjaxContext = FALSE;

	var $sMajixClass = "Searchform";
	var $aLibs = array(
		"rdt_searchform_class" => "res/js/searchform.js",
	);

	function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = false) {
		parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);
		$this->_initDescendants();	// early init (meaning before removing unprocessed rdts)
	}

	function _render() {

		$this->_initData();

		$aChildBags = $this->renderChildsBag();
		$sCompiledChilds = $this->renderChildsCompiled($aChildBags);

		if($this->isRemoteReceiver() && !$this->mayDisplayRemoteReceiver()) {
			return array(
				"__compiled" => "",
			);
		}

		return array(
			"__compiled" => $this->_wrapIntoContainer(
				$this->_displayLabel($sLabel) . $sCompiledChilds,
				$this->_getAddInputParams()
			),
			"childs" => $aChildBags
		);
	}

	function _wrapIntoContainer($sHtml, $sAddParams = "") {
		return "<div id=\"" . $this->_getElementHtmlId() . "\"" . $sAddParams . ">" . $sHtml . "</div>";
	}

	function getDescendants() {

		$aDescendants = array();
		$sMyName = $this->getAbsName();

		$aRdts = array_keys($this->oForm->aORenderlets);
		reset($aRdts);
		while(list(, $sName) = each($aRdts)) {
			if($this->oForm->aORenderlets[$sName]->isDescendantOf($sMyName)) {
				$aDescendants[] = $sName;
			}
		}

		return $aDescendants;
	}

	function _initDescendants($bForce = FALSE) {
		if($bForce === TRUE || $this->aDescendants === FALSE) {
			$this->aDescendants = $this->getDescendants();
		}
	}

	function _initData() {
		$this->_initDescendants(TRUE);	// done in _init(), re-done here to filter out unprocessed rdts
		$this->_initCriterias();	// if submitted, take from post ; if not, take from session
									// and inject values into renderlets
		$this->_initFilters();
		$this->_initDataSource();
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function isRemoteSender() {
	    return ($this->_navConf("/remote/mode") === "sender");
	}

	function isRemoteReceiver() {
	    return ($this->_navConf("/remote/mode") === "receiver");
	}

	function _initDataSource() {

    	if($this->isRemoteSender()) {
	    	return;
    	}

		if($this->oDataSource === FALSE) {

			if(($sDsToUse = $this->_navConf("/datasource/use")) === FALSE) {
				$this->oForm->mayday("RENDERLET SEARCHFORM - requires /datasource/use to be properly set. Check your XML conf.");
			} elseif(!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
				$this->oForm->mayday("RENDERLET SEARCHFORM - refers to undefined datasource '" . $sDsToUse . "'. Check your XML conf.");
			}

			$this->oDataSource =& $this->oForm->aODataSources[$sDsToUse];
		}
	}

	function clearFilters() {

		reset($this->aDescendants);
		while(list(, $sName) = each($this->aDescendants)) {
			$this->oForm->aORenderlets[$sName]->setValue("");
		}

		$this->aCriterias = FALSE;
		$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];
		$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["criterias"] = array();
		$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]['infos']['selection'] = array();

		if($this->isRemoteReceiver()) {
			$aAppData["rdt_lister"][$this->getRemoteSenderSearchHash()][$this->getRemoteSenderAbsName()]["criterias"] = array();
			$aAppData["rdt_lister"][$this->getRemoteSenderSearchHash()][$this->getRemoteSenderAbsName()]['infos']['selection'] = array();
		}

		
	}

	function getCriterias() {
		return $this->aCriterias;
	}


	function getRemoteSenderFormId() {
		if($this->isRemoteReceiver()) {
			if(($sSenderFormId = $this->_navConf("/remote/senderformid")) !== FALSE) {
				return $sSenderFormId;
			}
		}

		return FALSE;
	}

	function getRemoteSenderAbsName() {
		if($this->isRemoteReceiver()) {
			if(($sSenderAbsName = $this->_navConf("/remote/senderabsname")) !== FALSE) {
				return $sSenderAbsName;
			}
		}

		return FALSE;
	}

	function _initCriterias() {

		if($this->aCriterias === FALSE) {

			$bUpdate = FALSE;
			$bUseDefaultValue = FALSE;

		    if($this->isRemoteReceiver()) {

		        if(($sFormId = $this->getRemoteSenderFormId()) === FALSE) {
		            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.");
		        }

		        if(($sSearchAbsName = $this->getRemoteSenderAbsName()) === FALSE) {
		            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.");
		        }

		    } else {
		        $sFormId = $this->oForm->formid;
		        $sSearchAbsName = $this->getAbsName();
		    }

			$this->aCriterias = array();

			$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];

			if(!is_array($aAppData)) {
				$aAppData = array();
			}

			if(!array_key_exists("rdt_lister", $aAppData)) {
				$aAppData["rdt_lister"] = array();
			}


			if(!array_key_exists($this->getSearchHash(), $aAppData["rdt_lister"])) {
				$aAppData["rdt_lister"][$this->getSearchHash()] = array();
			}

			if(!array_key_exists($sSearchAbsName, $aAppData["rdt_lister"][$this->getSearchHash()])) {
				# searchform has never been used; we have to consider default values of search fields
				$bUseDefaultValue = TRUE;
				$aAppData["rdt_lister"][$this->getSearchHash()][$sSearchAbsName] = array();
			}

			if(
				!array_key_exists("criterias", $aAppData["rdt_lister"][$this->getSearchHash()][$sSearchAbsName]) || (
					$this->useStickyFilters() === FALSE && $this->oForm->isFirstDisplay()
				)
			) {
				$aAppData["rdt_lister"][$this->getSearchHash()][$sSearchAbsName]["criterias"] = array();
				$aAppData["rdt_lister"][$this->getSearchHash()][$sSearchAbsName]["infos"] = array(
					"lastsearchtime" => FALSE,
				);
			}

			if($this->shouldUpdateCriteriasClassical() || $this->shouldUpdateCriteriasAjax()) {
				$bUpdate = TRUE;
				if($this->isRemoteReceiver()) {
					// set in session
					reset($this->aDescendants);
					while(list(, $sAbsName) = each($this->aDescendants)) {
						$sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
						$sRemoteAbsName = $sSearchAbsName . "." . $sRelName;
						$this->aCriterias[$sRemoteAbsName] = $this->oForm->aORenderlets[$sAbsName]->getValue();
					}
				} else {
					// set in session
					reset($this->aDescendants);
					while(list(, $sAbsName) = each($this->aDescendants)) {
						if(!$this->oForm->aORenderlets[$sAbsName]->hasChilds()) {
							$this->aCriterias[$sAbsName] = $this->oForm->aORenderlets[$sAbsName]->getValue();
						}
					}
				}
			} elseif($this->shouldUpdateCriteriasRemoteReceiver()) {

				$bUpdate = TRUE;
				if($this->isRemoteReceiver()) {
					// set in session

					$aRawPost = $this->oForm->_getRawPost($sFormId);

					reset($this->aDescendants);
					while(list(, $sAbsName) = each($this->aDescendants)) {

						$sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
						$sRemoteAbsName = $sSearchAbsName . "." . $sRelName;
						$sRemoteAbsPath = str_replace(".", "/", $sRemoteAbsName);

						$mValue = $this->oForm->navDeepData($sRemoteAbsPath, $aRawPost);
						$this->aCriterias[$sRemoteAbsName] = $mValue;
						$this->oForm->aORenderlets[$sAbsName]->setValue($mValue);	// setting value in receiver

					}
				}
			} elseif($bUseDefaultValue === TRUE) {

				# using search fields default values as criterias
				$bUpdate = TRUE;

				reset($this->aDescendants);
				while(list(, $sAbsName) = each($this->aDescendants)) {
					$mDefaultValue = $this->oForm->aORenderlets[$sAbsName]->__getDefaultValue();
					if($mDefaultValue !== FALSE) {
						$this->aCriterias[$sAbsName] = $mDefaultValue;
					} else {
						$this->aCriterias[$sAbsName] = "";
					}
				}
			}

			if($bUpdate === TRUE) {

				if($this->_getParamsFromGET()) {

					$aGet = (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET($sFormId)) ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GET($sFormId) : array();

					reset($aGet);
					while(list($sAbsName, ) = each($aGet)) {
						if(array_key_exists($sAbsName, $this->oForm->aORenderlets)) {

							$this->aCriterias[$sAbsName] = $aGet[$sAbsName];

							$this->oForm->aORenderlets[$sAbsName]->setValue(
								$this->aCriterias[$sAbsName]
							);

							$aTemp = array(
								$sFormId => array(
									$sAbsName => 1,
								),
							);

							$this->oForm->setParamsToRemove($aTemp);
						}
					}
				}

				$aAppData["rdt_lister"][$this->getSearchHash()][$sSearchAbsName]["criterias"] = $this->aCriterias;
				$aAppData["rdt_lister"][$this->getSearchHash()][$sSearchAbsName]["infos"]["lastsearchtime"] = time();
			} else {
				// take from session
				$this->aCriterias = $aAppData["rdt_lister"][$this->getSearchHash()][$sSearchAbsName]["criterias"];

				if($this->isRemoteReceiver()) {

					if(($sFormId = $this->getRemoteSenderFormId()) === FALSE) {
			            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.");
			        }

			        if(($sSearchAbsName = $this->getRemoteSenderAbsName()) === FALSE) {
			            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.");
			        }

					reset($this->aCriterias);
					while(list($sAbsName, ) = each($this->aCriterias)) {
						$sRelName = $this->oForm->relativizeName(
							$sAbsName,
							$sSearchAbsName
						);

						$sLocalAbsName = $this->getAbsName() . "." . $sRelName;
						if(array_key_exists($sLocalAbsName, $this->oForm->aORenderlets)) {
							$this->oForm->aORenderlets[$sLocalAbsName]->setValue(
								$this->aCriterias[$sAbsName]
							);
						}
					}
				} else {
					reset($this->aCriterias);
					while(list($sAbsName, ) = each($this->aCriterias)) {
						if(array_key_exists($sAbsName, $this->oForm->aORenderlets)) {
							$this->oForm->aORenderlets[$sAbsName]->setValue(
								$this->aCriterias[$sAbsName]
							);
						}
					}
				}
			}

		}

		#debug($this->aCriterias, "criterias");

		#$this->aCriterias = $this->processBeforeSearch($this->aCriterias);
	}

	function useStickyFilters() {
		return $this->defaultTrue("/stickyfilters");
	}

	function shouldUpdateCriteriasRemoteReceiver() {

		if($this->isRemoteReceiver()) {
			if(($sFormId = $this->getRemoteSenderFormId()) === FALSE) {
	            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.");
	        }

	        if(($sSearchAbsName = $this->getRemoteSenderAbsName()) === FALSE) {
	            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.");
	        }

			if($this->oForm->oDataHandler->_isSearchSubmitted($sFormId) || $this->oForm->oDataHandler->_isFullySubmitted($sFormId)) {	# full submit to allow no-js browser to search
				reset($this->aDescendants);
				while(list(, $sAbsName) = each($this->aDescendants)) {
					$sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
					$sRemoteAbsName = $sSearchAbsName . "." . $sRelName;

					if($this->oForm->aORenderlets[$sAbsName]->hasSubmitted($sFormId, $sRemoteAbsName)) {
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}

	function shouldUpdateCriteriasClassical() {
		if($this->oForm->oDataHandler->_isSubmitted() === TRUE) {

			if($this->hasSubmitted() && $this->oForm->oDataHandler->_isSearchSubmitted()) {
				return TRUE;
			}

			reset($this->aDescendants);
			while(list(, $sAbsName) = each($this->aDescendants)) {
				if(
					array_key_exists($sAbsName, $this->oForm->aORenderlets) &&
					#$this->oForm->aORenderlets[$sAbsName]->maySubmit() &&	// any renderlet may submit using a majix event, so we don't check it anymore
					$this->oForm->aORenderlets[$sAbsName]->hasSubmitted() &&
					$this->oForm->oDataHandler->_isSearchSubmitted()) {	// the mode is not determined by the renderlet anymore, but rather by the datahandler (one common submit per page, anyway)

					return TRUE;
				}
			}
		} else {
			if($this->_getParamsFromGET()) {
				$aGet = (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET($this->oForm->formid)) ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GET($this->oForm->formid) : array();
				$aIntersect = array_intersect(array_keys($aGet), array_keys($this->oForm->aORenderlets));
				return count($aIntersect) > 0;	// are there get params in url matching at least one criteria in the searchform ?
			}
		}

		return FALSE;
	}

	function shouldUpdateCriteriasAjax() {
		return $this->bAjaxContext;
	}

	function shouldUpdateCriterias() {

		if($this->isRemoteReceiver()) {
			if($this->shouldUpdateCriteriasRemoteReceiver() === TRUE) {
				return TRUE;
			} if($this->mayDisplayRemoteReceiver() === TRUE) {
				return $this->shouldUpdateCriteriasClassical();
			}
		} else {
			return $this->shouldUpdateCriteriasClassical();
		}

		return FALSE;
	}

	function mayDisplayRemoteReceiver() {
		return $this->isRemoteReceiver() && !$this->defaultTrue("/remote/invisible");
	}

	function processBeforeSearch($aCriterias) {

		if(($aBeforeSearch = $this->_navConf("/beforesearch")) !== FALSE && tx_ameosformidable::isRunneable($aBeforeSearch)) {
			$aCriterias = $this->callRunneable($aBeforeSearch, $aCriterias);
		}

		if(!is_array($aCriterias)) {
			$aCriterias = array();
		}

		return $aCriterias;
	}

	function processAfterSearch($aResults) {

		if(($aAfterSearch = $this->_navConf("/aftersearch")) !== FALSE && tx_ameosformidable::isRunneable($aAfterSearch)) {
			$aResults = $this->callRunneable($aAfterSearch, $aResults);
		}

		if(!is_array($aResults)) {
			$aResults = array();
		}

		return $aResults;
	}

	function _initFilters() {

		if($this->aFilters === FALSE) {

			$this->aFilters = array();

			//$aFormData = $this->oForm->oDataHandler->_getFormDataManaged();
			$aCriterias = $this->processBeforeSearch($this->aCriterias);
			reset($aCriterias);

			if ($this->isRemoteReceiver()) {

				if(($sFormId = $this->getRemoteSenderFormId()) === FALSE) {
		            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.");
		        }

		        if(($sSearchAbsName = $this->getRemoteSenderAbsName()) === FALSE) {
		            $this->oForm->mayday("RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.");
		        }

				while(list($sRdtName,) = each($aCriterias)) {

					$sRelName = $this->oForm->relativizeName(
						$sRdtName,
						$sSearchAbsName
					);


					$sLocalAbsName = $this->getAbsName() . "." . $sRelName;

					if(array_key_exists($sLocalAbsName, $this->oForm->aORenderlets)) {
						$oRdt =& $this->oForm->aORenderlets[$sLocalAbsName];

						if($oRdt->_searchable()) {

							$sValue = $oRdt->_flatten($aCriterias[$sRdtName]);

							if(!$oRdt->_emptyFormValue($sValue)) {
								$this->aFilters[] = $oRdt->_sqlSearchClause($sValue);
							}
						}
					}
				}
			} else {
				while(list($sRdtName,) = each($aCriterias)) {
					if(array_key_exists($sRdtName, $this->oForm->aORenderlets)) {
						$oRdt =& $this->oForm->aORenderlets[$sRdtName];

						if($oRdt->_searchable()) {

							$sValue = $oRdt->_flatten($aCriterias[$sRdtName]);

							if(!$oRdt->_emptyFormValue($sValue)) {
								$this->aFilters[] = $oRdt->_sqlSearchClause($sValue);
							}
						}
					}
				}
			}

			reset($this->aFilters);
		}
	}

	function &_getFilters() {
		$this->_initFilters();
		reset($this->aFilters);
		return $this->aFilters;
	}

	function &fetchData($aConfig = array()) {
		return $this->_fetchData($aConfig);
	}

	function &_fetchData($aConfig = array()) {
		if($this->defaultFalse('/keepalllistidinsession')) {
			$aListUidConfig = array(
				'page' => 0,
				'sortcolumn' => $aConfig['sortcolumn'],
				'sortdirection' => $aConfig['sortdirection'],
			);

			$aRecords = $this->oDataSource->_fetchData(
				$aListUidConfig,
				$this->_getFilters()
			);

			if(($mCol = $this->_navConf('/columnforlist')) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mCol)) {
					$mCol = $this->oForm->callRunneable($mCol);
				}
			} else {
				$mCol = 'uid';
			}

			foreach($aRecords['results'] as $aRecord) {
				if(!empty($aRecord[$mCol])) {
					$aList[] = $aRecord[$mCol];
				}
			}

			$aData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["infos"];
			$aData["list"] = $aList;
		}

		$aResults = $this->processAfterSearch(
			$this->oDataSource->_fetchData(
				$aConfig,
				$this->_getFilters()
			)
		);

		if($this->defaultFalse("/keepresultsinsession") === TRUE) {
			$this->storeResultsInSession($aResults);
		}

		reset($aResults);
		return $aResults;
	}

	function storeResultsInSession($aResults) {
		$aData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["infos"];
		$aData["results"] = $aResults["results"];
		$aData["numrows"] = $aResults["numrows"];
	}

	function _renderOnly() {
		return $this->defaultTrue("/renderonly");
	}

	function _getParamsFromGET() {
		return $this->defaultFalse("/paramsfromget");
	}

	function _searchable() {
		return FALSE;
	}
	
	function handleRefreshContext($aContext) {
		if(is_array($aContext)) {
			foreach($aContext as $sKey => $aRdtContext) {
				if($oRdt =& $this->oForm->rdt($sKey) !== FALSE) {
					$oRdt->handleRefreshContext($aRdtContext);
					if(array_key_exists("value", $aRdtContext)) {
						$oRdt->setValue($aRdtContext["value"]);
					}
				}
			}
		}
	}

	function majixSubmitAjaxSearch() {
		$this->bAjaxContext = TRUE;
		$this->aCriterias = FALSE;
		$this->_initCriterias();

		$this->aFilters = FALSE;

		$aLister = array();
		
		foreach($this->oForm->aORenderlets as $oRdt) {
			if(trim($oRdt->_navConf('/type')) === 'LISTER') {
				if($oRdt->sDsType == 'searchform') {
					if($oRdt->_navConf('/searchform/use') == $this->getAbsName()) {
						$aLister[] = $oRdt;
					}
				}
			}
		}

		$aTask = array();
		foreach($aLister as $oListerRdt) {
			$aConfig = array(
				"page" => ($oRdt->aLimitAndSort["curpage"] - 1),
				"perpage" => $oRdt->aLimitAndSort["rowsperpage"],
				"sortcolumn" => $oRdt->aLimitAndSort["sortby"],
				"sortdirection" => $oRdt->aLimitAndSort["sortdir"],
			);
			if(isset($oListerRdt)) {
				$oListerRdt->clearSelection();
				$oListerRdt->_fetchData($aConfig);
				$oListerRdt->setPage(1);

				$aTask[] = $oListerRdt->majixRepaint();
			}
		}
		return $aTask;
	}

	function getSearchHash() {
		if($this->oForm->useFHash()) {
			return $this->oForm->getFHash();
		} else {
			return $this->oForm->formid;
		}
	}

	function getRemoteSenderSearchHash() {
		return $this->getRemoteSenderFormId();
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_searchform/api/class.tx_rdtsearchform.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_searchform/api/class.tx_rdtsearchform.php"]);
	}
?>
