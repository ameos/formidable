<?php
	
//	die("<b>NOT IMPLEMENTED FOR THE MOMENT !!!</b>");

// Exit, if script is called directly (must be included via eID in index_ts.php)
if (!defined ('PATH_typo3conf')) 	die ('Could not access this script directly!');

if(($sGetPhpSessId = trim(t3lib_div::_GP("phpsessid"))) !== "") {
	session_id($sGetPhpSessId);
	session_start();
} elseif(session_id() === "") {
	session_start();
}


class formidableajax {
	
	var $aRequest	= array();
	var $aConf		= FALSE;
	var $aSession	= array();
	var $aHibernation = array();
	var $oForm		= null;

	function init() {

		
		$value = t3lib_div::_GP("value");
		$value = stripslashes(str_replace('\\n', '###FORMIDABLECR###', $value));
		$value = str_replace("###FORMIDABLECR###", '\\n', $value);

		$context = t3lib_div::_GP("context");
		$context = stripslashes(str_replace('\\n', '###FORMIDABLECR###', $context));
		$context = str_replace("###FORMIDABLECR###", '\\n', $context);
		
		$this->aRequest = array(
			"safelock"		=> t3lib_div::_GP("safelock"),
			"sessionhash"	=> t3lib_div::_GP("sessionhash"),
			"object"		=> t3lib_div::_GP("object"),
			"servicekey"	=> t3lib_div::_GP("servicekey"),
			"eventid"		=> t3lib_div::_GP("eventid"),
			"serviceid"		=> t3lib_div::_GP("serviceid"),
			"value"			=> $value,
			"context"		=> $context,
			"formid"		=> t3lib_div::_GP("formid"),
			"thrower"		=> t3lib_div::_GP("thrower"),
			"arguments"		=> t3lib_div::_GP("arguments"),
			"trueargs"		=> t3lib_div::_GP("trueargs"),
		);
		

		if(array_key_exists("_SESSION", $GLOBALS) && array_key_exists("ameos_formidable", $GLOBALS["_SESSION"])) {
			if(array_key_exists($this->aRequest["object"], $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"])) {
				if(array_key_exists($this->aRequest["servicekey"], $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"][$this->aRequest["object"]])) {
					// requested service exists

					if(array_key_exists($this->aRequest["sessionhash"], $GLOBALS["_SESSION"]["ameos_formidable"]["hibernate"])) {

						if(array_key_exists($this->aRequest["safelock"], $GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"][$this->aRequest["object"]][$this->aRequest["servicekey"]])) {

							// valid session data
							// proceed then

							tslib_eidtools::connectDB();
							if(method_exists('tslib_eidtools', 'initTCA')) {
								tslib_eidtools::initTCA();
							}

							$this->aConf =& $GLOBALS
												["TYPO3_CONF_VARS"]
													["EXTCONF"]
														["ameos_formidable"]
															["ajax_services"]
																[$this->aRequest["object"]]
																	[$this->aRequest["servicekey"]]
																		["conf"];

							$this->aSession =&	$GLOBALS
													["_SESSION"]
														["ameos_formidable"]
															["ajax_services"]
																[$this->aRequest["object"]]
																	[$this->aRequest["servicekey"]]
																		[$this->aRequest["safelock"]];

							$this->aHibernation =& $GLOBALS
													["_SESSION"]
														["ameos_formidable"]
															["hibernate"]
																[$this->aRequest["sessionhash"]];
																
							require_once(PATH_formidable . "api/class.tx_ameosformidable.php");

							$this->oForm =& tx_ameosformidable::unHibernate($this->aHibernation);
							$this->oForm->cObj = t3lib_div::makeInstance('\\TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

							if($this->aConf["virtualizeFE"]) {
								$this->oForm->__virtualizeFE(
									$this->aHibernation["tsfe_config"]
								);
								$GLOBALS["TSFE"]->config = $this->aHibernation["tsfe_config"];
								$GLOBALS["TSFE"]->tmpl->setup["config."]["sys_language_uid"] = $this->aHibernation["sys_language_uid"];
								$GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."] = $this->aHibernation["formidable_tsconfig"];
								$GLOBALS["TSFE"]->sys_language_uid = $this->aHibernation["sys_language_uid"];
								$GLOBALS["TSFE"]->sys_language_content = $this->aHibernation["sys_language_content"];
								$GLOBALS["TSFE"]->lang = $this->aHibernation["lang"];
								$GLOBALS["TSFE"]->id = $this->aHibernation["pageid"];
								$GLOBALS["TSFE"]->spamProtectEmailAddresses = $this->aHibernation["spamProtectEmailAddresses"];
								$GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst'] = $this->aHibernation["spamProtectEmailAddresses_atSubst"];
								$GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_lastDotSubst'] = $this->aHibernation["spamProtectEmailAddresses_lastDotSubst"];
							}

							if($this->aConf["initBEuser"]) {
								$this->_initBeUser();
							}

							$aRdtKeys = array_keys($this->oForm->aORenderlets);
							reset($aRdtKeys);
							while(list(,$sKey) = each($aRdtKeys)) {
								if(is_object($this->oForm->aORenderlets[$sKey])) {
									$this->oForm->aORenderlets[$sKey]->awakeInSession($this->oForm);
								} else {
									//debug($sKey);
								}
							}

							reset($this->oForm->aODataSources);
							while(list($sKey,) = each($this->oForm->aODataSources)) {
								$this->oForm->aODataSources[$sKey]->awakeInSession($this->oForm);
							}
							
							
							$sAjaxCharset = strtoupper($this->oForm->getAjaxCharset());  // pas de distinction entre "iso" et "ISO"
							
							$bIsIso = strpos($sAjaxCharset, 'ISO') !== FALSE;
							$bIsLatin = strpos($sAjaxCharset, 'LATIN') !== FALSE;
							
							if($bIsIso || $bIsLatin) {
								// Source was in ISO charset. Since we always receive Ajax data in UTF-8, we know how to get it back to ISO:
								
								$this->aRequest['context'] = utf8_decode($this->aRequest['context']);
								$this->aRequest['value'] = utf8_decode($this->aRequest['value']);
								$this->aRequest['trueargs'] = utf8_decode($this->aRequest['trueargs']);
							}

							$this->aRequest["params"] = $this->oForm->json2array(
								$this->aRequest["value"]
							);
							
							
							$this->aRequest["trueargs"] = $this->oForm->json2array(
								$this->aRequest["trueargs"]
							);

							$this->aRequest["context"] =  $this->oForm->json2array(
								$this->aRequest["context"]
							);
							
							return TRUE;
						} else {
							$this->denyService("no safelock");
						}
					} else {
						$this->denyService("no hibernate; Check: that you have cookies enabled; that the formidable is NOT CACHED;");
					}
				} else {
					$this->denyService("no service key");
				}
			} else {
				$this->denyService("no object");
			}
		} else {
			$this->denyService("SESSION is not started !");
		}

		// reject invalid request
		return FALSE;
	}



	function _initFeUser() {
		tslib_eidtools::initFeUser();
	}
	
	function handleRequest() {
		
		$this->oForm->aInitTasksAjax = array();
		$this->oForm->aPostInitTasksAjax = array();
		$this->oForm->aRdtEventsAjax = array();
		$this->oForm->aHeadersAjax = array();
		
		if($this->aRequest["servicekey"] == "ajaxservice") {
			$mData = $this->oForm->handleAjaxRequest($this);
			$sJson = $mData;
		} else {
			if($this->aRequest["object"] == "tx_ameosformidable") {
				$aData = $this->oForm->handleAjaxRequest($this);
			} else {
				$aData = $this->oForm->aORenderlets[$this->getWhoThrown()]->handleAjaxRequest($this);
			}
						
			
			if(!is_array($aData)) {
				$aData = array();
			}
			
			$sJson = $this->oForm->array2json(
				array(
					"init" => $this->oForm->aInitTasksAjax,
					"postinit" => $this->oForm->aPostInitTasksAjax,
					"attachevents" => $this->oForm->aRdtEventsAjax,
					"attachheaders" => $this->oForm->aHeadersAjax,
					"tasks" => $aData,
				)
			);
		}
		
		$this->archiveRequest(
			$this->aRequest
		);

		if(($sCharset = $this->oForm->_navConf("charset", $this->oForm->aAjaxEvents[$this->aRequest["eventid"]]["event"])) === FALSE) {
			$sCharset = $this->oForm->getAjaxCharset();
		}
		
		$this->oForm->_storeFormInSession();

		header("Content-Type: application/json; charset=" . $sCharset);
		die($sJson);
	}

	function denyService($sMessage) {
		
		header("Content-Type: text/plain; charset=UTF-8");
		die("{/* SERVICE DENIED: " . $sMessage . " */}");
	}

	function _initBeUser() {

		global $BE_USER, $_COOKIE, $TYPO3_CONF_VARS;

		$temp_TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
		$TSFE = new $temp_TSFEclassName($TYPO3_CONF_VARS,0,0);
		$TSFE->connectToDB();

		// *********
		// BE_USER
		// *********
		$BE_USER='';
		if ($_COOKIE['be_typo_user']) {		// If the backend cookie is set, we proceed and checks if a backend user is logged in.
				require_once (PATH_t3lib.'class.t3lib_befunc.php');
				require_once (PATH_t3lib.'class.t3lib_userauthgroup.php');
				require_once (PATH_t3lib.'class.t3lib_beuserauth.php');
				require_once (PATH_t3lib.'class.t3lib_tsfebeuserauth.php');

					// the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
				$BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');	// New backend user object
				$BE_USER->OS = TYPO3_OS;
				$BE_USER->lockIP = $TYPO3_CONF_VARS['BE']['lockIP'];
				$BE_USER->start();			// Object is initialized
				$BE_USER->unpack_uc('');
				if ($BE_USER->user['uid'])	{
					$BE_USER->fetchGroupData();
					$TSFE->beUserLogin = 1;
				}
				if ($BE_USER->checkLockToIP() && $BE_USER->checkBackendAccessSettingsFromInitPhp())	{
					$BE_USER->extInitFeAdmin();
					if ($BE_USER->extAdmEnabled)	{
						require_once(t3lib_extMgm::extPath('lang').'lang.php');
						$LANG = t3lib_div::makeInstance('language');
						$LANG->init($BE_USER->uc['lang']);

						$BE_USER->extSaveFeAdminConfig();
							// Setting some values based on the admin panel
						$TSFE->forceTemplateParsing = $BE_USER->extGetFeAdminValue('tsdebug', 'forceTemplateParsing');
						$TSFE->displayEditIcons = $BE_USER->extGetFeAdminValue('edit', 'displayIcons');
						$TSFE->displayFieldEditIcons = $BE_USER->extGetFeAdminValue('edit', 'displayFieldIcons');

						if (t3lib_div::_GP('ADMCMD_editIcons'))	{
							$TSFE->displayFieldEditIcons=1;
							$BE_USER->uc['TSFE_adminConfig']['edit_editNoPopup']=1;
						}
						if (t3lib_div::_GP('ADMCMD_simUser'))	{
							$BE_USER->uc['TSFE_adminConfig']['preview_simulateUserGroup']=intval(t3lib_div::_GP('ADMCMD_simUser'));
							$BE_USER->ext_forcePreview=1;
						}
						if (t3lib_div::_GP('ADMCMD_simTime'))	{
							$BE_USER->uc['TSFE_adminConfig']['preview_simulateDate']=intval(t3lib_div::_GP('ADMCMD_simTime'));
							$BE_USER->ext_forcePreview=1;
						}

							// Include classes for editing IF editing module in Admin Panel is open
						if (($BE_USER->extAdmModuleEnabled('edit') && $BE_USER->extIsAdmMenuOpen('edit')) || $TSFE->displayEditIcons == 1)	{
							$TSFE->includeTCA();
							if ($BE_USER->extIsEditAction())	{
								require_once (PATH_t3lib.'class.t3lib_tcemain.php');
								$BE_USER->extEditAction();
							}
							if ($BE_USER->extIsFormShown())	{
								require_once(PATH_t3lib.'class.t3lib_tceforms.php');
								require_once(PATH_t3lib.'class.t3lib_iconworks.php');
								require_once(PATH_t3lib.'class.t3lib_loaddbgroup.php');
								require_once(PATH_t3lib.'class.t3lib_transferdata.php');
							}
						}

						if ($TSFE->forceTemplateParsing || $TSFE->displayEditIcons || $TSFE->displayFieldEditIcons)	{ $TSFE->set_no_cache(); }
					}

			//		$WEBMOUNTS = (string)($BE_USER->groupData['webmounts'])!='' ? explode(',',$BE_USER->groupData['webmounts']) : Array();
			//		$FILEMOUNTS = $BE_USER->groupData['filemounts'];
				} else {	// Unset the user initialization.
					$BE_USER='';
					$TSFE->beUserLogin=0;
				}
		} elseif ($TSFE->ADMCMD_preview_BEUSER_uid)	{
			require_once (PATH_t3lib.'class.t3lib_befunc.php');
			require_once (PATH_t3lib.'class.t3lib_userauthgroup.php');
			require_once (PATH_t3lib.'class.t3lib_beuserauth.php');
			require_once (PATH_t3lib.'class.t3lib_tsfebeuserauth.php');

				// the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
			$BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');	// New backend user object
			$BE_USER->userTS_dontGetCached = 1;
			$BE_USER->OS = TYPO3_OS;
			$BE_USER->setBeUserByUid($TSFE->ADMCMD_preview_BEUSER_uid);
			$BE_USER->unpack_uc('');
			if ($BE_USER->user['uid'])	{
				$BE_USER->fetchGroupData();
				$TSFE->beUserLogin = 1;
			} else {
				$BE_USER = '';
				$TSFE->beUserLogin = 0;
			}
		}

		return $BE_USER;
	}

	function getWhoThrown() {

		$sThrower = $this->aRequest["thrower"];
		$aWho = explode(".", $sThrower);
		
		if(count($aWho) > 1) {
			array_shift($aWho);
			return implode(".", $aWho);
		}

		return FALSE;
	}

	function &getThrower() {
		if(($sWho = $this->getWhoThrown()) !== FALSE) {
			if(array_key_exists($sWho, $this->oForm->aORenderlets)) {
				return $this->oForm->aORenderlets[$sWho];
			}
		}

		return FALSE;
	}

	function getParams() {
		return $this->aRequest["params"];
	}

	function getParam($sParamName) {
		if(array_key_exists($sParamName, $this->aRequest["params"])) {
			return $this->aRequest["params"][$sParamName];
		}

		return FALSE;
	}
	
	function archiveRequest($aRequest) {
		$this->oForm->archiveAjaxRequest($aRequest);
	}
	
	function getPreviousRequest() {
		return $this->oForm->getPreviousAjaxRequest();
	}
	
	function getPreviousParams() {
		return $this->oForm->getPreviousAjaxParams();
	}
}


$oAjax = new formidableajax();
if($oAjax->init() !== FALSE) {
	$oAjax->handleRequest();
} else {
	$oAjax->denyService();
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/remote/formidableajax.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/remote/formidableajax.php"]);
}
?>
