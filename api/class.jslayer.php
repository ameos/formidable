<?php

	class formidable_jslayer {

		var $oForm = null;
		var $bLoadScriptaculous = FALSE;
		var $bLoadScriptaculousDragDrop = FALSE;
		var $bLoadScriptaculousBuilder = FALSE;
		var $bLoadtooltip = FALSE;
		var $bLoadLightbox = FALSE;

		var $bLoadJQueryDragDrop = FALSE;
		var $bLoadJQueryEffects = FALSE;

		var $sJQueryUIPath = "";
		var $sJQueryUIMinifiedJsPath = "";
		var $sJQueryUIFatJsPath = "";
		var $ajQueryPluginsToLoad = array();

		function _init(&$oForm) {
			require_once(PATH_tslib . "class.tslib_pagegen.php");
			$this->oForm =& $oForm;

			$this->sJQueryUIPath = $this->oForm->sExtPath . "res/jsfwk/jquery/core+ui/";
			$this->sJQueryUIMinifiedJsPath = $this->sJQueryUIPath . "development-bundle/ui/minified/";
			$this->sJQueryUIFatJsPath = $this->sJQueryUIPath . "development-bundle/ui/";
		}

		function mayLoadJsFramework() {
			if($this->oForm->__getEnvExecMode() == "BE") {
				return TRUE;
			}

			if($this->oForm->defaultTrue($this->oForm->sXpathToMeta . "loadjsframework") !== TRUE) {
				return FALSE;
			}

			if(
				isset($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["loadJsFramework"]) &&
				intval($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["loadJsFramework"]) === 0
			) {
				return FALSE;
			}

			return TRUE;
		}

		function mayLoadPrototype() {
			if($this->oForm->__getEnvExecMode() == "BE") {
				return TRUE;
			}

			if($this->oForm->defaultTrue($this->oForm->sXpathToMeta . "loadprototype") !== TRUE) {
				return FALSE;
			}

			if(
				isset($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["loadPrototype"]) &&
				intval($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["loadPrototype"]) === 0
			) {
				return FALSE;
			}

			return TRUE;
		}

		function mayLoadPrototypeAddons() {
			if($this->oForm->__getEnvExecMode() == "BE") {
				return TRUE;
			}

			if($this->oForm->defaultTrue($this->oForm->sXpathToMeta . "loadprototypeaddons") !== TRUE) {
				return FALSE;
			}

			if(
				isset($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["loadPrototypeAddons"]) &&
				intval($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["loadPrototypeAddons"]) === 0
			) {
				return FALSE;
			}

			return TRUE;
		}

		function mayLoadScriptaculous() {
			if($this->oForm->defaultTrue($this->oForm->sXpathToMeta . "mayloadscriptaculous") !== TRUE) {
				return FALSE;
			}

			if(
				isset($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["mayLoadScriptaculous"]) &&
				intval($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["mayLoadScriptaculous"]) === 0
			) {
				return FALSE;
			}

			return TRUE;
		}

		function _includeOnceLibs() {

			if($this->mayLoadJsFramework() === TRUE) {
				if(!isset($GLOBALS["FORMIDABLE_LIBS_INCLUDED"])) {

					$this->oForm->additionalHeaderData(
						"<!-- Formidable core: loading -->",
						"tx_ameosformidable_first"
					);

					if($this->minified() === TRUE) {
						$this->_includeMinifiedJs();
					} else {
						$this->_includeNonMinifiedJs();
					}

					$GLOBALS["FORMIDABLE_LIBS_INCLUDED"] = TRUE;
				}
			} else {
				$this->_includeFormidablePath();
			}

			$this->_includeDebugStyles();
		}

		function minified() {
			return (
				$this->oForm->__getEnvExecMode() !== "BE" && 
				intval($this->oForm->conf["minify."]["enabled"]) === 1 &&
				(
					file_exists(
						$this->oForm->toServerPath(t3lib_extMgm::siteRelPath("ameos_formidable") . "res/jsfwk/minified/formidable.minified.prototype.js")
					) ||
					file_exists(
						$this->oForm->toServerPath(t3lib_extMgm::siteRelPath("ameos_formidable") . "res/jsfwk/minified/formidable.minified.jquery.js")
					)
				)
			);
		}

		function gziped() {
			return (
				$this->minified() &&
				intval($this->oForm->conf["minify."]["gzip"]) === 1 &&
				(
					file_exists(
						$this->oForm->toServerPath(t3lib_extMgm::siteRelPath("ameos_formidable") . "res/jsfwk/minified/formidable.minified.prototype.js.gz")
					) ||
					file_exists(
						$this->oForm->toServerPath(t3lib_extMgm::siteRelPath("ameos_formidable") . "res/jsfwk/minified/formidable.minified.jquery.js.gz")
					)
				)
			);
		}

		function _includeThisFormDesc() {

			$aConf = array(
				"sFormId" => $this->oForm->formid,
				"Misc" => array(
					"Urls" => array(
						"Ajax" => array(
							"event" => $this->oForm->_removeEndingSlash(t3lib_div::getIndpEnv("TYPO3_SITE_URL")) . "/index.php?eID=tx_ameosformidable&object=tx_ameosformidable&servicekey=ajaxevent",
							"service" => $this->oForm->_removeEndingSlash(t3lib_div::getIndpEnv("TYPO3_SITE_URL")) . "/index.php?eID=tx_ameosformidable&object=tx_ameosformidable&servicekey=ajaxservice",
						),
					),
					"MajixSpinner" => (($aSpinner = $this->oForm->_navConf($this->oForm->sXpathToMeta . "majixspinner")) !== FALSE) ? $aSpinner : array(),
				),
			);

			$sJson = $this->oForm->array2json($aConf);
			$sScript = <<<JAVASCRIPT

Formidable.Context.Forms["{$this->oForm->formid}"] = new Formidable.Classes.FormBaseClass(
	{$sJson}
);

JAVASCRIPT;

			if(isset($GLOBALS["BE_USER"]) && method_exists($GLOBALS["BE_USER"], "isAdmin") && $GLOBALS["BE_USER"]->isAdmin()) {

				if($this->oForm->bDebug) {

					$sScript .= <<<JAVASCRIPT

Formidable.f("{$this->oForm->formid}").Manager = {
	enabled: true,
	Xml: {
		path: "{$this->oForm->_xmlPath}"
	}
};

JAVASCRIPT;

				}
			}

			$this->oForm->attachInitTask(
				$sScript,
				"Form '" . $this->oForm->formid . "' instance description",
				"framework-init"
			);
		}

		function _includeDebugStyles() {
			if($this->oForm->bDebug) {

				$this->oForm->additionalHeaderDataLocalStylesheet(
					$this->oForm->sExtPath . "res/css/debug.css",
					"tx_ameosformidable_debugstyles"
				);
			}
		}

		function _includeMinifiedJs() {

			$this->_includeFormidablePath();
			if($this->_mayLoadJQuery()) {
				$sJsapi = 'jquery';
			} else {
				$sJsapi = 'prototype';
			}

			if($this->gziped()) {
				$sPath = t3lib_div::getIndpEnv("TYPO3_SITE_URL") . t3lib_extMgm::siteRelPath("ameos_formidable") . "res/jsfwk/minified/formidable.minified." . $sJsapi . ".js.php";
			} else {
				$sPath = t3lib_div::getIndpEnv("TYPO3_SITE_URL") . t3lib_extMgm::siteRelPath("ameos_formidable") . "res/jsfwk/minified/formidable.minified." . $sJsapi . ".js";
			}

			$this->oForm->additionalHeaderData(
				"<script type=\"text/javascript\" src=\"" . $sPath . "\"></script>",
				"tx_ameosformidable_minified",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_first"
			);

			$this->oForm->additionalHeaderData(
				"<!-- Formidable core: loaded -->\n",
				"tx_ameosformidable_core",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_minified"
			);

			$this->oForm->additionalHeaderData(
				"<!-- Formidable: minifiedlibs cutpoint -->\n",
				"tx_ameosformidable_minifiedlibs_cutpoint",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_core"
			);
		}

		function _mayLoadJQuery() {
			return ($this->oForm->_navConf($this->oForm->sXpathToMeta . "jsapi") === "jquery");
		}

		function _includeJQuery() {
			$this->oForm->additionalHeaderData(
				"\n<!-- Formidable jsapi: loading -->",
				"tx_ameosformidable_jsapi_loading",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_first"
			);

			$this->oForm->additionalHeaderDataLocalScript(
				#$this->oForm->sExtPath . "res/jsfwk/jquery/core+ui/js/jquery.min.js",
				$this->oForm->sExtPath . "res/jsfwk/jquery/core+ui/development-bundle/jquery-1.5.1.js",
				"tx_ameosformidable_jquery",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_jquery_loading",
				FALSE	// do not compile
			);

			$this->oForm->additionalHeaderDataLocalScript(
				$this->oForm->sExtPath . "res/jsfwk/jquery/plugins/jquery.inherit.js",
				"tx_ameosformidable_jquery_inherit",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_jquery",
				FALSE	// do not compile
			);

			$this->oForm->additionalHeaderDataLocalScript(
				$this->oForm->sExtPath . "res/jsfwk/jquery/plugins/jquery.bind.js",
				"tx_ameosformidable_jquery_bind",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_jquery_inherit",
				FALSE	// do not compile
			);

			$this->oForm->additionalHeaderDataLocalScript(
				$this->oForm->sExtPath . "res/jsfwk/jquery/plugins/jquery.betterjson.js",
				//$this->oForm->sExtPath . "res/jsfwk/jquery/plugins/jquery.json.js",
				//$this->oForm->sExtPath . "res/jsfwk/json/json2.js",
				"tx_ameosformidable_jquery_json",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_jquery_bind",
				FALSE	// do not compile
			);
/*
			$this->oForm->additionalHeaderDataLocalScript(
					$this->oForm->sExtPath . "res/jsfwk/json/cycle.js",
					"tx_ameosformidable_jquery_json_cycle",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					$sAfter = "tx_ameosformidable_jquery_json",
					FALSE	// do not compile
			);
			*/
			$this->oForm->additionalHeaderDataLocalScript(
				$this->oForm->sExtPath . "res/jsfwk/jquery/plugins/jquery.cookie.js",
				"tx_ameosformidable_jquery_cookie",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_jquery_json",
				FALSE	// do not compile
			);

			$this->oForm->additionalHeaderData(
				"\n<!-- Formidable jsapi: loaded -->",
				"tx_ameosformidable_jsapi_loaded",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_jquery_cookie"
			);
		}

		function _includeNonMinifiedJs() {

			$this->_includeFormidablePath();

			if($this->_mayLoadJQuery()) {
				$this->_includeJQuery();
			} else {
				if($this->mayLoadPrototype() && $this->mayLoadPrototypeAddons()) {
					$this->_includePrototypeAndAddons();
				} elseif($this->mayLoadPrototype()) {
					$this->_includePrototype();
				} elseif($this->mayLoadPrototypeAddons()) {
					$this->_includePrototypeAddons();
				}
			}

			$this->_includeJSFramework();

			$this->oForm->additionalHeaderData(
				"\n<!-- Formidable core: loaded -->\n",
				"tx_ameosformidable_core",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_jsframeworkfwk"
			);
		}

		function _includePrototypeAndAddons() {
			$this->_includePrototype();
			$this->_includePrototypeAddons();
		}

		function _includePrototype() {

			$this->oForm->additionalHeaderData(
				"\n<!-- Formidable prototype: loading -->",
				"tx_ameosformidable_prototype_loading",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_first"
			);

			$this->oForm->additionalHeaderDataLocalScript(
				$this->oForm->sExtPath . "res/jsfwk/prototype/prototype.js",
				"tx_ameosformidable_prototype",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_prototype_loading",
				FALSE	// do not compile
			);
		}

		function _includePrototypeAddons() {

			if($this->mayLoadPrototype()) {
				$sAfter = "tx_ameosformidable_prototype";
			} else {
				$sAfter = "tx_ameosformidable_first";
			}

			$this->oForm->additionalHeaderData(
				"<!-- Formidable prototype addons: loading -->",
				"tx_ameosformidable_prototype_addons_loading",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter
			);

			// Dean Edward's Base API for clean prototype inheritance

			$this->oForm->additionalHeaderDataLocalScript(
				$this->oForm->sExtPath . "res/jsfwk/prototype/addons/base/Base.js",
				"tx_ameosformidable_prototype_base",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_prototype_addons_loading",
				FALSE	// do not compile
			);

			// JSON stringifier
			// http://www.thomasfrank.se/downloadableJS/jsonStringify.js

			$this->oForm->additionalHeaderDataLocalScript(
				$this->oForm->sExtPath . "res/jsfwk/json/json.js",
				"tx_ameosformidable_json",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_prototype_base",
				FALSE	// do not compile
			);
			/*
			$this->oForm->additionalHeaderDataLocalScript(
					$this->oForm->sExtPath . "res/jsfwk/json/json2.js",
					"tx_ameosformidable_json",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					$sAfter = "tx_ameosformidable_prototype_base",
					FALSE	// do not compile
			);

			$this->oForm->additionalHeaderDataLocalScript(
					$this->oForm->sExtPath . "res/jsfwk/json/cycle.js",
					"tx_ameosformidable_json_cycle",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					$sAfter = "tx_ameosformidable_json",
					FALSE	// do not compile
			);
*/			
			$this->oForm->additionalHeaderData(
				"<!-- Formidable prototype addons: loaded -->",
				"tx_ameosformidable_prototype_addons",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_json"
			);
		}

		function _includeJSFramework() {

			$this->oForm->additionalHeaderData(
				"\n<!-- Formidable framework: loading -->",
				"tx_ameosformidable_jsframework_loading",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_prototype_fwk"
			);

			if($this->_mayLoadJQuery()) {
				$this->oForm->additionalHeaderDataLocalScript(
					$this->oForm->sExtPath . "res/jsfwk/formidable/formidable.jquery.js",
					"tx_ameosformidable_jsformidablefwk",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					$sAfter = "tx_ameosformidable_loading",
					FALSE	// do not compile
				);
			} else {
				$this->oForm->additionalHeaderDataLocalScript(
					$this->oForm->sExtPath . "res/jsfwk/formidable/formidable.prototype.js",
					"tx_ameosformidable_jsformidablefwk",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					$sAfter = "tx_ameosformidable_loading",
					FALSE	// do not compile
				);
			}

			$sPath = t3lib_div::getIndpEnv("TYPO3_SITE_URL") . t3lib_extMgm::siteRelPath("ameos_formidable") . "res/jsfwk/framework.js";

			$this->oForm->additionalHeaderData(
				"<script type=\"text/javascript\" src=\"" . $sPath . "\"></script>",
				"tx_ameosformidable_jsframework",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_jsformidablefwk"
			);

			$this->oForm->additionalHeaderData(
				"\n<!-- Formidable framework: loaded -->",
				"tx_ameosformidable_jsframework_loaded",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sAfter = "tx_ameosformidable_jsframework"
			);
		}

		function _includeFormidablePath() {
			$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extmgm::siteRelPath("ameos_formidable");
			$sScript =<<<JAVASCRIPT

	Formidable.initialize({path: '{$sPath}'});

JAVASCRIPT;

			$this->oForm->attachInitTask(
				$sScript,
				"Framework Formidable.path initialization"
			);
		}

		function loadJQueryEffects() {
			$this->bLoadJQueryEffects = TRUE;
		}

		function loadJQueryDragDrop() {
			$this->bLoadJQueryDragDrop = TRUE;
		}

		function loadScriptaculous() {
			$this->bLoadScriptaculous = TRUE;
		}

		function loadScriptaculousDragDrop() {
			$this->loadScriptaculous();
			$this->bLoadScriptaculousDragDrop = TRUE;
		}

		function loadScriptaculousBuilder() {
			$this->bLoadScriptaculousBuilder = TRUE;
		}

		function loadTooltip() {
			$this->loadScriptaculous();
			$this->loadScriptaculousBuilder();
			$this->bLoadtooltip = TRUE;
		}

		function loadLightbox() {
			$this->loadScriptaculous();
			$this->loadScriptaculousBuilder();
			$this->bLoadLightbox = TRUE;
		}

		function _loadScriptaculous() {
			if($this->bLoadScriptaculous === TRUE && $this->mayLoadScriptaculous()) {


				$this->oForm->additionalHeaderData(
					"<!-- Formidable scriptaculous: loading -->",
					"tx_ameosformidable_scriptaculous_fwk_loading",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					$sAfter = "tx_ameosformidable_core"
				);

				$this->oForm->additionalHeaderDataLocalScript(
					$this->oForm->sExtPath . "res/jsfwk/scriptaculous/scriptaculous.js",
					"tx_ameosformidable_scriptaculous",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					$sAfter = "tx_ameosformidable_scriptaculous_fwk_loading",
					FALSE	// mayCompile
				);

				$this->oForm->additionalHeaderDataLocalScript(
					$this->oForm->sExtPath . "res/jsfwk/scriptaculous/effects.js",
					"tx_ameosformidable_scriptaculous_effects",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					$sAfter = "tx_ameosformidable_scriptaculous",
					FALSE	// mayCompile
				);

				$sNextAfter = "tx_ameosformidable_scriptaculous_effects";

				if($this->bLoadScriptaculousDragDrop === TRUE) {
					$this->oForm->additionalHeaderDataLocalScript(
						$this->oForm->sExtPath . "res/jsfwk/scriptaculous/dragdrop.js",
						"tx_ameosformidable_scriptaculous_dragdrop",
						$bFirstPos = FALSE,
						$sBefore = FALSE,
						$sNextAfter,
						FALSE	// mayCompile
					);

					$sNextAfter = "tx_ameosformidable_scriptaculous_dragdrop";
				}

				if($this->bLoadScriptaculousBuilder === TRUE) {
					$this->oForm->additionalHeaderDataLocalScript(
						$this->oForm->sExtPath . "res/jsfwk/scriptaculous/builder.js",
						"tx_ameosformidable_scriptaculous_builder",
						$bFirstPos = FALSE,
						$sBefore = FALSE,
						$sNextAfter,
						FALSE	// mayCompile
					);

					$sNextAfter = "tx_ameosformidable_scriptaculous_builder";
				}

				$this->oForm->additionalHeaderData(
					"<!-- Formidable scriptaculous: loaded -->\n",
					"tx_ameosformidable_scriptaculous_fwk",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					$sNextAfter
				);
			}
		}

		function _loadTooltip() {
			if($this->bLoadtooltip === TRUE) {

				// tooltip css
				$this->oForm->additionalHeaderDataLocalStylesheet(
					$this->oForm->sExtPath . "res/jsfwk/tooltip/tooltips.css",
					"tx_ameosformidable_tooltip_css",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					"tx_ameosformidable_scriptaculous_fwk"
				);

				// tooltip js
				$this->oForm->additionalHeaderDataLocalScript(
					$this->oForm->sExtPath . "res/jsfwk/tooltip/tooltips.js",
					"tx_ameosformidable_tooltip_js",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					"tx_ameosformidable_tooltip_css"
				);
			}
		}

		function _loadLightbox() {
			if($this->bLoadLightbox === TRUE) {

				// lightbox css
				$this->oForm->additionalHeaderDataLocalStylesheet(
					$this->oForm->sExtPath . "res/jsfwk/lightbox/css/lightbox.css",
					"tx_ameosformidable_lightbox_css",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					"tx_ameosformidable_scriptaculous_fwk"
				);

				// lightbox js
				$this->oForm->additionalHeaderDataLocalScript(
					$this->oForm->sExtPath . "res/jsfwk/lightbox/js/lightbox.js",
					"tx_ameosformidable_lightbox_js",
					$bFirstPos = FALSE,
					$sBefore = FALSE,
					"tx_ameosformidable_lightbox_css"
				);
			}
		}

		function compileAndGzipFormidableScripts() {
			$aScriptsToCompile = array();

			$aHeaders =& $this->oForm->getAdditionalHeaderData();	// aHeaders is here a reference to the global header array
			$aKeys = array_keys($aHeaders);
			reset($aKeys);
			while(list(, $sKey) = each($aKeys)) {
				// key should start with "formidable:"

				if($sKey{0} === "f" && $sKey{1} === "o" && substr($sKey, 0, 11) === "formidable:") {

					// value should start with "local:script:"

					if(
						$aHeaders[$sKey]{0} === "l" &&
						$aHeaders[$sKey]{1} === "o" &&
						substr($aHeaders[$sKey], 0, 13) === "local:script:"
					) {
						$sAbsFile = substr($aHeaders[$sKey], 13);
						if(@file_exists($sAbsFile)) {
							$aScriptsToCompile[$sKey] = array(
								"path" => $sAbsFile,
								"filemtime" => @filemtime($sAbsFile),
								"filesize" => @filesize($sAbsFile),
							);
							$aHeaders[$sKey] = "<!-- " . $this->oForm->toRelPath($sAbsFile) . " compiled -->";
						} else {
							# debug($sAbsFile, "does not exist.");
						}
					}
				}
			}

			if(!empty($aScriptsToCompile)) {
				$bGzip = extension_loaded('zlib');

				$sCachePath = PATH_site . "typo3temp/ameos_formidable/js_gzip/";
				if(!file_exists($sCachePath)) {
					@mkdir($sCachePath);
					@copy(
						$this->oForm->sExtPath . "res/jsfwk/minified/formidable.gzipserve.php",
						$sCachePath . "formidable.gzipserve.php"
					);
				}

				$sFingerPrint = md5(serialize($aScriptsToCompile));
				$sJsBallPath = $sCachePath . $sFingerPrint . ".js";
				if($bGzip === TRUE) {
					$sJsBallPath .= ".gz";
					$sResourceUrl = $this->oForm->toWebPath($sCachePath . "formidable.gzipserve.php") . "?fingerprint=" . $sFingerPrint;
				} else {
					$sResourceUrl = $this->oForm->toWebPath($sJsBallPath);
				}

				if(!file_exists($sJsBallPath)) {
					$aLoadedScriptsManifest = array();

					// we have to produce the ball
					$sContents = "";
					reset($aScriptsToCompile);
					while(list($sKey,) = each($aScriptsToCompile)) {
						$aLoadedScriptsManifest[] = $this->oForm->toWebPath($aScriptsToCompile[$sKey]["path"]);
						$sContents .= "\n/*** " . $sKey . ":begin ***/\n" . $this->oForm->file_readBin($aScriptsToCompile[$sKey]["path"]) . "\n/*** " . $sKey . ":end ***/\n\n";
					}

					// adding js manifest (useful to determine in JS context wether a library has already been loaded or not)
					$sLoadedJsManifest = "['" . implode("',\n'", $aLoadedScriptsManifest) . "']";
					$sJsManifest = <<<JAVASCRIPT
/* JS Manifest */
Formidable.declareLoadedScripts({$sLoadedJsManifest});
JAVASCRIPT;

					$sContents .= $sJsManifest;

					if($bGzip) {
						$this->oForm->file_writeBin(
							$sJsBallPath,
							gzencode($sContents, 9),	// 9 = best compression
							FALSE	// add UTF-8 header
						);
					} else {
						$this->oForm->file_writeBin(
							$sJsBallPath,
							$sContents,	// 9 = best compression
							FALSE	// add UTF-8 header
						);
					}
				}

				$this->oForm->additionalHeaderData(
					'<script type="text/javascript" src="' . $sResourceUrl . '"></script>',
					FALSE,	// $sKey
					FALSE,	// $bFirstPos
					"tx_ameosformidable_minifiedlibs_cutpoint",	// $sBefore
					FALSE // $sAfter
				);
			}
		}

		function includeAdditionalLibraries() {
			if($this->_mayLoadJQuery()) {
				# JQuery

				$this->_loadJQueryUI();
			} else {
				# Prototype

				$this->_loadScriptaculous();
				$this->_loadTooltip();
				$this->_loadLightbox();
			}
		}

		function _loadJQueryUI() {

			/*
			$this->oForm->additionalHeaderDataLocalStylesheet(
				$this->sJQueryUIPath . "development-bundle/themes/base/jquery.ui.all.css",
				"tx_ameosformidable_jquery_ui_basecss"
			);
			*/

			$this->oForm->additionalHeaderDataLocalScript(
				$this->sJQueryUIMinifiedJsPath . "jquery.ui.core.min.js",
				"tx_ameosformidable_jquery_ui_core",
				FALSE,	# $bFirstPos
				FALSE,	# $sBefore
				FALSE,	#"tx_ameosformidable_jquery_ui_basecss",	# $sAfter
				TRUE	# mayCompile
			);

			$this->oForm->additionalHeaderDataLocalScript(
				$this->sJQueryUIMinifiedJsPath . "jquery.ui.widget.min.js",
				"tx_ameosformidable_jquery_ui_widget",
				FALSE,	# $bFirstPos
				FALSE,	# $sBefore
				"tx_ameosformidable_jquery_ui_core",	# $sAfter
				TRUE	# mayCompile
			);

			$this->oForm->additionalHeaderDataLocalScript(
				$this->sJQueryUIMinifiedJsPath . "jquery.ui.mouse.min.js",
				"tx_ameosformidable_jquery_ui_mouse",
				FALSE,	# $bFirstPos
				FALSE,	# $sBefore
				"tx_ameosformidable_jquery_ui_widget",	# $sAfter
				TRUE	# mayCompile
			);

			$sNextAfter = "tx_ameosformidable_jquery_ui_mouse";

			if($this->bLoadJQueryDragDrop === TRUE) {

				$this->oForm->additionalHeaderDataLocalScript(
					$this->sJQueryUIMinifiedJsPath . "jquery.ui.draggable.min.js",
					"tx_ameosformidable_jquery_ui_draggable",
					FALSE,	# $bFirstPos
					FALSE,	# $sBefore
					"tx_ameosformidable_jquery_ui_mouse",	# $sAfter
					TRUE	# mayCompile
				);

				$this->oForm->additionalHeaderDataLocalScript(
					$this->sJQueryUIMinifiedJsPath . "jquery.ui.sortable.min.js",
					"tx_ameosformidable_jquery_ui_sortable",
					FALSE,	# $bFirstPos
					FALSE,	# $sBefore
					"tx_ameosformidable_jquery_ui_draggable",	# $sAfter
					TRUE	# mayCompile
				);

				$sNextAfter = "tx_ameosformidable_jquery_ui_sortable";
			}

			$this->oForm->additionalHeaderData(
				"<!-- Formidable jQueryUI core: loaded -->",
				"tx_ameosformidable_jquery_ui_core_loaded",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sNextAfter
			);

			$sNextAfter = "tx_ameosformidable_jquery_ui_core_loaded";

			// loading plugins
			reset($this->ajQueryPluginsToLoad);
			while(list(, $sPlugin) = each($this->ajQueryPluginsToLoad)) {

				$sKey = "tx_ameosformidable_jquery_ui_" . $sPlugin;

				$this->oForm->additionalHeaderDataLocalScript(
					$this->sJQueryUIMinifiedJsPath . "jquery.ui." . $sPlugin . ".min.js",
					#$this->sJQueryUIFatJsPath . "jquery.ui." . $sPlugin . ".js",
					$sKey,
					FALSE,			# $bFirstPos
					FALSE,			# $sBefore
					$sNextAfter,	# $sAfter
					TRUE			# mayCompile
				);

				$sNextAfter = $sKey;
			}

			$this->oForm->additionalHeaderData(
				"<!-- Formidable jQueryUI plugins: loaded -->",
				"tx_ameosformidable_jquery_ui_plugins_loaded",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sNextAfter
			);
		}

		function jquery_loadUiPlugin($sPlugin) {
			if(!in_array($sPlugin)) {
				$this->ajQueryPluginsToLoad[] = $sPlugin;
			}
		}
	}

	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.jslayer.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/class.jslayer.php"]);
	}
?>
