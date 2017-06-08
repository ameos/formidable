<?php
/** 
 * Plugin 'va_file' for the 'ameos_formidable' extension.
 *
 * @author	Luc Muller <typo3dev@ameos.com>
 */


class tx_vafile extends formidable_mainvalidator {
	
	var $oFileFunc = null; //object for basics file function
	
	function validate($oRdt) {

		$sAbsName = $oRdt->getAbsName();
		$sFileName = $oRdt->getValue();

		$bError = FALSE;

		$this->oFileFunc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("t3lib_basicFileFunctions");

		if($oRdt->_getType() === "FILE") {	// renderlet:FILE
			$sFileName = strtolower($this->oFileFunc->cleanFileName($sFileName));
		} elseif($oRdt->_getType() === "UPLOAD") {
			// managing multiple, if set

			if($sFileName !== "") {
				if($oRdt->isMultiple()) {
					$aFileList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $sFileName);
					$sFileName = array_pop($aFileList);	// last one, and remove from list; will be added later if valid
				}
			}
		}

		if($sFileName === "") {
			// never evaluate if value is empty
			// as this is left to STANDARD:required
			return "";
		}

		$aKeys = array_keys($this->_navConf("/"));
		reset($aKeys);
		while(!$oRdt->hasError() && list(, $sKey) = each($aKeys)) {

			/***********************************************************************
			*
			*	/extension
			*
			***********************************************************************/

			if($sKey{0} === "e" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "extension")) {

				$sFileExts = $this->_navConf("/" . $sKey . "/value");
				if(tx_ameosformidable::isRunneable($sFileExts)) {
					$sFileExts = $this->callRunneable($sFileExts);
				}

				$aExtensions = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
					",",
					$sFileExts
				);

				if(!$this->_checkFileExt($sFileName, $aExtensions, $sAbsName)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"FILE:extension",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$sFileName
					);

					$bError = TRUE;
					break;
				}
			}




			
			/***********************************************************************
			*
			*	/filesize
			*
			***********************************************************************/

			if($sKey{0} === "f" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "filesize") && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "filesizekb") && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "filesizemb")) {
				$mSize = $this->_navConf("/" . $sKey . "/value");

				if(tx_ameosformidable::isRunneable($mSize)) {
					$mSize = $this->callRunneable($mSize);
				}

				if(!$this->_checkFileSize($sFileName, $mSize, $sAbsName)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"FILE:filesize",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$sFileName
					);

					$bError = TRUE;
					break;
				}
			}




			/***********************************************************************
			*
			*	/filesizekb
			*
			***********************************************************************/

			if($sKey{0} === "f" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "filesizekb")) {
				$mSize = $this->_navConf("/" . $sKey . "/value");

				if(tx_ameosformidable::isRunneable($mSize)) {
					$mSize = $this->callRunneable($mSize);
				}

				if(!$this->_checkFileSizeKb($sFileName, $mSize, $sAbsName)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"FILE:filesizekb",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$sFileName
					);

					$bError = TRUE;
					break;
				}
			}




			/***********************************************************************
			*
			*	/filesizemb
			*
			***********************************************************************/

			if($sKey{0} === "f" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sKey, "filesizemb")) {
				$mSize = $this->_navConf("/" . $sKey . "/value");

				if(tx_ameosformidable::isRunneable($mSize)) {
					$mSize = $this->callRunneable($mSize);
				}

				if(!$this->_checkFileSizeMb($sFileName, $mSize, $sAbsName)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						"FILE:filesizemb",
						$this->oForm->_getLLLabel($this->_navConf("/" . $sKey . "/message")),
						$sFileName
					);

					$bError = TRUE;
					break;
				}
			}
		}

		if($bError === TRUE && $oRdt->_getType() === "UPLOAD") {
			if($oRdt->isMultiple()) {
				// current filenamehas been remove from aFileList by previous array_pop
				$oRdt->setValue(implode(", ", $aFileList));
			} else {
				$oRdt->setValue("");
			}
		}
	}
	
	function _checkFileExt($sFileName,$aValues, $sAbsName) {
		
		if($sFileName !== "") {
			
			$aFileName = explode(".",$sFileName);
			$sExt = array_pop($aFileName);
			$sExt = strtolower(str_replace(".", "", $sExt));
			
			foreach($aValues as $key=>$val) {
				
				$val = strtolower(str_replace(".", "", $val));
				
				if($val === $sExt) {
					return TRUE;
				}
			}

			// no match, unlink
			$this->_unlink(
				$this->_getFullServerPath($sAbsName, $sFileName)
			);
			
			return FALSE;
		}

		return TRUE;
	}

	function _checkFileSizeKb($sFileName, $iMaxFileSize, $sAbsName) {
		return $this->_checkFileSize($sFileName, $iMaxFileSize, $sAbsName, "kilobyte");
	}

	function _checkFileSizeMb($sFileName, $iMaxFileSize, $sAbsName) {
		return $this->_checkFileSize($sFileName, $iMaxFileSize, $sAbsName, "megabyte");
	}

	function _checkFileSize($sFileName, $iMaxFileSize, $sAbsName, $sType = "byte") {

		if(!empty($sFileName)) {

			$sFullPath = $this->_getFullServerPath($sAbsName, $sFileName);
			require_once(PATH_t3lib . "class.t3lib_basicfilefunc.php");
			$aInfos = t3lib_basicFileFunctions::getTotalFileInfo($sFullPath);

			switch($sType) {
				case "kilobyte": {
					$iMaxFileSize = $iMaxFileSize * 1024;
					break;
				}
				case "megabyte": {
					$iMaxFileSize = $iMaxFileSize * 1024 * 1024;
					break;
				}
			}

			if(intval($aInfos["size"]) <= intval($iMaxFileSize)) {
				return TRUE;
			} else {
				$this->_unlink($sFullPath);
				return FALSE;
			}
		}

		return TRUE;
	}
   
	function _getFullServerPath($sAbsName, $sFileName) {
		return $this->oForm->aORenderlets[$sAbsName]->getFullServerPath($sFileName);
	}
	
	function _unlink($sFullPath) {
		@unlink($sFullPath);
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/va_file/api/class.tx_vafile.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/va_file/api/class.tx_vafile.php"]);
	}
?>
