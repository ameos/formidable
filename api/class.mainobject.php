<?php

	class formidable_mainobject {

		var $oForm			= null;
		var $aElement		= null;
		var $sExtPath		= null;
		var $sExtRelPath	= null;
		var $sExtWebPath	= null;
		var $aObjectType	= null;
		
		var $sXPath			= null;

		var $sNamePrefix = FALSE;
		var $aStatics = array(
			"navconf" => array(),
		);

		function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = FALSE) {
			
			$this->oForm =& $oForm;
			$this->aElement = $aElement;
			$this->aObjectType = $aObjectType;

			$this->sExtPath = $aObjectType["PATH"];
			$this->sExtRelPath = $aObjectType["RELPATH"];
			$this->sExtWebPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv("TYPO3_SITE_URL") . $this->sExtRelPath;

			$this->sXPath = $sXPath;

			$this->sNamePrefix = $sNamePrefix;

			if(is_array($this->oForm->conf[$aObjectType["OBJECT"] . "."]) && array_key_exists($aObjectType["EXTKEY"] . ".", $this->oForm->conf[$aObjectType["OBJECT"] . "."])) {
				$this->conf = $this->oForm->conf[$aObjectType["OBJECT"] . "."][$aObjectType["EXTKEY"] . "."];
			} else {
				$this->conf = array();
			}
		}

		function _getType() {
			return $this->aElement["type"];
		}

		function _navConf($sPath, $aConf = FALSE) {
			
			if($aConf !== FALSE) {
				return $this->oForm->_navConf($sPath, $aConf);
			}
			
			// caching results, if conf is this rdt conf
			if(!array_key_exists($sPath, $this->aStatics)) {
				$this->aStatics[$sPath] = $this->oForm->_navConf($sPath, $this->aElement);
			}
			
			return $this->aStatics[$sPath];
		}

		/**
		 * [Describe function...]
		 *
		 * @param	[type]		$sPath: ...
		 * @param	[type]		$aConf: ...
		 * @return	[type]		...
		 */
		function _isTrue($sPath) {
			return $this->isTrue($sPath);
		}

		function isTrue($sPath) {
			return $this->isTrueVal(
				$this->_navConf(
					$sPath
				)
			);
		}

		/**
		 * [Describe function...]
		 *
		 * @param	[type]		$sPath: ...
		 * @param	[type]		$aConf: ...
		 * @return	[type]		...
		 */
		function _isFalse($sPath) {
			return $this->isFalse($sPath);
		}

		function isFalse($sPath) {
			$mValue = $this->_navConf($sPath);

			if($mValue !== FALSE) {
				return $this->isFalseVal($mValue);
			} else {
				return FALSE;	// if not found in conf, the searched value is not FALSE, so _isFalse() returns FALSE !!!!
			}
		}

		/**
		 * [Describe function...]
		 *
		 * @param	[type]		$mVal: ...
		 * @return	[type]		...
		 */
		function _isTrueVal($mVal) {
			return $this->isTrueVal($mVal);
		}

		function isTrueVal($mVal) {
			if(tx_ameosformidable::isRunneable($mVal)) {
				$mVal = $this->callRunneable($mVal);
			}

			return (($mVal === TRUE) || ($mVal == '1') || (strtoupper($mVal) == 'TRUE'));
		}

		/**
		 * [Describe function...]
		 *
		 * @param	[type]		$mVal: ...
		 * @return	[type]		...
		 */
		function _isFalseVal($mVal) {
			return $this->isFalseVal($mVal);
		}

		function isFalseVal($mVal) {
			if(tx_ameosformidable::isRunneable($mVal)) {
				$mVal = $this->callRunneable($mVal);
			}

			return (($mVal == FALSE) || (strtoupper($mVal) == "FALSE"));
		}

		function _defaultTrue($sPath) {
			return $this->defaultTrue($sPath);
		}

		function _defaultFalse($sPath) {
			return $this->defaultFalse($sPath);
		}

		// alias for _defaultTrue()
		function defaultTrue($sPath) {
			
			if($this->_navConf($sPath) !== FALSE) {
				return $this->isTrue($sPath);
			} else {
				return TRUE;	// TRUE as a default
			}
		}

		// alias for _defaultFalse()
		function defaultFalse($sPath) {
			if($this->_navConf($sPath) !== FALSE) {
				return $this->isTrue($sPath);
			} else {
				return FALSE;	// FALSE as a default
			}
		}

		function _defaultTrueMixed($sPath) {
			return $this->defaultTrueMixed($sPath);
		}

		function defaultTrueMixed($sPath) {
			if(($mMixed = $this->_navConf($sPath)) !== FALSE) {
				
				if(strtoupper($mMixed) !== "TRUE" && strtoupper($mMixed) !== "FALSE") {
					return $mMixed;
				}

				return $this->isTrue($sPath);
			} else {
				return TRUE;	// TRUE as a default
			}
		}
		
		function defaultMixed($sPath, $mDefault) {
			if(($mMixed = $this->_navConf($sPath)) !== FALSE) {
				
				if(strtoupper($mMixed) !== "TRUE" && strtoupper($mMixed) !== "FALSE") {
					return $mMixed;
				}
			}
			
			return $mDefault;
		}

		/**
		 * [Describe function...]
		 *
		 * @param	[type]		$sPath: ...
		 * @param	[type]		$aConf: ...
		 * @return	[type]		...
		 */
		function _defaultFalseMixed($sPath) {
			return $this->defaultFalseMixed($sPath);
		}

		function defaultFalseMixed($sPath) {
			if(($mMixed = $this->_navConf($sPath)) !== FALSE) {
				if(is_string($mMixed)) {
					if(strtoupper($mMixed) !== "TRUE" && strtoupper($mMixed) !== "FALSE") {
						return $mMixed;
					}
				}

				return $this->isTrue($sPath);
			} else {
				return FALSE;	// FALSE as a default
			}
		}




		// this has to be static !!!
		static function loaded(&$aParams) {
		}

		function cleanBeforeSession() {
			$this->baseCleanBeforeSession();
			unset($this->oForm);
			$this->aStatics["navconf"] = array();
		}

		function baseCleanBeforeSession() {
			/*unset($this->oForm);*/
		}

		function awakeInSession(&$oForm) {
			$this->oForm =& $oForm;
		}

		function setParent(&$oParent) {
			/* nothing in main object */
		}
		
		function &callRunneable($mMixed) {
			
			// NOTE: for userobj, only ONE argument may be passed			
			$aArgs = func_get_args();
			
			if(($sContext = $this->_navConf("/userobj/php/context", $mMixed)) === "relative") {
				
				if(($mPhp = $this->_navConf("/userobj/php", $mMixed)) !== FALSE) {
					
					if(array_key_exists(1, $aArgs)) {
						$aParams = $aArgs[1];
					} else {
						$aParams = array();
					}
					
					if(is_array($mPhp) && array_key_exists("__value", $mPhp)) {
						$sPhp = $mPhp["__value"];
					} else {
						$sPhp = $mPhp;
					}

					$sClassName = uniqid("tempcl");
					$sMethodName = uniqid("tempmet");

					$this->__sEvalTemp = array("code" => $sPhp, "xml" => $mMixed);

					$GLOBALS['formidable_tempuserobj'] =& $this;
					$sPhp = str_replace("\$this", "\$GLOBALS['formidable_tempuserobj']", $sPhp);

					$sClass =	"class " . $sClassName . " {"
						.	"	function " . $sMethodName . "(\$_this, \$aParams) { \$_this=&\$GLOBALS['formidable_tempuserobj'];"
						.	"		" . $sPhp
						.	"	}"
						.	"}" ;

					//set_error_handler(array(&$this, "__catchEvalException"));
					eval($sClass);
					$oObj = new $sClassName();

					$this->oForm->pushUserObjParam($aParams);

					$sRes = call_user_func(
						array(
							&$oObj,
							$sMethodName
						),
						$this,
						$aParams
					);

					$this->oForm->pullUserObjParam();

					unset($this->__sEvalTemp);
					//restore_error_handler();

					return $sRes;

				}
			}
			
			// it's a codebehind
			$iNbParams = (count($aArgs) - 1);	// without the runneable itself
			
			switch($iNbParams) {
				case 0: { $mRes = $this->oForm->callRunneable($mMixed); break;}
				case 1: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1]); break;}
				case 2: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1], $aArgs[2]); break;}
				case 3: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3]); break;}
				case 4: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4]); break;}
				case 5: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5]); break;}
				case 6: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6]); break;}
				case 7: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7]); break;}
				case 8: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8]); break;}
				case 9: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8], $aArgs[9]); break;}
				case 10: { $mRes = $this->oForm->callRunneable($mMixed, $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8], $aArgs[9], $aArgs[10]); break;}
				default: {
					$this->mayday("CodeBehind " . $mMixed["exec"] . ": can not declare more than 10 arguments.");
					break;
				}
			}
			
			return $mRes;
		}
		
		function getName() {
			return $this->aObjectType["CLASS"];
		}

		function getForm() {
			return $this->oForm;
		}
	}
	
	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainobject.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.mainobject.php"]);
	}
?>
