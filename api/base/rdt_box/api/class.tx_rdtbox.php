<?php
/**
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtbox extends formidable_mainrenderlet {

	var $sMajixClass = "Box";
	var $bCustomIncludeScript = TRUE;
	var $aLibs = array(
		"rdt_box_class" => "res/js/box.js",
	);
	var $aPossibleCustomEvents = array(
		"ondragdrop",
		"ondraghover",
	);

	var $oDataSource = FALSE;
	var $sDsKey = FALSE;

	var $bForceHasChild = FALSE;

	function _render() {

		$sHtml = (tx_ameosformidable::isRunneable($this->aElement["html"])) ? $this->callRunneable($this->aElement["html"]) : $this->_navConf("/html");
		$sHtml = $this->oForm->_substLLLInHtml($sHtml);

		$sMode = $this->_navConf("/mode");
		if($sMode === FALSE) {
			$sMode = "div";
		} else {
			$sMode = strtolower(trim($sMode));
			if($sMode === "") {
				$sMode = "div";
			} elseif($sMode === "none" || $sMode === "inline") {
				$sMode = "inline";
			}
		}

		if($this->hasData()) {

			$sValue = $this->getValue();

			if(!$this->_emptyFormValue($sValue) && $this->hasData() && !$this->hasValue()) {
				$sHtml = $this->getValueForHtml($sValue);
			}

			$sName = $this->_getElementHtmlName();
			$sId = $this->_getElementHtmlId() . "_value";
			$sHidden = "<input type=\"hidden\" name=\"" . $sName . "\" id=\"" . $sId . "\" value=\"" . $this->getValueForHtml($sValue) . "\" />";
		} elseif($this->isDataBridge()) {

			$sDBridgeName = $this->_getElementHtmlName() . "[databridge]";
			$sDBridgeId = $this->_getElementHtmlId() . "_databridge";
			$sSignature = $this->dbridge_getCurrentDsetSignature();
			$sHidden = "<input type=\"hidden\" name=\"" . $sDBridgeName . "\" id=\"" . $sDBridgeId . "\" value=\"" . htmlspecialchars($sSignature) . "\" />";

			#$sHidden .= "<input type=\"hidden\" name=\"" . $this->oForm->formid . "[_databridge][" . base64_encode($this->_getElementHtmlIdWithoutFormId()) . "]\" value=\"" . base64_encode($this->oDataSource->getName()) . "\" />";
			#debug($sHidden2, "hidden2");
		}

		if($sMode !== "inline") {
			$sBegin = "<" . $sMode . " id='" . $this->_getElementHtmlId() . "' " . $this->_getAddInputParams() . ">";
			$sEnd = "</" . $sMode . ">" . $sHidden;
		} else {
			$sBegin = "<!--BEGIN:BOX:inline:" . $this->_getElementHtmlId() . "-->";
			$sEnd = "<!--END:BOX:inline:" . $this->_getElementHtmlId() . "-->";
		}

		$aChilds = $this->renderChildsBag();
		$sCompiledChilds = '';
		if(!empty($aChilds)) {
			$sCompiledChilds = $this->renderChildsCompiled(
				$aChilds
			);
		}

		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts(
			array(
				"hasdata" => $this->hasData(),
			)
		);

		if(($mDraggable = $this->_navConf("/draggable")) !== FALSE) {

			$aConf = array();

			if(is_array($mDraggable)) {
				if($this->defaultTrue("/draggable/use") === TRUE) {
					$bDraggable = TRUE;
					$aConf["revert"] = $this->defaultFalse("/draggable/revert");

					if(($sHandle = $this->_navConf("/draggable/handle")) !== FALSE) {
						$aConf["handle"] = $this->oForm->aORenderlets[$sHandle]->_getElementHtmlId();
					}

					if(($sConstraint = $this->_navConf("/draggable/constraint")) !== FALSE) {
						$aConf["constraint"] = strtolower($sConstraint);
					}
				}
			} else {
				$bDraggable = TRUE;
			}

			if($bDraggable === TRUE) {

				$sHtmlId = $this->_getElementHtmlId();

				$sJson = $this->oForm->array2json($aConf);
/*
				$sScript = '
new Draggable("' . $sHtmlId . '", ' . $sJson . ');
';
*/
				$sScript = 'Formidable.draggable("#' . $sHtmlId . '", ' . $sJson . ');';
				$this->oForm->attachInitTask($sScript);
			}
		}

		if(($mDroppable = $this->_navConf("/droppable")) !== FALSE) {

			$aConf = array();

			if(is_array($mDroppable)) {
				if($this->defaultTrue("/droppable/use") === TRUE) {
					$bDroppable = TRUE;

					if(($sAccept = $this->_navConf("/droppable/accept")) !== FALSE) {
						//$aConf["accept"] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $sAccept);
						$aConf["accept"] = $sAccept;
					}

					if(($sContainment = $this->_navConf("/droppable/containment")) !== FALSE) {
						$aConf["containment"] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($sContainment);
						reset($aConf["containment"]);
						while(list($iKey,) = each($aConf["containment"])) {
							$aConf["containment"][$iKey] = $this->oForm->aORenderlets[$aConf["containment"][$iKey]]->_getElementHtmlId();
						}
					}

					if(($sHoverClass = $this->_navConf("/droppable/hoverclass")) !== FALSE) {
						$aConf["hoverclass"] = $sHoverClass;
					}

					if(($sOverlap = $this->_navConf("/droppable/overlap")) !== FALSE) {
						$aConf["overlap"] = $sOverlap;
					}

					if(($bGreedy = $this->defaultFalse("/droppable/greedy")) !== FALSE) {
						$aConf["greedy"] = $bGreedy;
					}
				}
			} else {
				$bDroppable = TRUE;
			}

			if($bDroppable === TRUE) {

				$aTemp = array();

				$sHtmlId = $this->_getElementHtmlId();

				if(array_key_exists("ondragdrop", $this->aCustomEvents)) {
					$sJs = implode("\n", $this->aCustomEvents["ondragdrop"]);
					$sRandKey = md5(rand());
					$aTemp["###" . $sRandKey . "###"] = "function() {" . $sJs . "}";
					$aConf["onDrop"] = "###" . $sRandKey . "###";
				}

				if(array_key_exists("ondraghover", $this->aCustomEvents)) {
					$sJs = implode("\n", $this->aCustomEvents["ondraghover"]);
					$sRandKey = md5(rand());
					$aTemp["###" . $sRandKey . "###"] = "function() {" . $sJs . "}";
					$aConf["onHover"] = "###" . $sRandKey . "###";
				}

				$sJson = $this->oForm->array2json($aConf);
				reset($aTemp);
				while(list($sKey,) = each($aTemp)) {
					$sJson = str_replace('"' . $sKey . '"', $aTemp[$sKey], $sJson);
				}

/*
				$sScript = '
Droppables.add("' . $sHtmlId . '", ' . $sJson . ');
';
*/				$sScript = 'Formidable.droppable("#' . $sHtmlId . '", ' . $sJson . ');';
				$this->oForm->attachInitTask($sScript);
			}
		}

		//debug($this->aCustomEvents);

		$aHtmlBag = array(
			"__compiled" => $this->_displayLabel($sLabel) . $sBegin . $sHtml . $sCompiledChilds . $sEnd,
			"html" => $sHtml,
			"box." => array(
				"begin" => $sBegin,
				"end" => $sEnd,
				"mode" => $sMode,
			),
			"childs" => $aChilds
		);

		return $aHtmlBag;
	}

	function mayBeDataBridge() {
		return TRUE;
	}

	function setHtml($sHtml) {
		$this->aElement["html"] = $sHtml;
	}

	function _readOnly() {
		return TRUE;
	}

	function _renderOnly() {
		return $this->defaultTrue("/renderonly/");
	}

	function _renderReadOnly() {
		return $this->_render();
	}

	function _activeListable() {
		return $this->oForm->defaultTrue("/activelistable/", $this->aElement);
	}

	function _debugable() {
		return $this->oForm->defaultFalse("/debugable/", $this->aElement);
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

	function majixReplaceData($aData) {
		return $this->buildMajixExecuter(
			"replaceData",
			$aData
		);
	}

	function majixSetHtml($sData) {
		return $this->buildMajixExecuter(
			"setHtml",
			$this->oForm->_substLLLInHtml($sData)
		);
	}

	function majixSetValue($sData) {
		return $this->buildMajixExecuter(
			"setValue",
			$sData
		);
	}

	function majixToggleDisplay() {
		return $this->buildMajixExecuter(
			"toggleDisplay"
		);
	}

	function majixValidate($mErrorMethod = FALSE, $mValidMethod = FALSE) {
		$this->oForm->clearValidation();

		if($this->hasChilds()) {
			$aChildKeys = array_keys($this->aChilds);
			reset($aChildKeys);
			while(list(, $sKey) = each($aChildKeys)) {
				$this->aChilds[$sKey]->validate();
			}
		}

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

	function majixRepaint() {
		#$bBefore = $this->oForm->oRenderer->bDisplayLabels;
		#$this->oForm->oRenderer->bDisplayLabels = FALSE;
		$this->initDataSource();
		$aHtmlBag = $this->render();

		#$this->oForm->oRenderer->bDisplayLabels = $bBefore;

		return $this->buildMajixExecuter(
			"repaint",
			$aHtmlBag["__compiled"]
		);
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function _emptyFormValue($sValue) {

		if($this->hasData()) {
			return (trim($sValue) === "");
		}

		return TRUE;
	}

	function hasValue() {
		return ($this->_navConf("/data/value") !== FALSE || $this->_navConf("/data/defaultvalue") !== FALSE);
	}

	function _searchable() {
		if($this->hasData()) {
			return $this->defaultTrue("/searchable/");
		}

		return $this->defaultFalse("/searchable/");
	}

/*	function alterAjaxEventParams($aParams) {

//		debug($aParams["params"]);
//		$aParams["params"][] = "context::arguments[0]['id']";
//		$aParams["params"][] = "context::arguments[1]['id']";

		return $aParams;
	}*/

	function doAfterListRender(&$oListObject) {
		#debug($this->_getElementHtmlId(), "doBeforeListRender");
		parent::doAfterListRender($oListObject);

		if($this->hasChilds()) {
			$aChildKeys = array_keys($this->aChilds);
			reset($aChildKeys);
			while(list(, $sKey) = each($aChildKeys)) {
				$this->aChilds[$sKey]->doAfterListRender($oListObject);
			}
		}
	}

	function setValue($mValue) {

		# distributing values to childs

		if(is_array($mValue)) {
			$aKeys = array_keys($mValue);

			reset($aKeys);
			while(list(, $sKey) = each($aKeys)) {
				if(array_key_exists($sKey, $this->aChilds)) {
					$this->aChilds[$sKey]->setValue($mValue[$sKey]);
				}
			}
		}

	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_box/api/class.tx_rdtbox.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_box/api/class.tx_rdtbox.php"]);
	}

?>
