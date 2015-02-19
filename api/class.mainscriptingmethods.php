<?php

class formidable_mainscriptingmethods {
	
	function _init(&$oForm) {
		$this->oForm =& $oForm;		
	}
	
	function process($sMethod, $mData, $sArgs) {

		$aParams = $this->oForm->parseTemplateMethodArgs($sArgs);
		$sMethodName = strtolower("method_" . $sMethod);
		
		if(method_exists($this, $sMethodName)) {
			return $this->$sMethodName(
				$mData,
				$aParams
			);
		} else {
			if(is_object($mData) && is_string($sMethod) && method_exists($mData, $sMethod)) {
				switch(count($aParams)) {
					case 0: { return $mData->{$sMethod}(); break;}
					case 1: { return $mData->{$sMethod}($aParams[0]); break;}
					case 2: { return $mData->{$sMethod}($aParams[0], $aParams[1]); break;}
					case 3: { return $mData->{$sMethod}($aParams[0], $aParams[1], $aParams[2]); break;}
					case 4: { return $mData->{$sMethod}($aParams[0], $aParams[1], $aParams[2], $aParams[3]); break;}
					case 5: { return $mData->{$sMethod}($aParams[0], $aParams[1], $aParams[2], $aParams[3], $aParams[4]); break;}
				}
			}	
		}

		return AMEOSFORMIDABLE_LEXER_FAILED;
	}
		
} // END class formidable_mainscriptingmethods

?>