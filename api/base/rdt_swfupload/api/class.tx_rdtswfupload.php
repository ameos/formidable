<?php
/** 
 * Plugin 'rdt_swfupload' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_rdtswfupload extends formidable_mainrenderlet {
	
	var $sUploadedFileName = "";
	var $aUploadedFileNameByOriginalFileName = array();
	
	var $aLibs = array(
		"rdt_swfupload_lib" => "res/js/swfupload.js",
		"rdt_swfupload_lib_cookies" => "res/js/swfupload.cookies.js",
//		"rdt_swfupload_lib_queue" => "res/js/swfupload.queue.js",
//		"rdt_swfupload_lib_queuetracker" => "res/js/swfupload.queuetracker.js",
		"rdt_swfupload_class" => "res/js/rdt_swfupload.js",
	);

	var $sMajixClass = "SwfUpload";

	var $oButtonBrowse = FALSE;
	var $oButtonUpload = FALSE;
	var $oListQueue = FALSE;

	var $bCustomIncludeScript = TRUE;

	var $aPossibleCustomEvents = array(
		"onuploadprogress",
		"ondialogstart",
		"ondialogclose",
		"onuploadstart",
		"onuploadsuccess",
		"onuploaderror",
		"onuploadcomplete",
		"onfilequeued",
		"onqueueerror",
		"onqueueerrorfilesize",
		"onqueueerrorfiletype",
		"onqueuecomplete",
	);

	function _render() {
		
		$this->oForm->bStoreFormInSession = TRUE;	// requesting eID context for upload-service

		$this->initButtonBrowse();
		$this->initButtonUpload();
		$this->initListQueue();

		$aButtonBrowse = $this->oForm->_renderElement($this->oButtonBrowse);
		$aButtonUpload = $this->oForm->_renderElement($this->oButtonUpload);
		$aListQueue = $this->oForm->_renderElement($this->oListQueue);


		/* forging access to upload service */

		$sHtmlId = $this->_getElementHtmlId();
		$sObject = "rdt_swfupload";
		$sServiceKey = "upload";
		$sFormId = $this->oForm->formid;
		$sSafeLock = $this->_getSessionDataHashKey();
		$sThrower = $sHtmlId;

		$sUrl = $this->oForm->_removeEndingSlash(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("TYPO3_SITE_URL")) . "/index.php?eID=tx_ameosformidable&object=" . $sObject . "&servicekey=" . $sServiceKey . "&formid=" . $sFormId . "&safelock=" . $sSafeLock . "&thrower=" . $sThrower;
		$sButtonUrl = $this->oForm->getLLLabel("LLL:EXT:ameos_formidable/api/base/rdt_swfupload/res/locallang.xml:buttonbrowse.image_url");

		$aConf = array(
			"buttonBrowseId" => $this->oButtonBrowse->_getElementHtmlId(),
			"buttonUploadId" => $this->oButtonUpload->_getElementHtmlId(),
			"listQueueId" => $this->oListQueue->_getElementHtmlId(),
			"swfupload_config" => array(
				"upload_url" => $sUrl,
				"flash_url" => $this->sExtWebPath . "res/flash/swfupload.swf",
				"file_post_name" => "rdt_swfupload",
				"file_size_limit" => $this->getMaxUploadSize(),	// KiloBytes
				
				"file_types_description" => $this->getFileTypeDesc(),
				"file_types" => $this->getFileType(),

				"file_queue_limit" => $this->getQueueLimit(),

				"button_placeholder_id" => $this->oButtonBrowse->_getElementHtmlId(),
				"button_image_url" => $this->oForm->toWebPath($sButtonUrl),
				"button_width" => "61",
				"button_height" => "22",
				/*"button_text" => "<span class='theFont'>Browse</span>",
				"button_text_style" => ".theFont { font-size: 12; font-family: Verdana; text-align: center;}",
				"button_text_left_padding" => 0,
				"button_text_top_padding" => 2,*/
			),
		);

		$this->includeScripts($aConf);

		$sAddInputParams = $this->_getAddInputParams();

		$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"][$sObject][$sServiceKey][$sSafeLock] = array(
			"requester" => array(
				"name" => $this->getAbsName(),
				"xpath" => $this->sXPath,
			),
		);
		
		$aButtonBrowse["__compiled"] .= "<input type=\"hidden\" id=\"" . $this->_getElementHtmlId() . "\" />";
		return array(
			"__compiled" => $aButtonBrowse["__compiled"] . " " . $aButtonUpload["__compiled"] . " " . $aListQueue["__compiled"],
			"buttonBrowse" => $aButtonBrowse,
			"buttonUpload" => $aButtonUpload,
			"listQueue" => $aListQueue
		);
	}

	function includeScripts($aConf) {
		
		parent::includeScripts($aConf);
		$sAbsName = $this->getAbsName();

		$sInitScript =<<<INITSCRIPT
		
		try {
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").oSWFUpload = new SWFUpload(
				Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").config.swfupload_config
			);
		} catch(e) {
			//alert("SWFUpload exception !!!" + e.name + ":" + e.message);
			//throw(e);
		}

