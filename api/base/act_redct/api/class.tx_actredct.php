<?php
/** 
 * Plugin 'act_redct' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_actredct extends formidable_mainactionlet {
	
	function _doTheMagic($aRendered, $sForm) {
		
		$sUrl = "";
		
		if($this->oForm->oDataHandler->_allIsValid()) {
			
			if(($mPage = $this->_navConf("/pageid")) !== FALSE) {
				
				if(tx_ameosformidable::isRunneable($mPage)) {
					$mPage = $this->callRunneable($mPage);
				}
				
				$sUrl = $this->oForm->cObj->typolink_URL(array(
					"parameter" => $mPage
				));
				
				if(!t3lib_div::isFirstPartOfStr($sUrl, "http://") && trim($GLOBALS["TSFE"]->baseUrl) !== "") {
					$sUrl = $this->oForm->_removeEndingSlash($GLOBALS["TSFE"]->baseUrl) . "/" . $sUrl;
				}
			} else {
				
				$sUrl = $this->_navConf("/url");
				if(tx_ameosformidable::isRunneable($sUrl)) {
					$sUrl = $this->callRunneable($sUrl);
				}
			}

			if(is_string($sUrl) && trim($sUrl) !== "") {
				header("Location: " . $sUrl);
				exit();
			}
		}
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/act_redct/api/class.tx_actredct.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/act_redct/api/class.tx_actredct.php"]);
	}

?>
