<?php
/** 
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdttabpanel extends formidable_mainrenderlet {
	
	var $sMajixClass = "TabPanel";
	var $aLibs = array(
		"rdt_tabpanel_lib" => "res/js/libs/control.tabs.2.1.1.js",
		"rdt_tabpanel_class" => "res/js/tabpanel.js",
	);
	var $bCustomIncludeScript = TRUE;

	function _render() {
		
		$sBegin = "<ul id='" . $this->_getElementHtmlId() . "' " . $this->_getAddInputParams() . " onmouseup='this.blur()'>";
		$sEnd = "</ul>";

		$sCssId = $this->_getElementCssId();

		$aTabs = array();
		$aHtmlTabs = array();
		reset($this->aChilds);
		while(list($sName, ) = each($this->aChilds)) {
			$oRdt =& $this->aChilds[$sName];

			if($oRdt->_getType() == "TAB") {

				$sId = $oRdt->_getElementHtmlId();
				$aTabs[$sId] = array(
					"name" => $sName,
					"label" => $this->getLabel(),
					"htmlid" => $sId,
				);
			}
		}

		$aConfig = array(
			"activeClassName" => "active",
			"defaultTab" => "first",
			"linkSelector" => "li a.rdttab",
			"tabs" => $aTabs
		);

		if(($aUserConfig = $this->_navConf("config")) !== FALSE) {

			if(array_key_exists("activeclassname", $aUserConfig)) {
				$aConfig["activeClassName"] = $aUserConfig["activeclassname"];
			}

			if(array_key_exists("defaulttab", $aUserConfig)) {
				if(array_key_exists($aUserConfig["defaulttab"], $this->aChilds)) {
					// tab id
					$aConfig["defaultTab"] = $this->oForm->aORenderlets[$this->aChilds[$aUserConfig["defaulttab"]]->_navConf("/content")]->_getElementHtmlId();
				} else {
					// first, last, none
					$aConfig["defaultTab"] = $aUserConfig["defaulttab"];
				}
			}
		}

		$this->includeScripts(
			array(
				"libconfig" => $aConfig,
				"tabs" => $aTabs
			)
		);

		$aChilds = $this->renderChildsBag();
		$sCompiledChilds = $this->renderChildsCompiled(
			$aChilds
		);

		$aHtmlBag = array(
			"__compiled" => $this->_displayLabel($sLabel) . $sBegin . $sCompiledChilds . $sEnd,
			"ul." => array(
				"begin" => $sBegin,
				"end" => $sEnd,
			),
			"childs" => $aChilds
		);

		return $aHtmlBag;
	}
	
	function includeScripts($aLibs) {
		$sAbsName = $this->getAbsName();
		$sInitScript =<<<INITSCRIPT
		try {
			if(Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").domNode()) {
				Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").oTabPanel = new Control.Tabs(
					Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").domNode(),
					Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").config.libconfig
				);
			}
		} catch(e) {}
		
INITSCRIPT;
		
		$this->sys_attachPostInitTask(
				$sInitScript,
				"Post-init TABPANEL",
				$this->_getElementHtmlId()
		);
		
		parent::includeScripts($aLibs);
	}
	
	function _readOnly() {
		return TRUE;
	}

	function _renderOnly() {
		return TRUE;
	}
	
	function _renderReadOnly() {
		return $this->_render();
	}
	
	function _debugable() {
		return $this->oForm->defaultFalse("/debugable/", $this->aElement);
	}

	function majixSetActiveTab($sTab) {
		return $this->buildMajixExecuter(
			"setActiveTab",
			$this->oForm->aORenderlets[$this->aChilds[$sTab]->_navConf("/content")]->_getElementHtmlId()
		);
	}

	function majixNextTab() {
		return $this->buildMajixExecuter(
			"next"
		);
	}

	function majixPreviousTab() {
		return $this->buildMajixExecuter(
			"previous"
		);
	}

	function majixFirstTab() {
		return $this->buildMajixExecuter(
			"first"
		);
	}

	function majixLastTab() {
		return $this->buildMajixExecuter(
			"last"
		);
	}

	function mayHaveChilds() {
		return TRUE;
	}
	
	function shouldAutowrap() {
		return $this->oForm->defaultFalse("/childs/autowrap/");
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_tabpanel/api/class.tx_rdttabpanel.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_tabpanel/api/class.tx_rdttabpanel.php"]);
	}

?>