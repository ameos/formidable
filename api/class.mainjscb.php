<?php

class formidable_mainjscb {
	
	var $aConf = array();
	var $oForm = null;
	
	function init(&$oForm) {
		$this->oForm = $oForm;
	}
	
	function majixExec(/*$sMethod, $arg1, $arg2, ... */) {
		
		$aContext = array();

		$aListData = $this->oForm->oDataHandler->getListData();
		if(!empty($aListData)) {
			$aContext["currentrow"] = $aListData["uid"];
		}
		
		$aArgs = func_get_args();
		$sMethod = array_shift($aArgs);
		return $this->oForm->buildMajixExecuter(
			"executeCbMethod",
			array(
				"cb" => $this->aConf,
				"method" => $sMethod,
				"params" => $aArgs,
				"context" => $aContext,
			),
			$this->oForm->formid
		);
	}
	
	function __call($sMethod, $aArgs) {
		array_unshift($aArgs, $sMethod);	// add the method name as first parameter for $this->majixExec
		return call_user_func_array(array($this, "majixExec"), $aArgs);
	}
}

?>