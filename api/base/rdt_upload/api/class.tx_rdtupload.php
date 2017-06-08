<?php
/**
 * Plugin 'rdt_upload' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

class tx_rdtupload extends formidable_mainrenderlet {

	var $aUploaded = FALSE;	// array if file has just been uploaded
	var $bUseDam = null;	// will be set to TRUE or FALSE, depending on /dam/use=boolean, default FALSE
	var $bUseFal = null;	// will be set to TRUE or FALSE, depending on /fal/use=boolean, default FALSE
	var $bManageFile = FALSE;
	
	var $mTargetFile = AMEOSFORMIDABLE_NOTSET;
	var $mTargetDir = AMEOSFORMIDABLE_NOTSET;

	function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = FALSE) {
		parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);
		$this->aEmptyStatics["targetdir"] = AMEOSFORMIDABLE_VALUE_NOT_SET;
		$this->aEmptyStatics["targetfile"] = AMEOSFORMIDABLE_VALUE_NOT_SET;
		$this->aStatics["targetdir"] = $this->aEmptyStatics["targetdir"];
		$this->aStatics["targetfile"] = $this->aEmptyStatics["targetfile"];
	}

	function cleanStatics() {
		parent::cleanStatics();
		unset($this->aStatics["targetdir"]);
		unset($this->aStatics["targetfile"]);
		$this->aStatics["targetdir"] = $this->aEmptyStatics["targetdir"];
		$this->aStatics["targetfile"] = $this->aEmptyStatics["targetfile"];
	}

	function checkPoint($aPoints) {
		if(in_array("after-init-datahandler", $aPoints)) {
			$this->manageFile();
		}
	}

	function justBeenUploaded() {

		if($this->aUploaded !== FALSE) {
			reset($this->aUploaded);
			return $this->aUploaded;
		}

		return FALSE;
	}

	function _render() {

		if(($this->_navConf("/data/targetdir") === FALSE) && ($this->_navConf("/data/targetfile") === FALSE)) {
			$this->oForm->mayday("renderlet:UPLOAD[name=" . $this->_getName() . "] You have to provide either <b>/data/targetDir</b> or <b>/data/targetFile</b> for renderlet:UPLOAD to work properly.");
		}

		$sValue = $this->getValue();
		if(is_array($sValue)) {
			unset($sValue);
		}
		$sInput = '<input type="file" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '" ' . $this->_getAddInputParams() . ' />';
		$sInput .= '<input type="hidden" name="' . $this->_getElementHtmlName() . '[backup]" value="' . $this->getValueForHtml($sValue) . '" />';

		$aValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $this->getValueForHtml($sValue));

		reset($aValues);
		$sLinks = array();
		while(list($sKey,) = each($aValues)) {
			$aValues[$sKey] = $this->formatValue($aValues[$sKey]);
			$sWebPath = $this->oForm->toWebPath(
				$this->getServerPath($aValues[$sKey])
			);
			$aLinks[] = "<a href=\"" . $sWebPath . "\" target=\"_blank\">" . $aValues[$sKey] . "</a>";
		}

		$sLis = "<li>" . implode("</li><li>", $aValues) . "</li>";
		$sLinkLis = "<li>" . implode("</li><li>", $aLinks) . "</li>";
		$sValueCvs = implode(", ", $aValues);
		$sLinkCvs = implode(", ", $aLinks);

		$sValuePreview = "";

		if((trim($sValue) !== "") && ($this->defaultTrue("showlink") === TRUE)) {
			if(trim($sLinkCvs) !== "") {
				$sValuePreview = $sLinkCvs . "<br />";
			}
		} else {
			if(trim($sValueCvs) !== "") {
				$sValuePreview = $sValueCvs . "<br />";
			}
		}

		$sValue = implode(',', $aValues);
		$aRes = array(
			"__compiled" => $this->_displayLabel($this->getLabel()) . $sValuePreview . $sInput,
			"input" => $sInput,
			"filelist." => array(
				"csv" => $sValueCvs,
				"ol" => "<ol>" . $sLis . "</ol>",
				"ul" => "<ul>" . $sLis . "</ul>",
			),
			"linklist." => array(
				"csv" => $sLinkCvs,
				"ol" => "<ol>" . $sLinkLis . "</ol>",
				"ul" => "<ul>" . $sLinkLis . "</ul>",
			),
			"value" => $sValue,
			"value." => array(
				"preview" => $sValuePreview,
			),
		);

		if(!$this->isMultiple()) {
			if(trim($sValue) != "") {
				$aRes["file."]["webpath"] = $this->oForm->toWebPath($this->getServerPath());
			} else {
				$aRes["file."]["webpath"] = "";
			}
		}

		return $aRes;
	}

	function strtolower($string) {
		if($this->defaultTrue('/data/strtolower')) {
			return strtolower($string);
		}

		return $string;
	}

	function getServerPath($sFileName = FALSE) {
		if(($sTargetFile = $this->getTargetFile()) !== FALSE) {
			
			if($this->defaultFalse("/data/cleanfilename") || $this->defaultFalse("/cleanfilename")) {
				$sTargetDir = \TYPO3\CMS\Core\Utility\GeneralUtility::dirname($sTargetFile);
				$sName = basename($sTargetFile);
				$sName = $this->strtolower(
					$this->cleanFileName($sName)
				);
				$sTargetFile = $sTargetDir . '/' . $sName;
			}
			
			return $sTargetFile;
		} elseif($sFileName !== FALSE) {
			$sFileValue = $this->formatValue($sFileName);
		} else {
			$sFileValue = $this->formatValue($this->getValue());
		}

		if(trim($sFileValue) == '') {
			return FALSE;
		}
		
		return $this->getTargetDir() . $sFileValue;
	}

	function getFullServerPath($sFileName = FALSE) {
		// dummy method for compat with renderlet:FILE and validator:FILE
		return $this->getServerPath($sFileName);
	}

	function cleanFileName($sName) {
		$oFileTool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("t3lib_basicFileFunctions");
		$sName = $oFileTool->cleanFileName($sName);
		$fileName = str_replace(' ', '_', $fileName);
		$sName = str_replace(
			array('à','á','â','ã','ä','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','À','Á','Â','Ã','Ä','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý'),
			array('a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','U','Y'),
			$sName
		);
		
		while(strpos($sName, '__') !== FALSE) {
			$sName = str_replace('__', '_', $sName);
		}
		
		$sName = trim($sName, '_');
		return $sName;
	}
	
	function manageFile() {
		$this->bManageFile = TRUE;
		/*
			ALGORITHM of file management

			0: PAGE DISPLAY:
				1: file has been uploaded
					1.1: moving file to targetdir and setValue(newfilename)
						1.1.1: multiple == TRUE
							1.1.1.1: data *are not* stored in database (creation mode)
								1.1.1.1.1: setValue to backupdata . newfilename
							1.1.1.2: data *are* stored in database
								1.1.1.2.1: setValue to storeddata . newfilename
				2: file has not been uploaded
					2.1: data *are not* stored in database, as it's a creation mode not fully completed yet
						2.1.1: formdata is a string
							2.1.1.1: formdata != backupdata ( value has been set by some server event with setValue )
								2.1.1.1.1: no need to setValue as it already contains what we need
							2.2.1.2: formdata == backupdata
								2.2.1.2.1: setValue to backupdata
						2.1.2: formdata is an array, and error!=0 (form submitted but no file given)
							2.1.2.1: setValue to backupdata
					2.2: data *are* stored in database
							2.2.1: formdata is a string
								2.2.1.1: formdata != storeddata ( value has been set by some server event with setValue )
									2.2.1.1.1: no need to setValue as it already contains what we need
								2.2.1.2: formdata == storeddata
									2.2.1.2.1: setValue to storeddata
							2.2.2: formdata is an array, and error!=0 ( form submitted but no file given)
								2.2.2.1: setValue to storeddata
		*/

		$aData = $this->getValue();
		if(is_array($aData) && $aData["error"] == 0) {
			if($this->useFal()) {
				if(($storageIdentifier = $this->_navConf("/fal/storage")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($storageIdentifier)) {
						$storageIdentifier = $this->callRunneable($storageIdentifier);
					}
				}

				$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
				$storage = $storageRepository->findByUid($storageIdentifier);
				$storageConfiguration = $storage->getConfiguration();

				$folderPath = str_replace(PATH_site . $storageConfiguration['basePath'], '/', $this->getTargetDir());
				$folder = $storage->getFolder($folderPath);

				$finalName = basename($aData["name"]);
				if($this->defaultTrue("/data/cleanfilename") && $this->defaultTrue("/cleanfilename")) {
					$finalName = $this->strtolower(
						$this->cleanFileName($finalName)
					);
				}

				$newFile = $folder->addFile($aData["tmp_name"], $finalName, 'changeName');
				$this->setValue($newFile->getName());

			} else {			
				// a file has just been uploaded
				$oFileTool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("t3lib_basicFileFunctions");

				if(($sTargetFile = $this->getTargetFile()) !== FALSE) {
					$sTargetDir = \TYPO3\CMS\Core\Utility\GeneralUtility::dirname($sTargetFile);
					$sName = basename($sTargetFile);
					if($this->defaultFalse("/data/cleanfilename") || $this->defaultFalse("/cleanfilename")) {
						$sName = $this->strtolower(
							$this->cleanFileName($sName)
						);
					}
					$sTarget = $sTargetDir . '/' . $sName;
				} else {
					$sTargetDir = $this->getTargetDir();
					#debug($sTargetDir, "le bon targetdir");

					$sName = basename($aData["name"]);
					if($this->defaultTrue("/data/cleanfilename") && $this->defaultTrue("/cleanfilename")) {
						$sName = $this->strtolower(
							$this->cleanFileName($sName)
						);
					}

					$sTarget = $sTargetDir . $sName;
					if(!file_exists($sTargetDir)) {
						if($this->defaultFalse("/data/targetdir/createifneeded") === TRUE) {
							// the target does not exist, we have to create it
							$this->oForm->div_mkdir_deep_abs($sTargetDir);
						}
					}

					if(!$this->oForm->defaultFalse("/data/overwrite", $this->aElement)) {
						// rename the file if same name already exists

						$sExt = ((strpos($sName,'.') === FALSE) ? '' : '.' . substr(strrchr($sName, "."), 1));

						for($i=1; file_exists($sTarget); $i++) {
							$sTarget = $sTargetDir . substr($sName, 0, strlen($sName)-strlen($sExt)).'['.$i.']'.$sExt;
						}

						$sName = basename($sTarget);
					}
				}

				#debug($sTarget, "target");
				#debug(realpath("."));
				if(move_uploaded_file($aData["tmp_name"], $sTarget)) {

					// success
					$this->aUploaded = array(
						"dir" => $sTargetDir,
						"name" => $sName,
						"path" => $sTarget,
						"infos" => $aData,
					);
					#debug($this->aUploaded, "this->aUploaded");

					$sCurFile = $sName;
					if($this->useDam()) {
						$iUid = $this->damify(
							$this->getServerPath($sName)
						);

						$sCurFile = $iUid;
					}



					//debug($sCurFile, "curfile");

					if($this->isMultiple()) {
						// csv string of file names

						if($this->oForm->oDataHandler->_edition() === FALSE || $this->_renderOnly()) {
							//$aPost = $this->oForm->oDataHandler->_P();
							$sCurrent = $aData["backup"];
						} else {
							$sCurrent = trim($this->oForm->oDataHandler->_getStoredData($this->_getName()));
						}

						if($sCurrent !== "") {

							$aCurrent = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $sCurrent);
							if(!in_array($sCurFile, $aCurrent)) {
								$aCurrent[] = $sCurFile;
							}

							// adding filename to list
							$this->setValue(implode(",", $aCurrent));
						} else {

							// first value in multiple list
							$this->setValue($sCurFile);
						}
					} else {

						// replacing value in list
						$this->setValue($sCurFile);
					}
				}

				$this->handleDam();
			}

		} else {

			$aStoredData = $this->oForm->oDataHandler->_getStoredData();

			if(($this->oForm->oDataHandler->_edition() === FALSE) || (!array_key_exists($this->_getName(), $aStoredData))) {
				//$aPost = $this->oForm->oDataHandler->_P();


				if(is_string($aData)) {
					if($this->bForcedValue === TRUE) {
						// value has been set by some process (probably a server event) with setValue()
						/* nothing to do, so */
					} else {
						// persisting existing value
						$this->setValue(
							$aData
						);
					}
				} else {
					// it's an array, and error!=0
						// persisting existing value
					$sBackup = "";
					if(is_array($aData) && array_key_exists("backup", $aData)) {
						$sBackup = $aData["backup"];
					}

					$this->setValue($sBackup);
				}
			} else {

				$sStoredData = $aStoredData[$this->_getName()];

				if(is_string($aData)) {
					// $aData is a string

					if(trim($aData) !== $sStoredData) {
						// value has been set by some process (probably a server event) with setValue()
						/* nothing to do, so */
					} else {
						// persisting existing value
						$this->setValue($sStoredData);
					}
				} else {
					// it's an array, and error!=0
						// persisting existing value
					$this->setValue($sStoredData);
				}
			}
		}
		$this->bManageFile = FALSE;
	}

	function getTargetDir() {
		if($this->mTargetDir === AMEOSFORMIDABLE_NOTSET) {
			if($this->aStatics["targetdir"] === AMEOSFORMIDABLE_VALUE_NOT_SET) {
				$this->aStatics["targetdir"] = FALSE;
				if(($mTargetDir = $this->_navConf("/data/targetdir")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($mTargetDir)) {
						$mTargetDir = $this->callRunneable($mTargetDir);
					}
	
					if(is_string($mTargetDir) && trim($mTargetDir) !== "") {
	
						if($this->oForm->isAbsPath($mTargetDir)) {
							$this->aStatics["targetdir"] = $this->oForm->_removeEndingSlash($mTargetDir) . "/";
						} else {
							$this->aStatics["targetdir"] = $this->oForm->_removeEndingSlash(
								$this->oForm->toServerPath($mTargetDir)
							) . "/";
						}
					}
				}
			}
			#debug($this->aStatics);
			$this->mTargetDir = $this->aStatics["targetdir"];
		}
		
		return $this->mTargetDir;
	}

	function getTargetFile() {
		if($this->mTargetFile === AMEOSFORMIDABLE_NOTSET) {
			if(($mTargetFile = $this->_navConf("/data/targetfile")) !== FALSE) {
				if(tx_ameosformidable::isRunneable($mTargetFile)) {
					$mTargetFile = $this->callRunneable($mTargetFile);
				}
	
				if(is_string($mTargetFile) && trim($mTargetFile) !== "") {
					$this->mTargetFile = $this->oForm->toServerPath(trim($mTargetFile));
				}
			} else {
				$this->mTargetFile = FALSE;
			}
		}
		
		return $this->mTargetFile;
	}

	function isMultiple() {
		return $this->oForm->defaultFalse("/data/multiple", $this->aElement);
	}

	function deleteFile($sFile) {
		$aValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $this->getValue());
		unset($aValues[array_search($sFile, $aValues)]);
		@unlink($this->getFullServerPath($sFile));
		$this->setValue(implode(",", $aValues));
	}

	function useDam() {

		if(is_null($this->bUseDam)) {
			if($this->oForm->defaultFalse("/dam/use", $this->aElement) === TRUE) {
				if(!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded("dam")) {
					$this->oForm->mayday("renderlet:UPLOAD[name=" . $this->_getName() . "], can't connect to <b>DAM</b>: <b>EXT:dam is not loaded</b>.");
				}

				$this->bUseDam = TRUE;
				require_once(PATH_txdam . "lib/class.tx_dam.php");
			} else {
				$this->bUseDam = FALSE;
			}
		}

		return $this->bUseDam;
	}

	function useFal() {

		if(is_null($this->bUseFal)) {
			if($this->oForm->defaultFalse("/fal/use", $this->aElement) === TRUE) {
				$this->bUseFal = TRUE;				
			} else {
				$this->bUseFal = FALSE;
			}
		}

		return $this->bUseFal;
	}

	function handleDam() {
		if($this->useDam()) {

			$bSimulatedUser = FALSE;
			global $BE_USER;

			if(!isset($BE_USER) || !is_object($BE_USER) || intval($GLOBALS["BE_USER"]->user["uid"]) === 0) {
				// no be_user available
					// we are using the one created for formidable+dam, named _formidable+dam

				$rSql = $GLOBALS["TYPO3_DB"]->exec_SELECTquery(
					"uid",
					"be_users",
					"LOWER(username)='_formidable+dam'"	// no enableFields, as this user may should disabled for security reasons
				);

				if(($aRs = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
					// we found user _formidable+dam
						// simulating user

					unset($BE_USER);
					$BE_USER = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_beUserAuth');
					$BE_USER->OS = TYPO3_OS;
					$BE_USER->setBeUserByUid($aRs["uid"]);
					$BE_USER->fetchGroupData();
					$BE_USER->backendSetUC();

					$GLOBALS['BE_USER'] = $BE_USER;
					$bSimulatedUser = TRUE;
				} else {
					$this->oForm->mayday("renderlet:UPLOAD[name=" . $this->_getName() . "] /dam/use is enabled; for DAM to operate properly, you have to create a backend-user named '_formidable+dam' with permissions on dam tables");
				}
			}

			if($this->isMultiple()) {
				$aFiles = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", $this->getValue());
			} else {
				$aFiles = array($this->getValue());
			}

			reset($aFiles);
			while(list(, $sFileName) = each($aFiles)) {
				$sFilePath = $this->getServerPath($sFileName);

				tx_dam::notify_fileChanged($sFilePath);
				$oMedia = tx_dam::media_getForFile($sFilePath);

				if(($mTitle = $this->_navConf("/dam/addtitle")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($mTitle)) {
						$mTitle = $this->callRunneable(
							$mTitle,
							array(
								"filename" => $sFileName,
								"filepath" => $sFilePath,
								"media" => $oMedia,
								"currentcats" => $aCurCats,
								"files" => $aFiles,
							)
						);
					}
					$oMedia->meta["title"] = $mTitle;
					if(trim($mTitle) !== '') {
						$oMedia->meta["title"] = $mTitle;
					}
				}

				if(($mDescription = $this->_navConf("/dam/adddescription")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($mDescription)) {
						$mDescription = $this->callRunneable(
							$mDescription,
							array(
								"filename" => $sFileName,
								"filepath" => $sFilePath,
								"media" => $oMedia,
								"currentcats" => $aCurCats,
								"files" => $aFiles,
							)
						);
					}
					$oMedia->meta["description"] = $mDescription;
					if(trim($mDescription) !== '') {
						$oMedia->meta["description"] = $mDescription;
					}
				}

				if(($mCaption = $this->_navConf("/dam/addcaption")) !== FALSE) {
					if(tx_ameosformidable::isRunneable($mCaption)) {
						$mCaption = $this->callRunneable(
							$mCaption,
							array(
								"filename" => $sFileName,
								"filepath" => $sFilePath,
								"media" => $oMedia,
								"currentcats" => $aCurCats,
								"files" => $aFiles,
							)
						);
					}
					if(trim($mCaption) !== '') {
						$oMedia->meta["caption"] = $mCaption;
					}
				}

				if(($mCategories = $this->_navConf("/dam/addcategories")) !== FALSE) {

					require_once(PATH_txdam . "lib/class.tx_dam_db.php");

					$aCurCats = $GLOBALS["TYPO3_DB"]->exec_SELECTgetRows(
						"uid_foreign",
						"tx_dam_mm_cat",
						"uid_local='" . $oMedia->meta["uid"] . "'",
						"",
						"sorting ASC"
					);

					if(!is_array($aCurCats)) {
						$aCurCats = array();
					}

					reset($aCurCats);
					while(list($sKey,) = each($aCurCats)) {
						$aCurCats[$sKey] = $aCurCats[$sKey]["uid_foreign"];
					}

					if(tx_ameosformidable::isRunneable($mCategories)) {
						$mCategories = $this->callRunneable(
							$mCategories,
							array(
								"filename" => $sFileName,
								"filepath" => $sFilePath,
								"media" => $oMedia,
								"currentcats" => $aCurCats,
								"files" => $aFiles,
							)
						);
					}

					$aCategories = array();

					if(!is_array($mCategories)) {
						if(trim($mCategories) !== "") {
							$aCategories = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", trim($mCategories));
						}
					} else {
						$aCategories = $mCategories;
					}

					if(count($aCategories) > 0) {
						reset($aCurCats);
						$aCategories = array_unique(
							array_merge($aCurCats, $aCategories)
						);
						
						if(!empty($aCategories)) {
							$oMedia->meta["category"] = implode(",", $aCategories);
						}
					}
				}

				tx_dam_db::insertUpdateData($oMedia->meta);
			}
			if($bSimulatedUser === TRUE) {
				unset($BE_USER);
				unset($GLOBALS["BE_USER"]);
			}
		}
	}

	function damify($sAbsPath) {
		if($this->useDam()) {

			global $PAGES_TYPES;
			if(!isset($PAGES_TYPES)) {
				require_once(PATH_t3lib.'stddb/tables.php');
			}

			$bSimulatedUser = FALSE;
			global $BE_USER;

			// Simulate a be user to allow DAM to write in DB
				// see http://lists.typo3.org/pipermail/typo3-project-dam/2009-October/002751.html
				// and http://lists.netfielders.de/pipermail/typo3-project-dam/2006-August/000481.html

			if(!isset($BE_USER) || !is_object($BE_USER) || intval($GLOBALS["BE_USER"]->user["uid"]) === 0) {
				// no be_user available
					// we are using the one created for formidable+dam, named _formidable+dam

				$rSql = $GLOBALS["TYPO3_DB"]->exec_SELECTquery(
					"uid",
					"be_users",
					"LOWER(username)='_formidable+dam'"	// no enableFields, as this user may should disabled for security reasons
				);

				if(($aRs = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
					// we found user _formidable+dam
						// simulating user

					unset($BE_USER);
					$BE_USER = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_beUserAuth');
					$BE_USER->OS = TYPO3_OS;
					$BE_USER->setBeUserByUid($aRs["uid"]);
					$BE_USER->fetchGroupData();
					$BE_USER->backendSetUC();

					$GLOBALS['BE_USER'] = $BE_USER;
					$bSimulatedUser = TRUE;
				} else {
					$this->oForm->mayday("renderlet:UPLOAD[name=" . $this->_getName() . "] /dam/use is enabled; for DAM to operate properly, you have to create a backend-user named '_formidable+dam' with permissions on dam tables");
				}
			}

			$mBefore = intval($GLOBALS["T3_VAR"]["ext"]["dam"]["config"]["mergedTSconfig."]["setup."]["indexing."]["auto"]);
			$GLOBALS["T3_VAR"]["ext"]["dam"]["config"]["mergedTSconfig."]["setup."]["indexing."]["auto"] = 1;

			tx_dam::notify_fileChanged($sAbsPath);

				// previous line don't work anymore for some obscure reason.
					// Error seems to be in tx_dam::index_autoProcess()
					// at line 1332 while checking config value of setup.indexing.auto
					// EDIT: works now, http://lists.typo3.org/pipermail/typo3-project-dam/2009-October/002749.html
					// EDIT2: temporarily forcing setup.indexing.auto to be active seems to be the best solution when writing this

			$GLOBALS["T3_VAR"]["ext"]["dam"]["config"]["mergedTSconfig."]["setup."]["indexing."]["auto"] = $mBefore;


			if($bSimulatedUser === TRUE) {
				unset($BE_USER);
				unset($GLOBALS["BE_USER"]);
			}

			$oMedia = tx_dam::media_getForFile($sAbsPath);
			return $oMedia->meta["uid"];
		}

		return basename($sAbsPath);
	}


/*
	function getValue() {
		return parent::getValue();
		if(!$this->useDam() || $this->bManageFile) {
			return parent::getValue();
		}

		$aMValues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', parent::getValue());
		$aValues = array();
		foreach($aMValues as $mValue) {
			$oMedia = tx_dam::media_getByUid($mValue);
			if(empty($oMedia->meta)) {
				$aValues[] = $mValue;
			} else {
				$aValues[] = $oMedia->meta['file_name'];
			}
		}

		return implode(',', $aValues);
	}
*/

	public function formatValue($mValue) {
		if(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded("dam")) {
			if(is_numeric($mValue)) {
				$oMedia = tx_dam::media_getByUid($mValue);
				return $oMedia->meta['file_name'];
			}
		}

		return $mValue;
	}

}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_upload/api/class.tx_rdtupload.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_upload/api/class.tx_rdtupload.php"]);
	}
?>
