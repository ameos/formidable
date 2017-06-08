<?php
/** 
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtmodalbox extends formidable_mainrenderlet {
	
	var $aLibs = array(
		"rdt_modalbox_class" => "res/js/modalbox.js",
	);

	var $bCustomIncludeScript = TRUE;
	var $sMajixClass = "ModalBox";	
	var $aPostInitTasks = array();
	var $aPreUninitTasks = array();
	
	var $oDataSource = FALSE;
	
	function _render() {
		
		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts(
			array(
				"followScrollVertical" => $this->defaultTrue("/followscrollvertical"),
				"followScrollHorizontal" => $this->defaultTrue("/followscrollhorizontal"),
			)
		);

		return "";
	}
	
	function hasDomAtLoad() {
		return FALSE;
	}

	function _renderReadOnly()	{ return $this->_render();}
	function _readOnly()		{ return TRUE;}
	function _renderOnly()		{ return TRUE;}
	function mayHaveChilds()	{ return TRUE;}
	function mayBeDataBridge()	{ return TRUE;}

	function majixShowFreshBox($aConfig = array(), $aTags = array()) {

		$this->initChilds(
			TRUE	// existing renderlets in $this->oForm->aORenderlets will be overwritten
		);	// re-init childs before rendering

		$this->oForm->oDataHandler->refreshAllData();

		return $this->majixShowBox($aConfig, $aTags);
	}
	
	function majixShowBox($aConfig = array(), $aTags = array()) {

		if($this->oForm->__getEnvExecMode() !== "EID") {
			#$bOldValue = $this->oForm->bInlineEvents;
			#$this->oForm->bInlineEvents = TRUE;
			$aEventsBefore = array_keys($this->oForm->aRdtEvents);
			//debug($aEventsBefore);
		}
		
		if($this->isDataBridge()) {
			$sDBridgeName = $this->_getElementHtmlName() . "[databridge]";
			$sDBridgeId = $this->_getElementHtmlId() . "_databridge";
			$sSignature = $this->dbridge_getCurrentDsetSignature();
			$sHidden = "<input type=\"hidden\" name=\"" . $sDBridgeName . "\" id=\"" . $sDBridgeId . "\" value=\"" . htmlspecialchars($sSignature) . "\" />";
		}

		$aChildsBag = $this->renderChildsBag();
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($aChildsBag, $aTags);

		if($this->oForm->__getEnvExecMode() !== "EID") {
			#$this->oForm->bInlineEvents = $bOldValue;
			$aEventsAfter = array_keys($this->oForm->aRdtEvents);
			$aAddedKeys = array_diff($aEventsAfter, $aEventsBefore);
			$aAddedEvents = array();
			reset($aAddedKeys);
			while(list(, $sKey) = each($aAddedKeys)) {
				$aAddedEvents[$sKey] = $this->oForm->aRdtEvents[$sKey];
				unset($this->oForm->aRdtEvents[$sKey]);
				// unset because if rendered in a lister,
					// we need to be able to detect the new events even if they were already declared by other loops in the lister
			}

			//debug($aAddedEvents);
			$aConfig["attachevents"] = $aAddedEvents;
		}

		$sCompiledChilds = $this->renderChildsCompiled(
			$aChildsBag
		);

		$aConfig["html"] = $sCompiledChilds;
		$aConfig["postinit"] = $this->aPostInitTasks;
		$aConfig["preuninit"] = $this->aPreUninitTasks;

		return $this->buildMajixExecuter(
			"showBox",
			$aConfig
		);
	}

	function majixCloseBox() {
		return $this->buildMajixExecuter(
			"closeBox"
		);
	}

	function loadModalBox(&$oForm) {
		$oForm->oJs->loadScriptaculous();
		//$this->loadNiftyCube();

		$sPath = $oForm->_removeEndingSlash(PATH_site) . "/" . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath("ameos_formidable") . "api/base/rdt_modalbox/res/js/modalbox.js";

		$oForm->additionalHeaderDataLocalScript(
			$sPath,
			"rdt_modalbox_class"
		);
	}

	// this has to be static !!!
	static function loaded(&$aParams) {
		if($aParams["form"]) $aParams["form"]->oJs->loadScriptaculous();
	}

	function majixRepaint() {
		return $this->buildMajixExecuter(
			"repaint",
			$this->renderChildsCompiled(
				$this->renderChildsBag()
			)
		);
	}
	
	function attachPostInitTask($sScript, $sDesc = "", $sKey = FALSE) {
		if($sKey === FALSE) {
			$this->aPostInitTasks[] = $sScript;
		} else {
			$this->aPostInitTasks[$sKey] = $sScript;
		}
	}
	
	function attachPreUninitTask($sScript, $sDesc = "", $sKey = FALSE) {
		if($sKey === FALSE) {
			$this->aPreUninitTasks[] = $sScript;
		} else {
			$this->aPreUninitTasks[$sKey] = $sScript;
		}
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
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_modalbox/api/class.tx_rdtmodalbox.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_modalbox/api/class.tx_rdtmodalbox.php"]);
	}

?>