INITSCRIPT;

		$sUninitScript =<<<UNINITSCRIPT
		
		try {
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").destroy();
		} catch(e) {
			//alert("SWFUpload exception !!!" + e.name + ":" + e.message);
			//throw(e);
		}
			
UNINITSCRIPT;
				
		# the SWFUpload initalization is made post-init
			# as when rendered in an ajax context in a modalbox,
			# the HTML is available *after* init tasks
			# as the modalbox HTML is added to the page using after init tasks !
			
		$this->sys_attachPostInitTask(
			$sInitScript,
			"Post-init SWFUPLOAD",
			$this->_getElementHtmlId()
		);
		
		$this->sys_attachPreUninitTask(
			$sUninitScript,
			"Post-init SWFUPLOAD",
			$this->_getElementHtmlId()
		);
	}
	
	function handleAjaxRequest($oRequest) {

		require_once(PATH_t3lib."class.t3lib_basicfilefunc.php");

		$oFile = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("t3lib_basicFileFunctions");
		$aFile = $GLOBALS["_FILES"]["rdt_swfupload"];

		$sOriginalFileName = stripslashes($aFile["name"]);
		$sFileName = $sOriginalFileName;

		if($this->defaultTrue("/usedenypattern") !== FALSE) {
			if(!\TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern($sFileName)) {
				die("FILE EXTENSION DENIED");
			}
		}

		if($this->defaultFalse("/cleanfilename") !== FALSE) {
			$sFileName = strtolower(
				$oFile->cleanFileName($sFileName)
			);
		}

		$sTargetDir = $this->getTargetDir();
		$sTarget = $sTargetDir . $sFileName;
		if(!file_exists($sTargetDir)) {
			if($this->defaultFalse("/data/targetdir/createifneeded") === TRUE) {
				// the target does not exist, we have to create it
				$this->oForm->div_mkdir_deep_abs($sTargetDir);
			}
		}
		
		if(!$this->defaultFalse("/overwrite")) {
			$sExt = ((strpos($sFileName,'.') === FALSE) ? '' : '.' . substr(strrchr($sFileName, "."), 1));

			for($i=1; file_exists($sTarget); $i++) {
				$sTarget = $sTargetDir . substr($sFileName, 0, strlen($sFileName)-strlen($sExt)).'['.$i.']'.$sExt;
			}

			$sFileName = basename($sTarget);
		}
		
		$this->sUploadedFileName = $sFileName;
		$this->aUploadedFileNameByOriginalFileName[$sOriginalFileName] = $sFileName;
		
		\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move(
			$aFile["tmp_name"],
			$sTarget
		);
		
		return array();// die("OK: " . $sTarget);
	}
	
	function getUploadedFileName() {
		return $this->sUploadedFileName;
	}
	
	function getUploadedFileNameForOriginalFileName($sFileName) {
		return $this->aUploadedFileNameByOriginalFileName[$sFileName];
	}
	
	function getTargetDir() {

		require_once(PATH_t3lib . "class.t3lib_basicfilefunc.php");
		$oFileTool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("t3lib_basicFileFunctions");

		if(tx_ameosformidable::isRunneable(($sTargetDir = $this->_navConf("/data/targetdir/")))) {
			$sTargetDir = $this->callRunneable($sTargetDir);
		}

		return \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath(
			$oFileTool->slashPath(
				$oFileTool->rmDoubleSlash(
					$sTargetDir
				)
			)
		);
	}

	function initButtonBrowse() {
		if($this->oButtonBrowse === FALSE) {
			$sName = $this->getAbsName();

			$aConf = array(
				"type" => "BOX",
			);

			$aConf["name"] = $sName . "_btnbrowse";
			$this->oButtonBrowse = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonbrowse/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oButtonBrowse->getAbsName()] =& $this->oButtonBrowse;
		}
	}

/*
	function initButtonBrowse() {
		if($this->oButtonBrowse === FALSE) {
			$sName = $this->_getName();

			$sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sName}"]->majixSelectFiles(),
				);

PHP;

			$aConf = array(
				"type" => "BUTTON",
				"label" => "Browse",
				"onclick-999" => array(			// 999 to avoid overruling by potential customly defined event
					"runat" => "client",
					"userobj" => array(
						"php" => $sEvent,
					),
				),
			);

			if(($aCustomConf = $this->_navConf("/buttonbrowse")) !== FALSE) {
				$aConf = \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sName . "_btnbrowse";

			$this->oButtonBrowse = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonbrowse/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oButtonBrowse->_getName()] =& $this->oButtonBrowse;
		}
	}
*/
	function initButtonUpload() {
		if($this->oButtonUpload === FALSE) {
			$sName = $this->getAbsName();

			$sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sName}"]->majixStartUpload(),
				);

