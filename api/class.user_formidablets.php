<?php

	require_once(PATH_formidableapi);
	
	class user_formidablets {
	
		function manualHeaderPath($content, $conf) {
			$conf["pathonly"] = 1;
			return $this->manualHeader($conf);
		}
	
		function manualHeaderTag($content, $conf) {
			$conf["pathonly"] = 0;
			return $this->manualHeader($conf);
		}
	
		function manualHeader($conf) {
			$aRes = array();
		
			if(array_key_exists("load", $conf)) {
				$aLoad = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(",", strtolower($conf["load"]));
			
				reset($aLoad);
				while(list($sKey,) = each($aLoad)) {
					switch($aLoad[$sKey]) {
						case "minified": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/minified/formidable.minified.prototype.js", $conf["pathonly"]);
							break;
						}
						case "minifiedjquery": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/minified/formidable.minified.jquery.js", $conf["pathonly"]);
							break;
						}
						case "minified+gzipped": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/minified/formidable.minified.prototype.js.php", $conf["pathonly"]);
							break;
						}
						case "minified+gzippedjquery": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/minified/formidable.minified.jquery.js.php", $conf["pathonly"]);
							break;
						}
						case "jsframework": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/formidable/formidable.prototype.js", $conf["pathonly"]);
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/framework.js", $conf["pathonly"]);
							break;
						}
						case "jsframeworkjquery": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/formidable/formidable.jquery.js", $conf["pathonly"]);
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/framework.js", $conf["pathonly"]);
							break;
						}
						case "prototype": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/prototype/prototype.js", $conf["pathonly"]);
							break;
						}
						case "prototype+addons": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/prototype/prototype.js", $conf["pathonly"]);
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/prototype/addons/lowpro/lowpro.js", $conf["pathonly"]);
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/prototype/addons/base/Base.js", $conf["pathonly"]);
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/json/json.js", $conf["pathonly"]);
							break;
						}
						case "prototype_addonsonly": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/prototype/addons/lowpro/lowpro.js", $conf["pathonly"]);
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/prototype/addons/base/Base.js", $conf["pathonly"]);
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/json/json.js", $conf["pathonly"]);
							break;
						}
						case "scriptaculous": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/scriptaculous/scriptaculous.js", $conf["pathonly"]);
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/scriptaculous/effects.js", $conf["pathonly"]);
							break;
						}
						case "scriptaculous_dragdrop": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/scriptaculous/dragdrop.js", $conf["pathonly"]);
							break;
						}
						case "scriptaculous_builder": {
							$aRes[] = $this->wrapScriptTag(PATH_formidable . "res/jsfwk/scriptaculous/builder.js", $conf["pathonly"]);
							break;
						}
					}
				}
			}
		
			reset($aRes);
			return implode("\n", $aRes);
		}
	
		function wrapScriptTag($sPath, $bPathOnly = 0) {
			$sWebPath = tx_ameosformidable::toWebPath($sPath);
		
			if(intval($bPathOnly) === 1) {
				return $sWebPath;
			}
		
			return '<script type="text/javascript" src="' . $sWebPath . '"></script>';
		}
	}

?>