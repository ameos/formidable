<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Jerome Schneider (typo3dev@ameos.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Formidable API
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

define("AMEOSFORMIDABLE_EVENT_SUBMIT_FULL",		"AMEOSFORMIDABLE_EVENT_SUBMIT_FULL");
define("AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH",	"AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH");
define("AMEOSFORMIDABLE_EVENT_SUBMIT_TEST",		"AMEOSFORMIDABLE_EVENT_SUBMIT_TEST");
define("AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT",	"AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT");
define("AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR",	"AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR");
define("AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH",	"AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH");

define("AMEOSFORMIDABLE_LEXER_VOID",			"AMEOSFORMIDABLE_LEXER_VOID");
define("AMEOSFORMIDABLE_LEXER_FAILED",			"AMEOSFORMIDABLE_LEXER_FAILED");
define("AMEOSFORMIDABLE_LEXER_BREAKED",			"AMEOSFORMIDABLE_LEXER_BREAKED");

define("AMEOSFORMIDABLE_XPATH_FAILED",			"AMEOSFORMIDABLE_XPATH_FAILED");
define("AMEOSFORMIDABLE_TS_FAILED",				"AMEOSFORMIDABLE_TS_FAILED");

define("AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN",	".");
define("AMEOSFORMIDABLE_NESTED_SEPARATOR_END",		"");

define("AMEOSFORMIDABLE_NOTSET",				"AMEOSFORMIDABLE_NOTSET");


require_once(PATH_formidable . "api/class.mainobject.php");
require_once(PATH_formidable . "api/class.maindataset.php");
require_once(PATH_formidable . "api/class.maindatasource.php");
require_once(PATH_formidable . "api/class.mainvalidator.php");
require_once(PATH_formidable . "api/class.maindatahandler.php");
require_once(PATH_formidable . "api/class.mainrenderer.php");
require_once(PATH_formidable . "api/class.mainrenderlet.php");
require_once(PATH_formidable . "api/class.mainactionlet.php");
require_once(PATH_formidable . "api/class.jslayer.php");
require_once(PATH_formidable . "api/class.json.php");


class tx_ameosformidable {

	var $bInited				= FALSE;
	var $bInitFromTs			= FALSE;
	var $_xmlPath				= null;
	var $_xmlData				= null;
	var $_aConf					= null;

	var $sExtPath				= null;	// server abs path to formidable
	var $sExtRelPath			= null;	// server path to formidable, relative to server root
	var $sExtWebPath			= null;	// web path to formidable

	var $_aValidators			= null;
	var $_aDataSources			= null;
	var $_aDataHandlers			= null;
	var $_aRenderers			= null;
	var $_aRenderlets			= null;
	var $_aActionlets			= null;

	var $oRenderer				= null;
	var $oDataHandler			= null;
	var $aORenderlets			= array();
	var $aODataSources			= array();

	var $oSandBox				= null;		// stores sandbox for xml-level user-defined "macros"
	var $oJs					= null;
	var $oJson					= null;		// library for manipulation of "JavaScript Object Notation (JSON)" documents
	var $aInitTasksUnobtrusive	= array();
	var $aInitTasks				= array();
	var $aInitTasksOutsideLoad	= array();	// tinyMCE cannot be init'd within Event.observe(window, "load", function() {})
	var $aInitTasksAjax			= array();
	var $aPostInitTasks			= array();	// post init tasks are JS init executed after the init tasks
	var $aPostInitTasksAjax		= array();		// modalbox relies on that for it's HTML is added to the page in an init task when ajax
												// and so, some renderlets, like swfupload, need a chance to execute something when the HTML is ready

	var $_aValidationErrors			= array();
	var $_aValidationErrorsByHtmlId	= array();
	var $_aValidationErrorsInfos	= array();
	var $_aValidationErrorsTypes	= array();

	var $bDebug				= FALSE;
	var $bDebugIP			= FALSE;
	var $aDebug				= array();
	var $start_tstamp		= null;

	var $formid				= "";

	var $_oParent			= null;
	var $oParent			= null;		// alias for _oParent ...

	var $bRendered			= FALSE;
	var $aSteps				= FALSE;	// array of steps for multi-steps forms
	var $_aStep				= FALSE;	// current step extracted from session and stored for further user

	var $sApiVersion		= null;
	var $sXmlVersion		= FALSE;

	var $iForcedEntryId		= FALSE;
	var $_aSubXmlCache		= array();	// cache container for the _getXml method
	var $_aInjectedData		= array();	// contains data to inject in the form at init
	var $aLastTs			= array();
	var $cObj				= null;

	var $bStoreFormInSession	= FALSE;	// whether or not to keep FORM in session for further use (ex: processing ajax events)
	var $bStoreParentInSession	= FALSE;	// whether or not to keep parent in session, if form is stored (ie $bStoreFormInSession==TRUE)

	var $aServerEvents		= array();
	var $aAjaxEvents		= array();
	var $aAjaxArchive		= array();	// archives the successive ajax events that are triggered during the page lifetime
											// meant to be accessed thru getPreviousAjaxRequest() and getPreviousAjaxParams()
	var $aAjaxServices		= array();

	var $aTempDebug			= array();

	var $aCrossRequests		= array();
	var $aOnloadEvents		= array(	// stores events that have to be thrown at onload ( onDOMReady actually )
		"ajax"		=> array(),
		"client"	=> array()
	);

	var $aSkinManifests		= array();

	var $__aRunningObjects	= array();

	var $oHtml				= FALSE;
	var $aRdtEvents			= array();
	var $aRdtEventsAjax		= array();	// stores the events that are added to the page via ajax
	var $aPreRendered		= array();

	var $aHeadersAjax		= array();	// stores the headers that are added to the page via ajax
	var $aHeadersWhenInjectNonStandard = array();	// stores the headers when they have to be injected in the page content at given marker

	var $oMajixEvent		= FALSE;
	var $aUserObjParamsStack = array();
	var $aForcedUserObjParamsStack = array();
	var $aAvailableCheckPoints = array(
		"start",
		"before-compilation",	// kept for back-compat, but should not be here
		"after-compilation",
		"before-init",
		"before-init-renderer",
		"after-init-renderer",
		"before-init-renderlets",
		"after-init-renderlets",
		"before-init-datahandler",
		"after-init-datahandler",
		"after-init",
		"before-render",
		"after-validation",
		"after-validation-ok",
		"after-validation-nok",
		"after-render",
		"before-actionlets",
		"after-actionlets",
		"end-creation",
		"end-edition",
		"before-hibernation",
		"after-hibernation",
		"end",
	);

	var $aPossibleJsapi = array('prototype', 'jquery');
	var $aExcludeRdtForJsapi = array(
		'prototype' => array('PLUPLOAD'),
		'jquery' 	=> array(/*'DATE',*/ 'ACCORDION', 'JSTREE'),
	);
	var $sJsapi = FALSE;

	var $aAddVars = FALSE;
	var $aAddPostVars = FALSE;
	var $aAddGetVars = FALSE;
	var $aRawPost = array();	// stores the POST vars array, hashed by formid
	var $aRawGet = array();		// stores the GET vars array, hashed by formid
	var $aRawFile = array();	// stores the FILE vars array, hashed by formid

	var $sFormAction = FALSE;	// if FALSE, form action will be determined from GET() and thus, transparent
	var $aFormAction = array();
	var $aParamsToRemove = array();

	var $aCodeBehinds = array();
	var $aCB = array();
	var $aCodeBehindJsIncludes = array();
	var $aCodeBehindJsInits = array();
	var $aCurrentRdtStack = array();	// stacks the current renderlets (in majix context, and in normal context)
	var $aPostFlags = FALSE;

	var $bAjaxValidation = FALSE;
	var $oCollection = FALSE;		// used in initView()
	var $sFHash = FALSE;
	var $bUsePHPJson = FALSE;
	var $bMayPHPJsonForceObject = FALSE;

	var $sXpathToMeta = "/meta/";
	var $sXpathToControl = "/control/";
	var $sXpathToElements = "/elements/";

	var $bIsFirstDisplay = AMEOSFORMIDABLE_NOTSET;

	var $executionInfo = array();

	function tx_ameosformidable() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->aPostFlags = t3lib_div::_GP("postflag");
		if(!is_array($this->aPostFlags)) {
			$this->aPostFlags = array();
		}

		/* testing PHP json encode */
		$this->__detectPHPJson();
	}

	function __detectPHPJson() {

		$this->bUsePHPJson = FALSE;
		$this->bMayPHPJsonForceObject = FALSE;

		if(function_exists('json_encode')) {
			$this->bUsePHPJson = TRUE;

			if((@json_encode(array("hello"), JSON_FORCE_OBJECT) === '{"0":"hello"}')) {
				$this->bMayPHPJsonForceObject = TRUE;
			}
		}
	}

	function mayUsePHPJson() {
		return $this->bUsePHPJson;
	}

	function mayPHPJsonForceObject() {
		return $this->bMayPHPJsonForceObject;
	}

	/*********************************
	 *
	 * FORMidable initialization
	 *
	 *********************************/

	/**
	 * Standard init function
	 * Initializes :
	 * - the reference to the parent Extension ( stored in $this->_oParent )
	 * - the XML conf
	 * - the internal collection of Validators
	 * - the internal collection of DataHandlers
	 * - the internal collection of Renderers
	 * - the internal collection of Renderlets
	 * - the Renderer as configured in the XML conf in the /formidable/control/renderer/ section
	 * - the DataHandler as configured in the XML conf in the /formidable/control/datahandler/ section
	 *
	 * 		//	CURRENT SERVER EVENT CHECKPOINTS ( means when to process the even; ex:  <onclick runat="server" when="after-compilation" /> )
	 * 		//	DEFAULT IS *after-init*
	 * 		//
	 * 		//		start
	 * 		//		before-compilation
	 * 		//		before-compilation
	 * 		//		after-compilation
	 * 		//		before-init
	 * 		//		before-init-renderer
	 * 		//		after-init-renderer
	 * 		//		before-init-renderlets
	 * 		//		after-init-renderlets
	 * 		//		before-init-datahandler
	 * 		//		after-init-datahandler
	 * 		//		after-init
	 * 		//		before-render
	 * 		//		after-render
	 * 		//		end
	 *
	 * @param	object		Parent extension using FORMidable
	 * @param	mixed		Absolute path to the XML configuration file
	 * @param	[type]		$iForcedEntryId: ...
	 * @return	void
	 */
	function init(&$oParent, $mXml, $iForcedEntryId = FALSE) {
		$this->garbageCollector();
		$this->sessionStart();
		$this->start_tstamp	= t3lib_div::milliseconds();

		$this->makeHtmlParser();
		$this->_makeJsonObj();

		if($this->__getEnvExecMode() !== "FE") {	// virtualizing FE for BE and eID (ajax) modes
			$this->__virtualizeFE();
		}

	/***** BASE INIT *****
	*
	*/
		$this->sExtPath = PATH_formidable;
		$this->sExtRelPath = t3lib_extMgm::siteRelPath("ameos_formidable");
		$this->sExtWebPath = t3lib_div::getIndpEnv("TYPO3_SITE_URL") . t3lib_extMgm::siteRelPath("ameos_formidable");



		$this->sApiVersion = $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ext_emconf.php"]["version"];
		$this->sApiVersionInt = t3lib_div::int_from_ver($GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ext_emconf.php"]["version"]);

		$this->conf =& $GLOBALS["TSFE"]->config["config"]["tx_ameosformidable."];

		$this->_oParent =& $oParent;
		$this->oParent =& $oParent;

		$this->aTempDebug = array();

		$this->_loadDeclaredDataSources();
		$this->_loadDeclaredValidators();
		$this->_loadDeclaredDataHandlers();
		$this->_loadDeclaredRenderers();
		$this->_loadDeclaredRenderlets();
		$this->_loadDeclaredActionlets();


	/***** XML INIT *****
	*
	*/

		if($this->bInitFromTs === FALSE) {

			/** Cyrille Berliat : Patch to handle direct XML arrays when passed to init */
			if(is_array($mXml)) {
				$this->_aConf = $mXml;
			} else {
				$this->_xmlPath = $this->toServerPath($mXml);
				$this->_loadXmlConf();
			}

		} else {
			$this->_aConf = $mXml;
			$this->_aConf = $this->refineTS($this->_aConf);
		}


	/***** DEBUG INIT *****
	*
	*	After this point raw xml data is available ( means before precompilation )
	*	So it is now possible to get some basic config from the xml
	*
	*/


		/* determine if meta+control+elements or head+body */
		if($this->_navConf("/head") !== FALSE) {
			$this->sXpathToMeta = "/head/";
			$this->sXpathToControl = "/head/";
		}

		if($this->_navConf("/body") !== FALSE) {
			$this->sXpathToElements = "/body/";
		}

		if(t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
			$this->bDebugIP = TRUE;
		}

		$this->oJs = t3lib_div::makeInstance("formidable_jslayer");
		$this->oJs->_init($this);


	/***** INIT FORM SIGNATURE *****
	*
	*/
		$this->formid = $this->_navConf($this->sXpathToMeta . "form/formid");
		if(tx_ameosformidable::isRunneable($this->formid)) {
			$this->formid = $this->callRunneable($this->formid);
		}

		//$this->uniqueid = $this->formid . "_" . t3lib_div::shortMd5(serialize($this->_aConf) . "cUid:" . $this->_oParent->cObj->data["uid"], 5);

		// CHECKING FORMID COLLISION IN PAGE
		if(!array_key_exists($this->formid, $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["context"]["forms"])/* || !$this->defaultFalse($this->sXpathToMeta . "formwrap")*/) {
			$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["context"]["forms"][$this->formid] = array();
		} else {
			$this->mayday("Two (or more) Formidable are using the same formid '<b>" . $this->formid . "</b>' on this page - cannot continue");
		}

		$this->initAddVars();
		$this->buildCodeBehinds();

	/***** INIT DEFAULT (TEMPORARY) DATAHANDLER AND RENDERER *****
	*
	*	These two instances are meant to be destroyed later in the init process
	*	Useful for giving access to objects at precompilation time
	*
	*/

		$this->oDataHandler =& $this->_makeDefaultDataHandler();
		$this->oRenderer =& $this->_makeDefaultRenderer();

	/***** INIT EDIT MODE ? *****
	*
	*/

		if($iForcedEntryId !== FALSE) {
			// uid "iForcedEntryId" was passed to init() method of formidable

			if(($iCurrentEntryId = $this->oDataHandler->_currentEntryId()) !== FALSE) {
				// there is already an uid asked for edition
					// it has been passed thru POST var myformid[AMEOSFORMIDABLE_ENTRYID]

				if($iForcedEntryId != $iCurrentEntryId) {
					// the old edited uid is different of the newly asked one
						// therefore we'll ask formidable to *force* edition of this iForcedEntryId
						// meaning that formidable should forget field-values passed by POST
						// and re-take the record from DB
					$this->forceEntryId($iForcedEntryId);
				} else {
					// the old edited uid is the same that the newly asked one
					// let formidable handle himself the uid passed thru POST var myformid[AMEOSFORMIDABLE_ENTRYID]
					$iForcedEntryId = FALSE;
				}
			} else {
				$this->forceEntryId($iForcedEntryId);
			}
		} elseif(($mUid = $this->_navConf($this->sXpathToControl . "datahandler/editentry")) !== FALSE) {

			if(tx_ameosformidable::isRunneable($mUid)) {
				$mUid = $this->callRunneable($mUid);
			}

			if(($iCurrentEntryId = $this->oDataHandler->_currentEntryId()) !== FALSE) {
				if($mUid != $iCurrentEntryId) {
					$this->forceEntryId($mUid);
				}
			} else {
				$this->forceEntryId($mUid);
			}

		}

		if($this->iForcedEntryId === FALSE) {
			if(($iTempUid = $this->editionRequested()) !== FALSE) {
				$this->forceEntryId($iTempUid);
			} else {
				$this->forceEntryId($iForcedEntryId);
			}
		}

		$aRawPost = $this->_getRawPost();
		if(trim($aRawPost["AMEOSFORMIDABLE_SERVEREVENT"]) !== "") {
			$aServerEventParams = $this->_getServerEventParams();
			if(array_key_exists("_sys_earlybird", $aServerEventParams)) {
				$aEarlyBird = $aServerEventParams["_sys_earlybird"];

				$aEvent = $this->_navConf(
					$aEarlyBird["xpath"],
					$this->_aConf
				);

				$this->callRunneable(
					$aEvent,
					$aServerEventParams
				);
			}
		}



	/***** XML PRECOMPILATION *****
	*
	*	Applying modifiers on the xml structure
	*	Thus producing new parts of xml and deleting some
	*	To get the definitive XML
	*
	*/
	$this->_aConf = $this->_compileConf(
		$this->_aConf,
		$this->aTempDebug
	);

	$this->iDebug = intval($this->_navConf($this->sXpathToMeta . "debug"));

	if($this->iDebug > 0) {
		$this->bDebug = TRUE;
	} else {
		$this->bDebug = $this->isTrue($this->sXpathToMeta . "debug/");
		if($this->bDebug) {
			$this->iDebug = 2;	// LIGHT
		}
	}

	$GLOBALS["TYPO3_DB"]->store_lastBuiltQuery = TRUE;
	if($this->bDebug) {
		$GLOBALS["TYPO3_DB"]->debugOutput = TRUE;
	}



	/***** GRABBING SERVER EVENTS *****
	*
	*	Grabbing the server and ajax events
	*
	*/
		/*$this->_grabServerAndAjaxEvents(
			$this->_aConf["elements"]
		);*/

		$this->checkPoint(
			array(
				"start",
			)
		);



		$this->bReliableXML = TRUE;

		// RELIABLE XML DATA CANNOT BE ACCESSED BEFORE THIS POINT
		// AND THEREFORE NEITHER ALL OBJECTS CONFIGURED BY THIS XML
		// (END OF XML PRE-COMPILATION)

		$this->sDefaultLLLPrefix = $this->_navConf($this->sXpathToMeta . "defaultlll");

		if(tx_ameosformidable::isRunneable($this->sDefaultLLLPrefix)) {
			$this->sDefaultLLLPrefix = $this->callRunneable(
				$this->sDefaultLLLPrefix
			);
		}

		if($this->sDefaultLLLPrefix === FALSE && $this->isParentTypo3Plugin()) {
			if($this->oParent->scriptRelPath) {
				$sLLPhp = "EXT:" . $this->oParent->extKey . "/" . dirname($this->oParent->scriptRelPath) . "/locallang.php";
				$sLLXml = "EXT:" . $this->oParent->extKey . "/" . dirname($this->oParent->scriptRelPath) . "/locallang.xml";

				if(file_exists($this->toServerPath($sLLPhp))) {
					$this->sDefaultLLLPrefix = $sLLPhp;
				}

				if(file_exists($this->toServerPath($sLLXml))) {
					$this->sDefaultLLLPrefix = $sLLXml;
				}
			}
		}

		if($this->bDebug) {

			$aTrace		= debug_backtrace();
			$aLocation	= array_shift($aTrace);

			$this->_debug(
				"User called FORMidable<br>"
			.	"<br>&#149; In :<br>"
			.	"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $aLocation["file"] . ":" . $aLocation["line"]
			.	"<br>&#149; At :<br>"
			.	"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $aLocation["class"] . $aLocation["type"] . $aLocation["function"]
			.	"<br>&#149; With args: <br>"
			.	"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $this->_viewMixed($aLocation["args"])
			.	(($this->iForcedEntryId !== FALSE) ? "<br>&#149; Edition of entry " . $this->iForcedEntryId . " requested" : ""),
				"INITIALIZATION OF FORMIDABLE"
			);

			if(!empty($this->aTempDebug["aIncHierarchy"])) {
				$this->_debug(
					$this->aTempDebug["aIncHierarchy"],
					"XML INCLUSION HIERARCHY",
					FALSE
				);
			} else {
				$this->_debug(
					null,
					"NO XML INCLUSION",
					FALSE
				);
			}
		}



		$this->checkPoint(
			array(
				"after-compilation",
				"before-init",
				"before-init-renderer"
			)
		);

		if(($sAction = $this->_navConf($this->sXpathToMeta . "form/action")) !== FALSE) {

			if(tx_ameosformidable::isRunneable($sAction)) {
				$sAction = $this->callRunneable($sAction);
			}

			if($sAction !== FALSE) {
				$this->sFormAction = trim($sAction);
			} else {
				$this->sFormAction = FALSE;
			}
		} else {
			$this->sFormAction = FALSE;
		}

		$this->analyzeFormAction();
		if($this->useFHash()) {
			$this->formActionAdd(
				array(
					$this->formid => array('fhash' => $this->getFHash())
				)
			);
		}


		if(($sSandClass = $this->_includeSandBox()) !== FALSE) {
			$this->_createSandBox($sSandClass);
		}

		if(($aOnInit = $this->_navConf($this->sXpathToMeta . "oninit")) !== FALSE && tx_ameosformidable::isRunneable($aOnInit)) {
			$this->callRunneable($aOnInit);
		}

		$this->_initDataSources();
		$this->_initRenderer();

		$this->checkPoint(
			array(
				"after-init-renderer",
				"before-init-renderlets"
			)
		);

		$this->_initRenderlets();
		$this->fetchServerEvents();

		$this->checkPoint(
			array(
				"after-init-renderlets",
				"before-init-datahandler"
			)
		);

		$this->_initDataHandler(
			$this->iForcedEntryId
		);

		$this->checkPoint(
			array(
				"after-init-datahandler",
				"after-init",
			)
		);

		$this->bInited = TRUE;
	}

	function sessionStart() {
		if(trim(session_id()) === "") {
			session_start();
		}

		if(!array_key_exists("ameos_formidable", $GLOBALS["_SESSION"])) {

			$GLOBALS["_SESSION"]["ameos_formidable"] = array();
			$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"] = array();
			$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"] = array();
			$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxevent"] = array();

			$GLOBALS["_SESSION"]["ameos_formidable"]["hibernate"] = array();

			$GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"] = array();
		}
	}

	function isParentTypo3Plugin() {
		return (isset($this->oParent) && is_object($this->oParent) && is_a($this->oParent, "tslib_pibase"));
	}

	function isParentTypo3PluginCached() {
		return $this->isParentTypo3Plugin() && intval($this->oParent->pi_USER_INT_obj) === 0;
	}

	function isParentTypo3PluginNotCached() {
		return $this->isParentTypo3Plugin() && intval($this->oParent->pi_USER_INT_obj) === 1;
	}

	function isFirstDisplay() {
		if($this->bIsFirstDisplay !== AMEOSFORMIDABLE_NOTSET) {
			return $this->bIsFirstDisplay;
		}

		if($this->isParentTypo3PluginCached()) {
			# when cached, we cannot keep track of displays
			# therefore we consider that no display is the first one
			$this->bIsFirstDisplay = FALSE;
		} elseif($this->__getEnvExecMode() === "BE") {
			# TODO: track displays for BE
			$this->bIsFirstDisplay = FALSE;
		} else {
			$sCurrentPageUrl = $this->toWebPath($this->cObj->typolink_URL(array(
				"parameter" => $GLOBALS["TSFE"]->id
			)));

			$sRefererUrl = t3lib_div::getIndpEnv("HTTP_REFERER");

			$this->bIsFirstDisPlay = !t3lib_div::isFirstPartOfStr(
				$sRefererUrl,
				$sCurrentPageUrl
			);
		}

		return $this->bIsFirstDisPlay;
	}

	function initAPI(&$oParent) {
		$this->_oParent =& $oParent;
		$this->formid = t3lib_div::shortMD5(rand());
	}

	function initView(&$oParent, $mXml, /*\IFormidable_ds_collection*/ &$oCollection = NULL) {

		if(is_null($oCollection)) {
			$this->oCollection = FALSE;
		} else {
			$this->oCollection =& $oCollection;
		}

		$this->init($oParent, $mXml);
	}

	/**
	 * Makes a persistent instance of an HTML parser
	 * Mainly used for template processing
	 *
	 * @return	void
	 */
	function makeHtmlParser() {
		if($this->oHtml === FALSE) {
			$this->oHtml = t3lib_div::makeInstance("t3lib_parsehtml");
		}
	}

	function useJs() {
		return $this->defaultTrue($this->sXpathToMeta . "accessibility/usejs");
	}

	function useNewDataStructure() {
		return $this->defaultFalse($this->sXpathToMeta . "usenewdatastructure");
	}

	function isFormActionTransparent() {
		return $this->sFormAction === FALSE;
	}

	function analyzeFormAction() {

		if($this->isFormActionTransparent()) {
			$aGet = t3lib_div::_GET();

			if(array_key_exists("id", $aGet)) {
				unset($aGet["id"]);
			}

			$this->aFormAction = $aGet;
		} else {
			$this->aFormAction = array();
		}
	}

	function formActionAdd($aParams) {
		if($this->isFormActionTransparent()) {
			$this->aFormAction = t3lib_div::array_merge_recursive_overrule(
				$this->aFormAction,
				$aParams
			);
		}
	}

	function formActionRemove($aParams) {
		if($this->isFormActionTransparent()) {
			$this->aFormAction = $this->array_diff_key_recursive(
				$this->aFormAction,
				$aParams
			);
		}
	}

	function array_diff_key() {
		$arrs = func_get_args();
        $result = array_shift($arrs);
        foreach ($arrs as $array) {
            foreach ($result as $key => $v) {
                if (is_array($array) && array_key_exists($key, $array)) {
                    unset($result[$key]);
                }
            }
        }

		return $result;
	}

	function array_diff_key_recursive ($a1, $a2) {

		$r = array();
		reset($a1);
		while(list($k, $v) = each($a1)) {
			if(is_array($v)) {
				$r[$k] = $this->array_diff_key_recursive($a1[$k], $a2[$k]);
			} else {
				$r = $this->array_diff_key($a1, $a2);
            }

			if(is_array($r[$k]) && count($r[$k])==0) {
				unset($r[$k]);
			}
		}
		reset($r);
		return $r;
    }

	function array_diff_recursive($aArray1, $aArray2, $bStrict = FALSE) {
	    $aReturn = array();

	    foreach ($aArray1 as $mKey => $mValue) {
	        if (is_array($aArray2) && array_key_exists($mKey, $aArray2)) {
	            if (is_array($mValue)) {
	                $aRecursiveDiff = $this->array_diff_recursive($mValue, $aArray2[$mKey], $bStrict);
	                if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
	            } else {
					if($bStrict === FALSE) {
						if ($mValue != $aArray2[$mKey]) {
		                    $aReturn[$mKey] = $mValue;
		                }
					} else {
						if ($mValue !== $aArray2[$mKey]) {
		                    $aReturn[$mKey] = $mValue;
		                }
					}
	            }
	        } else {
	            $aReturn[$mKey] = $mValue;
	        }
	    }

	    return $aReturn;
	}

	function getFormAction() {

		if($this->isFormActionTransparent()) {
			$sEnvMode = $this->__getEnvExecMode();

			if($sEnvMode === "BE") {
				$sBaseUrl = t3lib_div::getIndpEnv("TYPO3_REQUEST_URL");
			} elseif($sEnvMode === "EID") {
				$sBaseUrl = t3lib_div::getIndpEnv("HTTP_REFERER");
			} elseif($sEnvMode === "FE") {
				//$aParams = t3lib_div::_GET();
			}

			if($sEnvMode === "BE" || $sEnvMode === "EID") {
				$sNewUrl = t3lib_div::linkThisUrl(
					$sBaseUrl,
					$this->aFormAction
				);
			} elseif($sEnvMode === "FE") {
				$sNewUrl = $this->toWebPath(
					$this->cObj->typolink_URL(array(
						"parameter" => $GLOBALS["TSFE"]->id,
						"additionalParams" => t3lib_div::implodeArrayForUrl(
							"",
							$this->aFormAction
						)
					))
				);
			}

			$sRes = $sNewUrl;
		} else {
			$sRes = $this->sFormAction;
		}

		if(($sAnchor = $this->_navConf($this->sXpathToMeta . "form/actionanchor")) !== FALSE) {
			if(tx_ameosformidable::isRunneable($sAnchor)) {
				$sAnchor = $this->callRunneable($sAnchor);
			}

			if($sAnchor !== FALSE && is_string($sAnchor)) {
				$sAnchor = trim(str_replace("#", "", $sAnchor));
				if($sAnchor !== "") {
					$sRes .= "#" . $sAnchor;
				}
			}
		}

		return $sRes;
	}

	function getFormMethod() {
		if(($sMethod = $this->_navConf($this->sXpathToMeta . "form/method")) !== FALSE) {
			return $sMethod;
		}

		return 'post';
	}

	function setParamsToRemove($aParams) {
		$this->aParamsToRemove = t3lib_div::array_merge_recursive_overrule(
			$this->aParamsToRemove,
			$aParams
		);

		$this->formActionRemove($aParams);
	}

	function initAddVars() {
		$this->initAddPost();
		$this->initAddGet();

		if(is_array($this->aAddPostVars)) {
			$this->aAddVars = $this->aAddPostVars;
		} elseif(is_array($this->aAddGetVars)) {
			$this->aAddVars = $this->aAddGetVars;
		} else {
			$this->aAddVars = FALSE;
		}
	}

	function initAddPost() {
		$this->aAddPostVars = $this->getAddPostVars();
	}

	function initAddGet() {
		$this->aAddGetVars = $this->getAddGetVars();
	}

	function getAddPostVars($sFormId = FALSE) {

		/*$aRawPost = $this->_getRawPost(
			$sFormId,
			$bCache = FALSE
		);*/

		if($sFormId === FALSE) {
			$sFormId = $this->formid;
		}

		$aRawPost = t3lib_div::_POST();
		$aRawPost = is_array($aRawPost[$sFormId]) ? $aRawPost[$sFormId] : array();

		if(array_key_exists("AMEOSFORMIDABLE_ADDPOSTVARS", $aRawPost) && trim($aRawPost["AMEOSFORMIDABLE_ADDPOSTVARS"]) !== "") {
			if(!is_array(($aAddPostVars = $this->oJson->decode($aRawPost["AMEOSFORMIDABLE_ADDPOSTVARS"])))) {
				$aAddPostVars = FALSE;
			}
		} else {
			$aAddPostVars = FALSE;
		}
		//debug($aAddPostVars, "aAddPostVars");
		return $aAddPostVars;
	}

	function getAddGetVars($sFormId = FALSE) {
		if($sFormId === FALSE) {
			$sFormId = $this->formid;
		}

		$aRawGet = t3lib_div::_GET();
		$aRawGet = is_array($aRawGet[$sFormId]) ? $aRawGet[$sFormId] : array();

		if(array_key_exists("action", $aRawGet) && trim($aRawGet["action"]) !== "") {
			$aAddGetVars = array();
			$aAddGetVars['action'] = $aRawGet['action'];
			unset($aRawGet['action']);
			foreach($aRawGet as $sKey => $sValue) {
				$aAddGetVars['params'][$sKey] = $sValue;
			}

		} else {
			$aAddGetVars = FALSE;
		}

		return array($aAddGetVars);
	}

	function editionRequested() {
		if($this->aAddVars !== FALSE) {
			reset($this->aAddVars);
			while(list($sKey, ) = each($this->aAddVars)) {
				if(is_array($this->aAddVars[$sKey]) && array_key_exists("action", $this->aAddVars[$sKey]) && $this->aAddVars[$sKey]["action"] === "requestEdition") {
					$sOurSafeLock = $this->_getSafeLock(
						"requestEdition" . ":" . $this->aAddVars[$sKey]["params"]["tablename"] . ":" . $this->aAddVars[$sKey]["params"]["recorduid"]
					);
					$sTheirSafeLock = $this->aAddVars[$sKey]["params"]["hash"];

					if($sOurSafeLock === $sTheirSafeLock) {
						return $this->aAddVars[$sKey]["params"]["recorduid"];
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * Takes an array of typoscript configuration, and adapt it to formidable syntax
	 *
	 * @param	array		$aConf: TS array for application
	 * @return	array		Refined array
	 */
	function refineTS($aConf) {

		$aTemp = array();

		// processing meta
		$aTemp["meta"] = array();
		reset($aConf["meta."]);
		while(list($sKey,) = each($aConf["meta."])) {
			if(is_string($aConf["meta."][$sKey]) && $aConf["meta."][$sKey] === "codebehind") {
				if(array_key_exists($sKey . ".", $aConf["meta."])) {
					$aTemp["meta"]["codebehind-" . $sKey] = $aConf["meta."][$sKey . "."];
				}
				unset($aConf["meta."][$sKey . "."]);
			} else {
				if(is_array($aConf["meta."][$sKey])) {
					$sPlainKey = substr($sKey, 0, -1);
					$aTemp["meta"][$sPlainKey] = $this->_removeDots($aConf["meta."][$sKey]);
				} else {
					$aTemp["meta"][$sKey] = $aConf["meta."][$sKey];
				}
			}
		}


		// processing control
		$aTemp["control"] = array();
		reset($aConf["control."]);
		while(list($sKey, ) = each($aConf["control."])) {
			if(is_string($aConf["control."][$sKey])) {
				if($sKey === "datahandler") {
					$aTemp["control"]["datahandler"] = array(
						"type" => substr($aConf["control."][$sKey], strlen("datahandler:"))
					);

					if(array_key_exists($sKey . ".", $aConf["control."])) {
						$aTemp["control"]["datahandler"] = t3lib_div::array_merge_recursive_overrule(
							$aTemp["control"]["datahandler"],
							$this->_removeDots($aConf["control."][$sKey . "."])
						);
					}
				} elseif($sKey === "renderer") {
					$aTemp["control"]["renderer"] = array(
						"type" => substr($aConf["control."][$sKey], strlen("renderer:"))
					);

					if(array_key_exists($sKey . ".", $aConf["control."])) {
						$aTemp["control"]["renderer"] = t3lib_div::array_merge_recursive_overrule(
							$aTemp["control"]["renderer"],
							$this->_removeDots($aConf["control."][$sKey . "."])
						);
					}
				}
			} else {
				if($sKey === "actionlets.") {
					$aTemp["control"]["actionlets"] = array();

					reset($aConf["control."][$sKey]);
					while(list($sActKey, ) = each($aConf["control."][$sKey])) {
						if(is_string($aConf["control."][$sKey][$sActKey])) {
							$aTemp["control"]["actionlets"]["actionlet-" . $sActKey] = array(
								"type" => substr($aConf["control."][$sKey][$sActKey], strlen("actionlet:"))
							);

							if(array_key_exists($sActKey . ".", $aConf["control."][$sKey])) {
								$aTemp["control"]["actionlets"]["actionlet-" . $sActKey] = t3lib_div::array_merge_recursive_overrule(
									$aTemp["control"]["actionlets"]["actionlet-" . $sActKey],
									$this->_removeDots($aConf["control."][$sKey][$sActKey . "."])
								);
							}
						}
					}
				} elseif($sKey === "datasources.") {
					$aTemp["control"]["datasources"] = array();

					reset($aConf["control."][$sKey]);
					while(list($sActKey, ) = each($aConf["control."][$sKey])) {
						if(is_string($aConf["control."][$sKey][$sActKey])) {
							$aTemp["control"]["datasources"]["datasource-" . $sActKey] = array(
								"type" => substr($aConf["control."][$sKey][$sActKey], strlen("datasource:"))
							);

							if(array_key_exists($sActKey . ".", $aConf["control."][$sKey])) {
								$aTemp["control"]["datasources"]["datasource-" . $sActKey] = t3lib_div::array_merge_recursive_overrule(
									$aTemp["control"]["datasources"]["datasource-" . $sActKey],
									$this->_removeDots($aConf["control."][$sKey][$sActKey . "."])
								);
							}
						}
					}
				} elseif($sKey === "sandbox.") {
					$aTemp["control"]["sandbox"] = $this->_removeDots($aConf["control."]["sandbox."]);
				}
			}
		}

		// processing renderlets
		$aTemp["elements"] = array();
		reset($aConf["elements."]);
		while(list($sKey, ) = each($aConf["elements."])) {
			if(is_string($aConf["elements."][$sKey])) {

				$aType = explode(":", $aConf["elements."][$sKey]);

				if($aType[0] === "renderlet") {
					if(array_key_exists($sKey . ".", $aConf["elements."])) {

						$aTemp["elements"][$aType[0] . "-" . $sKey . "-" . rand()] = $this->refineTS_renderlet(
							$aConf["elements."][$sKey],
							$aConf["elements."][$sKey . "."]
						);

					} else {
						$aTemp["elements"][$aType[0] . "-" . $sKey . "-" . rand()] = array("type" => $aType[1]);
					}
				}
			}
		}

		return $aTemp;
	}

	/**
	 * Takes a typoscript conf for a renderlet and refines it to formidable-syntax
	 *
	 * @param	string		$sTen: TS name like: 10 = renderlet:TEXT
	 * @param	array		$aTenDot: TS value of 10. like: 10.value = Hello World !
	 * @return	array		refined conf
	 */
	function refineTS_renderlet($sTen, $aTenDot) {
		$aType = explode(":", $sTen);
		$aRdt = array(
			"type" => $aType[1],
		);

		if(array_key_exists("childs.", $aTenDot)) {
			$aRdt["childs"] = array();

			reset($aTenDot["childs."]);
			while(list($sKey, $sChild) = each($aTenDot["childs."])) {

				$aChild = array();
				if(is_string($sChild)) {
					$aChildType = explode(":", $sChild);
					if($aChildType[0] === "renderlet") {
						if(array_key_exists($sKey . ".", $aTenDot["childs."])) {
							$aChild = $this->refineTS_renderlet(
								$sChild,
								$aTenDot["childs."][$sKey . "."]
							);
						} else {
							$aChild = $this->refineTS_renderlet(
								$sChild,
								array()
							);
						}
					}

					$aRdt["childs"][$aChildType[0] . "-" . $sKey . "-" . rand()] = $aChild;
				}
			}

			unset($aTenDot["childs."]);
		}

		if(array_key_exists("validators.", $aTenDot)) {
			$aRdt["validators"] = array();
			reset($aTenDot["validators."]);
			while(list($sKey, $sValidator) = each($aTenDot["validators."])) {
				$aValidator = array();
				if(is_string($sValidator)) {
					$aValType = explode(":", $sValidator);
					if($aValType[0] === "validator") {

						$aValidator["type"] = $aValType[1];

						if(array_key_exists($sKey . ".", $aTenDot["validators."])) {
							$aValidator = t3lib_div::array_merge_recursive_overrule(
								$aValidator,
								$this->_removeDots($aTenDot["validators."][$sKey . "."])
							);
						}

						$aRdt["validators"]["validator-" . $sKey] = $aValidator;
					}
				}
			}

			unset($aTenDot["validators."]);
		}

		$aRdt = t3lib_div::array_merge_recursive_overrule(
			$aRdt,
			$this->_removeDots($aTenDot)
		);

		reset($aRdt);
		return $aRdt;
	}

	/**
	 * Dispatch calls to checkpoints defined in the whole code of formidable
	 * Similar to hooks
	 *
	 * @param	array		$aPoints: names of the checkpoints to consider
	 * @return	void
	 */
	function checkPoint($aPoints) {
		$this->_processServerEvents($aPoints);
		$this->_processRdtCheckPoints($aPoints);
		$this->_processMetaCheckPoints($aPoints);
	}

	/**
	 * Handles checkpoint-calls on renderlets
	 *
	 * @param	array		$aPoints: names of the checkpoints to consider
	 * @return	void
	 */
	function _processRdtCheckPoints(&$aPoints) {
		if(count($this->aORenderlets) > 0) {
			$aKeys = array_keys($this->aORenderlets);
			while(list(, $sKey) = each($aKeys)) {
				$this->aORenderlets[$sKey]->checkPoint($aPoints);
			}
		}
	}

	function _processMetaCheckPoints($aPoints) {
		$aArgs = func_get_args();
		array_shift($aArgs);	// stripping $aPoints from dynamic parameters

		$aMeta = $this->_navConf($this->sXpathToMeta);
		$aKeys = array_keys($aMeta);

		reset($aKeys);
		while(list(, $sKey) = each($aKeys)) {
			$sWhen = "";

			if($sKey{0} == "o" && $sKey{1} == "n" && (substr($sKey, 0, 12) === "oncheckpoint")) {
				$sWhen = $this->_navConf($this->sXpathToMeta . $sKey . "/when");
				if(in_array($sWhen, $aPoints)) {
					if(tx_ameosformidable::isRunneable($aMeta[$sKey])) {
						// adding the runneable as first pos of parameters to callRunneable
						$aTheseArgs = $aArgs;
						array_unshift($aTheseArgs, $aMeta[$sKey]);
						call_user_func_array(array($this, "callRunneable"), $aTheseArgs);
					}
				}
			}
		}
	}

	/**
	 * Includes the sandbox in php context
	 *
	 * @return	mixed		FALSE or name of the sandbox class
	 */
	function _includeSandBox() {

		if(TRUE) {

			$aBox = $this->_navConf($this->sXpathToControl . "sandbox");

			if($aBox !== FALSE && is_array($aBox) && array_key_exists("extends", $aBox)) {
				$sExtends = (string)$aBox["extends"];
			} else {
				$sExtends = "EXT:ameos_formidable/res/shared/php/class.defaultsandbox.php::formidable_defaultsandbox";
			}

			$aExtends = explode("::", $sExtends);
			if(sizeof($aExtends) == 2) {
				$sFile = t3lib_div::getFileAbsFileName($aExtends[0]);
				$sClass = $aExtends[1];

				if(file_exists($sFile) && is_readable($sFile)) {
					ob_start();
					require_once($sFile);
					ob_end_clean();		// output buffering for easing use of php class files that execute something outside the class definition ( like BE module's index.php !!)
				} else {
					$this->mayday("<b>The declared php-FILE for sandbox ('" . $sFile . "') doesn't exists</b>");
				}
			} else {

				// trying to auto-determine class-name

				$sFile = t3lib_div::getFileAbsFileName($aExtends[0]);
				if(file_exists($sFile) && is_readable($sFile)) {
					$aClassesBefore = get_declared_classes();

					ob_start();
					require_once($sFile);
					ob_end_clean();		// output buffering for easing use of php class files that execute something outside the class definition ( like BE module's index.php !!)
					$aClassesAfter = get_declared_classes();

					$aNewClasses = array_diff($aClassesAfter, $aClassesBefore);

					if(count($aNewClasses) !== 1) {
						$this->mayday("<b>Cannot automatically determine the classname to use for the sandbox in '" . $sFile . "'</b><br />Please add '::myClassName' after the file-path in the sandbox declaration");
					} else {
						$sClass = array_shift($aNewClasses);
					}
				} else {
					$this->mayday("<b>The declared php-FILE for sandbox ('" . $sFile . "') doesn't exists</b>");
				}

			}

			if(class_exists($sClass)) {

				if($this->isUserObj($aBox)) {

					if(($sPhp = $this->_navConf("/userobj/php", $aBox)) !== FALSE) {

						if(class_exists($sClass)) {
							$sExtends = " extends " . $sClass;
						}

						$sClassName = "formidablesandbox_" . md5($sPhp);	// these 2 lines
						if(!class_exists($sClassName)) {					// allows same sandbox twice or more on the same page

							$sSandClass = <<<SANDBOXCLASS

	class {$sClassName} extends {$sClass} {
		var \$oForm = null;
		{$sPhp}
	}

SANDBOXCLASS;

							$this->__sEvalTemp = array("code" => $sSandClass, "xml" => $aBox);
							set_error_handler(array(&$this, "__catchEvalException"));
							eval($sSandClass);
							unset($this->__sEvalTemp);
							restore_error_handler();
						}

						return $sClassName;
					}
				} else {
					return $sClass;
				}
			} else {
				$this->mayday("<b>The declared php-CLASS for sandbox ('" . $sClass . "') doesn't exists</b>");
			}
		}

		return FALSE;
	}

	/**
	 * Builds a persistent instance of the sandbox
	 *
	 * @param	string		$sClassName: Name of the sandbox class, as returned by _includeSandBox()
	 * @return	void
	 */
	function _createSandBox($sClassName) {
		$this->oSandBox = new $sClassName();
		$this->oSandBox->oForm =& $this;
		if(method_exists($this->oSandBox, "init")) {
			$this->oSandBox->init($this);	// changed: avoid call-time pass-by-reference
		}
	}

	function buildCodeBehinds() {

		$aMetas = $this->_navConf($this->sXpathToMeta);

		if(tx_ameosformidable::__getEnvExecMode() === "EID") {
			$this->aCodeBehinds["js"] = array();
		} else {
			unset($this->aCodeBehinds);
			unset($this->aCB);
			$this->aCodeBehinds = array(
				"js" => array(),
				"php" => array(),
			);
		}

		// default CB, common
		$sDefaultCBClass = preg_replace("/[^a-zA-Z0-9_]/", "", $this->formid) . "_cb";
		$sDefaultCBDir = $this->toServerPath(dirname($this->_xmlPath));

		// default CB PHP
		$sDefaultCBPhpName = "cb";
		$sDefaultCBPhpFile = "class." . $sDefaultCBClass . ".php";
		$sDefaultCBPhpPath = $sDefaultCBDir . $sDefaultCBPhpFile;

		// default CB JS
		$sDefaultCBJsName = "js";
		$sDefaultCBJsFile = "class." . $sDefaultCBClass . ".js";
		$sDefaultCBJsPath = $sDefaultCBDir . $sDefaultCBJsFile;


		// detecting codebehinds, and checking if defaults are already set
		$bDefaultCBPhpDefined = FALSE;
		$bDefaultCBJsDefined = FALSE;

		$aTempCBs = array();
		reset($aMetas);
		while(list($sKey,) = each($aMetas)) {
			if($sKey{0} === "c" && $sKey{1} === "o" && t3lib_div::isFirstPartOfStr(strtolower($sKey), "codebehind")) {
				$aTempCBs[$sKey] = $this->buildCodeBehind(
					$aMetas[$sKey]
				);

				if($aTempCBs[$sKey]["type"] === "php" && ($aTempCBs[$sKey]["name"] === $sDefaultCBPhpName || $aTempCBs[$sKey]["filepath"] === $sDefaultCBPhpPath)) {
					$bDefaultCBPhpDefined = TRUE;
				}

				if($aTempCBs[$sKey]["type"] === "js" && ($aTempCBs[$sKey]["name"] === $sDefaultCBJsName || $aTempCBs[$sKey]["filepath"] === $sDefaultCBJsPath)) {
					$bDefaultCBJsDefined = TRUE;
				}
			}
		}

		if($this->_xmlPath !== FALSE) {
			// application is defined in an xml file, and we know it's location
			// checking for default codebehind file, named after formid
				// convention over configuration paradigm !


			// PHP CB
			if(!$bDefaultCBPhpDefined && file_exists($sDefaultCBPhpPath) && is_readable($sDefaultCBPhpPath)) {
				$aDefaultCB = $this->buildCodeBehind(array(
					"type" => "php",
					"name" => $sDefaultCBPhpName,
					"path" => $sDefaultCBPhpPath,
					"class" => $sDefaultCBClass,
				));

				$aTempCBs["codebehind-default-php"] = $aDefaultCB;
			}

			// JS CB
			if(!$bDefaultCBJsDefined && file_exists($sDefaultCBJsPath) && is_readable($sDefaultCBJsPath)) {
				$aDefaultCB = $this->buildCodeBehind(array(
					"type" => "js",
					"name" => $sDefaultCBJsName,
					"path" => $sDefaultCBJsPath . ":" . $sDefaultCBClass,
					"class" => $sDefaultCBClass,
				));

				$aTempCBs["codebehind-default-js"] = $aDefaultCB;
			}
		}

		reset($aTempCBs);
		while(list($sKey,) = each($aTempCBs)) {
			$aCB = $aTempCBs[$sKey];

			if($aCB["type"] === "php") {
				if(tx_ameosformidable::__getEnvExecMode() === "EID") {
					$this->aCodeBehinds["php"][$aCB["name"]]["object"] = unserialize($this->aCodeBehinds["php"][$aCB["name"]]["object"]);
					$this->aCodeBehinds["php"][$aCB["name"]]["object"]->oForm =& $this;
				} else {
					$this->aCodeBehinds["php"][$aCB["name"]] = $aCB;
				}

				$this->aCB[$aCB["name"]] =& $this->aCodeBehinds["php"][$aCB["name"]]["object"];
			} elseif($aCB["type"] === "js") {
				$this->aCodeBehinds["js"][$aCB["name"]] = $aCB;
				$this->aCodeBehinds["js"][$aCB["name"]]["object"] = $this->buildJsCbObject($aCB);

				$this->aCB[$aCB["name"]] =& $this->aCodeBehinds["js"][$aCB["name"]]["object"];
			}
		}

		reset($this->aCodeBehinds["php"]);
		while(list($sKey,) = each($this->aCodeBehinds["php"])) {
			$this->initPhpCb($sKey);
		}

		reset($this->aCodeBehinds["js"]);
		while(list($sKey,) = each($this->aCodeBehinds["js"])) {
			$this->initJsCb($sKey);
		}
	}

	function initPhpCb($sKey) {
		$sEnv = $this->__getEnvExecMode();
		if($sEnv === "EID") {
			if(method_exists($this->aCodeBehinds["php"][$sKey]["object"], "initajax")) {
				$this->aCodeBehinds["php"][$sKey]["object"]->initajax($this);	// changed: avoid call-time pass-by-reference
			}
		} else {
			if(method_exists($this->aCodeBehinds["php"][$sKey]["object"], "init")) {
				$this->aCodeBehinds["php"][$sKey]["object"]->init($this);	// changed: avoid call-time pass-by-reference
			}
		}
	}

	function initJSCb($sKey) {
		//debug($sFilePath);
		// inclusion of the JS
		$this->aCodeBehindJsIncludes[$this->aCodeBehinds["js"][$sKey]["class"]] = $this->toServerPath($this->aCodeBehinds["js"][$sKey]["filepath"]);

		$aConfig = array();
		$aTempConfig = array();

		if(isset($this->aCodeBehinds["js"][$sKey]["config"])) {
			$aTempConfig = $this->aCodeBehinds["js"][$sKey]["config"];

			if(tx_ameosformidable::isRunneable($aTempConfig)) {
				$aTempConfig = $this->callRunneable($aTempConfig);
			}

			if(!is_array($aTempConfig)) {
				$aTempConfig = array();
			}
		}

		$aConfig = t3lib_div::array_merge_recursive_overrule(
			$aTempConfig,
			array(
				"formid" => $this->formid
			)
		);

		$sJsonConfig = $this->array2Json($aConfig);

		$sScript = "Formidable.CodeBehind." . $this->aCodeBehinds["js"][$sKey]["class"] . " = new Formidable.Classes." . $this->aCodeBehinds["js"][$sKey]["class"] . "(" . $sJsonConfig . ");";
		$this->aCodeBehindJsInits[] = $sScript;
		$this->aCodeBehinds["js"][$sKey]["object"]->init($this);
	}

	function &buildJsCbObject($aCB) {
		require_once(PATH_formidable . "api/class.mainjscb.php");
		$oJsCb = t3lib_div::makeInstance("formidable_mainjscb");
		$oJsCb->aConf = $aCB;
/*		$oJsCb->init(
			$this,
			$aCB
		);*/
		return $oJsCb;
	}

	function buildCodeBehind($aCB) {

		$sCBRef = $aCB["path"];
		$sName = $aCB["name"];

		if($sCBRef{0} === "E" && $sCBRef{1} === "X" && t3lib_div::isFirstPartOfStr($sCBRef, "EXT:")) {
			$sCBRef = substr($sCBRef, 4);
			$sPrefix = "EXT:";
		} else {
			$sPrefix = "";
		}

		$aParts = explode(":", $sCBRef);

		$sFileRef = $sPrefix . $aParts[0];
		$sFilePath = $this->toServerPath($sFileRef);

		// determining type of the CB
		$sFileExt = strtolower(array_pop(t3lib_div::revExplode(".", $sFileRef, 2)));
		switch($sFileExt) {
			case "php": {
				if(is_file($sFilePath) && is_readable($sFilePath)) {

					if(count($aParts) < 2) {
						if(!in_array($sFilePath, get_included_files())) {

							// class has not been defined. Let's try to determine automatically the class name

							$aClassesBefore = get_declared_classes();
							ob_start();
							require_once($sFilePath);
							ob_end_clean();		// output buffering for easing use of php class files that execute something outside the class definition ( like BE module's index.php !!)
							$aClassesAfter = get_declared_classes();

							$aNewClasses = array_diff($aClassesAfter, $aClassesBefore);

							if(count($aNewClasses) !== 1) {
								$this->mayday("<b>CodeBehind: Cannot automatically determine the classname to use in '" . $sFilePath . "'</b><br />Please add ':myClassName' after the file-path to explicitely.");
							} else {
								$sClass = array_shift($aNewClasses);
							}
						} else {
							$this->mayday("<b>CodeBehind: Cannot automatically determine the classname to use in '" . $sFilePath . "'</b><br />Please add ':myClassName' after the file-path.");
						}
					} else {
						$sClass = $aParts[1];
						ob_start();
						require_once($sFilePath);
						ob_end_clean();		// output buffering for easing use of php class files that execute something outside the class definition ( like BE module's index.php !!)
					}

					if(class_exists($sClass)) {
						$oCB = new $sClass();
						$oCB->oForm =& $this;
						$oCB->aConf = $aCB;

						return array(
							"type" => "php",
							"name" => $sName,
							"filepath" => $sFilePath,
							"class" => $sClass,
							"object" => &$oCB,
							"config" => isset($aCB["config"]) ? $aCB["config"] : FALSE,
						);
					} else {
						$this->mayday("CodeBehind [" . $sCBRef . "]: class <b>" . $sClass . "</b> does not exist.");
					}
				} else {
					$this->mayday("CodeBehind [" . $sCBRef . "]: file <b>" . $sFileRef . "</b> does not exist.");
				}
				break;
			}
			case "js": {

				if(count($aParts) < 2) {
					$this->mayday("CodeBehind [" . $sCBRef . "]: you have to provide a class name for javascript codebehind <b>" . $sCBRef . "</b>. Please add ':myClassName' after the file-path.");
				} else {
					$sClass = $aParts[1];
				}

				if(is_file($sFilePath) && is_readable($sFilePath)) {
					if(intval(filesize($sFilePath)) === 0) {
						//$this->mayday("CodeBehind [" . $sCBRef . "]: seems to be empty</b>.");
						$this->smartMayday_CBJavascript($sFilePath, $sClass, FALSE);
					}

					return array(
						"type" => "js",
						"name" => $sName,
						"filepath" => $sFilePath,
						"class" => $sClass,
						"config" => isset($aCB["config"]) ? $aCB["config"] : FALSE,
					);
				}
				break;
			}
			default: {
				$this->mayday("CodeBehind [" . $sCBRef . "]: allowed file extensions are <b>'.php', '.js' and '.ts'</b>.");
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$oParent: ...
	 * @param	[type]		$aSteps: ...
	 * @return	[type]		...
	 */
	function initSteps(&$oParent, $aSteps) {

		$this->aSteps = $aSteps;

		$aExtract = $this->__extractStep();

		$iStep = $this->_getStep();
		$aCurStep = $this->aSteps[$iStep];

		$sPath = $aCurStep["path"];

		if($aExtract === FALSE || ($iEntryId = $aExtract["AMEOSFORMIDABLE_STEP_UID"]) === FALSE) {
			$iEntryId = (array_key_exists("uid", $aCurStep) ? $aCurStep["uid"] : FALSE);
		}

		$this->init(
			$oParent,	// changed: avoid call-time pass-by-reference
			$sPath,
			$iEntryId
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function _getStepperId() {

		if($this->aSteps !== FALSE) {
			return md5(serialize($this->aSteps));
		}

		return FALSE;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function _getStep() {

		$aStep = $this->__extractStep();
		if($aStep === FALSE) {
			return 0;
		}

		return $aStep["AMEOSFORMIDABLE_STEP"];
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function __extractStep() {

		$sStepperId = $this->_getStepperId();

		if($this->_aStep === FALSE) {
			if(		array_key_exists("ameos_formidable", $GLOBALS["_SESSION"])
				&&	array_key_exists("stepper", $GLOBALS["_SESSION"]["ameos_formidable"])
				&&	array_key_exists($sStepperId, $GLOBALS["_SESSION"]["ameos_formidable"]["stepper"])) {

				$this->_aStep = $GLOBALS["_SESSION"]["ameos_formidable"]["stepper"][$sStepperId];
				unset($GLOBALS["_SESSION"]["ameos_formidable"]["stepper"][$sStepperId]);
			} else {

				$aP = t3lib_div::_POST();

				if(array_key_exists("AMEOSFORMIDABLE_STEP", $aP) && array_key_exists("AMEOSFORMIDABLE_STEP_HASH", $aP)) {

					if($this->_getSafeLock($aP["AMEOSFORMIDABLE_STEP"]) === $aP["AMEOSFORMIDABLE_STEP_HASH"]) {
						$this->_aStep = array(
							"AMEOSFORMIDABLE_STEP" => $aP["AMEOSFORMIDABLE_STEP"],
							"AMEOSFORMIDABLE_STEP_UID" => FALSE,
						);
					}
				}
			}
		}

		return $this->_aStep;	// FALSE if none set
	}

	/**
	 * Util-method for use in __getEventsInConf
	 *
	 * @param	string		$sName: name of the current conf-key
	 * @return	boolean		TRUE if event (onXYZ), FALSE if not
	 */
	function __cbkFilterEvents($sName) {
		return $sName{0} === "o" && $sName{1} === "n";	// should start with "on", but speed check
	}

	/**
	 * Extracts all events defined in a conf array
	 *
	 * @param	array		$aConf: conf containing events to detect
	 * @return	array		events extracted
	 */
	function __getEventsInConf($aConf) {
		return array_merge(		// array_merge reindexes array
			array_filter(
				array_keys($aConf),
				array($this, "__cbkFilterEvents")
			)
		);
	}

	/**
	 * Creates unique ID for a given server event
	 *
	 * @param	string		$sRdtName: name of the renderlet defining the event
	 * @param	array		$aEvent: conf array of the event
	 * @return	string		Server Event ID
	 */
	function _getServerEventId($sRdtName, $aEvent) {
		return $this->_getSafeLock(
			$sRdtName . serialize($aEvent)
		);
	}

	/**
	 * Creates unique ID for a given ajax event
	 *
	 * @param	string		$sRdtName: name of the renderlet defining the event
	 * @param	array		$aEvent: conf array of the event
	 * @return	string		Ajax Event ID
	 */
	function _getAjaxEventId($sRdtName, $aEvent) {
		return $this->_getServerEventId($sRdtName, $aEvent);	// same HashKey algorithm
	}

	/**
	 * Creates unique ID for a given client event
	 *
	 * @param	string		$sRdtName: name of the renderlet defining the event
	 * @param	array		$aEvent: conf array of the event
	 * @return	string		Client Event ID
	 */
	function _getClientEventId($sRdtName, $aEvent) {
		return $this->_getServerEventId($sRdtName, $aEvent);	// same HashKey algorithm
	}

	/**
	 * Executes triggered server events
	 * Called by checkPoint()
	 *
	 * @param	array		$aTriggers: array of checkpoints names to consider
	 * @return	void
	 */
	function _processServerEvents($aTriggers) {

		$aP = $this->_getRawPost();
		if(array_key_exists("AMEOSFORMIDABLE_SERVEREVENT", $aP) && (trim($aP["AMEOSFORMIDABLE_SERVEREVENT"]) !== "")) {
			if(array_key_exists($aP["AMEOSFORMIDABLE_SERVEREVENT"], $this->aServerEvents)) {
				$aEvent = $this->aServerEvents[$aP["AMEOSFORMIDABLE_SERVEREVENT"]];
				if(in_array($aEvent["when"], $aTriggers)) {

					if(tx_ameosformidable::isRunneable($aEvent["event"])) {
						if(array_key_exists($aEvent["name"], $this->aORenderlets)) {
							$this->aORenderlets[$aEvent["name"]]->callRunneable(
								$aEvent["event"],
								$this->_getServerEventParams()
							);
						} else {
							// should never be the case
							$this->callRunneable(
								$aEvent["event"],
								$this->_getServerEventParams()
							);
						}
					}

				}
			}
		} else {
			// handling unobtrusive server events
				// triggered when onclick runart="server" is defined on a SUBMIT renderlet

			reset($this->aServerEvents);
			while(list($sKey, ) = each($this->aServerEvents)) {

				$sAbsName = $this->aServerEvents[$sKey]["name"];
				$sAbsPath = str_replace(".", "/", $sAbsName);

				if(($aRes = $this->navDeepData($sAbsPath, $aP)) !== FALSE) {
					if(array_key_exists($sAbsName, $this->aORenderlets) && $this->aORenderlets[$sAbsName]->aObjectType["TYPE"] === "SUBMIT") {
						$aEvent = $this->aServerEvents[$sKey];
						if(in_array($aEvent["when"], $aTriggers)) {
							$this->callRunneable(
								$aEvent["event"],
								array()
							);
						}
					}
				}
			}
		}
	}

	/**
	 * Get params for the triggered server event
	 *
	 * @return	array		Params
	 */
	function _getServerEventParams() {

		$aPost = $this->_getRawPost();

		if(
			array_key_exists("AMEOSFORMIDABLE_SERVEREVENT_PARAMS", $aPost) &&
			array_key_exists("AMEOSFORMIDABLE_SERVEREVENT_HASH", $aPost) &&
			$this->_getSafeLock($aPost["AMEOSFORMIDABLE_SERVEREVENT_PARAMS"]) == $aPost["AMEOSFORMIDABLE_SERVEREVENT_HASH"]) {

			return unserialize(base64_decode($aPost["AMEOSFORMIDABLE_SERVEREVENT_PARAMS"]));

		} else {
			return array();
		}
	}

	/**
	 * Returns RAW POST+FILES data
	 *
	 * @param	string		$sFormId: optional; if none given, current formid is used
	 * @return	array		POST+FILES data
	 */
	function _getRawPost($sFormId = FALSE, $bCache = TRUE) {

		if($sFormId === FALSE) {
			$sFormId = $this->formid;
		}

		if(!array_key_exists($sFormId, $this->aRawPost) || ($bCache === FALSE)) {
			$aPost = t3lib_div::_POST();
			$aPost	= is_array($aPost[$sFormId]) ? $aPost[$sFormId] : array();
			$aFiles = $this->_getRawFile();

			$aAddParams = array();

			if($sFormId === FALSE) {
				$aAddPostVars = $this->aAddPostVars;
			} else {
				$aAddPostVars = $this->getAddPostVars($sFormId);
			}

			if($aAddPostVars !== FALSE) {

				reset($aAddPostVars);
				while(list($sKey, ) = each($aAddPostVars)) {
					if(array_key_exists("action", $aAddPostVars[$sKey]) && $aAddPostVars[$sKey]["action"] === "formData") {

						reset($aAddPostVars[$sKey]["params"]);
						while(list($sParam, $sValue) = each($aAddPostVars[$sKey]["params"])) {

							$aAddParams =  t3lib_div::array_merge_recursive_overrule(
								$aAddParams,
								tx_ameosformidable::explodeUrl2Array(
									$sParam . "=" . $sValue,
									TRUE	// multidim ?
								)
							);
						}
					}
				}
			}

			$aRes = t3lib_div::array_merge_recursive_overrule($aPost, $aFiles);
			$aRes = t3lib_div::array_merge_recursive_overrule($aRes, $aAddParams);
			reset($aRes);

			if($bCache === FALSE) {
				return $aRes;
			}

			$this->aRawPost[$sFormId] = $aRes;
		}

		return $this->aRawPost[$sFormId];
	}

	function explodeUrl2Array($string,$multidim=FALSE) {
		if(function_exists("t3lib_div::explodeUrl2Array")) {
			return t3lib_div::explodeUrl2Array($string, $multidim);
		}

		$output = array();
		if ($multidim)	{
			parse_str($string,$output);
		} else {
			$p = explode('&',$string);
			foreach($p as $v)	{
				if (strlen($v))	{
					list($pK,$pV) = explode('=',$v,2);
					$output[rawurldecode($pK)] = rawurldecode($pV);
				}
			}
		}
		return $output;
	}

	function _getRawGet($sFormId = FALSE) {
		if($sFormId === FALSE) {
			$sFormId = $this->formid;
		}

		if(!array_key_exists($sFormId, $this->aRawGet)) {
			$aGet = t3lib_div::_GET($sFormId);
			$this->aRawGet[$sFormId] = is_array($aGet) ? $aGet : array();
		}

		reset($this->aRawGet[$sFormId]);
		return $this->aRawGet[$sFormId];
	}

	function getRawGet($sFormId = FALSE) {
		// alias for _getRawGet()
		return $this->_getRawGet($sFormId);
	}

	function _getRawFile($sFormId = FALSE) {
		if($sFormId === FALSE) {
			$sFormId = $this->formid;
		}

		if(!array_key_exists($sFormId, $this->aRawFile)) {
			$aElements = array();
			$aTemp = is_array($GLOBALS["_FILES"][$sFormId]) ? $GLOBALS["_FILES"][$sFormId] : array();
			$aF = array();

			if(!empty($aTemp)) {

				$aTemp = array($sFormId => $aTemp);
				reset($aTemp);

				foreach($aTemp as $var => $info) {
					foreach (array_keys($info) as $attr) {
						$this->groupFileInfoByVariable($aF, $info[$attr], $attr);
					}
				}
			}

			$this->aRawFile[$sFormId] = $aF;
		}

		reset($this->aRawFile[$sFormId]);
		return $this->aRawFile[$sFormId];
	}

	function getRawFile($sFormId = FALSE) {
		// alias for _getRawGet()
		return $this->_getRawFile($sFormId);
	}

	function groupFileInfoByVariable(&$top, $info, $attr) {

		if(is_array($info)) {
			foreach($info as $var => $val) {
				if(is_array($val)) {
					$this->groupFileInfoByVariable($top[$var], $val, $attr);
				} else {
					$top[$var][$attr] = $val;
				}
			}
		} else {
			$top[$attr] = $info;
		}

		return TRUE;
	}

	/**
	 * Loads declared libraries, like scriptaculous
	 * To declare: /meta/libs = scriptaculous, lib_x, lib_y
	 *
	 * @return	void
	 */
	function _includeLibraries() {
		$this->oRenderer->_includeLibraries();
		if(($sLibs = $this->_navConf($this->sXpathToMeta . "libs")) !== FALSE) {
			$aLibs = t3lib_div::trimExplode(",", $sLibs);
			reset($aLibs);

			if($this->oJs->_mayLoadJQuery()) {
				while(list(, $sLib) = each($aLibs)) {
					if($sLib === "effects") {
						$this->oJs->loadJQueryEffects();
					} elseif($sLib === "dragdrop") {
						$this->oJs->loadJQueryDragDrop();
					}
				}
			} else {
				while(list(, $sLib) = each($aLibs)) {
					if($sLib === "scriptaculous" || $sLib === "effects") {
						$this->oJs->loadScriptaculous();
					} elseif($sLib === "dragdrop") {
						$this->oJs->loadScriptaculousDragDrop();
					} elseif($sLib === "builder") {
						$this->oJs->loadScriptaculousBuilder();
					} elseif($sLib === "tooltip") {
						$this->oJs->loadToolTip();
					} elseif($sLib === "lightbox") {
						$this->oJs->loadLightbox();
					}
				}
			}
		}
	}

	/**
	 * Unsets renderlets having /process = FALSE
	 *
	 * @return	void
	 */
	function _filterUnProcessed() {

		$aRdts = array_keys($this->aORenderlets);

		reset($aRdts);
		while(list(, $sName) = each($aRdts)) {
			if(array_key_exists($sName, $this->aORenderlets) && !$this->aORenderlets[$sName]->hasParent()) {
				$this->aORenderlets[$sName]->filterUnprocessed();
			}
		}
	}

	/**
	 * Initialize formidable with typoscript
	 *
	 * @param	object		&$oParent: ref to parent object (usualy plugin)
	 * @param	array		$aConf: typoscript array
	 * @param	integer		$iForcedEntryId: optional; uid to edit, if any
	 * @return	void
	 */
	function initFromTs(&$oParent, $aConf, $iForcedEntryId = FALSE) {
		$this->bInitFromTs = TRUE;
		$this->init($oParent, $aConf, $iForcedEntryId);
	}

	/**
	 * Refine raw conf and:
	 *	-> inserts recursively all includexml declared
	 *	-> inserts recursively all includets declared
	 *	-> apply modifiers declared, if any
	 *	-> remove sections emptied by modifiers, if any
	 *	-> execute xmlbuilders declared, if any
	 *
	 * @param	array		$aConf: array of raw config to refine
	 * @param	[type]		$aTempDebug: internal use
	 * @return	array		refined array of conf
	 */
	function _compileConf($aConf, &$aTempDebug) {

		$aTempDebug["aIncHierarchy"] = array();
		$aInsertedHeaders = array();

		$aConf = $this->_insertSubXml(
			$aConf,
			$aTempDebug["aIncHierarchy"],
			$aInsertedHeaders
		);

		reset($aInsertedHeaders);
		while(list($sKey,) = each($aInsertedHeaders)) {
			$this->setDeepData(
				"/head",
				$aConf,
				$aInsertedHeaders[$sKey],
				TRUE	// bMergeIfArray
			);
		}

		$aConf = $this->_insertSubTS($aConf);
		$aConf = $this->_applyModifiers($aConf);
		$aConf = $this->_deleteEmpties($aConf);	// ????  surveiller

		$aConf = $this->_insertXmlBuilder($aConf);

		$this->_debug($aIncHierarchy, "FORMIDABLE CORE - INCLUSION HIERARCHY");
		return $aConf;
	}

	/**
	 * Executes and inserts conf generated by xmlbuilders, if any declared
	 *
	 * @param	array		$aConf: array of conf to process
	 * @param	array		$aTemp: optional; internal use
	 * @return	array		processed array of conf
	 */
	function _insertXmlBuilder($aConf, $aTemp = array()) {

		reset($aConf);
		while(list($key, $val) = each($aConf)) {

			if(is_array($val)) {

				if($key{0} === "x" && t3lib_div::isFirstPartOfStr($key, "xmlbuilder")) {

					$aTemp = $this->array_add(
						$this->callRunneable($val),
						$aTemp
					);

				} else {
					$aTemp[$key] = $this->_insertXmlBuilder($val);
				}
			} else {
				$aTemp[$key] = $val;
			}
		}

		return $aTemp;
	}

	/**
	 * Insert conf referenced by includexml tags
	 *
	 * @param	array		$aConf: array of conf to process
	 * @param	array		$aDebug: internal use
	 * @param	string		$sParent: optional; parent xpath
	 * @return	array		processed conf array
	 */
	function _insertSubXml($aConf, &$aDebug, &$aHeaders, $sParent = FALSE) {

		$aTemp = array();

		if($sParent === FALSE) {
			$sParent = "/formidable";
		}

		if(!$aConf) { return array();}

		$aConfKeys = array_keys($aConf);
		reset($aConf);
		while(list(, $key) = each($aConfKeys)) {
			$val =& $aConf[$key];

			if(is_array($val)) {

				if($key{0} === "i" && t3lib_div::isFirstPartOfStr($key, "includexml")) {

					if(array_key_exists("path", $val)) {
						$sPath = $val["path"];
					} elseif(trim($val["__value"]) !== "") {
						$sPath = $val["__value"];
					} else {
						$sPath = $this->_xmlPath;
					}

					$bInclude = TRUE;

					if(array_key_exists("condition", $val)) {
						$bInclude = $this->defaultTrue("/condition", $val);
					}

					if($bInclude) {

						$aDebug[] = array(
							$sParent . " 1- " . $sPath,
							"subxml" => array()
						);
						$iNewKey = count($aDebug) - 1;

						$aXml = $this->_getXml(
							//t3lib_div::getFileAbsFileName($sPath),
							$this->toServerPath($sPath),
							TRUE	// subXml, adds virtualroot for parsing
						);

						if(array_key_exists("xpath", $val)) {

							if($val["xpath"]{0} === ".") {
								$sXPath = $this->absolutizeXPath($val["xpath"], $sParent);
							} else {
								$sXPath = $val["xpath"];
							}

							$aXml = $this->xPath(
								"XPATH:" . $sXPath,
								$aXml,
								TRUE	// breakable
							);

							if($aXml === AMEOSFORMIDABLE_XPATH_FAILED) {
								$this->mayday("<b>XPATH:" . $sXPath . "</b> is not valid, or matched nothing.<br />XPATH breaked on: <b>" . $this->sLastXPathError . "</b>");
							}

							$aTemp = $this->array_add(
								$this->_insertSubXml(
									$aXml,
									$aDebug[$iNewKey]["subxml"],
									$aHeaders,
									$sParent . "/" . $key
								),
								$aTemp
							);

						} else {

							// evaluating if XML provides /head and /body
							if($this->_navConf("/formidable/head", $aXml) || $this->_navConf("/formidable/body", $aXml)) {
								if($aHead = $this->_navConf("/formidable/head", $aXml)) {
									$aHeaders[] = $aHead;
								}

								if($aBody = $this->_navConf("/formidable/body", $aXml)) {
									$aTemp = $this->array_add(
										$this->_insertSubXml(
											$aBody,
											$aDebug[$iNewKey]["subxml"],
											$aHeaders,
											$sParent . "/" . $key
										),
										$aTemp
									);
								}
							} else {
								$aTemp = $this->array_add(
									$this->_insertSubXml(
										$aXml,
										$aDebug[$iNewKey]["subxml"],
										$aHeaders,
										$sParent . "/" . $key
									),
									$aTemp
								);
							}
						}

						if(empty($aDebug[$iNewKey]["subxml"])) {
							unset($aDebug[$iNewKey]["subxml"]);
						}
					}
				} else {

					$aInsert = $this->_insertSubXml(
						$val,
						$aDebug,
						$aHeaders,
						$sParent . "/" . $key
					);

					if(array_key_exists($key, $aTemp)) {

						// reindexing the xml array for correct merging
						$counter = 0;
						while(array_key_exists($key . "-" . $counter, $aTemp)) {
							$counter++;
						}

						$aTemp[$key . "-" . $counter] = $aInsert;
					} else {
						$aTemp[$key] = $aInsert;
					}
				}
			} else {

				if($key{0} === "i" && t3lib_div::isFirstPartOfStr($key, "includexml")) {

					$aDebug[] = array(
						$sParent => $val,
						"subxml" => array()
					);

					$iNewKey = count($aDebug) - 1;

					$aXml = $this->_getXml(
						t3lib_div::getFileAbsFileName($val),
						TRUE	// subXml, adds virtualroot for parsing
					);

					// evaluating if XML provides /head and /body
					if($this->_navConf("/formidable/head", $aXml) || $this->_navConf("/formidable/body", $aXml)) {
						if($aHead = $this->_navConf("/formidable/head", $aXml)) {
							$aHeaders[] = $aHead;
						}

						if($aBody = $this->_navConf("/formidable/body", $aXml)) {
							$aTemp = $this->array_add(
								$this->_insertSubXml(
									$aBody,
									$aDebug[$iNewKey]["subxml"],
									$aHeaders,
									$sParent . "/" . $key
								),
								$aTemp
							);
						}
					} else {
						$aTemp = $this->array_add(
							$this->_insertSubXml(
								$aXml,
								$aDebug[$iNewKey]["subxml"],
								$aHeaders,
								$sParent . "/" . $key
							),
							$aTemp
						);
					}

					if(empty($aDebug[$iNewKey]["subxml"])) {
						unset($aDebug[$iNewKey]["subxml"]);
					}

				} else {
					$aTemp[$key] = $val;
				}
			}
		}

		return $aTemp;
	}

	/**
	 * Inserts conf declared by includets
	 *
	 * @param	array		$aConf: array of conf to process
	 * @param	array		$aTemp: optional; internal use
	 * @return	array		processed conf array
	 */
	function _insertSubTS($aConf, $aTemp = array()) {

		reset($aConf);
		while(list($key, $val) = each($aConf)) {

			if(is_array($val)) {

				if($key{0} === "i" && t3lib_div::isFirstPartOfStr($key, "includets")) {

					if(array_key_exists("path", $val)) {

						$aTs = $this->_getTS($val);

						$aTemp = $this->array_add(
							$this->_insertSubTS($aTs),
							$aTemp
						);

					} else {
						/* nothing ;) */
					}

				} else {
					$aTemp[$key] = $this->_insertSubTS($val);
				}
			}
			else {

				if($key{0} === "i" && t3lib_div::isFirstPartOfStr($key, "includets")) {

					$aTs = $this->_getTS($val);

					$aTemp = $this->array_add(
						$aTs,
						$aTemp
					);

				} else {
					$aTemp[$key] = $val;
				}
			}
		}

		return $aTemp;
	}

	/**
	 * Utility function for _insertSubTS
	 *
	 * @param	string		$sTSPath: ts path to get
	 * @return	mixed		ts conf
	 */
	function _getTS($sTSPath) {

		return $this->_navConf(
			$sTSPath,
			$this->_removeDots(
				$this->_oParent->conf
			)
		);
	}

	function absolutizeXPath($sRelXPath, $sBaseAbsPath) {
		$aCurPath = explode("/", substr($sBaseAbsPath, 1));
		$aRelPath = explode("/", $sRelXPath);

		reset($aRelPath);
		while(list(, $sSegment) = each($aRelPath)) {
			if($sSegment == "..") {
				array_pop($aCurPath);
			} else {
				$aCurPath[] = $sSegment;
			}
		}

		return "/" . implode("/", $aCurPath);
	}

	/**
	 * Obsolete method
	 *
	 * @param	array		$aXml: ...
	 * @param	array		$aDynaXml: ...
	 * @return	array		...
	 */
	function _substituteDynaXml($aXml, $aDynaXml) {
		$this->mayday("Method tx_ameosformidable->_substituteDynaXml() is obsolete.");
	}

	/**
	 * Removes conf-sections emptied by modifiers, if any
	 *
	 * @param	array		$aConf: array of conf to refine
	 * @return	array		processed conf array
	 */
	function _deleteEmpties($aConf) {

		reset($aConf);
		while(list($sKey, $mValue) = each($aConf)) {
			if(is_array($aConf[$sKey])) {
				if(array_key_exists("empty", $aConf[$sKey])) {
					unset($aConf[$sKey]);
				} else {
					$aConf[$sKey] = $this->_deleteEmpties($aConf[$sKey]);
				}
			}
		}

		reset($aConf);
		return $aConf;
	}

	/**
	 * Utility method for _applyModifiers()
	 *
	 * @param	array		$aSubConf
	 * @return	array
	 */
	function _applyLocalModifiers($aSubConf) {

		reset($aSubConf);
		if(($aModifiers = $this->_navConf("/modifiers", $aSubConf)) !== FALSE) {

			reset($aModifiers);
			while(list($sModKey, $aModifier) = each($aModifiers)) {

				if($this->_matchConditions($aModifier)) {

					$aSubConf =
						t3lib_div::array_merge_recursive_overrule(
							$aSubConf,
							$aSubConf["modifiers"][$sModKey]["modification"]
						);
				}
			}
		}

		$aSubConf = $this->_deleteEmpties($aSubConf);
		reset($aSubConf);
		return $aSubConf;
	}

	/**
	 * Applies declared modifiers, if any
	 *
	 * @param	array		$aConf: conf to process
	 * @return	array		processed conf
	 */
	function _applyModifiers($aConf) {

		reset($aConf);
		while(list($sKey, $mValue) = each($aConf)) {

			if(is_array($aConf[$sKey])) {
				if($sKey == "modifiers") {
					$aConf[$sKey] = $this->_applyModifiers($aConf[$sKey]);
					$aConf = $this->_applyLocalModifiers($aConf);
					unset($aConf[$sKey]);
				} else {
					$aConf[$sKey] = $this->_applyModifiers($aConf[$sKey]);
				}
			}
		}

		reset($aConf);
		return $aConf;
	}

	/**
	 * Forces datahandler to edit the given uid
	 *
	 * @param	int		$iForcedEntryId: uid to edit
	 * @return	void
	 */
	function _forceEntryId($iForcedEntryId = FALSE) {
		$this->iForcedEntryId = $iForcedEntryId;
	}

	function forceEntryId($iForcedEntryId = FALSE) {
		return $this->_forceEntryId($iForcedEntryId);
	}

	/**
	 * Removes dots in typoscript configuration arrays
	 *
	 * @param	array		$aData: typoscript conf
	 * @param	array		$aTemp: internal use
	 * @param	string		$sParentKey: key in conf currently processed
	 * @return	array		conf without TS dotted notation
	 */
	function _removeDots($aData, $aTemp = FALSE, $sParentKey = FALSE) {

		if($aTemp === FALSE) {
			$aTemp = array();
		}

		while(list($key, $val) = each($aData)) {
			if(is_array($val)) {
				if($sParentKey === "userobj." && $key === "cobj.") {
					$aTemp["cobj"] = $aData["cobj"];
					$aTemp["cobj."] = $aData["cobj."];
				} else {
					$aTemp[substr($key, 0, -1)] = $this->_removeDots($val, FALSE, $key);
				}
			} else {
				$aTemp[$key] = $val;
			}
		}

		return $aTemp;
	}

	/**
	 * Add dots to an array to have a typoscript-like structure
	 *
	 * @param	array		$aData: plain conf
	 * @param	array		$aTemp
	 * @return	array		typoscript dotted conf
	 */
	function _addDots($aData, $aTemp = FALSE) {

		if($aTemp === FALSE) {
			$aTemp = array();
		}

		while(list($key, $val) = each($aData)) {

			if(is_array($val)) {
				$aTemp[$key."."] = $this->_addDots($val);
			}
			else {
				$aTemp[$key] = $val;
			}
		}

		return $aTemp;
	}

	function relativizeName($sOurs, $sTheirs) {
		if(t3lib_div::isFirstPartOfStr($sOurs, $sTheirs)) {
			return substr($sOurs, strlen($sTheirs) + 1);
		}

		return $sOurs;
	}

	/**
	 * Returns the current version of formidable running
	 *
	 * @return	string		current formidable version number
	 */
	function _getVersion() {
		$_EXTKEY = "ameos_formidable";
		include(PATH_formidable . "ext_emconf.php");
		return $EM_CONF[$_EXTKEY]["version"];
	}

	/**
	 * Initializes the declared datahandler
	 *
	 * @param	int		$iForcedEntryId: optional; uid to edit, if any
	 * @return	void
	 */
	function _initDataHandler($iForcedEntryId = FALSE) {

		if(($aConfDataHandler = $this->_navConf($this->sXpathToControl . "datahandler/")) !== FALSE) {
			$this->oDataHandler =& $this->_makeDataHandler($aConfDataHandler);
		} else {
			$this->oDataHandler =& $this->_makeDefaultDataHandler();
		}

		if($iForcedEntryId === FALSE) {
			$entryId	= $this->oDataHandler->_currentEntryId();
			$forcedId	= FALSE;
		}
		else {
			$entryId	= $iForcedEntryId;
			$forcedId	= TRUE;
		}

		if($entryId !== FALSE){
			$this->oDataHandler->entryId		= $entryId;
			$this->oDataHandler->forcedId		= $forcedId;
		}

		$this->oDataHandler->_initCols();
		/*$this->oDataHandler->_getStoredData();			// initialization

		$this->oDataHandler->_getFormData();			// initialization

		/*$this->oDataHandler->__aFormData = $this->oDataHandler->_processBeforeRender(
			$this->oDataHandler->__aFormData
		);

		$this->oDataHandler->_getFormDataManaged();		// initialization
		*/

		$this->oDataHandler->refreshAllData();

		$this->_debug($this->oDataHandler->__aStoredData, "oDataHandler->__aStoredData initialized with these values");
		$this->_debug($this->oDataHandler->__aFormData, "oDataHandler->__aFormData initialized with these values");
		$this->_debug($this->oDataHandler->__aFormDataManaged, "oDataHandler->__aFormDataManaged initialized with these values");
	}

	function getDataHandler() {
		return $this->oDataHandler;
	}	
	
	function _makeDefaultDataHandler() {
		return $this->_makeDataHandler(
			array(
				"type" => "STANDARD"
			)
		);
	}

	function _makeDefaultRenderer() {
		return $this->_makeRenderer(
			array(
				"type" => "STANDARD"
			)
		);
	}

	/**
	 * Initializes the declared renderer
	 *
	 * @return	void
	 */
	function _initRenderer() {

		if(($aConfRenderer = $this->_navConf($this->sXpathToControl . "renderer/")) !== FALSE) {
			$this->oRenderer =& $this->_makeRenderer($aConfRenderer);
		} else {
			$this->_makeDefaultRenderer();
		}
	}

	/**
	 * Initializes the declared datasources
	 *
	 * @return	void
	 */
	function _initDataSources() {

		$this->_makeDataSources(
			$this->_navConf($this->sXpathToControl . "datasources/"),
			$this->sXpathToControl . "datasources/"
		);

		$this->_makeDataSources(
			$this->_navConf($this->sXpathToControl),
			$this->sXpathToControl
		);
	}

	/**
	 * Initializes the declared datasources
	 *
	 * @param	array		$aConf: conf as given in /control/datasources/*
	 * @param	string		$sXPath: xpath from where the given conf comes
	 * @return	void		...
	 */
	function _makeDataSources($aConf, $sXPath) {

		if(is_array($aConf)) {

			reset($aConf);
			while(list($sElementName, ) = each($aConf)) {

				if($sElementName{0} === "d" && t3lib_div::isFirstPartOfStr($sElementName, "datasource") && !t3lib_div::isFirstPartOfStr($sElementName, "datasources")) {

					$aElement =& $aConf[$sElementName];

					$this->aODataSources[trim($aElement["name"])] = $this->_makeDataSource(
						$aElement,
						$sXPath . $sElementName
					);
				}
			}
		}
	}

	/**
	 * Initializes the declared Renderlets
	 *
	 * @return	void
	 */
	function _initRenderlets() {

		$this->_makeRenderlets(
			$this->_navConf($this->sXpathToElements),
			$this->sXpathToElements,
			$bChilds = FALSE,
			$this	// not used, but required as passing params by ref is not possible with default param value
		);

		$aRdts = array_keys($this->aORenderlets);
		reset($aRdts);
		while(list(, $sAbsName) = each($aRdts)) {
			$this->aORenderlets[$sAbsName]->initDependancies();
		}
	}

	/**
	 * Initializes the declared Renderlets
	 *
	 * @param	array		$aConf: array of conf; usually /renderlets/*
	 * @param	string		$sXPath: xpath from where the given conf comes
	 * @param	boolean		$bChilds: TRUE if initializing childs, FALSE if not
	 * @param	boolean		$bOverWrite: if FALSE, two renderlets declared with the same name will trigger a mayday
	 * @return	array		array of references to built renderlets
	 */
	function _makeRenderlets($aConf, $sXPath, $bChilds, &$oChildParent, $bOverWrite = FALSE) {

		$aRdtRefs = array();

		if(is_array($aConf)) {

			reset($aConf);
			while(list($sElementName,) = each($aConf)) {

				if($sElementName{0} === "r" && t3lib_div::isFirstPartOfStr($sElementName, "renderlet")) {

					#$aElement =& $aConf[$sElementName];

					if(array_key_exists("name", $aConf[$sElementName]) && (trim($aConf[$sElementName]["name"]) != "")) {
						$sName = trim($aConf[$sElementName]["name"]);
						$bAnonymous = FALSE;
					} else {
						$sName = $this->_getAnonymousName($aConf[$sElementName]);
						//$this->_aConf["elements"][$sElementName]["name"] = $sName;		# might be buggy in case of includeXml where elementname can be renderlet-X, whereas it already exists in the $this->_aConf global renderlet array
						$aConf[$sElementName]["name"] = $sName;
						$bAnonymous = TRUE;
					}

					if(!$bAnonymous && !$bOverWrite && array_key_exists($sName, $this->aORenderlets)) {
						$this->mayday("Two (or more) renderlets are using the same name '<b>" . $sName . "</b>' on this form ('<b>" . $this->formid . "</b>') - cannot continue<br /><h2>Inclusions:</h2>" . $this->_viewMixed($this->aTempDebug["aIncHierarchy"]));
					}

					//if(($bChilds === FALSE) || $this->defaultTrue("/process", $aElement)) {
					//if($this->defaultTrue("/process", $aElement)) {

						$oRdt =& $this->_makeRenderlet(
							$aConf[$sElementName],
							$sXPath . $sElementName . "/",
							$bChilds,
							$oChildParent,
							$bAnonymous,
							$sNamePrefix
						);

						$sAbsName = $oRdt->getAbsName();
						$sName = $oRdt->getName();

						$this->aORenderlets[$sAbsName] =& $oRdt;
						unset($oRdt);

						// brothers-childs are stored without prefixing, of course
						$aRdtRefs[$sName] =& $this->aORenderlets[$sAbsName];
/*						if($sDataBridgeAbsName !== FALSE) {
							$this->aORenderlets[$sAbsName]->setDataBridge($sDataBridgeAbsName);
						}*/
					//}
				}
			}
		}

		#debug(array_keys($this->aORenderlets));

		return $aRdtRefs;
	}
	/**
	 * Loads the internal _aConf configuration array from the XML file
	 * IMPORTANT NOTE : the root /formidable is deleted, so all pathes shouldn't start with /formidable
	 *
	 * @return	void
	 */
	function _loadXmlConf() {

		$this->_aConf = $this->_getXml($this->_xmlPath);
		$this->_aConf = $this->_aConf["formidable"];	// the root is deleted

		$this->sXmlVersion = $this->_navConf("/version", $this->_aConf);
		if(($this->sXmlMinVersion = $this->_navConf("/minversion", $this->_aConf)) !== FALSE) {
			if(t3lib_div::int_from_ver($this->sApiVersion) < t3lib_div::int_from_ver($this->sXmlMinVersion)) {
				$this->mayday("The given XML requires a version of Formidable (<b>" . $this->sXmlMinVersion . "</b> or above) more recent than the one installed (<b>" . $this->sApiVersion . "</b>).");
			}
		}

		if(($this->sXmlMaxVersion = $this->_navConf("/maxversion", $this->_aConf)) !== FALSE) {
			if(t3lib_div::int_from_ver($this->sApiVersion) > t3lib_div::int_from_ver($this->sXmlMaxVersion)) {
				$this->mayday("The given XML requires a version of Formidable (<b>" . $this->sXmlMaxVersion . "</b> maximum) older than the one installed (<b>" . $this->sApiVersion . "</b>).");
			}
		}

	}

	/**
	 * Generates an anonymous name for a renderlet
	 *
	 * @param	array		$aElement: conf of the renderlet
	 * @return	string		anonymous name generated
	 */
	function _getAnonymousName($aElement) {
		return "anonymous_" . $this->_getSafeLock(
			serialize($aElement)
		);
	}

	/**
	 * Obsolete
	 *
	 * @param	[type]		$sPath: ...
	 * @return	[type]		...
	 */
	function _getMeta($sPath) {
		$this->mayday("Method tx_ameosformidable->_getMeta() is obsolete.");
	}

	/**
	 * Binary-reads a file
	 *
	 * @param	string		$sPath: absolute server path to file
	 * @return	string		file contents
	 */
	function file_readBin($sPath) {
		$sData = "";
		$rFile = fopen($sPath, "rb");
		while(!feof($rFile)) {
			$sData .= fread($rFile, 1024);
		}
		fclose($rFile);

		return $sData;
	}

	/**
	 * Binary-writes a file
	 *
	 * @param	string		$sPath: absolute server path to file
	 * @param	string		$sData: file contents
	 * @param	boolean		$bUTF8: add UTF8-BOM or not ?
	 * @return	void
	 */
	function file_writeBin($sPath, $sData, $bUTF8 = TRUE) {
		$rFile=fopen($sPath, "wb");
		if($bUTF8 === TRUE) {
			fputs($rFile, "\xEF\xBB\xBF" . $sData);
		} else {
			fputs($rFile, $sData);
		}
		fclose($rFile);
	}

	function _getXmlPlain($sPath) {
		//return self::_getXml($sPath, FALSE, TRUE);
		return $this->_getXml($sPath, FALSE, TRUE);
	}

	/**
	 * Reads and parse an xml file, and returns an array of XML
	 * Fresh or cached data, depending on $this->conf["cache."]["enabled"]
	 *
	 * @param	string		$sPath: abs server path to xml file
	 * @param	boolean		$isSubXml
	 * @return	array		xml data
	 */
	function _getXml($sPath, $isSubXml = FALSE, $bPlain = FALSE) {

		if(!file_exists($sPath)) {
			if($isSubXml === FALSE) {
				$this->smartMayday_XmlFile($sPath);
			} else {
				$this->mayday("FORMIDABLE CORE - The given XML file path (<b>'" . $sPath . "'</b>) doesn't exists.");
			}
		} elseif(is_dir($sPath)) {
			$this->mayday("FORMIDABLE CORE - The given XML file path (<b>'" . $sPath . "'</b>) is a directory, and should be a file.");
		} elseif(!is_readable($sPath)) {
			$this->mayday("FORMIDABLE CORE - The given XML file path (<b>'" . $sPath . "'</b>) exists but is not readable.");
		} else {

			$sHash = md5($sPath);

			if(array_key_exists($sHash, $this->_aSubXmlCache)) {
				return $this->_aSubXmlCache[$sHash];
			} else {

				$aConf = array();

				if($this->conf["cache."]["enabled"] == 1) {
					$sProtection = "<?php die('Formidable - Cache protected'); ?><!--FORMIDABLE_CACHE-->";

					//debug(stat($sPath));
					$sHash = md5($sPath . "-" . @filemtime($sPath) . "-" . $this->sApiVersion);
					$sFile = "formidablexmlcache_" . $sHash . ".php";
					$sCacheDir = "ameos_formidable/cache/";
					$sCachePath = PATH_site . "typo3temp/" . $sCacheDir . $sFile;

					if(file_exists($sCachePath)) {
						$aConf = unserialize(
							base64_decode(
								substr($this->file_readBin($sCachePath), strlen($sProtection) + 3)		/* 3 is size of UTF8-header, aka BOM or Byte Order Mark */
							)
						);
						if(is_array($aConf)) {
							reset($aConf);
						}
					}
				}

				if(empty($aConf)) {

					$sXmlData = $this->file_readBin($sPath);
					if(trim($sXmlData) === "") {
						$this->smartMayday_XmlFile($sPath, "FORMIDABLE CORE - The given XML file path (<b>'" . $sPath . "'</b>) exists but is empty.");
					}

					$aMatches = array();
					preg_match("/^<\?xml(.*)\?>/", $sXmlData, $aMatches);

					// Check result
					if(!empty($aMatches)) {
						$sXmlProlog = $aMatches[0];
						$sXmlData = preg_replace("/^<\?xml(.*)\?>/", "", $sXmlData);

					} else {
						$sXmlProlog = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>';
					}

					if($isSubXml) {
						$sXmlData = $sXmlProlog . "\n" . "<phparray>" . $sXmlData . "</phparray>";
					} else {
						$sXmlData = $sXmlProlog . "\n" . $sXmlData;
					}

					if($bPlain === FALSE) {
						$aConf = $this->div_xml2array($sXmlData);
					} else {
						$aConf = $this->div_xml2array_plain($sXmlData);
					}


					if(is_array($aConf)) {

						if($isSubXml && array_key_exists("phparray", $aConf) && is_array($aConf["phparray"])) {
							$aConf = $aConf["phparray"];
						}

						reset($aConf);

					} else {
						$this->mayday("FORMIDABLE CORE - The given XML file (<b>'" . $sPath . "'</b>) isn't well-formed XML<br>Parser says : <b>" . $aConf . "</b>");
					}

					if($this->conf["cache."]["enabled"] == 1) {

						if(!@is_dir(PATH_site . "typo3temp/" . $sCacheDir)) {
							if(function_exists("t3lib_div::mkdir_deep")) {
								t3lib_div::mkdir_deep(PATH_site . "typo3temp/", $sCacheDir);
							} else {
								$this->div_mkdir_deep(PATH_site . "typo3temp/", $sCacheDir);
							}
						}

						$this->file_writeBin(
							$sCachePath,
							$sProtection . base64_encode(serialize($aConf)),
							TRUE	// add UTF-8 header
						);
					}
				}

				reset($aConf);
				$this->_aSubXmlCache[$sHash] = $aConf;
				return $aConf;
			}
		}
	}

	/**
	 * Includes and makes instance of the desired object
	 *
	 * @param	string		$sInternalKey: name of the object; something like rdt_text, dh_db, ...
	 * @param	string		$sObjectType: type of the object; something like renderlets, datahandlers, ...
	 * @return	object		Built object or FALSE if failed
	 */
	function _loadObject($sInternalKey, $sObjectType) {

		global $TYPO3_CONF_VARS;	// needed to allow XCLASS on renderlets
										// http://bugs.typo3.org/view.php?id=9812

		$aDeclaredObjects =& $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["declaredobjects"][$sObjectType];

		if(array_key_exists($sInternalKey, $aDeclaredObjects)) {

			$aLoadedObjects =& $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"][$sObjectType];

			if(!array_key_exists($sInternalKey, $aLoadedObjects)) {
				$aTemp = array(
					"EXTKEY" => $aDeclaredObjects[$sInternalKey]["key"],
					"TYPE" => $sInternalKey,
					"BASE" => $aDeclaredObjects[$sInternalKey]["base"],
					"OBJECT" => $sObjectType,
				);

				$aTemp["PATH"]			= tx_ameosformidable::_getExtPath($aTemp);
				$aTemp["RELPATH"]		= tx_ameosformidable::_getExtRelPath($aTemp);
				$aTemp["CLASS"]			= "tx_" . str_replace("_", "", $aTemp["EXTKEY"]);
				$aTemp["CLASSPATH"]		= $aTemp["PATH"]  . "api/class." . $aTemp["CLASS"] . ".php";

				if($aTemp["BASE"] === TRUE && file_exists($aTemp["PATH"]  . "ext_localconf.php")) {
					$aTemp["LOCALCONFPATH"]	= $aTemp["PATH"]  . "ext_localconf.php";
				}

				$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"][$sObjectType][$aTemp["TYPE"]] = $aTemp;

				if(file_exists($aTemp["CLASSPATH"])) {
					require_once($aTemp["CLASSPATH"]);
				}

				if(file_exists($aTemp["LOCALCONFPATH"])) {
					require_once($aTemp["LOCALCONFPATH"]);
				}

				$aLoadedObjects[$sInternalKey] = $aTemp;
			}

			if(tx_ameosformidable::__getEnvExecMode() !== "EID") {
				if(isset($this)) {	// allow static calls
					$this->__aRunningObjects[$sObjectType . "::" . $sInternalKey] = array(
						"internalkey" => $sInternalKey,
						"objecttype" => $sObjectType,
					);
				}

				// calls tx_myrdtclass::loaded();
					// params are not passed by ref with call_user_func, so have to pass an array with &
				call_user_func(array($aLoadedObjects[$sInternalKey]["CLASS"], 'loaded'), array("form" => &$this));
			}

			return $aLoadedObjects[$sInternalKey];
		}

		return FALSE;
	}

	/**
	 * Loads the internal collection of available datasources
	 *
	 * @return	void
	 */
	function _loadDeclaredDataSources() {
		$this->_aDataSources = $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["datasources"];
		reset($this->_aDataSources);
	}

	/**
	 * Loads the internal collection of available datahandlers
	 *
	 * @return	void
	 */
	function _loadDeclaredDataHandlers() {
		$this->_aDataHandlers = $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["datahandlers"];
		reset($this->_aDataHandlers);
	}

	/**
	 * Loads the internal collection of available renderlets
	 *
	 * @return	void
	 */
	function _loadDeclaredRenderlets() {
		$this->_aRenderlets = $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["renderlets"];
		reset($this->_aRenderlets);
	}

	/**
	 * Loads the internal collection of available renderers
	 *
	 * @return	void
	 */
	function _loadDeclaredRenderers() {
		$this->_aRenderers = $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["renderers"];
		reset($this->_aRenderers);
	}

	/**
	 * Loads the internal collection of available validators
	 *
	 * @return	void
	 */
	function _loadDeclaredValidators() {
		$this->_aValidators = $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["validators"];
		reset($this->_aValidators);
	}

	/**
	 * Loads the internal collection of available actionlets
	 *
	 * @return	void
	 */
	function _loadDeclaredActionlets() {
		$this->_aActionlets = $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["actionlets"];
		reset($this->_aActionlets);
	}

	function isTemplateMethod($sToken) {
		$aRes = array();
		$bMatch = (preg_match("@^[\w]+\((.|\n)*\)$@", $sToken, $aRes) !== 0);

		return $bMatch;
	}

	function templateDataAsString($mData) {
		if(is_array($mData)) {
			if(array_key_exists("__compiled", $mData)) {
				$mData = $mData["__compiled"];
			} else {
				$mData = "";
			}
		}

		return $mData;
	}

	function parseTemplateMethodArgs($sArgs) {
		$aParams = array();
		$sArgs = trim($sArgs);
		if($sArgs !== "") {
			$aArgs = t3lib_div::trimExplode(",", $sArgs);
			reset($aArgs);
			while(list(, $sArg) = each($aArgs)) {
				$sTrimArg = trim($sArg);
				if($sTrimArg{0} === '"' && $sTrimArg{(strlen($sTrimArg)-1)} === '"') {
					$sTrimArg = str_replace('\"', '"', $sTrimArg);
				}

				if($sTrimArg{0} === "'" && $sTrimArg{(strlen($sTrimArg)-1)} === "'") {
					$sTrimArg = str_replace("\'", "'", $sTrimArg);
				}

				if((($sTrimArg{0} === '"' && $sTrimArg{(strlen($sTrimArg)-1)} === '"')) || (($sTrimArg{0} === "'" && $sTrimArg{(strlen($sTrimArg)-1)} === "'"))) {
					$aParams[] = substr($sTrimArg, 1, -1);
				} elseif(is_numeric($sTrimArg)) {
					$aParams[] = ($sTrimArg + 0);
				} else {
					$aParams[] = tx_ameosformidable::resolveForTemplate($sTrimArg);
				}
			}
		}

		reset($aParams);
		return $aParams;
	}

	// $mData is the data that comes from the preceding method in the chained execution
		// ex: "hello".concat(", world") => "hello" is $mData, and ", world" the arguments

	function executeTemplateMethod($sInterpreter, $mData, $sCall) {
		$sMethod = substr($sCall, 0, strpos($sCall, "("));
		$sArgs = trim(substr(strstr($sCall, "("), 1, -1));

		$sClassPath = "class." . $sInterpreter . ".php";

		require_once(PATH_formidable . "api/" . $sClassPath);
		$oMethods = t3lib_div::makeInstance("formidable_" . $sInterpreter);
		$oMethods->_init($this);

		return $oMethods->process(
			$sMethod,
			$mData,
			$sArgs
		);
	}

	function &getRdtForTemplateMethod($mData) {
		// returns the renderlet object corresponding to what's asked in the template
			// if none corresponds, then FALSE is returned

		if($this->isRenderlet($mData)) {
			return $mData;
		}

		if(is_array($mData) && array_key_exists("htmlid.", $mData) && array_key_exists("withoutformid", $mData["htmlid."])) {
			$sHtmlId = $mData["htmlid."]["withoutformid"];
			if(array_key_exists($sHtmlId, $this->aORenderlets)) {
				return $this->aORenderlets[$sHtmlId];
			}
		}

		return FALSE;
	}

	function parseForTemplate($sPath) {

		$sPath = trim($sPath);
		//debug($sPath, "parseForTemplate");

		$iOpened = 0;
		$bInString = FALSE;

		$iCurrent = 0;
		$aCurrent = array(
			0 => array(
				"expr" => "",
				"rec" => FALSE,
				"args" => FALSE
			)
		);

		$sArgs = "";

		//$aStr = tx_ameosformidable::str_split($sPath, 1);
		//reset($aStr);
		$sLastCar = "";
		$iNbCars = strlen($sPath);
		//while(list(, $sCar) = each($aStr)) {
		for($iCar = 0; $iCar < $iNbCars; $iCar++) {
			$sCar = $sPath{$iCar};

			if(!$bInString && $sCar === ".") {
				if($iOpened === 0) {
					$iCurrent++;
					$aCurrent[$iCurrent] = array(
						"expr" => "",
						"rec" => FALSE,
						"args" => FALSE
					);
					$sCar = "";
				}
			}

			if($sCar === '"' && $sLastCar !== "\\") {
				$bInString = !$bInString;
			} elseif(!$bInString && $sCar === "(") {
				$iOpened++;
			} elseif(!$bInString && $sCar === ")") {
				$iOpened--;
				$sTrimArg = trim($aCurrent[$iCurrent]["args"]);
				$aCurrent[$iCurrent]["args"] = $sTrimArg;
				if($sTrimArg{0} !== '"' && strpos($sTrimArg, "(") !== FALSE) {
					$aCurrent[$iCurrent]["rec"] = TRUE;
				} else {
					$aCurrent[$iCurrent]["rec"] = FALSE;
				}
			}

			if($iOpened !== 0) {
				if($sCar === "(" && $aCurrent[$iCurrent]["args"] === FALSE) {
					$aCurrent[$iCurrent]["args"] = "";
				} else {
				//	debug($sCar, "car:" . $iOpened);
					$aCurrent[$iCurrent]["args"] .= $sCar;
				}
			} else {

				if($bInString || ($sCar !== "(" && $sCar !== ")" && $sCar !== " " && $sCar !== "\n" && $sCar !== "\r" && $sCar !== "\t")) {
					if(!$bInString || $sCar !== "\\") {
						$aCurrent[$iCurrent]["expr"] .= $sCar;
					}
				}
			}
			$sLastCar = $sCar;
		}

		return $aCurrent;
	}

	function resolveForInlineConf($sPath, $oRdt = FALSE) {
		return tx_ameosformidable::_resolveScripting(
			"inlineconfmethods",
			$sPath,
			$oRdt
		);
	}

	function resolveForMajixParams($sPath, $oRdt = FALSE) {
		return tx_ameosformidable::_resolveScripting(
			"majixmethods",
			$sPath,
			$oRdt
		);
	}

	function resolveForTemplate($sPath, $aConf = FALSE, $mStackedValue = array()) {

		if($aConf === FALSE && is_object($this)) {
			$aConf = $this->currentTemplateMarkers();
		}

		return tx_ameosformidable::_resolveScripting(
			"templatemethods",
			$sPath,
			$aConf,
			$mStackedValue
		);
	}

	function _resolveScripting($sInterpreter, $sPath, $aConf = FALSE, $mStackedValue = array()) {

		$aRes = tx_ameosformidable::parseForTemplate($sPath);
		$aValue = $aConf;

		reset($aRes);
		while(list($i, $aExp) = each($aRes)) {

			if($aValue === AMEOSFORMIDABLE_LEXER_FAILED || $aValue === AMEOSFORMIDABLE_LEXER_BREAKED) {
				// throwing exception to notify that lexer has failed or has breaked
				return $aValue;
			}

			$sTrimExpr = trim($aExp["expr"]);

			if($aExp["rec"] === TRUE) {
				if($sTrimExpr{0} == '"' && $sTrimExpr{(strlen($sTrimExpr) - 1)} == '"') {
					$aValue = substr($sTrimExpr, 1, -1);
				} else {

					$aBeforeValue = $aValue;

					$aValue = tx_ameosformidable::_resolveScripting(
						$sInterpreter,
						$aExp["args"],
						$aConf,
						$aValue
					);

					if(!is_array($aValue)) {
						$sExecString = $aExp["expr"] . "(\"" . $aValue . "\")";
					} else {
						$sExecString = $aExp["expr"];
					}

					$aValue = tx_ameosformidable::_resolveScripting(
						$sInterpreter,
						$sExecString,
						$aBeforeValue
					);
				}

				$sDebug = $aExp["args"];
			} else {
				if($sTrimExpr{0} == '"' && $sTrimExpr{(strlen($sTrimExpr) - 1)} == '"') {
					$aValue = substr($sTrimExpr, 1, -1);
				} else {

					$sExecString = $aExp["expr"];
					if($aExp["args"] !== FALSE) {
						$sExecString .= "(" . trim($aExp["args"]) . ")";
					}

					if(array_key_exists(($i+1), $aRes)) {
						$aNextExp = $aRes[$i+1];
						$sNextExecString = $aNextExp["expr"];
						if($aNextExp["args"] !== FALSE) {
							$sNextExecString .= "(" . trim($aNextExp["args"]) . ")";
						}
					} else {
						$sNextExecString = FALSE;
					}

					$aValue = tx_ameosformidable::_resolveScripting_atomic(
						$sInterpreter,
						$sExecString,
						$aValue,
						$sNextExecString
					);
				}
				$sDebug = $sExecString;
			}
		}

		return $aValue;
	}

	function _resolveScripting_atomic($sInterpreter, $sPath, $aConf = AMEOSFORMIDABLE_LEXER_VOID, $sNextPath = FALSE) {

		if($aConf === AMEOSFORMIDABLE_LEXER_VOID && is_object($this)) {
			$aConf = $this->currentTemplateMarkers();
		}

		if(is_array($aConf)) {
			reset($aConf);
		}

		$curZone = $aConf;

		if(tx_ameosformidable::isTemplateMethod($sPath)) {

			$curZone = tx_ameosformidable::executeTemplateMethod(
				$sInterpreter,
				$curZone,
				$sPath
			);
			if($curZone === AMEOSFORMIDABLE_LEXER_FAILED || $curZone === AMEOSFORMIDABLE_LEXER_BREAKED) {
				return AMEOSFORMIDABLE_LEXER_FAILED;
			}
		} else {

			if(is_array($curZone)) {
				if(array_key_exists($sPath, $curZone) && array_key_exists($sPath . ".", $curZone)) {

					// ambiguous case: both "token" and "token." exists in the data array
					/*
						algo:
							if there's a next token asked after this one
								if "nexttoken" or "nexttoken." exists in "token."
									current zone become "token."
								else
									current zone become "token"
							else
								current zone become "token"

					*/

					if($sNextPath !== FALSE) {
						if(array_key_exists($sNextPath, $curZone[$sPath . "."]) || array_key_exists($sNextPath . ".", $curZone[$sPath . "."])) {
							$curZone = $curZone[$sPath . "."];
						} else {
							$curZone = $curZone[$sPath];
						}
					} else {
						$curZone = $curZone[$sPath];
					}

				} elseif(array_key_exists($sPath, $curZone) || array_key_exists($sPath . ".", $curZone)) {
					if($sNextPath !== FALSE && array_key_exists($sPath . ".", $curZone) && is_array($curZone[$sPath . "."])) {
						if(array_key_exists($sNextPath, $curZone[$sPath . "."]) || array_key_exists($sNextPath . ".", $curZone[$sPath . "."])) {
							$curZone = $curZone[$sPath . "."];
						} else {
							$curZone = $curZone[$sPath];
						}
					} else {
						$curZone = $curZone[$sPath];
					}
				} elseif(is_string($sPath) && is_numeric($sPath)) {
					return ($sPath + 0);
				} elseif(is_string($sPath) && $this->isTrueVal($sPath)) {
					return TRUE;
				} elseif(is_string($sPath) && $this->isFalseVal($sPath)) {
					return FALSE;
				} else {
					return AMEOSFORMIDABLE_LEXER_FAILED;
				}
			} else {
				return $curZone;
			}
		}

		return $curZone;
	}

	function _navConf($path, $aConf = -1, $sSep = '/', $bEvaluateSmartStrings = TRUE) {

		if($aConf === -1 || !is_array($aConf)) {
			if(is_array($this->_aConf)) {
				reset($this->_aConf);
				$curZone = $this->_aConf;
			} else {
				$curZone = array();
			}
		} else {
			reset($aConf);
			$curZone = $aConf;
		}

		if($path === '/') {
			return $curZone;
		}

		if($path{0} === $sSep)					{ $path = substr($path, 1);}
		$iLen = strlen($path);
		$aPath = explode($sSep, $path);
		$iSize = count($aPath);

		if($path{$iLen - 1} === $sSep)	{
			unset($aPath[$iSize]);
			$iSize--;
		}

		for($i = 0; $i < $iSize; $i++) {

			if(is_array($curZone) && array_key_exists($aPath[$i], $curZone)) {
				$curZone = $curZone[$aPath[$i]];
				if($bEvaluateSmartStrings === TRUE && is_string($curZone)) {
					$curZone = $this->evaluate_smartString($curZone);
				}
			} else {
				return FALSE;
			}
		}

		return $curZone;
	}

	function smart($sString) {
		return $this->evaluate_smartString($sString);
	}

	function evaluate_smartString($sString) {
		if(!is_string($sString)) {
			return $sString;
		}

		$s0 = $sString{0};
		$s1 = $sString{1};

		if($s0 === 'X' && substr($sString, 0, 6) === 'XPATH:') {
			$sString = $this->xPath($sString);
		} elseif($s0 === 'T' && substr($sString, 0, 3) === 'TS:') {
			$sTsPointer = $sString;
			if(($sString = $this->getTS($sString, TRUE)) === AMEOSFORMIDABLE_TS_FAILED) {
				$this->mayday("The typoscript pointer <b>" . $sTsPointer . "</b> evaluation has failed, as the pointed property does not exists within the current Typoscript template");
			}
		} elseif($s0 === 'T' && substr($sString, 0, 4) === 'TCA:') {
			$sString = $this->getTcaVal($sString);
		} elseif($s0 === 'L' && substr($sString, 0, 4) === 'LLL:') {
			$sString = $this->_getLLLabel($sString);
		} elseif($s0 === 'E' && $s1 === 'X' && substr($sString, 0, 8) === 'EXTCONF:') {
			$sString = $this->getExtConfVal($sString);
		} elseif($s0 === 'E' && $s1 === 'X' && substr($sString, 0, 4) === 'EXT:') {
			$sString = $this->evaluate_EXTpathString($sString);
		} elseif($s0 === 'R' && $s1 === 'E' && substr($sString, 0, 4) === 'REL:') {
			$sString = $this->evaluate_EXTpathString($sString);
		}

		#debug($sString, "evaluated");

		return $sString;
	}

	function evaluate_EXTPathString($sString) {

		if($sString{0} === 'E' && $sString{1} === 'X' && substr($sString, 0, 4) === 'EXT:') {
			return t3lib_div::getFileAbsFileName($sString);
		}

		if($sString{0} === 'R' && $sString{1} === 'E' && $sString{2} === 'L' && $sString{3} === ':') {
			return $this->toRelPath(substr($sString, 4));
		}

		return $sString;
	}

	function navDef($sPath, $mDefault, $aConf = -1) {

		if(($aTemp = $this->_navConf($sPath, $aConf)) !== FALSE) {
			return $aTemp;
		}

		return $mDefault;
	}

	function navDeepData($sPath, $aData) {
		return $this->_navConf(
			$sPath,
			$aData,
			"/",
			FALSE	// do not evaluate smart strings, as incoming data may content malicious instructions (as well as harmless LLL: or EXT:)
		);
	}

	function setDeepData($path, &$aConf, $mValue, $bMergeIfArray = FALSE) {

		if(!is_array($aConf)) {
			return FALSE;
		}

		$sSep = "/";
		//debug($aConf, "SETTING DEEP DATA INTO " . $path);

		if($path{0} === $sSep)					{ $path = substr($path, 1);}
		if($path{strlen($path) - 1} === $sSep)	{ $path = substr($path, 0, strlen($path) - 1);}

		$aPath = explode($sSep, $path);
		reset($aPath);
		reset($aConf);
		$curZone =& $aConf;

		$iSize = sizeOf($aPath);
		for($i = 0; $i < $iSize; $i++) {

			if(!is_array($curZone) && ($i !== ($iSize-1))) {
				return FALSE;
			}

			if(is_array($curZone) && !array_key_exists($aPath[$i], $curZone)) {
				$curZone[$aPath[$i]] = array();
			}

			$curZone =& $curZone[$aPath[$i]];

			if($i === ($iSize-1)) {
				$mBackup = $curZone;

				if(is_array($curZone) && is_array($mValue) && $bMergeIfArray === TRUE) {
					// merging arrays
					$curZone = t3lib_div::array_merge_recursive_overrule(
						$curZone,
						$mValue
					);
				} else {
					$curZone=$mValue;
				}

				return $mBackup;
			}
		}

		return FALSE;
	}

	function unsetDeepData($path, &$aConf) {

		if(!is_array($aConf)) {
			return FALSE;
		}

		$sSep = "/";

		if($path{0} === $sSep)					{ $path = substr($path, 1);}
		if($path{strlen($path) - 1} === $sSep)	{ $path = substr($path, 0, strlen($path) - 1);}

		$aPath = explode($sSep, $path);
		reset($aPath);
		reset($aConf);
		$curZone =& $aConf;

		$iSize = sizeOf($aPath);
		for($i = 0; $i < $iSize; $i++) {

			if(!is_array($curZone) && ($i !== ($iSize-1))) {
				return FALSE;
			}

			if(is_array($curZone) && !array_key_exists($aPath[$i], $curZone)) {
				return FALSE;
			}

			if($i === ($iSize-1)) {
				unset($curZone[$aPath[$i]]);
				return TRUE;
			} else {
				$curZone =& $curZone[$aPath[$i]];
			}
		}

		return FALSE;
	}

	function implodePathesForArray($aData) {
		$aPathes = array();
		$this->implodePathesForArray_rec($aData, $aPathes);
		reset($aPathes);
		return $aPathes;
	}

	function implodePathesForArray_rec($aData, &$aPathes, $aSegment = array()) {
		$aKeys = array_keys($aData);
		reset($aKeys);
		while(list(,$sKey) = each($aKeys)) {
			$aTemp = $aSegment;
			$aTemp[] = $sKey;
			if(is_array($aData[$sKey])) {
				$this->implodePathesForArray_rec(
					$aData[$sKey],
					$aPathes,
					$aTemp
				);
			} else {
				$aPathes[] = implode($aTemp, "/");
			}
		}
	}

	/**
	 * Resolves an xpath and returns value pointed by this xpath
	 *
	 * @param	string		$sPath: xpath
	 * @return	mixed
	 */
	function xPath($sPath, $aConf = -1, $bBreakable = FALSE) {

		$this->sLastXPathError = "";

		if(is_string($sPath) && $sPath{0} === "X" && substr($sPath, 0, 6) === "XPATH:") {

			$sPath = $this->_trimSlashes(
				strtolower(substr($sPath, 6))
			);

			if(strpos($sPath, "[")) {
				$aSegments = array();

				if($aConf === -1) {
					$aConf = $this->_aConf;
				}

				$aParts = explode("/", $sPath);
				reset($aParts);
				while(list(, $sPart) = each($aParts)) {
					$aTemp = explode("[", str_replace("]", "", $sPart));
					if(count($aTemp) > 1) {
						// we have to search on a criteria sequence
						$sWhat = $aTemp[0];
						$sTempCrits = $aTemp[1];
						$aTempCrits = t3lib_div::trimExplode(",", $sTempCrits);
						reset($aTempCrits);
						$aCrits = array();
						while(list(, $sTempCrit) = each($aTempCrits)) {
							$aCrit = t3lib_div::trimExplode("=", $sTempCrit);
							$aCrits[$aCrit[0]] = $aCrit[1];
						}

						$aSegments[] = array(
							"what" => $sWhat,
							"crits" => $aCrits,
							"segment" => $sPart,
						);
					} else {
						$aSegments[] = array(
							"what" => $sPart,
							"crits" => FALSE,
							"segment" => $sPart,
						);
					}
				}

				$aPossibles = array(
					0 => $aConf
				);


				reset($aConf);
				while(list($iLevel, $aSegment) = each($aSegments)) {

					$bSegMatch = FALSE;

					$this->sLastXPathError .= "/" . $aSegment["segment"];

					$aNewPossibles = array();
					$aPossKeys = array_keys($aPossibles);
					while(list(, $sPosKey) = each($aPossKeys)) {

						$aKeys = array_keys($aPossibles[$sPosKey]);
						reset($aKeys);
						while(list(, $sKey) = each($aKeys)) {

							if(substr($sKey, 0, strlen($aSegment["what"])) == $aSegment["what"]) {
								$bMatch = TRUE;
								if($aSegment["crits"] !== FALSE) {
									reset($aSegment["crits"]);
									while(list($sProp, $sValue) = each($aSegment["crits"])) {
										$bMatch = $bMatch && (array_key_exists(strtolower($sProp), $aPossibles[$sPosKey][$sKey]) && strtolower($aPossibles[$sPosKey][$sKey][$sProp]) == strtolower($sValue));
									}
								}

								if($bMatch) {
									$bSegMatch = TRUE;
									$aNewPossibles[$sKey] = $aPossibles[$sPosKey][$sKey];
								}
							}
						}
					}

					if($bSegMatch === FALSE && $bBreakable === TRUE) {
						return AMEOSFORMIDABLE_XPATH_FAILED;
					}

					$aPossibles = $aNewPossibles;
				}

				reset($aPossibles);
				return $aPossibles;
			}

			return $this->_navConf($sPath, $aConf);
		}

		return FALSE;
	}

	function getTs($sKey, $bBreakable = FALSE) {
		if($sKey{0} === "T" && $sKey{1} === "S" && $sKey{2} == ":") {
			$sKey = substr($sKey, 3);
		}

		$aSegments = explode("#", str_replace(".", ".#", $sKey));	// adding the trailing dots '.' in the exploded array
		$mCurLevel =& $GLOBALS["TSFE"]->tmpl->setup;
		reset($aSegments);
		while(list(, $sSeg) = each($aSegments)) {
			if(!is_array($mCurLevel) || !array_key_exists($sSeg, $mCurLevel)) {
				if($bBreakable === TRUE) {
					return AMEOSFORMIDABLE_TS_FAILED;
				} else {
					return "";
				}
			}

			$mCurLevel =& $mCurLevel[$sSeg];
		}

		return $mCurLevel;
	}

	function getExtConfVal($sExtConf) {
		if($sExtConf{0} === "E" && $sExtConf{1} === "X" && substr($sExtConf, 0, 8) === 'EXTCONF:') {
			$sExtConf = substr($sExtConf, 8);
		} else {
			$sPath = $sExtConf;
		}

		$sPath = str_replace(".", "/", $sExtConf);
		$sRes = $this->_navConf($sPath, $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]);
		return $sRes;

		//return FALSE;
	}

	function getTcaVal($sAddress) {
		if($sAddress{0} === "T" && $sAddress{1} === "C" && substr($sAddress, 0, 4) === 'TCA:') {

			$aParts = explode(":", $sAddress);
			unset($aParts[0]);

			$sPath = $aParts[1];
			$aPath = explode("/", $sPath);
			$sTable = $aPath[0];

			t3lib_div::loadTCA($sTable);
			return $this->_navConf($sPath, $GLOBALS["TCA"]);
		}

		return FALSE;
	}

	function fetchServerEvents() {
		$aKeys = array_keys($this->aORenderlets);
		reset($aKeys);
		while(list(, $sKey) = each($aKeys)) {
			$this->aORenderlets[$sKey]->fetchServerEvents();
		}
	}


	/**
	 * Alias for _render()
	 *
	 * @return	string
	 */
	function render() {	// alias for _render
		return $this->_render();
	}

	/**
	 * Renders the whole application
	 *
	 * @return	string		Full rendered HTML
	 */
	function _render() {

		if($this->bInited === FALSE) {
			$this->start_tstamp	= t3lib_div::milliseconds();	// because it has not been initialized yet
			$this->mayday("TRIED TO RENDER FORM BEFORE CALLING INIT() !");
		}

		#$this->fetchServerEvents();	// moved just after call to initRenderlets()

		$this->checkPoint(
			array(
				"before-render",
			)
		);

		$this->bRendered = TRUE;

/*		$this->_debug($this->oDataHandler->_P(), "RAW POST RETURN");
		$this->_debug($this->oDataHandler->_getFormData(), "_getFormData");
		$this->_debug($this->oDataHandler->_getFormDataManaged(), "_getFormDataManaged");*/

		$this->oRenderer->renderStyles();

		if($this->oDataHandler->_isSubmitted()) {

			if($this->oDataHandler->_isFullySubmitted()) {

				$this->_debug("", "HANDLING --- FULL --- SUBMIT EVENT");

				// validation of the renderlets
				$this->validateEverything();

				if($this->oDataHandler->_allIsValid()) {
					$this->checkPoint(
						array(
							"after-validation-ok",
						)
					);
				} else {
					$this->checkPoint(
						array(
							"after-validation-nok",
						)
					);
				}

				$this->_filterUnProcessed();
				$this->_includeLibraries();


				// the datahandler is executed
				#$this->processDataBridges(TRUE);
				$sDH = $this->oDataHandler->_doTheMagic(TRUE);

				// Renderlets are rendered
				$aRendered = t3lib_div::array_merge_recursive_overrule(
					$this->_renderElements(),
					$this->aPreRendered
				);

				if(count($this->_aValidationErrors) > 0) {
					$this->_debug($this->_aValidationErrors, "SOME ELEMENTS ARE NOT VALIDATED");
				} else {
					$this->_debug("", "ALL ELEMENTS ARE VALIDATED");
				}

				// the renderer is executed
				$aHtmlBag = $this->oRenderer->_render(
					$aRendered
				);

				$this->checkPoint(
					array(
						"after-render",
					)
				);

				// ACTIONLETS are executed
				if($this->oDataHandler->_allIsValid()) {
					$this->checkPoint(
						array(
							"before-actionlets",
						)
					);

					$this->_executeActionlets(
						$aRendered,
						$aHtmlBag["CONTENT"]
					);

					$this->checkPoint(
						array(
							"after-actionlets",
						)
					);
				}

			} elseif($this->oDataHandler->_isRefreshSubmitted()) {

				$this->_debug("NO VALIDATION REQUIRED", "HANDLING --- REFRESH --- SUBMIT EVENT");

				$this->_filterUnProcessed();
				$this->_includeLibraries();

				// the datahandler is executed
				#$this->processDataBridges(FALSE);
				$sDH = $this->oDataHandler->_doTheMagic(FALSE);

				// Renderlets are rendered
				$aRendered = t3lib_div::array_merge_recursive_overrule(
					$this->_renderElements(),
					$this->aPreRendered
				);

				// the renderer is executed
				$aHtmlBag = $this->oRenderer->_render(
					$aRendered
				);

				$this->checkPoint(
					array(
						"after-render",
					)
				);

			} elseif($this->oDataHandler->_isTestSubmitted()) {

				$this->_debug("VALIDATION REQUIRED ( ONLY )", "HANDLING --- TEST --- SUBMIT EVENT");

				// validation of the renderlets
				$this->validateEverything();

				$this->_filterUnProcessed();
				$this->_includeLibraries();

				// the datahandler is executed
				#$this->processDataBridges(FALSE);
				$sDH = $this->oDataHandler->_doTheMagic(FALSE);

				// Renderlets are rendered
				$aRendered = t3lib_div::array_merge_recursive_overrule(
					$this->_renderElements(),
					$this->aPreRendered
				);

				// the renderer is executed
				$aHtmlBag = $this->oRenderer->_render(
					$aRendered
				);

				$this->checkPoint(
					array(
						"after-render",
					)
				);

			} elseif($this->oDataHandler->_isDraftSubmitted()) {

				$this->_debug("NO VALIDATION REQUIRED", "HANDLING --- DRAFT --- SUBMIT EVENT");

				// validation of the renderlets
				$this->validateEverythingDraft();

				$this->_filterUnProcessed();
				$this->_includeLibraries();

				// the datahandler is executed
				#$this->processDataBridges(TRUE);
				$sDH = $this->oDataHandler->_doTheMagic(TRUE);

				// Renderlets are rendered
				$aRendered = t3lib_div::array_merge_recursive_overrule(
					$this->_renderElements(),
					$this->aPreRendered
				);

				// the renderer is executed
				$aHtmlBag = $this->oRenderer->_render(
					$aRendered
				);

				$this->checkPoint(
					array(
						"after-render",
					)
				);

			} elseif($this->oDataHandler->_isClearSubmitted() || $this->oDataHandler->_isSearchSubmitted()) {

				$this->_debug("NO VALIDATION REQUIRED", "HANDLING --- CLEAR OR SEARCH --- SUBMIT EVENT");

				$this->_filterUnProcessed();
				$this->_includeLibraries();

				// the datahandler is executed
				#$this->processDataBridges(FALSE);
				$sDH = $this->oDataHandler->_doTheMagic(FALSE);

				// Renderlets are rendered
				$aRendered = t3lib_div::array_merge_recursive_overrule(
					$this->_renderElements(),
					$this->aPreRendered
				);

				// the renderer is executed
				$aHtmlBag = $this->oRenderer->_render(
					$aRendered
				);

				$this->checkPoint(
					array(
						"after-render",
					)
				);
			}
		} else {

			$this->_debug("NO VALIDATION REQUIRED", "NO SUBMIT EVENT TO HANDLE");

			$this->_filterUnProcessed();
			$this->_includeLibraries();

			// the datahandler is executed
			#$this->processDataBridges(FALSE);
			$sDH = $this->oDataHandler->_doTheMagic(FALSE);

			// Renderlets are rendered
			$aRendered = t3lib_div::array_merge_recursive_overrule(
				$this->_renderElements(),
				$this->aPreRendered
			);

			// the renderer is executed
			$aHtmlBag = $this->oRenderer->_render($aRendered);

			$this->checkPoint(
				array(
					"after-render",
				)
			);
		}

		$this->checkPoint(
			array(
				"before-js-inclusion"
			)
		);

		if($this->defaultTrue($this->sXpathToMeta . "exportstyles")) {
			$aStyles = $this->getAllHtmlTags("style", $aHtmlBag["CONTENT"]);
			if(!empty($aStyles)) {

				$aHtmlBag["CONTENT"] = str_replace($aStyles, "<!-- Style tag exported to external css file -->\n", $aHtmlBag["CONTENT"]);

				reset($aStyles);
				while(list(, $sStyle) = each($aStyles)) {

					$sStyle = $this->oHtml->removeFirstAndLastTag($sStyle);

					reset($this->aORenderlets);
					while(list($sName, ) = each($this->aORenderlets)) {
						$sStyle = str_replace("#" . $sName, "#" . $this->aORenderlets[$sName]->_getElementCssId(), $sStyle);
					}

					$this->additionalHeaderData(
						$this->inline2TempFile(
							$sStyle,
							'css',
							"Exported style-tags of '" . $this->formid . "'"
						)
					);
				}
			}
		}

		$this->fetchAjaxServices();

		$debug = "";

		$this->_debug($aHtmlBag, "FORMIDABLE CORE - RETURN");

		if($this->bDebug) {
			$debug = $this->debug();
		}

		if($this->useJs()) {
			reset($this->aORenderlets);

			$this->attachAccessibilityInit();

			if($this->aAddPostVars !== FALSE) {

				reset($this->aAddPostVars);

				while(list($sKey, ) = each($this->aAddPostVars)) {
					if(array_key_exists("action", $this->aAddPostVars[$sKey]) && $this->aAddPostVars[$sKey]["action"] == "execOnNextPage") {

						$aTask = $this->aAddPostVars[$sKey]["params"];

						$this->attachInitTask(
							$this->oRenderer->_getClientEvent(
								$aTask["object"],
								array(),
								$aTask,
								"execOnNextPage"
							)
						);
					}
				}
			}

			$this->attachCodeBehindJsIncludes();
			$this->attachCodeBehindJsInits();
			$this->attachRdtEvents();
			$this->attachAjaxServices();

			reset($this->aOnloadEvents["ajax"]);

			while(list($sEventId, $aEvent) = each($this->aOnloadEvents["ajax"])) {
				//debug($aEvent, $sEventId);
				$this->attachInitTask(
					$this->oRenderer->_getAjaxEvent(
						$this->aORenderlets[$aEvent["name"]],
						$aEvent["event"],
						"onload"
					),
					"AJAX Event onload for " . $this->formid . "." . $aEvent["name"],
					$sEventId
				);
			}

			reset($this->aOnloadEvents["client"]);

			while(list(, $aEvent) = each($this->aOnloadEvents["client"])) {
				$this->attachInitTask(
					$this->oRenderer->_getClientEvent(
						$aEvent["name"],
						$aEvent["event"],
						$aEvent["eventdata"],
						"onload"
					),
					"CLIENT Event onload for " . $this->formid . "." . $aEvent["name"]
				);
			}

			if(!empty($this->aInitTasks)) {
				$sJsTasks = implode("", $this->aInitTasks) . "\n";
				$sJsTasks .= 'Formidable.Context.Forms["' . $this->formid . '"].executePostInit();';

				$sJs = "Formidable.onDomLoaded(function() {\n" . $sJsTasks . "\n});";
				$sJs .= implode("\n", $this->aInitTasksOutsideLoad);
				if($this->shouldGenerateScriptAsInline() === FALSE) {
					$this->additionalHeaderData(
						$this->inline2TempFile(
							$sJs,
							'js',
							"Formidable '" . $this->formid . "' initialization"
						)
					);
				} else {
					$this->additionalHeaderData(
						"<!-- BEGIN:Formidable '" . $this->formid . "' initialization-->\n" .
						"<script type=\"text/javascript\">\n" . $sJs . "\n</script>\n" .
						"<!-- END:Formidable '" . $this->formid . "' initialization-->\n"
					);
				}
			}

			$this->oJs->includeAdditionalLibraries();

			if(!empty($this->aPostInitTasks)) {
				# attaching postinit tasks to a custom event
				//$sJs = "Event.observe(document, 'formidable:formpostinit-" . $this->formid . "', function() {\n" . implode("", $this->aPostInitTasks) . "\n});";
				$sJs = "Formidable.attachEvent(document, 'formidable:formpostinit-" . $this->formid . "', function() {\n" . implode("", $this->aPostInitTasks) . "\n});";

				if($this->shouldGenerateScriptAsInline() === FALSE) {
					$this->additionalHeaderData(
						$this->inline2TempFile(
							$sJs,
							'js',
							"Formidable '" . $this->formid . "' post-initialization"
						)
					);
				} else {
					$this->additionalHeaderData(
						"<!-- BEGIN:Formidable '" . $this->formid . "' post-initialization-->\n" .
						"<script type=\"text/javascript\">\n" . $sJs . "\n</script>\n" .
						"<!-- END:Formidable '" . $this->formid . "' post-initialization-->\n"
					);
				}
			}

			if(($sHeaderMarker = $this->getMarkerForHeaderInjection()) !== FALSE) {
				$GLOBALS["tx_ameosformidable"]["headerinjection"][] = array(
					"marker" => $sHeaderMarker,
					"headers" => $this->aHeadersWhenInjectNonStandard
				);
			} elseif($this->manuallyInjectHeaders()) {
				$GLOBALS["tx_ameosformidable"]["headerinjection"][] = array(
					"manual" => TRUE,
					"headers" => $this->aHeadersWhenInjectNonStandard
				);
			} else {
				if($this->shouldCompileAndGzipLocalScripts()) {
					$this->oJs->compileAndGzipFormidableScripts();
				} else {
					// nothing, TYPO3 will include headers as usual
				}
			}
		}

		if($this->oDataHandler->bHasCreated) {
			$this->checkPoint(
				array(
					"after-js-inclusion",
					"after-validation",
					"end-creation",
					"end"
				)
			);
		} elseif($this->oDataHandler->bHasEdited) {
			$this->checkPoint(
				array(
					"after-js-inclusion",
					"after-validation",
					"end-edition",
					"end"
				)
			);
		} else {
			$this->checkPoint(
				array(
					"after-js-inclusion",
					"after-validation",
					"end"
				)
			);
		}

		//debug($this->aAjaxEvents);

		$this->bStoreFormInSession = ($this->bStoreFormInSession || $this->defaultFalse($this->sXpathToMeta . "keepinsession"));

		if($this->bStoreFormInSession === TRUE) {
			$this->_storeFormInSession();
		} else {
			$this->_clearFormInSession();
		}

		$this->end_tstamp = t3lib_div::milliseconds();
		
		if(!empty($sDH)) {
			return $aHtmlBag["FORMBEGIN"] . $sDH . $aHtmlBag["HIDDEN"] . $aHtmlBag["FORMEND"] . $debug;
		} else {
			return $aHtmlBag["FORMBEGIN"] . $aHtmlBag["CONTENT"] . $aHtmlBag["HIDDEN"] . $aHtmlBag["FORMEND"] . $debug;
		}
	}

	function fetchAjaxServices() {
		$aServices = array_merge(		// array_merge reindexes array
			array_filter(
				array_keys($this->_navConf($this->sXpathToMeta)),
				array($this, "__cbkFilterAjaxServices")
			)
		);

		reset($aServices);
		while(list(, $sServiceKey) = each($aServices)) {
			if(($mService = $this->_navConf($this->sXpathToMeta . $sServiceKey)) !== FALSE) {
				$sName = array_key_exists("name", $mService) ? trim(strtolower($mService["name"])) : "";
				$sServiceId = $this->getAjaxServiceId($mService["name"]);

				if($sName === "") {
					$this->mayday("Ajax service: ajax service requires /name to be set.");
				}
				/*
				if(array_key_exists($sName, $this->aAjaxServices)) {
					$this->mayday("Ajax service '" . $mService["name"] . "': two or more ajax services are set with the same name.");
				}
				*/

				$this->aAjaxServices[$sServiceId] = array(
					"definition" => $mService,
				);
			}
		}

		if(!empty($aServices)) {
			// an ajax service (or more) is declared
			// we have to store this form in session
			// for serving ajax requests

			$this->bStoreFormInSession = TRUE;

			$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxservice"][$this->_getSessionDataHashKey()] = array(
				"requester" => array(
					"name" => "tx_ameosformidable",
					"xpath" => "/",
				),
			);
		}
	}

	function attachAjaxServices() {
		$aRes = array();
		$sSafeLock = $this->_getSessionDataHashKey();

		reset($this->aAjaxServices);
		while(list($sServiceId,) = each($this->aAjaxServices)) {
			$sMixedCaseName = trim($this->aAjaxServices[$sServiceId]["definition"]["name"]);

			$sJs =<<<JAVASCRIPT
Formidable.f("{$this->formid}").declareAjaxService("{$sMixedCaseName}", "{$sServiceId}", "{$sSafeLock}");
JAVASCRIPT;
			$aRes[] = $sJs;
		}

		$this->attachInitTask(
			implode("\n", $aRes),
			"Ajax Services"
		);
	}

	function __cbkFilterAjaxServices($sName) {
		$sName = strtolower($sName);
		return ($sName{0} === "a") && ($sName{1} === "j") && (substr($sName, 0, 11) === "ajaxservice");	// should start with "aj"
	}

	function getAjaxServiceId($sName) {
		return $this->_getSafeLock("ajaxservice:" . $this->formid . ":" . $sName);
	}

	function processDataBridges($bShouldProcess = TRUE) {
		if($bShouldProcess === FALSE) {
			return;
		}

		$aRdts = array_keys($this->aORenderlets);

		reset($aRdts);
		while(list(, $sName) = each($aRdts)) {
			if(array_key_exists($sName, $this->aORenderlets) && !$this->aORenderlets[$sName]->hasParent()) {
				#debug($sName, "processDataBridges");
				$this->aORenderlets[$sName]->processDataBridge();
			}
		}
	}

	function attachRdtEvents() {
		//debug($this->aRdtEvents);

		$this->attachInitTask(
			implode("\n", $this->aRdtEvents),
			"RDT Events"
		);
	}

	function attachCodeBehindJsIncludes() {
		if(!empty($this->aCodeBehindJsIncludes)) {
			$this->additionalHeaderData("<!-- Formidable " . $this->formid . ": CodeBehind includes -->");
			reset($this->aCodeBehindJsIncludes);
			while(list($sKey,) = each($this->aCodeBehindJsIncludes)) {

				$this->additionalHeaderDataLocalScript(
					$this->aCodeBehindJsIncludes[$sKey],
					$sKey
				);
			}

			$this->additionalHeaderData("<!-- Formidable " . $this->formid . ": CodeBehind includes end -->");
		}
	}

	function attachCodeBehindJsInits() {
		$this->attachInitTask(
			implode("\n", $this->aCodeBehindJsInits),
			"CodeBehind inits"
		);
	}

	function attachAccessibilityInit() {
		reset($this->aORenderlets);
		while(list($sKey, ) = each($this->aORenderlets)) {
			if($this->aORenderlets[$sKey]->hideIfJs() === TRUE) {
				$this->attachInitTask(
					"Formidable.f('" . $this->formid . "').o('" . $this->aORenderlets[$sKey]->_getElementHtmlId() . "').displayNone();",
					"Access"
				);
			}
		}

		$this->aInitTasks = array_merge(array_values($this->aInitTasksUnobtrusive), array_values($this->aInitTasks));
		// array_merge of array_values to avoid overruling
	}

	function shouldGenerateScriptAsInline() {
		return $this->defaultFalse($this->sXpathToMeta . "inlinescripts");
	}

	function attachPostInitTask($sScript, $sDesc = "", $sKey = FALSE) {
		if($this->__getEnvExecMode() === "EID") {
			$this->attachPostInitTask_ajax(
				$sScript,
				$sDesc,
				$sKey
			);
		} else {
			$this->attachPostInitTask_plain(
				$sScript,
				$sDesc,
				$sKey
			);
		}
	}

	function attachPreUninitTask($sScript, $sDesc = "", $sKey = FALSE) {
		// no implementation for the moment
			// as it's mainly useful when uniting a modalbox for instance
			// (!hasDomAtLoad())
	}

	function attachPostInitTask_plain($sScript, $sDesc = "", $sKey = FALSE) {

		if($sDesc != "") {
			$sDesc = "\n\n/* FORMIDABLE: " . trim(str_replace(array("/*", "*/", "//"), "", $sDesc)) . " */";
		}

		$sJs = "\n" . trim($sScript);
		if($sKey === FALSE) {
			$this->aPostInitTasks[] = $sJs;
		} else {
			$this->aPostInitTasks[$sKey] = $sJs;
		}
	}

	function attachPostInitTask_ajax($sScript, $sDesc = "", $sKey = FALSE) {

		if($sDesc != "") {
			$sDesc = "\n\n/* FORMIDABLE: " . trim(str_replace(array("/*", "*/", "//"), "", $sDesc)) . " */";
		}

		$sJs = $sDesc . "\n" . trim($sScript) . "\n";

		if($sKey === FALSE) {
			$this->aPostInitTasksAjax[] = $sJs;
		} else {
			$this->aPostInitTasksAjax[$sKey] = $sJs;
		}
	}

	/**
	 * Declares a JS task to execute at page init time
	 *
	 * @param	string		$sScript: JS code
	 * @param	string		$sDesc: optional; description of the code, place as a comment in the HTML
	 * @param	string		$sKey: optional; key of the js code in the header array
	 * @param	boolean		$bOutsideLoad: optional; load it at onload time, or after
	 * @return	void
	 */
	function attachInitTask($sScript, $sDesc = "", $sKey = FALSE, $bOutsideLoad = FALSE) {

		if($this->__getEnvExecMode() === "EID") {
			$this->attachInitTask_ajax(
				$sScript,
				$sDesc,
				$sKey,
				$bOutsideLoad
			);
		} else {
			$this->attachInitTask_plain(
				$sScript,
				$sDesc,
				$sKey,
				$bOutsideLoad
			);
		}
	}

	function attachInitTaskUnobtrusive($sScript) {
		$this->aInitTasksUnobtrusive[] = $sScript;
	}

	function attachInitTask_plain($sScript, $sDesc = "", $sKey = FALSE, $bOutsideLoad = FALSE) {

		if($sDesc != "") {
			$sDesc = "\n\n/* FORMIDABLE: " . trim(str_replace(array("/*", "*/", "//"), "", $sDesc)) . " */";
		}

		//$sJs = $sDesc . "\n" . trim($sScript) . "\n";
		$sJs = "\n" . trim($sScript);

		if($bOutsideLoad) {
			if($sKey === FALSE) {
				$this->aInitTasksOutsideLoad[] = $sJs;
			} else {
				$this->aInitTasksOutsideLoad[$sKey] = $sJs;
			}
		} else {
			if($sKey === FALSE) {
				$this->aInitTasks[] = $sJs;
			} else {
				$this->aInitTasks[$sKey] = $sJs;
			}
		}
	}

	function attachInitTask_ajax($sScript, $sDesc = "", $sKey = FALSE, $bOutsideLoad = FALSE) {

		if($sDesc != "") {
			$sDesc = "\n\n/* FORMIDABLE: " . trim(str_replace(array("/*", "*/", "//"), "", $sDesc)) . " */";
		}

		$sJs = $sDesc . "\n" . trim($sScript) . "\n";

		if($sKey === FALSE) {
			$this->aInitTasksAjax[] = $sJs;
		} else {
			$this->aInitTasksAjax[$sKey] = $sJs;
		}
	}

	/**
	 * Renders all the Renderlets elements as defined in conf
	 *
	 * @param	boolean		$bRenderChilds: render childs ? or not
	 * @return	array		Array of rendered elements, structured as $elementname => $renderedHTML
	 */
	function _renderElements($bRenderChilds = FALSE) {

		$aHtml = array();

		$aKeys = array_keys($this->aORenderlets);
		$iKeys = sizeOf($aKeys);

		for($k = 0; $k < $iKeys; $k++) {

			$sName = $aKeys[$k];

			if(!$this->aORenderlets[$sName]->isChild() || $bRenderChilds) {
				if($this->oDataHandler->aObjectType["TYPE"] != "LISTER" || $this->aORenderlets[$sName]->_searchable()) {
					if(($mHtml = $this->_renderElement($this->aORenderlets[$sName])) !== FALSE) {
						$aHtml[$sName] = $mHtml;
					}
				}
			}
		}

		reset($aHtml);
		return $aHtml;
	}

	/**
	 * Renders the given Renderlet
	 *
	 * @param	array		$aElement: details about the Renderlet to render, extracted from conf
	 * @return	string		The Rendered HTML
	 */
	function _renderElement(&$oRdt) {
		$begin = microtime(true);
		if(!$oRdt->i18n_hideBecauseNotTranslated()) {

			$mHtml = "";
			$sAbsName = $oRdt->getAbsName();
			$mHtml = $this->oRenderer->processHtmlBag(
				$oRdt->render(),
				$oRdt	// changed: avoid call-time pass-by-reference
			);
			$end = microtime(true);
			$this->executionInfo['' . ($end - $begin)] = $oRdt->getAbsName();
			return $mHtml;
		}

		return FALSE;
	}






	/**
	 * Returns system informations about an object-type
	 *
	 * @param	string		$type: something like TEXT, IMAGE, ...
	 * @param	array		$aCollectionInfos: the collection of objects where to get infos
	 * @return	mixed		array of info or FALSE if failed
	 */
	function _getInfosForType($type, $aCollectionInfos) {

		reset($aCollectionInfos);
		while(list(, $aInfos) = each($aCollectionInfos)) {

			if($aInfos["TYPE"] == $type) {
				reset($aInfos);
				return $aInfos;
			}
		}

		return FALSE;
	}

	/**
	 * Returns system-informations about the given datasource type
	 *
	 * @param	string		$type: given type
	 * @return	array		system-informations
	 */
	function _getInfosDataSourceForType($sType) {
		return $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["datasources"][$sType];
	}

	/**
	 * Returns system-informations about the given datahandler type
	 *
	 * @param	string		$type: given type
	 * @return	array		system-informations
	 */
	function _getInfosDataHandlerForType($sType) {
		return $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["datahandlers"][$sType];
	}

	/**
	 * Returns system-informations about the given renderer type
	 *
	 * @param	string		$type: given type
	 * @return	array		system-informations
	 */
	function _getInfosRendererForType($sType) {
		return $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["renderers"][$sType];
	}

	/**
	 * Returns system-informations about the given renderlet type
	 *
	 * @param	string		$type: given type
	 * @return	array		system-informations
	 */
	function _getInfosRenderletForType($sType) {
		return $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["renderlets"][$sType];
	}

	/**
	 * Returns system-informations about the given validator type
	 *
	 * @param	string		$type: given type
	 * @return	array		system-informations
	 */
	function _getInfosValidatorForType($sType) {
		return $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["validators"][$sType];
	}

	/**
	 * Returns system-informations about the given actionlet type
	 *
	 * @param	string		$type: given type
	 * @return	array		system-informations
	 */
	function _getInfosActionletForType($sType) {
		return $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["actionlets"][$sType];
	}

	/**
	 * Returns js api
	 *
	 * @return string js api
	 */
	function _getJsapi() {
		if($this->sJsapi === FALSE) {
			$this->sJsapi = $this->_navConf($this->sXpathToMeta . 'jsapi');
			if(!in_array($this->sJsapi, $this->aPossibleJsapi)) {
				$this->sJsapi = 'prototype';
			}
		}

		return $this->sJsapi;
	}

	/**
	 * Makes and initializes a datasource object
	 *
	 * @param	array		$aElement: conf for this object instance
	 * @param	string		$sXPath: xpath where this conf is declared
	 * @return	object
	 */
	function _makeDataSource($aElement, $sXPath) {
		return $this->_makeObject($aElement, "datasources", $sXPath);
	}

	/**
	 * Makes and initializes a datahandler object
	 *
	 * @param	array		$aElement: conf for this object instance
	 * @return	object
	 */
	function _makeDataHandler($aElement) {
		return $this->_makeObject($aElement, "datahandlers", $this->sXpathToControl . "datahandler/");
	}

	/**
	 * Makes and initializes a renderer object
	 *
	 * @param	array		$aElement: conf for this object instance
	 * @return	object
	 */
	function _makeRenderer($aElement) {
		return $this->_makeObject($aElement, "renderers", $this->sXpathToControl . "renderer/");
	}

	/**
	 * Makes and initializes a renderlet object
	 *
	 * @param	array		$aElement: conf for this object instance
	 * @param	string		$sXPath: xpath where this conf is declared
	 * @return	object
	 */

	function &_makeRenderlet($aElement, $sXPath, $bChilds = FALSE, &$oChildParent, $bAnonymous=FALSE, $sNamePrefix=FALSE) {

		$aOParent = array();

		if($bChilds !== FALSE) {
			// optional params cannot be passed by ref, so we're using the array-trick here
			$aOParent = array(&$oChildParent);
		}

		if(in_array($aElement['type'], $this->aExcludeRdtForJsapi[$this->_getJsapi()])) {
			$this->mayday('Renderlet ' . $aElement['type'] . ' is not available for js api : ' . $this->_getJsapi());
		}

		$oRdt =& $this->_makeObject($aElement, "renderlets", $sXPath, $sNamePrefix, $aOParent);
		#debug($oChildParent->testit, "TEST REFERENCE:" . $aElement["name"]);
		$oRdt->bAnonymous = $bAnonymous;
		$oRdt->bChild = $bChilds;

		$oRdt->initHasBeenPosted();

		return $oRdt;
	}

	/**
	 * Makes and initializes a validator object
	 *
	 * @param	array		$aElement: conf for this object instance
	 * @return	object
	 */
	function _makeValidator($aElement) {
		return $this->_makeObject($aElement, "validators", "");
	}

	/**
	 * Makes and initializes an actionlet object
	 *
	 * @param	array		$aElement: conf for this object instance
	 * @param	string		$sXPath: xpath where this conf is declared
	 * @return	object
	 */
	function _makeActionlet($aElement) {
		return $this->_makeObject($aElement, "actionlets", $this->sXpathToControl . "actionlets/");
	}

	/**
	 * Makes and initializes an object
	 *
	 * @param	array		$aElement: conf for this object instance
	 * @param	array		$sNature: renderers, datahandlers, ...
	 * @param	string		$sXPath: xpath where this conf is declared
	 * @return	object
	 */
	function &_makeObject($aElement, $sNature, $sXPath, $sNamePrefix = FALSE, $aOParent = array()) {

		$aObj = $this->_loadObject(
			$aElement["type"],
			$sNature
		);

		if(is_array($aObj)) {

			$oObj = t3lib_div::makeInstance($aObj["CLASS"]);

			if(!empty($aOParent) && is_object($aOParent[0])) {
				$oObj->setParent($aOParent[0]);
				#$aOParent[0]->testit .= ":dammitFinal";
			}

			$oObj->_init($this, $aElement, $aObj, $sXPath, $sNamePrefix);

			return $oObj;
		} else {
			$this->mayday("TYPE " . $aElement["type"] . " is not associated to any " . $sNature);
		}
	}

	function validateEverything() {
		$this->_validateElements();
	}

	function validateEverythingDraft() {
		$this->_validateElementsDraft();
	}

	/**
	 * Validates data returned by all the Renderlets elements as defined in conf
	 *
	 * @return	void		Writes into $this->_aValidationErrors[] using tx_ameosformidable::_declareValidationError()
	 */
	function _validateElements() {

		if($this->oDataHandler->_isSubmitted() || $this->isAjaxValidation()) {

			$aRdtKeys = array_keys($this->aORenderlets);

			reset($aRdtKeys);
			while(list(, $sAbsName) = each($aRdtKeys)) {
				if(($this->aORenderlets[$sAbsName]->_isSubmitted() || $this->isAjaxValidation()) && ($this->aORenderlets[$sAbsName]->getIterableAncestor() === FALSE)) {
					$this->aORenderlets[$sAbsName]->validate();
				}
			}
		}
	}

	/**
	 * Validates data returned by all the Renderlets, draft-mode
	 *
	 * @return	void
	 */
	function _validateElementsDraft() {

		if($this->oDataHandler->_isSubmitted()) {

			$aRdtKeys = array_keys($this->aORenderlets);

			reset($aRdtKeys);
			while(list(, $sName) = each($aRdtKeys)) {
				if($this->aORenderlets[$sName]->_hasToValidateForDraft()) {
					$this->aORenderlets[$sName]->validate();
				}
			}
		}
	}

	function clearValidation() {
		$this->_aValidationErrors = array();
		$this->_aValidationErrorsByHtmlId = array();
		$this->_aValidationErrorsInfos = array();
		$this->_aValidationErrorsTypes = array();
	}

	function hasErrors() {
		return !empty($this->_aValidationErrorsInfos);
	}

	/**
	 * Return
	 *
	 * @param	[type]		$aConditioner: ...
	 * @return	[type]		...
	 */
	function _matchConditions($aConditioner) {

		$bRet = TRUE;

		if(($aConditions = $this->_navConf("/conditions/", $aConditioner)) !== FALSE) {

			if(($sLogic = $this->_navConf("/logic", $aConditions)) === FALSE) {
				$sLogic = "AND";
			} else {
				$sLogic = strtoupper($sLogic);
			}

			while(list($sCondKey, ) = each($aConditions)) {

				if($sCondKey{0} === "c" && $sCondKey{1} === "o" && t3lib_div::isFirstPartOfStr($sCondKey, "condition")) {
					$aCondition = $this->_navConf($sCondKey, $aConditions);
					switch($sLogic) {
						case "OR": {
							$bRet = $bRet || $this->_matchCondition($aCondition);
							break;
						}
						case "AND":
						default: {
							$bRet = $bRet && $this->_matchCondition($aCondition);
							break;
						}
					}
				}
			}
		}

		return $bRet;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$aCondition: ...
	 * @return	[type]		...
	 */
	function _matchCondition($aCondition) {

		reset($aCondition);

		if(tx_ameosformidable::isRunneable($aCondition)) {
			return $this->callRunneable(
				$aCondition
			);
		} else {

			list($sType, $aInfos) = each($aCondition);

			if(tx_ameosformidable::isRunneable($aInfos)) {
				$aInfos = $this->callRunneable(
					$aInfos
				);
			}

			switch(strtoupper($sType)) {

				case "ISTRUE" : {
					return $this->isTrueVal($aInfos);
					break;
				}
				case "ISFALSE" : {
					return $this->isFalseVal($aInfos);
					break;
				}
				case "USERID" :
				case "USERIDS" : {

					$aUserIds = t3lib_div::trimExplode(",", $aInfos);

					if(is_array($aUserIds)) {
						return in_array(
							@intval($GLOBALS["TSFE"]->fe_user->user[$GLOBALS["TSFE"]->fe_user->userid_column]),
							$aUserIds
						);
					}

					break;
				}
				case "ISAUTHENTIFIED" : {

					$bAuth = (intval($GLOBALS["TSFE"]->fe_user->user[$GLOBALS["TSFE"]->fe_user->userid_column]) > 0);
					return ($this->isTrueVal($aCondition["ISAUTHENTIFIED"])) ? $bAuth : !$bAuth;
					break;
				}
				case "USERNAME" :
				case "USERNAMES" : {

					$aUserNames = t3lib_div::trimExplode(",", $aInfos);

					if(is_array($aUserNames)) {
						return @in_array(
							$GLOBALS["TSFE"]->fe_user->user[$GLOBALS["TSFE"]->fe_user->username_column],
							$aUserNames
						);
					}

					break;
				}
				case "USERGROUP" :
				case "USERGROUPS" : {

					$aUserGroups = t3lib_div::trimExplode(",", $aInfos);
					$aCurrentUserGroups = t3lib_div::trimExplode(
						",",
						$GLOBALS["TSFE"]->fe_user->user["usergroup"]
					);

					if(count(array_intersect($aUserGroups, $aCurrentUserGroups)) > 0) {
						return TRUE;
					}
					else {
						return FALSE;
					}

					break;
				}
				case "FORMID" : {

					if($aInfos == $this->formid) {
						return TRUE;
					}

					return FALSE;
					break;
				}
				case "ISCREATION" : {
					return !($this->oDataHandler->_edition());
					break;
				}
				case "ISEDITION" : {
					return ($this->oDataHandler->_edition());
					break;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Declares validation error
	 * Used by Validators Objects
	 *
	 * @param	string		$sElementName
	 * @param	string		$sKey
	 * @param	string		$sMessage: the error message to display
	 * @return	void		Writes into $this->_aValidationErrors[]
	 */
	function _declareValidationError($sElementName, $sKey, $sMessage, $mValue = '') {

		if(array_key_exists($sElementName, $this->aORenderlets)) {
			#debug($this->aORenderlets[$sElementName]->_getElementHtmlId(), "_declareValidationError");
			$sHtmlId = $this->aORenderlets[$sElementName]->_getElementHtmlIdWithoutFormId();

			if(!array_key_exists($sHtmlId, $this->_aValidationErrorsByHtmlId)) {

				$sNamespace = array_shift(explode(":", $sKey));
				$sType = array_pop(explode(":", $sKey));

				if(trim($sMessage) === "") {
					if($this->sDefaultLLLPrefix !== FALSE) {
						# trying to automap the error message
						//$sKey = ;
						$sMessage = $this->_getLLLabel("LLL:" . $sElementName . ".error." . $sType);
					}
				}

				$sMessage = str_replace('[value]', $mValue, $sMessage);
				
				$this->_aValidationErrors[$sElementName] = $sMessage;	// only one message per renderlet per refresh ( errors displayed one by one )
				$this->_aValidationErrorsByHtmlId[$sHtmlId] = $sMessage;

				$this->_aValidationErrorsInfos[$sHtmlId] = array(
					"elementname" => $sElementName,
					"message" => $sMessage,
					"namespace" => $sNamespace,
					"type" => $sType,
				);

				$this->_aValidationErrorsTypes[$sKey] = array(
					"namespace" => $sNamespace,
					"type" => $sType,
				);
			}
		}
	}

	// $sKey like "STANDARD:required", "DB:unique", ...
	function _hasErrorType($sKey) {
		// consider unstable as of rev 101 if /process has unset renderlet, and this renderlet was the only one to throw that type of error
			// TODO: unset also type if it's the case
		return array_key_exists($sKey, $this->_aValidationErrorsTypes);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sExtKey: ...
	 * @param	[type]		$sServiceKey: ...
	 * @param	[type]		$bVirtualizeFE: ...
	 * @param	[type]		$bInitBEuser: ...
	 * @return	[type]		...
	 */
	function declareAjaxService($sExtKey, $sServiceKey, $bVirtualizeFE = TRUE, $bInitBEuser = FALSE) {
		$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ajax_services"][$sExtKey][$sServiceKey]["conf"] = array(
			"virtualizeFE"	=> $bVirtualizeFE,
			"initBEuser"	=> $bInitBEuser,
		);
	}

	/**
	 * Execute each actionlet declared for this FORM
	 *
	 * @param	array		$aRendered:	array containing the HTML of the rendered renderlets
	 * @param	string		$sForm: the whole FORM html string
	 * @return	void
	 * @see tx_ameosformidable::_render()
	 */
	function _executeActionlets($aRendered, $sForm) {
		$this->_executeActionletsByPath($this->sXpathToControl, $aRendered, $sForm);
		$this->_executeActionletsByPath($this->sXpathToControl . "actionlets", $aRendered, $sForm);
	}

	function _executeActionletsByPath($sPath, $aRendered, $sForm) {
		$aActionlets = $this->_navConf($sPath);

		if(is_array($aActionlets)) {
			while(list($sKey, $aActionlet) = each($aActionlets)) {
				if($sKey{0} === "a" && $sKey{1} === "c" && t3lib_div::isFirstPartOfStr($sKey, "actionlet") && !t3lib_div::isFirstPartOfStr($sKey, "actionlets")) {
					$this->_executeActionlet($aActionlet, $aRendered, $sForm);
				}
			}
		}
	}

	/**
	 * Executes the specific process for this actionlet
	 *
	 * @param	array		$aActionlet: details about the Renderlet element to validate, extracted from XML conf / used in formidable_mainvalidator::validate()
	 * @param	array		$aRendered: array containing the HTML of the rendered renderlets
	 * @param	string		$sForm: the whole FORM html string
	 * @return	void
	 * @see tx_ameosformidable::_executeActionlets()
	 */
	function _executeActionlet($aActionlet, $aRendered, $sForm) {

		$oActionlet = $this->_makeActionlet($aActionlet);
		$oActionlet->_doTheMagic($aRendered, $sForm);
	}









	/*********************************
	 *
	 * Debugging functions
	 *
	 *********************************/


	/**
	 * Displays a full debug of :
	 * - the XML conf
	 * - the collection of declared DataHandlers
	 * - the collection of declared Renderers
	 * - the collection of declared Renderlets
	 * - the collection of declared Validators
	 *
	 * Can be called by the parent Extension, or by FORMidable itselves, if the XML conf sets /formidable/meta/debug/ to TRUE
	 *
	 * @param	[type]		$bExpand: ...
	 * @return	void
	 * @see	tx_ameosformidable::mayday(), tx_ameosformidable::_render()
	 */
	function debug($bExpand = FALSE) {

//		$this->oJs->_includeThisFormDebugFuncs();

		$aHtml = array();

		$aHtml[] = "<a name = '" . $this->formid . "formidable_debugtop' />";

		if($bExpand === FALSE) {
			$aHtml[] = "<a href = 'javascript:void(Formidable.f(\"" . $this->formid . "\").toggleDebug())'><img src='" . t3lib_div::getIndpEnv("TYPO3_SITE_URL") . t3lib_extmgm::siteRelPath("ameos_formidable") . "/res/images/debug.gif' border='0' alt='Toggle FORMidable::debug()' title='Toggle FORMidable::debug()'></a>";
			$aHtml[] = "<div id = '" . $this->formid . "_debugzone' style = 'font-family: Verdana; display: none; background-color: #bed1f4; padding-left: 10px; padding-top: 3px; padding-bottom: 10px;font-size: 9px;'>";
		} else {
			$aHtml[] = "<img src='" . t3lib_div::getIndpEnv("TYPO3_SITE_URL") . t3lib_extmgm::siteRelPath("ameos_formidable") . "/res/images/debug.gif' border='0' alt='Toggle FORMidable::debug()' title='Toggle FORMidable::debug()'>";
			$aHtml[] = "<div id = '" . $this->formid . "_debugzone' style = 'font-family: Verdana; display: block; background-color: #bed1f4; padding-left: 10px; padding-top: 3px; padding-bottom: 10px;font-size: 9px;'>";
		}

		$aHtml[] = "<h4>FORMidable debug()</h4>";

		$aHtml[] = "<h5>t3lib_div::_POST()</h5>";
		$aHtml[] = t3lib_div::view_array(t3lib_div::_POST());

		$aHtml[] = "<h5>t3lib_div::_GET()</h5>";
		$aHtml[] = t3lib_div::view_array(t3lib_div::_GET());

		/*$aHtml[] = "<ul>";
		$aHtml[] = "<li><a href = 'http://typo3.org/documentation/document-library/ameos_formidable/' target = '_blank'>FORMidable user documentation</a></li>";

		$aHtml[] = "</ul>";*/
		$aHtml[] = "<br>";
		$aHtml[] = "<ul>";

		if(!is_null($this->_xmlPath)) {
			// conf passed by php array ( // typoscript )
			$aHtml[] = "<li><a href = '#" . $this->formid . "formidable_xmlpath' target = '_self'>XML Path</a></li>";
		}

		$aHtml[] = "<li><a href = '#" . $this->formid . "formidable_configuration' target = '_self'>FORM configuration</a></li>";
		$aHtml[] = "<li><a href = '#" . $this->formid . "formidable_callstack' target = '_self'>Call stack</a></li>";
//		$aHtml[] = "<li><a href = '#" . $this->formid . "formidable_objects' target = '_self'>Available DataHandlers, Renderers, Renderlets, Validators and Actionlets</a></li>";
		$aHtml[] = "</ul>";

		if(!is_null($this->_xmlPath)) {

			$aHtml[] = "<a name = '" . $this->formid . "formidable_xmlpath' />";
			$aHtml[] = "<h5>XML Path</h5>";
			$aHtml[] = $this->_xmlPath;

			$aHtml[] = "<p align = 'right'><a href = '#" . $this->formid . "formidable_debugtop' target = '_self'>^top^</a></p>";


			$aHtml[] = "<a name = '" . $this->formid . "formidable_xmlfile' />";
		}


		$aHtml[] = "<a name = '" . $this->formid . "formidable_configuration' />";
		$aHtml[] = "<h5>FORM configuration</h5>";
		$aHtml[] = "<div WIDTH = '100%' style = 'HEIGHT: 400px; overflow: scroll'>" . t3lib_div::view_array($this->_aConf) . "</div>";
		$aHtml[] = "<p align = 'right'><a href = '#" . $this->formid . "formidable_debugtop' target = '_self'>^top^</a></p>";

		$aHtml[] = "<a name = '" . $this->formid . "formidable_callstack' />";
		$aHtml[] = "<h5>Call stack</h5>";
		$aHtml[] = implode("<hr>", $this->aDebug);
		$aHtml[] = "<p align = 'right'><a href = '#" . $this->formid . "formidable_debugtop' target = '_self'>^top^</a></p>";

		$aHtml[] = "</div>";

		return implode("\n", $aHtml);
	}

	/**
	 * Internal debug function
	 * Calls the TYPO3 debug function if the XML conf sets /formidable/meta/debug/ to TRUE
	 *
	 * @param	mixed		$variable: the variable to dump
	 * @param	string		$name: title of this debug section
	 * @param	string		$line: PHP code line calling this function ( __LINE__ )
	 * @param	string		$file: PHP script calling this function ( __FILE__ )
	 * @param	integer		$recursiveDepth: number of levels to debug, if recursive variable
	 * @param	string		$debugLevel: the sensibility of this warning
	 * @return	void
	 */
	function _debug($variable, $name, $bAnalyze = TRUE) {

		if($this->bDebug) {

			$aTrace		= debug_backtrace();
			$aLocation	= array_shift($aTrace);
			$aTrace1	= array_shift($aTrace);
			$aTrace2	= array_shift($aTrace);
			$aTrace3	= array_shift($aTrace);
			$aTrace4	= array_shift($aTrace);

			$numcall = sizeof($this->aDebug) + 1;

			$aDebug = array();
			$aDebug[] = "<p align = 'right'><a href = '#" . $this->formid . "formidable_debugtop' target = '_self'>^top^</a></p>";
			$aDebug[] = "<a name = '" . $this->formid . "formidable_call" . $numcall . "' />";
			$aDebug[] = "<a href = '#" . $this->formid . "formidable_call" . ($numcall - 1) . "'>&lt;&lt; prev</a> / <a href = '#" . $this->formid . "formidable_call" . ($numcall + 1) . "'>next &gt;&gt;</a><br>";
			$aDebug[] = "<strong>#" . $numcall ." - " . $name . "</strong>";
			$aDebug[] = "<br/>";
			$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>(Total exec. time: </b>" . round(t3lib_div::milliseconds() - $this->start_tstamp, 4) / 1000 ." sec)</span>";
			$aDebug[] = "<br/>";


			$aDebug[] = "<a href='javascript:void(Formidable.f(\"" . $this->formid . "\").toggleBacktrace(" . $numcall . "))'>Toggle details</a><br>";
			$aDebug[] = "<div id='" . $this->formid . "_formidable_call" . $numcall . "_backtrace' style='display: none; background-color: #FFFFCC' >";
//			$aDebug[] = "<hr/>";

			if($this->iDebug < 2) {
				$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call 0: </b>" . str_replace(PATH_site, "/", $aLocation["file"]) . ":" . $aLocation["line"]  . " | <b>" . $aTrace1["class"] . $aTrace1["type"] . $aTrace1["function"] . "</b></span><br>" . $this->_viewMixed($aTrace1["args"]);
				$aDebug[] = "<hr/>";
				$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call -1: </b>" . str_replace(PATH_site, "/", $aTrace1["file"]) . ":" . $aTrace1["line"]  . " | <b>" . $aTrace2["class"] . $aTrace2["type"] . $aTrace2["function"] . "</b></span><br>" . $this->_viewMixed($aTrace2["args"]);
				$aDebug[] = "<hr/>";
				$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call -2: </b>" . str_replace(PATH_site, "/", $aTrace2["file"]) . ":" . $aTrace2["line"]  . " | <b>" . $aTrace3["class"] . $aTrace3["type"] . $aTrace3["function"] . "</b></span><br>" . $this->_viewMixed($aTrace3["args"]);
				$aDebug[] = "<hr/>";
				$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call -3: </b>" . str_replace(PATH_site, "/", $aTrace3["file"]) . ":" . $aTrace3["line"]  . " | <b>" . $aTrace4["class"] . $aTrace4["type"] . $aTrace4["function"] . "</b></span><br>" . $this->_viewMixed($aTrace4["args"]);
				$aDebug[] = "<hr/>";
			}

//			$aDebug[] = "<p style='font-family: verdana;font-size: 9px; font-style: italic;'>" . t3lib_div::debug_trail() . "</p>";

			//if($this->iDebug < 2)
			//{
				if(is_string($variable)) {
					$aDebug[] = $variable;
				} else {
					if($bAnalyze) {
						$aDebug[] = $this->_viewMixed($variable);
					} else {
						$aDebug[] = t3lib_div::view_array($variable);
					}
				}
			//}

			$aDebug[] = "</div>";

			$aDebug[] = "<br/>";

			$this->aDebug[] = implode("", $aDebug);
		}
	}

	/**
	 * Stops Formidable and PHP execution : die() if some critical error appeared
	 *
	 * @param	string		$msg: the error message
	 * @return	void
	 */
	function mayday($msg) {

		if($this->__getEnvExecMode() === "EID") {
			die("Formidable::Mayday\n\n" . trim(strip_tags($msg)));
		}

		$aTrace		= debug_backtrace();
		$aLocation	= array_shift($aTrace);
		$aTrace1	= array_shift($aTrace);
		$aTrace2	= array_shift($aTrace);
		$aTrace3	= array_shift($aTrace);
		$aTrace4	= array_shift($aTrace);

		$aDebug = array();
		$aDebug[] = "<span class='notice'><b>XML: </b> " . $this->_xmlPath . "</span>";
		$aDebug[] = "<br/>";
		$aDebug[] = "<span class='notice'><b>Formidable: </b>v" . $this->sApiVersion . "</span>";
		$aDebug[] = "<br/>";
		$aDebug[] = "<span class='notice'><b>Total exec. time: </b>" . round(t3lib_div::milliseconds() - $this->start_tstamp, 4) / 1000 ." sec</span>";
		$aDebug[] = "<br/>";


		$aDebug[] = "<h2 id='backtracetitle'>Call stack</h2>";
		$aDebug[] = "<div class='backtrace'>";
		$aDebug[] = "<span class='notice'><b>Call 0: </b>" . str_replace(PATH_site, "/", $aLocation["file"]) . ":" . $aLocation["line"]  . " | <b>" . $aTrace1["class"] . $aTrace1["type"] . $aTrace1["function"] . "</b></span><br/>With parameters: " . (!empty($aTrace1["args"]) ? $this->_viewMixed($aTrace1["args"]) : " no parameters");
		$aDebug[] = "<hr/>";
		$aDebug[] = "<span class='notice'><b>Call -1: </b>" . str_replace(PATH_site, "/", $aTrace1["file"]) . ":" . $aTrace1["line"]  . " | <b>" . $aTrace2["class"] . $aTrace2["type"] . $aTrace2["function"] . "</b></span><br />With parameters: " . (!empty($aTrace2["args"]) ? $this->_viewMixed($aTrace2["args"]) : " no parameters");
		$aDebug[] = "<hr/>";
		$aDebug[] = "<span class='notice'><b>Call -2: </b>" . str_replace(PATH_site, "/", $aTrace2["file"]) . ":" . $aTrace2["line"]  . " | <b>" . $aTrace3["class"] . $aTrace3["type"] . $aTrace3["function"] . "</b></span><br />With parameters: " . (!empty($aTrace3["args"]) ? $this->_viewMixed($aTrace3["args"]) : " no parameters");
		$aDebug[] = "<hr/>";
		$aDebug[] = "<span class='notice'><b>Call -3: </b>" . str_replace(PATH_site, "/", $aTrace3["file"]) . ":" . $aTrace3["line"]  . " | <b>" . $aTrace4["class"] . $aTrace4["type"] . $aTrace4["function"] . "</b></span><br />With parameters: " . (!empty($aTrace4["args"]) ? $this->_viewMixed($aTrace4["args"]) : " no parameters");
		$aDebug[] = "<hr/>";

		if(is_callable(array("t3lib_div", "debug_trail"))) {
			$aDebug[] = "<span class='notice'>" . t3lib_div::debug_trail() . "</span>";
			$aDebug[] = "<hr/>";
		}

		$aDebug[] = "</div>";

		$aDebug[] = "<br/>";

		$sContent =	"<h1 id='title'>Formidable::Mayday</h1>";
		$sContent .= "<div id='errormessage'>" . $msg . "</div>";
		$sContent .= "<hr />";
		$sContent .= implode("", $aDebug);
		//$sContent .= $this->debug(TRUE);

		$sPage =<<<MAYDAYPAGE
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Formidable::Mayday</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="noindex, nofollow" />
		<style type="text/css">

			#title {
				color: red;
				font-family: Verdana;
			}

			#errormessage {
				border: 2px solid red;
				padding: 10px;
				color: white;
				background-color: red;
				font-family: Verdana;
				font-size: 12px;
			}

			.notice {
				font-family: Verdana;
				font-size: 9px;
				font-style: italic;
			}

			#backtracetitle {
			}

			.backtrace {
				background-color: #FFFFCC;
			}

			HR {
				border: 1px solid silver;
			}
		</style>
	</head>
	<body>
		{$sContent}
	</body>
</html>

MAYDAYPAGE;

/*		if($this->bDebug) {
			die($sPage);
		} elseif($this->bDebugIP) {
			$sPage = "<h4 style='color: red'>This full detail error-message is displayed because your IP(" . t3lib_div::getIndpEnv('REMOTE_ADDR') . ") matches the TYPO3 devIPMask</h4>" . $sPage;
			die($sPage);
		} elseif($GLOBALS["TSFE"]->TYPO3_CONF_VARS['FE']['pageNotFound_handling']) {
			$GLOBALS["TSFE"]->pageNotFoundAndExit('FORMIDABLE Mayday: ' . $msg);
		}*/

		die($sPage);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mVar: ...
	 * @return	[type]		...
	 */
	function debug4ajax($mVar) {
		echo "/*";
		print_r($mVar);
		echo "*/";
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mMixed: ...
	 * @param	[type]		$bRecursive: ...
	 * @return	[type]		...
	 */
	function d() {

		$aVars = func_get_args();

		if(func_num_args() === 1) {
			$aVars = func_get_arg(0);
		}

		echo "<div>" . tx_ameosformidable::_viewMixed(
			$aVars,
			TRUE,
			0
		) . "</div>";
		flush();
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mMixed: ...
	 * @param	[type]		$bRecursive: ...
	 * @param	[type]		$iLevel: ...
	 * @return	[type]		...
	 */
	function _viewMixed($mMixed, $bRecursive = TRUE, $iLevel=0) {

		$sStyle = "font-family: Verdana; font-size: 9px;";
		$sStyleBlack = $sStyle . "color: black;";
		$sStyleRed = $sStyle . "color: red;";
		$sStyleGreen = $sStyle . "color: green;";

		$aBgColors = array(
			"FFFFFF", "F8F8F8", "EEEEEE", "E7E7E7", "DDDDDD", "D7D7D7", "CCCCCC", "C6C6C6", "BBBBBB", "B6B6B6", "AAAAAA", "A5A5A5", "999999", "949494", "888888", "848484", "777777", "737373"
		);

		if(is_array($mMixed)) {

			$result="<table border=1 style='border: 1px solid silver' cellpadding=1 cellspacing=0 bgcolor='#" . $aBgColors[$iLevel] . "'>";

			if(!count($mMixed)) {
				$result.= "<tr><td><span style='" . $sStyleBlack . "'><b>".htmlspecialchars("EMPTY!")."</b></span></td></tr>";
			} else {
				while(list($key, $val)=each($mMixed)) {

					$result.= "<tr><td valign='top'><span style='" . $sStyleBlack . "'>".htmlspecialchars((string)$key)."</span></td><td>";

					if(is_array($val))	{
						$result.=tx_ameosformidable::_viewMixed($val, $bRecursive, $iLevel + 1);
					} else {
						$result.= "<span style='" . $sStyleRed . "'>".tx_ameosformidable::_viewMixed($val, $bRecursive, $iLevel + 1)."<br /></span>";
					}

					$result.= "</td></tr>";
				}
			}

			$result.= "</table>";

		} elseif(is_resource($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>RESOURCE: </span>" . $mMixed;
		} elseif(is_object($mMixed)) {
			if($bRecursive) {
				$result = "<span style='" . $sStyleGreen . "'>OBJECT (" . get_class($mMixed) .") : </span>" . tx_ameosformidable::_viewMixed(get_object_vars($mMixed), FALSE, $iLevel + 1);
			} else {
				$result = "<span style='" . $sStyleGreen . "'>OBJECT (" . get_class($mMixed) .") : !RECURSION STOPPED!</span>";// . t3lib_div::view_array(get_object_vars($mMixed), FALSE);
			}
		} elseif(is_bool($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>BOOLEAN: </span>" . ($mMixed ? "TRUE" : "FALSE");
		} elseif(is_string($mMixed)) {
			if(empty($mMixed)) {
				$result = "<span style='" . $sStyleGreen . "'>STRING(0)</span>";
			} else {
				$result = "<span style='" . $sStyleGreen . "'>STRING(" . strlen($mMixed) . "): </span>" . nl2br(htmlspecialchars((string)$mMixed));
			}
		} elseif(is_null($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>!NULL!</span>";
		} elseif(is_integer($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>INTEGER: </span>" . $mMixed;
		} else {
			$result = "<span style='" . $sStyleGreen . "'>MIXED: </span>" . nl2br(htmlspecialchars(strVal($mMixed)));
		}

		return $result;
	}

	/*********************************
	 *
	 * Utilitary functions
	 *
	 *********************************/

	function getLLLabel($mLabel) {
		return $this->_getLLLabel($mLabel);
	}
	/**
	 * Returns the translated string for the given LLL path
	 *
	 * @param	string		$label: LLL path
	 * @return	string		The translated string
	 */
	function _getLLLabel($mLabel) {

		if(tx_ameosformidable::isRunneable($mLabel)) {
			$mLabel = $this->callRunneable($mLabel);
		}

		if($this->sDefaultLLLPrefix !== FALSE) {
			if(t3lib_div::isFirstPartOfStr($mLabel, "LLL:") && !t3lib_div::isFirstPartOfStr($mLabel, "LLL:EXT:")) {
				$mLabel = str_replace("LLL:", "LLL:" . $this->sDefaultLLLPrefix . ":", $mLabel);
			}
		}

		$mLabel = tx_ameosformidable::resolveLLL($mLabel);

		return $mLabel;
	}

	function resolveLLL($sLLL) {
		if(!is_string($sLLL)) {
			$sLLL = "";
		}

		if($sLLL{0} === "L" && t3lib_div::isFirstPartOfStr($sLLL, "LLL:")) {
			if(TYPO3_MODE == "FE") {
				// front end
				return str_replace(
					array('###BR###', '###CR###'),
					array('<br />', "\n"),
					$GLOBALS["TSFE"]->sL($sLLL)
				);
			}
			else {
				// back end
				return str_replace(
					array('###BR###', '###CR###'),
					array('<br />', "\n"),
					$GLOBALS["LANG"]->sL($sLLL)
				);
			}
		}

		return $sLLL;
	}

	/**
	 * Callback function for preg_callback_replace
	 *
	 * Returns the translated string for the given {LLL} path
	 *
	 * @param	string		$label: {LLL} path
	 * @return	string		The translated string
	 */
	function _getLLLabelTag($aLabel) {
		return $this->_getLLLabel(
			str_replace(array("{", "}"), "", array_pop($aLabel))
		);
	}

	function parseTemplate(
		$templatePath,
		$templateMarker,
		$aTags = array(),
		$aExclude = array(),
		$bClearNotUsed = TRUE,
		$aLabels = array(),
		$bThrusted = FALSE
	) {
		return $this->_parseTemplate($templatePath, $templateMarker, $aTags, $aExclude, $bClearNotUsed, $aLabels, $bThrusted);
	}
	/**
	 * Parses a template
	 *
	 * @param	string		$templatePath: the path to the template file
	 * @param	string		$templateMarker: the marker subpart
	 * @param	array		$aTags: array containing the values to render
	 * @param	[type]		$aExclude: ...
	 * @param	[type]		$bClearNotUsed: ...
	 * @param	[type]		$aLabels: ...
	 * @return	string		HTML string with substituted values
	 */
	function _parseTemplate(
		$templatePath,
		$templateMarker,
		$aTags = array(),
		$aExclude = array(),
		$bClearNotUsed = TRUE,
		$aLabels = array(),
		$bThrusted = FALSE
	) {

		// $tempUrl : the path of the template for use with t3lib_div::getUrl()
		// $tempMarker :  the template subpart marker
		// $aTags : the marker array for substitution
		// $aExclude : tag names that should not be substituted

		if($templateMarker{0} === "L" && substr($templateMarker, 0, 4) === "LLL:") {
			$templateMarker = $this->getLLLabel($templateMarker);
		}

		$templatePath = tx_ameosformidable::toServerPath($templatePath);

		return tx_ameosformidable::_parseTemplateCode(
			t3lib_parsehtml::getSubpart(
				t3lib_div::getUrl($templatePath),
				$templateMarker
			),
			$aTags,
			$aExclude,
			$bClearNotUsed,
			$aLabels,
			$bThrusted
		);
	}

	/**
	 * Parses a template
	 *
	 * @param	string		$templatePath: the path to the template file
	 * @param	string		$templateMarker: the marker subpart
	 * @param	array		$aTags: array containing the values to render
	 * @param	[type]		$aExclude: ...
	 * @param	[type]		$bClearNotUsed: ...
	 * @param	[type]		$aLabels: ...
	 * @return	string		HTML string with substituted values
	 */
	function _parseThrustedTemplate(
		$templatePath,
		$templateMarker,
		$aTags = array(),
		$aExclude = array(),
		$bClearNotUsed = TRUE,
		$aLabels = array()
	) {

		return tx_ameosformidable::_parseTemplate(
			$templatePath,
			$templateMarker,
			$aTags,
			$aExclude,
			$bClearNotUsed,
			$aLabels,
			TRUE	// $bThrusted
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function _getParentExtSitePath() {

		if(TYPO3_MODE === "FE") {
			if(is_subclass_of($this->_oParent, "tslib_pibase")) {
				$sExtKey = $this->_oParent->extKey;
			} else {
				$sExtKey = "ameos_formidable";
			}
		} else {
			$sExtKey = $GLOBALS["_EXTKEY"];
		}

		return t3lib_div::getIndpEnv("TYPO3_SITE_URL") . t3lib_extMgm::siteRelPath($sExtKey);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sHtml: ...
	 * @return	[type]		...
	 */
	function _substLLLInHtml($sHtml) {

		if($sHtml{0} === "L" && t3lib_div::isFirstPartOfStr($sHtml, "LLL:")) {
			return $this->_getLLLabel($sHtml);
		}

		return @preg_replace_callback(
			"/{LLL:[a-zA-Z0-9_:\/.\-]*}/",
			array($this, "_getLLLabelTag"),
			$sHtml
		);
	}

	function pushTemplateMarkers($aMarkers) {
		if(!isset($GLOBALS["tx_ameosformidable"][$this->formid]["aTags"])) {
			$GLOBALS["tx_ameosformidable"][$this->formid]["aTags"] = array();
		}

		$GLOBALS["tx_ameosformidable"][$this->formid]["aTags"][] = $aMarkers;
	}

	function pullTemplateMarkers() {
		return array_pop($GLOBALS["tx_ameosformidable"][$this->formid]["aTags"]);
	}

	function currentTemplateMarkers() {
		if(!isset($GLOBALS["tx_ameosformidable"][$this->formid]["aTags"])) {
			return array();
		}

		if(empty($GLOBALS["tx_ameosformidable"][$this->formid]["aTags"])) {
			return array();
		}

		$iCount = count($GLOBALS["tx_ameosformidable"][$this->formid]["aTags"]);
		return $GLOBALS["tx_ameosformidable"][$this->formid]["aTags"][($iCount-1)];
	}

	function templateMarkersStack() {
		if(!isset($GLOBALS["tx_ameosformidable"][$this->formid]["aTags"])) {
			return array();
		}

		reset($GLOBALS["tx_ameosformidable"][$this->formid]["aTags"]);
		return $GLOBALS["tx_ameosformidable"][$this->formid]["aTags"];
	}

	function _parseThrustedTemplateCode(
		$sHtml,
		$aTags,
		$aExclude = array(),
		$bClearNotUsed = TRUE,
		$aLabels = array()
	) {
		return $this->_parseTemplateCode(
			$sHtml,
			$aTags,
			$aExclude,
			$bClearNotUsed,
			$aLabels,
			$bThrusted = TRUE
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$sHtml: ...
	 * @param	[type]		$aTags: ...
	 * @param	[type]		$aExclude: ...
	 * @param	[type]		$bClearNotUsed: ...
	 * @param	[type]		$aLabels: ...
	 * @return	[type]		...
	 */
	function _parseTemplateCode(
		$sHtml,
		$aTags,
		$aExclude = array(),
		$bClearNotUsed = TRUE,
		$aLabels = array(),
		$bThrusted = FALSE
	) {
		if(!isset($this->sPfxBegin)) {
			$this->sPfxBegin = MD5(rand());
			$this->sPfxEnd = MD5(rand());
		}

		if(!is_array($aTags)) {
			$aTags = array();
		}

		if(is_object($this)) {
			$this->pushTemplateMarkers($aTags);
		}

		if(is_object($this->_oParent) && !empty($this->_oParent->extKey)) {
			$aTags["PARENTPATH"] = $this->_getParentExtSitePath();
		}

		if(count($aExclude) > 0) {

			$sExcludePfx = md5(microtime(TRUE));
			$sExcludePfx2 = md5(microtime(TRUE)+1);

			reset($aExclude);
			while(list(, $tag) = each($aExclude)) {

				$sHtml = str_replace("{" . $tag . "}", $sExcludePfx . $tag . $sExcludePfx, $sHtml);
				$sHtml = str_replace("{" . $tag . ".label}", $sExcludePfx2 . $tag . $sExcludePfx2, $sHtml);
			}
		}

		reset($aTags);
		while(list($sName, $mVal) = each($aTags)) {
			#debug($sName, "on remplace les subparts");
			if(($sRdtSubPart = t3lib_parsehtml::getSubpart($sHtml, "###" . $sName . "###")) !== "") {
				$sHtml = t3lib_parsehtml::substituteSubpart(
					$sHtml,
					"###" . $sName . "###",
					$mVal["__compiled"],
					FALSE,
					FALSE
				);
			}
		}

		$sHtml = $this->processForEaches($sHtml);
		$sHtml = $this->processWithAs($sHtml);
		$sHtml = $this->processPerimeters($sHtml, $bClearNotUsed);
		$sHtml = $this->processMarkers($sHtml, $bClearNotUsed, $bThrusted);

		if(count($aExclude) > 0) {
			reset($aExclude);
			while(list(, $tag) = each($aExclude)) {
				$sHtml = str_replace($sExcludePfx . $tag . $sExcludePfx, "{" . $tag . "}", $sHtml);
				$sHtml = str_replace($sExcludePfx2 . $tag . $sExcludePfx2, "{" . $tag . ".label}", $sHtml);
			}
		}

		if(is_object($this)) {
			$this->pullTemplateMarkers();
		}

		if($bClearNotUsed) {
			$sHtml = str_replace(
				array(
					$this->sPfxBegin, $this->sPfxEnd
				),
				array(
					"{", "}"
				),
				$sHtml
			);

			$sHtml = preg_replace("|{[^\{\}\n]*}|", "", $sHtml);
		}

		return $sHtml;
	}

	function sanitizeStringForTemplateEngine($sString) {
		return str_replace(
	        array("{", "}"),
	        array("&#123;", "&#125;"),
	        $sString
		);
	}

	function processMarkers($sHtml, $bClearNotUsed = TRUE, $bThrusted = FALSE) {
		$sPattern = '/{([^\{\}\n]*)}/';

		if($bThrusted === TRUE) {
			if($bClearNotUsed === TRUE) {
				$sCbk = "processMarkersCallBackClearNotUsedThrusted";
			} else {
				$sCbk = "processMarkersCallBackKeepNotUsedThrusted";
			}
		} else {
			if($bClearNotUsed === TRUE) {
				$sCbk = "processMarkersCallBackClearNotUsed";
			} else {
				$sCbk = "processMarkersCallBackKeepNotUsed";
			}
		}

		$sHtml = preg_replace_callback(
			$sPattern,
			array(
				&$this,
				$sCbk
			),
			$sHtml,
			-1	// no limit
		);

		return $sHtml;
	}

	function processMarkersCallBackClearNotUsed($aMatch) {
		return $this->processMarkersCallBack(
			$aMatch,
			TRUE,
			FALSE
		);
	}

	function processMarkersCallBackKeepNotUsed($aMatch) {
		return $this->processMarkersCallBack(
			$aMatch,
			FALSE,
			FALSE
		);
	}

	function processMarkersCallBackClearNotUsedThrusted($aMatch) {
		return $this->processMarkersCallBack(
			$aMatch,
			TRUE,
			TRUE
		);
	}

	function processMarkersCallBackKeepNotUsedThrusted($aMatch) {
		return $this->processMarkersCallBack(
			$aMatch,
			FALSE,
			TRUE
		);
	}

	function processMarkersCallBack($aMatch, $bClearNotUsed, $bThrusted) {

		$sCatch = $aMatch[1];
		$aTags = $this->currentTemplateMarkers();

		if(($sCatch{0} === "L" && $sCatch{1} === "L" && $sCatch{2} === "L") && ($sCatch{3} === ":")) {
			if(isset($this)) {
				return $this->_getLLLabel($sCatch);
			}
		} else {
			if(($mVal = tx_ameosformidable::resolveForTemplate($sCatch, $aTags)) !== AMEOSFORMIDABLE_LEXER_FAILED && $mVal !== AMEOSFORMIDABLE_LEXER_BREAKED) {
				if(is_array($mVal)) {
					if(array_key_exists("__compiled", $mVal)) {
						if($bThrusted) {
							return $mVal["__compiled"];
						} else {
							return $this->sanitizeStringForTemplateEngine($mVal["__compiled"]);
						}
					} else {
						return "";
					}
				} else {
					if($bThrusted) {
						return $mVal;
					} else {
						return $this->sanitizeStringForTemplateEngine($mVal);
					}
				}
			} else {
				//nothing
				if($bClearNotUsed) {
					return "";
				} else {
					if($bThrusted) {
						return $aMatch[0];
					} else {
						return $this->sPfxBegin . $aMatch[1] . $this->sPfxEnd;
					}
				}
			}
		}
	}

	function processPerimeters($sHtml, $bClearNotUsed = TRUE) {
		$aMatches = array();
		$sPattern = '/\<\!\-\-.*(\#\#\#(.+)\ \bperimeter\b\#\#\#).*\-\-\>([^\1]*?)\<\!\-\-.*\1.*\-\-\>/';

		if($bClearNotUsed === TRUE) {
			$sCbk = "processPerimetersCallBackClearNotUsed";
		} else {
			$sCbk = "processPerimetersCallBackKeepNotUsed";
		}

		$sHtml = preg_replace_callback(
			$sPattern,
			array(
				&$this,
				$sCbk
			),
			$sHtml,
			-1	// no limit
		);

		return $sHtml;
	}

	function processPerimetersCallBackClearNotUsed($aMatch) {
		return $this->processPerimetersCallBack(
			$aMatch,
			TRUE
		);
	}

	function processPerimetersCallBackKeepNotUsed($aMatch) {
		return $this->processPerimetersCallBack(
			$aMatch,
			FALSE
		);
	}

	function processPerimetersCallBack($aMatch, $bClearNotUsed = TRUE) {

		$sCond = $aMatch[2];
		$bDelete = FALSE;
		$mValue = $this->resolveForTemplate(
			$sCond,
			$this->currentTemplateMarkers()
		);

		if($mValue === AMEOSFORMIDABLE_LEXER_FAILED || $mValue === AMEOSFORMIDABLE_LEXER_BREAKED) {
			// boxes are rendered before the whole template,
				// and therefore might define perimeters on conditions
				// that will be evaluable only later in the process
				// if deletion is not asked, we keep the failed perimeters intact
				// to give a chance to later passes to catch it

			if($bClearNotUsed === TRUE) {
				$bDelete = TRUE;	// deletion
			} else {
				return $aMatch[0];	// keep it intact, to give a chance to later passes to catch it
			}
		} elseif(is_array($mValue)) {
			if(array_key_exists("__compiled", $mValue)) {
				if(trim($mValue["__compiled"]) === "") {
					$bDelete = TRUE;
				}
			} elseif(empty($mValue)) {
				$bDelete = TRUE;
			}
		} else {
			if(
				(is_string($mValue) && (trim($mValue) === "")) OR
				(is_bool($mValue) && $mValue===FALSE) OR
				($mValue === 0) OR
				($mValue === NULL)
			) {
				$bDelete = TRUE;
			}
		}

		if($bDelete === TRUE) {
			return "";
		}

		$sHtml = $this->processPerimeters($aMatch[3]);
		return $sHtml;
	}

	function processWithAs($sHtml) {
		$sPattern = '/\<\!\-\-.*(\#\#\#with (.+)\ \bas\b\ \b(.+)\#\#\#).*\-\-\>([^\1]*?)\<\!\-\-.*\1.*\-\-\>/';
		$sHtml = preg_replace_callback(
			$sPattern,
			array(
				&$this,
				"processWithAsCallBack"
			),
			$sHtml,
			-1	// no limit
		);

		return $sHtml;
	}

	function processWithAsCallBack($aMatch) {

		$aRes = array();

		$aTags = $this->currentTemplateMarkers();
		$bDelete = FALSE;
		$mValue = $this->resolveForTemplate(
			$aMatch[2],
			$aTags
		);

		if($mValue === AMEOSFORMIDABLE_LEXER_FAILED || $mValue === AMEOSFORMIDABLE_LEXER_BREAKED) {
			$bDelete = TRUE;
		} else {
			if(is_array($mValue)) {
				reset($mValue);
			}

/*			$aMarkers = array(
				"context" => $aTags,
				"key" => $sKey,
				trim($aMatch[3]) => $mValue
			);
*/
			$aTags[trim($aMatch[3])] = $mValue;

			$aRes[] = $this->_parseTemplateCode(
				$aMatch[4],
				$aTags,
				$aExclude = array(),
				$bClearNotUsed = FALSE
			);
		}

		if($bDelete === TRUE || count($aRes) === 0) {
			return "";
		}

		return implode("", $aRes);
	}

	function processForEaches($sHtml) {

		$sPattern = '/\<\!\-\-.*(\#\#\#foreach (.+)\ \bas\b\ \b(.+)\#\#\#).*\-\-\>([^\1]*?)\<\!\-\-.*\1.*\-\-\>/';
		$sHtml = preg_replace_callback(
			$sPattern,
			array(
				&$this,
				"processForEachesCallBack"
			),
			$sHtml,
			-1	// no limit
		);

		return trim($sHtml);
	}

	function processForEachesCallBack($aMatch) {
		$aRes = array();

		$aTags = $this->currentTemplateMarkers();
		$bDelete = FALSE;
		$mValue = $this->resolveForTemplate(
			$aMatch[2],
			$aTags
		);

		if($mValue === AMEOSFORMIDABLE_LEXER_FAILED || $mValue === AMEOSFORMIDABLE_LEXER_BREAKED) {
			$bDelete = TRUE;
		} elseif(is_array($mValue)) {
			reset($mValue);
			while(list($sKey,) = each($mValue)) {
				$aMarkers = array(
					"context" => $aTags,
					"key" => $sKey,
					$aMatch[3] => $mValue[$sKey]
				);

				$aRes[] = $this->_parseTemplateCode(
					$aMatch[4],
					$aMarkers,
					$aExclude = array(),
					$bClearNotUsed = FALSE
				);
			}
		}

		if($bDelete === TRUE || count($aRes) === 0) {
			return "";
		}

		return implode("", $aRes);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$main_prefix: ...
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function prefixResourcePath($main_prefix, $content) {
		$this->makeHtmlParser();

		// fooling the htmlparser to avoid replacement of {tags} in template
		$content = str_replace("{", "http://{", $content);
		$content = $this->oHtml->prefixResourcePath(
			$main_prefix,
			$content
		);
		return str_replace("http://{", "{", $content);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sTag: ...
	 * @param	[type]		$sHtml: ...
	 * @return	[type]		...
	 */
	function getAllHtmlTags($sTag, $sHtml) {
		$this->makeHtmlParser();
		$aParts = $this->oHtml->splitIntoBlock(
			$sTag,
			$sHtml
		);

		$iCount = count($aParts);
		for($k = 0; $k < $iCount; $k+=2) {
			unset($aParts[$k]);
		}

		reset($aParts);
		return array_reverse(array_reverse($aParts));	// reordering keys
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$iErrno: ...
	 * @param	[type]		$sMessage: ...
	 * @param	[type]		$sFile: ...
	 * @param	[type]		$iLine: ...
	 * @param	[type]		$oObj: ...
	 * @return	[type]		...
	 */
	function __catchEvalException($iErrno, $sMessage, $sFile, $iLine, $oObj) {

		$aErrors = array (
			E_ERROR		=> "Error",
//			E_WARNING	=> "Warning",
			E_PARSE		=> "Parse error",
		);

		if(array_key_exists($iErrno, $aErrors)) {
			ob_start();
			highlight_string($this->__sEvalTemp["code"]);
			$sPhp = ob_get_contents();
			ob_end_clean();
			$sXml = $this->_viewMixed($this->__sEvalTemp["xml"]);

			$this->mayday("<b>" . $aErrors[$iErrno] . "</b>: " . $sMessage . " in <b>" . $sFile . "</b> on line <b>" . $iLine . "</b><br /><hr />" . $sXml . "<hr/>" . $sPhp);
		}

		return TRUE;
	}

	function pushUserObjParam($aParam) {
		array_push($this->aUserObjParamsStack, $aParam);
	}

	function pullUserObjParam() {
		array_pop($this->aUserObjParamsStack);
	}

	function pushForcedUserObjParam($aParam) {
		//$this->aForcedUserObjParamsStack[$sName] = $aParam;
		array_push($this->aForcedUserObjParamsStack, $aParam);
		return (count($this->aForcedUserObjParamsStack) - 1);
	}

	function pullForcedUserObjParam($iIndex = FALSE) {

		if($iIndex === FALSE) {
			if(!empty($this->aForcedUserObjParamsStack)) {
				array_pop($this->aForcedUserObjParamsStack);
			}
		} else {
			if(array_key_exists($iIndex, $this->aForcedUserObjParamsStack)) {
				unset($this->aForcedUserObjParamsStack[$sName]);
			}
		}
	}

	function getForcedUserObjParams() {
		$aParams = array();
		if(!empty($this->aForcedUserObjParamsStack)) {
			$aParams = $this->aForcedUserObjParamsStack[count($this->aForcedUserObjParamsStack) - 1];
		}

		return $aParams;
	}

	function getUserObjParams() {

		$aParams = array();

		if(!empty($this->aUserObjParamsStack)) {
			$aParams = $this->aUserObjParamsStack[count($this->aUserObjParamsStack) - 1];
		}

		if(!empty($this->aForcedUserObjParamsStack)) {
			$aForcedParams = $this->getForcedUserObjParams();
			$aParams = t3lib_div::array_merge_recursive_overrule($aParams, $aForcedParams);
		}

		return $aParams;
	}

	function getParams() {
		return $this->getUserObjParams();
	}

	function getPreviousParams() {
		return $this->getPreviousAjaxParams();
	}

	function getListData($sKey = FALSE) {
		return $this->oDataHandler->_getListData($sKey);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$aUserobj: ...
	 * @param	[type]		$aParams: ...
	 * @param	[type]		$aItems: ...
	 * @return	[type]		...
	 */
	function _callUserObj($aUserobj, $aParams = array()) {

		if(is_array($this->_navConf("/userobj/", $aUserobj))) {

			$aUserObjParams = $this->_navConf("/userobj/params/", $aUserobj);

			if($aUserObjParams !== FALSE && is_array($aUserObjParams)) {

				while(list($index, $aParam) = each($aUserObjParams)) {

					$name = $aParam["name"];
					$value = $aParam["value"];

					$aParams[$name] = $value;
				}
				reset($aParams);
			}

			if(($mPhp = $this->_navConf("/userobj/php", $aUserobj)) !== FALSE) {

				if(is_array($mPhp) && array_key_exists("__value", $mPhp)) {
					$sPhp = $mPhp["__value"];
				} else {
					$sPhp = $mPhp;
				}

				$sClassName = uniqid("tempcl");
				$sMethodName = uniqid("tempmet");

				$this->__sEvalTemp = array("code" => $sPhp, "xml" => $aUserobj);

				$GLOBALS['formidable_tempuserobj'] =& $this;
				$sPhp = str_replace("\$this", "\$GLOBALS['formidable_tempuserobj']", $sPhp);

				$sClass =	"class " . $sClassName . " {"
					.	"	function " . $sMethodName . "(\$_this, \$aParams) { \$_this=&\$GLOBALS['formidable_tempuserobj'];"
					.	"		" . $sPhp
					.	"	}"
					.	"}" ;

				set_error_handler(array(&$this, "__catchEvalException"));
				eval($sClass);
				$oObj = new $sClassName();

				$this->pushUserObjParam($aParams);

				$sRes = call_user_func(
					array(
						&$oObj,
						$sMethodName
					),
					$this,
					$aParams
				);

				$this->pullUserObjParam();

				unset($this->__sEvalTemp);
				restore_error_handler();

				return $sRes;

			} elseif(($this->_navConf("/userobj/cobj", $aUserobj)) !== FALSE) {
				if($this->__getEnvExecMode() === "BE") {
					return $this->cObj->cObjGetSingle(
						$aUserobj["userobj"]["cobj"],
						$aUserobj["userobj"]["cobj."]
					);
				} else {
					return $GLOBALS["TSFE"]->cObj->cObjGetSingle(
						$aUserobj["userobj"]["cobj"],
						$aUserobj["userobj"]["cobj."]
					);
				}
			} elseif(($sTs = $this->_navConf("/userobj/ts", $aUserobj)) !== FALSE) {

				$sTs = '
						temp.ameos_formidable >
						temp.ameos_formidable {
							' . $sTs . '
						}
				';

			//	require_once(PATH_t3lib."class.t3lib_tsparser.php");

				$oParser = t3lib_div::makeInstance("t3lib_tsparser");
				$oParser->tt_track = 0;	// Do not log time-performance information
				$oParser->setup = $GLOBALS["TSFE"]->tmpl->setup;

				if(array_key_exists("params.", $oParser->setup)) { unset($oParser->setup["params."]);}

				$oParser->setup["params."] = $this->_addDots($aParams);

				if(($aUserObjParams = $this->_navConf("/userobj/params", $aUserobj)) !== FALSE) {
					if(is_array($aUserObjParams)) {

						if(tx_ameosformidable::isRunneable($aUserObjParams)) {
							$aUserObjParams = $this->callRunneable($aUserObjParams);
							if(!is_array($aUserObjParams)) {
								$aUserObjParams = array();
							}
						}

						$oParser->setup["params."] = t3lib_div::array_merge_recursive_overrule(
							$oParser->setup["params."],
							$aUserObjParams
						);
					}
				}


				$oParser->parse($sTs);
				$this->aLastTs = $oParser->setup["temp."]["ameos_formidable."];

				$sOldCWD = getcwd();		// changing current working directory for use of GIFBUILDER in BE
				chdir(PATH_site);

				$aRes = $this->cObj->cObjGet(
					$oParser->setup["temp."]["ameos_formidable."]
				);
				chdir($sOldCWD);

				return $aRes;
			} elseif(($sJs = $this->_navConf("/userobj/js", $aUserobj)) !== FALSE) {

				if(($aParams = $this->_navConf("/userobj/params", $aUserobj)) !== FALSE) {

					if(tx_ameosformidable::isRunneable($aParams)) {
						$aParams = $this->callRunneable($aParams);
					}
				}

				if(!is_array($aParams)) {
					$aParams = array();
				}

				return $this->majixExecJs(
					trim($sJs),
					$aParams
				);

			} else {

				$extension = $this->_navConf("/userobj/extension/", $aUserobj);
				$method = $this->_navConf("/userobj/method/", $aUserobj);



				if(strcasecmp($extension, "this") == 0)
				{ $oExtension =& $this->_oParent;}
				else
				{ $oExtension = t3lib_div::makeInstance($extension);}

				if(is_object($oExtension)) {

					if(method_exists($oExtension, $method)) {

						$newData = $oExtension->{$method}($aParams, $this);

						$this->_debug($newData, "RESULT OF " . $extension . "->" . $method . "()");

						return $newData;
					} else {

						$sObject = ($extension == "this") ? "\$this (<b>" . get_class($this->_oParent) . "</b>)" : $extension;
						$this->mayday($this->_navConf("/type/", $aElement) . " <b>" . $this->_navConf("/name/", $aElement) . "</b> : callback method <b>" . $method . "</b> of the Object <b>" . $sObject . "</b> doesn't exist");
					}
				}
			}
		}
	}

	function &_callCodeBehind($aCB/*, ...*/) {

		if(array_key_exists("exec", $aCB)) {

			$aArgs = func_get_args();
			$bCbRdt = FALSE;
			$bStatic = FALSE;

			$aExecInfo = $aCB;
			$sCBRef = $aCB["exec"];
			$bFlip = FALSE;
			if(substr($sCBRef, -10) == '.isFalse()') {
				$bFlip = TRUE;
				$sCBRef = str_replace('.isFalse()', '', $sCBRef);
			}
			$aExec = tx_ameosformidable::parseForTemplate($sCBRef);
			$aInlineArgs = tx_ameosformidable::parseTemplateMethodArgs($aExec[1]["args"]);

			if(t3lib_div::isFirstPartOfStr($sCBRef, "rdt(")) {
				$bCbRdt = TRUE;
				$aCbRdtArgs = tx_ameosformidable::parseTemplateMethodArgs($aExec[0]["args"]);
				if(($oRdt =& $this->rdt($aCbRdtArgs[0])) === FALSE) {
					$this->mayday("CodeBehind " . $sCBRef . ": Refers to an undefined renderlet");
				}
			}

			if(preg_match('/(.*)::(.*)/i', $sCBRef) === 1) {
				$bStatic = TRUE;
			}

			if(count($aInlineArgs) > 0) {
				reset($aInlineArgs);
				while(list($sKey, ) = each($aInlineArgs)) {
					if(is_object($aInlineArgs[$sKey])) {
						$aArgs[] =& $aInlineArgs[$sKey];
					} else {
						$aArgs[] = $aInlineArgs[$sKey];
					}
				}
			}

			$iNbParams = (count($aArgs) - 1);	// without the runneable itself

			$sName = $aExec[0]["expr"];
			$sMethod = $aExec[1]["expr"];

			$aTemp = $aArgs;
			array_shift($aTemp);
			if($iNbParams === 1) {
				$this->pushUserObjParam($aTemp[0]);	// back compat with revisions when only one single array-parameter was allowed
			} else {
				$this->pushUserObjParam($aTemp);
			}
			unset($aTemp);

			if(array_key_exists($sName, $this->aCodeBehinds["php"])) {
				$sType = "php";
			} elseif(array_key_exists($sName, $this->aCodeBehinds["js"])) {
				$sType = "js";
			} else {
				if($bCbRdt !== TRUE && $bStatic !== TRUE) {
					$this->mayday("CodeBehind " . $sCBRef . ": " . $sName . " is not a declared CodeBehind");
				}
			}

			if($bCbRdt === TRUE) {
				$sType = "php";
				$oCbObj =& $oRdt;
				$sClass = get_class($oCbObj);
			} else {
				if($sType === "php") {
					$aCB =& $this->aCodeBehinds[$sType][$sName];
					$oCbObj =& $aCB["object"];
					$sClass = $aCB["class"];
				} elseif($sType === "js") {
					$aCB =& $this->aCodeBehinds[$sType][$sName]["object"]->aConf;
					$oCbObj =& $this->aCodeBehinds[$sType][$sName]["object"];
					$sClass = $aCB["class"];
				}
			}

			switch($sType) {
				case "php": {

					if(is_object($oCbObj) && method_exists($oCbObj, $sMethod)) {
						if(count($aArgs) > 1) {
							$mRes = call_user_func_array(array($oCbObj, $sMethod), array_slice($aArgs, 1));	// omitting the first param (ref to the codebehind descriptor)
						} else {
							$mRes = call_user_func_array(array($oCbObj, $sMethod), array());	// no params
						}
					} else {
						if(!is_object($oCbObj)) {
							$this->mayday("CodeBehind " . $sCBRef . ": " . $sClass . " is not a valid PHP class");
						} else {
							$this->mayday("CodeBehind " . $sCBRef . ": <b>" . $sMethod . "()</b> method does not exists on object <b>" . $sClass . "</b>");
						}
					}
					break;
				}
				case "js": {

					if(count($aArgs) > 1) {
						// calls to non existing PHP methods will be intercepted by __call on the jscb PHP object
						// and passed to majixExec of the same object
						$mRes = call_user_func_array(array($oCbObj, $sMethod), array_slice($aArgs, 1));	// omitting the first param (ref to the codebehind descriptor)
					} else {
						$mRes = call_user_func_array(array($oCbObj, $sMethod), array());	// no params
					}
				}
			}



			if($bStatic === TRUE) {
				$aStaticExec = tx_ameosformidable::parseForTemplate($sCBRef);
				$aStaticArgs = explode(',', $aStaticExec[0]['args']);
				foreach($aStaticArgs as $iKey => $mArg) {
					if(trim($mArg) === 'TRUE') {
						$aStaticArgs[$iKey] = TRUE;
					}
					if(trim($mArg) === 'FALSE') {
						$aStaticArgs[$iKey] = FALSE;
					}
				}


				if(($iBracket = strpos($sCBRef, '(')) !== FALSE) {
					$sCBRef = trim(substr($sCBRef, 0, $iBracket));
				}

				$aMethod = explode('::', $sCBRef);

				if(!class_exists($aMethod[0], FALSE)) {
					$this->mayday('Class ' . $aMethod[0] . ' not exist');
				}

				if(!method_exists($aMethod[0], $aMethod[1])) {
					$this->mayday('Method ' . $sCBRef . ' not exist');
				}

				if(count($aStaticArgs) >= 1) {
					$mRes = call_user_func_array($sCBRef, $aStaticArgs);
				} else {
					$mRes = call_user_func_array($sCBRef, array());	// no params
				}
			}


			$this->pullUserObjParam();
			if($bFlip) {
				return !$mRes;
			}
			return $mRes;
		}
	}

	// taken from http://drupal.org/node/66183
	function array_insert($arr1, $key, $arr2, $before = FALSE) {
		$index = array_search($key, array_keys($arr1), TRUE);
		if($index === FALSE){
			$index = count($arr1); // insert @ end of array if $key not found
		} else {
			if(!$before) {
				$index++;
			}
		}

		$end = array_splice($arr1, $index);
		return array_merge($arr1, $arr2, $end);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$iStart: ...
	 * @param	[type]		$aData: ...
	 * @param	[type]		$bCycle: ...
	 * @param	[type]		$iDirection: ...
	 * @param	[type]		$bKey: ...
	 * @return	[type]		...
	 */
	function __getNeighbourInArray($iStart, $aData, $bCycle, $iDirection, $bKey = FALSE) {

		if(!empty($aData)) {

			$aKeys = array_keys($aData);
			if($bKey !== FALSE) {
				$iPos = array_search(
					$iStart,
					$aKeys
				);
			} else {
				$iPos = array_search(
					$iStart,
					$aData
				);
			}

			if($iPos === FALSE) {
				// search value does not exist in the given array
				return FALSE;
			}

			$iNeighbourPos = intval($iPos+($iDirection));

			if(array_key_exists($iNeighbourPos, $aKeys)) {

				if($bKey !== FALSE) {
					return $aKeys[$iNeighbourPos];
				} else {
					return $aData[$aKeys[$iNeighbourPos]];
				}

			} elseif($bCycle) {
				if($iDirection > 0) {

					if($bKey !== FALSE) {
						return $aKeys[0];
					} else {
						return $aData[$aKeys[0]];
					}

				} else {

					if($bKey !== FALSE) {
						return $aKeys[(count($aKeys) - 1)];
					} else {
						return $aData[$aKeys[(count($aKeys) - 1)]];
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$iStart: ...
	 * @param	[type]		$aData: ...
	 * @param	[type]		$bCycle: ...
	 * @param	[type]		$bKey: ...
	 * @return	[type]		...
	 */
	function _getNextInArray($iStart, $aData, $bCycle = FALSE, $bKey = FALSE) {
		return tx_ameosformidable::__getNeighbourInArray(
			$iStart,
			$aData,
			$bCycle,
			+1,
			$bKey
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$iStart: ...
	 * @param	[type]		$aData: ...
	 * @param	[type]		$bCycle: ...
	 * @param	[type]		$bKey: ...
	 * @return	[type]		...
	 */
	function _getPrevInArray($iStart, $aData, $bCycle = FALSE, $bKey = FALSE) {
		return tx_ameosformidable::__getNeighbourInArray(
			$iStart,
			$aData,
			$bCycle,
			-1,
			$bKey
		);
	}


	function _isTrue($sPath, $aConf = -1) {
		return $this->isTrue($sPath, $aConf);
	}

	function isTrue($sPath, $aConf = -1) {
		return $this->isTrueVal(
			$this->_navConf(
				$sPath,
				$aConf
			)
		);
	}

	function _isFalse($sPath, $aConf = -1) {
		return $this->isFalse($sPath, $aConf);
	}

	function isFalse($sPath, $aConf = -1) {

		$mValue = $this->_navConf(
			$sPath,
			$aConf
		);

		if($mValue !== FALSE) {
			return $this->isFalseVal($mValue);
		} else {
			return FALSE;	// if not found in conf, the searched value is not FALSE, so isFalse() returns FALSE !!!!
		}
	}

	function _isTrueVal($mVal) {
		return $this->isTrueVal($mVal);
	}

	function isTrueVal($mVal) {

		if(tx_ameosformidable::isRunneable($mVal)) {
			$mVal = $this->callRunneable(
				$mVal
			);
		}

		return (($mVal === TRUE) || ($mVal == "1") || (strtoupper($mVal) == "TRUE"));
	}

	function _isFalseVal($mVal) {
		return $this->isFalseVal($mVal);
	}

	function isFalseVal($mVal) {

		if(tx_ameosformidable::isRunneable($mVal)) {
			$mVal = $this->callRunneable(
				$mVal
			);
		}

		return (($mVal == FALSE) || (strtoupper($mVal) == "FALSE"));
	}


	function _defaultTrue($sPath, $aConf = -1) {
		return $this->defaultTrue($sPath, $aConf);
	}

	function defaultTrue($sPath, $aConf = -1) {

		if($this->_navConf($sPath, $aConf) !== FALSE) {
			return $this->isTrue($sPath, $aConf);
		} else {
			return TRUE;	// TRUE as a default
		}
	}


	function _defaultFalse($sPath, $aConf = -1) {
		return $this->defaultFalse($sPath, $aConf);
	}

	function defaultFalse($sPath, $aConf = -1) {

		if($this->_navConf($sPath, $aConf) !== FALSE) {
			return $this->isTrue($sPath, $aConf);
		} else {
			return FALSE;	// FALSE as a default
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mInfos: ...
	 * @return	[type]		...
	 */
	function _getExtRelPath($mInfos) {

		if(!is_array($mInfos)) {
			// should be object type

			if(isset($this)) {
				$aInfos = $this->_getInfosRenderletForType($mInfos);
			} else {
				$aInfos = tx_ameosformidable::_getInfosForType(
					$mInfos,
					$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["renderlets"]
				);
			}
		} else {
			$aInfos = $mInfos;
		}

		if($aInfos["BASE"] === TRUE) {
			return t3lib_extMgm::siteRelPath("ameos_formidable") . "api/base/" . $aInfos["EXTKEY"] . "/";
		} else {
			return t3lib_extMgm::siteRelPath($aInfos["EXTKEY"]);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mInfos: ...
	 * @return	[type]		...
	 */
	function _getExtPath($mInfos) {

		if(!is_array($mInfos)) {
			// should be object type

			if(isset($this)) {
				$aInfos = $this->_getInfosRenderletForType($mInfos);
			} else {
				$aInfos = tx_ameosformidable::_getInfosForType(
					$mInfos,
					$GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["renderlets"]
				);
			}
		} else {
			$aInfos = $mInfos;
		}

		if($aInfos["BASE"] === TRUE) {
			return PATH_formidable . "api/base/" . $aInfos["EXTKEY"] . "/";
		} else {
			return t3lib_extmgm::extPath($aInfos["EXTKEY"]);
		}

		return $extsrcpath;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$aTags: ...
	 * @param	[type]		$aUserobjParams: ...
	 * @return	[type]		...
	 */
	function _getCustomTags($aTags, $aUserobjParams = array()) {

		$aCustomTags = array(
			"values" => array(),
			"labels" => array()
		);

		if(is_array($aTags)) {
			reset($aTags);
			while(list(, $aTag) = each($aTags))
			{
				$label = array_key_exists("label", $aTag) ? $aTag["label"] : "";
				$name = $aTag["name"];
				$value = $aTag["value"];

				if(tx_ameosformidable::isRunneable($aTag["value"])) {
					$value = $this->callRunneable(
						$aTag["value"],
						$aUserobjParams
					);
				}

				if($value !== FALSE) {
					$aCustomTags["values"][$name] = $value;
					$aCustomTags["labels"][$name] = $label;
				}
			}
		}

		reset($aCustomTags);
		return $aCustomTags;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sName: ...
	 * @param	[type]		$mValue: ...
	 * @return	[type]		...
	 */
	function injectData($sName, $mValue) {
		$this->mayday("injectData is disabled");
		$this->_aInjectedData[$sName] = $mValue;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sName: ...
	 * @return	[type]		...
	 */
	function unsetInjectedData($sName) {

		if(array_key_exists($sName, $this->_aInjectedData)) {
			unset($this->_aInjectedData[$sName]);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function renderList() {

		if(!$this->bRendered) {
			$this->mayday("ATTEMPT TO CALL renderlist() BEFORE CALL TO render()");
		}

		if($this->oDataHandler->aObjectType["TYPE"] == "LISTER") {
			return $this->oDataHandler->sHtmlList;
		}

		return "";
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sStr: ...
	 * @return	[type]		...
	 */
	function _getSafeLock($sStr = FALSE) {

		if($sStr === FALSE) {
			$sStr = $this->conf["misc."]["safelockseed"];
		}

		return t3lib_div::shortMD5(
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . "||" . $sStr
		);
	}

	function getSafeLock($sStr = FALSE) {
		return $this->_getSafeLock($sStr);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sStr: ...
	 * @param	[type]		$sLock: ...
	 * @return	[type]		...
	 */

	 function _checkSafeLock($sStr = false, $sLock) {
		if($sStr === FALSE) {
			$sStr = $this->conf["misc."]["safelockseed"];
		}

		if(t3lib_div::shortMD5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . "||" . $sStr) === $sLock) {
			return TRUE;
		} else {
			return FALSE;
		}
	 }

	 function checkSafeLock($sStr = false,$sLock){
		$this->_checkSafeLock($sStr = false,$sLock);
	 }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$rRes: ...
	 * @return	[type]		...
	 */
	function _watchOutDB($rRes, $sSql = FALSE) {

		if(!is_resource($rRes) && $GLOBALS["TYPO3_DB"]->sql_error()) {

			$sMsg = "SQL QUERY IS NOT VALID";
			$sMsg .= "<br/><br />";
			$sMsg .= "<b>" . $GLOBALS["TYPO3_DB"]->sql_error() . "</b>";
			$sMsg .= "<br /><br />";

			if($sSql !== FALSE) {
				$sMsg .= $sSql;
			} else {
				$sMsg .= "<i style='margin-left: 20px;display: block;'>" . nl2br($GLOBALS["TYPO3_DB"]->debug_lastBuiltQuery) . "</i>";
			}

			$this->mayday($sMsg);
		}

		return $rRes;
	}

	// alias for __sendMail
	function sendMail($sAdresse, $sMessage, $sSubject, $sFromAd, $sFromName, $sReplyAd, $sReplyName, $aAttachPaths = array(), $iMediaRef=0, $sCcAd = '', $sCcName = '', $sCciAd = '', $sCciName = '') {
		if(is_object($this)) {
			return $this->__sendMail($sAdresse, $sMessage, $sSubject, $sFromAd, $sFromName, $sReplyAd, $sReplyName, $aAttachPaths, $iMediaRef, $sCcAd, $sCcName, $sCciAd, $sCciName);
		} else {
			return tx_ameosformidable::__sendMail($sAdresse, $sMessage, $sSubject, $sFromAd, $sFromName, $sReplyAd, $sReplyName, $aAttachPaths, $iMediaRef, $sCcAd, $sCcName, $sCciAd, $sCciName);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sAdresse: ...
	 * @param	[type]		$sMessage: ...
	 * @param	[type]		$sSubject: ...
	 * @param	[type]		$sFromAd: ...
	 * @param	[type]		$sFromName: ...
	 * @param	[type]		$sReplyAd: ...
	 * @param	[type]		$sReplyName: ...
	 * @param	[type]		$aAttachPaths: ...
	 * @return	[type]		...
	 */
	function __sendMail($sAdresse, $sMessage, $sSubject, $sFromAd, $sFromName, $sReplyAd, $sReplyName, $aAttachPaths = array(), $iMediaRef=0, $sCcAd = '', $sCcName = '', $sCciAd = '', $sCciName = '') {

		//require_once(PATH_t3lib . "class.t3lib_htmlmail.php");

		$sDebugSendMail = trim($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["debugSendMail"]);

		if(is_object($this)) {
			if(($sXmlDebugSendMail = $this->_navConf($this->sXpathToMeta . "debugsendmail")) !== FALSE) {
				$sDebugSendMail = $sXmlDebugSendMail;
			}
		}

		if(trim($sDebugSendMail) !== "") {
			$sAdresseOld = $sAdresse;
			$sAdresse = $sDebugSendMail;
			$sMessage .= "<hr />Formidable /meta/debugSendMail: This mail would be sent to " . $sAdresseOld;
		}

		$aListAdresse = t3lib_div::trimExplode(',', $sAdresse);
		$aAdresse = array();
		foreach($aListAdresse as $sAdresseItem) {
			$aAdresse[$sAdresseItem] = $sAdresseItem;
		}		

		$oMail = t3lib_div::makeInstance('t3lib_mail_Message');
		$oMail->setSubject($sSubject)->setTo($aAdresse)->setBody($sMessage, 'text/html');

		if(trim($sFromAd) !== '') {
			if(trim($sFromName) === '') {
				$sFromName = $sFromAd;
			}
			$oMail->setFrom(array($sFromAd => $sFromName));
		}
		
		if(trim($sReplyAd) !== '') {
			if(trim($sReplyName) === '') {
				$sReplyName = $sReplyAd;
			}
			$oMail->setReplyTo(array($sReplyAd => $sReplyName));
		}

		if(trim($sCcAd) !== '') {
			if(trim($sCcName) === '') {
				$sCcName = $sCcAd;
			}
			$oMail->setCc(array($sCcAd => $sCcName));
		}

		if(trim($sCciAd) !== '') {
			if(trim($sCciName) === '') {
				$sCciName = $sCciAd;
			}
			$oMail->setBcc(array($sCciAd => $sCciName));
		}

		if(is_array($aAttachPaths) && !empty($aAttachPaths)) {

			$oFile = t3lib_div::makeInstance("t3lib_basicFileFunctions");

			reset($aAttachPaths);
			while(list(, $sPath) = each($aAttachPaths)) {

				$sFilePath = t3lib_div::fixWindowsFilePath(
					$oFile->rmDoubleSlash(
						$sPath
					)
				);

				if(file_exists($sFilePath) && is_file($sFilePath) && is_readable($sFilePath)) {
					$oMail->attach(Swift_Attachment::fromPath($sFilePath)->setFilename(basename($sFilePath)));
				}
			}
		}
			
		$oMail->send();
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sVarName: ...
	 * @param	[type]		$aData: ...
	 * @param	[type]		$bMultiLines: ...
	 * @return	[type]		...
	 */
	function _arrayToJs($sVarName, $aData, $bMultiLines=FALSE) {

		// deprecated; use array2json instead
		$aJs = array();
		$aJs[] = "var " . $sVarName . " = new Array();";

		$aJsSafe = array(
			rawurlencode("-"),
			rawurlencode("_"),
			rawurlencode("."),
			rawurlencode("!"),
			rawurlencode("~"),
			rawurlencode("*"),
		);

		$aJsSafeReplace = array(
			"-",
			"_",
			".",
			"!",
			"~",
			"*",
		);

		reset($aData);
		while(list($sKey, $mVal) = each($aData)) {
			$aJs[] = $sVarName . "[\"" . $sKey . "\"]=unescape(\"" . str_replace(array("%96", "%92"), array("", "'"), rawurlencode($mVal)) . "\");";
		}

		if($bMultiLines) {
			return "\n" . implode("\n", $aJs) . "\n";
		} else {
			return implode("", $aJs);
		}
	}

	function arrayToRdtItems($aData, $sCaptionMap = FALSE, $sValueMap = FALSE) {
		// alias for _arrayToRdtItems()
		return $this->_arrayToRdtItems($aData, $sCaptionMap, $sValueMap);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$aData: ...
	 * @return	[type]		...
	 */
	function _arrayToRdtItems($aData, $sCaptionMap = FALSE, $sValueMap = FALSE) {

		$aItems = array();
		if(empty($aData) || !is_array($aData)) {
			$aData = array();
		}
		reset($aData);

		if($sCaptionMap !== FALSE && $sValueMap !== FALSE) {
			while(list($sKey, ) = each($aData)) {
				$aItems[] = array(
					"value" => $aData[$sKey][$sValueMap],
					"caption" => $aData[$sKey][$sCaptionMap],
				);
			}
		} else {
			while(list($sValue, $sCaption) = each($aData)) {
				$aItems[] = array(
					"value" => $sValue,
					"caption" => $sCaption
				);
			}
		}

		reset($aItems);
		return $aItems;
	}

	function _rdtItemsToArray($aData) {
		$aArray = array();

		reset($aData);
		while(list($iKey, ) = each($aData)) {
			$aArray[$aData[$iKey]["value"]] = $aData[$iKey]["caption"];
		}

		reset($aArray);
		return $aArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sCaptionKey: ...
	 * @param	[type]		$sValueKey: ...
	 * @param	[type]		$aData: ...
	 * @return	[type]		...
	 */
	function _arrayRowsToRdtItems($sCaptionKey, $sValueKey, $aData) {

		$aItems = array();

		reset($aData);
		while(list(, $aRow) = each($aData)) {
			$aItems[] = array(
				"value" => $aRow[$sValueKey],
				"caption" => $aRow[$sCaptionKey]
			);
		}

		reset($aItems);
		return $aItems;
	}

	function _tcaToRdtItems($aItems) {
		reset($aItems);
		return $this->_arrayToRdtItems($aItems, "0", "1");
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$iTemplateUid: ...
	 * @param	[type]		$iPageId: ...
	 * @return	[type]		...
	 */
	function _parseTsInBE($iTemplateUid, $iPageId) {

		require_once (PATH_t3lib."class.t3lib_page.php");
		require_once (PATH_t3lib."class.t3lib_tstemplate.php");
		require_once (PATH_t3lib."class.t3lib_tsparser_ext.php");


		global $tmpl;

		$tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");	// Defined global here!
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();

		// Gets the rootLine
		$sys_page = t3lib_div::makeInstance("t3lib_pageSelect");

		$tmpl->runThroughTemplates(
			$sys_page->getRootLine(
				$iPageId
			),
			$iTemplateUid
		);

		// This generates the constants/config + hierarchy info for the template.

//		$tplRow = $tmpl->ext_getFirstTemplate($pageId,$template_uid);	// Get the row of the first VISIBLE template of the page. whereclause like the frontend.

		$tmpl->generateConfig();

		$aConfig = $tmpl->setup["config."];
		reset($aConfig);
		return $aConfig;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$a1: ...
	 * @param	[type]		$a2: ...
	 * @return	[type]		...
	 */
	function array_add($a1, $a2) {

		if(is_array($a1)) {
			$aTemp = array(); $aTemp2 = array();
			reset($a1); reset($a2);

			while(list($key, $val) = each($a1)) {

				if($key != "type" && array_key_exists($key, $a2)) {

					$counter = 0;
					while(array_key_exists($key . "-" . $counter, $a2)) {
						$counter++;
					}

					$a2[$key . "-" . $counter] = $val;
				} else {
					$a2[$key] = $val;
				}
			}
		}
		reset($a2);
		return $a2;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function _perf() {
		return $this->end_tstamp - $this->start_tstamp;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function _makeJsonObj() {
		if(is_null($this->oJson)) {
			$this->oJson = t3lib_div::makeInstance("formidable_json");
			$this->oJson->use = SERVICES_JSON_LOOSE_TYPE;	// *decodes* objects as associative arrays
		}
	}

	# encodes an array to JSON string
	function array2json($aArray) {

		if($this->mayUsePHPJson() && $this->getAjaxCharset() === "UTF-8") {
			# PHP supports JSON_ENCODE (PHP 5.0+)

			if($this->mayPHPJsonForceObject()) {
				# PHP supports JSON_FORCE_OBJECT (PHP 5.3+)
				return json_encode($aArray, JSON_FORCE_OBJECT);
			} else {
				# PHP does not support JSON_FORCE_OBJECT (PHP 5.0.0 - 5.2.9)
				tx_ameosformidable::enforceObjectForJSON($aArray);
				return json_encode($aArray);
			}
		} else {
			$this->_makeJsonObj();
			return $this->oJson->encode($aArray);
		}
	}

	function getAjaxCharset() {

		$aAliases = array(
			"utf8" => "UTF-8",
			"utf-8" => "UTF-8",
			"UTF8" => "UTF-8",
			"latin1" => "ISO-8859-1",
			"LATIN1" => "ISO-8859-1",
		);

		if(($sCharset = $this->_navConf($this->sXpathToMeta . "ajaxcharset")) === FALSE) {
			$sCharset = "UTF-8";
		}

		if(array_key_exists($sCharset, $aAliases)) {
			return $aAliases[$sCharset];
		}

		return $sCharset;
	}

	function enforceObjectForJSON(&$aArray) {
		if(is_array($aArray)) {
			$aKeys = array_keys($aArray);
			reset($aKeys);
			while(list(,$sKey) = each($aKeys)) {
				if(is_array($aArray[$sKey])) {
					tx_ameosformidable::enforceObjectForJSON($aArray[$sKey]);
				}
			}

			$aArray = (object)$aArray;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sJson: ...
	 * @return	[type]		...
	 */
	function json2array($sJson) {
		$this->_makeJsonObj();
		return $this->oJson->decode($sJson);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$aArray: ...
	 * @param	[type]		$bFirst: ...
	 * @return	[type]		...
	 */
	function array2tree($aArray, $bFirst = TRUE) {

		$aNodes = array();
		while(list($sKey, $mVal) = each($aArray)) {
			if(is_array($mVal)) {
				$aNodes[] = array(
					"label" => $sKey,
					"nodes" => $this->array2tree($mVal, FALSE),
				);
			} else {

				$sLabel = (trim($sKey) !== "") ? trim($sKey) . ": " : "";

				$aNodes[] = array(
					"label" => $sLabel . trim($mVal)	// avoiding null values
				);
			}
		}

		if($bFirst && count(array_keys($aArray)) > 1) {
			return array(
				array(
					"label" => "Root",
					"nodes" => $aNodes,
				),
			);
		}

		return $aNodes;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sStr: ...
	 * @return	[type]		...
	 */
	function _strToHtmlChar($sStr) {

		$sOut = "";
		$iLen = strlen($sStr);

		for ($a=0; $a<$iLen; $a++) {
			$sOut .= '&#'.ord(substr($sStr, $a, 1)).';';
		}

		return $sOut;
	}

	function getMarkerForHeaderInjection() {
		if(
			isset($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["injectHeadersInContentAtMarker"]) &&
			($sHeaderMarker = trim($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["injectHeadersInContentAtMarker"])) !== ""
		) {
			return $sHeaderMarker;
		}

		return FALSE;
	}

	function mayUseStandardHeaderInjection() {
		return ($this->getMarkerForHeaderInjection() === FALSE) && ($this->manuallyInjectHeaders() === FALSE);
	}

	function manuallyInjectHeaders() {
		if(isset($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["injectHeadersManually"])) {
			// notnot returns real boolean
			return !!intval($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["injectHeadersManually"]);
		}

		return FALSE;
	}

	function additionalHeaderDataLocalStylesheet($sAbsFilePath, $sKey = FALSE, $bFirstPos = FALSE, $sBefore = FALSE, $sAfter = FALSE) {
		$sAbsWebPath = $this->toWebPath($sAbsFilePath);
		$this->additionalHeaderData(
			'<link rel="stylesheet" type="text/css" href="' . $sAbsWebPath . '" />',
			$sKey,
			$bFirstPos,
			$sBefore,
			$sAfter
		);
	}

	function shouldCompileAndGzipLocalScripts() {
		return $this->oJs->gziped();
	}

	function additionalHeaderDataLocalScript($sAbsFilePath, $sKey = FALSE, $bFirstPos = FALSE, $sBefore = FALSE, $sAfter = FALSE, $bMayCompile = TRUE) {
		if($this->useJs() === FALSE) {
			return FALSE;
		}
		
		if($bMayCompile === TRUE && $this->shouldCompileAndGzipLocalScripts()) {
			$this->additionalHeaderData(
				"local:script:" . $sAbsFilePath,
				$sKey,
				$bFirstPos,
				$sBefore,
				$sAfter
			);
		} else {
			$sAbsWebPath = $this->toWebPath($sAbsFilePath);
			$this->additionalHeaderData(
				'<script type="text/javascript" src="' . $sAbsWebPath . '"></script>',
				$sKey,
				$bFirstPos,
				$sBefore,
				$sAfter
			);
/*			$sLoadedJsManifest = '["' . $sAbsWebPath . '"]';
			$sJsManifest = 'if(Formidable) Formidable.declareLoadedScripts(' . $sLoadedJsManifest . ');';
			$this->additionalHeaderData(
				'<script type="text/javascript" src="' . $sAbsWebPath . '"></script>
				 <script type="text/javascript">' . $sJsManifest . '</script>',
				$sKey,
				$bFirstPos,
				$sBefore,
				$sAfter
			);	*/
		}
	}

	function additionalHeaderData($sData, $sKey = FALSE, $bFirstPos = FALSE, $sBefore = FALSE, $sAfter = FALSE) {
		$sData = trim($sData);

		if(TYPO3_MODE === "FE") {
			if($this->mayUseStandardHeaderInjection()) {
				$aHeaders =& $GLOBALS["TSFE"]->additionalHeaderData;
			} else {
				$aHeaders =& $this->aHeadersWhenInjectNonStandard;
			}
		} else {
			$aHeaders =& $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["context"]["be_headerdata"];
		}

		if($sKey === FALSE) {
			$sKey = "nokey-" . count($aHeaders);
		}

		$sKey = "formidable:" . $sKey;

		if($sBefore !== FALSE) {
			$sBefore = "formidable:" . $sBefore;
		}

		if($sAfter !== FALSE) {
			$sAfter = "formidable:" . $sAfter;
		}

		if($this->__getEnvExecMode() === "EID") {
			if($sKey === FALSE) {
				$this->aHeadersAjax[] = $sData;
			} else {
				$this->aHeadersAjax[$sKey] = $sData;
			}
		} else {
			if($sKey === FALSE) {
				if($bFirstPos === TRUE) {
					$aHeaders = array(rand() => $sData) + $aHeaders;
				} elseif($sBefore !== FALSE || $sAfter !== FALSE) {
					if($sBefore !== FALSE) {
						$bBefore = TRUE;
						$sLookFor = $sBefore;
					} else {
						$bBefore = FALSE;
						$sLookFor = $sAfter;
					}

					$aHeaders = $this->array_insert(
						$aHeaders,
						$sLookFor,
						array(count($aHeaders) => $sData),
						$bBefore
					);
				} else {
					$aHeaders[] = $sData;
				}
			} else {

				if($sKey == "ameosformidable_tx_rdttinymce") {
					if(!in_array($sData, $aHeaders)) {
						array_unshift($aHeaders, $sData);
					}
				} else {
					if($bFirstPos === TRUE) {
						$aHeaders = array($sKey => $sData) + $aHeaders;
					} elseif($sBefore !== FALSE || $sAfter !== FALSE) {

						if($sBefore !== FALSE) {
							$bBefore = TRUE;
							$sLookFor = $sBefore;
						} else {
							$bBefore = FALSE;
							$sLookFor = $sAfter;
						}

						$aHeaders = $this->array_insert(
							$aHeaders,
							$sLookFor,
							array($sKey => $sData),
							$bBefore
						);
					} else {
						$aHeaders[$sKey] = $sData;
					}
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function &getAdditionalHeaderData() {
		if(TYPO3_MODE === "FE") {
			return $GLOBALS["TSFE"]->additionalHeaderData;
		} else {
			return $GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["context"]["be_headerdata"];
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$str: ...
	 * @param	[type]		$ext: ...
	 * @param	[type]		$sDesc: ...
	 * @return	[type]		...
	 */
	function inline2TempFile($str, $ext, $sDesc="")	{

		$output = "";

		if(is_string($str)) {
			if($sDesc != "") {
				$sDesc = "\n\n<!-- FORMIDABLE: " . str_replace(array("<!--", "-->"), "", $sDesc) . " -->";
			}

			// Create filename / tags:
			$script = '';
			$sHash = substr(md5($str),0,10);
			$sFirst = $sHash{0};

			switch($ext) {
				case 'js': {
					if(!file_exists(PATH_site."typo3temp/ameos_formidable/js/")) {
						@mkdir(PATH_site."typo3temp/ameos_formidable/js/");
					}

					if(!file_exists(PATH_site."typo3temp/ameos_formidable/js/" . $sFirst . "/")) {
						@mkdir(PATH_site."typo3temp/ameos_formidable/js/" . $sFirst . "/");
					}

					$script = 'typo3temp/ameos_formidable/js/' . $sFirst . '/javascript_'.$sHash.'.js';
					$output = $sDesc . "\n" . '<script type="text/javascript" src="'.htmlspecialchars(t3lib_div::getIndpEnv("TYPO3_SITE_URL") . $script).'"></script>' . "\n\n";
					break;
				}
				case 'css': {
					if(!file_exists(PATH_site."typo3temp/ameos_formidable/css/")) {
						@mkdir(PATH_site."typo3temp/ameos_formidable/css/");
					}

					if(!file_exists(PATH_site."typo3temp/ameos_formidable/css/" . $sFirst . "/")) {
						@mkdir(PATH_site."typo3temp/ameos_formidable/css/" . $sFirst . "/");
					}

					$script = 'typo3temp/ameos_formidable/css/' . $sFirst . '/stylesheet_'.$sHash.'.css';
					$output = $sDesc . "\n" . '<link rel="stylesheet" type="text/css" href="'.htmlspecialchars(t3lib_div::getIndpEnv("TYPO3_SITE_URL") . $script).'" />' . "\n\n";
					break;
				}
			}

			// Write file:
			if($script){
				if(!@is_file(PATH_site.$script)) {
					t3lib_div::writeFile(PATH_site.$script, $str);
				}
			}
		}

		return $output;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sKey: ...
	 * @return	[type]		...
	 */
	function issetAdditionalHeaderData($sKey) {
		$sKey = "formidable:" . $sKey;
		if(TYPO3_MODE === "FE") {
			return isset($GLOBALS['TSFE']->additionalHeaderData[$sKey]);
		} else {
			return isset($this->_oParent->doc->inDocStylesArray[$sKey]);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sPath: ...
	 * @return	[type]		...
	 */
	function toWebPath($sPath) {

		if(t3lib_div::isFirstPartOfStr(strtolower($sPath), "http://") || t3lib_div::isFirstPartOfStr(strtolower($sPath), "https://")) {
			return $sPath;
		}

		return tx_ameosformidable::_removeEndingSlash(t3lib_div::getIndpEnv("TYPO3_SITE_URL")) . "/" . tx_ameosformidable::_removeStartingSlash(tx_ameosformidable::toRelPath($sPath));
	}

	/**
	 * Converts an absolute or EXT path to a relative path plus a leading slash.
	 *
	 * @param	string		$sPath: the path to convert, must be either an
	 * 						absolute path or a path starting with "EXT:"
	 * @return	string		$sPath converted to a relative path plus a leading
	 * 						slash
	 */
	function toRelPath($sPath) {
		if (substr($sPath, 0, 4) === "EXT:") {
			$sPath = t3lib_div::getFileAbsFileName($sPath);
		}

		$sDocRoot = PATH_site;
		$sPath = str_replace($sDocRoot, "", $sPath);

		if ($sPath{0} != "/") {
			$sPath = "/" . $sPath;
		}

		return $sPath;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sPath: ...
	 * @return	[type]		...
	 */
	function toServerPath($sPath) {
		// removes the leading slash so the path _really_ is relative
		$sPath = tx_ameosformidable::_removeStartingSlash(
			tx_ameosformidable::toRelPath($sPath)
		);

		if (file_exists($sPath) && is_dir($sPath) && ($sPath{(strlen($sPath) - 1)} !== "/")) {
			$sPath .= "/";
		}

		$sDocRoot = PATH_site;
		return tx_ameosformidable::_removeEndingSlash($sDocRoot) . "/" . $sPath;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sPath: ...
	 * @return	[type]		...
	 */
	function _removeStartingSlash($sPath) {
		return ($sPath{0} === "/") ? substr($sPath, 1) : $sPath;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sPath: ...
	 * @return	[type]		...
	 */
	function _removeEndingSlash($sPath) {
		if(substr($sPath, -1) === '/') {
			$sPath = substr($sPath, 0, -1);
		}
		return $sPath;
	}

	function _trimSlashes($sPath) {
		return $this->_removeStartingSlash(
			$this->_removeEndingSlash(
				$sPath
			)
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sPath: ...
	 * @return	[type]		...
	 */
	function isAbsServerPath($sPath) {
		$sServerRoot = t3lib_div::getIndpEnv("TYPO3_DOCUMENT_ROOT");
		return (substr($sPath, 0, strlen($sServerRoot)) === $sServerRoot);
	}

	function isAbsPath($sPath) {
		return $sPath{0} === "/";
	}

	function isAbsWebPath($sPath) {
		return (
			(substr(strtolower($sPath), 0, 7) === "http://") ||
			(substr(strtolower($sPath), 0, 8) === "https://")
		);
	}

	function getNextRecordInSelection($sFormid, $sSearchid, $iCurrentId = FALSE) {
		$aData = &$GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$sFormid][$sSearchid]['infos'];
		if($iCurrentId === FALSE) {
			$iCurrentId = $this->oDataHandler->currentParentEntryId();
		}

		$iNextid = FALSE;
		$iListUid = current($aData['list']);
		while($iListUid) {
			if(intval($iListUid) === intval($iCurrentId)) {
				while($iListNextid = next($aData['list'])) {
					if(in_array($iListNextid, $aData['selection'])) {
						$iNextid = $iListNextid;
						break;
					}
				}
				break;
			}
			$iListUid = next($aData['list']);
		}

		reset($aData['list']);
		return $iNextid;
	}

	function getPreviousRecordInSelection($sFormid, $sSearchid, $iCurrentId = FALSE) {
		$aData = &$GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$sFormid][$sSearchid]['infos'];
		if($iCurrentId === FALSE) {
			$iCurrentId = $this->oDataHandler->currentParentEntryId();
		}

		$iPrevid = FALSE;
		$iListUid = end($aData['list']);
		while($iListUid) {
			if(intval($iListUid) === intval($iCurrentId)) {
				while($iListPrevid = prev($aData['list'])) {
					if(in_array($iListPrevid, $aData['selection'])) {
						$iPrevid = $iListPrevid;
						break;
					}
				}
				break;
			}
			$iListUid = prev($aData['list']);
		}
		reset($aData['list']);
		return $iPrevid;
	}

	function getNextRecordInList($sFormid, $sSearchid, $iCurrentId = FALSE) {
		$aData = &$GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$sFormid][$sSearchid]['infos'];
		if($iCurrentId === FALSE) {
			$iCurrentId = $this->oDataHandler->currentParentEntryId();
		}

		$iNextid = FALSE;
		if(is_array($aData)) {
			if(array_key_exists('list', $aData) && is_array($aData['list'])) {
				$iListUid = current($aData['list']);
				while($iListUid) {
					if(intval($iListUid) === intval($iCurrentId)) {
						$iNextid = next($aData['list']);
						break;
					}
					$iListUid = next($aData['list']);
				}

				reset($aData['list']);
			}
		}
		return $iNextid;
	}

	function getPreviousRecordInList($sFormid, $sSearchid, $iCurrentId = FALSE) {
		$aData = &$GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$sFormid][$sSearchid]['infos'];
		if($iCurrentId === FALSE) {
			$iCurrentId = $this->oDataHandler->currentParentEntryId();
		}
		$iPrevid = FALSE;
		if(is_array($aData)) {
			if(array_key_exists('list', $aData) && is_array($aData['list'])) {
				$iListUid = end($aData['list']);
				while($iListUid) {
					if(intval($iListUid) === intval($iCurrentId)) {
						$iPrevid = prev($aData['list']);
						break;
					}
					$iListUid = prev($aData['list']);
				}
			
				reset($aData['list']);
			}
		}
		return $iPrevid;
	}

	function getNextRecord($sFormid, $sSearchid, $iCurrentId = FALSE) {
		if($this->selectionIsEmpty($sFormid, $sSearchid)) {
			return $this->getNextRecordInList($sFormid, $sSearchid, $iCurrentId);
		} else {
			return $this->getNextRecordInSelection($sFormid, $sSearchid, $iCurrentId);
		}
	}

	function getPreviousRecord($sFormid, $sSearchid, $iCurrentId = FALSE) {
		if($this->selectionIsEmpty($sFormid, $sSearchid)) {
			return $this->getPreviousRecordInList($sFormid, $sSearchid, $iCurrentId);
		} else {
			return $this->getPreviousRecordInSelection($sFormid, $sSearchid, $iCurrentId);
		}
	}

	function selectionIsEmpty($sFormid, $sSearchid) {
		$aData = &$GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$sFormid][$sSearchid]['infos'];
		return empty($aData['selection']) === TRUE;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function __getEnvExecMode() {

		if(TYPO3_MODE == "BE") {
			return "BE";
		} elseif(TYPO3_MODE == "FE") {
			if(is_null(t3lib_div::_GP('eID'))) {
				return "FE";
			} else {
				return "EID";
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function __virtualizeFE($aConfig = FALSE) {

		if(
			array_key_exists("TYPO3_CONF_VARS", $GLOBALS) &&
			array_key_exists("SC_OPTIONS", $GLOBALS["TYPO3_CONF_VARS"]) &&
			array_key_exists("t3lib/class.t3lib_userauth.php", $GLOBALS["TYPO3_CONF_VARS"]["SC_OPTIONS"]) &&
			array_key_exists("logoff_post_processing", $GLOBALS["TYPO3_CONF_VARS"]["SC_OPTIONS"]["t3lib/class.t3lib_userauth.php"]) &&
			($iPos = array_search("tx_phpmyadmin_utilities->pmaLogOff", $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'])) !== FALSE
		) {
			// deactivating the logoff-hook of PMA, that changes the session_name (!) and causes the session to be incorrectly saved at the end of PHP execution
			unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][$iPos]);
		}

		if (!defined('PATH_tslib')) {
			if (@is_dir(PATH_site.TYPO3_mainDir.'sysext/cms/tslib/')) {
				define('PATH_tslib', PATH_site.TYPO3_mainDir.'sysext/cms/tslib/');
			} elseif (@is_dir(PATH_site.'tslib/')) {
				define('PATH_tslib', PATH_site.'tslib/');
			}
		}
/*
		require_once(PATH_tslib.'class.tslib_content.php');
		require_once(PATH_t3lib.'class.t3lib_timetrack.php');
		require_once(PATH_tslib.'class.tslib_fe.php');
		require_once(PATH_t3lib.'class.t3lib_page.php');
		require_once(PATH_t3lib.'class.t3lib_userauth.php');
		require_once(PATH_tslib.'class.tslib_feuserauth.php');
		require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
		require_once(PATH_t3lib.'class.t3lib_cs.php');
*/
		$GLOBALS["TT"] = new t3lib_timeTrack;
		$GLOBALS["CLIENT"] = t3lib_div::clientInfo();

		// ***********************************
		// Create $TSFE object (TSFE = TypoScript Front End)
		// Connecting to database
		// ***********************************

		if(t3lib_div::int_from_ver(TYPO3_version) >= t3lib_div::int_from_ver('4.3.0')) {
			// makeInstanceClassName is deprecated since TYPO3 4.3.0

			$GLOBALS["TSFE"] = t3lib_div::makeInstance(
				'tslib_fe',
				$GLOBALS["TYPO3_CONF_VARS"],
				t3lib_div::_GP('id'),
				t3lib_div::_GP('type'),
				t3lib_div::_GP('no_cache'),
				t3lib_div::_GP('cHash'),
				t3lib_div::_GP('jumpurl'),
				t3lib_div::_GP('MP'),
				t3lib_div::_GP('RDCT')
			);
		} else {
			$temp_TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
			$GLOBALS["TSFE"] = new $temp_TSFEclassName(
				$GLOBALS["TYPO3_CONF_VARS"],
				t3lib_div::_GP('id'),
				t3lib_div::_GP('type'),
				t3lib_div::_GP('no_cache'),
				t3lib_div::_GP('cHash'),
				t3lib_div::_GP('jumpurl'),
				t3lib_div::_GP('MP'),
				t3lib_div::_GP('RDCT')
			);
		}

		//$GLOBALS["TSFE"]->forceTemplateParsing = TRUE;

		//$GLOBALS['TSFE']->absRefPrefix = "/";
		$GLOBALS["TSFE"]->connectToDB();
		$GLOBALS["TSFE"]->initFEuser();
		$GLOBALS["TSFE"]->determineId();

		# catching TYPO34.5+ exceptions (when registering cache handler)

		if($sExecMode !== "BE" && $sExecMode !== "FE") {
			$GLOBALS["TSFE"]->getCompressedTCarray();
		}

		$GLOBALS["TSFE"]->initTemplate();
		$GLOBALS["TSFE"]->getFromCache();


		if(!is_array($GLOBALS["TSFE"]->config)) {
			$GLOBALS["TSFE"]->config = array();
			$GLOBALS["TSFE"]->forceTemplateParsing = TRUE;
		}

		if($aConfig === FALSE) {
			$GLOBALS["TSFE"]->getConfigArray();
		} else {
			$GLOBALS["TSFE"]->config = $aConfig;
		}

		$GLOBALS["TSFE"]->convPOSTCharset();
		$GLOBALS["TSFE"]->settingLanguage();
		$GLOBALS["TSFE"]->settingLocale();

		$GLOBALS["TSFE"]->cObj = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function _storeFormInSession() {

		$aOurTSFEConfig = $GLOBALS["TSFE"]->config;

		$this->checkPoint(
			array(
				"before-hibernation",
			),
			array(
				"tsfe_config" => &$aOurTSFEConfig,
			)
		);

		$this->cleanBeforeSession();

		if($this->__getEnvExecMode() === "BE") {
			$sLang = $GLOBALS["LANG"]->lang;
		} else {
			$sLang = $GLOBALS["TSFE"]->lang;
		}

		$aTempT3Var = $GLOBALS["T3_VAR"];
		$aTempT3Var["callUserFunction"] = array();
		if(array_key_exists("tx_realurl", $aTempT3Var["callUserFunction_classPool"])) {
			$aTempT3Var["callUserFunction_classPool"]["tx_realurl"]->pObj = null;
		}

		$GLOBALS["_SESSION"]["ameos_formidable"]["hibernate"][$this->_getSessionDataHashKey()] = array(
			"object" => serialize($this),
			"xmlpath" => $this->_xmlPath,
			"runningobjects" => $this->__aRunningObjects,
			"sys_language_uid" => intval($GLOBALS["TSFE"]->sys_language_uid),
			"sys_language_content" => intval($GLOBALS["TSFE"]->sys_language_content),
			"tsfe_config" => $aOurTSFEConfig,
			"pageid" => $GLOBALS["TSFE"]->id,
			"lang" => $sLang,
			"spamProtectEmailAddresses" => $GLOBALS["TSFE"]->spamProtectEmailAddresses,
			"spamProtectEmailAddresses_atSubst" => $GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst'],
			"spamProtectEmailAddresses_lastDotSubst" => $GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_lastDotSubst'],
			"parent" => FALSE,
			"formidable_tsconfig" => $GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."],
			"be_user" => isset($GLOBALS["BE_USER"]) ? serialize($GLOBALS["BE_USER"]) : FALSE,
			"t3_var" => serialize($aTempT3Var),
		);

		if($this->bStoreParentInSession === TRUE) {

			$sClass = get_class($this->_oParent);
			$aParentConf = $GLOBALS["TSFE"]->tmpl->setup["plugin."][$sClass . "."];

			$GLOBALS["_SESSION"]["ameos_formidable"]["hibernate"][$this->_getSessionDataHashKey()]["parent"] = array(
				"classpath" => $this->_removeEndingSlash(t3lib_div::getIndpEnv("TYPO3_DOCUMENT_ROOT")) . "/" . $this->_removeStartingSlash($aParentConf["includeLibs"]),
			);
		}
	}

	function cleanBeforeSession() {

		$this->oDataHandler->cleanBeforeSession();
		$this->oRenderer->cleanBeforeSession();

		reset($this->aORenderlets);
		$aKeys = array_keys($this->aORenderlets);
		reset($aKeys);
		while(list(, $sKey) = each($aKeys)) {
			if(isset($this->aORenderlets[$sKey]) && !$this->aORenderlets[$sKey]->hasParent()) {
				$this->aORenderlets[$sKey]->cleanBeforeSession();
			}
		}

		reset($this->aODataSources);
		while(list($sKey, ) = each($this->aODataSources)) {
			$this->aODataSources[$sKey]->cleanBeforeSession();
		}

		reset($this->aCodeBehinds["php"]);
		while(list($sKey, ) = each($this->aCodeBehinds["php"])) {
			unset($this->aCodeBehinds["php"][$sKey]["object"]->oForm);
			$this->aCodeBehinds["php"][$sKey]["object"] = serialize($this->aCodeBehinds["php"][$sKey]["object"]);
			unset($this->aCB[$sKey]);
			#debug($this->aCodeBehinds["php"][$sKey]["object"]);
		}

		unset($this->oSandBox->oForm);
		unset($this->_oParent);
		unset($this->oParent);
		unset($this->oJs->oForm);
		unset($this->oMajixEvent);
		unset($this->cObj);

		$this->oSandBox = serialize($this->oSandBox);
		$this->aDebug = array();
		$this->_aSubXmlCache = array();
		$this->aInitTasksUnobtrusive = array();
		$this->aInitTasks = array();
		$this->aInitTasksOutsideLoad = array();
		$this->aInitTasksAjax = array();
		$this->aPostInitTasks = array();
		$this->aPostInitTasksAjax = array();
		$this->aOnloadEvents = array(
			"client" => array(),
			"ajax" => array(),
		);
		$this->aCurrentRdtStack = array();

		if(!isset($GLOBALS["_SESSION"]["ameos_formidable"]["tsconf"])) {
			$GLOBALS["_SESSION"]["ameos_formidable"]["tsconf"] = $this->conf;
		}

		unset($this->conf);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function _clearFormInSession() {
		if(is_array($GLOBALS["_SESSION"]["ameos_formidable"]["hibernate"]) && array_key_exists($this->_getSessionDataHashKey(), $GLOBALS["_SESSION"]["ameos_formidable"]["hibernate"])) {
			unset($GLOBALS["_SESSION"]["ameos_formidable"]["hibernate"][$this->_getSessionDataHashKey()]);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function _getSessionDataHashKey() {
		$sSafeLock = $GLOBALS["TSFE"]->id . "||" . $this->formid;
		if($this->useFHash()) {
			$sSafeLock.= '||' . $this->getFHash();
		}

		return $this->_getSafeLock($sSafeLock);
		/*
		return $this->_getSafeLock(
			$GLOBALS["TSFE"]->id . "||" . $this->formid	// (unique but stable accross refreshes)
		);*/
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$oRequest: ...
	 * @return	[type]		...
	 */
	function handleAjaxRequest(&$oRequest) {

		if($oRequest->aRequest["servicekey"] == "ajaxevent") {

			$sEventId = $oRequest->aRequest["eventid"];
			if(!array_key_exists($sEventId, $this->aAjaxEvents)) {
				$oRequest->denyService("Unknown Event ID " . $sEventId . "(" . $this->formid . ")");
			}

			$this->oMajixEvent =& $oRequest;
			$oThrower =& $oRequest->getThrower();

			if($oThrower !== FALSE && $this->isTrueVal($this->aAjaxEvents[$sEventId]["event"]["syncvalue"])) {
				$oThrower->setValue($oRequest->aRequest["params"]["sys_syncvalue"]);
				unset($oRequest->aRequest["params"]["sys_syncvalue"]);
			}

			if(!empty($oRequest->aRequest["context"])) {
				reset($oRequest->aRequest["context"]);
				while(list($sKey,) = each($oRequest->aRequest["context"])) {
					if($oRdt =& $this->rdt($sKey) !== FALSE) {
						$oRdt->handleRefreshContext($oRequest->aRequest["context"][$sKey]);
					}
				}
			}

			if(tx_ameosformidable::isRunneable($this->aAjaxEvents[$sEventId]["event"])) {
				if($oRequest->aRequest["trueargs"] !== FALSE) {
					$aArgs =& $oRequest->aRequest["trueargs"];
					$iNbParams = count($aArgs);
				} else {
					$iNbParams = 0;
				}

				if($oThrower !== FALSE) {
					$oObject =& $oThrower;
				} else {
					$oObject =& $this;
				}

				// logic: for back-compat, when trueargs is empty, we pass parameters as we always did
					// if trueargs set, we replicate arguments

				switch($iNbParams) {
					case 0: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $oRequest->aRequest["params"]); break;}
					case 1: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0]); break;}
					case 2: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0], $aArgs[1]); break;}
					case 3: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0], $aArgs[1], $aArgs[2]); break;}
					case 4: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3]); break;}
					case 5: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4]); break;}
					case 6: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5]); break;}
					case 7: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6]); break;}
					case 8: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7]); break;}
					case 9: { return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8]); break;}
					case 10:{ return $oObject->callRunneable($this->aAjaxEvents[$sEventId]["event"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8], $aArgs[9]); break;}
					default: {
						$this->mayday("CallRunneable: can not declare more than 10 arguments.");
						break;
					}
				}
			}
		} elseif($oRequest->aRequest["servicekey"] == "ajaxservice") {

			$sServiceId = $oRequest->aRequest["serviceid"];
			if(!array_key_exists($sServiceId, $this->aAjaxServices)) {
				$oRequest->denyService("Unknown Service ID " . $sServiceId);
			}

			if(tx_ameosformidable::isRunneable($this->aAjaxServices[$sServiceId]["definition"])) {

				if($oRequest->aRequest["trueargs"] !== FALSE) {
					$aArgs =& $oRequest->aRequest["trueargs"];
					$iNbParams = count($aArgs);
				} else {
					$iNbParams = 0;
				}

				// if trueargs set, we replicate arguments
				switch($iNbParams) {
					case 0: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"]); break;}
					case 1: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0]); break;}
					case 2: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0], $aArgs[1]); break;}
					case 3: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0], $aArgs[1], $aArgs[2]); break;}
					case 4: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3]); break;}
					case 5: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4]); break;}
					case 6: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5]); break;}
					case 7: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6]); break;}
					case 8: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7]); break;}
					case 9: { $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8]); break;}
					case 10:{ $mRes = $this->callRunneable($this->aAjaxServices[$sServiceId]["definition"], $aArgs[0], $aArgs[1], $aArgs[2], $aArgs[3], $aArgs[4], $aArgs[5], $aArgs[6], $aArgs[7], $aArgs[8], $aArgs[9]); break;}
					default: {
						$this->mayday("CallRunneable: can not declare more than 10 arguments.");
						break;
					}
				}
			}

			return $this->array2json($mRes);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sFormId: ...
	 * @return	[type]		...
	 */
	function &getFromContext($sFormId) {
		$sExecMode = $this->__getEnvExecMode();
		if($sExecMode === "EID") {
			// ajax context
			// getting form in session
			if(array_key_exists($sFormId, $GLOBALS["_SESSION"]["ameos_formidable"]["hibernate"])) {
				return tx_ameosformidable::unHibernate(
					$GLOBALS["_SESSION"]["ameos_formidable"]["hibernate"][$this->_getSessionDataHashKey()]
				);
			} else {
				return FALSE;
			}
		} else {

		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$aHibernation: ...
	 * @return	[type]		...
	 */
	function &unHibernate(&$aHibernation) {
		tx_ameosformidable::loadRunningObjects($aHibernation);
		tx_ameosformidable::loadParent($aHibernation);

		if($aHibernation["be_user"] !== FALSE) {
			$GLOBALS["BE_USER"] = unserialize($aHibernation["be_user"]);
		}
//		require_once(t3lib_extMgm::extPath('sv') . 'class.tx_sv_authbase.php');
		$GLOBALS["T3_VAR"] = unserialize($aHibernation["t3_var"]);

		$oForm = unserialize($aHibernation["object"]);
		$oForm->cObj = t3lib_div::makeInstance('tslib_cObj');
		$oForm->conf =& $GLOBALS["_SESSION"]["ameos_formidable"]["tsconf"];

		$oForm->_includeSandBox();	// rebuilding class
		$oForm->oSandBox = unserialize($oForm->oSandBox);
		$oForm->oSandBox->oForm =& $oForm;

		$oForm->oDataHandler->oForm =& $oForm;
		$oForm->oRenderer->oForm =& $oForm;
		$oForm->buildCodeBehinds();

		return $oForm;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$aHibernation: ...
	 * @return	[type]		...
	 */
	function loadRunningObjects(&$aHibernation) {
		$aRObjects =& $aHibernation["runningobjects"];
		reset($aRObjects);
		while(list(, $aObject) = each($aRObjects)) {
			tx_ameosformidable::_loadObject(
				$aObject["internalkey"],
				$aObject["objecttype"]
			);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$aHibernation: ...
	 * @return	[type]		...
	 */
	function loadParent(&$aHibernation) {
		if($aHibernation["parent"] !== FALSE) {
			$sClassPath = $aHibernation["parent"]["classpath"];
			require_once($sClassPath);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sMethod: ...
	 * @param	[type]		$mData: ...
	 * @param	[type]		$sFormId: ...
	 * @param	[type]		$sElementId: ...
	 * @return	[type]		...
	 */
	function majixStatic($sMethod, $mData, $sFormId, $sElementId) {

		$aExecuter = $this->buildMajixExecuter(
			$sMethod,
			$mData,
			$sElementId
		);

		$aExecuter["formid"] = $sFormId;

		return $aExecuter;
	}

	function majixExecJs($sJs, $aParams = array()) {

		$aContext = array();

		$aListData = $this->oDataHandler->getListData();
		if(!empty($aListData)) {
			$aContext["currentrow"] = $aListData["uid"];
		}

		return $this->buildMajixExecuter(
			"execJs",
			$sJs,
			$this->formid,
			array(
				"context" => $aContext,
				"params" => $aParams
			)
		);
	}

	function addMajixOnload($aMajixTasks) {
		$this->aOnloadEvents["client"][] = array(
			"name" => "Event added by addMajixOnload()",
			"eventdata" => $aMajixTasks
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sMethod: ...
	 * @param	[type]		$mData: ...
	 * @param	[type]		$sElementId: ...
	 * @return	[type]		...
	 */
	function buildMajixExecuter($sMethod, $mData, $sElementId, $mDataBag = array()) {
		return array(
			"method" => $sMethod,
			"data" => $mData,
			"object" => $sElementId,
			"databag" => $mDataBag
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function majixSubmit() {

		return $this->buildMajixExecuter(
			"submitFull",
			null,
			$this->formid
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function majixSubmitRefresh() {
		return $this->majixRefresh();
	}

	function majixSubmitSearch() {
		return $this->buildMajixExecuter(
			"submitSearch",
			null,
			$this->formid
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function majixRefresh() {

		return $this->buildMajixExecuter(
			"submitRefresh",
			null,
			$this->formid
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sName: ...
	 * @return	[type]		...
	 */
	function majixScrollTo($sName) {

		return $this->buildMajixExecuter(
			"scrollTo",
			$sName,
			$this->formid
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sUrl: ...
	 * @return	[type]		...
	 */
	function majixSendToPage($sUrl) {
		return $this->buildMajixExecuter(
			"sendToPage",
			$sUrl,
			$this->formid
		);
	}

	function majixForceDownload($sFilePath) {

		if(!$this->isAbsWebPath($sFilePath)) {
			$sWebPath = $this->toWebPath($sFilePath);
		} else {
			$sWebPath = $sFilePath;
		}

		$aParams = array();
		$aParams["url"] = $sWebPath;

		return $this->buildMajixExecuter(
			"openPopup",
			$aParams,
			$this->formid
		);
	}

	function majixOpenPopup($mParams) {

		$aParams = array();

		if(is_string($mParams)) {
			$aParams["url"] = $mParams;
		} else {
			$aParams = $mParams;
		}

		return $this->buildMajixExecuter(
			"openPopup",
			$aParams,
			$this->formid
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sMessage: ...
	 * @return	[type]		...
	 */
	function majixDebug($sMessage) {
		return $this->buildMajixExecuter(
			"debug",
			$this->_viewMixed($sMessage),
			$this->formid
		);
	}

	function majixRequestNewI18n($sTableName, $iRecordUid, $iLangUid) {
		return $this->buildMajixExecuter(
			"requestNewI18n",
			array(
				"tablename" => $sTableName,
				"recorduid" => $iRecordUid,
				"languid" => $iLangUid,
				"hash" => $this->_getSafeLock("requestNewI18n" . ":" . $sTableName . ":" . $iRecordUid . ":" . $iLangUid),
			),
			$this->formid
		);
	}

	function majixRequestEdition($iRecordUid, $sTableName = FALSE) {

		if($sTableName === FALSE) {
			$sTableName = $this->oDataHandler->tablename();
		}

		if($sTableName !== FALSE) {
			return $this->buildMajixExecuter(
				"requestEdition",
				array(
					"tablename" => $sTableName,
					"recorduid" => $iRecordUid,
					"hash" => $this->_getSafeLock("requestEdition" . ":" . $sTableName . ":" . $iRecordUid),
				),
				$this->formid
			);
		}
	}

	function majixExecOnNextPage($aTask) {
		return $this->buildMajixExecuter(
			"execOnNextPage",
			$aTask,
			$this->formid
		);
	}

	function majixGetLocalAnchor() {
		return $this->buildMajixExecuter(
			"getLocalAnchor",
			array(),
			"tx_ameosformidable"
		);
	}

	function majixValidate($mErrorMethod = FALSE, $mValidMethod = FALSE) {
		$this->bAjaxValidation = TRUE;
		$this->clearValidation();
		$this->validateEverything();

		if(empty($this->_aValidationErrorsInfos)) {
			if($mValidMethod !== FALSE) {
				return $this->_callCodeBehind(
					array('exec' => $mValidMethod, '__value' => ''),
					$this->_aValidationErrorsInfos
				);
			}

			if($mErrorMethod !== FALSE) {
				return $this->_callCodeBehind(
					array('exec' => $mErrorMethod, '__value' => ''),
					$this->_aValidationErrorsInfos
				);
			}

			return $this->majixSubmit();

		} else {

			if($mErrorMethod !== FALSE) {
				return $this->_callCodeBehind(
					array('exec' => $mErrorMethod, '__value' => ''),
					$this->_aValidationErrorsInfos
				);
			}

			return $this->buildMajixExecuter(
				"displayErrors",
				$this->_aValidationErrorsInfos,
				$this->formid
			);
		}

	}
	
	function majixAlert($sMessage) {
		return $this->buildMajixExecuter(
			"alert",
			$this->getLLLabel($sMessage),
			"tx_ameosformidable"
		);
	}
	
	function majixPrint() {
		return $this->buildMajixExecuter(
			"print",
			array(),
			"tx_ameosformidable"
		);
	}

	function majixSetCookie($sCookieName, $sCookieValue, $aOptions = array()) {
		# valid options are:
		#	"expires": integer, cookie life expressed in days; default equals session duration
		#	"domain": string, domain on which to restrict cookie broadcast; default broadcasts only on domain but not on subdomains; to allow subdomains, define as ".yourdomain.com" (note the prefixing dot)
		#	"path": string, path on which to restrict cookie broadcast; default allows all pathes on given domain
		#	"secure": boolean, wheter the cookie should be secure or not

		$aFilteredOptions = array(
			"name" => $sCookieName,
			"value" => $sCookieValue,
			"expires" => "",
			"domain" => "",
			"path" => "",
			"secure" => FALSE,
		);

		if(array_key_exists("expires", $aOptions)) {
			$aFilteredOptions["expires"] = intval($aOptions["expires"]);
		}

		if(array_key_exists("domain", $aOptions)) {
			$aFilteredOptions["domain"] = trim($aOptions["domain"]);
		}

		if(array_key_exists("path", $aOptions)) {
			$aFilteredOptions["path"] = trim($aOptions["path"]);
		}

		if(array_key_exists("secure", $aOptions) && is_bool($aOptions["secure"]) || is_integer($aOptions["secure"])) {
			$aFilteredOptions["path"] = !(intval($aOptions["secure"]) === 0);	# TRUE if anything else than "", 0 or FALSE
		}

		return $this->buildMajixExecuter(
			"setCookie",
			$aFilteredOptions,
			"tx_ameosformidable"
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sUrl: ...
	 * @return	[type]		...
	 */
	function xhtmlUrl($sUrl) {
		return str_replace("&", "&amp;", $sUrl);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sUrl: ...
	 * @return	[type]		...
	 */
	function sendToPage($sUrl) {
		if(is_numeric($sUrl)) {
			$sUrl = $this->toWebPath(
				$this->cObj->typolink_URL(array(
					"parameter" => $sUrl
				))
			);
		}

		header("Location: " . $sUrl);
		die();
	}

	function reloadCurrentUrl() {
		$this->sendToPage(
			t3lib_div::getIndpEnv("TYPO3_REQUEST_URL")
		);
	}

	function forceDownload($sAbsPath, $sFileName = FALSE) {

		if($sFileName === FALSE) {
			$sFileName = basename($sAbsPath);
		}

		header("Expires: Mon, 01 Jul 1997 00:00:00 GMT"); // some day in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Content-type: application/x-download");
		header("Content-Disposition: attachment; filename=" . $sFileName);
		header("Content-Transfer-Encoding: binary");
		fpassthru(fopen($sAbsPath, "r"));
		die();
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mMixed: ...
	 * @return	[type]		...
	 */
	function isUserObj($mMixed) {
		return /*faster than is-array*/((array) $mMixed === $mMixed) && isset($mMixed["userobj"]);
	}

	function hasCodeBehind($mMixed) {
		 return /*faster than is-array*/((array) $mMixed === $mMixed) && isset($mMixed["exec"]);
	}

	static function isRunneable($mMixed) {
		/* no sub-method calls for speed */
		return (((array) $mMixed === $mMixed) && isset($mMixed["exec"])) || (((array) $mMixed === $mMixed) && isset($mMixed["userobj"]));
	}

	function callRunneable($mMixed) {
		// NOTE: for userobj, only ONE argument may be passed
		$aArgs = func_get_args();

		if($this->hasCodeBehind($mMixed)) {
			// it's a codebehind
			$mRes = call_user_func_array(array($this, "_callCodeBehind"), $aArgs);
			return $mRes;
		} elseif($this->isUserObj($mMixed)) {
			if(array_key_exists(1, $aArgs)) {
				$aParams = $aArgs[1];
			} else {
				$aParams = array();
			}
			return $this->_callUserObj(
				$mMixed,
				$aParams
			);
		}

		return $mMixed;
	}

	function clearSearchForm($sSearchRdtName, $sFormId = FALSE) {
		if($sFormId === FALSE) {
			$sFormId = $this->formid;
		}

		if($sFormId === $this->formid) {
			if(array_key_exists($sSearchRdtName, $this->aORenderlets)) {
				$this->aORenderlets[$sSearchRdtName]->clearFilters();
				return;
			}
		}

		// else
		$GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$sFormId][$sSearchRdtName]["criterias"] = array();
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$oModule: ...
	 * @return	[type]		...
	 */
	function backendHeaders(&$oModule) {
		$sHeaders = "<!-- FORMIDABLE JS FWK begin-->\n" . implode("\n", self::getAdditionalHeaderData()) . "\n<!-- FORMIDABLE JS FWK end-->\n<!--###POSTJSMARKER###-->";
		$sForm = "<!-- DEFAULT FORM NEUTRALIZED BY FORMIDABLE " . $oModule->doc->form . "-->";

		if($oModule->content === "") {
			$oModule->doc->form = $sForm . $sHeaders;
		} else {
			$oModule->content = str_replace(
				array(
					"<!--###POSTJSMARKER###-->",
					$oModule->doc->form,
				),
				array(
						$sHeaders,
						$sForm,
				),
				$oModule->content
			);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sStr: ...
	 * @return	[type]		...
	 */
	function convertAccents($sStr) {
		return html_entity_decode(
			preg_replace(
				'/&([a-zA-Z])(uml|acute|grave|circ|tilde|slash|ring|elig|cedil);/',
				'$1',
				htmlentities($sStr, ENT_COMPAT, "UTF-8")
			)
		);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sStr: ...
	 * @return	[type]		...
	 */
	function removeNonAlnum($sStr) {
		// removes everything but a-z, A-Z, 0-9
		return preg_replace(
			"/[^<>[:alnum:]]/",
			"",
			$sStr
		);
	}

	function generatePassword($iLength = 6) {

		$aLetters = array(
			"cons" => "aeiouy",
			"voy" => "bcdfghjklmnpqrstvwxz",
		);

		$sPassword = "";
		$sType = "cons";

		for($k=0; $k < $iLength; $k++) {
			$sType = ($sType === "cons") ? "voy" : "cons";
			$iNbLetters = strlen($aLetters[$sType]);
			$sPassword .= $aLetters[$sType]{rand(0, ($iNbLetters - 1))};
		}

		return $sPassword;
	}

	function isRenderlet(&$mObj) {
		if(is_object($mObj) && is_a($mObj, "formidable_mainrenderlet")) {
			return TRUE;
		}

		return FALSE;
	}

	function archiveAjaxRequest($aRequest) {
		array_push(
			$this->aAjaxArchive,
			$aRequest
		);
	}

	function getPreviousAjaxRequest() {
		if(!empty($this->aAjaxArchive)) {
			return $this->aAjaxArchive[(count($this->aAjaxArchive) - 1)];
		}

		return FALSE;
	}

	function getPreviousAjaxParams() {
		if(($aPrevRequest = $this->getPreviousAjaxRequest()) !== FALSE) {
			return $aPrevRequest["params"];
		}

		return FALSE;
	}

	function div_autoLogin($iUserId) {

		if($this->__getEnvExecMode() === "FE") {
			$rSql = $GLOBALS["TYPO3_DB"]->exec_SELECTquery(
				"*",
				"fe_users",
				"uid='" . $iUserId . "'"
			);

			if(($aUser = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
				$GLOBALS["TSFE"]->fe_user->createUserSession($aUser);
				$GLOBALS["TSFE"]->fe_user->loginSessionStarted = TRUE;
				$GLOBALS["TSFE"]->fe_user->user = $GLOBALS["TSFE"]->fe_user->fetchUserSession();

				return TRUE;
			}
		}

		return FALSE;
	}

	function wrapImplode($sWrap, $aData, $sGlue = "") {
		$aRes = array();
		reset($aData);
		while(list($iKey,) = each($aData)) {
			if(is_string($aData[$iKey])) {
				$aRes[] = str_replace("|", $aData[$iKey], $sWrap);
			}
		}

		return implode($sGlue, $aRes);
	}

	function str_split($text, $split = 1){
		//place each character of the string into an array
		$array = array();
		for ($i=0; $i < strlen($text); $i++){
			$key = "";
			for ($j = 0; $j < $split; $j++){
				$key .= $text[$i+$j];
			}
			$i = $i + $j - 1;
			array_push($array, $key);
		}
		return $array;
	}

	function div_stripSomeTags($sContent, $sTags, $bPreserveInnerHtml = FALSE, $sReplaceEndTagsBy = FALSE) {
		$aTags = t3lib_div::trimExplode(",", $sTags);
		reset($aTags);
		while(list(, $sTag) = each($aTags)) {
			$sContent = tx_ameosformidable::div_stripSomeTag($sContent, $sTag, $bPreserveInnerHtml, $sReplaceEndTagsBy);
		}

		return $sContent;
	}

	function div_stripSomeTag($sContent, $sTag, $bPreserveInnerHtml = FALSE, $sReplaceEndTagsBy = FALSE) {
		$sEmptyMarker = md5(rand());

		$sPattern = ":<" . $sTag . ".*?>(.*?)</" . $sTag . ">[\s]*:si";

		if($bPreserveInnerHtml === FALSE) {
			$sReplacementPattern = "###" . $sEmptyMarker . "###";
		} else {
			$sReplacementPattern = "\$1";
		}

		if($sReplaceEndTagsBy !== FALSE) {
			$sReplacementPattern .= $sReplaceEndTagsBy;
		}

		$sContent = preg_replace($sPattern, $sReplacementPattern, $sContent);
		$sPattern = ":<(.*?)[\s]+?.*?>[\s]*?\#\#\#" . $sEmptyMarker . "\#\#\#[\s]*?</\\1.?>[\s]*:si";
		do {
			$sContentBefore = $sContent;
			$sContent = preg_replace(
				$sPattern,
				$sReplacementPattern,
				$sContent
			);
		} while($sContentBefore !== $sContent);

		$sContent = str_replace("###" . $sEmptyMarker . "###", "", $sContent);
		return $sContent;
	}

	function div_rteToHtml($sRteHtml, $sTable = "", $sColumn = "") {
		$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();

		$aConfig = $pageTSConfig['RTE.']['default.']['FE.'];
		$aSpecConf['rte_transform']['parameters'] = array(
			"flag" => "rte_enabled",
			"mode" => "ts"
		);

		$aDataArray = array(
			$sColumn => $sRteHtml,
		);


		return \TYPO3\CMS\Backend\Rte\AbstractRte::transformContent(
			'rte',
			$sRteHtml,
			$sTable,
			$sColumn,
			$aDataArray,
			$aSpecConf,
			$aConfig,
			'',
			0
		);
	}

	function div_mkdir_deep($destination,$deepDir) {
		$allParts = t3lib_div::trimExplode('/',$deepDir,1);
		$root = '';
		foreach($allParts as $part)	{
			$root.= $part.'/';
			if (!is_dir($destination.$root))	{
				t3lib_div::mkdir($destination.$root);
				if (!@is_dir($destination.$root))	{
					return 'Error: The directory "'.$destination.$root.'" could not be created...';
				}
			}
		}
	}

	function div_mkdir_deep_abs($deepDir) {
		return $this->div_mkdir_deep("/", $deepDir);
	}

	function devlog($sMessage, $iPad = 0, $bCallStack = FALSE) {

		$sCallStack = "";
		if($bCallStack === TRUE) {
			$sCallStack = t3lib_div::debug_trail() . "\n";
		}

		error_log($sCallStack . str_repeat("\t", $iPad) . $sMessage . "\n", 3, $this->toServerPath("EXT:ameos_formidable/dev.log.txt"));

	}

	function div_xml2array_plain($data) {
		return $this->div_xml2array(
			$data,
			$keepAttribs=1,
			$caseFolding=0,
			$skipWhite=0,
			$prefix=FALSE,
			$numeric='n',
			$index='index',
			$type='type',
			$base64='base64',
			$php5defCharset='UTF-8',
			TRUE
		);
	}

	function div_camelize($sString) {
		$sCamelized = "";

		$aParts = explode("-", $sString);
		$iLen = count($aParts);
		if($iLen == 1) {
			return $aParts[0];
		}

		if($sString{0} === "-") {
			$sCamelized = strtoupper($aParts[0]{0}) . substr($aParts[0], 1);
		} else {
			$sCamelized = $aParts[0];
		}

		for($i=1; $i < $iLen; $i++) {
			$sCamelized .= strtoupper($aParts[$i]{0}) . substr($aParts[$i], 1);
		}

		return $sCamelized;
	}

	function div_camelizeKeys($aData) {
		$aRes = array();
		reset($aData);
		while(list($sKey,) = each($aData)) {
			$aRes[$this->div_camelize($sKey)] = $aData[$sKey];
		}

		reset($aRes);
		return $aRes;
	}

	function div_arrayToCsvFile($aData, $sFilePath = FALSE, $sFSep=";", $sLSep="\r\n", $sStringWrap="\"") {
		if($sFilePath === FALSE) {
			$sFilePath = t3lib_div::tempnam("csv-" . strftime("%Y.%m.%d-%Hh%Mm%Ss" . "-")) . ".csv";
		} else {
			$sFilePath = tx_ameosformidable::toServerPath($sFilePath);
		}

		tx_ameosformidable::file_writeBin(
			$sFilePath,
			tx_ameosformidable::div_arrayToCsvString(
				$aData,
				$sFSep,
				$sLSep,
				$sStringWrap
			),
			FALSE
		);

		return $sFilePath;
	}

	function div_arrayToCsvString($aData, $sFSep=";", $sLSep="\r\n", $sStringWrap="\"") {
		// CSV class taken from http://snippets.dzone.com/posts/show/3128
		require_once(PATH_formidable . "res/shared/php/csv/class.csv.php");

		$oCsv = new CSV(
			$sFSep,
			$sLSep,
			$sStringWrap
		);
		$oCsv->setArray($aData);
		return $oCsv->getContent();
	}

	function div_getHeadersForUrl($sUrl) {

		$aRes = array();

		if(($sHeaders = t3lib_div::getURL($sUrl, 2)) !== FALSE) {
			$aHeaders = t3lib_div::trimExplode("\n", $sHeaders);

			reset($aHeaders);
			while(list($sKey, $sLine) = each($aHeaders)) {
				if($sKey === 0) {
					$aRes["Status"] = $sLine;
				} else {
					if(trim($sLine) !== "") {
						$aHeaderLine = explode(":", $sLine);
						$sHeaderKey = trim(array_shift($aHeaderLine));
						$sHeaderVal = trim(implode(":", $aHeaderLine));

						$aRes[$sHeaderKey] = $sHeaderVal;
					}
				}
			}
		}

		reset($aRes);
		return $aRes;
	}

	function &rdt($sName) {
		if(array_key_exists($sName, $this->aORenderlets)) {
			return $this->aORenderlets[$sName];
		}

		$aKeys = array_keys($this->aORenderlets);
		reset($aKeys);
		while(list(, $sKey) = each($aKeys)) {
			if($this->aORenderlets[$sKey]->getName() === $sName) {
				return $this->aORenderlets[$sKey];
			}
		}

		return FALSE;
	}

	function &cb($sName) {
		if(array_key_exists($sName, $this->aCB)) {
			return $this->aCB[$sName];
		}

		return FALSE;
	}

	function &ds($sName) {
		if(array_key_exists($sName, $this->aODataSources)) {
			return $this->aODataSources[$sName];
		}

		return FALSE;
	}

	/**
	 * Method div_xml2array taken from the Developer API (api_macmade).
	 *
	 * (c) 2004 macmade.net
	 * All rights reserved
	 *
	 * The goal of this API is to provide to the Typo3 developers community
	 * some useful functions, to help in the process of extension development.
	 *
	 * It includes functions, for frontend, backend, databases and miscellaneous
	 * development.
	 *
	 * It's not here to replace any of the existing Typo3 core class or
	 * function. It just try to complete them by providing a quick way to
	 * develop extensions.
	 *
	 * Please take a look at the manual for a complete description of this API.
	 *
	 * @author		Jean-David Gadina (macmade@gadlab.net)
	 * @version		2.3
	 */

	/**
	 * Convert XML data to an array.
	 *
	 * This function is used to convert an XML data to a multi-dimensionnal array,
	 * representing the structure of the data.
	 *
	 * This function is based on the Typo3 array2xml function, in t3lib_div. It basically
	 * does the same, but has a few more options, like the inclusion of the xml tags arguments
	 * in the output array. This function also has support for same multiple tag names
	 * inside the same XML element, which is not the case with the core Typo3 function. In that
	 * specific case, the array keys are suffixed with '-N', where N is a numeric value.
	 *
	 * SPECIAL NOTE: This function can be called without the API class instantiated.
	 *
	 * @param	$data		The XML data to process
	 * @param	$keepAttribs		If set, also includes the tag attributes in the array (with key 'xml-attribs')
	 * @param	$caseFolding		XML parser option: case management
	 * @param	$skipWhite		XML parser option: white space management
	 * @param	$prefix		A tag prefix to remove
	 * @param	$numeric		Keep only the numeric value for a tag prefixed with this argument (default is 'n')
	 * @param	$index		Set the tag name to an alternate value found in the tag arguments (default is 'index')
	 * @param	$type		Force the tag value to a special type, found in the tag arguments (default is 'type')
	 * @param	$base64		Decode the tag value from base64 if the specified tag argument is present (default is 'base64')
	 * @param	$php5defCharset		The default charset to use with PHP5
	 * @return	An		array with the XML structure, or an XML error message if the data is not valid
	 */
	function div_xml2array($data,$keepAttribs=1,$caseFolding=0,$skipWhite=0,$prefix=FALSE,$numeric='n',$index='index',$type='type',$base64='base64',$php5defCharset='UTF-8', $bPlain=FALSE) {

		// Function ID
		$funcId = 'div_xml2array';

		// Storage
		$xml = array();
		$xmlValues = array();
		$xmlIndex = array();
		$stack = array(array());

		// Counter
		$stackCount = 0;

		// New XML parser
		$parser = xml_parser_create();

		// Case management option
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,$caseFolding);

		// White space management option
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,$skipWhite);

		// Support for PHP5 charset detection
		if ((double) phpversion() >= 5) {

			// Find the encoding parameter in the XML declaration
			@preg_match('^[[:space:]]*<\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"',substr($data,0,200),$result);

			// Check result
			if ($result[1]) {

				// Charset found in the XML declaration
				$charset = $result[1];

			} else if ($TYPO3_CONF_VARS['BE']['forceCharset']) {

				// Force charset to Typo3 configuration if defined
				$charset = $TYPO3_CONF_VARS['BE']['forceCharset'];

			} else {

				// Default charset
				$charset = $php5defCharset;
			}

			// Charset management option
			xml_parser_set_option($parser,XML_OPTION_TARGET_ENCODING,$charset);
		}

		// Parse XML structure
		xml_parse_into_struct($parser,$data,$xmlValues,$xmlIndex);

		// Error in XML
		if (xml_get_error_code($parser)) {

			// Error
			$error = 'XML error: ' . xml_error_string(xml_get_error_code($parser)) . ' at line ' . xml_get_current_line_number($parser);

			// Free XML parser
			xml_parser_free($parser);

			// Return error
			return $error;

		} else {

			// Free XML parser
			xml_parser_free($parser);

			// Counter for multiple same keys
			$sameKeyCount = array();

			// Process each value
			while(list($key,$val) = each($xmlValues)) {

				if($bPlain === FALSE) {

					// lower-case on tagName
					$val['tag'] = strtolower($val['tag']);

					// lower-case on attribute name
					if(array_key_exists("attributes", $val)) {
						$val["attributes"] = array_change_key_case($val["attributes"], CASE_LOWER);
					}
				}


				// Get the tag name (without prefix if specified)
				$tagName = ($prefix && t3lib_div::isFirstPartOfStr($val['tag'], $prefix)) ? substr($val['tag'],strlen($prefix)) : $val['tag'];

				if($bPlain === FALSE) {
					$aTagName = explode(
						":",
						$tagName
					);

					if(sizeof($aTagName) > 1) {

						// debug($aTagName[1]);

						$type = $aTagName[1];
						$tagName = $aTagName[0];

						$val['attributes']['type'] = strtoupper($type);
					}
				}


				// Support for numeric tags (<nXXX>)
				$numTag = (substr($tagName,0,1) == $numeric) ? substr($tagName,1) : FALSE;

				// Check if tag is a real numeric value
				if ($numTag && !strcmp(intval($numTag),$numTag)) {

					// Store only numeric value
					$tagName = intval($numTag);
				}

				// Support for alternative value
				if (strlen($val['attributes'][$index])) {

					// Store alternate value
					$tagName = $val['attributes'][$index];
				}

				// Check if array key already exists
				if (array_key_exists($tagName,$xml)) {

					// Check if the current level has already a key counter
					if (!isset($sameKeyCount[$val['level']])) {

						// Create array
						$sameKeyCount[$val['level']] = 0;
					}

					// Increase key counter
					$sameKeyCount[$val['level']]++;

					// Change tag name to avoid overwriting existing values
					$tagName = $tagName . '-' . $sameKeyCount[$val['level']];
				}

				//debug($tagName);





				// Check tag type
				switch($val['type']) {

					// Open tag
					case 'open':

						// Storage
						$xml[$tagName] = array();



						// Memorize content
						$stack[$stackCount++] = $xml;

						// Reset main storage
						$xml = array();

						// Support for tag attributes
						if ($keepAttribs && $val['attributes']) {
//							echo t3lib_div::view_array($val['attributes'], "attributes");
							$xml = $val['attributes'];
						}


					break;

					// Close tag
					case 'close':

						// Memorize array
						$tempXML = $xml;

						// Decrease the stack counter
						$xml = $stack[--$stackCount];

						// Go to the end of the array
						end($xml);

						// Add temp array
						if(!empty($tempXML)) {
							$xml[key($xml)] = $tempXML;
						}

						// Unset temp array
						unset($tempXML);

						// Unset key counters for the child level
						unset($sameKeyCount[$val['level'] + 1]);
						reset($xml);
					break;

					// Complete tag
					case 'complete':

						// Check for base64
						if ($val['attributes']['base64']) {

							// Decode value
							$xml[$tagName] = base64_decode($val['value']);

						} else {

							// Add value (force string)
							if(
								array_key_exists("value", $val) != ""
								&&
								$tagName != "0"
							) {
								$xml[$tagName] = (string) $val['value'];
							} else {
								$xml[$tagName] = "";
							}

							// Support for value types
							switch((string)$val['attributes'][$type]) {

								// Integer
								case 'integer':

									// Force variable type
									$xml[$tagName] = (integer) $xml[$tagName];

								break;

								// Double
								case 'double':

									$xml[$tagName] = (double) $xml[$tagName];

								break;

								// Boolean
								case 'boolean':

									// Force type
									$xml[$tagName] = (bool) $xml[$tagName];

								break;

								// Array
								case 'array':

									// Create an empty array
									$xml[$tagName] = array();

								break;
							}
						}

						// Memorize tag value
//						$tempTagValue = $xml[$tagName];

						// New array with value
//						$xml[$tagName] = $tempTagValue;

						// Support for tag attributes
						if ($keepAttribs && $val['attributes']) {

							// Store attributes
							if(is_array($xml[$tagName])) {
								$xml[$tagName] = array_merge($xml[$tagName], $val['attributes']);
							} else {
								$xml[$tagName] = array_merge(
									$val['attributes'],
									array(
										"__value" => $val["value"]
									)
								);
							}

							// Unset memorized value
							unset($tempTagValue);
						}

					break;
				}
			}

			// Return the array of the XML root element
			return $xml;
		}
	}

	function getMajix() {
		return $this->oMajixEvent;
	}

	function &getMajixSender() {
		return $this->getMajixThrower();
	}

	function &getMajixThrower() {
		if($this->oMajixEvent !== FALSE) {
			return $this->oMajixEvent->getThrower();
		}

		return FALSE;
	}

	function pushCurrentRdt(&$oRdt) {
		$this->aCurrentRdtStack[] =& $oRdt;
	}

	function &getCurrentRdt() {
		if(empty($this->aCurrentRdtStack)) {
			return FALSE;
		}

		return $this->aCurrentRdtStack[(count($this->aCurrentRdtStack) - 1)];
	}

	function &pullCurrentRdt() {
		if(empty($this->aCurrentRdtStack)) {
			return FALSE;
		}

		return array_pop($this->aCurrentRdtStack);
	}

	function getFormId() {
		return $this->formid;
	}

	function userIsAuthentified() {
		return (intval($GLOBALS['TSFE']->fe_user->user['uid']) > 0);
	}

	function garbageCollector() {

		$iNow = time();
		$iGCProbability = intval($GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ext_conf_template"]["gc_probability"]);
		$iGCMaxAge = intval($GLOBALS["TYPO3_CONF_VARS"]["EXTCONF"]["ameos_formidable"]["ext_conf_template"]["gc_jsmaxage"]);

		if($iGCProbability <= 0 || $iGCMaxAge <= 0) {
			return;
		}

		if(($iNow % $iGCProbability) !== 0)	{
			return;
		}

		// executing garabage collector
			// on temporary JS files

		$sPath = PATH_site . "typo3temp/ameos_formidable/js/";

		if(!file_exists($sPath)) {
			return;
		}

		$aFiles = t3lib_div::getAllFilesAndFoldersInPath(
			array(),
			$sPath,
			'',	// $extList
			0,	// $regDirs
			1	// $recursivityLevels
		);


		if(!empty($aFiles)) {
			reset($aFiles);
			while(list(, $sFile) = each($aFiles)) {
				if(($iNow - fileatime($sFile)) > $iGCMaxAge) {
					@unlink($sFile);
				}
			}

			clearstatcache();
		}
	}

	function isDomEventHandler($sHandler) {
		$aList = array(
			"onabort",  		# Refers to the loading of an image that is interrupted.
			"onblur", 			# Refers to an element losing the focus of the web browser.
			"onchange", 		# Refers to a content is change, usually inside a text input box.
			"onclick", 			# Refers to when an object is clicked.
			"ondblclick", 		# Refers to when an object is double clicked.
			"onerror", 			# Refers to when an error occurs.
			"onfocus", 			# Refers to when an element is given focus.
			"onkeydown", 		# Refers to when a keyboard key is pressed down.
			"onkeypress", 		# Refers to when a keyboard key is pressed and/or held down.
			"onkeyup", 			# Refers to when a keyboard key is released.
			"onload", 			# Refers to when a web page or image loads.
			"onmousedown", 		# Refers to when the mouse button is pressed down.
			"onmousemove", 		# Refers to when the mouse is moved.
			"onmouseout", 		# Refers to when the mouse is moved away from an element.
			"onmouseover", 		# Refers to when the mouse moves over an element.
			"onmouseup", 		# Refers to when the mouse button is released.
			"onreset", 			# Refers to when a reset button is clicked.
			"onresize", 		# Refers to when a window is resized.
			"onselect", 		# Refers to when an element is selected.
			"onsubmit", 		# Refers to when a submit button is clicked.
			"onunload",     	# document is unloaded
			"oncut",			# something is cut
			"oncopy",			# something is copied
			"onpaste",			# something is pasted
			"onbeforecut",		# before something is cut
			"onbeforecopy",		# before something is copied
			"onbeforepaste",	# before something is pasted
		);

		return in_array(strtolower(trim($sHandler)), $aList);
	}

	function isAjaxValidation() {
		return $this->bAjaxValidation === TRUE;
	}

	function useFHash() {
		return $this->defaultFalse($this->sXpathToMeta . 'usehash');
	}

	function getFHash() {
		if($this->sFHash === FALSE) {
			$this->sFHash = $this->generateFHash();
		}

		return $this->sFHash;
	}

	function generateFHash() {
		$aGet = $this->oDataHandler->_G();
		if(isset($aGet['fhash'])) {
			return $aGet['fhash'];
		} else {
			return t3lib_div::shortMD5(time());
		}
	}


	// declare*() methods below are meant to smoothen transition between 0.7.x and 1.0/2.0+
	function declareDataHandler() {
		if(TYPO3_MODE === "BE" && t3lib_extMgm::isLoaded("seminars")) {
			echo "<br /><br /><b>Warning</b>: you are using Formidable version <i>" . $GLOBALS["EM_CONF"]["ameos_formidable"]["version"] . "</i> with Seminars, requiring version 0.7.0.<br />";
		}
	}

	function declareActionlet() {}
	function declareRenderer() {}
	function declareRenderlet() {}
	function declareValidator() {}

	function smartMayday_XmlFile($sPath, $sMessage = FALSE) {

		$sVersion = $this->_getVersion();
		$sXml =<<<XMLFILE
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<formidable version="{$sVersion}"
	xmlns:renderlet="http://formidable.typo3.ug/xmlns/{$sVersion}/renderlet">

	<head>
		<name>New FML file</name>
		<form formid="myform"/>
	</head>

	<body>
		<renderlet:TEXT name="mytxt" label="Some text field"/>
	</body>

</formidable>
XMLFILE;

		if($sMessage === FALSE) {
			$sMessage = "FORMIDABLE CORE - The given FML file path (<b>" . $sPath . "</b>) does not exist";
		}

		$sXml = htmlspecialchars($sXml);
		$sMayday =<<<ERRORMESSAGE

	<div>{$sMessage}</div>
	<hr />
	<div>This basic FML might be useful: </div>
	<br />
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4; font-family: Courier; padding-left: 20px;'>
		<br />
<pre>{$sXml}</pre>
		<br /><br />
	</div>

ERRORMESSAGE;

		$this->mayday($sMayday);
	}

	function smartMayday_CBJavascript($sPath, $sClassName, $sMessage = FALSE) {

		$sJs =<<<XMLFILE
Formidable.Classes.{$sClassName} = Formidable.inherit({
	init: function() {
		// your init code here
	},
	doSomething: function() {
		// your implementation here
	}

}, Formidable.Classes.CodeBehindClass);
XMLFILE;

		if($sMessage === FALSE) {
			$sMessage = "FORMIDABLE CORE - The given javascript CodeBehind file path (<b>" . $sPath . "</b>) does not exist or is empty.";
		}

		$sJs = htmlspecialchars($sJs);
		$sMayday =<<<ERRORMESSAGE

	<div>{$sMessage}</div>
	<hr />
	<div>This basic JS codebehind might be useful</div>
	<br />
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4; font-family: Courier; padding-left: 20px;'>
		<br />
<pre>{$sJs}</pre>
		<br /><br />
	</div>

ERRORMESSAGE;

		$this->mayday($sMayday);
	}
	
	static function db_quoteStr($sStr) {
		if(!empty($sStr) && is_string($sStr)) {
			return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $sStr);
		}
		
		return $sStr;
	}
	
	static function db_listQuery($field, $value) {
		$value = (string) $value;
		if (strpos(',', $value) !== FALSE) {
			throw new InvalidArgumentException('$value must not contain a comma (,) in $this->db_listQuery() !', 1294585862);
		}
		$pattern = tx_ameosformidable::db_quoteStr($value);
		$where = 'FIND_IN_SET(\'' . $pattern . '\',' . $field . ')';
		return $where;
	}
}

	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.tx_ameosformidable.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.tx_ameosformidable.php"]);
	}
?>
