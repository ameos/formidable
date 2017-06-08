<?php
/** 
 * Plugin 'rdt_dewplayer' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtdewplayer extends formidable_mainrenderlet {
	
	function _render() {

		$sLabel = $this->getLabel();

		$sPath = $this->_getPath();
		$sHtmlId = $this->_getElementHtmlId();
		$sHtmlName = $this->_getElementHtmlName();
		$sAddParams = $this->_getAddInputParams();

		$bAutoStart = $this->oForm->defaultFalse("/autostart", $this->aElement);
		$bAutoReplay = $this->oForm->defaultFalse("/autoreplay", $this->aElement);
		$sBgColor = (($sTempColor = $this->_navConf("/bgcolor")) !== FALSE) ? $sTempColor : "FFFFFF";

		$sMoviePath = $this->sExtWebPath . "res/dewplayer.swf";

		$sColor = str_replace("#", "", $sBgColor);
		$sMoviePath .= "?bgcolor=" . $sColor;
		$sFlashParams .= '<param name="bgcolor" value="' . $sColor . '" />';

		$sFlashParams = "";
		
		if($bAutoStart) {
			$sMoviePath .= "&autostart=1";
			$sFlashParams .= '<param name="autostart" value="1" />';
		}
		
		if($bAutoReplay) {
			$sMoviePath .= "&autoreplay=1";
			$sFlashParams .= '<param name="autoreplay" value="1" />';
		}



		$sMoviePath .= "&mp3=" . rawurlencode($sPath);

		$sInput =<<< FLASHOBJECT
			
			<object
				name=		"{$sHtmlName}"
				id=			"{$sHtmlId}"
				codebase=	"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0"
				type=		"application/x-shockwave-flash"
				data=		"{$sMoviePath}"
				width=		"200"
				height=		"20"
				align=		"middle"
				{$sAddParams}>

				<param name="allowScriptAccess" value="sameDomain" />
				<param name="movie" value="{$sMoviePath}" />
				<param name="quality" value="high" />
				{$sFlashParams}

			</object>

FLASHOBJECT;

		

		$aHtmlBag = array(
			"__compiled" => $this->_displayLabel($sLabel) . $sInput,
			"input" => $sInput,
			"mp3." => array(
				"file" => $sPath,
			)
		);
		
		return $aHtmlBag;
	}

	function _renderOnly() {
		return true;
	}
	
	function _getPath() {
		
		if(($sPath = $this->_navConf("/path")) !== FALSE) {
			
			if(tx_ameosformidable::isRunneable($sPath)) {
				$sPath = $this->callRunneable($sPath);
			}

			if(\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sPath, "EXT:")) {
				
				$sPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("TYPO3_SITE_URL") .
					str_replace(
						\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("TYPO3_DOCUMENT_ROOT"),
						"",
						\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($sPath)
					);
			}
		}
		
		return $sPath;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_dewplayer/api/class.tx_rdtdewplayer.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_dewplayer/api/class.tx_rdtdewplayer.php"]);
	}
?>