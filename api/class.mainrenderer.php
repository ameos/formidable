<?php

	class formidable_mainrenderer extends formidable_mainobject {

		var $aCustomHidden	= null;
		var $bFormWrap		= TRUE;
		var $bValidation	= TRUE;
		var $bDisplayLabels	= TRUE;
		var $bDisplayClassError = TRUE;
		var $sDefaultLabelClass = "";	// no class on labels by default

		function _init(&$oForm, $aElement, $aObjectType, $sXPath) {

			parent::_init($oForm, $aElement, $aObjectType, $sXPath);

			$this->_setDisplayLabels(!$this->oForm->isFalse($this->oForm->sXpathToMeta . "displaylabels"));
			$this->_setFormWrap(!$this->oForm->isFalse($this->oForm->sXpathToMeta . "formwrap"));
			$this->_setDisplayErrorClass(!$this->oForm->isFalse($this->oForm->sXpathToMeta . "displayerrorclass"));
		}

		function _render($aRendered) {
			return $this->_wrapIntoForm(implode("<br />\n", $aRendered));
		}

		function _includeLibraries() {

			if($this->oForm->useJs()) {
				$this->oForm->oJs->_includeOnceLibs();
				$this->oForm->oJs->_includeThisFormDesc();
			}

		}

		function _wrapIntoDebugContainer($aHtmlBag, &$oRdt) {

			$sName = $oRdt->getAbsName();

			$sHtml = <<<TEMPLATE


				<div class="ameosformidable_debugcontainer_void">
					<div style="pointer: help;" class="ameosformidable_debughandler_void">{$oRdt->aElement["type"]}:{$sName}</div>
					{$aHtmlBag["__compiled"]}
				</div>
TEMPLATE;
			$aHtmlBag["__compiled"] = $sHtml;

			if(array_key_exists("input", $aHtmlBag)) {

				$sInfos = rawurlencode($aHtmlBag["input"]);
				$sHtml = <<<TEMPLATE


				<div class="ameosformidable_debugcontainer_void">
					<div style="pointer: help;" class="ameosformidable_debughandler_void">{$sName}.input</div>
					{$aHtmlBag["input"]}
				</div>
TEMPLATE;
				$aHtmlBag["input"] = $sHtml;

			}
			return $aHtmlBag;
		}

		function _wrapIntoForm($html) {

			if(!empty($this->oForm->oDataHandler->newEntryId)) {
				$iEntryId = $this->oForm->oDataHandler->newEntryId;
			} else {
				$iEntryId = $this->oForm->oDataHandler->_currentEntryId();
			}

			$hidden_entryid		= $this->_getHiddenEntryId($iEntryId);
			$hidden_custom		= $this->_getHiddenCustom();
			$sSysHidden			=	"<input type='hidden' name='" . $this->oForm->formid . "[AMEOSFORMIDABLE_SERVEREVENT]' id='" . $this->oForm->formid . "_AMEOSFORMIDABLE_SERVEREVENT' />" .
									"<input type='hidden' name='" . $this->oForm->formid . "[AMEOSFORMIDABLE_SERVEREVENT_PARAMS]' id='" . $this->oForm->formid . "_AMEOSFORMIDABLE_SERVEREVENT_PARAMS' />" .
									"<input type='hidden' name='" . $this->oForm->formid . "[AMEOSFORMIDABLE_SERVEREVENT_HASH]' id='" . $this->oForm->formid . "_AMEOSFORMIDABLE_SERVEREVENT_HASH' />" .
									"<input type='hidden' name='" . $this->oForm->formid . "[AMEOSFORMIDABLE_ADDPOSTVARS]' id='" . $this->oForm->formid . "_AMEOSFORMIDABLE_ADDPOSTVARS' />" .
									"<input type='hidden' name='" . $this->oForm->formid . "[AMEOSFORMIDABLE_VIEWSTATE]' id='" . $this->oForm->formid . "_AMEOSFORMIDABLE_VIEWSTATE' />" .
									"<input type='hidden' name='" . $this->oForm->formid . "[AMEOSFORMIDABLE_SUBMITTED]' id='" . $this->oForm->formid . "_AMEOSFORMIDABLE_SUBMITTED' value='" . AMEOSFORMIDABLE_EVENT_SUBMIT_FULL . "' />" .
									"<input type='hidden' name='" . $this->oForm->formid . "[AMEOSFORMIDABLE_SUBMITTER]' id='" . $this->oForm->formid . "_AMEOSFORMIDABLE_SUBMITTER' />";

			if(($sStepperId = $this->oForm->_getStepperId()) !== FALSE) {
				$sSysHidden .=	"<input type='hidden' name='AMEOSFORMIDABLE_STEP' id='AMEOSFORMIDABLE_STEP' value='" . $this->oForm->_getStep() . "' />" .
								"<input type='hidden' name='AMEOSFORMIDABLE_STEP_HASH' id='AMEOSFORMIDABLE_STEP_HASH' value='" . $this->oForm->_getSafeLock($this->oForm->_getStep()) . "' />";
			}
			
			$method = $this->oForm->getFormMethod();
			if($method == 'GET') {
				$sSysHidden .= "<input type='hidden' name='id' value='" . $GLOBALS['TSFE']->id . "' />";
			}

			$aHtmlBag =
				array(
					"SCRIPT"		=> "",
					"FORMBEGIN"		=> "",
					"CONTENT"		=> $html,
					/*"HIDDEN"		=> $hidden_entryid . $hidden_custom . $sSysHidden,*/
					"HIDDEN"		=> "<p style='position:absolute; top:-5000px; left:-5000px;'>" . $hidden_entryid . $hidden_custom . $sSysHidden . "</p>",	// in P for XHTML validation
					"FORMEND"		=> "",
				);

			if($this->bFormWrap) {

				$formid			= "";
				$formaction		= "";
				$formonsubmit	= "";
				$formmethod		= "";
				$formcustom		= "";

				/*$formid = " id=\"" . $this->oForm->formid . "\" name=\"" . $this->oForm->formid . "\" ";*/
				$formid = " id=\"" . $this->oForm->formid . "\" ";

				$formaction = " action=\"" . $this->oForm->xhtmlUrl($this->oForm->getFormAction()) . "\" ";
				$formmethod = " method=\"" . $method . "\" ";				

				if(($sOnSubmit = $this->oForm->_navConf($this->oForm->sXpathToMeta . "form/onsubmit")) !== FALSE) {
					$formonsubmit = " onSubmit = \"" . $sOnSubmit . "\" ";
				}
				
				if(($sCustom = $this->oForm->_navConf($this->oForm->sXpathToMeta . "form/custom")) !== FALSE) {
					$formcustom = " " . $sCustom . " ";
				}

				$aHtmlBag["FORMBEGIN"]	=	"<form enctype=\"multipart/form-data\" " . $formid . $formaction . $formonsubmit . $formcustom . $formmethod . ">";
				$aHtmlBag["FORMEND"]	=	"</form>";
			}

			reset($aHtmlBag);
			return $aHtmlBag;
		}

		function _getFullSubmitEvent() {
			return "Formidable.f('" . $this->oForm->formid . "').submitFull();";
		}

		function _getRefreshSubmitEvent() {
			return "Formidable.f('" . $this->oForm->formid . "').submitRefresh();";
		}

		function _getDraftSubmitEvent() {
			return "Formidable.f('" . $this->oForm->formid . "').submitDraft();";
		}

		function _getTestSubmitEvent() {
			return "Formidable.f('" . $this->oForm->formid . "').submitTest();";
		}

		function _getClearSubmitEvent() {
			return "Formidable.f('" . $this->oForm->formid . "').submitClear();";
		}

		function _getSearchSubmitEvent() {
			return "Formidable.f('" . $this->oForm->formid . "').submitSearch();";
		}

		function _getServerEvent($sRdtAbsName, $aEvent, $sEventId, $aData = array()) {

			// $aData is typicaly the current row if in lister

			$sJsParam = "false";
			$sHash = "false";
			$aGrabbedParams = array();
			$aFullEvent = $this->oForm->aServerEvents[$sEventId];

			if($aFullEvent["earlybird"] === TRUE) {
				# registering absolute name,
					# this will help when early-processing the event
				#debug($aFullEvent, "laaa");

				$aGrabbedParams["_sys_earlybird"] = array(
					"absname" => $aFullEvent["name"],
					"xpath" => $this->oForm->_removeEndingSlash($this->oForm->aORenderlets[$aFullEvent["name"]]->sXPath) . "/" . $aFullEvent["trigger"]
				);
			}

			#if(!empty($aData)) {
				reset($aFullEvent["params"]);
				while(list($sKey,) = each($aFullEvent["params"])) {
					$sParam = $aFullEvent["params"][$sKey]["get"];
					
					if(t3lib_div::isFirstPartOfStr($sParam, "rowData::")) {
						$sParam = substr($sParam, 9);
					}
					
					if(array_key_exists($sParam, $aData)) {
						$aGrabbedParams[$sParam] = $aData[$sParam];
					} else {
						$aGrabbedParams[] = $sParam;
					}
				}
			#}

			if(!empty($aGrabbedParams)) {
				$sJsParam = base64_encode(serialize($aGrabbedParams));
				$sHash = "'" . $this->oForm->_getSafeLock($sJsParam) . "'";
				$sJsParam = "'" . $sJsParam . "'";
			}

			#debug($sJsParam, "sJsParam");

			$sConfirm = "false";
			if(array_key_exists("confirm", $aEvent) && trim($aEvent["confirm"] !== "")) {

				/*$sConfirm = "'" . rawurlencode(
					$this->oForm->_getLLLabel(
						$aEvent["confirm"]
					)
				) . "'";*/

				// charset problem patched by Nikitim S.M
					// http://support.typo3.org/projects/formidable/m/typo3-project-formidable-russian-locals-doesnt-work-int-formidable-20238-i-wrote-the-solvation/p/15/

				$sConfirm = "'" .
					addslashes($this->oForm->_getLLLabel(
						$aEvent["confirm"]
					))
				. "'";
			}

			if(($sSubmitMode = $this->oForm->_navConf("submit", $aEvent)) !== FALSE) {
				switch($sSubmitMode) {
					case "full" : {
						return "Formidable.f('" . $this->oForm->formid . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_FULL, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
					case "refresh" : {
						return "Formidable.f('" . $this->oForm->formid . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_REFRESH, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
					case "draft" : {
						return "Formidable.f('" . $this->oForm->formid . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_DRAFT, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
					case "test" : {
						return "Formidable.f('" . $this->oForm->formid . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_TEST, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
					case "search" : {
						return "Formidable.f('" . $this->oForm->formid . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_SEARCH, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
				}
			} else {
				// default: REFRESH
				return "Formidable.f('" . $this->oForm->formid . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_REFRESH , " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
			}
		}
		
		function synthetizeAjaxEvent(&$oRdt, $sEventHandler, $sCb=FALSE, $sPhp=FALSE, $mParams=FALSE, $bCache=TRUE, $bSyncValue=FALSE, $bRefreshContext=FALSE) {
			$aEvent = array(
				"runat" => "ajax",
				"cache" => intval($bCache),	// intval because FALSE would be bypassed by navconf
				"syncvalue" => intval($bSyncValue),	// same reason
				"refreshcontext" => intval($bRefreshContext),	// same reason
				"params" => $mParams,
			);
			
			if($sCb !== FALSE) {
				$aEvent["exec"] = $sCb;
			} elseif($sPhp !== FALSE) {
				$aEvent["userobj"]["php"] = $sPhp;
			}

			$sRdtAbsName = $oRdt->getAbsName();
			$sEventId = $this->oForm->_getAjaxEventId(
				$sRdtAbsName,
				array($sEventHandler => $aEvent)
			);

			$this->oForm->aAjaxEvents[$sEventId] = array(
				"name" => $sRdtAbsName,
				"eventid" => $sEventId,
				"trigger" => $sEventHandler,
				"cache" => intval($bCache),	// because FALSE would be bypassed by navconf
				"event" => $aEvent,
			);
			
			return $this->_getAjaxEvent(
				$oRdt,
				$aEvent,
				$sEventHandler
			);
		}
		
		function _getAjaxEvent(&$oRdt, $aEvent, $sEvent) {

			$sRdtName = $oRdt->getAbsName();

			$sEventId = $this->oForm->_getAjaxEventId(
				$sRdtName,
				array($sEvent => $aEvent)
			);

			$sRdtId = $oRdt->_getElementHtmlId();
			$sHash = $oRdt->_getSessionDataHashKey();
			$bSyncValue = $this->oForm->defaultFalse("/syncvalue", $aEvent);
			$bCache = $this->oForm->defaultTrue("/cache", $aEvent);
			$bPersist = $this->oForm->defaultFalse("/persist", $aEvent);
			$bRefreshContext = $this->oForm->defaultFalse("/refreshcontext", $aEvent);		


			$sConfirm = "false";
			if(array_key_exists("confirm", $aEvent) && trim($aEvent["confirm"] !== "")) {

				$sConfirm = "'" .
					addslashes($this->oForm->_getLLLabel(
						$aEvent["confirm"]
					))
				. "'";
			}

			$aParams = array();
			$aParamsCollection = array();
			$aRowParams = array();

			if(($mParams = $this->oForm->_navConf("/params", $aEvent)) !== FALSE) {
				if(is_string($mParams)) {
					
					$aTemp = t3lib_div::trimExplode(",", $mParams);
					reset($aTemp);
					while(list(, $sParam) = each($aTemp)) {
						$aParamsCollection[] = array(
							"get" => $sParam,
							"as" => FALSE,
						);
					}
				} else {
					$aParamsCollection = array_values($mParams);
				}
				
				#print_r(array($aParamsCollection, $oRdt->getAbsName()));
				
				// the new syntax
				// <params><param get="this()" as="this" /></params>

				reset($aParamsCollection);
				while(list($sKey,) = each($aParamsCollection)) {

					$sParam = $aParamsCollection[$sKey]["get"];
					$sAs = $aParamsCollection[$sKey]["as"];
					
					if(t3lib_div::isFirstPartOfStr($sParam, "rowData::")) {
						unset($aParams[$iKey]);
						$sParamName = substr($sParam, 9);
						
						if(($sValue = $this->oForm->oDataHandler->_getListData($sParamName)) !== FALSE) {
							$aRowParams[$sParamName] = $sValue;
						} else {
							$aRowParams[$sParamName] = "";
						}
					} elseif(t3lib_div::isFirstPartOfStr($sParam, "rowInput::")) {
						/* replacing *id* with *id for this row*; will be handled by JS framework */

						// note: _getAjaxEvent() is called when in rows rendering for list
						// _getElementHtmlId() on a renderlet is designed to return the correct html id for the input of this row in such a case

						$sParamName = substr($sParam, 10);
						
						
						if($sAs === FALSE) {
							$sAs = $sParamName;
						}
						
						if(array_key_exists($sParamName, $this->oForm->aORenderlets)) {
							$aParams[$sKey] = "rowInput::" . $sAs . "::" . $this->oForm->aORenderlets[$sParamName]->_getElementHtmlId();
						}
					} elseif(t3lib_div::isFirstPartOfStr($sParam, "rawData::")) {
						
						$aRawParam = explode('::', $sParam);
						$aParams[$aRawParam[1]] = $aRawParam[2];						
					} elseif(t3lib_div::isFirstPartOfStr($sParam, "sys_event.")) {
						$aParams[$sKey] = $sParam;
					} elseif(array_key_exists($sParam, $this->oForm->aORenderlets)) {
						
						if($sAs === FALSE) {
							$sAs = $sParam;
						}
						
						$aParams[$sKey] = "rowInput::" . $sAs . "::" . $this->oForm->aORenderlets[$sParam]->_getElementHtmlId();
					} elseif($sParam === "\$this") {
						$aParams[$sKey] = "rowInput::this::" . $oRdt->getAbsName();
					} else {
						
						if($sAs === FALSE) {
							$sAs = $sParam;
						}
						
						$mResult = $this->oForm->resolveForMajixParams(
							$sParam,
							$oRdt	// this will be $mData in the majixmethods class
						);

						if($this->oForm->isRenderlet($mResult)) {
							#debug("It's a renderlet");
							$sAs = $aParamsCollection[$sKey]["as"];
							$aParams[$sKey] = "rowInput::" . $sAs . "::" . $mResult->getAbsName();
						} else {
							debug($mResult, $sParam);
						}
					}
				}
			}
			
			if($bSyncValue === TRUE) {
				$aParams[] = "rowInput::sys_syncvalue::" . $sRdtName;
			}
			
			$aAjaxEventParams = $oRdt->alterAjaxEventParams(array(
				"eventname" => $sEvent,
				"eventid" => $sEventId,
				"hash" => $sHash,
				"cache" => $bCache,
				"persist" => $bPersist,
				"syncvalue" => $bSyncValue,
				"params" => $aParams,
				"row" => $aRowParams,
				"sessionhash" => $this->oForm->_getSessionDataHashKey()
			));

			$sJsonParams = $this->oForm->array2json($aAjaxEventParams["params"]);
			$sJsonRowParams = $this->oForm->array2json($aAjaxEventParams["row"]);

			$iDelay = (isset($aEvent['delay'])) ? intval($aEvent['delay']) : 0;
			$bRefreshContext = ($bRefreshContext) ? 1 : 0;

			return "try{arguments;} catch(e) {arguments=[];} Formidable.f('" . $this->oForm->formid . "').executeAjaxEvent('" . $aAjaxEventParams["eventname"] . "', '" . $sRdtId . "', '" . $aAjaxEventParams["eventid"] . "', '" . $aAjaxEventParams["hash"] . "', '" . $aAjaxEventParams["sessionhash"] . "', " . (($aAjaxEventParams["cache"]) ? "true" : "false") . ", " . (($aAjaxEventParams["persist"]) ? "true" : "false") . ", " . $sJsonParams . ", " . $sJsonRowParams . ", arguments, " . $sConfirm . ", " . $iDelay . ", " . $bRefreshContext . ");";
		}

		function wrapEventsForInlineJs($aEvents) {

			$aJson = array();
			reset($aEvents);
			while(list(, $sJs) = each($aEvents)) {
				$aJson[] = rawurlencode($sJs);
			}

			return "Formidable.executeInlineJs(" . $this->oForm->array2json($aJson) . ");";
		}

		function _getClientEvent($sObjectId, $aEvent = array(), $aEventData, $sEvent) {

			if(empty($aEventData)) {
				$aEventData = array();
			}

			$sData = $this->oForm->array2json(
				array(
					"init" => array(),			// init and attachevents are here for majix-ajax compat
					"attachevents" => array(),
					"tasks" => $aEventData,
				)
			);

			$bPersist = $this->oForm->defaultFalse("/persist", $aEvent);

			$sConfirm = "false";
			if(array_key_exists("confirm", $aEvent) && trim($aEvent["confirm"] !== "")) {

				$sConfirm = "'" .
					addslashes($this->oForm->_getLLLabel(
						$aEvent["confirm"]
					))
				. "'";
			}

			$iDelay = (isset($aEvent['delay'])) ? intval($aEvent['delay']) : 0;

			return "Formidable.f('" . $this->oForm->formid . "').executeClientEvent('" . $sObjectId . "', " . (($bPersist) ? "true" : "false") . ", {$sData}, '" . $sEvent . "', arguments, " . $sConfirm . ", " . $iDelay . ");";
		}

		function _getHiddenEntryId($entryId) {

			if(!empty($entryId)) {
				return "<input type = \"hidden\" id=\"" . $this->_getHiddenHtmlId("AMEOSFORMIDABLE_ENTRYID") . "\" name=\"" . $this->_getHiddenHtmlName("AMEOSFORMIDABLE_ENTRYID") . "\" value=\"" . $entryId . "\" />";
			}

			return "";
		}

		function _getHiddenCustom() {
			if(is_array($this->aCustomHidden) && sizeof($this->aCustomHidden) > 0)
			{ return implode("", $this->aCustomHidden);}

			return "";
		}

		function _setHiddenCustom($name, $value) {

			if(!is_array($this->aCustomHidden)) {
				$this->aCustomHidden = array();
			}

			$this->aCustomHidden[$name] = "<input type=\"hidden\" id=\"" . $this->_getHiddenHtmlId($name) . "\" name=\"" . $this->_getHiddenHtmlName($name) . "\" value=\"" . $value . "\" />";
		}

		function _getHiddenHtmlName($sName) {
			return $this->oForm->formid . "[" . $sName . "]";
		}

		function _getHiddenHtmlId($sName) {
			return $this->oForm->formid . "_" . $sName;
		}

		function _setFormWrap($bWrap) {
			$this->bFormWrap = $bWrap;
		}

		function _setValidation($bValidation) {
			$this->bValidation = $bValidation;
		}

		function _getThisFormId() {
			return $this->oForm->formid;
		}

		function _setDisplayLabels($bDisplayLabels) {
			$this->bDisplayLabels = $bDisplayLabels;
		}

		function _setDisplayErrorClass($bDisplayErrorClass) {
			$this->bDisplayErrorClass = $bDisplayErrorClass;
		}

		/*function _displayLabel($label) {
			return ($this->bDisplayLabels && (trim($label) != "")) ? "<div>" . $label . "</div>\n" : "";
		}*/

		function renderStyles() {

			if(($mStyle = $this->_navConf("/style")) !== FALSE) {

				$sUrl = FALSE;
				$sStyle = FALSE;

				if(tx_ameosformidable::isRunneable($mStyle)) {
					$sStyle = $this->callRunneable($mStyle);
				} elseif(is_array($mStyle) && array_key_exists("__value", $mStyle) && trim($mStyle["__value"]) != "") {
					$sStyle = $mStyle["__value"];
				} elseif(is_array($mStyle) && array_key_exists("url", $mStyle)) {
					if(tx_ameosformidable::isRunneable($mStyle["url"])) {
						$sUrl = $this->callRunneable($mStyle["url"]);
					} else {
						$sUrl = $mStyle["url"];
					}

					if($this->defaultFalse("/style/rewrite") === TRUE) {

						if(!$this->oForm->isAbsWebPath($sUrl)) {
							$sUrl = $this->oForm->toServerPath($sUrl);
							$sStyle = t3lib_div::getUrl($sUrl);
							$sUrl = FALSE;
						}
					}
				} elseif(is_string($mStyle)) {
					$sStyle = $mStyle;
				}

				if($sStyle !== FALSE) {
					reset($this->oForm->aORenderlets);
					while(list($sName, ) = each($this->oForm->aORenderlets)) {
						$oRdt =& $this->oForm->aORenderlets[$sName];
						$sStyle = str_replace(
							array(
								"#" . $sName,
								"{PARENTPATH}"
							),
							array(
								"#" . $oRdt->_getElementCssId(),
								$this->oForm->_getParentExtSitePath()
							),
							$sStyle
						);
					}

					//$sStyle = str_replace(";", " !important;", $sStyle);

					$this->oForm->additionalHeaderData(
						$this->oForm->inline2TempFile(
							$sStyle,
							'css',
							"Form '" . $this->oForm->formid . "' styles"
						)
					);
				}

				if($sUrl !== FALSE) {
					$sUrl = $this->oForm->toWebPath($sUrl);
					$this->oForm->additionalHeaderData(
						"<link rel='stylesheet' type='text/css' href='" . $sUrl . "' />"
					);
				}
			}
		}

		function processHtmlBag($mHtml, &$oRdt) {

			//$sLabel = array_key_exists("label", $oRdt->aElement) ? $this->oForm->_getLLLabel($oRdt->aElement["label"]) : "";
			$sLabel = $oRdt->getGenerateLabel();

			if(is_string($mHtml)/* && (($mHtml = trim($mHtml)) !== "")*/) {		// can be empty with empty readonly

				$mHtml = array(
					"__compiled"	=> $mHtml
				);
			}

			if(!empty($mHtml) && array_key_exists("__compiled", $mHtml) && is_string($mHtml["__compiled"])/* && trim($mHtml["__compiled"]) !== ""*/) {

				if(($mWrap = $oRdt->_navConf("/wrap")) !== FALSE) {

					if(tx_ameosformidable::isRunneable($mWrap)) {
						$mWrap = $this->callRunneable($mWrap);
					}

					$mWrap = $this->oForm->_substLLLInHtml($mWrap);

					$mHtml["__compiled"] = str_replace("|", $mHtml["__compiled"], $mWrap);
					// wrap added for f.schossig	2006/08/29
				}

				if(!array_key_exists("label", $mHtml)) {
					$mHtml["label"] = $sLabel;
				}

				if(!array_key_exists("label.", $mHtml)) {
					$mHtml["label."] = array();
				}

				if(!array_key_exists("tag", $mHtml["label."])) {
					$mHtml["label."]["tag"] = $oRdt->getLabelTag($sLabel);
				}
				
				if(!array_key_exists("tag.", $mHtml["label."])) {
					$mHtml["label."]["tag."] = array();
				}
				
				if(!array_key_exists("wrap", $mHtml["label."]["tag."])) {
					$mHtml["label."]["tag."]["wrap"] = $oRdt->getLabelTag("|");
				}

				if(!array_key_exists("htmlname", $mHtml)) {
					$mHtml["htmlname"] = $oRdt->_getElementHtmlName();
				}

				if(!array_key_exists("htmlid", $mHtml)) {
					$mHtml["htmlid"] = $oRdt->_getElementHtmlId();
				}

				if(!array_key_exists("htmlid.", $mHtml)) {
					$mHtml["htmlid."] = array();
				}

				if(!array_key_exists("withoutformid", $mHtml["htmlid."])) {
					$mHtml["htmlid."]["withoutformid"] = $oRdt->_getElementHtmlIdWithoutFormId();
				}

				if(($aError = $oRdt->getError()) !== FALSE) {
					$mHtml["error"] = $aError["message"];
					$mHtml["error."] = $this->oForm->_addDots($aError);
					$sClass = $mHtml["htmlid."]["withoutformid"];
					$mHtml["error."]["message."]["tag"] = "<span class=\"rdterror " . $sClass . "\" for=\"" . $mHtml["htmlid"] . "\">" . $aError["message"] . "</span>";
					$mHtml["error."]["class"] = "hasError";
				}

				if($oRdt->_readOnly() && !array_key_exists("readonly", $mHtml)) {
					$mHtml["readonly"] = TRUE;
				}
				
				/*
				$mHtml = $this->recombineHtmlBag(
					$mHtml,
					$oRdt
				);
				*/
				if($oRdt->_navConf("/recombine") !== FALSE) {
					$this->oForm->mayday("[" . $oRdt->getName() . "] <b>/recombine is deprecated</b>. You should use template methods instead");
				}

				if($this->oForm->bDebug && $oRdt->_debugable()) {
					$mHtml = $this->_wrapIntoDebugContainer(
						$mHtml,
						$oRdt
					);
				}
			} else {
				$mHtml = array();
			}

			reset($mHtml);
			return $mHtml;
		}

		function displayOnlyIfJs($aRendered) {

			$aRdts = array_keys($this->oForm->aORenderlets);
			reset($aRdts);
			while(list(, $sRdt) = each($aRdts)) {
				if($this->oForm->aORenderlets[$sRdt]->displayOnlyIfJs() === TRUE) {
					$sJson = $this->oForm->oJson->encode(
						$aRendered[$sRdt]["__compiled"]
					);

					$sId = $this->oForm->aORenderlets[$sRdt]->_getElementHtmlId() . "_unobtrusive";
					$aRendered[$sRdt]["__compiled"] = "<span id='" . $sId . "'></span>";

					$this->oForm->attachInitTaskUnobtrusive('
						if(Formidable.getElementById("' . $sId . '")) {Formidable.getElementById("' . $sId . '").innerHTML=' . $sJson . ';}
					');
				}
			}

			return $aRendered;
		}
		
		function wrapErrorMessage($sMessage) {
			
			if($this->isTrue("/template/errortagcompilednowrap")) {
				return $sMessage;
			}
			
			if(($sErrWrap = $this->_navConf("/template/errortagwrap")) !== FALSE) {

				if(tx_ameosformidable::isRunneable($sErrWrap)) {
					$sErrWrap = $this->callRunneable($sErrWrap);
				}
				
				$sErrWrap = $this->oForm->_substLLLInHtml($sErrWrap);
			} else {
				$sErrWrap = "<span class='errors'>|</span>";
			}
			
			return str_replace(
				"|",
				$sMessage,
				$sErrWrap
			);
		}
		
		function compileErrorMessages($aMessages) {
			if($this->defaultFalse("/template/errortagcompilednobr")) {
				return implode("", $aMessages);
			}
			
			return implode("<br />", $aMessages);
		}

	}

	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainrenderer.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainrenderer.php"]);
	}
?>
