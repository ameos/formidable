<?php
/** 
 * Plugin 'dh_raw' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_dhraw extends formidable_maindatahandler {

	function _doTheMagic($bShouldProcess = TRUE) {

		if($bShouldProcess && $this->_allIsValid()) {

			$aData = $this->_getFormData();

			// calling back
			$callback = $this->oForm->_navConf($this->oForm->sXpathToControl . "datahandler/");
			if(tx_ameosformidable::isRunneable($callback)) {
				$this->callRunneable(
					$callback,
					$aData
				);
			} else {
				$callback = $this->oForm->_navConf($this->oForm->sXpathToControl . "datahandler/parentcallback/");
				if($callback === FALSE) {
					$callback = $this->oForm->_navConf($this->oForm->sXpathToControl . "datahandler/callback/");
				}
				
				if($callback !== FALSE) {
					if(tx_ameosformidable::isRunneable($callback)) {
						$this->callRunneable(
							$callback,
							$aData
						);
					} elseif(is_string($callback)) {
						if(method_exists($this->oForm->_oParent, $callback)) {
							$this->oForm->_oParent->{$callback}($aData);
						} else {
							$this->oForm->mayday("DATAHANDLER RAW : the callback method " . $callback . " doesn't exists in the definition of the Parent object");
						}
					}
				} else {
					$this->oForm->mayday("DATAHANDLER RAW : you MUST declare a callback method on the Parent Object <b>" . get_class($this->oForm->_oParent) . "</b> in the section <b>/control/datahandler/parentcallback/</b> of the XML conf for this DATAHANDLER ( RAW )");
				}
			}
		}
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/dh_raw/api/class.tx_dhraw.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/dh_raw/api/class.tx_dhraw.php"]);
	}
?>