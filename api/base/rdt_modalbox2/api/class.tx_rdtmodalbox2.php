<?php
/** 
 * Plugin 'rdt_modalbox2' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtmodalbox2 extends formidable_mainrenderlet {
	
	var $aLibs = array(
		"rdt_modalbox2_class" => "res/js/modalbox2.js",
		"rdt_modalbox2_lib_class" => "res/js/modalbox1.6.0/modalbox.js",
	);

	var $bCustomIncludeScript = TRUE;
	var $sMajixClass = "ModalBox2";
	
	var $aPostInitTasks = array();
	var $aPreUninitTasks = array();

	function _render() {
		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts();

		return "";
	}
	
	function hasDomAtLoad()		{ return FALSE;}

	function _renderReadOnly()	{ return $this->_render();}
	function _readOnly()		{ return TRUE;}
	function _renderOnly()		{ return TRUE;}
	function mayHaveChilds()	{ return TRUE;}

	function majixShowBox($aConfig=array(), $aTags=array()) {

		if($this->oForm->__getEnvExecMode() !== "EID") {
			$aEventsBefore = array_keys($this->oForm->aRdtEvents);
		}

		$aChildsBag = $this->renderChildsBag();
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($aChildsBag, $aTags);

		if($this->oForm->__getEnvExecMode() !== "EID") {
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

			$aConfig["attachevents"] = $aAddedEvents;
			$aConfig["postinit"] = $this->oForm->aPostInitTasks;
		} else {
			# specific to this renderlet
				# as events have to be attached to the HTML
				# after the execution of the majix tasks
					# in that case, using the modalbox's afterLoad event handler
			$aConfig["attachevents"] = $this->oForm->aRdtEventsAjax;
			$aConfig["postinit"] = $this->oForm->aPostInitTasksAjax;
			$this->oForm->aRdtEventsAjax = array();
			$this->oForm->aPostInitTasksAjax = array();
		}
		
		$aConfig["postinit"] = array_merge($this->aPostInitTasks, $aConfig["postinit"]);
		$aConfig["preuninit"] = $this->aPreUninitTasks;

		$sCompiledChilds = $this->renderChildsCompiled(
			$aChildsBag
		);

		$aConfig["html"] = $sCompiledChilds;

		return $this->buildMajixExecuter(
			"showBox",
			$aConfig
		);
	}

	function majixCloseBox($aOptions = FALSE) {
		return $this->buildMajixExecuter(
			"closeBox",
			$aOptions
		);
	}

	function majixRepaint() {
		return $this->buildMajixExecuter(
			"repaint",
			$this->renderChildsCompiled(
				$this->renderChildsBag()
			)
		);
	}

	function majixResizeToContent() {
		return $this->buildMajixExecuter(
			"resizeToContent"
		);
	}

	function majixResizeToInclude($sHtmlId) {
		return $this->buildMajixExecuter(
			"resizeToInclude",
			$sHtmlId
		);
	}

	// this has to be static !!!
	function loaded(&$aParams) {
		$aParams["form"]->oJs->loadScriptaculous();
		$sCss = $aParams["form"]->toServerPath("EXT:ameos_formidable/api/base/rdt_modalbox2/res/js/modalbox1.6.0/modalbox.css");
		$aParams["form"]->additionalHeaderDataLocalStylesheet(
			$sCss
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


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_modalbox2/api/class.tx_rdtmodalbox2.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_modalbox2/api/class.tx_rdtmodalbox2.php"]);
	}

?>
