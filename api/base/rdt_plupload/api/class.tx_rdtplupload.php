<?php
/** 
 * Plugin 'rdt_rdtplupload' for the 'ameos_formidable' extension.
 *
 * TODO: overwrite custom events, filters 
 *
 *
 * @author	Ameos <typo3dev@ameos.com>
 */


class tx_rdtplupload extends formidable_mainrenderlet {

	var $sMajixClass = "PlUpload";
	var $aLibs = array(
		"rdt_plupload_lib" => "res/js/plupload.full.js",
		"rdt_plupload_class" => "res/js/rdt_plupload.js"
	);
	
	var $bCustomIncludeScript = TRUE;
	var $aPossibleCustomEvents = array(
		"onbeforeupload",		# BeforeUpload(uploader:Uploader, file:File); Fires when just before a file is uploaded
		"onchunkuploaded",	# ChunkUploaded(uploader:Uploader, file:File, response:Object); Fires when file chunk is uploaded
		"ondestroy",		# Destroy(uploader:Uploader); Fires when destroy method is called
		"onerror",			# Error(uploader:Uploader, error:Object); Fires when a error occurs
		"onfilesadded",		# FilesAdded(uploader:Uploader, files:Array); Fires while when the user selects files to upload
		"onfilesremoved",		# FilesRemoved(uploader:Uploader, files:Array); Fires while a file was removed from queue
		"onfileuploaded",		# FileUploaded(uploader:Uploader, file:File, response:Object); Fires when a file is successfully uploaded
		"oninit",			# Init(uploader:Uploader); Fires when the current RunTime has been initialized
		"onpostinit",		# PostInit(uploader:Uploader); Fires after the init event incase you need to perform actions there
		"onqueuechanged",		# QueueChanged(uploader:Uploader); Fires when the file queue is changed
		"onrefresh",		# Refresh(uploader:Uploader); Fires when the silverlight/flash or other shim needs to move.
		"onstatechanged",		# StateChanged(uploader:Uploader); Fires when the overall state is being changed for the upload queue
		"onuploadcomplete",	# UploadComplete(uploader:Uploader, files:Array); Fires when all files in a queue are uploaded
		"onuploadfile",		# UploadFile(uploader:Uploader, file:File); Fires when a file is to be uploaded by the runtime
		"onuploadprogress",	# UploadProgress(uploader:Uploader, file:File); Fires while a file is being uploaded
	);
	
	var $sTargetDir = AMEOSFORMIDABLE_VALUE_NOT_SET;
	var $bUseDam = null;	// will be set to TRUE or FALSE, depending on /dam/use=boolean, default FALSE
	
	function _render() {
		
		if(($this->_navConf("/data/targetdir") === FALSE)) {
			$this->oForm->mayday("renderlet:PLUPLOAD[name=" . $this->_getName() . "] You have to provide <b>/data/targetdir</b> for renderlet:PLUPLOAD to work properly.");
		}
	
		if(empty($GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxevent"][$this->_getSessionDataHashKey()])) {
			$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxevent"][$this->_getSessionDataHashKey()] = array(
				"requester" => array(
					"name" => "tx_ameosformidable",
					"xpath" => "/",
				),
			);
		}
	
		$sPrefixId = $this->_getElementHtmlId();
		
		$sQueueId  = $sPrefixId . '-queue';
		$sBrowseId = $sPrefixId . '-browse';
		$sUploadId = $sPrefixId . '-upload';
		$sValuesId = $sPrefixId . '-values';
		$sValuesName = $this->_getElementHtmlName();
		
		$sHiddenValue = trim(str_replace('\\', '', $this->getJsonValue()), '"');
		
		$sInputHidden = '<input type="hidden" name="' . $sValuesName . '" id="' . $sValuesId . '" value=\'' . $sHiddenValue . '\'>';
		$sBrowseLink = '<a id="' . $sBrowseId . '" href="javascript:void(0);" title="'.$this->oForm->getLLLabel("LLL:EXT:ameos_formidable/api/base/rdt_plupload/res/locallang.xml:browselabel").'">' . $this->getBrowseLabel() . '</a>';
		$sInput = $sInputHidden . $sBrowseLink;
		
		if($this->hasDefaultBehaviour()) {
			$sInput .= '&nbsp;<a id="' . $sUploadId . '" href="#" title="'.$this->oForm->getLLLabel("LLL:EXT:ameos_formidable/api/base/rdt_plupload/res/locallang.xml:uploadlabel").'">' . $this->getUploadLabel() . '</a><div id="' . $sQueueId . '"></div>';
		}

		$sAddParams = $this->_getAddInputParams();
		
		/* BEGIN: forging access to upload service */

		$sHtmlId = $this->_getElementHtmlId();
		$sObject = "rdt_plupload";
		$sServiceKey = "upload";
		$sFormId = $this->oForm->formid;
		$sSafeLock = $this->_getSessionDataHashKey();
		$sThrower = $sHtmlId;

		$sUploadUrl = $this->oForm->_removeEndingSlash(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("TYPO3_SITE_URL")) . "/index.php?eID=tx_ameosformidable&object=" . $sObject . "&servicekey=" . $sServiceKey . "&formid=" . $sFormId . "&sessionhash=" . $sSafeLock . "&safelock=" . $sSafeLock . "&thrower=" . $sThrower;
		$sUploadUrl .= "&phpsessid=" . session_id();

		$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"][$sObject][$sServiceKey][$sSafeLock] = array(
			"requester" => array(
				"name" => $this->getAbsName(),
				"xpath" => $this->sXPath,
			),
		);
		