PHP;

			$aConf = array(
				"type" => "BUTTON",
				"label" => "Upload",
				"onclick-999" => array(			// 999 to avoid overruling by potential customly defined event
					"runat" => "client",
					"userobj" => array(
						"php" => $sEvent,
					),
				),
			);

			if(($aCustomConf = $this->_navConf("/buttonupload")) !== FALSE) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sName . "_btnupload";

			$this->oButtonUpload = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonupload/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oButtonUpload->getAbsName()] =& $this->oButtonUpload;
		}
	}
	
	function initListQueue() {
		if($this->oListQueue === FALSE) {
			$sName = $this->getAbsName();

			$aConf = array(
				"type" => "LISTBOX",
				"label" => "Queue",
				"multiple" => true,
				"style" => "width: 100%"
			);

			if(($aCustomConf = $this->_navConf("/listqueue")) !== FALSE) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sName . "_listqueue";
			
			$this->oListQueue = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "listqueue/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oListQueue->getAbsName()] =& $this->oListQueue;


			$sEvent =<<<JAVASCRIPT

	if(this.rdt("{$sName}").domNode()) {
		aParams = this.getParams();
		this.rdt("{$sName}").addFileInQueue(
			aParams["sys_event"].file.name + " [" + aParams["sys_event"].file.humanSize + "]",
			aParams["sys_event"].file.id
		);
	}

JAVASCRIPT;

			$this->aElement["onfilequeued-999"] = array(			// 999 to avoid overruling by potential customly defined event
				"runat" => "client",
				"userobj" => array(
					"js" => $sEvent,
				),
			);

			$sEvent =<<<JAVASCRIPT
	
	if(this.rdt("{$sName}").domNode()) {
		aParams = this.getParams();
		this.rdt("{$sName}").removeFileInQueue(
			aParams["sys_event"].file.id
		);
	}

JAVASCRIPT;

			$this->aElement["onuploadsuccess-999"] = array(			// 999 to avoid overruling by potential customly defined event
				"runat" => "client",
				"userobj" => array(
					"js" => $sEvent,
				),
			);
		}
	}
	
	function majixSelectFiles() {
		return $this->buildMajixExecuter(
			"selectFiles"
		);
	}

	function majixStartUpload() {
		return $this->buildMajixExecuter(
			"startUpload"
		);
	}

	function getMaxUploadSize() {

		// sizes are all converted to KB

		$aSizes = array(
			"iPhpFileMax"	=> 1024 * intval(ini_get("upload_max_filesize")),
			"iPhpPostMax"	=> 1024 * intval(ini_get("post_max_size")),
			"iT3FileMax"	=> intval($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize']),
		);

		if(($mFileSize = $this->_navConf("maxsize")) !== FALSE) {
			// maxSize has to be KB

			if(tx_ameosformidable::isRunneable($mFileSize)) {
				$mFileSize = $this->callRunneable($mFileSize);
			}

			$mFileSize = intval($mFileSize);
			if($mFileSize > 0) {
				$aSizes["userdefined"] = $mFileSize;
			}
		}

		asort($aSizes);
		return array_shift($aSizes);
	}

	function getQueueLimit() {
		if(($mLimit = $this->_navConf("/queuelimit")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($mLimit)) {
				$mLimit = $this->callRunneable($mLimit);
			}

			return intval($mLimit);
		}

		return 0;	// no limit
	}

	function getFileType() {
		
		if(($mFileType = $this->_navConf("/filetype")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($mFileType)) {
				$mFileType = $this->callRunneable($mFileType);
			}
			
			if(is_array($mFileType)) {
				$mFileType = implode(";", $mFileType);
			}
			
			$mFileType = str_replace(
				array(
					",",
					".",
					"*"
				),
				array(
					";",
					"",
					""
				),
				$mFileType
			);
			
			$aFileTypes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(";", strtolower($mFileType));
			
			$aRefinedTypes = array();
			reset($aFileTypes);
			while(list(, $sType) = each($aFileTypes)) {
				$aRefinedTypes[] = "*." . $sType;
			}
			
			return implode(";", $aRefinedTypes);
		}

		return "*.*";
	}

	function getFileTypeDesc() {
		
		$sFileTypeDesc = "LLL:EXT:ameos_formidable/api/base/rdt_swfupload/res/locallang.xml:filetypedesc.allfiles";

		if(($mFileTypeDesc = $this->_navConf("/filetypedesc")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($mFileTypeDesc)) {
				$mFileTypeDesc = $this->callRunneable($mFileTypeDesc);
			}

			$sFileTypeDesc = $mFileTypeDesc;
		}

		return $this->oForm->_getLLLabel($sFileTypeDesc);
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_swfupload/api/class.tx_rdtswfupload.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_swfupload/api/class.tx_rdtswfupload.php"]);
	}
?>
