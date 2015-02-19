<?php
/** 
 * Plugin 'ds_phparray' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

class tx_dsphparray extends formidable_maindatasource {

	var $aSource = FALSE;
	var $aPosByUid = FALSE;
	var $aConfig = array();
	var $aFilters = array();
	var $iTotalRows = 0;

	function &_fetchData($aConfig = array(), $aFilters = array()) {

		$this->aConfig =& $aConfig;
		$this->aFilters =& $aFilters;

		$this->_initBinding();

		return array(
			"numrows" => $this->iTotalRows,
			"results" => &$this->aSource,
		);
	}

	function _initBinding() {
		if(tx_ameosformidable::isRunneable(($aBindsTo = $this->_navConf("/bindsto")))) {
			
			$this->aSource =& $this->callRunneable($aBindsTo);

			$this->iTotalRows = count($this->aSource);

			if(!is_array($this->aSource)) {
				$this->aSource = array();
				$this->iTotalRows = 0;
			}
		} else {
			$this->oForm->mayday("DATASOURCE PHPARRAY \"" . $this->aElement["name"] . "\" - requires /bindsTo. Check your XML conf.");
		}
		
		$this->_sortSource();
		$this->_limitSource();
	}

	function _sortSource() {
		if(trim($this->aConfig["sortcolumn"]) !== "") {
			
			$aSorted = array();
			
			reset($this->aSource);
			$named_hash = array();

			foreach($this->aSource as $key => $fields) {
				$named_hash[$key] = $fields[$this->aConfig["sortcolumn"]];
			}

			if($this->aConfig["sortdirection"] === "desc") {
				#arsort($named_hash, $flags=0);
				natsort($named_hash);
				$named_hash = array_reverse($named_hash, TRUE);
			} else {
				#asort($named_hash, $flags=0);
				natsort($named_hash);
			}
			
			$k = 1;
			$this->aPosByUid = array();
			$sorted_records = array();
			
			foreach($named_hash as $key=>$val) {
				$aSorted[$key] = $this->aSource[$key];
				$this->aPosByUid[$aSorted[$key]["uid"]] = $k;
				$k++;
			}
			
			reset($this->aPosByUid);
			
			return $this->aSource =& $aSorted;
		} else {
			
			$k = 1;
			$this->aPosByUid = array();
			$aKeys = array_keys($this->aSource);
			
			reset($aKeys);
			while(list(, $sKey) = each($aKeys)) {
				$this->aPosByUid[$this->aSource[$sKey]["uid"]] = $k;
				$k++;
			}
			
			reset($this->aPosByUid);
		}
	}

	function _limitSource() {

		$aLimit = $this->_getRecordWindow(
			$this->aConfig["page"],
			$this->aConfig["perpage"]
		);

		$this->aSource = array_slice(
			$this->aSource,
			$aLimit["offset"],
			$aLimit["nbdisplayed"]
		);
	}
	
	function getRowNumberForUid($iUid) {
		if(array_key_exists($iUid, $this->aPosByUid)) {
			return $this->aPosByUid[$iUid];
		}
		
		return FALSE;
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/ds_phparray/api/class.tx_dsphparray.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/ds_phparray/api/class.tx_dsphparray.php"]);
	}
?>