		/* END: forging access to upload service */

		$aIncludeScripts = array(
			"container" 	=> $this->_getElementHtmlId(),
			"browse_button" => $sBrowseId,
			"upload_button" => $sUploadId,
			"files_queue"	=> $sQueueId, 
			"hidden_field"	=> $sValuesId,
			"runtimes"		=> $this->getRuntimes(),
			"max_file_size"	=> $this->getMaxUploadSize() . 'Mb',
			"filetype"		=> $this->getFiletype(),
			"upload_script"	=> $sUploadUrl,
			"flash_swf_url" => $this->sExtWebPath . "res/js/plupload.flash.swf",
			"silverlight_xap_url" => $this->sExtWebPath . "res/js/plupload.silverlight.xap",
			"defaultbehaviour" => $this->hasDefaultBehaviour(),
			"multi_selection" => $this->maySelectMultipleFiles(),
		);
		
		$this->includeScripts($aIncludeScripts);
		
		return array(
			"__compiled" => $this->_wrapIntoContainer(
				$this->_displayLabel($this->getLabel()) . $sInput, 
				$sAddParams
			),
			"input" => $sInput,
			'hidden' => $sInputHidden,
			"label" => $this->getLabel(),
			"ids." => array(
				"container" => $this->_getElementHtmlId(),
				"browse"	=> $sBrowseId,
				"upload"	=> $sUploadId,
				"queue"	=> $sQueueId
			)
		);
	}
	
	function hasDefaultBehaviour() {
		return $this->defaultTrue("/defaultbehaviour");
	}
	
	function maySelectMultipleFiles() {
		return $this->defaultTrue("/multipleselection");
	}
	
	function updateValue($aParams) {
		$this->setValue($aParams['sys_event']['value']);
	}
	
	function includeScripts($aConf = array()) {
		parent::includeScripts($aConf);
		
		$sAbsName = $this->_getElementHtmlId();
		$sInitScript =<<<INITSCRIPT

		try {
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").plUploadObjectInit();
		} catch(e) {
			// alert("PLUpload exception !!!" + e.name + ":" + e.message);
			// throw(e);
		}

INITSCRIPT;

		$this->sys_attachPostInitTask(
			$sInitScript,
			"Post-init PLUPLOAD",
			$this->_getElementHtmlId()
		);
	}
	
	function _wrapIntoContainer($sHtml, $sAddParams = "") {
		return "<div id=\"" . $this->_getElementHtmlId() . "\"" . $sAddParams . ">" . $sHtml . "</div>";
	}
	
	function getBrowseLabel() {
		if(($sLabel = $this->_navConf("/browselabel")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($sLabel)) {
				$sLabel = $this->callRunneable($sLabel);
			}
			
			return $sLabel;
		}
		
		return $this->oForm->getLLLabel("LLL:EXT:ameos_formidable/api/base/rdt_plupload/res/locallang.xml:browselabel");
	}
	
	function getUploadLabel() {
		if(($sLabel = $this->_navConf("/uploadlabel")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($sLabel)) {
				$sLabel = $this->callRunneable($sLabel);
			}
			
			return $sLabel;
		}
		
		return $this->oForm->getLLLabel("LLL:EXT:ameos_formidable/api/base/rdt_plupload/res/locallang.xml:uploadlabel");
	}
	
	function getTargetDir() {
		if($this->sTargetDir === AMEOSFORMIDABLE_VALUE_NOT_SET) {
			$this->sTargetDir = FALSE;
			if(($mTargetDir = $this->_navConf("/data/targetdir")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mTargetDir)) {
					$mTargetDir = $this->callRunneable($mTargetDir);
				}

				if(is_string($mTargetDir) && trim($mTargetDir) !== "") {

					if($this->oForm->isAbsPath($mTargetDir)) {
						$this->sTargetDir = $this->oForm->_removeEndingSlash($mTargetDir) . "/";
					} else {
						$this->sTargetDir = $this->oForm->_removeEndingSlash(
							$this->oForm->toServerPath($mTargetDir)
						) . "/";
					}
				}
			}
		}

		return $this->sTargetDir;
	}

	function getMaxUploadSize() {
		$aSizes = array(
			"iPhpFileMax"	=> intval(ini_get("upload_max_filesize")),
			"iPhpPostMax"	=> intval(ini_get("post_max_size")),
			//"iT3FileMax"	=> intval($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize']),
		);

		if(($mFileSize = $this->_navConf("maxsize")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($mFileSize)) {
				$mFileSize = $this->callRunneable($mFileSize);
			}

			$mFileSize = intval($mFileSize);
			if($mFileSize > 0) {
				$aSizes["userdefined"] = $mFileSize;
			}
		}

		asort($aSizes);
		return array_shift($aSizes)*1024*1024;
	}
	
	function getRuntimes() {
		if(($sRuntimes = $this->_navConf("/runtimes")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($sRuntimes)) {
				$sRuntimes = $this->callRunneable($sRuntimes);
			}
			
			return $sRuntimes;
		}
		
		return 'html5,flash,silverlight,html4';
		#return 'flash';
	}
	
	function getFileType() {
		if(($sFilters = $this->_navConf("/filetype")) !== FALSE) {
			return $sFilters;
		}
		
		return FALSE;
	}
	
	function handleAjaxRequest_exit($aInfo) {
		$aInfo["jsonrpc"] = "2.0";
		$aInfo["id"] = "id";
		
		reset($aInfo);
		return $this->oForm->array2json($aInfo);
	}
	
	function handleAjaxRequest(&$oRequest) {
		
		$bRenamed = FALSE;
		
		$oFile = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility");
		
		// 5 minutes execution time
		@set_time_limit(5 * 60);
		
		// Get Target dir
		$targetDir = $this->getTargetDir();

		// Get parameters
		$chunk = isset($GLOBALS["_REQUEST"]["chunk"]) ? $GLOBALS["_REQUEST"]["chunk"] : 0;
		$chunks = isset($GLOBALS["_REQUEST"]["chunks"]) ? $GLOBALS["_REQUEST"]["chunks"] : 0;
		$fileName = isset($GLOBALS["_REQUEST"]["name"]) ? $GLOBALS["_REQUEST"]["name"] : '';
		$originalFileName = $fileName;
		
		// Check filename against T3 deny pattern		
		if($this->defaultTrue("/usedenypattern") !== FALSE) {
			if(!\TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern($fileName)) {
				return $this->handleAjaxRequest_exit(array(
					"error" => array(
						"code" => 104,
						"message" => "File type is denied.",
					)
				));
			}
		}
		
		// Clean the fileName for security reasons
		if($this->defaultFalse("/cleanfilename") !== FALSE) {
			$fileName = strtolower(
				$oFile->cleanFileName($fileName)
			);

			$fileName = str_replace(' ', '_', $fileName);
			$fileName = str_replace(
				array('à','á','â','ã','ä','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','À','Á','Â','Ã','Ä','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý'),
				array('a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','U','Y'),
				$fileName
			);

			while(strpos($fileName, '__') !== FALSE) {
				$fileName = str_replace('__', '_', $fileName);
			}

			$fileName = trim($fileName, '_');
			
			if($originalFileName !== $fileName) {
				$bRenamed = TRUE;
			}
		}
		
		// Make sure the fileName is unique but only if chunking is disabled
		if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);

			$count = 1;
			while(file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b)) {
				$count++;
			}

			$fileName = $fileName_a . '_' . $count . $fileName_b;
			$bRenamed = TRUE;
		}
		
		
		// Look for the content type header
		if(isset($GLOBALS["_SERVER"]["HTTP_CONTENT_TYPE"])) {
			$contentType = $GLOBALS["_SERVER"]["HTTP_CONTENT_TYPE"];
		}

		if(isset($GLOBALS["_SERVER"]["CONTENT_TYPE"])) {
			$contentType = $GLOBALS["_SERVER"]["CONTENT_TYPE"];
		}

		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($contentType, "multipart") !== false) {
			if (isset($GLOBALS["_FILES"]['file']['tmp_name']) && is_uploaded_file($GLOBALS["_FILES"]['file']['tmp_name'])) {
				// Open temp file
				$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($GLOBALS["_FILES"]['file']['tmp_name'], "rb");

					if ($in) {
						while($buff = fread($in, 4096)) {
							fwrite($out, $buff);
						}
					} else {
						return $this->handleAjaxRequest_exit(array(
							"error" => array(
								"code" => 101,
								"message" => "Failed to open input stream.",
							)
						));
					}
					
					fclose($in);
					fclose($out);
					@unlink($GLOBALS["_FILES"]['file']['tmp_name']);
					
				} else {
					return $this->handleAjaxRequest_exit(array(
						"error" => array(
							"code" => 102,
							"message" => "Failed to open output stream.",
						)
					));
				}
			} else {
				return $this->handleAjaxRequest_exit(array(
					"error" => array(
						"code" => 103,
						"message" => "Failed to move uploaded file.",
					)
				));
			}
		} else {
			// Open temp file
			$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in) {
					while ($buff = fread($in, 4096)) {
						fwrite($out, $buff);
					}
				} else {
					return $this->handleAjaxRequest_exit(array(
						"error" => array(
							"code" => 101,
							"message" => "Failed to open input stream.",
						)
					));
				}

				fclose($in);
				fclose($out);
			} else {
				return $this->handleAjaxRequest_exit(array(
					"error" => array(
						"code" => 102,
						"message" => "Failed to open output stream.",
					)
				));
			}
		}
		
		if($bRenamed === TRUE) {
			// Return JSON-RPC response
			$aResults = array(
				"result" => array(
					"renamed" => TRUE,
					"originalfilename" => $originalFileName,
					"filename" => $fileName,
				),
				"request" => $_REQUEST
			);
		} else {
			$aResults = array(
				"result" => array(
					"renamed" => FALSE,
					"filename" => $fileName,
				),
				"request" => $_REQUEST
			);
		}
		
		$sResult = $this->handleAjaxRequest_exit($aResults);
		
		# exiting for we don't want Formidable to wrap it with it's own RPC layer
		die($sResult);
	}
	
	function majixStartUpload() {
		return $this->buildMajixExecuter(
			"startUpload"
		);
	}
	
	function setValue($mValue) {
		if(!is_array($mValue)) {
			if(empty($mValue)) {
				$mValue = array();
			} else {
				$mValue = array($mValue);
			}
		}
	
		if(array_key_exists('value', $mValue)) {
			$mValue = $mValue['value'];
		}
		
		parent::setValue($mValue);
	}
	
	function getJsonValue() {
		$aValue = $this->getValue();
		if(empty($aValue)) {
			$aValue = array('value' => '');
		}
		return json_encode($aValue, TRUE);
		return $this->oForm->array2json($aValue);
	}

	
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_plupload/api/class.tx_rdtplupload.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_plupload/api/class.tx_rdtplupload.php"]);
}
