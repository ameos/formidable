<?php

	define("AMEOSFORMIDABLE_VALUE_NOT_SET", "AMEOSFORMIDABLE_VALUE_NOT_SET");

	class formidable_mainrenderlet extends formidable_mainobject {

		var $__aCacheItems = array();

		var $aChilds		= array();
		var $aDependants	= array();
		var $aDependsOn		= array();
		var $bChild			= FALSE;

		var $aLibs = array();
		var $sMajixClass = "";
		var $bCustomIncludeScript = FALSE;	// TRUE if the renderlet needs to handle script inclusion itself

		var $aSkin = FALSE;

		var $sCustomElementId = FALSE;		// if != FALSE, will be used instead of generated HTML id ( useful for checkbox-group renderlet )
		var $aPossibleCustomEvents = array();
		var $aCustomEvents = array();
		var $oRdtParent = FALSE;
		var $sRdtParent = FALSE;	// store the parent-name while in session-hibernation

		var $aForcedItems = FALSE;
		var $bAnonymous = FALSE;
		var $bHasBeenSubmitted = FALSE;
		var $bHasBeenPosted = FALSE;
		var $mForcedValue;
		var $bForcedValue = FALSE;

		var $bIsDataBridge = FALSE;
		var $bHasDataBridge = FALSE;
		var $oDataSource = FALSE;	// connection to datasource object, for databridge renderlets
		var $sDataSource = FALSE;		// hibernation state
		var $oDataBridge = FALSE;	// connection to databridge renderlet, plain renderlets
		var $sDataBridge = FALSE;		// hibernation state
		var $aDataBridged = array();
		var $aDataSetSignatures = array();	// dataset signature, hash on this rdt-htmlid for sliding accross iterations in lister (as it contains the current row uid when iterating)
		
		#var $sDefaultLabelClass = "formidable-rdrstd-label";
		var $bVisible = TRUE;	// should the renderlet be visible in the page ?

		var $sCustomRootHtml = FALSE;

		var $aStatics = array(
			"type" => AMEOSFORMIDABLE_VALUE_NOT_SET,
			"namewithoutprefix" => AMEOSFORMIDABLE_VALUE_NOT_SET,
			"absName" => array(),
			"elementHtmlName" => array(),
			"elementHtmlNameWithoutFormId" => array(),
			"elementHtmlId" => array(),
			"elementHtmlIdWithoutFormId" => array(),
			"hasParent" => AMEOSFORMIDABLE_VALUE_NOT_SET,
			"hasSubmitted" => array(),
			"dbridge_getSubmitterAbsName" => AMEOSFORMIDABLE_VALUE_NOT_SET,
			"rawpostvalue" => array(),
			"dsetMapping" => AMEOSFORMIDABLE_VALUE_NOT_SET,
			"i18n_shouldNotTranslate" => AMEOSFORMIDABLE_VALUE_NOT_SET,
		);

		var $aEmptyStatics = array();

		function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = FALSE) {
			parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);
			$this->aEmptyStatics = $this->aStatics;

			$this->initDataSource();
			if(($this->oDataBridge =& $this->getDataBridgeAncestor()) !== FALSE) {
				$this->bHasDataBridge = TRUE;
				$this->oDataBridge->aDataBridged[] = $this->getAbsName();
				#if($this->oDataBridge->dbridge_setValue())
			}

			$this->initChilds();
			$this->initProgEvents();
			#$this->initDependancies();
		}

		function initChilds($bReInit = FALSE) {
			if($this->mayHaveChilds() && $this->hasChilds()) {

				$sXPath = $this->sXPath . "childs/";
				$this->aChilds =& $this->oForm->_makeRenderlets(
					$this->oForm->_navConf($sXPath),
					$sXPath,
					TRUE,	// $bChilds ?
					$this,
					$bReInit	// set to TRUE if existing renderlets need to be overwritten
				);					// used in rdt_modalbox->majixShowBox() for re-init before render
			}
		}

		function initDependancies() {
			if(($sDeps = $this->_navConf("/dependson")) !== FALSE) {
				$aDeps = t3lib_div::trimExplode(",", trim($sDeps));
				
				reset($aDeps);
				while(list(, $sDep) = each($aDeps)) {
					
					if(array_key_exists($sDep, $this->oForm->aORenderlets)) {
						$this->aDependsOn[] = $sDep;
						$this->oForm->aORenderlets[$sDep]->aDependants[] = $this->getAbsName();
					} else {
						$mRes = $this->oForm->resolveForInlineConf($sDep);
						if($this->oForm->isRenderlet($mRes)) {
							$sAbsName = $mRes->getAbsName();
							$this->aDependsOn[] = $sAbsName;
							$this->oForm->aORenderlets[$sAbsName]->aDependants[] = $this->getAbsName();
						}
					}
				}
			}
		}

		function cleanStatics() {
			if($this->mayHaveChilds() && $this->hasChilds()) {
				$aChildsKeys = array_keys($this->aChilds);
				reset($aChildsKeys);
				while(list(, $sKey) = each($aChildsKeys)) {
					$this->aChilds[$sKey]->cleanStatics();
				}
			}
			
			unset($this->aStatics["absName"]);
			unset($this->aStatics["elementHtmlName"]);
			unset($this->aStatics["elementHtmlNameWithoutFormId"]);
			unset($this->aStatics["elementHtmlId"]);
			unset($this->aStatics["elementHtmlIdWithoutFormId"]);
			unset($this->aStatics["hasSubmitted"]);
			unset($this->aStatics["dbridge_getSubmitterAbsName"]);
			unset($this->aStatics["i18n_shouldNotTranslate"]);
			
			$this->aStatics["absName"] = $this->aEmptyStatics["absName"];
			$this->aStatics["elementHtmlName"] = $this->aEmptyStatics["elementHtmlName"];
			$this->aStatics["elementHtmlNameWithoutFormId"] = $this->aEmptyStatics["elementHtmlNameWithoutFormId"];
			$this->aStatics["elementHtmlId"] = $this->aEmptyStatics["elementHtmlId"];
			$this->aStatics["elementHtmlIdWithoutFormId"] = $this->aEmptyStatics["elementHtmlIdWithoutFormId"];
			$this->aStatics["hasSubmitted"] = $this->aEmptyStatics["hasSubmitted"];
			$this->aStatics["dbridge_getSubmitterAbsName"] = $this->aEmptyStatics["dbridge_getSubmitterAbsName"];
			$this->aStatics["dsetMapping"] = $this->aEmptyStatics["dsetMapping"];
			$this->aStatics["i18n_shouldNotTranslate"] = $this->aEmptyStatics["i18n_shouldNotTranslate"];
		}

		function doBeforeIteration(&$oIterating) {
			$this->cleanStatics();
		}

		function doAfterIteration() {
			$this->cleanStatics();
		}

		function doBeforeIteratingRender(&$oIterating) {

			//$this->cleanStatics();

			if($this->mayBeDataBridge()) {
				$this->initDatasource();
				$this->processDataBridge();
			}
		}

		function doAfterIteratingRender(&$oIterating) {
		}

		function doBeforeNonIteratingRender(&$oIterating) {
			$this->cleanStatics();

			if(!$this->hasParent() && $this->mayBeDataBridge()) {
				#$this->initDatasource();
				$this->processDataBridge();
			}
		}

		function doAfterNonIteratingRender(&$oIterating) {

		}
		
		function doBeforeListRender(&$oListObject) {
			// nothing here
		}
		
		function doAfterListRender(&$oListObject) {
			$this->includeLibs();
			$this->includeScripts(array(
				"iterating" => false,
				"iterated" => true,
				"iterator" => $oListObject->_getElementHtmlId()
			));
		}

		// abstract method
		function initDataSource() {

			if(!$this->mayBeDataBridge()) {
				return FALSE;
			}

			if(($sDs = $this->_navConf("/datasource/use")) !== FALSE) {

				if(!array_key_exists($sDs, $this->oForm->aODataSources)) {
					$this->oForm->mayday("renderlet:" . $this->_getType() . "[name='" . $this->getName() . "'] bound to unknown datasource '<b>" . $sDs . "</b>'.");
				}

				$this->oDataSource =& $this->oForm->aODataSources[$sDs];
				$this->bIsDataBridge = TRUE;

				if((($oIterableAncestor = $this->getIterableAncestor()) !== FALSE) && !$oIterableAncestor->isIterating()) {
					// is iterable but not iterating, so no datasource initialization
					return FALSE;
				}

				if(($sKey = $this->dbridge_getPostedSignature(TRUE)) !== FALSE) {
					// found a posted signature for this databridge
						// using given signature
				} elseif(($sKey = $this->_navConf("/datasource/key")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($sKey)) {
						$sKey = $this->callRunneable($sKey);
						if($sKey === FALSE || is_null($sKey)) {
							$sKey = "new";
						}
					}
				} else {
					$sKey = "new";
				}

				if($sKey === FALSE) {
					$this->oForm->mayday("renderlet:" . $this->_getType() . "[name='" . $this->getName() . "'] bound to datasource '<b>" . $sDs . "</b>' is missing a valid key to connect to data.");
				}

				$sSignature = $this->oDataSource->initDataSet($sKey);
				$this->aDataSetSignatures[$this->_getElementHtmlId()] = $sSignature;
			}
		}

		function hasParent() {
			return ($this->oRdtParent !== FALSE && is_object($this->oRdtParent)/* && is_a($this->oRdtParent, "formidable_mainrenderlet")*/);
		}

		function isChildOf($sRdtName) {
			return ($this->hasParent() && ($this->oRdtParent->getAbsName() === $sRdtName));
		}

		function isDescendantOf($sRdtName) {

			if($this->hasParent() && $sRdtName !== $this->getAbsName()) {

				$sCurrent = $this->getAbsName();

				if($this->oForm->aORenderlets[$sCurrent]->isChildOf($sRdtName) === TRUE) {
					return TRUE;
				}

				while(array_key_exists($sCurrent, $this->oForm->aORenderlets) && $this->oForm->aORenderlets[$sCurrent]->hasParent()) {

					$sCurrent = $this->oForm->aORenderlets[$sCurrent]->oRdtParent->getAbsName();
					if(array_key_exists($sCurrent, $this->oForm->aORenderlets) && $this->oForm->aORenderlets[$sCurrent]->isChildOf($sRdtName)) {
						return TRUE;
					}
				}
			}

			return FALSE;
		}

		function isAncestorOf($sAbsName) {
			if(array_key_exists($sAbsName, $this->oForm->aORenderlets) && $this->oForm->aORenderlets[$sAbsName]->isDescendantOf($this->getAbsName())) {
				return TRUE;
			}

			return FALSE;
		}

		function hasBeenPosted() {
			return $this->bHasBeenPosted;
		}

		function hasBeenSubmitted() {
			if($this->hasDataBridge()) {
				//debug("hasBeenSubmitted", $this->getName());
				return FALSE;
			}

			return $this->hasBeenPosted();
		}

		function hasBeenDeeplyPosted() {

			$bHasBeenPosted = $this->hasBeenPosted();

			if(!$bHasBeenPosted && $this->mayHaveChilds() && $this->hasChilds()) {
				$aChildKeys = array_keys($this->aChilds);
				reset($aChildKeys);
				while(!$bHasBeenPosted && (list(, $sKey) = each($aChildKeys))) {
					#$sAbsName = $this->aChilds[$sKey]->getAbsName();
					$bHasBeenPosted = $bHasBeenPosted && $this->aChilds[$sKey]->hasBeenDeeplyPosted();
				}
			}

			return $bHasBeenPosted;
		}

		function hasBeenDeeplySubmitted() {
			if($this->hasDataBridge()) {
				return FALSE;
			}

			return $this->hasBeenDeeplyPosted();
		}

		function isAnonymous() {
			return $this->bAnonymous !== FALSE;
		}
		
		function getPostFlag() {
			return "<input type=\"hidden\" id=\"postflag." . $this->_getElementHtmlId() . "\" name=\"postflag[" . $this->_getElementHtmlId() . "]\" value=\"1\" />";
		}
		function checkPoint(&$aPoints) {
			/* nothing by default */
		}

		function initProgEvents() {
			if(($aEvents = $this->_getProgServerEvents()) !== FALSE) {

				reset($aEvents);
				while(list($sEvent, $aEvent) = each($aEvents)) {

					if($aEvent["runat"] == "server") {

						$aDefinedEvent = $aEvent;

						$sEventId = $this->oForm->_getServerEventId($this->_getName(), $aEvent);	// before any modif to get the *real* eventid

						$aNeededParams = array();

						if(array_key_exists("params", $aEvent) && is_string($aEvent["params"])) {
							$aNeededParams = t3lib_div::trimExplode(",", $aEvent["params"]);
							$aEvent["params"] = $aNeededParams;
						}

						$this->oForm->aServerEvents[$sEventId] = array(
							"eventid" => $sEventId,
							"trigger" => $sEvent,
							"when" => (array_key_exists("when", $aEvent) ? $aEvent["when"] : "after-init"),	// default when : end
							"event" => $aEvent,
							"params" => $aNeededParams,
							"raw" => $aDefinedEvent,
						);
					}

				}
			}
		}

		function _getProgServerEvents() {
			return FALSE;
		}

		function render($bForceReadonly = FALSE) {
			if((($oIterating = $this->getIteratingAncestor()) !== FALSE)) {
				$this->doBeforeIteratingRender($oIterating);
			} else {
				$this->doBeforeNonIteratingRender($oIterating);
			}

			if($bForceReadonly === TRUE || $this->_readonly()) {
				$mRendered = $this->_renderReadOnly();
			} else {
				$mRendered = $this->_render();
			}
			
			$this->includeLibs();
			
			if(!$this->bCustomIncludeScript) {
				$this->includeScripts();
			}
			
			$this->attachCustomEvents();

			if($oIterating !== FALSE) {
				$this->doAfterIteratingRender($oIterating);
			} else {
				$this->doAfterNonIteratingRender($oIterating);
			}
			
			if($this->displayOnlyIfJs() === TRUE) {
				$sJson = $this->oForm->oJson->encode(
					$mRendered["__compiled"]
				);

				$sId = $this->_getElementHtmlId() . "_unobtrusive";
				$mRendered["__compiled"] = "<span id='" . $sId . "'></span>";

				$this->oForm->attachInitTaskUnobtrusive('
					if(Formidable.getElementById("' . $sId . '")) {Formidable.getElementById("' . $sId . '").innerHTML=' . $sJson . ';}
				');
			}
			
			return $mRendered;
		}

		function _render() {
			return $this->getLabel();
		}

		function renderWithForcedValue($mValue) {
			$this->forceValue($mValue);
			$mRendered = $this->render();
			$this->unForceValue();

			return $mRendered;
		}

		function forceValue($mValue) {
			$this->mForcedValue = $mValue;
			$this->bForcedValue = TRUE;
		}

		function unForceValue() {
			$this->mForcedValue = FALSE;
			$this->bForcedValue = FALSE;
		}

		function renderReadOnlyWithForcedValue($mValue) {
			$this->forceValue($mValue);
			$mRendered = $this->render(TRUE);
			$this->unForceValue();
			return $mRendered;
		}

		function _renderReadOnly() {

			$mValue = $this->getValue();
			$mHuman = $this->_getHumanReadableValue($mValue);
			if($this->defaultFalse('/nl2br')) {
				$mHuman = nl2br($mHuman);
			}
			//$sPostFlag = "<input type=\"hidden\" id=\"" . $this->_getElementHtmlId() . "\" name=\"" . $this->_getElementHtmlName() . "\" value=\"1\" />";
			$this->getPostFlag();
			$sCompiled = $this->wrapForReadOnly($mHuman) . $sPostFlag;

			$mHtml = array(
				"__compiled" => $sCompiled,
				"additionalinputparams" => $this->_getAddInputParams($sId),
				"value" => $mValue,
				"value." => array(
					"nl2br" => nl2br($mValue),
					"humanreadable" => $mHuman,
				)
			);
			
			if(($sListHeader = $this->_navConf("/listheader")) !== FALSE) {
				$mHtml["listheader"] = $this->oForm->_getLLLabel($sListHeader);
			}

			if(!is_array($mHtml["__compiled"])) {
				$mHtml["__compiled"] = $this->_displayLabel($this->getLabel()) . $mHtml["__compiled"];
			}
			
			$this->includeLibs();

			return $mHtml;
		}

		function wrapForReadOnly($sHtml) {
			$sAddParams = '';
			if(($sStyle = trim($this->_getStyle(FALSE, ''))) !== "") {
				$sAddParams = ' style="' . $sStyle . '"';
			}
			
			return "<span class=\"readonly\" id=\"" . $this->_getElementHtmlId() . "\"" . $sAddParams . ">" . $sHtml . "</span>";
		}

		function _displayLabel($sLabel) {
			if($this->oForm->oRenderer->bDisplayLabels) {
				return $this->getLabelTag($sLabel);
			}

			return "";
		}

		function getLabel($sLabel = FALSE, $sDefault=FALSE) {
			$sRes = "";

			if($sLabel === FALSE) {
				if(($sLabel = $this->_navConf("/label")) !== FALSE) {
					$sRes = $this->oForm->_getLLLabel($sLabel);
				} else {
					if($this->oForm->sDefaultLLLPrefix !== FALSE) {
						// trying to automap label
						$sKey = "LLL:" . $this->getAbsName() . ".label";
						$sRes = $this->oForm->_getLLLabel($sKey);
					}
				}
			} else {
				$sRes = $this->oForm->_getLLLabel($sLabel);
			}

			if(trim($sRes) === "" && $sDefault !== FALSE) {
				$sRes = $this->getLabel($sDefault);
			}
			
			if(($sLabelWrap = $this->_navConf("/labelwrap")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($sLabelWrap)) {
					$sLabelWrap = $this->callRunneable($sLabelWrap);
				}
				
				if(!$this->oForm->isFalseVal($sLabelWrap)) {
					$sRes = str_replace("|", $sRes, $sLabelWrap);
				}
			}
			
			$this->sGenerateLabel = "" . $sRes;
			return $this->sGenerateLabel;
		}
		
		function getGenerateLabel() {
			return $this->sGenerateLabel;
		}

		function getLabelTag($sLabel) {

			$sElementHtmlId = $this->_getElementHtmlId();
			$sId = $sElementHtmlId . "_label";
			$aClasses = array();
			
			if($this->oForm->oRenderer->sDefaultLabelClass !== '') {
				$aClasses[] = $this->oForm->oRenderer->sDefaultLabelClass;
			}
			
			$aClasses[] = $sId;

			$forAttribute = !$this->_readOnly() ? ' for="' . $sElementHtmlId . '"' : '';

			if(($sLabelClass = $this->_navConf("/labelclass")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($sLabelClass)) {
					$aClasses[] = $this->oForm->callRunneable($sLabelClass);
				}else{
					$aClasses[] = $sLabelClass;
				}
			}
			
			if($this->oForm->oRenderer->defaultFalse("/autordtclass") === TRUE) {
				$aClasses[] = $this->getName() . "_label";
			}
			
			$aClasses = array_unique($aClasses);

			if($this->hasError() && $this->oForm->oRenderer->bDisplayErrorClass) {
				$aError = $this->getError();
				$aClasses[] = "hasError";
				$aClasses[] = "hasError" . ucfirst($aError["info"]["type"]);
			}

			if(count($aClasses) === 0) {
				$sClassAttribute = "";
			} else {
				$sClassAttribute = implode(" ", $aClasses);
			}

			$sAddStyle = "";

			if($this->isVisible() === FALSE || $this->_shouldHideBecauseDependancyEmpty() || $this->_shouldHideBecauseDependancyNotEmpty()) {
				$sAddStyle = " style='display: none;' ";
			}

			if(trim($sLabel) !== "") {
				return "<label id='" . $sId . "'" . $sAddStyle . " class='" . $sClassAttribute . "'" . $forAttribute . ">" . $sLabel . "</label>";
			}

			return "";
		}

		function _getType() {

			if($this->aStatics["type"] === AMEOSFORMIDABLE_VALUE_NOT_SET) {
				$this->aStatics["type"] = $this->_navConf("/type");
			}

			return $this->aStatics["type"];
		}

		function _getName() {
			return $this->_getNameWithoutPrefix();
		}

		// alias for _getName()
		function getName() {
			return $this->_getName();
		}

		function _getNameWithoutPrefix() {
			return $this->aElement["name"];
		}

		function getId() {	// obsolete as of revision 1.0.193SVN
			return $this->getAbsName();
		}

		function getAbsName($sName = FALSE) {
			
			if($sName === FALSE) {
				$sName = $this->_getNameWithoutPrefix();
			}
			
			if(!array_key_exists($sName, $this->aStatics["absName"])) {
				if($this->hasParent()) {
					$this->aStatics["absName"][$sName] = $this->oRdtParent->getAbsName() . AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $sName . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
				} else {
					$this->aStatics["absName"][$sName] = $sName;
				}
			}
			
			return $this->aStatics["absName"][$sName];
		}

		function getNameRelativeTo(&$oRdt) {
			$sOurAbsName = $this->getAbsName();
			$sTheirAbsName = $oRdt->getAbsName();

			return $this->oForm->relativizeName($sOurAbsName, $sTheirAbsName);
		}

		function dbridged_getNameRelativeToDbridge() {
			return $this->getNameRelativeTo($this->oDataBridge);
		}

		function _getElementHtmlName($sName = FALSE) {

			if($sName === FALSE) {
				$sName = $this->_getNameWithoutPrefix();
			}

			if(!array_key_exists($sName, $this->aStatics["elementHtmlName"])) {
				$sPrefix = "";

				if($this->hasParent()) {
					$sPrefix = $this->oRdtParent->_getElementHtmlName();
				} else {
					$sPrefix = $this->oForm->formid;
				}

				$this->aStatics["elementHtmlName"][$sName] = $sPrefix . "[" . $sName . "]";
			}

			return $this->aStatics["elementHtmlName"][$sName];
		}

		function _getElementHtmlNameWithoutFormId($sName = FALSE) {

			if($sName === FALSE) {
				$sName = $this->_getNameWithoutPrefix();
			}

			if(!array_key_exists($sName, $this->aStatics["elementHtmlNameWithoutFormId"])) {
				if($this->hasParent()) {
					$this->aStatics["elementHtmlNameWithoutFormId"][$sName] = $this->oRdtParent->_getElementHtmlNameWithoutFormId() . "[" . $sName . "]";
				} else {
					$this->aStatics["elementHtmlNameWithoutFormId"][$sName] = $sName;
				}
			}

			return $this->aStatics["elementHtmlNameWithoutFormId"][$sName];
		}

		function _getElementHtmlId($sId = FALSE) {

			if($sId === FALSE) {
				$sId = $this->_getNameWithoutPrefix();
			}

			if(!array_key_exists($sId, $this->aStatics["elementHtmlId"])) {
				$sPrefix = "";

				if($this->hasParent()) {
					$sPrefix = $this->oRdtParent->_getElementHtmlId();
					if($this->oRdtParent->_getType() === "LISTER" && $this->oRdtParent->isIterating()) {

						if(!empty($this->oRdtParent->__aCurRow)) {
							$sPrefix .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $this->oRdtParent->__aCurRow["uid"] . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
						}
					}
				} else {
					$sPrefix = $this->oForm->formid;
				}

				$this->aStatics["elementHtmlId"][$sId] = $sPrefix . AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $sId . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
			}

			return $this->aStatics["elementHtmlId"][$sId];
		}

		function _getElementHtmlIdWithoutFormId($sId = FALSE) {

			if($sId === FALSE) {
				$sId = $this->_getNameWithoutPrefix();
			}

			if(!array_key_exists($sId, $this->aStatics["elementHtmlIdWithoutFormId"])) {
				$sPrefix = "";

				if($this->hasParent()) {
					$sPrefix = $this->oRdtParent->_getElementHtmlIdWithoutFormId();
					
					if($this->oRdtParent->isIterating() && !empty($this->oRdtParent->__aCurRow)) {
						$sPrefix .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $this->oRdtParent->__aCurRow["uid"] . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
					}
					
					$this->aStatics["elementHtmlIdWithoutFormId"][$sId] = $sPrefix . AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $sId . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
					
				} else {
					$this->aStatics["elementHtmlIdWithoutFormId"][$sId] = $sId;
				}
			}
			
			return $this->aStatics["elementHtmlIdWithoutFormId"][$sId];
		}

		function &getIterableAncestor() {
			if($this->hasParent()) {
				if($this->oRdtParent->isIterable()) {
					return $this->oRdtParent;
				} else {
					return $this->oRdtParent->getIterableAncestor();
				}
			}

			return FALSE;
		}

		function &getIteratingAncestor() {

			if($this->hasParent()) {
				if($this->oRdtParent->isIterable() && $this->oRdtParent->isIterating()) {
					return $this->oRdtParent;
				} else {
					return $this->oRdtParent->getIteratingAncestor();
				}
			}

			return FALSE;
		}

		function &getDataBridgeAncestor() {

			if($this->hasParent()) {
				if($this->oRdtParent->isDataBridge()) {
					return $this->oRdtParent;
				} else {
					return $this->oRdtParent->getDataBridgeAncestor();
				}
			}

			return FALSE;
		}
		
		function &getNoDomAtLoadAncestor() {

			if($this->hasParent()) {
				if(!$this->oRdtParent->hasDomAtLoad()) {
					return $this->oRdtParent;
				} else {
					return $this->oRdtParent->getNoDomAtLoadAncestor();
				}
			}

			return FALSE;
		}
		
		function hasDomAtLoad() {
			return TRUE;
		}

		function _getElementCssId($sId = FALSE) {
			return str_replace(
				array("."),
				array("\."),
				$this->_getElementHtmlId($sId)
			);
		}




		function __getDefaultValue() {

			$mValue = $this->_navConf("/data/defaultvalue/");

			if(tx_ameosformidable::isRunneable($mValue)) {
				// here bug corrected thanks to Gary Wong @ Spingroup
				// see http://support.typo3.org/projects/formidable/m/typo3-project-formidable-defaultvalue-bug-in-text-renderlet-365454/
				$mValue = $this->callRunneable($mValue);
			}

			return $this->_substituteConstants($mValue);
		}

		function declareCustomValidationErrors() {

		}

		function __getValue() {

			if(($mValue = $this->_navConf("/data/value/")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mValue)) {
					$mValue = $this->oForm->callRunneable($mValue);
				}
			}

			return $this->_substituteConstants($mValue);
		}

		function setValue($mValue) {

			$sAbsName = $this->getAbsName();
			$sAbsPath = str_replace(".", "/", $sAbsName);
			
			$this->oForm->setDeepData(
				$sAbsPath,
				$this->oForm->oDataHandler->__aFormData,
				$mValue
			);
			
			$this->forceValue($mValue);
		}

		function _getListValue() {

			$mValue = $this->_navConf("/data/listvalue/");

			if(is_array($mValue)) {

				// on verifie si on doit appeler un userobj pour recuperer la valeur par defaut

				if(tx_ameosformidable::isRunneable($mValue)) {
					$mValue = $this->callRunneable($mValue);
				} else {
					$mValue = "";
				}
			}

			return $this->_substituteConstants($mValue);
		}

		function _getValue() {
			$this->oForm->mayday("_getValue() is deprecated");
			if($this->bForcedValue === TRUE) {
				return $this->mForcedValue;
			} else {
				return $this->oForm->oDataHandler->getRdtValue(
					$this->getAbsName()
				);
			}
		}
		
		function hasRelationMM() {
			return ($this->getRelationMMTable() !== FALSE);
		}
		
		function getRelationMMTable() {
			if(($sMMTable = $this->_navConf("/data/relation/mm")) !== FALSE && is_string($sMMTable) && trim($sMMTable) !== "") {
				return trim($sMMTable);
			}
			
			return FALSE;
		}

		function getValue() {
			return $this->oForm->oDataHandler->getRdtValue(
				$this->getAbsName()
			);
		}
		
		function getValueForHtml($mValue = FALSE) {
			if($mValue === FALSE) {
				$mValue = $this->getValue();	
			}
			
			if(is_string($mValue)) {
				return $this->oForm->sanitizeStringForTemplateEngine(
					htmlspecialchars($mValue)
				);
			}
			
			return $mValue;
		}

		function refreshValue() {
			$this->setValue(
				$this->oForm->oDataHandler->getRdtValue_noSubmit_noEdit(
					$this->getAbsName()
				)
			);
		}

		function _substituteConstants($sValue) {

			if($sValue === "CURRENT_TIMESTAMP") {
				$sValue = time();
			} elseif($sValue === "CURRENT_PAGEID") {
				// front end only
				$sValue = $GLOBALS["TSFE"]->id;
			} elseif($sValue === "CURRENT_USERID") {
				// front end only
				$sValue = $GLOBALS["TSFE"]->fe_user->user["uid"];
			}

			return $sValue;
		}

		function _getAddInputParamsArray($aAdditional = array()) {

			$aAddParams = array();

			if(!is_array($aAdditional)) {
				$aAdditional = array();
			}

			if(!array_key_exists("style", $aAdditional)) {
				$aAdditional["style"] = "";
			}

			if(($sClass = trim($this->_getClasses())) !== "") {
				$aAddParams[] = $sClass;
			}

			if(($sStyle = trim($this->_getStyle(FALSE, $aAdditional["style"]))) !== "") {
				$aAddParams[] = $sStyle;
			}

			if(($sCustom = trim($this->_getCustom())) !== "") {
				$aAddParams[] = $sCustom;
			}

			if(($sEvents = trim($this->_getEvents())) !== "") {
				$aAddParams[] = $sEvents;
			}

			/*
				disabled-property for renderlets patch by Manuel Rego Casasnovas
				@see http://lists.netfielders.de/pipermail/typo3-project-formidable/2007-December/000803.html
			*/

			if(($sDisabled = trim($this->_getDisabled())) !== "") {
				$aAddParams[] = $sDisabled;
			}

			if(($sTitle = $this->_navConf("/title")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($sTitle)) {
					$sTitle = $this->callRunneable($sTitle);
				}

				$sTitle = $this->oForm->_substLLLInHtml($sTitle);

				if(trim($sTitle) !== "") {
					$aAddParams[] = "title=\"" . strip_tags(str_replace("\"", "\\\"", $sTitle)) . "\"";
					if(($bTooltip = $this->defaultFalse("/tooltip")) !== FALSE) {

						$this->oForm->oJs->loadTooltip();
						$sId = $this->_getElementHtmlId();

						$sJsOptions = $this->oForm->array2json(array(
							"mouseFollow" => FALSE,
							"content" => $sTitle,
						));

						$sJs =<<<TOOLTIP

	new Tooltip(Formidable.f("{$this->oForm->formid}").o("{$sId}").domNode(), {$sJsOptions});

TOOLTIP;
						$this->oForm->attachPostInitTask(
							$sJs,
							$sId . " tooltip initialization"
						);
					}
				}
			}
			
			if(($sHtmlAutoComplete = $this->htmlAutocomplete()) !== "") {
				$aAddParams[] = $sHtmlAutoComplete;
			}

			#print_r($aAddParams);
			return $aAddParams;
		}

		function _getAddInputParams($aAdditional = array()) {

			$aAddParams = $this->_getAddInputParamsArray($aAdditional);
			
			if(count($aAddParams) > 0) {
				$sRes = " " . implode(" ", $aAddParams) . " ";
			} else {
				$sRes = "";
			}
			
			return $sRes;
		}

		function _getCustom($aConf = FALSE) {

			if(($mCustom = $this->_navConf("/custom/", $aConf)) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mCustom)) {
					$mCustom = $this->callRunneable($mCustom);
				}

				return " " . $mCustom . " ";
			}

			return "";
		}
		
		function _shouldHideBecauseDependancyEmpty() {
			$bOrZero = FALSE;
			if($this->defaultFalse("/hideifdependancyempty") === TRUE || (($bOrZero = $this->defaultFalse("/hideifdependancyemptyorzero")) === TRUE)) {
				if($this->hasDependancies()) {
					reset($this->aDependsOn);
					while(list(, $sKey) = each($this->aDependsOn)) {
						if(!array_key_exists($sKey, $this->oForm->aORenderlets) || $this->oForm->aORenderlets[$sKey]->isValueEmpty() || ($bOrZero === TRUE && (intval($this->oForm->aORenderlets[$sKey]->getValue()) === 0))) {
							return TRUE;
						}
					}
				}
			}
			
			return FALSE;
		}
		
		function _shouldHideBecauseDependancyNotEmpty() {
			if($this->defaultFalse("/hideifdependancynotempty") === TRUE) {
				if($this->hasDependancies()) {
					reset($this->aDependsOn);
					while(list(, $sKey) = each($this->aDependsOn)) {
						if(array_key_exists($sKey, $this->oForm->aORenderlets) && !$this->oForm->aORenderlets[$sKey]->isValueEmpty()) {
							return TRUE;
						}
					}
				}
			}
			
			return FALSE;
		}
		
		function _getStyleArray($aConf=FALSE, $sAddStyle="") {
			
			$aStyles = array();
			$sStyle = "";
			
			if(($mStyle = $this->_navConf("/style", $aConf)) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mStyle)) {
					$mStyle = $this->callRunneable($mStyle);
				}
			}
			
			if(is_string($mStyle)) {
				$sStyle = str_replace('"', "'", $mStyle);
				$aStyles = $this->explodeStyle($sStyle);
			} elseif(is_array($mStyle)) {
				$aStyles = $mStyle;
			}

			if($this->isVisible() === FALSE || $this->_shouldHideBecauseDependancyEmpty() || $this->_shouldHideBecauseDependancyNotEmpty()) {
				$aStyles["display"] = "none";
			}
			
			reset($aStyles);
			return $aStyles;
		}

		function explodeStyle($sStyle) {

			$aStyles = array();

			if(trim($sStyle) !== "") {
				$aTemp = t3lib_div::trimExplode(";", $sStyle);
				reset($aTemp);
				while(list($sKey,) = each($aTemp)) {
					if(trim($aTemp[$sKey]) !== "") {
						//$aStyleItem = t3lib_div::trimExplode(":", $aTemp[$sKey]);
						$iPosColon = strpos($aTemp[$sKey], ":");
						
						$aStyles[strtolower(trim(substr($aTemp[$sKey], 0, $iPosColon)))] = $this->oForm->evaluate_smartString(
							trim(
								substr($aTemp[$sKey], $iPosColon + 1)
							)
						);
					}
				}
			}

			reset($aStyles);
			return $aStyles;
		}
		
		function buildStyleProp($aStyles) {
			$aRes = array();
			
			reset($aStyles);
			while(list($sProp, $sVal) = each($aStyles)) {
				$aRes[] = $sProp . ": " . $sVal;	
			}
			
			reset($aRes);
			if(count($aRes) > 0) {
				return " style=\"" . implode("; ", $aRes) . ";\" ";
			}
			
			return "";			
		}
		
		function _getStyle($aConf = FALSE, $sAddStyle = "") {
			
			$aStyles = $this->_getStyleArray($aConf = FALSE, $sAddStyle = "");
			return $this->buildStyleProp($aStyles);
		}

		function isVisible() {
			return $this->bVisible && $this->defaultTrue("/visible");
		}
		
		function setVisible() {
			$this->bVisible = TRUE;
		}
		
		function setInvisible() {
			$this->bVisible = FALSE;
		}

		function isValueEmpty() {
			return trim($this->getValue()) === "";
		}

		function isDataEmpty() {
			return $this->isValueEmpty();
		}

		function _getClassesArray($aConf = FALSE, $bIsRdt = TRUE) {
			$aClasses = array();

			if(($mClass = $this->_navConf("/class/", $aConf)) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mClass)) {
					$mClass = $this->callRunneable($mClass);
				}

				if(is_string($mClass) && (trim($mClass) !== "")) {
					$aClasses = t3lib_div::trimExplode(" ", $mClass);
				}
			}
			
			if($bIsRdt === TRUE) {
				if($this->oForm->oRenderer->defaultFalse("autordtclass") === TRUE) {
					$aClasses[] = $this->getName();
				}

				if($this->hasError() && $this->oForm->oRenderer->bDisplayErrorClass) {
					$aError = $this->getError();
					$aClasses[] = "hasError";
					$aClasses[] = "hasError" . ucfirst($aError["info"]["type"]);
				}
			}

			reset($aClasses);
			return $aClasses;
		}

		function _getClasses($aConf = FALSE, $bIsRdt = TRUE) {

			$aClasses = $this->_getClassesArray($aConf, $bIsRdt);

			if(count($aClasses) === 0) {
				$sClassAttribute = "";
			} else {
				$sClassAttribute = " class=\"" . implode(" ", $aClasses) . "\" ";
			}

			return $sClassAttribute;
		}

		/*
			disabled-property for renderlets patch by Manuel Rego Casasnovas
			@see http://lists.netfielders.de/pipermail/typo3-project-formidable/2007-December/000803.html
		*/

		function _getDisabled() {

			if($this->defaultFalse("/disabled/")) {
				return " disabled=\"disabled\" ";
			}

			return "";
		}

		function fetchServerEvents() {
			$aEvents = array();
			$aGrabbedEvents = $this->oForm->__getEventsInConf($this->aElement);
			reset($aGrabbedEvents);
			while(list(, $sEvent) = each($aGrabbedEvents)) {
				if(($mEvent = $this->_navConf("/" . $sEvent . "/")) !== FALSE) {
					//debug($mEvent);

					if(is_array($mEvent)) {

						$sRunAt = trim(strtolower((array_key_exists("runat", $mEvent) && in_array($mEvent["runat"], array("inline", "client", "ajax", "server"))) ? $mEvent["runat"] : "client"));

						if(($iPos = strpos($sEvent, "-")) !== FALSE) {
							$sEventName = substr($sEvent, 0, $iPos);
						} else {
							$sEventName = $sEvent;
						}

						if($sRunAt === "server") {
							$sEventId = $this->oForm->_getServerEventId(
								$this->getAbsName(),
								array($sEventName => $mEvent)
							);	// before any modif to get the *real* eventid

							$aNeededParams = array();

							if(array_key_exists("params", $mEvent)) {
								if(is_string($mEvent["params"])) {
									
									$aTemp = t3lib_div::trimExplode(",", $mEvent["params"]);
									reset($aTemp);
									while(list($sKey,) = each($aTemp)) {
										$aNeededParams[] = array(
											"get" => $aTemp[$sKey],
											"as" => FALSE,
										);
									}
								} else {
									// the new syntax
									// <params><param get="uid" as="uid" /></params>
									$aNeededParams = $mEvent["params"];
								}
							}
							
							reset($aNeededParams);

							$sWhen = $this->oForm->_navConf("/when", $mEvent);
							if($sWhen === FALSE) {
								$sWhen = "end";
							}

							if(!in_array($sWhen, $this->oForm->aAvailableCheckPoints)) {
								$this->oForm->mayday("SERVER EVENT on <b>" . $sEventName . " " . $this->getAbsName() . "</b>: defined checkpoint (when='" . $sWhen . "') does not exists; Available checkpoints are: <br /><br />" . t3lib_div::view_array($this->oForm->aAvailableCheckPoints));
							}

							$bEarlyBird = FALSE;

							if(array_search($sWhen, $this->oForm->aAvailableCheckPoints) < array_search("after-init-renderlets", $this->oForm->aAvailableCheckPoints)) {
								if($sWhen === "start") {
									#debug("ici");
									$bEarlyBird = TRUE;
								} else {
									$this->oForm->mayday("SERVER EVENT on <b>" . $sEventName . " " . $this->getAbsName() . "</b>: defined checkpoint (when='" . $sWhen . "') triggers too early in the execution to be catchable by a server event.<br />The first checkpoint available for server event is <b>after-init-renderlets</b>. <br /><br />The full list of checkpoints is: <br /><br />" . t3lib_div::view_array($this->oForm->aAvailableCheckPoints));
								}
							}

							$this->oForm->aServerEvents[$sEventId] = array(
								"name" => $this->getAbsName(),
								"eventid" => $sEventId,
								"trigger" => $sEventName,
								"when" => $sWhen,	// default when : end
								"event" => $mEvent,
								"params" => $aNeededParams,
								"raw" => array($sEventName => $mEvent),
								"earlybird" => $bEarlyBird,
							);
						}
					}
				}
			}
		}

		function _getEventsArray() {

			$aEvents = array();

			$aGrabbedEvents = $this->oForm->__getEventsInConf($this->aElement);

			reset($aGrabbedEvents);
			while(list(, $sEvent) = each($aGrabbedEvents)) {
				
				if(($mEvent = $this->_navConf("/" . $sEvent . "/")) !== FALSE) {
					
					if(is_array($mEvent)) {

						$sRunAt = (array_key_exists("runat", $mEvent) && in_array($mEvent["runat"], array("js", "inline", "client", "ajax", "server"))) ? $mEvent["runat"] : "client";

						if(($iPos = strpos($sEvent, "-")) !== FALSE) {
							$sEventName = substr($sEvent, 0, $iPos);
						} else {
							$sEventName = $sEvent;
						}

						switch($sRunAt) {
							case "server": {
								$sEventId = $this->oForm->_getServerEventId(
									$this->getAbsName(),
									array($sEventName => $mEvent)
								);

								$aTempListData = $this->oForm->oDataHandler->_getListData();

								$aEvent = $this->oForm->oRenderer->_getServerEvent(
									$this->getAbsName(),
									$mEvent,
									$sEventId,
									($aTempListData === FALSE ? array() : $aTempListData)
								);

								break;
							}
							case "ajax": {

								$sEventId = $this->oForm->_getAjaxEventId(
									$this->getAbsName(),
									array($sEventName => $mEvent)
								);

								//debug($sEventId, $this->_getName() . ":" . $sEvent);


								$aTemp = array(
									"name" => $this->getAbsName(),
									"eventid" => $sEventId,
									"trigger" => $sEventName,
									"cache" => $this->oForm->defaultTrue("/cache", $mEvent),
									"event" => $mEvent,
								);

								if(!array_key_exists($sEventId, $this->oForm->aAjaxEvents)) {
									$this->oForm->aAjaxEvents[$sEventId] = $aTemp;
								}

								if($sEvent === "onload") {
									if(!array_key_exists($sEventId, $this->oForm->aOnloadEvents["ajax"])) {
										$this->oForm->aOnloadEvents["ajax"][$sEventId] = $aTemp;
									}
								}


								if($this->oForm->defaultFalse("/needparent", $mEvent) === TRUE) {
									$this->oForm->bStoreParentInSession = TRUE;
								}

								$aEvent = $this->oForm->oRenderer->_getAjaxEvent(
									$this,
									$mEvent,
									$sEventName
								);

								// an ajax event is declared
								// we have to store this form in session
								// for serving ajax requests

								$this->oForm->bStoreFormInSession = TRUE;

								$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxevent"][$this->_getSessionDataHashKey()] = array(
									"requester" => array(
										"name" => "tx_ameosformidable",
										"xpath" => "/",
									),
								);

								break;
							}
							case "js": {

								if(tx_ameosformidable::isRunneable($mEvent)) {

									$aEvent = $this->callRunneable($mEvent);

									$aEvent = $this->oForm->oRenderer->_getClientEvent(
										$this->_getElementHtmlId(),
										$mEvent,
										$aEvent,
										$sEventName
									);
								}
								break;
							}
							case "client": {

								// array client mode event

								if($sEventName !== "onload") {

									if(tx_ameosformidable::isRunneable($mEvent)) {
										$aEvent = $this->callRunneable($mEvent);

										if(is_array($aEvent)) {
											// event is an array of tasks to execute on js objects
											$aEvent = $this->oForm->oRenderer->_getClientEvent(
												$this->_getElementHtmlId(),
												$mEvent,
												$aEvent,
												$sEventName
											);
										} else {
											// event has been converted from userobj to custom string event
										}

									} else {
										if(array_key_exists("refresh", $mEvent)) {
											$aEvent = $this->_getEventRefresh($mEvent["refresh"]);
										} elseif(array_key_exists("submit", $mEvent)) {
											$aEvent = $this->_getEventSubmit();
										}
									}
								} else {

									if(tx_ameosformidable::isRunneable($mEvent)) {
										$aEvent = $this->callRunneable($mEvent);
									} else {
										$aEvent = $mEvent;
									}

									$this->oForm->aOnloadEvents["client"]["onload:" . $this->_getElementHtmlIdWithoutFormId()] = array(
										"name" => $this->_getElementHtmlId(),
										"event" => $mEvent,
										"eventdata" => $aEvent
									);
								}
								break;
							}
							case "inline": {

								if(tx_ameosformidable::isRunneable($mEvent)) {
									$aEvent = $this->callRunneable($mEvent);
								} else {
									$aEvent = $mEvent["__value"];
								}

								break;
							}
						}
					} else {

						$aEvent = $mEvent;
						/*
						// custom string client mode event
						if(tx_ameosformidable::isRunneable($mEvent)) {
							$aEvent = $this->callRunneable($mEvent);
						} else {
						}*/
					}


					if($sEventName !== "onload" && !$this->isCustomEventHandler($sEventName)) {
												
						if(!$this->oForm->isDomEventHandler($sEventName)) {
							$sEventName = "formidable:" . $sEventName;
						}
						
						if(!array_key_exists($sEventName, $aEvents)) {
							$aEvents[$sEventName] = array();
						}
						
						$aEvents[$sEventName][] = $aEvent;
					} elseif($this->isCustomEventHandler($sEventName)) {
						$this->aCustomEvents[$sEventName][] = $aEvent;
					}
				}
			}

			if($this->aSkin && $this->skin_declaresHook("geteventsarray")) {

				$aEvents = $this->callRunneable(
					$this->aSkin["submanifest"]["hooks"]["geteventsarray"],
					array(
						"object" => &$this,
						"events" => $aEvents
					)
				);
			}
			
			reset($aEvents);
			return $aEvents;
		}

		function alterAjaxEventParams($aParams) {
			return $aParams;
		}

		function isCustomEventHandler($sEvent) {
			return in_array(
				$sEvent,
				$this->aPossibleCustomEvents
			);
		}

		function skin_declaresHook($sHook) {
			return	$this->aSkin
					&& array_key_exists("hooks", $this->aSkin["submanifest"])
					&& array_key_exists($sHook, $this->aSkin["submanifest"]["hooks"])
					&& tx_ameosformidable::isRunneable($this->aSkin["submanifest"]["hooks"][$sHook]);
		}

		function _getEvents() {

			$aHtml = array();
			$aEvents = $this->_getEventsArray();

			if(!empty($aEvents)) {
				reset($aEvents);
				while(list($sEvent, $aEvent) = each($aEvents)) {

					if($sEvent == "custom") {
						$aHtml[] = implode(" ", $aEvent);
					} else {
						if($this->oForm->bInlineEvents === TRUE) {
							$aHtml[] = $sEvent . "='" . $this->oForm->oRenderer->wrapEventsForInlineJs($aEvent) . "'";
						} else {
							$this->attachEvents($sEvent, $aEvent);
						}
					}
				}
			}

			return " " . implode(" ", $aHtml) . " ";
		}

		function attachEvents($sEvent, $aEvents) {
			
			if(($sEvent{0} === "o" && $sEvent{1} === "n")) {
				$sEventHandler = substr($sEvent, 2);
			} elseif(substr($sEvent, 0, 11) === "formidable:") {
				$sEventHandler = substr($sEvent, 0, 11) . substr($sEvent, 13);	// formidable:onsomething becomes formidable:something
			} else {
				$sEventHandler = $sEvent;
			}
			
			$sFunction = implode(";\n", $aEvents);
			$sElementId = $this->_getElementHtmlId();

			if($sEventHandler === "click" && $this->_getType() === "LINK") {
				$sAppend = "Formidable.stopEvent(event);";
			}

			$sEvents =<<<JAVASCRIPT
Formidable.f("{$this->oForm->formid}").unattachEvent("{$sElementId}", "{$sEventHandler}");
Formidable.f("{$this->oForm->formid}").attachEvent("{$sElementId}", "{$sEventHandler}", function(event) {{$sFunction};{$sAppend}});
JAVASCRIPT;

			if($this->oForm->__getEnvExecMode() === "EID") {
				$this->oForm->aRdtEventsAjax[$sEvent . "-" . $sElementId] = $sEvents;
			} else {
				$this->oForm->aRdtEvents[$sEvent . "-" . $sElementId] = $sEvents;
			}
		}
		
		function attachCustomEvents() {
			if($this->_readOnly()) {
				return;
			}
			$sHtmlId = $this->_getElementHtmlId();

			reset($this->aPossibleCustomEvents);
			while(list(, $sEvent) = each($this->aPossibleCustomEvents)) {
				if(array_key_exists($sEvent, $this->aCustomEvents)) {

					$sJs = implode("\n", $this->aCustomEvents[$sEvent]);
					$sScript =<<<JAVASCRIPT
Formidable.f("{$this->oForm->formid}").o("{$sHtmlId}").addHandler("{$sEvent}", function() {{$sJs}});
JAVASCRIPT;
					$this->sys_attachPostInitTask($sScript);
				}
			}
		}
		
		function sys_attachPostInitTask($sScript, $sDesc = "", $sKey = FALSE) {
			if(($oNoDomAncestor = $this->getNoDomAtLoadAncestor()) === FALSE) {
				$this->oForm->attachPostInitTask(
					$sScript,
					$sDesc,
					$sKey
				);
			} else {
				$oNoDomAncestor->attachPostInitTask(
					$sScript,
					$sDesc,
					$sKey
				);
			}
		}
		
		function sys_attachPreUninitTask($sScript, $sDesc = "", $sKey = FALSE) {
			if(($oNoDomAncestor = $this->getNoDomAtLoadAncestor()) === FALSE) {
				$this->oForm->attachPreUninitTask(
					$sScript,
					$sDesc,
					$sKey
				);
			} else {
				$oNoDomAncestor->attachPreUninitTask(
					$sScript,
					$sDesc,
					$sKey
				);
			}
		}

		function _getEventRefresh($mRefresh) {

			if(is_array($mRefresh)) {

				if(($mAction = $this->oForm->_navConf("/action", $mRefresh)) !== FALSE) {

					if(tx_ameosformidable::isRunneable($mAction)) {
						$mAction = $this->callRunneable($mAction);
					}
				}

				return $this->oForm->oRenderer->_getRefreshSubmitEvent(
					$this->oForm->_navConf("/formid", $mRefresh),
					$mAction
				);

			} elseif($this->oForm->isTrueVal($mRefresh) || empty($mRefresh)) {
				return $this->oForm->oRenderer->_getRefreshSubmitEvent();
			}
		}

		function _getEventSubmit() {
			return $this->oForm->oRenderer->_getFullSubmitEvent();
		}

		function _getSessionDataHashKey() {
			return $this->oForm->_getSessionDataHashKey();
		}

		function forceItems($aItems) {
			$this->aForcedItems = $aItems;
		}

		function _getItems($aConf = FALSE) {

			if($this->aForcedItems !== FALSE) {
				reset($this->aForcedItems);
				return $this->aForcedItems;
			}

			$elementname = $this->_getName();

			$aItems = array();
			$aXmlItems = array();
			$aUserItems = array();

			if(($bFromTCA = $this->defaultFalse("/data/items/fromtca")) === TRUE) {
				t3lib_div::loadTCA($this->oForm->oDataHandler->tableName());
				if(($aItems = $this->oForm->_navConf("columns/" . $this->_getName() . "/config/items", $GLOBALS["TCA"][$this->oForm->oDataHandler->tableName()])) !== FALSE) {
					$aItems = $this->oForm->_tcaToRdtItems($aItems);
				}
			} else {
				
				$aXmlItems = $this->_navConf("/data/items/", $aConf);

				if(!is_array($aXmlItems)) {
					$aXmlItems = array();
				}

				$aXmlItemsKeys = array_keys($aXmlItems);
				reset($aXmlItemsKeys);
				$iCurOffset = 0;
				while(list($iCurKey, $sKey) = each($aXmlItemsKeys)) {
					$aAutoItems = array();
					$iStep = 0;
					if($sKey{0} === "i" && $sKey{4} === "r" && substr($sKey, 0, 9) == "itemrange") {
						$aRangingItems = $aXmlItems[$sKey];
						$iFrom = intval($this->callRunneable($aRangingItems['from']));
						$iTo = intval($this->callRunneable($aRangingItems['to']));
						$iStep = intval($this->callRunneable($aRangingItems['step']));
						if($iStep == '0'){
							$iStep = 1;
						}
						$iStepping = 0;
						if($iFrom < $iTo){
							for($i=$iFrom; $i<=$iTo; $i++){
								$iStepping++;
								if($iStepping == '1'){
									$aAutoItems['auto-item-'.$i.'-'.$sKey] = array(
										'caption' => $i,	
										'value' => $i,	
										'__value' => false
									);
								}
								if($iStepping == $iStep){
									$iStepping = 0;
								}
							}
						}else{
							for($i=$iFrom; $i>=$iTo; $i--){
								$iStepping++;
								if($iStepping == '1'){
									$aAutoItems['auto-item-'.$i.'-'.$sKey] = array(
										'caption' => $i,	
										'value' => $i,	
										'__value' => false
									);
								}
								if($iStepping == $iStep){
									$iStepping = 0;
								}
							}
						}
						
						array_splice($aXmlItems, ($iCurKey + $iCurOffset), 1, $aAutoItems);
						$iCurOffset += count($aAutoItems) - 1;	// calculating new array_splice offset
					} else {
						#$aXmlItems[$sKey] = $aXmlItems;
					}
				}

				reset($aXmlItems);
				while(list($sKey, ) = each($aXmlItems)) {
					if(substr($sKey, 0, 8) == 'optgroup') {
						if(tx_ameosformidable::isRunneable($aXmlItems[$sKey]["label"])) {
							$aXmlItems[$sKey]["label"] = $this->callRunneable(
								$aXmlItems[$sKey]["label"]
							);
						}
						
						if(tx_ameosformidable::isRunneable($aXmlItems[$sKey]["class"])) {
							$aXmlItems[$sKey]["class"] = $this->callRunneable(
								$aXmlItems[$sKey]["class"]
							);
						}
						
						while(list($sChildKey, ) = each($aXmlItems[$sKey])) {
							if(substr($sChildKey, 0, 4) == 'item') {
								if(tx_ameosformidable::isRunneable($aXmlItems[$sKey][$sChildKey]["caption"])) {
									$aXmlItems[$sKey][$sChildKey]["caption"] = $this->callRunneable(
										$aXmlItems[$sKey][$sChildKey]["caption"]
									);
								}

								if(tx_ameosformidable::isRunneable($aXmlItems[$sKey][$sChildKey]["value"])) {
									$aXmlItems[$sKey][$sChildKey]["value"] = $this->callRunneable(
										$aXmlItems[$sKey][$sChildKey]["value"]
									);
								}

								if(array_key_exists("custom", $aXmlItems[$sKey][$sChildKey])) {
									if(tx_ameosformidable::isRunneable($aXmlItems[$sKey][$sChildKey]["custom"])) {
										$aXmlItems[$sKey][$sChildKey]["custom"] = $this->callRunneable(
											$aXmlItems[$sKey][$sChildKey]["custom"]
										);
									}
								}

								if(array_key_exists("labelcustom", $aXmlItems[$sKey][$sChildKey])) {
									if(tx_ameosformidable::isRunneable($aXmlItems[$sKey][$sChildKey]["labelcustom"])) {
										$aXmlItems[$sKey][$sChildKey]["labelcustom"] = $this->callRunneable(
											$aXmlItems[$sKey][$sChildKey]["labelcustom"]
										);
									}
								}

								if(trim($aXmlItems[$sKey][$sChildKey]["caption"]) === "") {
									$sDefaultLLLCaption = "LLL:" . $this->getAbsName() . ".items." . $aXmlItems[$sKey]["value"] . ".caption";
									
									if(($sTempCaption = $this->oForm->_getLLLabel($sDefaultLLLCaption)) !== "") {
										$aXmlItems[$sKey][$sChildKey]["caption"] = $sTempCaption;
									}
								}
							}
						}
						
					} else {
						if(tx_ameosformidable::isRunneable($aXmlItems[$sKey]["caption"])) {
							$aXmlItems[$sKey]["caption"] = $this->callRunneable(
								$aXmlItems[$sKey]["caption"]
							);
						}

						if(tx_ameosformidable::isRunneable($aXmlItems[$sKey]["value"])) {
							$aXmlItems[$sKey]["value"] = $this->callRunneable(
								$aXmlItems[$sKey]["value"]
							);
						}

						if(array_key_exists("custom", $aXmlItems[$sKey])) {
							if(tx_ameosformidable::isRunneable($aXmlItems[$sKey]["custom"])) {
								$aXmlItems[$sKey]["custom"] = $this->callRunneable(
									$aXmlItems[$sKey]["custom"]
								);
							}
						}

						if(array_key_exists("labelcustom", $aXmlItems[$sKey])) {
							if(tx_ameosformidable::isRunneable($aXmlItems[$sKey]["labelcustom"])) {
								$aXmlItems[$sKey]["labelcustom"] = $this->callRunneable(
									$aXmlItems[$sKey]["labelcustom"]
								);
							}
						}

						if(trim($aXmlItems[$sKey]["caption"]) === "") {
							$sDefaultLLLCaption = "LLL:" . $this->getAbsName() . ".items." . $aXmlItems[$sKey]["value"] . ".caption";
							
							if(($sTempCaption = $this->oForm->_getLLLabel($sDefaultLLLCaption)) !== "") {
								$aXmlItems[$sKey]["caption"] = $sTempCaption;
							}
						}
					}
#					$aXmlItems[$sKey]["caption"] = $this->oForm->_getLLLabel($aXmlItems[$sKey]["caption"]);
#					$aXmlItems[$sKey]["value"] = $this->_substituteConstants($aXmlItems[$sKey]["value"]);

				}

				reset($aXmlItems);
				$aUserItems = array();
				$aData = $this->_navConf("/data/", $aConf);
				if(tx_ameosformidable::isRunneable($aData)) {
					$aUserItems = $this->callRunneable($aData);
				}

				$aDb = $this->_navConf("/db/", $aConf);
				if (is_array($aDb)) {
					// Get database table
					if(($mTable = $this->_navConf("/db/table/", $aConf)) !== FALSE) {
						if(tx_ameosformidable::isRunneable($mTable)) {
							$mTable = $this->callRunneable($mTable);
						}
					}

					// Get value field, otherwise uid will be used as value
					if(($mValueField = $this->_navConf("/db/value/", $aConf)) !== FALSE) {
						if(tx_ameosformidable::isRunneable($mValueField)) {
							$mValueField = $this->callRunneable($mValueField);
						}
					} else {
						$mValueField = 'uid';
					}

					// Get where part
					if(($mWhere = $this->_navConf("/db/where/", $aConf)) !== FALSE) {
						if(tx_ameosformidable::isRunneable($mWhere)) {
							$mWhere = $this->callRunneable($mWhere);
						}
					}

					if (($this->defaultFalse("/db/static/", $aConf) === TRUE) &&
						(t3lib_extMgm::isLoaded('static_info_tables'))) {
						// If it is a static table
						$aDbItems = $this->__getItemsStaticTable($mTable, $mValueField, $mWhere);
					} else {
						// Get caption field
						if(($mCaptionField = $this->_navConf("/db/caption/", $aConf)) !== FALSE) {
							if(tx_ameosformidable::isRunneable($mCaptionField)) {
								$mCaptionField = $this->callRunneable($mCaptionField);
							}
						} else {
							if (($mCaptionField = $this->oForm->_navConf($mTable . "/ctrl/label", $GLOBALS["TCA"])) === FALSE) {
								$mCaptionField = 'uid';
							}
						}

						// Build the query with value and caption fields
						$sFields = $mValueField . " as value, " . $mCaptionField . " as caption";

						// Get the items
						$aDbItems = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($sFields, $mTable, $mWhere, "", "caption");
					}
				}

				$aItems = $this->_mergeItems($aXmlItems, $aUserItems);
				$aItems = $this->_mergeItems($aItems, $aDbItems);
			}	
			
			if(!is_array($aItems)) {
				$aItems = array();
			}

			if(($mAddBlank = $this->defaultFalseMixed("/addblank", $aConf)) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mAddBlank)) {
					$mAddBlank = $this->oForm->callRunneable($mAddBlank);
				}

				if($mAddBlank !== FALSE) {
					if($mAddBlank === TRUE) {
						$sCaption = "";
					} else {
						$sCaption = $this->oForm->getLLLabel($mAddBlank);
					}
				
					array_unshift($aItems, array(
						"caption" => $sCaption,
						"value" => "",
					));
				}
			}
			
			reset($aItems);
			while(list($sKey,) = each($aItems)) {
				$aItems[$sKey]["caption"] = $this->oForm->evaluate_smartString($aItems[$sKey]["caption"]);
				$aItems[$sKey]["value"] = $this->oForm->evaluate_smartString($aItems[$sKey]["value"]);
			}

			reset($aItems);
			return $aItems;
		}

		function _mergeItems($aXmlItems, $aUserItems) {

			if(!is_array($aXmlItems)) { $aXmlItems = array();}
			if(!is_array($aUserItems)) { $aUserItems = array();}

			$aItems = array_merge($aXmlItems, $aUserItems);

			if(is_array($aItems) && sizeof($aItems) > 0) {
				reset($aItems);
				return $aItems;
			}

			return array();
		}

		function _flatten($mData) {
			return $mData;
		}

		function _unFlatten($sData) {
			return $sData;
		}

		function _getHumanReadableValue($data) {
			return $data;
		}

		function _emptyFormValue($value) {
			if(is_array($value)) {
				return empty($value);
			} else {
				return (strlen(trim($value)) == 0);
			}
		}

		function _sqlSearchClause($sValue, $sFieldPrefix = "", $sFieldName = "", $bRec = TRUE) {

			if($sFieldName === "") {
				$sName = $this->_getName();
			} else {
				$sName = $sFieldName;
			}

			$sSql = $sFieldPrefix . $sName . " LIKE '%" . $this->oForm->db_quoteStr($sValue) . "%'";

			if($bRec === TRUE) {
				$sSql = $this->overrideSql(
					$sValue,
					$sFieldPrefix,
					$sName,
					$sSql
				);
			}

			return $sSql;
		}

		function overrideSql($sValue, $sFieldPrefix, $sFieldName, $sSql) {
			$sTable = $this->oForm->oDataHandler->tableName();

			if($sFieldName === "") {
				$sName = $this->_getName();
			} else {
				$sName = $sFieldName;
			}

			$aFields = array($sName);

			if(($aConf = $this->_navConf("/search/")) !== FALSE) {

				if(array_key_exists("onfields", $aConf)) {

					if(tx_ameosformidable::isRunneable($aConf["onfields"])) {
						$sOnFields = $this->callRunneable($aConf["onfields"]);
					} else {
						$sOnFields = $aConf["onfields"];
					}

					$aFields = t3lib_div::trimExplode(",", $sOnFields);
					reset($aFields);
				} else {
					$aFields = array($sName);
				}

				if(array_key_exists("overridesql", $aConf)) {

					if(tx_ameosformidable::isRunneable($aConf["overridesql"])) {
						$aSql = array();
						reset($aFields);
						while(list(, $sField) = each($aFields)) {

							$aSql[] = $this->callRunneable(
								$aConf["overridesql"],
								array(
									"name"		=> $sField,
									"table"		=> $sTable,
									"value"		=> $sValue,
									"prefix"	=> $sFieldPrefix,
									"defaultclause" => $this->_sqlSearchClause(
										$sValue,
										$sFieldPrefix,
										$sField,
										$bRec = FALSE
									),
								)
							);
						}

						if(!empty($aSql)) {
							$sSql = " (" . implode(" OR ", $aSql) . ") ";
						}
					} else {
						$sSql = $aConf["overridesql"];
					}

					$sSql = str_replace("|", $sValue, $sSql);
				} else {
					
					if(array_key_exists("mode", $aConf)) {
						if((is_array($aConf["mode"]) && array_key_exists("startswith", $aConf["mode"])) || $aConf["mode"] == "startswith") {
							// on effectue la recherche sur le dbut des champs avec LIKE A%

							$sValue = trim($sValue);
							$aSql = array();

							reset($aFields);
							while(list(, $sField) = each($aFields)) {
								if($sValue != "number") {
									$aSql[] = "(" . $sFieldPrefix . $sField . " LIKE '" . $this->oForm->db_quoteStr($sValue) . "%')";
								} else {
									for($k = 0; $k < 10; $k++) {
										$aSql[] = "(" . $sFieldPrefix . $sField . " LIKE '" . $this->oForm->db_quoteStr($k) . "%')";
									}
								}
							}

							if(!empty($aSql)) {
								$sSql = " (" . implode(" OR ", $aSql) . ") ";
							}

						} elseif((is_array($aConf["mode"]) && (array_key_exists("googlelike", $aConf["mode"]) || array_key_exists("orlike", $aConf["mode"]))) || $aConf["mode"] == "googlelike" || $aConf["mode"] == "orlike") {
							// on doit effectuer la recherche comme le ferait google :)
							// comportement : recherche AND sur "espaces", "+", ","
							//				: gestion des pluriels
							//				: recherche full text si "jj kjk jk"

							$sValue = str_replace(array(" ", ",", " and ", " And ", " aNd ", " anD ", " AnD ", " ANd ", " aND ", " AND ", " et ", " Et ", " eT ", " ET "), "+", trim($sValue));
							$aWords = t3lib_div::trimExplode("+", $sValue);

							if(is_array($aConf["mode"]) && array_key_exists("handlepluriels", $aConf["mode"])) {
								reset($aWords);
								while(list($sKey, $sWord) = each($aWords)) {
									if(strtolower(substr($sWord, -1, 1)) === "s") {
										$aWords[$sKey] = substr($sWord, 0, (strlen($sWord) - 1));
									}
								}
							}

							$aSql = array();

							reset($aFields);
							while(list(, $sField) = each($aFields)) {

								$aTemp = array();

								reset($aWords);
								while(list($iKey, $sWord) = each($aWords)) {
									$aTemp[] = $sFieldPrefix . $sField . " LIKE '%" . $this->oForm->db_quoteStr($sWord) . "%' ";
								}

								if(!empty($aTemp)) {
									if((is_array($aConf["mode"]) && array_key_exists("orlike", $aConf["mode"])) || $aConf["mode"] == "orlike") {
										$aSql[] = "(" . implode(" OR ", $aTemp) . ")";
									} else {
										$aSql[] = "(" . implode(" AND ", $aTemp) . ")";
									}
								}
							}

							if(!empty($aSql)) {
								$sSql = " (" . implode(" OR ", $aSql) . ") ";
							}
						} elseif((is_array($aConf["mode"]) && array_key_exists("and", $aConf["mode"])) || strtoupper($aConf["mode"]) == "AND") {
							$sValue = trim($sValue);
							$aSql = array();

							reset($aFields);
							while(list(, $sField) = each($aFields)) {
								$aSql[] = $this->_sqlSearchClause(
									$sValue,
									$sFieldPrefix,
									$sField,
									$bRec = FALSE
								);
							}

							if(!empty($aSql)) {
								$sSql = " (" . implode(" AND ", $aSql) . ") ";
							}
						} else {
							$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - given /search/mode does not exist; should be one of 'startswith', 'googlelike', 'orlike'");
						}
					} else {	/* default mode */

						$sValue = trim($sValue);
						$aSql = array();

						reset($aFields);
						while(list(, $sField) = each($aFields)) {
							$aSql[] = $this->_sqlSearchClause(
								$sValue,
								$sFieldPrefix,
								$sField,
								$bRec = FALSE
							);
						}

						if(!empty($aSql)) {
							$sSql = " (" . implode(" OR ", $aSql) . ") ";
						}
					}
				}
			}

			return $sSql;
		}

		function _renderOnly() {
			return $this->isTrue("/renderonly/") || $this->i18n_shouldNotTranslate();
		}

		function hasData() {
			return $this->defaultFalse("/hasdata") || ($this->_renderOnly() === FALSE);
		}

		function _activeListable() {		// listable as an active HTML FORM field or not in the lister
			return $this->defaultFalse("/activelistable/");
		}

		function _listable() {
			return $this->defaultTrue("/listable/");
		}

		function _translatable() {
			return $this->defaultTrue("/i18n/translate/");
		}

		function i18n_shouldNotTranslate() {
			if($this->aStatics["i18n_shouldNotTranslate"] !== AMEOSFORMIDABLE_VALUE_NOT_SET) {
				return $this->aStatics["i18n_shouldNotTranslate"];
			}
			
			$this->aStatics["i18n_shouldNotTranslate"] =
						$this->oForm->oDataHandler->i18n()									// DH handles i18n ?
					&&	!$this->oForm->oDataHandler->i18n_currentRecordUsesDefaultLang()	// AND record is NOT in default language
					&&	!$this->_translatable();											// AND renderlet is NOT translatable
								
			return $this->aStatics["i18n_shouldNotTranslate"];
		}

		function _hideableIfNotTranslatable() {
			return $this->defaultFalse("/i18n/hideifnottranslated");
		}

		function i18n_hideBecauseNotTranslated() {
			if($this->i18n_shouldNotTranslate()) {
				return $this->_hideableIfNotTranslatable();
			}

			return FALSE;
		}

		function _hasToValidateForDraft() {
			return $this->defaultFalse("/validatefordraft/");
		}

		function _debugable() {
			return $this->defaultTrue("/debugable/");
		}

		function _readOnly() {
			return ($this->isTrue("/readonly/")) || $this->i18n_shouldNotTranslate();
		}

		function _searchable() {
			return $this->defaultTrue("/searchable/");
		}

		function _virtual() {
			return in_array(
				$this->_getName(),
				$this->oForm->oDataHandler->__aVirCols
			);
		}

		// alias of _hasThrown(), for convenience
		function hasThrown($sEvent, $sWhen = FALSE) {
			return $this->_hasThrown($sEvent, $sWhen);
		}

		function _hasThrown($sEvent, $sWhen = FALSE) {

			$sEvent = strtolower($sEvent);
			if($sEvent{0} !== "o" || $sEvent{1} !== "n") {
				// events should always start with on
				$sEvent = "on" . $sEvent;
			}
			
			if(array_key_exists($sEvent, $this->aElement) && array_key_exists("runat", $this->aElement[$sEvent]) && $this->aElement[$sEvent]["runat"] == "server") {
				$aEvent = $this->aElement[$sEvent];
			} elseif(($aProgEvents = $this->_getProgServerEvents()) !== FALSE && array_key_exists($sEvent, $aProgEvents)) {
				$aEvent = $aProgEvents[$sEvent];
			} else {
				return FALSE;
			}
			
			if($sWhen === FALSE || $aEvent[$sEvent]["when"] == $sWhen) {

				$aP = $this->oForm->_getRawPost();
				
				if(array_key_exists("AMEOSFORMIDABLE_SERVEREVENT", $aP)) {
					if(array_key_exists($aP["AMEOSFORMIDABLE_SERVEREVENT"], $this->oForm->aServerEvents)) {
						$sEventId = $this->oForm->_getServerEventId(
							$this->getAbsName(),
							$this->oForm->aServerEvents[$aP["AMEOSFORMIDABLE_SERVEREVENT"]]["raw"]
						);
						
						return ($sEventId === $aP["AMEOSFORMIDABLE_SERVEREVENT"]);
					}
				}
			}

			return FALSE;
		}

		function includeLibs() {
			if($this->oForm->useJs()) {
				if($this->oForm->_getJsapi() == 'jquery') {
					if(isset($this->aJqueryLibs) && !empty($this->aJqueryLibs)) {
						reset($this->aJqueryLibs);
						while(list($sKey, $sLib) = each($this->aJqueryLibs)) {

							$this->oForm->additionalHeaderDataLocalScript(
								$this->sExtPath . $sLib,
								$sKey
							);
						}
					} elseif(!empty($this->aLibs)) {
						reset($this->aLibs);
						while(list($sKey, $sLib) = each($this->aLibs)) {

							$this->oForm->additionalHeaderDataLocalScript(
								$this->sExtPath . $sLib,
								$sKey
							);
						}
					}
				} else {
					if(!empty($this->aLibs)) {
						reset($this->aLibs);
						while(list($sKey, $sLib) = each($this->aLibs)) {

							$this->oForm->additionalHeaderDataLocalScript(
								$this->sExtPath . $sLib,
								$sKey
							);
						}
					}
				}
			}

		}

		function mayUseJs() {
			return $this->defaultTrue("js");
		}

		function includeScripts($aConfig = array()) {
			
			if(!$this->mayUseJs()) {
				return;
			}

			if($this->sMajixClass != "") {
				$sClass = $this->sMajixClass;
			} else {
				$sClass = "RdtBaseClass";
			}

			$aChildsIds = array();

			if($this->mayHaveChilds() && $this->hasChilds()) {

				$aKeys = array_keys($this->aChilds);
				reset($aKeys);
				while(list(, $sKey) = each($aKeys)) {
					$aChildsIds[$sKey] = $this->aChilds[$sKey]->_getElementHtmlId();
				}
			}

			if($this->hasParent()) {
				$sParentId = $this->oRdtParent->_getElementHtmlId();
			} else {
				$sParentId = FALSE;
			}

			$sHtmlId = $this->_getElementHtmlId();
			$sJson = $this->oForm->array2json(
				array_merge(
					array(
						"id" => $sHtmlId,
						"localname" => $this->getName(),
						"name" => $this->_getElementHtmlName(),
						"namewithoutformid" => $this->_getElementHtmlNameWithoutFormId(),
						"idwithoutformid" => $this->_getElementHtmlIdWithoutFormId(),
						"formid" => $this->oForm->formid,
						"_rdts" => $aChildsIds,
						"parent" => $sParentId,
						"error" => $this->getError(),
						"abswebpath" => $this->sExtWebPath,
						"readonly" => $this->_readOnly()
					),
					$aConfig
				)
			);

			$sScript =<<<JAVASCRIPT
Formidable.Context.Forms["{$this->oForm->formid}"].Objects["{$sHtmlId}"] = new Formidable.Classes.{$sClass}({$sJson});

JAVASCRIPT;

			$this->oForm->attachInitTask(
				$sScript,
				$sClass . " " . $sHtmlId . " initialization",
				$sHtmlId
			);
		}

		function mayHaveChilds() {
			return FALSE;
		}

		function hasChilds() {
			return isset($this->aElement["childs"]);
		}

		function isChild() {
			return $this->bChild;
		}

		function mayBeDataBridge() {
			return FALSE;
		}

		function isDataBridge() {
			return $this->mayBeDataBridge() && $this->bIsDataBridge === TRUE;
		}

		function hasDataBridge() {
			return $this->bHasDataBridge;
			//return $this->oDataBridge !== FALSE;
		}

		function renderChildsBag() {

			$aRendered = array();

			if($this->mayHaveChilds() && $this->hasChilds()) {

				reset($this->aChilds);
				while(list($sName, ) = each($this->aChilds)) {
					$oRdt =& $this->aChilds[$sName];
					if($this->bForcedValue === TRUE && is_array($this->mForcedValue) && array_key_exists($sName, $this->mForcedValue)) {
						// parent may have childs
							// AND has forced value
							// AND value is a nested array of values
							// AND subvalue for current child exists in the data array
								// => forcing subvalue for this child
						$oRdt->forceValue($this->mForcedValue[$sName]);
						$aRendered[$sName] = $this->oForm->_renderElement($oRdt);
						$oRdt->unForceValue();
					} else {
						$aRendered[$sName] = $this->oForm->_renderElement($oRdt);
					}

				}
			}
			
			// adding prerendered renderlets in the html bag
			$sAbsName = $this->getAbsName();
			$sAbsPath = str_replace(".", ".childs.", $sAbsName);
			$sAbsPath = str_replace(".", "/", $sAbsPath);
			
			if(($mValue = $this->oForm->navDeepData($sAbsPath, $this->oForm->aPreRendered)) !== FALSE) {
				if(is_array($mValue) && array_key_exists("childs", $mValue)) {
					$aRendered = t3lib_div::array_merge_recursive_overrule(
						$aRendered,
						$mValue["childs"]
					);
				}
			}

			reset($aRendered);
			return $aRendered;
		}

		function renderChildsCompiled($aChildsBag) {

			if(($this->_navConf("/childs/template/path")) !== FALSE) {
				// templating childs
					// mechanism:
					// childs can be templated if name of parent renderlet is present in template as a subpart marker
					// like for instance with renderlet:BOX name="mybox", subpart will be <!-- ###mybox### begin--> My childs here <!-- ###mybox### end-->
				
				$aTemplate = $this->_navConf("/childs/template");

				$sPath = $this->oForm->toServerPath($this->oForm->_navConf("/path", $aTemplate));

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

				$mHtml = t3lib_parsehtml::getSubpart(
					t3lib_div::getUrl($sPath),
					$sSubpart
				);

				if(trim($mHtml) == "") {
					$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template (<b>'" . $sPath . "'</b> with subpart marquer <b>'" . $sSubpart . "'</b>) <b>returned an empty string</b> - Check your template");
				}


				return $this->oForm->_parseTemplateCode(
					$mHtml,
					$aChildsBag,
					array(),
					FALSE
				);
			} else {

				if($this->oForm->oRenderer->_getType() === "TEMPLATE") {

					// child-template is not defined, but maybe is it implicitely the same as current template renderer ?
					if(($sSubpartName = $this->_navConf("/childs/template/subpart")) === FALSE) {
						$sSubpartName = $this->getName();
					}

					$sSubpartName = str_replace("#", "", $sSubpartName);

					if(($sHtml = $this->getCustomRootHtml()) === FALSE) {
						$sHtml = $this->oForm->oRenderer->getTemplateHtml();
					}

					$sSubpart = $this->oForm->oHtml->getSubpart($sHtml, "###" . $sSubpartName . "###");
					$aTemplateErrors = array();
					$aCompiledErrors = array();
					$aDeepErrors = $this->getDeepErrorRelative();
					reset($aDeepErrors);
					while(list($sKey,) = each($aDeepErrors)) {
						
						$sTag = $this->oForm->oRenderer->wrapErrorMessage($aDeepErrors[$sKey]["message"]);
						
						$aCompiledErrors[] = $sTag;
						
						$aTemplateErrors[$sKey] = $aDeepErrors[$sKey]["message"];
						$aTemplateErrors[$sKey . "."] = array(
							"tag" => $sTag,
							"info" => $aDeepErrors[$sKey]["info"],
						);
					}
					
					$aChildsBag["errors"] = $aTemplateErrors;
					$aChildsBag["errors"]["__compiled"] = $this->oForm->oRenderer->compileErrorMessages($aCompiledErrors);

					if(!empty($sSubpart)) {
						$sRes = $this->oForm->_parseTemplateCode(
							$sSubpart,
							$aChildsBag,
							array(),
							FALSE
						);
						
						return $sRes;
					}
				}

				$sCompiled = "";

				reset($aChildsBag);
				while(list($sName, $aBag) = each($aChildsBag)) {
					if($this->shouldAutowrap()) {
						$sCompiled .= "\n<div class='formidable-rdrstd-rdtwrap'>" . $aBag["__compiled"] . "</div>";
					} else {
						$sCompiled .= "\n" . $aBag["__compiled"];
					}
				}

				return $sCompiled;
			}
		}
		
		function shouldAutowrap() {
			return $this->defaultTrue("/childs/autowrap/");
		}


		function buildMajixExecuter($sMethod, $aData = array()) {
			return $this->oForm->buildMajixExecuter(
				$sMethod,
				$aData,
				$this->_getElementHtmlId()
			);
		}

		function majixDoNothing() {
			return $this->buildMajixExecuter("doNothing");
		}

		function majixDisplayBlock() {
			return $this->buildMajixExecuter("displayBlock");
		}

		function majixDisplayNone() {
			return $this->buildMajixExecuter("displayNone");
		}

		function majixDisplayDefault() {
			return $this->buildMajixExecuter("displayDefault");
		}

		function majixVisible() {
			return $this->buildMajixExecuter("visible");
		}

		function majixHidden() {
			return $this->buildMajixExecuter("hidden");
		}

		function majixDisable() {
			return $this->buildMajixExecuter("disable");
		}

		function majixEnable() {
			return $this->buildMajixExecuter("enable");
		}

		function majixReplaceData($sData) {
			return $this->buildMajixExecuter(
				"replaceData",
				$sData
			);
		}

		function majixReplaceLabel($sLabel) {
			return $this->buildMajixExecuter(
				"replaceLabel",
				$this->oForm->_getLLLabel($sLabel)
			);
		}

		function majixClearData() {
			return $this->buildMajixExecuter(
				"clearData"
			);
		}

		function majixClearValue() {
			return $this->buildMajixExecuter(
				"clearValue"
			);
		}

		function majixSetValue($sValue) {
			return $this->buildMajixExecuter(
				"setValue",
				$sValue
			);
		}

		function majixFx($sEffect, $aParams = array()) {
			return $this->buildMajixExecuter(
				"Fx",
				array(
					"effect" => $sEffect,
					"params" => $aParams,
				)
			);
		}

		function majixFocus() {
			return $this->buildMajixExecuter(
				"focus"
			);
		}

		function majixScrollTo() {
			return $this->oForm->majixScrollTo(
				$this->_getElementHtmlId()
			);
		}

		function majixSetErrorStatus($aError = array()) {
			return $this->buildMajixExecuter(
				"setErrorStatus",
				$aError
			);
		}

		function majixRemoveErrorStatus() {
			return $this->buildMajixExecuter(
				"removeErrorStatus"
			);
		}
		
		
		function majixSubmitSearch() {
			return $this->buildMajixExecuter(
				"triggerSubmit",
				"search"
			);
		}
		
		function majixSubmitFull() {
			return $this->buildMajixExecuter(
				"triggerSubmit",
				"full"
			);
		}
		
		function majixSubmitClear() {
			return $this->buildMajixExecuter(
				"triggerSubmit",
				"clear"
			);
		}
		
		function majixSubmitRefresh() {
			return $this->buildMajixExecuter(
				"triggerSubmit",
				"refresh"
			);
		}
		
		function majixSubmitDraft() {
			return $this->buildMajixExecuter(
				"triggerSubmit",
				"draft"
			);
		}
		
		function majixSetInvisible() {
			return $this->buildMajixExecuter(
				"setInvisible"
			);
		}
		
		function majixSetVisible() {
			return $this->buildMajixExecuter(
				"setVisible"
			);
		}
		
		function majixValidate($mErrorMethod = FALSE, $mValidMethod = FALSE) {
			$this->oForm->clearValidation();
			$this->validate();
					
			if(empty($this->_aValidationErrorsInfos[$this->getName()])) {
				if($mValidMethod !== FALSE) {
					return $this->oForm->_callCodeBehind(
						array('exec' => $mValidMethod, '__value' => ''), 
						$this->_aValidationErrorsInfos[$this->getName()]
					);
				}
			
				if($mErrorMethod !== FALSE) {
					return $this->oForm->_callCodeBehind(
						array('exec' => $mErrorMethod, '__value' => ''), 
						$this->_aValidationErrorsInfos[$this->getName()]
					);
				}
			
				return TRUE;
			
			} else {
			
				if($mErrorMethod !== FALSE) {
					return $this->oForm->_callCodeBehind(
						array('exec' => $mErrorMethod, '__value' => ''), 
						$this->_aValidationErrorsInfos[$this->getName()]
					);
				}
			
				return $this->buildMajixExecuter(
					"displayError",
					$this->_aValidationErrorsInfos[$this->getName()],
					$this->formid
				);
			}
			
		}
		
		function defaultWrap() {
			return $this->defaultTrue("/defaultwrap");
		}

		function hideIfJs() {
			return $this->defaultFalse("/hideifjs");
		}

		function displayOnlyIfJs() {
			return $this->defaultFalse("/displayonlyifjs");
		}

		function baseCleanBeforeSession() {
			
			$sThisAbsName = $this->getAbsName();	// keep it before being unable to calculate it
			
			if($this->hasChilds() && isset($this->aChilds) && is_array($this->aChilds)) {
				$aChildKeys = array_keys($this->aChilds);
				reset($aChildKeys);
				while(list(, $sKey) = each($aChildKeys)) {
					$this->aChilds[$sKey]->cleanBeforeSession();
				}
			}

			if($this->hasParent()) {
				$this->sRdtParent = $this->oRdtParent->getAbsName();
				unset($this->oRdtParent);	// TODO: reconstruct ajax-side
				$this->oRdtParent = FALSE;
			}

			if($this->isDataBridge()) {
				$aKeys = array_keys($this->aDataBridged);
				reset($aKeys);
				while(list(, $sKey) = each($aKeys)) {
					$sAbsName = $this->aDataBridged[$sKey];
					if(array_key_exists($sAbsName, $this->oForm->aORenderlets)) {
						$this->oForm->aORenderlets[$sAbsName]->sDataBridge = $sThisAbsName;
						unset($this->oForm->aORenderlets[$sAbsName]->oDataBridge);
						$this->oForm->aORenderlets[$sAbsName]->oDataBridge = FALSE;
					}
				}

				$this->sDataSource = $this->oDataSource->getName();
				unset($this->oDataSource);
				$this->oDataSource = FALSE;
			}

			unset($this->aStatics);
			$this->aStatics = $this->aEmptyStatics;
			$this->aCustomEvents = array();
		}

		function awakeInSession(&$oForm) {
			$this->oForm =& $oForm;

			if($this->sRdtParent !== FALSE) {
				$this->oRdtParent =& $this->oForm->aORenderlets[$this->sRdtParent];
				$this->sRdtParent = FALSE;
			}

			if($this->sDataSource !== FALSE) {
				$this->oDataSource =& $this->oForm->aODataSources[$this->sDataSource];
				$this->sDataSource = FALSE;
			}

			if($this->sDataBridge !== FALSE) {
				$this->oDataBridge =& $this->oForm->aORenderlets[$this->sDataBridge];
				$this->sDataBridge = FALSE;
			}
		}

		function hasSubmitted($sFormId = FALSE, $sAbsName = FALSE) {

			/*	algorithm:
				if isNaturalSubmitter()
					=> TRUE
					natural submitters are posting their value when submitting
					so we have to check for this value in the returned data array
				else if form is submitted and the submitterId == this renderletId
					=> TRUE
					every other renderlet might submit using a javascript submit event
					during the javascript processing, the submitter id is stored in the hidden field AMEOSFORMIDABLE_SUBMITTER
					right before the submit of the form
					so we may just check if the posted id corresponds to this renderlet id
			*/

			$bRes = FALSE;

			$aSubmitValues = array(
				AMEOSFORMIDABLE_EVENT_SUBMIT_FULL,
				AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH,
				AMEOSFORMIDABLE_EVENT_SUBMIT_TEST,
				AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT,
				AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR,
				AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH,
			);

			$mPostValue = $this->getRawPostValue($sFormId, $sAbsName);

			if($sFormId === FALSE && $sAbsName === FALSE) {				
				$sElementHtmlId = $this->_getElementHtmlId();		
				if(array_key_exists($sElementHtmlId, $this->aStatics["hasSubmitted"])) {
					return $this->aStatics["hasSubmitted"][$sElementHtmlId];
				}
			}


			if($this->maySubmit() && $this->isNaturalSubmitter()) {
				// handling the special case of natural submitter for accessibility reasons
				if($mPostValue !== FALSE) {
					$bRes = TRUE;
				}
			} else {
				if($this->oForm->oDataHandler->_isSubmitted($sFormId)) {
					$sSubmitter = $this->oForm->oDataHandler->getSubmitter($sFormId);
					if($sSubmitter === $this->_getElementHtmlIdWithoutFormId()) {
						$bRes = TRUE;
					}
				}
			}
			
			if($sFormId === FALSE && $sAbsName === FALSE) {
				$this->aStatics["hasSubmitted"][$sElementHtmlId] = $bRes;
			}

			return $bRes;
		}

		function getRawPostValue($sFormId = FALSE, $sAbsName = FALSE) {

			if($sFormId === FALSE) {
				$sFormId = $this->oForm->formid;
				if($sAbsName === FALSE) {
					$sDataId = $this->_getElementHtmlIdWithoutFormId();
				} else {
					$sDataId = $this->oForm->aORenderlets[$sAbsName]->_getElementHtmlIdWithoutFormId();
				}
			} else {
				$sDataId = $sAbsName;
			}

			if(!array_key_exists($sDataId, $this->aStatics["rawpostvalue"])) {
				$this->aStatics["rawpostvalue"][$sDataId] = FALSE;
				$aP = $this->oForm->_getRawPost($sFormId);
				$sAbsPath = str_replace(".", "/", $sDataId);

				if(($mData = $this->oForm->navDeepData($sAbsPath, $aP)) !== FALSE) {
					$this->aStatics["rawpostvalue"][$sDataId] = $mData;
				}
			}

			return $this->aStatics["rawpostvalue"][$sDataId];
		}

		function wrap($sHtml) {
			if(($mWrap = $this->_navConf("/wrap")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mWrap)) {
					$mWrap = $this->callRunneable($mWrap);
				}

				return $this->oForm->cObj->noTrimWrap($sHtml, $mWrap);
			}

			return $sHtml;
		}

		function getFalse() {
			return FALSE;
		}

		function getTrue() {
			return TRUE;
		}

		function shouldProcess() {

			$mProcess = $this->_navConf("/process");

			if($mProcess !== FALSE) {
				if(tx_ameosformidable::isRunneable($mProcess)) {

					$mProcess = $this->callRunneable($mProcess);

					if($mProcess === FALSE) {
						return FALSE;
					}
				} elseif($this->oForm->isFalseVal($mProcess)) {
					return FALSE;
				}
			}

			$aUnProcessMap = $this->oForm->_navConf($this->oForm->sXpathToControl . "factorize/switchprocess");
			if(tx_ameosformidable::isRunneable($aUnProcessMap)) {
				$aUnProcessMap = $this->callRunneable($aUnProcessMap);
			}

			if(is_array($aUnProcessMap) && array_key_exists($this->_getName(), $aUnProcessMap)) {
				return $aUnProcessMap[$this->_getName()];
			}

			return TRUE;
		}

		function handleAjaxRequest(&$oRequest) {
			/* specialize me */
		}

		function setParent(&$oParent) {
			#$oParent->testit = "setParentOne:";
			$this->oRdtParent =& $oParent;
			#$this->oRdtParent->testit .= ":setParentTwo->oRdtParent:";
		}

		function addCssClass($sNewClass) {

			if(($sClass = $this->_navConf("/class")) !== FALSE) {
				$sClass = trim($sClass);
				$aClasses = t3lib_div::trimExplode(" ", $sClass);
			} else {
				$aClasses = array();
			}

			$aClasses[] = $sNewClass;
			$this->aElement["class"] = implode(" ", array_unique($aClasses));
		}

		function filterUnProcessed() {

			if($this->mayHaveChilds() && $this->hasChilds()) {

				if(isset($this->aChilds)) {
					$aChildKeys = array_keys($this->aChilds);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {
						$this->aChilds[$sChildName]->filterUnProcessed();
					}
				}

				if(isset($this->aOColumns)) {
					$aChildKeys = array_keys($this->aOColumns);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {
						$this->aOColumns[$sChildName]->filterUnProcessed();
					}
				}
			}

			if($this->shouldProcess() === FALSE) {
				$this->unsetRdt();
			}
		}
		/**
		 * Unsets the rdt corresponding to the given name
		 * Also unsets it's childs if any, and it's validators-errors if any
		 *
		 * @param	string		$sName: ...
		 * @return	void
		 */
		function unsetRdt() {
			

			if($this->mayHaveChilds() && $this->hasChilds()) {

				if(isset($this->aChilds)) {
					$aChildKeys = array_keys($this->aChilds);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {
						$this->aChilds[$sChildName]->unsetRdt();
						unset($this->aChilds[$sChildName]);
					}
				}

				if(isset($this->aOColumns)) {
					$aChildKeys = array_keys($this->aOColumns);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {
						$this->aOColumns[$sChildName]->unsetRdt();
						unset($this->aOColumns[$sChildName]);
					}
				}

			}

			if($this->hasDataBridge()) {
				# if the renderlet is registered in a databridge, we have to remove it
				$iKey = array_search($this->getAbsName(), $this->oDataBridge->aDataBridged);
				unset($this->oDataBridge->aDataBridged[$iKey]);
			}

			// unsetting events
				// onload events
			$sName = $this->getAbsName();

			$aAjaxOnloadEventsKeys = array_keys($this->oForm->aOnloadEvents["ajax"]);
			while(list(, $sKey) = each($aAjaxOnloadEventsKeys)) {
				if($this->oForm->aOnloadEvents["ajax"][$sKey]["name"] === $sName) {
					unset($this->oForm->aOnloadEvents["ajax"][$sKey]);
				}
			}

			//unset($this->oForm->_aValidationErrors[$sName]);	// removes potentialy thrown validation errors
			$this->cancelError();
			
			if($this->hasParent()) {
				unset($this->oRdtParent->aChilds[$this->getName()]);
			}

			unset($this->oForm->aORenderlets[$sName]);
			unset($this->oForm->oDataHandler->__aFormData[$sName]);
			unset($this->oForm->oDataHandler->__aFormDataManaged[$sName]);

			// pre-setting it's render to void
			#$this->oForm->aPreRendered[$sName] = "";
			
			$sDeepPath = str_replace(".", ".childs.", $sName);
			$sDeepPath = str_replace(".", "/", $sDeepPath);
			$this->oForm->setDeepData(
				$sDeepPath,
				$this->oForm->aPreRendered,
				array(),
				TRUE	// $bMergeIfArray
			);
			
			#debug($this->oForm->aPreRendered);
		}

		function majixRepaint() {
			#$bBefore = $this->oForm->oRenderer->bDisplayLabels;
			#$this->oForm->oRenderer->bDisplayLabels = FALSE;

			$aHtmlBag = $this->render();

			#$this->oForm->oRenderer->bDisplayLabels = $bBefore;

			return $this->buildMajixExecuter(
				"repaint",
				$aHtmlBag["__compiled"]
			);
		}

		function hasDependants() {
			return (count($this->aDependants) > 0);
		}

		function hasDependancies() {
			return (count($this->aDependsOn) > 0);
		}
		
		function majixRepaintDependancies($aTasks = FALSE) {

			if($aTasks !== FALSE) {
				// this is a php-hack to allow optional yet passed-by-ref arguments
				$aTasks =& $aTasks[0];
			}
			
			if(!is_array($aTasks)) {
				$aTasks = array();
			}

			//$aTasks = array();
			if($this->hasDependants()) {
				reset($this->aDependants);
				while(list(, $sAbsName) = each($this->aDependants)) {

					$this->oForm->aORenderlets[$sAbsName]->refreshValue();
					$aTasks[] = $this->oForm->aORenderlets[$sAbsName]->majixRepaint();

					if($this->oForm->aORenderlets[$sAbsName]->hasDependants()) {
						$this->oForm->aORenderlets[$sAbsName]->majixRepaintDependancies(array(&$aTasks));
					}
					
					if($this->oForm->aORenderlets[$sAbsName]->hasChilds()) {
						$aChildKeys = array_keys($this->oForm->aORenderlets[$sAbsName]->aChilds);
						reset($aChildKeys);
						while(list(, $sChild) = each($aChildKeys)) {
							$this->oForm->aORenderlets[$sAbsName]->aChilds[$sChild]->majixRepaintDependancies(array(&$aTasks));
						}
					}
				}
			}

			reset($aTasks);
			return $aTasks;
		}

		function processDataBridge() {

			#debug($this->_getElementHtmlId(), "processDataBridge");

			if($this->mayHaveChilds() && $this->hasChilds()) {

				if(isset($this->aChilds)) {
					$aChildKeys = array_keys($this->aChilds);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {

						if(	!$this->hasParent() ||
							(
								!$this->oRdtParent->isIterable() ||
								($this->oRdtParent->isIterable() && $this->oRdtParent->isIterating())
							)
						) {
							if($this->aChilds[$sChildName]->_isSubmittedForValidation()) {
								$this->aChilds[$sChildName]->validate();
							}

							$this->aChilds[$sChildName]->processDataBridge();
						}
						
					}
				}

				if(isset($this->aOColumns)) {
					$aChildKeys = array_keys($this->aOColumns);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {

						if($this->aOColumns[$sChildName]->_isSubmittedForValidation()) {
							$this->aOColumns[$sChildName]->validate();
						}

						$this->aOColumns[$sChildName]->processDataBridge();
					}
				}
			}

			if($this->isDataBridge() && $this->oDataSource->writable() && $this->dbridge_isFullySubmitted()) {
				#debug($this->_getElementHtmlId(), "submitted");
				// all is valid for this dbridge and if global submit : all form is valid
				if(
					$this->dbridge_allIsValid() && 
					(
						!$this->dbridge_globalSubmitable() || !$this->oForm->hasErrors()
					)
				) {
					$sSignature = $this->dbridge_getCurrentDsetSignature();

					$aKeys = array_keys($this->aDataBridged);
					reset($aKeys);
					while(list(, $iKey) = each($aKeys)) {
						$sAbsName = $this->aDataBridged[$iKey];
						if($sAbsName === FALSE || (!$this->oForm->aORenderlets[$sAbsName]->_renderOnly() && !$this->oForm->aORenderlets[$sAbsName]->_readOnly())) {

							$sMappedPath = $this->dbridge_mapPath($sAbsName);

							if($sMappedPath !== FALSE) {
								$this->oDataSource->dset_setCellValue(
									$sSignature,
									$sMappedPath,
									$this->oForm->aORenderlets[$sAbsName]->getValue(),
									$sAbsName
								);
							}
						}
					}

					$this->oDataSource->dset_writeDataSet($sSignature);
				}
			}
		}

		function dbridge_allIsValid() {
			$bValid = TRUE;

			if($this->isDataBridge()) {
				$sThisAbsName = $this->getAbsName();
				$aErrorKeys = array_keys($this->oForm->_aValidationErrors);
				#debug($this->oForm->_aValidationErrors, "_aValidationErrors");
				#debug($this->oForm->_aValidationErrorsByHtmlId, "_aValidationErrorsByHtmlId");
				reset($aErrorKeys);
				while($bValid && list(, $sAbsName) = each($aErrorKeys)) {
					if(array_key_exists($sAbsName, $this->oForm->aORenderlets) && $this->oForm->aORenderlets[$sAbsName]->isDescendantOf($sThisAbsName)) {
						$bValid = FALSE;
					}
				}
			}

			return $bValid;
		}

		function dbridge_getRdtValueInDataSource($sAbsName) {
			$sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
			$sPath = str_replace(".", "/", $sRelName);

			$sSignature = $this->dbridge_getCurrentDsetSignature();
			if(($mData = $this->oForm->navDeepData($sPath, $this->oDataSource->aODataSets[$sSignature]->getData())) !== FALSE) {
				return $mData;
			}

			return "";
		}

		function dbridge_getSubmitterAbsName() {
			if($this->aStatics["dbridge_getSubmitterAbsName"] !== AMEOSFORMIDABLE_VALUE_NOT_SET) {
				return $this->aStatics["dbridge_getSubmitterAbsName"];
			}

			$aKeys = array_keys($this->aDataBridged);
			reset($aKeys);
			while(list(, $iKey) = each($aKeys)) {
				$sAbsName = $this->aDataBridged[$iKey];

				if($this->oForm->aORenderlets[$sAbsName]->hasSubmitted()) {
					$this->aStatics["dbridge_getSubmitterAbsName"] = $sAbsName;
					return $sAbsName;
				}
			}

			$this->aStatics["dbridge_getSubmitterAbsName"] = FALSE;
			return FALSE;
		}

		function dbridge_globalSubmitable() {
			return $this->defaultFalse("/datasource/globalsubmit");
		}

		function dbridge_isSubmitted() {
			#debug($this->dbridge_getSubmitterAbsName(), $this->getAbsName());
			#debug($this->dbridge_getCurrentDsetObject());
			if(($this->dbridge_getSubmitterAbsName() !== FALSE) || $this->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isSubmitted();
			}

			return FALSE;
		}

		function dbridge_isClearSubmitted() {
			if(($this->dbridge_getSubmitterAbsName() !== FALSE) || $this->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isClearSubmitted();
			}

			return FALSE;
		}

		function dbridge_isFullySubmitted() {
			if(($this->dbridge_getSubmitterAbsName() !== FALSE) || $this->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isFullySubmitted();
			}

			return FALSE;
		}

		function dbridge_mapPath($sAbsName) {
			#debug($sAbsName, "dbridge_mapPath");
			# first, see if a mapping has been explicitely set on the renderlet
			if(($sPath = $this->oForm->aORenderlets[$sAbsName]->_navConf("/map")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($sPath)) {
					$sPath = $this->callRunneable($sPath);
				}

				if($sPath !== FALSE) {
					return $sPath;
				}
			}

			# then, see if a mapping has been set in the databridge-level /mapping property
			if(($aMapping = $this->dbridge_getMapping()) !== FALSE) {
				$sRelName = $this->oForm->aORenderlets[$sAbsName]->dbridged_getNameRelativeToDbridge();

				$aKeys = array_keys($aMapping);
				reset($aKeys);
				while(list(, $iKey) = each($aKeys)) {
					if($aMapping[$iKey]["rdt"] === $sRelName) {
						$sPath = $aMapping[$iKey]["data"];
						return str_replace(".", "/", $sPath);
					}
				}
			}

			# finaly, we give a try to the automapping feature
			return $this->oDataSource->dset_mapPath(
				$this->dbridge_getCurrentDsetSignature(),
				$this,
				$sAbsName
			);
		}

		function dbridged_mapPath() {
			return $this->oDataBridge->dbridge_mapPath($this->getAbsName());
		}

		function dbridge_getMapping() {
			if($this->aStatics["dsetMapping"] === AMEOSFORMIDABLE_VALUE_NOT_SET) {
				if(($aMapping = $this->_navConf("/datasource/mapping")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($aMapping)) {
						$aMapping = $this->callRunneable($aMapping);
					}

					if(is_array($aMapping)) {
						$this->aStatics["dsetMapping"] = $aMapping;
						reset($this->aStatics["dsetMapping"]);
					} else {
						$this->aStatics["dsetMapping"] = FALSE;
					}
				} else {
					$this->aStatics["dsetMapping"] = FALSE;
				}
			}

			return $this->aStatics["dsetMapping"];
		}
		
		function _isSubmittedForValidation() {
			return $this->_isSubmitted() && (
				$this->_isFullySubmitted() ||
				$this->_isTestSubmitted()
			);
		}

		function _isSubmitted() {

			if($this->isDataBridge()) {
				return $this->dbridge_isSubmitted();
			}

			if($this->hasDataBridge()) {
				return $this->oDataBridge->dbridge_isSubmitted();
			}

			return $this->oForm->oDataHandler->_isSubmitted();
		}

		function _isClearSubmitted() {

			if($this->isDataBridge()) {
				return $this->dbridge_isClearSubmitted();
			}

			if($this->hasDataBridge()) {
				return $this->oDataBridge->dbridge_isClearSubmitted();
			}

			return $this->oForm->oDataHandler->_isClearSubmitted();
		}

		function _isFullySubmitted() {
			if($this->isDataBridge()) {
				return $this->dbridge_isFullySubmitted();
			}

			if($this->hasDataBridge()) {
				return $this->oDataBridge->dbridge_isFullySubmitted();
			}

			return $this->oForm->oDataHandler->_isFullySubmitted();
		}

		function _isRefreshSubmitted() {
			if(!$this->hasDataBridge() || ($this->oDataBridge->dbridge_getSubmitterAbsName() !== FALSE) || $this->oDataBridge->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isRefreshSubmitted();
			}

			return FALSE;
		}

		function _isTestSubmitted() {
			if(!$this->hasDataBridge() || ($this->oDataBridge->dbridge_getSubmitterAbsName() !== FALSE) || $this->oDataBridge->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isTestSubmitted();
			}

			return FALSE;
		}

		function _isDraftSubmitted() {
			if(!$this->hasDataBridge() || ($this->oDataBridge->dbridge_getSubmitterAbsName() !== FALSE) || $this->oDataBridge->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isDraftSubmitted();
			}

			return FALSE;
		}

		function _isSearchSubmitted() {
			if(!$this->hasDataBridge() || ($this->oDataBridge->dbridge_getSubmitterAbsName() !== FALSE) || $this->oDataBridge->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isSearchSubmitted();
			}

			return FALSE;
		}

		function _edition() {

			if($this->isDataBridge()) {
				return $this->dbridge_edition();
			}
			
			if($this->hasDataBridge()) {
				return $this->dbridged_edition();
			}

			return $this->oForm->oDataHandler->_edition();
		}

		function dbridge_edition() {
			if(($sSignature = $this->dbridge_getCurrentDsetSignature()) !== FALSE) {
				if(array_key_exists($sSignature, $this->oDataSource->aODataSets)) {
					return $this->oDataSource->aODataSets[$sSignature]->isAnchored();
				}
			}

			return FALSE;
		}

		function dbridged_edition() {
			return $this->oDataBridge->dbridge_edition();
		}

		function maySubmit() {
			return TRUE;
		}

		function isNaturalSubmitter() {
			return FALSE;
		}

		function dbridge_getPostedSignature($bDecode = TRUE) {
			if($this->isDataBridge()) {

				$sName = $this->getAbsName() . ".databridge";
				$sPath = str_replace(".", "/", $sName);

				if(($sSignature = $this->oForm->navDeepData($sPath, $this->oForm->_getRawPost())) !== FALSE) {
					$sSignature = trim($sSignature);

					if($sSignature === "") {
						return FALSE;
					}

					if($bDecode === TRUE) {
						return $this->oDataSource->dset_decodeSignature($sSignature);
					} else {
						return $sSignature;
					}
				}
			}

			return FALSE;
		}

		function dbridge_getCurrentDsetSignature() {
			return $this->aDataSetSignatures[$this->_getElementHtmlId()];
		}

		function &dbridge_getCurrentDsetObject() {
			return $this->oDataSource->aODataSets[$this->dbridge_getCurrentDsetSignature()];
		}

		function dbridged_getCurrentDsetSignature() {
			return $this->oDataBridge->dbridge_getCurrentDsetSignature();
		}

		function &dbridged_getCurrentDsetObject() {
			return $this->oDataBridge->dbridge_getCurrentDsetObject();
		}

		function dbridge_getCurrentDset() {
			$oDataSet =& $this->dbridge_getCurrentDsetObject();
			return $oDataSet->getDataSet();
		}

		function dbridged_getCurrentDset() {
			return $this->oDataBridge->dbridge_getCurrentDset();
		}

		function isIterating() {
			return FALSE;
		}
		
		function isIterated() {
			return FALSE;
		}

		function isIterable() {
			return FALSE;
		}

		function __getItemsStaticTable($sTable, $sValueField = 'uid', $sWhere = '') {
			// Get user language
			if(TYPO3_MODE == 'FE') {
				$sLang = $GLOBALS['TSFE']->lang;
			} else {
				$sLang = $GLOBALS['LANG']->lang;
			}

			// Get field names
			$aFieldNames = tx_staticinfotables_div::getTCAlabelField($sTable, TRUE, $sLang);
			$sFields = implode(', ', $aFieldNames);

			// Get data from static table
			$aRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($sValueField . ', ' . $sFields, $sTable, $sWhere, '', $sFields);

			$aItems =  array();

			if (empty($aRows)) {
				return $aItems;
			}

			// For each row
			foreach ($aRows as $aRow) {
				foreach ($aFieldNames as $sFieldName) {
					if ($aRow[$sFieldName]) { // If exists
						$sCaption = $aRow[$sFieldName];
						break;
					}
				}

				$aTmp = array(
					'caption' => $sCaption,
					'value' => $aRow[$sValueField]
				);

				array_push($aItems, $aTmp);
			}

			return $aItems;
		}

		function cancelError() {
			// removes potentialy thrown validation errors

			$sAbsName = $this->getAbsName();
			$sHtmlId = $this->_getElementHtmlIdWithoutFormId();

			unset($this->oForm->_aValidationErrors[$sAbsName]);
			unset($this->oForm->_aValidationErrorsByHtmlId[$sHtmlId]);
			unset($this->oForm->_aValidationErrorsInfos[$sHtmlId]);
			//unset($this->oForm->_aValidationErrorsTypes[$sAbsName]);
		}
		
		function majixAddClass($sClass) {
			return $this->buildMajixExecuter(
				"addClass",
				$sClass
			);
		}

		function majixRemoveClass($sClass) {
			return $this->buildMajixExecuter(
				"removeClass",
				$sClass
			);
		}

		function majixRemoveAllClass() {
			return $this->buildMajixExecuter(
				"removeAllClass",
				$sClass
			);
		}

		function majixSetStyle($aStyles) {
			$aStyles = $this->oForm->div_camelizeKeys($aStyles);
			return $this->buildMajixExecuter(
				"setStyle",
				$aStyles
			);
		}

		function persistHidden() {
			return "<input type=\"hidden\" id=\"" . $this->_getElementHtmlId() . "\" name=\"" . $this->_getElementHtmlName() . "\" value=\"" . htmlspecialchars($this->getValue()) . "\" />";
		}

		function hasDeepError() {
			if($this->mayHaveChilds() && $this->hasChilds()) {
				$bHasErrors = FALSE;

				$aChildKeys = array_keys($this->aChilds);
				reset($aChildKeys);
				while(!$bHasErrors && (list(, $sKey) = each($aChildKeys))) {
					$bHasErrors = $bHasErrors || $this->aChilds[$sKey]->hasDeepError();
				}

				return $bHasErrors;
			}
			
			return $this->hasError();
		}

		function hasError() {
			$sHtmlId = $this->_getElementHtmlIdWithoutFormId();
			if(array_key_exists($sHtmlId, $this->oForm->_aValidationErrorsByHtmlId)) {
				return TRUE;
			}

			return FALSE;
		}

		function getError() {
			if($this->hasError()) {
				$sAbsName = $this->getAbsName();
				$sHtmlId = $this->_getElementHtmlIdWithoutFormId();

				return array(
					"message" => $this->oForm->_aValidationErrorsByHtmlId[$sHtmlId],
					"info" => $this->oForm->_aValidationErrorsInfos[$sHtmlId],
				);
			}

			return FALSE;
		}
		
		function getDeepError() {
			$aErrors = array();
			$aErrors = $this->getDeepError_rec($aErrors);
			reset($aErrors);
			return $aErrors;
		}
		
		function getDeepErrorRelative() {
			$aErrors = array();
			$aErrorsRel = array();
			
			$aErrors = $this->getDeepError_rec($aErrors);
			
			reset($aErrors);
			while(list($sAbsName,) = each($aErrors)) {
				$aErrorsRel[$this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this)] = $aErrors[$sAbsName];
			}
			
			reset($aErrorsRel);
			return $aErrorsRel;
		}
		
		function getDeepError_rec($aErrors) {
			
			if($this->mayHaveChilds() && $this->hasChilds()) {
				$aChildKeys = array_keys($this->aChilds);
				reset($aChildKeys);
				while((list(, $sKey) = each($aChildKeys))) {
					if($this->aChilds[$sKey]->hasError()) {
						$aErrors[$this->aChilds[$sKey]->getAbsName()] = $this->aChilds[$sKey]->getError();
					}
					
					$aErrors = $this->aChilds[$sKey]->getDeepError_rec($aErrors);
				}
			}
			
			if(($aThisError = $this->getError()) !== FALSE) {
				$aErrors[$this->getAbsName()] = $aThisError;
			}
			
			reset($aErrors);
			return $aErrors;
		}

		/**
		 * Validates the given Renderlet element
		 *
		 * @param	array		$aElement: details about the Renderlet element to validate, extracted from XML conf / used in formidable_mainvalidator::validate()
		 * @return	void		Writes into $this->_aValidationErrors[] using tx_ameosformidable::_declareValidationError()
		 */
		function validate() {
			$this->validateByPath("/");
			$this->validateByPath("/validators");
			$this->declareCustomValidationErrors();
		}

		function validateByPath($sPath) {
			if(!$this->hasError()) {
				$aConf = $this->_navConf($sPath);
				if(is_array($aConf) && !empty($aConf)) {

					$sAbsName = $this->getAbsName();

					while(!$this->hasError() && list($sKey, $aValidator) = each($aConf)) {
						if($sKey{0} === "v" && $sKey{1} === "a" && t3lib_div::isFirstPartOfStr($sKey, "validator") && !t3lib_div::isFirstPartOfStr($sKey, "validators")) {
							// the conf section exists
							// call validator
							$oValidator = $this->oForm->_makeValidator($aValidator);

							if($oValidator->_matchConditions()) {

								$bHasToValidate = TRUE;

								$aValidMap = $this->oForm->_navConf($this->oForm->sXpathToControl . "factorize/switchvalidation");
								if(tx_ameosformidable::isRunneable($aValidMap)) {
									$aValidMap = $this->callRunneable($aValidMap);
								}

								if(is_array($aValidMap) && array_key_exists($sAbsName, $aValidMap)) {
									$bHasToValidate = $aValidMap[$sAbsName];
								}

								if($bHasToValidate === TRUE) {
									$oValidator->validate($this);
								}
							}
						}
					}
				}
			}
		}
		
		function callRunneable($mMixed) {

			$aArgs = func_get_args();
			$iNbParams = (count($aArgs) - 1);	// without the runneable itself
			
			$this->oForm->pushCurrentRdt($this);
			
			switch($iNbParams) {
				case 0: { $mRes = parent::callRunneable($mMixed); break;}
				case 1: { $mRes = parent::callRunneable($mMixed, $aArgs[1]); break;}
				case 2: { $mRes = parent::callRunneable($mMixed, $aArgs[1], $aArgs[2]); break;}
				case 3: { $mRes = parent::callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3]); break;}
				case 4: { $mRes = parent::callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4]); break;}
				case 5: { $mRes = parent::callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5]); break;}
				case 6: { $mRes = parent::callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6]); break;}
				case 7: { $mRes = parent::callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7]); break;}
				case 8: { $mRes = parent::callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8]); break;}
				case 9: { $mRes = parent::callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8], $aArgs[9]); break;}
				case 10:{ $mRes = parent::callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8], $aArgs[9], $aArgs[10]); break;}
				default: {
					$this->mayday("Runneable on " . $this->getName() . " can not declare more than 10 arguments.");
					break;
				}
			}
			
			$this->oForm->pullCurrentRdt();
			return $mRes;
		}
		
		function synthetizeAjaxEventUserobj($sEventHandler, $sPhp, $mParams=FALSE, $bCache=TRUE, $bSyncValue=FALSE, $bRefreshContext=FALSE) {
			return $this->oForm->oRenderer->synthetizeAjaxEvent(
				$this,
				$sEventHandler,
				FALSE,
				$sPhp,
				$mParams,
				$bCache,
				$bSyncValue,
				$bRefreshContext
			);
		}
		
		function synthetizeAjaxEventCb($sEventHandler, $sCb, $mParams=FALSE, $bCache=TRUE, $bSyncValue=FALSE, $bRefreshContext=FALSE) {
			return $this->oForm->oRenderer->synthetizeAjaxEvent(
				$this,
				$sEventHandler,
				$sCb,
				FALSE,
				$mParams,
				$bCache,
				$bSyncValue,
				$bRefreshContext
			);
		}
		
		function htmlAutocomplete() {
			if($this->mayHtmlAutocomplete()) {
				if($this->shouldHtmlAutocomplete()) {
					return "";
				} else {
					return " autocomplete=\"off\" ";
				}
			}
			
			return "";	// if rdt may not htmlautocomplete, no need to counter-indicate it
		}

		function shouldHtmlAutocomplete() {
			return $this->defaultFalse("/htmlautocomplete");
		}
		
		function mayHtmlAutocomplete() {
			return FALSE;
		}
		
		function initHasBeenPosted() {
			$this->bHasBeenPosted = array_key_exists($this->_getElementHtmlId(), $this->oForm->aPostFlags);

			if(!$this->bHasBeenPosted) {
				$aRawPost = $this->oForm->_getRawPost();
				$sAbsName = $this->getAbsName();
				$sAbsPath = str_replace(".", "/", $sAbsName);

				if($this->oForm->navDeepData($sAbsPath, $aRawPost) !== FALSE) {
					$this->bHasBeenPosted = TRUE;
				}
			}
		}
		
		function handleRefreshContext($aContext) {
			//debug($aContext);
			if(array_key_exists("value", $aContext)) {
				$this->setValue($aContext["value"]);
			}
		}

		function getCustomRootHtml() {
			return $this->sCustomRootHtml;
		}

		function setCustomRootHtml($sCustomRootHtml) {
			return $this->sCustomRootHtml = $sCustomRootHtml;
		}
	}

	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainrenderlet.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainrenderlet.php"]);
	}
?>
