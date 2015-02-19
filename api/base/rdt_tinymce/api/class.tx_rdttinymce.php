<?php
/** 
 * Plugin 'rdt_tinymce' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

//	FOR CONFIGURATON REFERENCE, SEE
//		http://wiki.moxiecode.com/index.php/TinyMCE:Configuration

class tx_rdttinymce extends formidable_mainrenderlet {
	
	var $aLibs = array(
		"rdt_tinymce_lib" => "res/tiny_mce/tiny_mce_src.js",
	);

	function _render() {
		
		
		
		$sLabel = $this->getLabel();
		$sValue = $this->oForm->div_rteTohtml(
			$this->getValue()
		);
		$sValueForHtml = $this->getValueForHtml($sValue);
		
		$aUserConf = array();
		if(!is_array(($aUserConfig = $this->_navConf("/config")))) {
			$aUserConfig = array();
		}

		/****
			We are refining the /config array, as each key might be LLL:, XPATH: or TS:
				And these prefixes are not automatically handled on sub-levels of returned arrays
				by _navConf() for performance reasons
		*/

		$aKeys = array_keys($aUserConfig);
		reset($aKeys);
		while(list(,$sKey) = each($aKeys)) {
			$aUserConf[$sKey] = $this->_navConf("/config/" . $sKey);
		}

		reset($aUserConf);

		/*
		****/

		$aAdditionalStyle = array();

		if(is_array($aUserConfig) && !empty($aUserConfig)) {
			// this is done on textarea to set size on the control whenever javascript is not enabled
				// thanks to Hauke Hain for providing the patch

			if(($sWidth = $this->_navConf("/width", $aUserConfig)) !== FALSE) {
				$aAdditionalStyle[] = "width:" . intval($sWidth) . "px";
			}

			if(($sHeight = $this->_navConf("/height", $aUserConfig)) !== FALSE) {
				$aAdditionalStyle[] = "height:" . intval($sHeight) . "px";
			}
		}

		if(!empty($aAdditionalStyle)) {
			$sStyle = " style=\"" . implode(";", $aAdditionalStyle) . "\" ";
		}

		$sInput = "<textarea name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" rows='2' cols='20'" . $sStyle . ">" . $sValueForHtml . "</textarea>";

		$aHtmlBag = array(
			"__compiled" => $this->_displayLabel($sLabel) . $sInput,
			"input" => $sInput,
		);
		
		if(($sApiPath = $this->_navConf("/config/tinymce_path")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($sApiPath)) {
				$sApiPath = $this->oForm->callRunneable($sApiPath);
			}

			if($sApiPath !== FALSE) {
				$sApiPath = $this->oForm->_removeEndingSlash($sApiPath) . "/";
			}
		}
		
		if(empty($sApiPath)) {
			$sApiPath = $this->sExtPath  ."res/tiny_mce/";
		}

		/*	$this->oForm->additionalHeaderDataLocalScript(
			$sApiPath . "tiny_mce.js",
			"ameosformidable_tx_rdttinymce"
		);	*/


		$aConfig = array(
			"mode" => "exact",
			"elements" => $this->_getElementHtmlId(),
		);
		
		if(is_array($aUserConfig) && !empty($aUserConfig)) {
			$aConfig = t3lib_div::array_merge_recursive_overrule($aUserConfig, $aConfig);
		}

		if(!array_key_exists("theme", $aConfig)) {
			$aConfig["theme"] = "simple";
		}

		if(array_key_exists("content_css", $aConfig)) {
			$aConfig["content_css"] = $this->oForm->toWebPath($aConfig["content_css"]);
		}

		if(is_array($aAddConfig = $this->_navConf("/addconfig")) && tx_ameosformidable::isRunneable($aAddConfig)) {
			$aAddConfig = $this->callRunneable($aAddConfig);
			if(is_array($aAddConfig) && !empty($aAddConfig)) {
				$aConfig = t3lib_div::array_merge_recursive_overrule($aConfig, $aAddConfig);
			}
		}

		//	FOR CONFIGURATON REFERENCE, SEE
		//		http://wiki.moxiecode.com/index.php/TinyMCE:Configuration
		$aConfig['convert_urls'] = FALSE;
		$sJson = $this->oForm->array2json($aConfig);

		$this->oForm->attachInitTask(
			"tinyMCE.init(" . $sJson . ");",
			"TinyMCE " . $this->_getElementHtmlId() . " initialization",
			$this->_getElementHtmlId(),
			TRUE
		);

		

		return $aHtmlBag;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_tinymce/api/class.tx_rdttinymce.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_tinymce/api/class.tx_rdttinymce.php"]);
	}
?>
