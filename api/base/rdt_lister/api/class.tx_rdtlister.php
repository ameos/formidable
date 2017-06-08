<?php
/**
 * Plugin 'rdt_lister' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtlister extends formidable_mainrenderlet {

	var $aLibs = array(
		"rdt_lister_class" => "res/js/lister.js",
	);

	var $sMajixClass = "Lister";

	var $bCustomIncludeScript = TRUE;

	var $oDataStream = FALSE;
	var $sDsType = FALSE;
	var $aOColumns = FALSE;
	var $aChilds = FALSE;		// reference to aOColumns
	var $aActionListbox = FALSE;	// render of rdt actions
	var $aActions = array();	// list of actions
	var $bAllRowsSelected = FALSE;
	var $aPager = FALSE;
	var $aLimitAndSort = FALSE;
	var $bDefaultTemplate = FALSE;
	var $bNoTemplate = FALSE;
	var $bResetPager = FALSE;
	var $mCurrentSelected = FALSE;

	var $aRdtByRow = array();
	var $aClientified = array();
	var $aClientifyColumns = array();

	var $iCurRowNum = FALSE;
	var $iTempPage = FALSE;

	var $aSelectedrows = FALSE;

	var $sGridurl;

	var $aCurrentRows = FALSE;
	var $aExportableColumns = FALSE;
	var $aSearchableColumns = FALSE;

	function _render() {

		if(!$this->mayShowAtStartup() && $this->oForm->isFirstDisplay()) {
			return "";
		}

		$this->_initDataStream();
		#$this->aLimitAndSort = FALSE;
		$this->_initLimitAndSort();

		$this->aRdtByRow = array();
		$this->aClientifyColumns = array();
		$this->aClientified = array();
		if(($sClientify = $this->_navConf("/clientify")) !== FALSE && trim($sClientify) !== "") {
			$this->aClientifyColumns = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $sClientify);
		}

		if(!$this->isFlexgridLister()) {
			$aData = $this->_fetchData(
				$aConfig = array(
					"page" => ($this->aLimitAndSort["curpage"] - 1),
					"perpage" => $this->aLimitAndSort["rowsperpage"],
					"sortcolumn" => $this->aLimitAndSort["sortby"],
					"sortdirection" => $this->aLimitAndSort["sortdir"],
				)
			);
		}

		if(intval($aData["numrows"]) === 0) {
			if(($mEmpty = $this->_navConf("/ifempty")) !== FALSE && !$this->isFlexgridLister()) {
				if(is_array($mEmpty)) {
					if($this->oForm->defaultTrue("/process", $mEmpty) === FALSE) {
						return array(
							"__compiled" => "",
							"pager." => array(
								"numrows" => 0,
							)
						);
					}

					if($this->oForm->_navConf("/message", $mEmpty) !== FALSE) {
						if(tx_ameosformidable::isRunneable($mEmpty["message"])) {
							$sMessage = $this->callRunneable($mEmpty["message"]);
						} else {
							$sMessage = $mEmpty["message"];
						}

						$sMessage = $this->oForm->_substLLLInHtml($sMessage);

						return array(
							"__compiled" => $this->_wrapIntoContainer($this->oForm->_getLLLabel($sMessage), $this->_getAddInputParams()),
							"pager." => array(
								"numrows" => 0,
							)
						);
					}
				} else {
					return array(
						"__compiled" => $this->_wrapIntoContainer($this->oForm->_getLLLabel($mEmpty), $this->_getAddInputParams()),
						"pager." => array(
							"numrows" => 0,
						)
					);
				}
			}
		}

		$this->_initPager($aData["numrows"]);
		if($this->isEditableLister() && $this->defaultFalse("/keepselectioninsession") === TRUE && $this->sDsType == 'searchform') {
			$aSessionData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->oDataStream->getSearchHash()][$this->oDataStream->getAbsName()]["infos"];
			$this->aSelectedRows = $aSessionData["selection"];
		}

		$this->_initActionsListbox();

		$sAddParams = $this->_getAddInputParams();

		$aHtmlBag = array(
			"__compiled" => $this->_wrapIntoContainer($this->_renderList($aData), $sAddParams),
			"addparams" => $sAddParams,
			"pager." => array(
				"display" => ($this->aPager["display"] === TRUE) ? "1" : "0",
				"page" => $this->aPager["page"],
				"pagemax" => $this->aPager["pagemax"],
				"offset" => $this->aPager["offset"],
				"numrows" => $this->aPager["numrows"],
				"links." => array(
					"first" => $this->aPager["links"]["first"],
					"prev" => $this->aPager["links"]["prev"],
					"next" => $this->aPager["links"]["next"],
					"last" => $this->aPager["links"]["last"],
				),
				"rowsperpage" => $this->aLimitAndSort["rowsperpage"],
				"limitoffset" => $this->aLimitAndSort["limitoffset"],
				"limitdisplayed" => $this->aLimitAndSort["limitdisplayed"],
				"sortby" => $this->aLimitAndSort["sortby"],
				"sortdir" => $this->aLimitAndSort["sortdir"],
			),
		);

		if(is_array($this->aActionListbox) && array_key_exists('__compiled', $this->aActionListbox)) {
			$aHtmlBag['actions']  = $this->aActionListbox['__compiled'];
			$aHtmlBag['actions.'] = $this->aActionListbox;
		} else {
			$aHtmlBag['actions']  = $this->aActionListbox;
		}
		
		if($this->isFlexgridLister()) {
			$aHtmlBag["grid-class"] = 'grid-' . $this->getAbsName();
		}

		$bAjaxLister = $this->isAjaxLister();
		if($bAjaxLister === TRUE) {
			$this->oForm->bStoreFormInSession = TRUE;
			if(empty($GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxevent"][$this->_getSessionDataHashKey()])) {
				$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxevent"][$this->_getSessionDataHashKey()] = array(
					"requester" => array(
						"name" => "tx_ameosformidable",
						"xpath" => "/",
					),
				);
			}
		}


		$aIncludeScripts = array(
			"rdtbyrow" => $this->aRdtByRow,
			"columns" => array_keys($this->aOColumns),
			"clientified" => $this->aClientified,
			"isajaxlister" => $bAjaxLister,
			"iseditablelister" => $this->isEditableLister(),
			"isflexigrid" => $this->isFlexgridLister(),
			"selectedrow" => $this->getSelectedRows(),
			"currentrows" => array(),
			"isiterable" => TRUE,
			"selected" => $this->mCurrentSelected,
			"sort" => array(
				"column" => $this->aLimitAndSort["sortby"],
				"direction" => $this->aLimitAndSort["sortdir"],
			),
			"repaintfirst" => $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').repaintFirst()",
				FALSE,
				FALSE
			),
			"repaintprev" => $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').repaintPrev()",
				FALSE,
				FALSE
			),
			"repaintnext" => $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').repaintNext()",
				FALSE,
				FALSE
			),
			"repaintlast" => $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').repaintLast()",
				FALSE,
				FALSE
			),
			"repaintsortby" => $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').repaintSortBy()",
				"sys_event.sortcol, sys_event.sortdir",
				FALSE
			),
		);

		 if($this->isEditableLister()) {
			$aIncludeScripts["selectrow"] = $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').selectRow()",
				"sys_event.currentrows",
				FALSE
			);

			$aIncludeScripts["unselectrow"] = $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').unselectRow()",
				"sys_event.currentrows",
				FALSE
			);

			$aIncludeScripts["selectallrows"] = $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').selectAllRows()",
				FALSE,
				FALSE
			);
			
			$aIncludeScripts["unselectallrows"] = $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').unselectAllRows()",
				FALSE,
				FALSE
			);
		}

		foreach($this->aPager['window'] as $iPage => $sLink) {
			$aIncludeScripts["repaintwindow"][$iPage] = $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').repaintWindow()",
				"rawData::page::" . $iPage,
				FALSE
			);
		}

		if($this->isFlexgridLister()) {
			$this->aLibs['rdt_lister_flexgridlib'] = 'res/flexgrid/js/flexigrid.js';

			$this->oForm->additionalHeaderDataLocalStylesheet(
				$this->oForm->toServerPath('/typo3conf/ext/ameos_formidable/api/base/rdt_lister/res/flexgrid/css/flexigrid.pack.css'),
				"tx_ameosformidable_flexgrid_cssfile_" . $this->_getName()
			);

			if($this->isAdvanceFlexgridLister()) {
				/* BEGIN: forging access to advance flexgrid mode */

				$sHtmlId = $this->_getElementHtmlId();
				$sObject = "rdt_lister";
				$sServiceKey = "getdata";
				$sFormId = $this->oForm->formid;
				$sSafeLock = $this->_getSessionDataHashKey();
				$sSessionHash = $this->_getSessionDataHashKey();
				$sThrower = $sHtmlId;

				$this->sGridurl = $this->oForm->_removeEndingSlash(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("TYPO3_SITE_URL")) . "/index.php?eID=tx_ameosformidable&object=" . $sObject . "&servicekey=" . $sServiceKey . "&formid=" . $sFormId . "&safelock=" . $sSafeLock . "&thrower=" . $sThrower . '&sessionhash='. $sSessionHash;

				$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"][$sObject][$sServiceKey][$sSafeLock] = array(
					"requester" => array(
						"name" => $this->getAbsName(),
						"xpath" => $this->sXPath,
					),
				);

				/* END: forging access to advance flexgrid mode */
			}
		}

		$this->includeScripts($aIncludeScripts);

		return $aHtmlBag;
	}

	function mayShowAtStartup() {
		return $this->defaultTrue("/showatstartup");
	}

	function _wrapIntoContainer($sHtml, $sAddParams = "") {
		if($this->isInline()) {
			$sBegin = "<!--BEGIN:LISTER:inline:" . $this->_getElementHtmlId() . "-->";
			$sEnd = "<!--END:LISTER:inline:" . $this->_getElementHtmlId() . "-->";
			return $sBegin . $sHtml . $sEnd;
		} elseif($this->bDefaultTemplate === TRUE) {
			return "<div id=\"" . $this->_getElementHtmlId() . "\" class=\"ameosformidable-rdtlister-defaultwrap\"" . $sAddParams . ">" . $sHtml . "</div>";
		} elseif($this->bNoTemplate === TRUE) {
			return $sHtml;
		} else {
			return "<div id=\"" . $this->_getElementHtmlId() . "\"" . $sAddParams . ">" . $sHtml . "</div>";
		}
	}

	function isInline() {
		return $this->_navConf("/mode") === "inline";
	}

	function _initDataStream() {

		if($this->oDataStream === FALSE) {

			if(($sDsToUse = $this->_navConf("/searchform/use")) === FALSE) {
				if(($sDsToUse = $this->_navConf("/datasource/use")) === FALSE) {
					$this->oForm->mayday("RENDERLET LISTER <b>" . $this->_getName() . "</b> - requires <b>/datasource/use</b> OR <b>/searchform/use</b> to be properly set. Check your XML conf.");
				} else {
					if(!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
						$this->oForm->mayday("RENDERLET LISTER <b>" . $this->_getName() . "</b> - refers to undefined datasource '" . $sDsToUse . "'. Check your XML conf.");
					} else {
						$this->oDataStream =& $this->oForm->aODataSources[$sDsToUse];
						$this->sDsType = "datasource";
					}
				}
			} else {
				if(tx_ameosformidable::isRunneable($sDsToUse)) {
					$sDsToUse = $this->callRunneable($sDsToUse);
				}

				$oRdt = $this->oForm->rdt($sDsToUse);

				if($oRdt === FALSE) {
					$this->oForm->mayday("RENDERLET LISTER - refers to undefined searchform '" . $sDsToUse . "'. Check your XML conf.");
				} elseif(($sDsType = $oRdt->_getType()) !== "SEARCHFORM" && ($sDsType = $oRdt->_getType()) !== "ADVSEARCHFORM") {
					$this->oForm->mayday("RENDERLET LISTER - defined searchform <b>'" . $sDsToUse . "'</b> is not of <b>SEARCHFORM</b> type, but of <b>" . $sDsType . "</b> type");
				} else {
					$this->oDataStream =& $this->oForm->aORenderlets[$oRdt->getAbsName()];
					$this->sDsType = "searchform";
					if($this->oDataStream->shouldUpdateCriterias()) {
						$this->bResetPager = TRUE;
					}
				}
			}
		}
	}

	function _initLimitAndSort() {

		if($this->aLimitAndSort === FALSE || $this->getIteratingAncestor() !== FALSE) {	// if iterating ancestor, lister in lister !
			$iCurPage = $this->_getPage();
			if($this->iTempPage !== FALSE) {
				$iCurPage = $this->iTempPage;
				$this->iTempPage = FALSE;
			}

			$iRowsPerPage = 5;	// default value

			if(($mRowsPerPage = $this->_navConf("/pager/rows/perpage")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mRowsPerPage)) {
					$mRowsPerPage = $this->callRunneable($mRowsPerPage);
				}

				if(intval($mRowsPerPage) > 0) {
					$iRowsPerPage = $mRowsPerPage;
				} elseif(intval($mRowsPerPage) === -1) {
					$iRowsPerPage = 1000000;
				}
			}

			$aSort = $this->_getSortColAndDirection();

			if(trim($aSort["col"]) !== "" && array_key_exists($aSort["col"], $this->aOColumns)) {
				if(($sRealSortCol = $this->aOColumns[$aSort["col"]]->_navConf("/sortcol")) === FALSE) {
					$sRealSortCol = $aSort["col"];
				}
			} else {
				$sRealSortCol = $aSort["col"];
			}

			$this->aLimitAndSort = array(
				"curpage" => $iCurPage,
				"rowsperpage" => $iRowsPerPage,
				"limitoffset" => ($iCurPage - 1) * $iRowsPerPage,
				"limitdisplayed" => $iRowsPerPage,
				"sortby" => $sRealSortCol,
				"sortdir" => $aSort["dir"],
			);
		}
	}

	function getPageForLineNumber($iNum) {

		if(intval($this->aLimitAndSort["rowsperpage"]) !== 0) {
			$iPageMax = (ceil($iNum / $this->aLimitAndSort["rowsperpage"]));
		} else {
			$iPageMax = 0;
		}

		return $iPageMax;
	}

	function shouldAvoidPageOneInUrl() {
		return $this->defaultTrue("/pager/avoidpageoneinurl");
	}

	function _initPager($iNumRows) {

		$iPageMax = $this->getPageForLineNumber($iNumRows);

		if($iPageMax > 1 || $this->defaultFalse("/pager/alwaysdisplay")) {
			$bDisplay = TRUE;
		} else {
			$bDisplay = FALSE;
		}

		// generating javascript links & functions

		$sLinkFirst = $sLinkPrev = $sLinkNext = $sLinkLast = "";

		if($iPageMax >= 1) {

			if($this->aLimitAndSort["curpage"] > 1) {

				if($this->shouldAvoidPageOneInUrl()) {
					$sLinkFirst = $this->_buildLink(array(), array("page" => 1));
				} else {
					$sLinkFirst = $this->_buildLink(array("page" => 1));
				}

				if($this->aLimitAndSort["curpage"] > 2) {
					$sLinkPrev = $this->_buildLink(array(
						"page" => $this->aLimitAndSort["curpage"] - 1
					));
				} else {
					$sLinkPrev = $sLinkFirst;
				}
			}

			// print 'next' link only if we're not
			// on the last page

			if($this->aLimitAndSort["curpage"] < $iPageMax) {

				$sLinkNext = $this->_buildLink(array(
					"page" => ($this->aLimitAndSort["curpage"] + 1)
				));

				$sLinkLast = $this->_buildLink(array(
					"page" => $iPageMax
				));
			}
		}

		$iPage = ($iPageMax == 0) ? 0 : $this->aLimitAndSort["curpage"];
		$bAlwaysFullWidth = FALSE;

		$aWindow = array();

		if(($mWindow = $this->_navConf("/pager/window")) !== FALSE && $iNumRows > 0) {

			if(tx_ameosformidable::isRunneable($mWindow)) {
				$iWindowWidth = $this->callRunneable($mWindow);
			} elseif(is_array($mWindow) && (($mWidth = $this->_navConf("/pager/window/width")) !== FALSE)) {
				if(tx_ameosformidable::isRunneable($mWidth)) {
					$mWidth = $this->callRunneable($mWidth);
				}

				$iWindowWidth = intval($mWidth);

				if(($mAlwaysFullWidth = $this->defaultFalse("/pager/window/alwaysfullwidth")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($mAlwaysFullWidth)) {
						$mAlwaysFullWidth = $this->callRunneable($mAlwaysFullWidth);
					}

					$bAlwaysFullWidth = $mAlwaysFullWidth;
				}
			} else {
				$iWindowWidth = $mWindow;
			}

			if($iWindowWidth !== FALSE) {

				// generating something like < 24 25 *26* 27 28 >

				/*
					window pager patch by Manuel Rego Casasnovas
					@see http://lists.netfielders.de/pipermail/typo3-project-formidable/2008-January/000816.html
				*/


				$iStart = $iPage - ($iWindowWidth - 1);
				if($iStart < 1) {
					$iStart = 1;
				}

				if($iStart == 1) {
					$sLinkFirst = "";
				}

				#$iEnd = $iPage + 1;
				#$iEnd = $iPage + ($iWindowWidth - 2);
				$iEnd = $iPage + ($iWindowWidth - 1);

				if($iEnd > $iPageMax) {
					$iEnd = $iPageMax;
				}

				if($iEnd == $iPageMax) {
					$sLinkLast = "";
				}

				if($bAlwaysFullWidth && (($iPageMax + 1) < $iWindowWidth)) {
					$iEnd = $iWindowWidth;
				}

				for($k = $iStart; $k <= $iEnd; $k++) {
					if($k <= $iPageMax) {
						$aWindow[$k] = $this->_buildLink(array(
							"page" => $k
						));
					} /*else {
						$aWindow[$k] = FALSE;
					}*/
				}
			}
		}

		if(sizeof($aWindow) === 1) {
			$aWindow = array();
		}


		$this->aPager = array(
			"display"	=>	$bDisplay,
			"numrows"	=>	$iNumRows,
			"offset"	=>	$this->aLimitAndSort["limitoffset"],
			"page"		=>	$iPage,
			"pagemax"	=>	$iPageMax,
			"rowsperpage" => $this->aLimitAndSort["rowsperpage"],
			"links"		=>	array(
				"first"		=>	$sLinkFirst,
				"prev"		=>	$sLinkPrev,
				"next"		=>	$sLinkNext,
				"last"		=>	$sLinkLast,
			),
			"window"	=>	$aWindow,
			"alwaysfullwidth" => $bAlwaysFullWidth,
		);
	}

	function _buildLink($aParams, $aExcludeParams = array()) {

		$aRdtParams = array(
			$this->oForm->formid => array(
				$this->_getElementHtmlId() => $aParams
			)
		);

		$sEnvMode = $this->oForm->__getEnvExecMode();
		if($sEnvMode === "BE") {
			$sRequestUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("TYPO3_REQUEST_URL");
			$aQueryParts = parse_url($sRequestUrl);

			$aParams = array();
			if($aQueryParts['query']) {
				parse_str($aQueryParts['query'], $aParams);
			}

			$aBaseParams = $aParams;
			if(array_key_exists($this->oForm->formid, $aBaseParams)) {
				unset($aBaseParams[$this->oForm->formid]);
			}

			$sBaseUrl = $aQueryParts["scheme"] . "://" . $aQueryParts["host"] . $aQueryParts["path"];
			if(!empty($aBaseParams)) {
				$sBaseUrl .= "?" . substr(\TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl("", $aBaseParams), 1);
			}

		} elseif($sEnvMode === "EID") {
			$sBaseUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("HTTP_REFERER");
			$aQueryParts = parse_url($sBaseUrl);
			$aParams = array();
			if($aQueryParts['query']) {
				parse_str($aQueryParts['query'], $aParams);
			}
		} elseif($sEnvMode === "FE") {
			$aParams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
		}

        $aFullParams = $aParams;
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
			$aFullParams,
			$aRdtParams
		);


		if(!empty($aExcludeParams) || !empty($this->oForm->aParamsToRemove)) {

			$aRdtParamsExclude = array(
				$this->oForm->formid => array(
					$this->_getElementHtmlId() => $aExcludeParams
				)
			);


			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
				$aRdtParamsExclude,
				$this->oForm->aParamsToRemove
			);
				// excluding also params that have been marked as "please remove"
				// like what's done for the form action when setting get-params
				// to alter the search
			#debug($aRdtParamsExclude, "exclude!!!");

			$aPathes = $this->oForm->implodePathesForArray($aRdtParamsExclude);
			reset($aPathes);
			while(list(, $sPath) = each($aPathes)) {
				$this->oForm->unsetDeepData(
					$sPath,
					$aFullParams
				);
			}
		}

		if(is_array($aFullParams) && array_key_exists("cHash", $aFullParams)) {
			unset($aFullParams["cHash"]);
		}

		if($this->oForm->defaultFalse("/cachehash", $this->aElement) === TRUE) {
            $cacheHash = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');
			$aFullParams["cHash"] = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(
				serialize($cacheHash->calculateCacheHash($aFullParams))
			);
		}

		if($sEnvMode === "BE" || $sEnvMode === "EID") {
			return $this->oForm->xhtmlUrl(
				\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl(
					$sBaseUrl,
					$aFullParams
				)
			);
		} elseif($sEnvMode === "FE") {
            if (!is_array($aFullParams)) {
                $aFullParams = array();
            }
			if(is_array($aFullParams) && array_key_exists("id", $aFullParams)) {
				unset($aFullParams["id"]);
			}

			return $this->oForm->cObj->typolink_URL(array(
				"parameter" => $GLOBALS["TSFE"]->id,
				"additionalParams" => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl(
					"",
					$aFullParams
				)
			));
		}
	}

	function &_fetchData($aConfig = FALSE) {
		if($aConfig === FALSE) {
			$this->_initDataStream();
			$this->_initColumns();
			$this->aLimitAndSort = FALSE;
			$this->_initLimitAndSort();

			return $this->_fetchData(
				$aConfig = array(
					"page" => ($this->aLimitAndSort["curpage"] - 1),
					"perpage" => $this->aLimitAndSort["rowsperpage"],
					"sortcolumn" => $this->aLimitAndSort["sortby"],
					"sortdirection" => $this->aLimitAndSort["sortdir"],
				)
			);
		} else {
			return $this->oDataStream->_fetchData($aConfig);
		}
	}

	function &_renderList(&$aRows) {

		$aTemplate = $this->_getTemplate();

		$this->_renderList_displayRows($aTemplate, $aRows);
		$this->_renderList_displayPager($aTemplate);
		$this->_renderList_displaySortHeaders($aTemplate);
		$this->_renderList_displaySelectall($aTemplate);
		$this->_renderList_displayActions($aTemplate);

		reset($this->aOColumns);
		while(list($sColumn, ) = each($this->aOColumns)) {
			$aTemplate["html"] = str_replace(
				"{" . $sColumn . ".label}",
				$this->getListHeader($sColumn),
				$aTemplate["html"]
			);
		}

		$aTemplate["html"] = $this->oForm->_substLLLInHtml($aTemplate["html"]);

		// including styles and CSS files

		if($aTemplate["styles"] !== "") {

			if($this->bDefaultTemplate === TRUE) {
				$sComment = "Stylesheet of DEFAULT TEMPLATE for renderlet:LISTER " . $this->_getName();
				$sKey = "tx_ameosformidable_renderletlister_defaultstyle";
			} else {
				$sComment = "Dynamic stylesheet for renderlet:LISTER " . $this->_getName();
				$sKey = "tx_ameosformidable_renderletlister_dynamicstyle_" . $this->_getName();
			}

			$this->oForm->additionalHeaderData(
				$this->oForm->inline2TempFile($aTemplate["styles"], "css", $sComment),
				$sKey
			);
		}

		if($aTemplate["cssfile"] !== "") {
			$this->oForm->additionalHeaderDataLocalStylesheet(
				$this->oForm->toServerPath($aTemplate["cssfile"]),
				"tx_ameosformidable_renderletlister_cssfile_" . $this->_getName()
			);
		}

		return $aTemplate["html"];
	}

	function getListHeader($sColumn) {

		if(($sLabel = $this->aOColumns[$sColumn]->_navConf("/listheader")) === FALSE) {

			$sAutoMap = "LLL:" . $this->aOColumns[$sColumn]->getAbsName() . ".listheader";
			if($this->oForm->sDefaultLLLPrefix !== FALSE && (($sAutoLabel = $this->oForm->_getLLLabel($sAutoMap)) !== "")) {
				return $sAutoLabel;
			}

			if(($sLabel = $this->aOColumns[$sColumn]->getLabel()) === "") {
				return "";
			}
		}

		if(tx_ameosformidable::isRunneable($sLabel)) {
			$sLabel = $this->oForm->callRunneable($sLabel);
		}

		return $this->oForm->_getLLLabel($sLabel);
	}

	function _renderList_displayRows(&$aTemplate, &$aRows) {

		$aRowsHtml = array();
		if($this->bNoTemplate !== TRUE) {
			$aAltRows = array();
			$aRowsHtml = array();
			$sRowsPart = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($aTemplate["html"], "###ROWS###");

			if($aTemplate["default"] === TRUE) {
				$sAltList = "###ROW1###, ###ROW2###";
			}elseif(($sAltRows = $this->_navConf("/template/alternaterows")) !== FALSE && tx_ameosformidable::isRunneable($sAltRows)) {
				$sAltList = $this->callRunneable($sAltRows);
			}elseif(($sAltList = $this->_navConf("/template/alternaterows")) === FALSE ){
				$this->oForm->mayday("RENDERLET LISTER <b>" . $this->_getName() . "</b> requires /template/alternaterows to be properly set. Please check your XML configuration");
			}

			$aAltList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $sAltList);
			if(sizeof($aAltList) > 0) {
				reset($aAltList);
				while(list(, $sAltSubpart) = each($aAltList)) {
					$aAltRows[] = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($sRowsPart, $sAltSubpart);
				}

				$iNbAlt = sizeOf($aAltRows);
			}
		}

		$aColKeys = array_keys($this->aOColumns);
		reset($aColKeys);
		while(list(,$sName) = each($aColKeys)) {
			$this->aOColumns[$sName]->doBeforeListRender($this);
		}

		$iRowNum = 0;
		$this->iCurRowNum = 0;

		$aTableCols = FALSE;
		if(is_array($aRows)) {
			reset($aRows);
			if(is_array($aRows["results"])) {
				while(list($iIndex, ) = each($aRows["results"])) {
					$this->iCurRowNum = $iRowNum;
					$aCurRow = $aRows["results"][$iIndex];
					$iRowUid = $aCurRow["uid"];

					if(!is_array($aCurRow)) {
						$aCurRow = array();
					}

					if($aTableCols === FALSE) {
						$aTableCols = array_keys($aCurRow);
						reset($aTableCols);
					}

					$this->__aCurRow = $aCurRow;
					array_push($this->oForm->oDataHandler->__aListData, $aCurRow);
					$aCurRow = $this->processBeforeRender($aCurRow);
					$aCurRow = $this->_refineRow($aCurRow);
					$aCurRow = $this->processBeforeDisplay($aCurRow);

					$this->__aCurRow = array();

					$aCurRow = $this->filterUnprocessedColumns($aCurRow, $aTableCols);
					$this->addRow($aCurRow['uid'], $aCurRow);

					if($this->bNoTemplate === TRUE) {
						reset($this->aOColumns);
						while(list($sCol,) = each($this->aOColumns)) {
							$sRowHtml = $aCurRow[$sCol]["__compiled"];
						}
					} else {
						if($this->mCurrentSelected !== FALSE && $iRowUid == $this->mCurrentSelected) {
							$aCurRow["rowclass"] = "row-selected";
						} else {
							$aCurRow["rowclass"] = "row-unselected ";
						}

						$aCurRow["row"] = $aRows["results"][$iIndex];

						$sRowHtml = $this->oForm->_parseTemplateCode(
							$aAltRows[$iRowNum % $iNbAlt],		// current alternate subpart for row
							$aCurRow
						);
					}

					$aRowsHtml[] = $this->rowWrap($sRowHtml);
					array_pop($this->oForm->oDataHandler->__aListData);

					$iRowNum++;
				}
			}
		}

		$this->iCurRowNum = FALSE;

		$aColKeys = array_keys($this->aOColumns);
		reset($aColKeys);
		while(list(,$sName) = each($aColKeys)) {
			$this->aOColumns[$sName]->doAfterListRender($this);
		}

		if($this->bNoTemplate === FALSE) {
			if($this->defaultTrue("/template/allowincompletesequence") === FALSE) {
				$iNbResultsOnThisPage = count($aRows["results"]);
				if(($iNbResultsOnThisPage % $iNbAlt) !== 0) {
					for($k = $iRowNum % $iNbAlt; $k < $iNbAlt; $k++) {

						$aRowsHtml[] = $this->oForm->_parseTemplateCode(
							$aAltRows[$k],		// current alternate subpart for row
							array(),
							array(),
							FALSE
						);
					}
				}
			}

			$aTemplate["html"] = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart(
				$aTemplate["html"],
				"###ROWS###",
				implode("", $aRowsHtml),
				FALSE,
				FALSE
			);
		} else {
			$aTemplate["html"] = implode($aRowsHtml);
		}
	}

	function rowWrap($sHtmlRow) {
		if(($sWrap = $this->_navConf("/columns/wrap")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($sWrap)) {
				$sWrap = $this->callRunneable($sWrap);
			}

			if(is_string($sWrap)) {
				return str_replace("|", $sHtmlRow, $this->oForm->_substLLLInHtml($sWrap));
			}
		}

		return $sHtmlRow;
	}

	function processBeforeRender($aRow) {

		if(($aBeforeRender = $this->_navConf("/beforerender")) !== FALSE && tx_ameosformidable::isRunneable($aBeforeRender)) {
			$aRow = $this->callRunneable($aBeforeRender, $aRow);
		}

		if($this->shouldClientify()) {
			$this->aClientified[$aRow["uid"]] = array();
			reset($this->aClientifyColumns);
			while(list(, $sCol) = each($this->aClientifyColumns)) {
				if(array_key_exists($sCol, $aRow)) {
					$this->aClientified[$aRow["uid"]][$sCol] = $aRow[$sCol];
				}
			}
		}

		return $aRow;
	}

	function processBeforeDisplay($aRow) {

		if(($aBeforeDisplay = $this->_navConf("/beforedisplay")) !== FALSE && tx_ameosformidable::isRunneable($aBeforeDisplay)) {
			$aRow = $this->callRunneable($aBeforeDisplay, $aRow);
		}

		return $aRow;
	}

	function shouldClientify() {
		return !empty($this->aClientifyColumns);
	}

	function filterUnprocessedColumns($aRow, $aDataSetCols) {
		if(!is_array($aRow)) {
			$aRow = array();
		}

		if(!is_array($aDataSetCols)) {
			$aDataSetCols = array();
		}

		reset($aRow);
		while(list($sKey,) = each($aRow)) {
			if($sKey !== "uid" && !array_key_exists($sKey, $this->aOColumns) && in_array($sKey, $aDataSetCols)) {
				unset($aRow[$sKey]);
			}
		}

		reset($aRow);
		return $aRow;
	}

	function _renderList_displayPager(&$aTemplate) {

		$sHtmlId = $this->_getElementHtmlId();

		if(($mHtml = $this->_navConf("/pager/html")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($mHtml)) {
				$mHtml = $this->callRunneable($mHtml, $this->aPager);
			}

			$sPager = $mHtml;

		} elseif($this->aPager["display"] === TRUE) {
			$sPager = $aTemplate["pager"];
			$aLinks = array();

			$sPager = $this->oForm->_parseThrustedTemplateCode(
				$sPager,
				array(
					"page" => $this->aPager["page"],
					"pagemax" => $this->aPager["pagemax"]
				),
				array(),
				FALSE
			);


			reset($this->aPager["links"]);
			while(list($sWhich, $sLink) = each($this->aPager["links"])) {

				if($sLink !== "") {
					$aLinks[$sWhich] = $this->oForm->_parseTemplateCode(
						\Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($sPager, "###LINK" . strtoupper($sWhich) . "###"),
						array(
							"link" => $sLink,
							"linkid" => $sHtmlId . "_pagelink_" . strtolower($sWhich)
						),
						array(),
						FALSE
					);
				} else {
					$aLinks[$sWhich] = "";
				}
			}

			$sPager = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($sPager, "###LINKFIRST###",$aLinks["first"], FALSE, FALSE);
			$sPager = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($sPager, "###LINKPREV###",	$aLinks["prev"], FALSE, FALSE);
			$sPager = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($sPager, "###LINKNEXT###",	$aLinks["next"], FALSE, FALSE);
			$sPager = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($sPager, "###LINKLAST###",	$aLinks["last"], FALSE, FALSE);

			// generating window
			$sWindow = "";
			if(!empty($this->aPager["window"])) {

				#$sWindow = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($sPager, "###WINDOW###");
				$sWindow = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($aTemplate["pager"], "###WINDOW###");
				$sLinkNo = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($sWindow, "###NORMAL###");
				$sLinkAct = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($sWindow, "###ACTIVE###");
				$sMoreBefore = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($sWindow, "###MORE_BEFORE###");
				$sMoreAfter = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($sWindow, "###MORE_AFTER###");

				if($this->aPager["alwaysfullwidth"] === TRUE) {
					if(trim(($sLinkDisabled = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($sWindow, "###DISABLED###"))) === "") {
						$this->oForm->mayday(
							"RENDERLET " . $this->_getType() . " <b>" . $this->_getName() . "</b> - In your pager's template, you have to provide a <b>###DISABLED###</b> subpart inside the <b>###WINDOW###</b> subpart when defining <b>/window/alwaysFullWidth=TRUE</b>"
						);
					}
				}

				$aLinks = array();

				reset($this->aPager["window"]);
				if(key($this->aPager["window"]) > 2 && trim($sMoreBefore) !== "") {
					$aLinks[] = $sMoreBefore;
				}

				reset($this->aPager["window"]);
				while(list($iPageNum, $sLink) = each($this->aPager["window"])) {

					if($sLink === FALSE) {
						$aLinks[] = $this->oForm->_parseThrustedTemplateCode(
							$sLinkAct,
							array(
								"link" => $sLink,
								"linkid" => $sHtmlId . "_pagelink_window_" . $iPageNum,
								"page" => $iPageNum,
							)
						);
					} elseif($this->aPager["page"] == $iPageNum) {
						$aLinks[] = $this->oForm->_parseThrustedTemplateCode(
							$sLinkAct,
							array(
								"link" => $sLink,
								"linkid" => $sHtmlId . "_pagelink_window_" . $iPageNum,
								"page" => $iPageNum,
							)
						);
					} else {
						$aLinks[] = $this->oForm->_parseThrustedTemplateCode(
							$sLinkNo,
							array(
								"link" => $sLink,
								"linkid" => $sHtmlId . "_pagelink_window_" . $iPageNum,
								"page" => $iPageNum,
							)
						);
					}
				}

				end($this->aPager["window"]);
				if((key($this->aPager["window"]) < ($this->aPager["pagemax"] - 1)) && trim($sMoreAfter) !== "") {
					$aLinks[] = $sMoreAfter;
 				}

				$sLinks = implode(" ", $aLinks);

				$sWindow = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($sWindow, "###WINDOWLINKS###", $sLinks, FALSE, FALSE);
			}

			$sPager = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($sPager, "###WINDOW###", $sWindow, FALSE, FALSE);
		} else {
			$sPager = "";
		}

		$aTemplate["html"] = $this->oForm->_parseThrustedTemplateCode(
			$aTemplate["html"],
			array(
				"PAGER" => $sPager
			),
			array(),
			FALSE
		);
	}

	function _renderList_displaySelectall(&$aTemplate) {
		if($this->isEditableLister()) {
			$sCheck = ($this->bAllRowsSelected === TRUE) ? 'checked="checked"' : '';
			$sSelectall = '<input type="checkbox" class="formidable-selectall" id="selectall-' . $this->_getElementHtmlId() . '" name="' . $this->_getElementHtmlName() . '[selectall]" id="' . $this->_getElementHtmlId() . '.selectall" value="1" ' . $sCheck . '/>';
			$sSelectall.= '<div class="selectall" id="selectallwrap-' . $this->_getElementHtmlId() . '" style="display:none;">
				<a href="javascript:void(0);" id="thispage-' . $this->_getElementHtmlId() . '">' . $this->oForm->_getLLLabel('LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:selection.thispage') . '</a><br />
				<a href="javascript:void(0);" id="allpages-' . $this->_getElementHtmlId() . '">' . $this->oForm->_getLLLabel('LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:selection.allpages') . '</a>
			</div>';
		} else {
			$sSelectall = '';
		}

		$aTemplate["html"] = $this->oForm->_parseThrustedTemplateCode(
			$aTemplate["html"],
			array(
				"SELECTALL" => $sSelectall
			),
			array(),
			FALSE
		);
	}

	function _renderList_displaySortHeaders(&$aTemplate) {

		$sListHtmlId = $this->_getElementHtmlId();

		reset($this->aOColumns);
		while(list($sColumn, ) = each($this->aOColumns)) {

			$sSubpart = "###SORT_" . $sColumn . "###";

			if(($sSortHtml = trim(\Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($aTemplate["html"], $sSubpart))) != "") {

				$sSortHtml = $this->oForm->_substLLLInHtml($sSortHtml);

				if($this->aOColumns[$sColumn]->defaultTrue("/sort") === TRUE) {

					$sNewDir = "asc";
					$sLabelDir = "";
					$sCssClass = "sort-no";
					$sSortSymbol = "";

					if(($this->aLimitAndSort["sortby"] === $sColumn)) {

						if(strtolower($this->aLimitAndSort["sortdir"]) === "desc") {

							$sNewDir = "asc";
							$sLabelDir = (($this->aTemplate["default"] === TRUE) ? " [Z-a]" : "");
							$sCssClass = "sort-act-desc";
							$sSortSymbol = "&#x25BC;";

						} else {

							$sNewDir = "desc";
							$sLabelDir = (($this->aTemplate["default"] === TRUE) ? " [a-Z]" : "");
							$sCssClass = "sort-act-asc";
							$sSortSymbol = "&#x25B2;";
						}
					}

					$sLink = $this->_buildLink(array(
						"sort" => $sColumn . "-" . $sNewDir
					));

					if(($sHeader = $this->getListHeader($sColumn)) !== "") {
						$sAccesTitle = "{LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sortby} &quot;" . strip_tags($sHeader) . "&quot; {LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sort." . $sNewDir . "}";
					} else {
						$sAccesTitle = "{LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sort} {LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sort." . $sNewDir . "}";
					}

					if(($this->defaultFalse("pager/sort/useunicodegeometricshapes")) == FALSE) {
						$sSortSymbol = "";
					}

					#$sAccesTitle = "{LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sortby} &quot;" . $sHeader . "&quot; {LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sort." . $sNewDir . "}";
					$sTag = "<a id=\"" . $sListHtmlId . "_sortlink_" . $sColumn . "\" href=\"" . $sLink . "\" title=\"" . $sAccesTitle . "\" class=\"" . $sColumn . "_sort " . $sCssClass . "\">" . $sSortHtml . $sLabelDir . $sSortSymbol . "</a>";
				} else {
					$sTag = $sSortHtml;
				}

				$aTemplate["html"] = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart(
					$aTemplate["html"],
					$sSubpart,
					$sTag,
					FALSE,
					FALSE
				);
			}
		}
	}

	function _renderList_displayActions(&$aTemplate) {
		$sAction = is_array($this->aActionListbox) ? $this->aActionListbox['__compiled'] : $this->aActionListbox;
		$aTemplate["html"] = $this->oForm->_parseThrustedTemplateCode(
			$aTemplate["html"],
			array(
				"ACTIONS" => $sAction
			),
			array(),
			FALSE
		);
	}

	function &_getTemplate() {

		$aRes = array(
			"default" => FALSE,
			"html" => "",
			"styles" => "",
			"cssfile" => "",
			"pager" => "",
		);

		if((($aTemplate = $this->_navConf("/template")) === FALSE) || (($this->bNoTemplate = $this->defaultFalse("/template/notemplate")) === TRUE)) {

			if($this->bNoTemplate === FALSE) {
				// no template defined, building default lister template
				$aRes = $this->__buildDefaultTemplate();
				$this->bDefaultTemplate = TRUE;
				$this->bNoTemplate = FALSE;
			} else {
				$aRes = array(
					"default" => FALSE,
				);

				$this->bDefaultTemplate = FALSE;
				$this->bNoTemplate = TRUE;
			}
		} else {

			if(is_array($aTemplate) && array_key_exists("path", $aTemplate)) {
				if(tx_ameosformidable::isRunneable($aTemplate["path"])) {
					$aTemplate["path"] = $this->callRunneable($aTemplate["path"]);
				}
			} else {
				$this->oForm->mayday("RENDERLET LISTER <b>" . $this->_getName() . "</b> - Template defined, but <b>/template/path</b> is missing. Please check your XML configuration");
			}

			if($aTemplate["path"]{0} === 'T' && substr($aTemplate["path"], 0, 3) === 'TS:') {
				$sTsPointer = $sString;
				if(($aTemplate["path"] = $this->oForm->getTS($aTemplate["path"], TRUE)) === AMEOSFORMIDABLE_TS_FAILED) {	$this->oForm->mayday("The typoscript pointer <b>" . $sTsPointer . "</b> evaluation has failed, as the pointed property does not exists within the current Typoscript template");
				}
			}

			if(is_array($aTemplate) && array_key_exists("subpart", $aTemplate)) {
				if(tx_ameosformidable::isRunneable($aTemplate["subpart"])) {
					$aTemplate["subpart"] = $this->callRunneable($aTemplate["subpart"]);
				}
			} else {
				$this->oForm->mayday("RENDERLET LISTER <b>" . $this->_getName() . "</b> - Template defined, but <b>/template/subpart</b> is missing. Please check your XML configuration");
			}

			$aTemplate["path"] = $this->oForm->toServerPath($aTemplate["path"]);


			if(file_exists($aTemplate["path"])) {
				if(is_readable($aTemplate["path"])) {
					$aRes["html"] = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart(
						\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($aTemplate["path"]),
						$aTemplate["subpart"]
					);

					if(trim($aRes["html"]) === "") {
						$this->_autoTemplateMayday($aTemplate, TRUE);
					}
				} else {
					$this->oForm->mayday("RENDERLET LISTER <b>" . $this->_getName() . "</b> - the given template file '<b>" . $aTemplate["path"] . "</b>' isn't readable. Please check permissions for this file.");
				}
			} else {
				$this->_autoTemplateMayday($aTemplate);
			}

			/* managing styles and CSS file */

			if(array_key_exists("cssfile", $aTemplate)) {
				if(tx_ameosformidable::isRunneable($aTemplate["cssfile"])) {
					$aTemplate["cssfile"] = $this->callRunneable($aTemplate["cssfile"]);
				}

				$aRes["cssfile"] = $this->oForm->toWebPath($aTemplate["cssfile"]);
			}

			/* styles after css-file to eventually override css-file directives */
			if(array_key_exists("styles", $aTemplate)) {
				if(tx_ameosformidable::isRunneable($aTemplate["styles"])) {
					$aTemplate["styles"] = $this->callRunneable($aTemplate["styles"]);
				}

				$aRes["styles"] = $aTemplate["styles"];
			}


			/* get pager */

			if(($aPagerTemplate = $this->_navConf("/pager/template")) !== FALSE) {

				if(is_array($aPagerTemplate) && array_key_exists("path", $aPagerTemplate)) {
					if(tx_ameosformidable::isRunneable($aPagerTemplate["path"])) {
						$aPagerTemplate["path"] = $this->callRunneable($aPagerTemplate["path"]);
					}
				} else {
					$this->oForm->mayday("RENDERLET LISTER <b>" . $this->_getName() . "</b> - Template for PAGER is defined, but <b>/pager/template/path</b> is missing. Please check your XML configuration");
				}

				if($aPagerTemplate["path"]{0} === 'T' && substr($aPagerTemplate["path"], 0, 3) === 'TS:') {
					$sTsPointer = $sString;
					if(($aPagerTemplate["path"] = $this->oForm->getTS($aPagerTemplate["path"], TRUE)) === AMEOSFORMIDABLE_TS_FAILED) {	$this->oForm->mayday("The typoscript pointer <b>" . $sTsPointer . "</b> evaluation has failed, as the pointed property does not exists within the current Typoscript template");
					}
				}

				if(is_array($aPagerTemplate) && array_key_exists("subpart", $aPagerTemplate)) {
					if(tx_ameosformidable::isRunneable($aPagerTemplate["subpart"])) {
						$aPagerTemplate["subpart"] = $this->callRunneable($aPagerTemplate["subpart"]);
					}
				} else {
					$this->oForm->mayday("RENDERLET LISTER <b>" . $this->_getName() . "</b> - Template for PAGER defined, but <b>/pager/template/subpart</b> is missing. Please check your XML configuration");
				}

				$aPagerTemplate["path"] = $this->oForm->toServerPath($aPagerTemplate["path"]);

				if(file_exists($aPagerTemplate["path"])) {
					if(is_readable($aPagerTemplate["path"])) {
						$aRes["pager"] = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart(
							\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($aPagerTemplate["path"]),
							$aPagerTemplate["subpart"]
						);

						if(trim($aRes["pager"]) === "") {
							$this->_autoPagerMayday($aPagerTemplate, TRUE);
						}

					} else {
						$this->oForm->mayday("RENDERLET LISTER <b>" . $this->_getName() . "</b> - the given template file for PAGER '<b>" . $aPagerTemplate["path"] . "</b>' isn't readable. Please check permissions for this file.");
					}
				} else {
					$this->_autoPagerMayday($aPagerTemplate);
				}
			}
		}

		reset($aRes);
		return $aRes;
	}

	function _autoTemplateMayday($aTemplate, $bSubpartError = FALSE) {

		/* ERROR message with automatic generated TEMPLATE and CSS */
		$aDefaultTemplate = $this->__buildDefaultTemplate("#" . str_replace(".", "\.", $this->_getElementHtmlId()));

		$sDefaultTemplate = htmlspecialchars($aDefaultTemplate["html"]);
		$sDefaultStyles = htmlspecialchars($aDefaultTemplate["styles"]);

		$sError = $bSubpartError ?
			"RENDERLET LISTER <b>" . $this->_getName() . "</b> - the given SUBPART '<b>" . $aTemplate["subpart"] . "</b>' doesn't exists."
			: "RENDERLET LISTER <b>" . $this->_getName() . "</b> - the given TEMPLATE FILE '<b>" . $aTemplate["path"] . "</b>' doesn't exists.";

		$sMessage =<<<ERRORMESSAGE

	<div>{$sError}</div>
	<hr />
	<div>If you're going to create this template, these automatically generated html template and styles might be usefull</div>
	<h2>Automatic LIST template</h2>
	<div>Copy/paste this in <b>{$aTemplate["path"]}</b></div>
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4; font-family: Courier;'>
		<br />
<pre>
&lt;!-- {$aTemplate["subpart"]} begin--&gt;

{$sDefaultTemplate}

&lt;!-- {$aTemplate["subpart"]} end--&gt;
</pre>
		<br /><br />
	</div>
	<h2>Automatic CSS</h2>
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4;'><pre>{$sDefaultStyles}</pre></div>

ERRORMESSAGE;

		$this->oForm->mayday($sMessage);
	}

	function _autoPagerMayday($aTemplate, $bSubpartError = FALSE) {

		/* ERROR message for PAGER with automatic generated TEMPLATE */

		$sDefaultPager = htmlspecialchars($this->__getDefaultPager());

		$sError = $bSubpartError ?
			"RENDERLET LISTER <b>" . $this->_getName() . "</b> - the given SUBPART for PAGER '<b>" . $aTemplate["subpart"] . "</b>' doesn't exists."
			: "RENDERLET LISTER <b>" . $this->_getName() . "</b> - the given TEMPLATE FILE for PAGER '<b>" . $aTemplate["path"] . "</b>' doesn't exists.";

		$sMessage =<<<ERRORMESSAGE

	<div>{$sError}</div>
	<hr />
	<div>If you're going to create this template, these automatically generated html template might be usefull</div>
	<h2>Automatic PAGER template</h2>
	<div>Copy/paste this in <b>{$aTemplate["path"]}</b></div>
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4; font-family: Courier;'>
		<br />
<pre>
&lt;!-- {$aTemplate["subpart"]} begin--&gt;
{$sDefaultPager}
&lt;!-- {$aTemplate["subpart"]} end--&gt;
</pre>
<br /><br />
	</div>

ERRORMESSAGE;

		$this->oForm->mayday($sMessage);
	}

	function &__getDefaultPager() {
		$sPath = $this->sExtPath . "res/html/default-template.html";
		$sSubPart = "###LISTPAGER###";

		return \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart(
			\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sPath),
			$sSubPart
		);
	}

	function &__buildDefaultTemplate($sCssPrefix = ".ameosformidable-rdtlister-defaultwrap") {

		$aRes = array(
			"default" => TRUE,
			"html" => "",
			"styles" => "",
			"cssfile" => "",
			"pager" => "",
		);

		$aHtml = array(
			"TOP" => array(),
			"DATA" => array(
				"ROW1" => array(),
				"ROW2" => array(),
			),
		);

		$sPath		= $this->sExtPath . "res/html/default-template.html";
		$sSubpart	= "###LIST###";

		$aRes["html"] = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart(
			\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sPath),
			$sSubpart
		);

		/* including default styles in external CSS */

		$aRes["styles"] = $this->oForm->_parseThrustedTemplateCode(
			\Ameos\AmeosFormidable\Html\HtmlParser::getSubpart(
				\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sPath),
				"###STYLES###"
			),
			array(
				"PREFIX" => $sCssPrefix,
				"EXTPATH" => "/" . $this->sExtRelPath
			),
			array(),
			FALSE
		);

		/*END of CSS */



		$sTopColumn = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($aRes["html"],		"###TOPCOLUMN###");
		$sDataColumn1 = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($aRes["html"],		"###DATACOLUMN1###");
		$sDataColumn2 = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart($aRes["html"],		"###DATACOLUMN2###");

		reset($this->aOColumns);
		$bSelectall = ($this->isEditableLister()) ? TRUE : FALSE;

		while(list($sColName,) = each($this->aOColumns)) {

			if($this->defaultTrue("/columns/listheaders") === TRUE) {

				// building sorting header for this column

				if(($sHeader = $this->getListHeader($sColName)) === FALSE) {
					$sHeader = "{" . $sColName . ".label}";
				}

				if($bSelectall) {
					$sColcontent = '{SELECTALL}';
					$bSelectall = !$bSelectall;
				} else {
					$sColcontent = "<!-- ###SORT_" . $sColName . "### begin-->" . $sHeader . "<!-- ###SORT_" . $sColName . "### end-->";
				}

				$aHtml["TOP"][]	= $this->oForm->_parseThrustedTemplateCode(
					$sTopColumn,
					array(
						"COLNAME"		=> $sColName,
						"COLCONTENT"	=> $sColcontent,
					),
					array(),	// exclude
					FALSE		// bClearNotUsed
				);
			}

			// building data cells for this column
			$aTemp = array(
				"COLNAME"		=> $sColName,
				"COLCONTENT"	=> "{" . $sColName . "}",
			);

			$aHtml["DATA"]["ROW1"][]	= $this->oForm->_parseThrustedTemplateCode(
				$sDataColumn1,
				$aTemp,
				array(),
				FALSE
			);

			$aHtml["DATA"]["ROW2"][]	= $this->oForm->_parseThrustedTemplateCode(
				$sDataColumn2,
				$aTemp,
				array(),
				FALSE
			);
			/*
			$aHtml["DATA"]["ROWACT"][]	= $this->oForm->_parseThrustedTemplateCode(
				$sDataColumnAct,
				$aTemp,
				array(),
				FALSE
			);
			*/
		}

		$aRes["html"] = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($aRes["html"], "###STYLES###", "", FALSE, FALSE);
		$aRes["html"] = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($aRes["html"], "###DATACOLUMN1###", implode("", $aHtml["DATA"]["ROW1"]), FALSE, FALSE);
		$aRes["html"] = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($aRes["html"], "###DATACOLUMN2###", implode("", $aHtml["DATA"]["ROW2"]), FALSE, FALSE);
		$aRes["html"] = \Ameos\AmeosFormidable\Html\HtmlParser::substituteSubpart($aRes["html"], "###TOPCOLUMN###", implode("", $aHtml["TOP"]), FALSE, FALSE);

		$aRes["html"] = $this->oForm->_parseThrustedTemplateCode(
			$aRes["html"],
			array(
				"NBCOLS" => sizeOf($this->aOColumns)
			),
			array(),
			FALSE
		);

		/* RETRIEVING pager */

		$aRes["pager"] = $this->__getDefaultPager();

		return $aRes;
	}




	function initChilds($bReInit = FALSE) {
		$this->_initColumns();
	}

	function _initColumns() {

		if($this->aOColumns === FALSE) {

			if(($aColumns = $this->_navConf("/columns")) !== FALSE && is_array($aColumns)) {
				if($this->isEditableLister()) {
                    $aTempColumns = array('column-selectrow' => array(
                        'type' => 'renderlet:CHECKSINGLE',
                        'label' => '',
                        'class' => 'formidable-selectrow',
                        'name' => 'selectrow',
                        'activelistable' => TRUE,
                        'canhiddencolumn' => 'false',
                        'exportable' => 'false',
                        'searchable' => 'false'
                    ));
					\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
						$aTempColumns,
						$aColumns
					);
                    $aColumns = $aTempColumns;
				}

				$this->aOColumns = array();
				$aColKeys = array_keys($aColumns);
				reset($aColKeys);
				while(list(, $sTagName) = each($aColKeys)) {

					if($this->oForm->defaultTrue("/process", $aColumns[$sTagName])) {

						$aColumns[$sTagName]["type"] = str_replace("renderlet:", "", $aColumns[$sTagName]["type"]);

						if(array_key_exists("name", $aColumns[$sTagName]) && (trim($aColumns[$sTagName]["name"]) != "")) {
							$sName = trim($aColumns[$sTagName]["name"]);
							$bAnonymous = FALSE;
						} else {
							$sName = $this->oForm->_getAnonymousName($aColumns[$sTagName]);
							$this->aElement["columns"][$sTagName]["name"] = $sName;
							$aColumns[$sTagName]["name"] = $sName;
							$bAnonymous = TRUE;
						}

						$oRdt =& $this->oForm->_makeRenderlet(
							$aColumns[$sTagName],
							$this->sXPath . "columns/" . $sTagName . "/",
							$bChilds = TRUE,
							$this,
							$bAnonymous,
							$sNamePrefix
						);

						$sAbsName = $oRdt->getAbsName();
						$sName = $oRdt->getName();

						$this->oForm->aORenderlets[$sAbsName] =& $oRdt;
						unset($oRdt);


						if($this->oForm->defaultTrue("/exportable", $aColumns[$sTagName])) {
							$this->aExportableColumns[$sName] = $sName;
						}

						if($this->oForm->defaultTrue("/searchable", $aColumns[$sTagName])) {
							$this->aSearchableColumns[$sName] = $sName;
						}

						// columns are localy stored without prefixing, of course
						$this->aOColumns[$sName] =& $this->oForm->aORenderlets[$sAbsName];
					}
				}
			} else {
				$this->aOColumns = array();
			}

			$this->aChilds =& $this->aOColumns;
		}
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

		/*$aData =& $this->oForm->oDataHandler->_getListData();
		if(!empty($aData)) {
			$sRes .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $aData["uid"] . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
		}*/

		return $sRes;
	}

	function &_refineRow(&$aRow) {

		$sUid = $aRow["uid"];

		if(is_array($aRow)) {
			$aColKeys = array_keys($this->aOColumns);
			reset($aColKeys);
			while(list(,$sName) = each($aColKeys)) {

				$this->aOColumns[$sName]->doBeforeIteration($this);
				$this->aOColumns[$sName]->_getElementHtmlName(); // magic, don't remove
				if(array_key_exists($sName, $aRow)) {

					$sAbsName = $this->aOColumns[$sName]->getAbsName();

					if(array_key_exists($sAbsName, $this->oForm->aPreRendered)) {
						$aRow[$sAbsName] = $this->oForm->aPreRendered[$sAbsName];
					} else {
						$this->aRdtByRow[$sUid][$sName] = $this->aOColumns[$sName]->_getElementHtmlId();

						if($this->aOColumns[$sName]->_activeListable()) {

							$aRow[$sName] = $this->oForm->oRenderer->processHtmlBag(
								$this->aOColumns[$sName]->renderWithForcedValue($aRow[$sName]),
								$this->aOColumns[$sName]
							);
						} else {
							$aRow[$sName] = $this->oForm->oRenderer->processHtmlBag(
								$this->aOColumns[$sName]->renderReadOnlyWithForcedValue($aRow[$sName]),
								$this->aOColumns[$sName]
							);
						}
					}
				} else {
					// not in the data row
					// probably a virtual column

					// calling _getValue() here, as value has to be evaluated for each row
					$mValue = $this->aOColumns[$sName]->getValue();

					if($this->aOColumns[$sName]->_activeListable()) {

						$this->aRdtByRow[$sUid][$sName] = $this->aOColumns[$sName]->_getElementHtmlId();

						$aRow[$sName] = $this->oForm->oRenderer->processHtmlBag(
							$this->aOColumns[$sName]->renderWithForcedValue($mValue),
							$this->aOColumns[$sName]
						);
					} else {

						$aRow[$sName] = $this->oForm->oRenderer->processHtmlBag(
							$this->aOColumns[$sName]->renderReadOnlyWithForcedValue($mValue),
							$this->aOColumns[$sName]
						);
					}
				}

				$this->aOColumns[$sName]->doAfterIteration();
			}
		} else {
			$aRow = array();
		}

		reset($aRow);
		return $aRow;
	}

	function _getPage() {

		if($this->bResetPager !== TRUE) {
			if($this->aLimitAndSort !== FALSE && $this->getIteratingAncestor() === FALSE) {
				return $this->aLimitAndSort["curpage"];
			} else {

				$aGet = $this->oForm->oDataHandler->_G();
				$sName = $this->_getElementHtmlId();

				if(array_key_exists($sName, $aGet) && array_key_exists("page", $aGet[$sName])) {
					return (($iPage = intval($aGet[$sName]["page"])) >= 1) ? $iPage : 1;
				}
			}
		}

		return 1;
	}

	function _getSortColAndDirection() {

		if($this->aLimitAndSort !== FALSE) {
			$aRes = array(
				"col" => $this->aLimitAndSort["sortby"],
				"dir" => $this->aLimitAndSort["sortdir"],
			);
		} else {

			$aRes = array(
				"col" => "",
				"dir" => "",
			);

			$aGet = $this->oForm->oDataHandler->_G();
			$sName = $this->_getElementHtmlId();

			if(array_key_exists($sName, $aGet) && array_key_exists("sort", $aGet[$sName])) {
				$sSort = $aGet[$sName]["sort"];
				$aSort = explode("-", $sSort);

				if(sizeOf($aSort) == 2) {
					$sCol = $aSort[0];
					if(!array_key_exists($sCol, $this->aOColumns)) {
						$sCol = array_shift(array_keys($this->aOColumns));
					}

					$sDir = $aSort[1];
					if(!in_array($sDir, array("asc", "desc"))) {
						$sDir = "asc";
					}
				}

				$aRes = array(
					"col" => $sCol,
					"dir" => $sDir
				);

				$this->persistSort($aRes);

			} elseif($this->shouldPersistSort() && (($aSort = $this->getPersistedSort()) !== FALSE)) {
				return $aSort;
			} elseif($this->_navConf("/pager/sort") !== FALSE) {

				if(($sSortCol = $this->_navConf("/pager/sort/column")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($sSortCol)) {
						$aRes["col"] = $this->callRunneable($sSortCol);
					} else {
						$aRes["col"] = $sSortCol;
					}
				}

				if(($sSortDir = $this->_navConf("/pager/sort/direction")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($sSortDir)) {
						$aRes["dir"] = $this->callRunneable($sSortDir);
					} else {
						$aRes["dir"] = $sSortDir;
					}
				}
			}
		}

		return $aRes;
	}

	function persistSort($aSort) {

		$sAbsName = $this->getAbsName();
		$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];

		if(!array_key_exists($this->oForm->formid, $aAppData)) {
			$aAppData[$this->oForm->formid] = array();
		}

		if(!array_key_exists($sAbsName, $aAppData[$this->oForm->formid])) {
			$aAppData[$this->oForm->formid][$sAbsName] = array();
		}

		$aAppData[$this->oForm->formid][$sAbsName]["sort"] = $aSort;
	}

	function shouldPersistSort() {
		return $this->defaultFalse("/persistantsort");
	}

	function getPersistedSort() {
		$sAbsName = $this->getAbsName();
		$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];
		if(isset($aAppData[$this->oForm->formid][$sAbsName]["sort"])) {
			return $aAppData[$this->oForm->formid][$sAbsName]["sort"];
		}

		return FALSE;
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function hasChilds() {
		return TRUE;
	}

	function _activeListable() {
		return $this->oForm->defaultTrue("/activelistable/", $this->aElement);
	}

	function _renderOnly() {
		return TRUE;
	}

	function cleanBeforeSession() {

		unset($this->aChilds);
		$this->aChilds = FALSE;

		$aKeys = array_keys($this->aOColumns);
		reset($aKeys);
		while(list(, $sKey) = each($aKeys)) {
			if(is_object($this->aOColumns[$sKey])) {
				$sName = $this->aOColumns[$sKey]->getAbsName();
				unset($this->aOColumns[$sKey]);
				$this->aOColumns[$sKey] = $sName;
#				debug($sName, "laaaaaa");
				$this->oForm->aORenderlets[$sName]->cleanBeforeSession();
			}
		}

		$this->baseCleanBeforeSession();
		unset($this->oForm);
	}

	function awakeInSession(&$oForm) {

		parent::awakeInSession($oForm);

		$aKeys = array_keys($this->aOColumns);

		reset($aKeys);
		while(list(, $sKey) = each($aKeys)) {
			$sName = $this->aOColumns[$sKey];
			if(!is_object($sName)) {
				unset($this->aOColumns[$sKey]);
				$this->aOColumns[$sKey] =& $this->oForm->aORenderlets[$sName];
			}
		}

		$this->aChilds =& $this->aOColumns;
	}

	function getCurRowNum() {
		return $this->iCurRowNum;
	}

	function getCurRow() {
		return $this->__aCurRow;
	}

	function isIterating() {
		return $this->getCurRowNum() !== FALSE;
	}

	function isIterable() {
		return TRUE;
	}

	function isAjaxLister() {
		return $this->defaultFalse("/ajaxlister");
	}

	function isEditableLister() {
		if($this->defaultFalse("/editablelister") === TRUE && $this->isAjaxLister() !== TRUE) {
			$this->oForm->mayday("An editable lister must be an ajax lister (for the moment).");
		}

		return $this->defaultFalse("/editablelister");
	}

	function isFlexgridLister() {
		 return $this->defaultFalse("/flexgridlister");
	}

	function isAdvanceFlexgridLister() {
		return $this->defaultFalse('/flexigridconfiguration/advancegrid');
	}

	function setPage($iPage) {
		if($this->aLimitAndSort === FALSE) {
			$this->iTempPage = $iPage;
		} else {
			if(intval($iPage) === 0) {
				$iPage = 1;
			}
			$this->aLimitAndSort["curpage"] = $iPage;
			$this->aLimitAndSort["limitoffset"] = (($iPage - 1) * intval($this->aLimitAndSort["rowsperpage"]));
		}
	}

	function majixRepaintPage($iPage) {
		$this->setPage($iPage);
		return $this->majixRepaint();
	}

	function generateFlexigridJson() {
		$aJson = array();

		if(($sTitle = $this->_navConf('/flexigridconfiguration/title')) !== FALSE) {
			$aJson[] = 'title : "' . $this->oForm->getLLLabel($sTitle) . '"';
		}

		if(($sWidth = $this->_navConf('/flexigridconfiguration/width')) !== FALSE) {
			$aJson[] = 'width : "' . $sWidth . '"';
		}

		if(($sHeight = $this->_navConf('/flexigridconfiguration/height')) !== FALSE) {
			$aJson[] = 'height : "' . $sHeight . '"';
		}

		if($this->defaultFalse('/flexigridconfiguration/showtabletogglebtn')) {
			$aJson[] = 'showTableToggleBtn : true';
		}

		if($this->defaultTrue('/flexigridconfiguration/striped') === FALSE) {
			$aJson[] = 'striped : false';
		}

		if($this->defaultTrue('/flexigridconfiguration/userp') === FALSE) {
			$aJson[] = 'useRp : false';
		}

		if($this->defaultTrue('/flexigridconfiguration/resizable') === FALSE) {
			$aJson[] = 'resizable : false';
		}

		if($this->aLimitAndSort["rowsperpage"] > 0) {
			$aJson[] = 'rp : ' . $this->aLimitAndSort["rowsperpage"] . '';
		}

		if(($sRpoptions = $this->_navConf('/flexigridconfiguration/rpoptions')) !== FALSE) {
			$aJson[] = 'rpOptions : ' . $sRpoptions . '';
		}

		if($this->defaultFalse('/flexigridconfiguration/usepager')) {
			$aJson[] = 'usepager : true';
		}

		if(($sPagestat = $this->_navConf('/flexigridconfiguration/pagestat')) !== FALSE) {
			$aJson[] = 'pagestat : "' . $this->oForm->getLLLabel($sPagestat) . '"';
		}

		if(($sPagetext = $this->_navConf('/flexigridconfiguration/pagetext')) !== FALSE) {
			$aJson[] = 'pagetext : "' . $this->oForm->getLLLabel($sPagetext) . '"';
		}

		if(($sOutof = $this->_navConf('/flexigridconfiguration/outof')) !== FALSE) {
			$aJson[] = 'outof : "' . $this->oForm->getLLLabel($sOutof) . '"';
		}

		if(($sMinwidth = $this->_navConf('/flexigridconfiguration/minwidth')) !== FALSE) {
			$aJson[] = 'minwidth : "' . $sMinwidth . '"';
		}

		if(($sMinheight = $this->_navConf('/flexigridconfiguration/minheight')) !== FALSE) {
			$aJson[] = 'minheight : "' . $sMinheight . '"';
		}

		if(!empty($this->aLimitAndSort["sortby"])) {
			$aJson[] = 'sortname : "' . $this->aLimitAndSort["sortby"] . '"';
		}

		if(!empty($this->aLimitAndSort["sortdir"])) {
			$aJson[] = 'sortorder : "' . $this->aLimitAndSort["sortdir"] . '"';
		}

		if(($mEmpty = $this->_navConf("/ifempty")) !== FALSE) {
			if(is_array($mEmpty)) {

				if($this->oForm->_navConf("/message", $mEmpty) !== FALSE) {
					if(tx_ameosformidable::isRunneable($mEmpty["message"])) {
						$sMessage = $this->callRunneable($mEmpty["message"]);
					} else {
						$sMessage = $mEmpty["message"];
					}

					$sMessage = $this->oForm->_substLLLInHtml($sMessage);
					$aJson[] = 'nomsg : "' . $sMessage . '"';
				}
			} else {
				$aJson[] = 'nomsg : "' . $this->oForm->_getLLLabel($mEmpty) . '"';
			}
		}

		if(($aButtons = $this->_navConf('/flexigridconfiguration/buttons')) !== FALSE) {
			$aJsonButtons = array();
			foreach($aButtons as $sKey => $aButton) {
				if($sKey{0} == 'b' && $sKey{1} == 'u' && substr($sKey, 0, 6) === 'button') {
					// show button ?
					if(!isset($aButton['visible'])) {
						$mVisible = TRUE;
					} else {				
						if(tx_ameosformidable::isRunneable($aButton['visible'])) {
							$mVisible = $this->callRunneable($aButton['visible']);
						} else {
							$mVisible = (strtolower($aButton['visible']) === 'true');
						}
					}
					
					if($mVisible === TRUE) {
						$aJsonButton = array();
						$aJsonButton[] = 'name : "' . $this->oForm->getLLLabel($aButton['label']) . '"';
						$aJsonButton[] = 'bclass : "' . $aButton['class'] . '"';
						$aJsonButton[] = 'onpress : function(){' . $this->synthetizeAjaxEventCb('onclick', $aButton['exec'], FALSE, FALSE) . '}';
		
						$aJsonButtons[] = '{' . implode(',', $aJsonButton) . '}';
					}
				}
			}
			
			if(!empty($aJsonButtons)) {
				$aJson[] = 'buttons : [' . implode(',', $aJsonButtons) . ']';
			}
		}

		if($this->isAdvanceFlexgridLister()) {
			$aColsJson = array();
			foreach($this->aOColumns as $sColname => $oColumn) {
				$aColJson = array();

				if($sColname == 'selectrow') {
					$sCheck = ($this->bAllRowsSelected === TRUE) ? 'checked=\'checked\'' : '';
					$aColJson[] = 'display: "<input type=\'checkbox\' name=\'' . $this->_getElementHtmlName() . '[selectall]\' class=\'formidable-selectall\' id=\'' . $this->_getElementHtmlId() . '.selectall\' value=\'1\' ' . $sCheck . '/>"';
				} else {
					$aColJson[] = 'display: "' . $this->getListHeader($sColname) . '"';
				}

				$aColJson[] = 'name: "' . $sColname . '"';

				if(($sWidth = $oColumn->_navConf('/width')) !== FALSE) {
					$aColJson[] = 'width: "' . $sWidth . '"';
				} elseif($sColname == 'selectrow') {
					$aColJson[] = 'width: "40"';
				}

				if(($sSortable = $oColumn->_navConf('/sortable')) !== FALSE) {
					$aColJson[] = 'sortable: ' . $sSortable;
				} elseif($sColname == 'selectrow') {
					$aColJson[] = 'sortable: false';
				}

				if($oColumn->defaultFalse('/hide') !== FALSE) {
					$aColJson[] = 'hide: true';
				}

				if(($sAlign = $oColumn->_navConf('/align')) !== FALSE) {
					$aColJson[] = 'align: "' . $sAlign . '"';
				}

				$aColsJson[] = '{' . implode(',', $aColJson) . '}';
			}

			$aJson[] = 'colModel : [' . implode(',', $aColsJson) . ']';
			$aJson[] = 'url: "' . $this->sGridurl . '"';
			$aJson[] = 'dataType: "xml"';
		}

		$aJson[] = 'onSuccess : function() {
			Formidable.f("' . $this->oForm->formid . '").o("' . $this->_getElementHtmlIdWithoutFormId() . '").initSelectrow();
		}';

		return '{' . implode(',', $aJson) . '}';
	}

	function includeScripts($aConf = array()) {
		parent::includeScripts($aConf);

		$sAbsName = $this->_getElementHtmlIdWithoutFormId();

		$sInitScript = '';
		if($this->isFlexgridLister())	{
			if(($sGridClass = $this->_navConf('/flexigridconfiguration/class')) !== FALSE) {
				$sJson = $this->generateFlexigridJson();
				$sInitScript.=<<<INITSCRIPT
					$(".{$sGridClass}").flexigrid({$sJson});
INITSCRIPT;
			} else {
				$this->oForm->mayday("RENDERLET LISTER - flexgrid lister need a class.");
			}
		}

		$sInitScript.=<<<INITSCRIPT
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").init();

INITSCRIPT;

		# initalization is made post-init
			# as when rendered in an ajax context in a modalbox,
			# the HTML is available *after* init tasks
			# as the modalbox HTML is added to the page using after init tasks !

		$this->oForm->attachPostInitTask(
			$sInitScript,
			"Post-init LISTER initialization",
			$this->_getElementHtmlId()
		);
	}

	function repaintFirst() {
		return $this->majixRepaintPage(1);
	}

	function repaintPrev() {
		$this->bResetPager = FALSE;
		$iPage = $this->_getPage();
		return $this->majixRepaintPage($iPage-1);
	}

	function repaintNext() {
		$this->bResetPager = FALSE;
		$iPage = $this->_getPage();
		return $this->majixRepaintPage($iPage+1);
	}

	function repaintLast() {
		return $this->majixRepaintPage($this->aPager["pagemax"]);
	}

	function setSortColumn($sCol) {
		$this->aLimitAndSort["sortby"] = $sCol;
	}

	function setSortDirection($sDir) {
		$this->aLimitAndSort["sortdir"] = $sDir;
	}

	function repaintSortBy($aParams) {
		$this->setSortColumn($aParams["sys_event"]["sortcol"]);
		$this->setSortDirection($aParams["sys_event"]["sortdir"]);
		return $this->majixRepaint();
	}

	function repaintWindow($aParams) {
		return $this->majixRepaintPage($aParams['page']);
	}

	function selectRow($aParams) {
		if(trim($aParams['sys_event']['currentrows']) !== '') {
			$aCurrentRows = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $aParams['sys_event']['currentrows']);

			foreach($aCurrentRows as $iRowUid) {
				$this->aSelectedRows[$iRowUid] = $iRowUid;
			}
		}
		
		if($this->defaultFalse("/keepselectioninsession") === TRUE && $this->sDsType == 'searchform') {
			$aData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->oDataStream->getSearchHash()][$this->oDataStream->getAbsName()]["infos"];
			$aData["selection"] = $this->aSelectedRows;
		}
	}

	function unselectRow($aParams) {
		if(trim($aParams['sys_event']['currentrows']) !== '') {
			$aCurrentRows = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $aParams['sys_event']['currentrows']);

			foreach($aCurrentRows as $iRowUid) {
				unset($this->aSelectedRows[$iRowUid]);
			}
		}
		
		if($this->defaultFalse("/keepselectioninsession") === TRUE && $this->sDsType == 'searchform') {
			$aData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->oDataStream->getSearchHash()][$this->oDataStream->getAbsName()]["infos"];
			$aData["selection"] = $this->aSelectedRows;
		}
	}

	function selectAllRows($aParams) {
		$aConfig = array(
			"page" => 0,
			"perpage" => 1000000,
			"sortcolumn" => $this->aLimitAndSort["sortby"],
			"sortdirection" => $this->aLimitAndSort["sortdir"],
		);

		$aData = $this->_fetchData($aConfig);
		foreach($aData['results'] as $aRow) {
			$this->aSelectedRows[$aRow['uid']] = $aRow['uid'];
		}
		
		if($this->defaultFalse("/keepselectioninsession") === TRUE && $this->sDsType == 'searchform') {
			$aData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->oDataStream->getSearchHash()][$this->oDataStream->getAbsName()]["infos"];
			$aData["selection"] = $this->aSelectedRows;
		}
	}

	function unselectAllRows($aParams) {
		$this->aSelectedRows = array();
		if($this->defaultFalse("/keepselectioninsession") === TRUE && $this->sDsType == 'searchform') {
			$aData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->oDataStream->getSearchHash()][$this->oDataStream->getAbsName()]["infos"];
			$aData["selection"] = $this->aSelectedRows;
		}
	}

	function setSelected($iUid) {
		$this->mCurrentSelected = $iUid;
	}

	function getSelectedRows() {
		// assertions
		if(!is_array($this->aSelectedRows)) {
			return;
		}

		return array_keys($this->aSelectedRows);
	}

	function setSelectedRows($aRows) {
		$this->aSelectedRows = array();
		foreach($aRows as $iUid) {
			$this->aSelectedRows[$iUid] = $iUid;
		}		

		if($this->defaultFalse("/keepselectioninsession") === TRUE && $this->sDsType == 'searchform') {
			$aData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->oDataStream->getSearchHash()][$this->oDataStream->getAbsName()]["infos"];
			$aData["selection"] = $this->aSelectedRows;
		}
	}

	function getFirstSelectedRow() {
		if($this->isUniqueSelection()) {
			reset($this->aSelectedRows);
			return current($this->aSelectedRows);
		}
	}
	
	function getSelectedRow() {
		if($this->isUniqueSelection()) {
			return current($this->aSelectedRows);
		}
	}

	function selectionIsEmpty() {
		return !is_array($this->aSelectedRows) || empty($this->aSelectedRows);
	}

	function isUniqueSelection() {
		return is_array($this->aSelectedRows) && count($this->aSelectedRows) == 1;
	}

	function clearSelection() {
		$this->aSelectedrows = array();
		$GLOBALS['_SESSION']['ameos_formidable']['applicationdata']['rdt_lister'][$this->oDataStream->getSearchHash()][$this->oDataStream->getAbsName()]['infos']['selection'] = array();
	}

	function getPageNumberForUid($iUid) {
		if(($iPos = $this->oDataStream->getRowNumberForUid($iUid)) !== FALSE) {
			return $this->getPageForLineNumber($iPos);
		}

		return FALSE;
	}

	function _initActionsListbox() {
		if(($aActions = $this->_navConf("/actions")) !== FALSE && is_array($aActions)) {
			$aItems = $this->getActionsItems();
			if(!empty($aItems)) {
				$this->initActions();
				if(($sLabel = $this->_navConf("/actions/label")) === FALSE) {
					$sLabel = '';
				}

				if(($sBlank = $this->_navConf("/actions/blank")) === FALSE) {
					$sBlank = '';
				}

				if(($sClass = $this->_navConf("/actions/class")) === FALSE) {
					$sClass = '';
				}

			
				$aConf = array(
					'type' => 'LISTBOX',
					'name' => 'actions',
					'label' => $sLabel,
					'class' => $sClass,
					'data' => array('items' => $aItems),
					'addblank' => $sBlank,
					'onchange'=> array(
						'runat' => 'ajax',
						'syncvalue' => 'true',
						'cache' => 'false',
						'exec' => "rdt('" . $this->getAbsName() . "').actionsChange()",
					)
				);

				$oRdt =& $this->oForm->_makeRenderlet(
					$aConf,
					$this->sXPath . 'actions/',
					FALSE,
					$this,
					FALSE,
					FALSE
				);

				$sAbsName = $oRdt->getAbsName();
				$sName = $oRdt->getName();

				$this->oForm->aORenderlets[$sAbsName] =& $oRdt;

				if(($this->_navConf('/actions/template/path')) !== FALSE) {
					$aTemplate = $this->_navConf('/actions/template');
					$sPath = $this->oForm->toServerPath($this->oForm->_navConf('/path', $aTemplate));

					if(!file_exists($sPath)) {
						$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) doesn't exists.");
					} elseif(is_dir($sPath)) {
						$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) is a directory, and should be a file.");
					} elseif(!is_readable($sPath)) {
						$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path exists but is not readable.");
					}

					if(($sSubpart = $this->oForm->_navConf('/subpart', $aTemplate)) === FALSE) {
						$sSubpart = $this->getName();
					}

					$mHtml = \Ameos\AmeosFormidable\Html\HtmlParser::getSubpart(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sPath), $sSubpart);
					if(trim($mHtml) == "") {
						$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template (<b>'" . $sPath . "'</b> with subpart marquer <b>'" . $sSubpart . "'</b>) <b>returned an empty string</b> - Check your template");
					}

					$aMarkers = array('actions' => $oRdt->render());
					foreach($this->aActions as $aAction) {
						if(array_key_exists('content', $aAction)) {
							$sType = $this->oForm->rdt($aAction['content'])->_getType();
							if($sType !== 'MODALBOX' && $sType !== 'MODALBOX2') {					
								if(($this->oForm->rdt($aAction['content'])->_navConf('/childs/template/path')) === FALSE) {
									$this->oForm->rdt($aAction['content'])->setCustomRootHtml($mHtml);
								}
								$aMarkers[$aAction['content']] = $this->oForm->rdt($aAction['content'])->render();
							}
						}
					}

					$this->aActionListbox = $this->oForm->_parseTemplateCode($mHtml, $aMarkers, array(), FALSE);
						
				} else {
					$this->aActionListbox = $oRdt->render();
				}
			}
		}
	}

	function actionsChange() {
		$sValue = $this->oForm->rdt('actions')->getValue();

		$aTasks = array();
		foreach($this->aActions as $sName => $aAction) {
			if($sName !== $sValue && isset($aAction['content'])) {
				$sType = $this->oForm->rdt($aAction['content'])->_getType();
				if($sType !== 'MODALBOX' && $sType !== 'MODALBOX2') {
					$aTasks[] = $this->oForm->rdt($aAction['content'])->majixSetInvisible();
				}
			}
		}

		if(isset($this->aActions[$sValue]['content'])) {
			$sType = $this->oForm->rdt($this->aActions[$sValue]['content'])->_getType();
			if($sType !== 'MODALBOX' && $sType !== 'MODALBOX2') {
				$aTasks[] = $this->oForm->rdt($this->aActions[$sValue]['content'])->majixSetVisible();
			} else {
				$aTasks[] = $this->oForm->rdt($this->aActions[$sValue]['content'])->majixShowBox();
			}
		} elseif(isset($this->aActions[$sValue]['exec'])) {
			$aTasks[] = $this->oForm->_callCodeBehind(
				array('exec' => $this->aActions[$sValue]['exec'], '__value' => '')
			);
		}

		return $aTasks;
	}

	function initActions() {
		$this->aActions = array();
		if(($aActions = $this->_navConf("/actions")) !== FALSE && is_array($aActions)) {
			foreach($aActions as $sKey => $aAction) {
				$mDisabled = FALSE;
				if(is_array($aAction) && array_key_exists('disabled', $aAction)) {
					$mDisabled = $this->isFalseVal($mDisabled['disabled']);
				}
				if(substr($sKey, 0, 6) === 'action' && !$mDisabled) {
					if(isset($aAction['content'])) {
						$this->aActions[$aAction['name']] = array('content' => $aAction['content']);
					} elseif(isset($aAction['exec'])) {
						$this->aActions[$aAction['name']] = array('exec' => $aAction['exec']);
					}
				}
			}
		}
	}

	function getActionsItems() {
		$aData = array();
		if(($aActions = $this->_navConf("/actions")) !== FALSE && is_array($aActions)) {
			foreach($aActions as $sKey => $aAction) {
				if(substr($sKey, 0, 6) === 'action' ) {
					$mVisible = TRUE;
					if(array_key_exists('visible', $aAction)) {
						$mVisible = $this->isTrueVal($aAction['visible']);
					}

					$mDisabled = FALSE;
					if(array_key_exists('disabled', $aAction)) {
						$mDisabled = $this->isFalseVal($mDisabled['disabled']);
					}
					
					if($mVisible) {
						if($mDisabled) {
							$aData[] = array('value' => $aAction['name'], 'caption' => $aAction['label'], 'disabled' => TRUE);
						} else {
							$aData[] = array('value' => $aAction['name'], 'caption' => $aAction['label']);
						}
					}
				}
			}
		}

		return $aData;
	}

	function getNbRows() {
		return intval($this->aPager["numrows"]);
	}

	function getRow($iUid) {
		if(isset($this->aCurrentRows[$iUid])) {
			foreach($this->aCurrentRows[$iUid] as $sColumn => $sValue) {
				if($sColumn !== 'selectrow' && in_array($sColumn, $this->aExportableColumns)) {
					$aCurRow[$sColumn] = $sValue;
				}
			}

			return $aCurRow;
		}

		$aConfig = array(
			"page" => 0,
			"perpage" => $this->aLimitAndSort["rowsperpage"],
			"sortcolumn" => $this->aLimitAndSort["sortby"],
			"sortdirection" => $this->aLimitAndSort["sortdir"],
		);

		$aFilter = array('uid = ' . intval($iUid));
		if($this->sDsType == 'searchform') {
			$oDataSource = $this->oDataStream->oDataSource;
		} else {
			$oDataSource = $this->oDataSource;
		}

		$aData = $oDataSource->_fetchData($aConfig, $aFilter);
		foreach(array_keys($this->aOColumns) as $sColumn) {
			if($sColumn !== 'selectrow' && in_array($sColumn, $this->aExportableColumns)) {
				$aCurRow[$sColumn] = $aData['results'][0][$sColumn];
			}
		}
		return $aCurRow;
	}

	function getColumnsName() {
		return array_keys($this->aOColumns);
	}

	function getCurrentRows() {
		return $this->aCurrentRows;
	}

	function addRow($iKey, $aRow) {
		// assertions
		if(!is_array($this->aCurrentRows)) {
			return;
		}

		if(!is_array($this->aOColumns)) {
			return;
		}

		if(!is_array($aRow)) {
			return;
		}


		foreach(array_keys($this->aOColumns) as $sColumn) {
			if($sColumn !== 'selectrow') {
				if(isset($aRow[$sColumn]['value.']['humanreadable'])) {
					$this->aCurrentRows[$iKey][$sColumn] = $aRow[$sColumn]['value.']['humanreadable'];
				} elseif(isset($aRow[$sColumn]['html'])) {
					$this->aCurrentRows[$iKey][$sColumn] = $aRow[$sColumn]['html'];
				}
			}
		}
	}

	function getExportableColumns() {
		return $this->aExportableColumns;
	}

	function getSearchableColumns() {
		return $this->aSearchableColumns;
	}

	function handleAjaxRequest(&$oRequest) {
		$iCurrentPage = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP("page"));
		$aConfig = array(
			'page' => intval($iCurrentPage - 1),
			'perpage' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GP("rp"),
			'sortcolumn' => (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP("sortname") == 'undefined') ? '' : \TYPO3\CMS\Core\Utility\GeneralUtility::_GP("sortname"),
			'sortdirection' => (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP("sortorder") == 'undefined') ? '' : \TYPO3\CMS\Core\Utility\GeneralUtility::_GP("sortorder"),
		);

		$aData = $this->_fetchData($aConfig);

		$iRowNum = 0;
		$this->iCurRowNum = 0;

		$sXml = '<?xml version="1.0" encoding="utf-8"?>';
		$sXml.= '<rows><page>' . $iCurrentPage . '</page><total>' . $aData['numrows'] . '</total>';

		reset($aData);
		while(list($iIndex, ) = each($aData["results"])) {
			$this->iCurRowNum = $iRowNum;
			$aCurRow = $aData["results"][$iIndex];
			$iRowUid = $aCurRow["uid"];

			if(!is_array($aCurRow)) {
				$aCurRow = array();
			}

			if($aTableCols === FALSE) {
				$aTableCols = array_keys($aCurRow);
				reset($aTableCols);
			}

			$this->__aCurRow = $aCurRow;
			array_push($this->oForm->oDataHandler->__aListData, $aCurRow);
			$aCurRow = $this->processBeforeRender($aCurRow);

			$aCurRow = $this->_refineRow($aCurRow);

			$aCurRow = $this->processBeforeDisplay($aCurRow);
			$aCurRow = $this->filterUnprocessedColumns($aCurRow, $aTableCols);
			$this->addRow($aCurRow['uid'], $aCurRow);

			$sXml.= '<row id="' . $iRowUid . '">';
			reset($this->aOColumns);
			while(list($sCol,) = each($this->aOColumns)) {
				$sXml.= '<cell><![CDATA[' . $aCurRow[$sCol]["__compiled"] . ']]></cell>';
			}

			$sXml.= '</row>';
			$this->__aCurRow = array();
			$iRowNum++;
		}

		$sXml.= '</rows>';

		header('Content-Type: text/xml');
		echo $sXml;
		die();
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_lister/api/class.tx_rdtlister.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_lister/api/class.tx_rdtlister.php"]);
	}
?>
