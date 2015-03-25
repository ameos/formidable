<?php
/** 
 * Plugin 'rdt_advsearchform' for the 'ameos_formidable' extension.
 *
 * @author	Ameos <typo3dev@ameos.com>
 */

class tx_rdtadvsearchform extends formidable_mainrenderlet {
	
	const FORMIDABLE_ADVSEARCHFORM_EXACTLY = 'exactly';
	const FORMIDABLE_ADVSEARCHFORM_NOTEXACTLY = 'notexactly';
	const FORMIDABLE_ADVSEARCHFORM_APPROXIMATELY = 'approximately';
	const FORMIDABLE_ADVSEARCHFORM_NOTAPPROXIMATELY = 'notapproximately';
	const FORMIDABLE_ADVSEARCHFORM_DATEEXACTLY = 'dateexactly';
	const FORMIDABLE_ADVSEARCHFORM_DATENOTEXACTLY = 'datenotexactly';
	const FORMIDABLE_ADVSEARCHFORM_DATESUP = 'datesuperior';
	const FORMIDABLE_ADVSEARCHFORM_DATEINF = 'dateinferior';

	const FORMIDABLE_ADVSEARCHFORM_LOCAL = 'local';
	const FORMIDABLE_ADVSEARCHFORM_FOREIGN = 'foreign';
	const FORMIDABLE_ADVSEARCHFORM_MMFOREIGN = 'mm';
	const FORMIDABLE_ADVSEARCHFORM_CUSTOM = 'custom';
	const FORMIDABLE_ADVSEARCHFORM_OR = 'logicbox_or';
	const FORMIDABLE_ADVSEARCHFORM_AND = 'logicbox_and';
	const FORMIDABLE_ADVSEARCHFORM_CURRENTCOL = 'current_column';

	var $aSearchFields = FALSE;
	var $aLogicFields = FALSE;
	var $aSearchRows = FALSE;		
	
	var $oDataSource = FALSE;
	var $aDescendants = FALSE;
	var $aFilters = FALSE;
	var $aRdtSearchmangement = FALSE;
	
	var $aSaveSearch = FALSE;

	var $sMajixClass = "Advsearchform";
	var $aLibs = array(
		"rdt_advsearchform_class" => "res/js/advsearchform.js",
	);
		
	var $bCustomIncludeScript = TRUE;
	var $sDateFormat = '%Y/%m/%d';
	
	function _init(&$oForm, $aElement, $aObjectType, $sXPath) {	
		parent::_init($oForm, $aElement, $aObjectType, $sXPath);
		$this->oForm->sessionStart();
	}

	function _render() {	
		$this->_initData();
	
		$this->oForm->bStoreFormInSession = TRUE;
		if(empty($GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxevent"][$this->_getSessionDataHashKey()])) {
			$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"]["tx_ameosformidable"]["ajaxevent"][$this->_getSessionDataHashKey()] = array(
				"requester" => array(
					"name" => "tx_ameosformidable",
					"xpath" => "/",
				),
			);
		}
		$aIncludeScripts = array(
			'searchrows' => $this->aSearchRows,
			'searchmanagement' => $this->searchManagementIsEnable(),
			'add' => $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').addRow()",
				"sys_event.searchrows, sys_event.target",
				FALSE
			),
			'remove' => $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').removeRow()",
				"sys_event.searchrows, sys_event.target",
				FALSE
			),
			'modifiy' => $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').modifyRow()",
				"sys_event.searchrows, sys_event.target",
				FALSE
			),
			'addsubquery' => $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').addSubquery()",
				"sys_event.searchrows, sys_event.target",
				FALSE
			)
		);

		$aHtmlBag = array();

		if($this->searchManagementIsEnable()) {
			$aIncludeScripts['savesearch'] = $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').saveSearch()",
				"sys_event.savesearch",
				FALSE
			);	
			$aIncludeScripts['loadsearch'] = $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').loadSearch()",
				"sys_event.loadsearch",
				FALSE
			);
			$aIncludeScripts['removesearch'] = $this->synthetizeAjaxEventCb(
				"onclick",
				"rdt('" . $this->getAbsName() . "').removeSearch()",
				"sys_event.loadsearch"
			);
			
			$this->_renderSearchform_displaySearchmanagement($aHtmlBag);
		}
		
		$this->_renderSearchform_displayFields($aHtmlBag);
		$this->_renderSearchform_displayChilds($aHtmlBag);
		$this->_renderSearchform($aHtmlBag);		

		$this->includeScripts($aIncludeScripts);
		return $aHtmlBag;
	}
	
	function _renderSearchform(&$aHtmlBag) {
		$aTemplate = $this->getSearchformTemplate();
		$aHtmlBag["__compiled"] = $this->oForm->_parseTemplateCode(
			$aTemplate["html"],
			$aHtmlBag
		);
		$aHtmlBag["__compiled"] = $this->_wrapIntoContainer($aHtmlBag["__compiled"], $this->_getAddInputParams());
	}
	
	function _renderSearchform_displayFields(&$aHtmlBag) {
		
		$aTemplate = $this->getFieldsTemplate();
		$aAltRows = array();
		$aRowsHtml = array();
		$sRowsPart = t3lib_parsehtml::getSubpart($aTemplate["html"], "###ROWS###");
		
		if($aTemplate["default"] === TRUE) {
			$sAltList = "###ROW1###, ###ROW2###";
		} elseif (($sAltRows = $this->_navConf("/fields/template/alternaterows")) !== FALSE && $this->oForm->isRunneable($sAltRows)) {
			$sAltList = $this->callRunneable($sAltRows);
		} elseif (($sAltList = $this->_navConf("/fields/template/alternaterows")) === FALSE ) {
			$this->oForm->mayday("RENDERLET ADVSEARCHFORM <b>" . $this->_getName() . "</b> 
				requires /template/alternaterows to be properly set. Please check your XML configuration");
		}

		$aAltList = t3lib_div::trimExplode(",", $sAltList);
		if(sizeof($aAltList) > 0) {
			reset($aAltList);
			while(list(, $sAltSubpart) = each($aAltList)) {
				$aAltRows[] = t3lib_parsehtml::getSubpart($sRowsPart, $sAltSubpart);
			}

			$iNbAlt = sizeOf($aAltRows);
		}
		
		$iRowNum = 0;

		foreach($this->aSearchRows as $iKey => $aRow) {
			$sRowTemplate = $aAltRows[$iRowNum % $iNbAlt];
			
			foreach($aRow['childs'] as $sKey => $aRowValue) {
				if(strpos($sKey, 'searchrow_') !== FALSE) {
					$aRow['childs']['childrows'].= $this->_renderRow($aRowValue, $sRowTemplate);
				}
			}

			$sRowContent = $this->oForm->_parseTemplateCode(
				$sRowTemplate,		// current alternate subpart for row
				$aRow['childs']
			);
			
			$sRowContent = $aRow['box.']['begin'] . $sRowContent . $aRow['box.']['end'];
						
			$aHtmlBag['fields.'][$iKey] = $sRowContent;			
			$iRowNum++;
		}

		$aHtmlBag["fields"] = t3lib_parsehtml::substituteSubpart(
			$aTemplate["html"],
			"###ROWS###",
			implode("", $aHtmlBag['fields.']),
			FALSE,
			FALSE
		);
	}
	
	function _renderRow($aRow, $sRowTemplate) {
		$aRow['childs']['childrows'] = '';
		foreach($aRow['childs'] as $sKey => $aRowValue) {
			if(strpos($sKey, 'searchrow_') !== FALSE) {
				$aRow['childs']['childrows'].= $this->_renderRow($aRowValue, $sRowTemplate);
			}
		}

		$sRowContent = $this->oForm->_parseTemplateCode(
			$sRowTemplate,
			$aRow['childs']
		);
		$sRowContent = $aRow['box.']['begin'] . $sRowContent . $aRow['box.']['end'];
		return $sRowContent;		
	}
	
	function _renderSearchform_displayChilds(&$aHtmlBag) {
		$aChildBags = $this->renderChildsBag();
		$sCompiledChilds = $this->renderChildsCompiled($aChildBags);

		$aHtmlBag['childs'] = $sCompiledChilds;
		$aHtmlBag['childs.'] = $aChildBags;
	}
	
	function _renderSearchform_displaySearchmanagement(&$aHtmlBag) {
		$sCompiled = '';
		foreach($this->aRdtSearchmangement as $aRdt) {
			$sCompiled = $aRdt['__compiled'];
		}
		$aHtmlBag['searchmanagement'] = $sCompiled;
		$aHtmlBag['searchmanagement.'] = $this->aRdtSearchmangement;
	}
	
	function getSearchformTemplate() {
		$aRes = array(
			'default' => FALSE,
			'html' => ''
		);
		
		if(($aTemplate = $this->_navConf("/template")) === FALSE) {
			$aRes['default'] = TRUE;
			$aRes['html'] = t3lib_parsehtml::getSubpart(
				t3lib_div::getUrl($this->sExtPath . "res/html/default-template.html"),
				'###DEFAULT_ADVSEARCHFORM###'
			);
		} else {
			$aRes = $this->getTemplate($aRes, $aTemplate, '/template/path');
		}
		
		return $aRes;
	
	}
	
	function getFieldsTemplate() {
		$aRes = array(
			'default' => FALSE,
			'html' => ''
		);
		
		if(($aTemplate = $this->_navConf("/fields/template")) === FALSE) {
			$aRes['default'] = TRUE;
			$aRes['html'] = t3lib_parsehtml::getSubpart(
				t3lib_div::getUrl($this->sExtPath . "res/html/default-template.html"),
				'###DEFAULT_ADVSEARCHFORM_FIELDS###'
			);
		} else {
			$aRes = $this->getTemplate($aRes, $aTemplate, '/fields/template/path');			
		}
		
		return $aRes;
	}
	
	function getTemplate($aRes, $aTemplate, $sPath) {
		if(is_array($aTemplate) && array_key_exists("path", $aTemplate)) {
			if($this->oForm->isRunneable($aTemplate["path"])) { 
				$aTemplate["path"] = $this->callRunneable($aTemplate["path"]);
			}
		} else {
			$this->oForm->mayday("RENDERLET ADVSEARCHFORM <b>" . $this->_getName() . "</b> - 
				Template defined, but <b>" . $sPath . "</b> is missing. Please check your XML configuration");
		}

		if($aTemplate["path"]{0} === 'T' && substr($aTemplate["path"], 0, 3) === 'TS:') {
			if(($aTemplate["path"] = $this->oForm->getTS($aTemplate["path"], TRUE)) === AMEOSFORMIDABLE_TS_FAILED) {
				$this->oForm->mayday("The typoscript pointer <b>" . $aTemplate["path"] . "</b>
					evaluation has failed, as the pointed property does not exists within the current Typoscript template");
			}
		}

		if(is_array($aTemplate) && array_key_exists("subpart", $aTemplate)) {
			if($this->oForm->isRunneable($aTemplate["subpart"])) {
				$aTemplate["subpart"] = $this->callRunneable($aTemplate["subpart"]);
			}
		} else {
			$this->oForm->mayday("RENDERLET ADVSEARCHFORM <b>" . $this->_getName() . "</b> - 
				Template defined, but <b>" . $sPath . "</b> is missing. Please check your XML configuration");
		}

		$aTemplate["path"] = $this->oForm->toServerPath($aTemplate["path"]);
	
	
		if(file_exists($aTemplate["path"])) {
			if(is_readable($aTemplate["path"])) {
				$aRes["html"] = t3lib_parsehtml::getSubpart(
					t3lib_div::getUrl($aTemplate["path"]),
					$aTemplate["subpart"]
				);

				if(trim($aRes["html"]) === "") {
					$this->oForm->mayday("RENDERLET ADVSEARCHFORM <b>" . $this->_getName() . "</b> - 
						the given SUBPART '<b>" . $aTemplate["subpart"] . "</b>' doesn't exists");
				}
			} else {
				$this->oForm->mayday("RENDERLET ADVSEARCHFORM <b>" . $this->_getName() . "</b> - 
					the given template file '<b>" . $aTemplate["path"] . "</b>' isn't readable. Please check permissions for this file.");
			}
		} else {
			$this->oForm->mayday("RENDERLET ADVSEARCHFORM <b>" . $this->_getName() . "</b> - 
				the given TEMPLATE FILE '<b>" . $aTemplate["path"] . "</b>' doesn't exists.");
		}
		
		return $aRes;
	}
	
	function _wrapIntoContainer($sHtml, $sAddParams = "") {
		return "<div id=\"" . $this->_getElementHtmlId() . "\"" . $sAddParams . ">" . $sHtml . "</div>";
	}

	function isInline() {
		return $this->_navConf("/mode") === "inline";
	}
	
	function _initData() {
		if($this->logicoperatorIsEnable()) {
			$this->_initLogicOperator();
		}		
		$this->_initFields();
		$this->_initDataSource();
		$this->_initRows();
		$this->_initDescendants();

		if($this->shouldUpdateCriterias()) {
			$values = $this->getValue();
			foreach($values as $key => $value) {
				if(strpos($key, 'searchrow') === FALSE && $key != 'managesearchbox') {
					$this->oForm->rdt($key)->setValue($value);
					$aData = &$GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->getSearchHash()][$this->getAbsName()];
					$aData['childs'][$key] = $value;
				}
			}
		} else {
			$aData = &$GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->getSearchHash()][$this->getAbsName()];
			if(isset($aData['childs']) && is_array($aData['childs'])) {
				foreach($aData['childs'] as $key => $value) {
					if($this->oForm->rdt($key)) {
						$this->oForm->rdt($key)->setValue($value);
					}
				}
			}			
		}

		if($this->searchManagementIsEnable()) {
			$this->_initSearchmanagement();
		}
	}
	
	function _initLogicOperator() {
		
		$this->aLogicFields['or'] = array(
			'caption' => $this->oForm->getLLLabel('LLL:EXT:ameos_formidable/api/base/rdt_advsearchform/res/locallang/locallang.xml:logicbox.or'),
			'name' => 'logicbox_or',
			'mode' => 'logicbox',
			'search_type' => self::FORMIDABLE_ADVSEARCHFORM_OR,
			'value' => 'or'
		);
		
		$this->aLogicFields['and'] = array(
			'caption' => $this->oForm->getLLLabel('LLL:EXT:ameos_formidable/api/base/rdt_advsearchform/res/locallang/locallang.xml:logicbox.and'),
			'name' => 'logicbox_and',
			'mode' => 'logicbox',
			'search_type' => self::FORMIDABLE_ADVSEARCHFORM_AND,
			'value' => 'and'
		);
	}
	
		// TODO: TCA field
	function _initFields() {
		if(($mFields = $this->_navConf('/fields')) === FALSE) {
			$this->oForm->mayday("RENDERLET ADVSEARCHFORM - requires /fields. Check your XML conf.");
		}
		$aFields = array();
		if(($sTable = $this->_navConf('/fields/table')) !== FALSE) {		
			t3lib_div::loadTCA($sTable);
			$aTableFields = $GLOBALS['TYPO3_DB']->admin_get_fields($sTable);
			foreach($aTableFields as $aTableField) {				
				$aFields[$aTableField['Field']] = array(
					'name' => $aTableField['Field'],
					'caption' => $this->getAutoLabelForField($aTableField['Field'], $sTable),
					'search_type' => 'local',
					'search_config' => array(
						'db_searchfield' => $aTableField['Field'],
						'db_localtable' => $sTable
					)
				);
			}
		}
		
		foreach($mFields as $sKey => $aField) {
			if(is_array($aField) && $sKey[0] == 'f' && $sKey[1] == 'i' && $sKey[2] == 'e') {
				$aFields[$aField['name']] = $aField;
			}
		}

		if($this->oForm->isRunneable($mFields)) {
			$aFields = $this->callRunneable($mFields, $aFields);
		}
		
		if($this->searchInCurrentColumnIsEnable()) {
			$this->aSearchFields['currentcolumn'] = array(
				'name' => 'currentcolumn',
				'caption' => $this->oForm->getLLLabel('LLL:EXT:ameos_formidable/api/base/rdt_advsearchform/res/locallang/locallang.xml:currentcolumn'),
				'search_type' => self::FORMIDABLE_ADVSEARCHFORM_CURRENTCOL,
				'type' => 'approximately'
			);
		}
		
		
		foreach($aFields as $aField) {
			$this->aSearchFields[$aField['name']] = $aField;
		}
	}
	
	function _initDataSource() {
		if($this->oDataSource === FALSE) {

			if(($sDsToUse = $this->_navConf("/datasource/use")) === FALSE) {
				$this->oForm->mayday("RENDERLET ADVSEARCHFORM - requires /datasource/use to be properly set. Check your XML conf.");
			} elseif(!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
				$this->oForm->mayday("RENDERLET ADVSEARCHFORM - refers to undefined datasource '" . $sDsToUse . "'. Check your XML conf.");
			}

			$this->oDataSource =& $this->oForm->aODataSources[$sDsToUse];
		}
	}
	
	function _initDescendants($bForce = FALSE) {
		if($bForce === TRUE || $this->aDescendants === FALSE) {
			$this->aDescendants = $this->getDescendants();
		}
	}
	
	function getDescendants() {
		$aDescendants = array();
		$sMyName = $this->getAbsName();
		
		$aRdts = array_keys($this->oForm->aORenderlets);
		reset($aRdts);
		while(list(, $sName) = each($aRdts)) {
			if($this->oForm->aORenderlets[$sName]->isDescendantOf($sMyName)) {
				$aDescendants[] = $sName;
			}
		}

		return $aDescendants;
	}
		
	function _initRows() {
		if($this->aSearchRows === FALSE) {
			
			$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];
			if(!$this->shouldUpdateCriterias()) {
				$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["loadedsearch"] = false;
				$aCriterias =& $aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["criterias"];
			
				if(empty($aCriterias)) {
					$aRow = array();
					$sIndex = $this->getNewindex();
					$this->aSearchRows[$sIndex] = $this->makeRow($sIndex, $aRow);
					
				} else {
					foreach($aCriterias as $sKey => $aRow) {
						$sIndex = str_replace('searchrow_', '', $sKey);
						$aRow = $this->_initPostData($aRow);

						$this->aSearchRows[$sIndex] = $this->makeRow($sIndex, $aRow);
					}
				}

			} else {
				
				$aPost = t3lib_div::_POST($this->oForm->formid);
				$aSearformPost = $aPost;
				$aSplitName = explode('.', $this->getAbsName());
				foreach($aSplitName as $sSplitName) {
					$aSearformPost = $aSearformPost[$sSplitName];
				}
				foreach($aSearformPost as $sKey => $aPostRow) {
					if(strpos($sKey, 'searchrow') !== FALSE) {
						$sIndex = str_replace('searchrow_', '', $sKey);
						$aPostRow = $this->_initPostData($aPostRow);

						$this->aSearchRows[$sIndex] = $this->makeRow($sIndex, $aPostRow);					
					}
					
				}
				$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["criterias"] = $this->getSearch();
				$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["loadedsearch"] = false;
				$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["infos"]["lastsearchtime"] = time();
			}
		}
	}
	
	function _initPostData($aData) {
		foreach($aData as $sPostKey => $aValue) {
			if(strpos($sPostKey, 'searchrow_') !== FALSE) {
				$sPostIndex = str_replace('searchrow_', '', $sPostKey);
				$aData['childs'][$sPostIndex] = $aValue;
				$aData['childs'][$sPostIndex] = $this->_initPostData($aData['childs'][$sPostIndex]);
				unset($aData[$sPostKey]);
			}
		}
		
		return $aData;
	}
	
	function makeRow($sIndex, $aData, $sMode = FALSE) {
		if(!empty($this->aSearchFields[$aData['subject']]['mode'])) {
			$sMode = trim($this->aSearchFields[$aData['subject']]['mode']);
		} elseif(!empty($this->aLogicFields[$aData['subject']]['mode'])) {
			$sMode = trim($this->aLogicFields[$aData['subject']]['mode']);
		} else {
			$sMode = 'default';
		}		

		if(empty($this->aSearchFields[$aData['subject']]['type'])) {
			$sType = 'all';
		} else {
			$sType = trim($this->aSearchFields[$aData['subject']]['type']); 
		}
		
		$aRow = array(
			'type' => 'BOX',
			'name' => 'searchrow_' . $sIndex,
			'childs' => array(),
			'class' => $this->getClass('row')
		);

		$sXPath = $this->sXPath . 'fields/searchrow_' . $sIndex . '/';
		$oRow = & $this->oForm->_makeRenderlet($aRow, $sXPath, TRUE, $this, FALSE, FALSE);
		$oRow->aChilds = $this->_makeChildRow($sIndex, $oRow, $aData, $sMode, $sType);
		
		$this->oForm->aORenderlets[$oRow->getAbsName()] =& $oRow;
		
		return $oRow->render();
	}
		
	function _makeSubjectField($sIndex, $oParent, $sValue, $sMode) {
		$aSubjectData = array();

		if($sMode === 'logicbox') {
			$aFields = &$this->aLogicFields;
		} else {
			$aFields = &$this->aSearchFields;
		}

		foreach($aFields as $sField => $aField) {
			if(trim($aField['caption']) === '') {
				$aField['caption'] = 'LLL:' . $this->getAbsName() . '.fields.' . $sField . '.caption';
			}
		
			$aSubjectData[] = array(
				'value' => $sField,
				'caption' => $this->oForm->getLLLabel($aField['caption'])
			);
		}

		$aSubject = array(
			'type' => 'LISTBOX',
			'name' => 'subject',
			'addblank' => ($sMode === 'logicbox') ? FALSE : TRUE,
			'data' => array(
				'value' => $sValue,
				'items' => $aSubjectData
			),
			'class' => $this->getClass('subject', $oParent)
		);

		$sXPath = $oParent->sXPath . 'childs/subject/';
		$oSubject = $this->oForm->_makeRenderlet($aSubject, $sXPath, TRUE, $oParent, FALSE, FALSE);
		
		$this->oForm->aORenderlets[$oSubject->getAbsName()] =& $oSubject;
		return $oSubject;
	}
	
	function _makeTypeField($sIndex, $oParent, $sValue, $sType='all') {
		$sPrefixLLLType = 'LLL:EXT:ameos_formidable/api/base/rdt_advsearchform/res/locallang/locallang.xml:type';
		
		$aExactly = array(
			array(
				'caption' => $this->oForm->getLLLabel($sPrefixLLLType . '.exactly'), 
				'value' => self::FORMIDABLE_ADVSEARCHFORM_EXACTLY
			),
			array(
				'caption' => $this->oForm->getLLLabel($sPrefixLLLType . '.notexactly'),
				'value' => self::FORMIDABLE_ADVSEARCHFORM_NOTEXACTLY
			),
		);
		
		$aApproximately = array(
			array(
				'caption' => $this->oForm->getLLLabel($sPrefixLLLType . '.approximately'), 
				'value' => self::FORMIDABLE_ADVSEARCHFORM_APPROXIMATELY
			),
			array(
				'caption' => $this->oForm->getLLLabel($sPrefixLLLType . '.notapproximately'), 
				'value' => self::FORMIDABLE_ADVSEARCHFORM_NOTAPPROXIMATELY
			),
		);
		
		$aDate = array(
			array(
				'caption' => $this->oForm->getLLLabel($sPrefixLLLType . '.dateexactly'), 
				'value' => self::FORMIDABLE_ADVSEARCHFORM_DATEEXACTLY
			),
			array(
				'caption' => $this->oForm->getLLLabel($sPrefixLLLType . '.datenotexactly'),
				'value' => self::FORMIDABLE_ADVSEARCHFORM_DATENOTEXACTLY
			),
			array(
				'caption' => $this->oForm->getLLLabel($sPrefixLLLType . '.datesuperior'),
				'value' => self::FORMIDABLE_ADVSEARCHFORM_DATESUP
			),
			array(
				'caption' => $this->oForm->getLLLabel($sPrefixLLLType . '.dateinferior'),
				'value' => self::FORMIDABLE_ADVSEARCHFORM_DATEINF
			),
		);

		switch($sType) {
			case 'all':
				$aItems = array_merge($aApproximately, $aExactly);
				break;
				
			case 'approximately':
				$aItems = $aApproximately;
				break;
				
			case 'exactly':
				$aItems = $aExactly;
				break;
				
			case 'date':
				$aItems = $aDate;
				break;
		}

		$aType = array(
			'type' => 'LISTBOX',
			'name' => 'type',
			'label' => '',
			'data' => array(
				'value' => $sValue,
				'items' => $aItems
			),
			'class' => $this->getClass('type', $oParent)
		);
		
		$sXPath = $oParent->sXPath . 'childs/type/';
		$oType = $this->oForm->_makeRenderlet($aType, $sXPath, TRUE, $oParent, FALSE, FALSE);

		$this->oForm->aORenderlets[$oType->getAbsName()] =& $oType;
		return $oType;
	}
	
	function _makeTextValueField($sIndex, $oParent, $sValue) {
		$aTextValue = array(
			'type' => 'TEXT',
			'name' => 'value',
			'data' => array(
				'value' => $sValue,
			),
			'class' => $this->getClass('value', $oParent)
		);
		
		$sXPath = $oParent->sXPath . 'childs/value/';
		$oTextValue = $this->oForm->_makeRenderlet($aTextValue, $sXPath, TRUE, $oParent, FALSE, FALSE);

		$this->oForm->aORenderlets[$oTextValue->getAbsName()] =& $oTextValue;
		return $oTextValue;
	}
	
	function _makeListValueField($sIndex, $oParent, $sValue, $sField) {
		$aItems = array();
		if(trim($sField) != '') {
			$aItems = $this->_getItems($this->aSearchFields[$sField]);
		}

		$aListValue = array(
			'type' => 'LISTBOX',
			'name' => 'value',
			'data' => array(
				'value' => $sValue,
				'items' => $aItems
			),
			'class' => $this->getClass('value', $oParent)
		);
		
		$sXPath = $oParent->sXPath . 'childs/value/';
		$oListValue = $this->oForm->_makeRenderlet($aListValue, $sXPath, TRUE, $oParent, FALSE, FALSE);

		$this->oForm->aORenderlets[$oListValue->getAbsName()] =& $oListValue;
		return $oListValue;
	}

	function _makeChecksingleValueField($sIndex, $oParent, $sValue, $sField) {
		$aTextValue = array(
			'type' => 'CHECKSINGLE',
			'name' => 'value',
			'data' => array(
				'value' => $sValue,
			),
			'class' => $this->getClass('value', $oParent)
		);
		
		$sXPath = $oParent->sXPath . 'childs/value/';
		$oTextValue = $this->oForm->_makeRenderlet($aTextValue, $sXPath, TRUE, $oParent, FALSE, FALSE);

		$this->oForm->aORenderlets[$oTextValue->getAbsName()] =& $oTextValue;
		return $oTextValue;
	}
	
	function _makeDateField($sIndex, $oParent, $sValue) {
		$aDateValue = array(
			'type' => 'DATE',
			'name' => 'value',
			'data' => array(
				'value' => $sValue,
				'datetime' => array(
					'format' => $this->sDateFormat
				)
			),
			'class' => $this->getClass('value', $oParent)
		);
		
		$sXPath = $oParent->sXPath . 'childs/value/';
		$oDateValue = $this->oForm->_makeRenderlet($aDateValue, $sXPath, TRUE, $oParent, FALSE, FALSE);

		$this->oForm->aORenderlets[$oDateValue->getAbsName()] =& $oDateValue;
		
		
				
		return $oDateValue;
	}
	
	function _makeAddButton($sIndex, $oParent) {
		$aAdd = array(
			'type' => 'BUTTON',
			'name' => 'add',
			'label' => '+',
			'class' => $this->getClass('btnadd', $oParent)
		);

		$sXPath = $oParent->sXPath . 'childs/add/';
		$oAdd = $this->oForm->_makeRenderlet($aAdd, $sXPath, TRUE, $oParent, FALSE, FALSE);

		$this->oForm->aORenderlets[$oAdd->getAbsName()] =& $oAdd;
		return $oAdd;	
	}
	
	function _makeRemoveButton($sIndex, $oParent) {
		$aRemove = array(
			'type' => 'BUTTON',
			'name' => 'remove',
			'label' => '-',
			'class' => $this->getClass('btnremove', $oParent)
		);

		$sXPath = $oParent->sXPath . 'childs/remove/';
		$oRemove = $this->oForm->_makeRenderlet($aRemove, $sXPath, TRUE, $oParent, FALSE, FALSE);

		$this->oForm->aORenderlets[$oRemove->getAbsName()] =& $oRemove;
		return $oRemove;
	}
	
	function _makeSubqueryButton($sIndex, $oParent) {
		$aSubquery = array(
			'type' => 'BUTTON',
			'name' => 'subquery',
			'label' => '...',
			'class' => $this->getClass('btnsubquery', $oParent)
		);

		$sXPath = $oParent->sXPath . 'childs/subquery/';
		$oSubquery = $this->oForm->_makeRenderlet($aSubquery, $sXPath, TRUE, $oParent, FALSE, FALSE);

		$this->oForm->aORenderlets[$oSubquery->getAbsName()] =& $oSubquery;
		return $oSubquery;
	}
	
	function _makeChildBox($sIndex, &$oParent, $aValue) {
		if(!empty($this->aSearchFields[$aValue['subject']]['mode'])) {
			$sMode = trim($this->aSearchFields[$aValue['subject']]['mode']);
		} elseif(!empty($this->aLogicFields[$aValue['subject']]['mode'])) {
			$sMode = trim($this->aLogicFields[$aValue['subject']]['mode']);
		} else {
			$sMode = 'default';
		}

		if(empty($this->aSearchFields[$aValue['subject']]['type'])) {
			$sType = 'all';
		} else {
			$sType = trim($this->aSearchFields[$aValue['subject']]['type']); 
		}

		$aBox = array(
			'type' => 'BOX',
			'name' => 'searchrow_' . $sIndex,
			'class' => 'searchrow-indent',
			'childs' => array(),
			'class' => $this->getClass('row', $oParent)
		);

		$sXPath = $oParent->sXPath . 'childs/searchrow_' . $sIndex . '/';
		$oBox = & $this->oForm->_makeRenderlet($aBox, $sXPath, TRUE, $oParent, FALSE, FALSE);
		$oBox->aChilds = $this->_makeChildRow($sIndex, $oBox, $aValue, $sMode, $sType);
		
		$this->oForm->aORenderlets[$oBox->getAbsName()] =& $oBox;

		return $oBox;
	}
	
	function _makeChildRow($sIndex, &$oRow, $aData, $sMode = 'default', $sTypes='all') {
		$aChilds = array();
		$aChilds['subject'] 	= $this->_makeSubjectField($sIndex, $oRow, $aData['subject'], $sMode);
		$aChilds['add'] 		= $this->_makeAddButton($sIndex, $oRow);
		$aChilds['remove'] 	= $this->_makeRemoveButton($sIndex, $oRow);
		
		if($this->logicoperatorIsEnable()) {
			$aChilds['subquery']	= $this->_makeSubqueryButton($sIndex, $oRow);
		}
		
		if ($sMode === 'logicbox') {
			if(empty($aData['childs'])) {
				$sChildIndex = $this->getNewindex();
				$sChildName = 'searchrow_' . $sChildIndex;
				$aChilds[$sChildName]	= $this->_makeChildBox($sChildIndex, $oRow, array());
				
			} else {
				foreach($aData['childs'] as $sChildKey => $aChild) {
					$sChildName = 'searchrow_' . $sChildKey;
					$aChilds[$sChildName]	= $this->_makeChildBox($sChildKey, $oRow, $aChild);
				}
			}

		} elseif($sMode === 'listbox') {
			$aChilds['type'] 	= $this->_makeTypeField($sIndex, $oRow, $aData['type'], $sTypes);
			$aChilds['value'] 	= $this->_makeListValueField($sIndex, $oRow, $aData['value'], $aData['subject']);
	
		} elseif($sMode === 'checksingle') {
		//	$aChilds['type'] 	= $this->_makeTypeField($sIndex, $oRow, $aData['type'], $sTypes);
			$aChilds['value'] 	= $this->_makeChecksingleValueField($sIndex, $oRow, $aData['value'], $aData['subject']);
	
		} elseif($sMode === 'date') {
			if(isset($this->aSearchFields[$aData['subject']]['format'])) {
				$this->sDateFormat = trim($this->aSearchFields[$aData['subject']]['format']); 	
			}
			
			$aChilds['type'] 		= $this->_makeTypeField($sIndex, $oRow, $aData['type'], $sTypes);
			$aChilds['value']		= $this->_makeDateField($sIndex, $oRow, $aData['value'], $this->sDateFormat);
			$this->sDateFormat = '%Y/%m/%d';
		} else {
			$aChilds['type'] 		= $this->_makeTypeField($sIndex, $oRow, $aData['type'], $sTypes);
			$aChilds['value'] 	= $this->_makeTextValueField($sIndex, $oRow, $aData['value']);
		}

		return $aChilds;
	}
		
	function addRow($aParams, $bLogicmode = FALSE) {		
		
		$sIndex = $this->getNewindex();
		$aTarget = explode('.', $aParams['sys_event']['target']);
		if(sizeof($aTarget) > 1) {
			//return $this->oForm->majixDebug($aParams);
			$aDeepTarget = &$aParams['sys_event']['searchrows'];
			foreach($aTarget as $sTargetKey) {
				if(isset($aDeepTarget[$sTargetKey])) {
					$aParentTarget = &$aDeepTarget;
					$aDeepTarget = &$aDeepTarget[$sTargetKey];
				} elseif(isset($aDeepTarget['childs'][$sTargetKey])) {
					$aParentTarget = &$aDeepTarget;
					$aDeepTarget = &$aDeepTarget['childs'][$sTargetKey];
				}
			}
		
			if(isset($aParentTarget)) {
				$sSubject = ($bLogicmode) ? 'or' : '';
				// add a deep level row
				if(!isset($aParentTarget['childs'])) { // first child
					$aParentTarget['childs'][$sIndex] = array(
						'subject' => $sSubject,
						'type' => self::FORMIDABLE_ADVSEARCHFORM_APPROXIMATELY,
						'value' => '',
					);
				} else {
					$sTarget = array_pop($aTarget);
					$aChilds = array();
					foreach($aParentTarget['childs'] as $sChildIndex => $aValue) {
						$aChilds[$sChildIndex] = $aValue;
						if($sChildIndex == $sTarget) {
							$aChilds[$sIndex] = array(
								'subject' => $sSubject,
								'type' => self::FORMIDABLE_ADVSEARCHFORM_APPROXIMATELY,
								'value' => '',
							);
						}
					}
					$aParentTarget['childs'] = $aChilds;
				}
			}
		}		

		$aNewSearchRows = array();
		foreach($this->aSearchRows as $sKey => $aSearchRow) {
			$aNewSearchRows[$sKey] = $this->makeRow($sKey, $aParams['sys_event']['searchrows'][$sKey]);
			if($sKey == $aParams['sys_event']['target']) { // add a first level row
				$aData = ($bLogicmode) ? array('subject' => 'or') : array();
				$aNewSearchRows[$sIndex] = $this->makeRow($sIndex, $aData);
			}
		}
		
		$this->aSearchRows = $aNewSearchRows;

		return $this->majixRepaint();
	}
	
	function removeRow($aParams) {
		$aTarget = explode('.', $aParams['sys_event']['target']);
		if(sizeof($aTarget) > 1) {
			$aDeepTarget = &$aParams['sys_event']['searchrows'];
			
			foreach($aTarget as $sTargetKey) {
				if(isset($aDeepTarget[$sTargetKey])) {
					$aParentTarget = &$aDeepTarget;
					$aDeepTarget = &$aDeepTarget[$sTargetKey];
				} elseif(isset($aDeepTarget['childs'][$sTargetKey])) {
					$aParentTarget = &$aDeepTarget;
					$aDeepTarget = &$aDeepTarget['childs'][$sTargetKey];
				}
			}
			
			unset($aParentTarget['childs'][$sTargetKey]);
			if(empty($aParentTarget['childs'])) {
				unset($aParentTarget['childs']);
			}
		} elseif(sizeof($this->aSearchRows) > 1) {
			unset($this->aSearchRows[$aParams['sys_event']['target']]);
		}
		
		
		foreach($this->aSearchRows as $sKey => $aSearchRow) {
			$this->aSearchRows[$sKey] = $this->makeRow($sKey, $aParams['sys_event']['searchrows'][$sKey]);
		}
		
		return $this->majixRepaint();
	}
	
	function modifyRow($aParams) {
		$aTarget = explode('.', $aParams['sys_event']['target']);
		if(sizeof($aTarget) > 1) {
			$aDeepTarget = &$aParams['sys_event']['searchrows'];
			
			foreach($aTarget as $sTargetKey) {
				if(isset($aDeepTarget[$sTargetKey])) {
					$aParentTarget = &$aDeepTarget;
					$aDeepTarget = &$aDeepTarget[$sTargetKey];
				} elseif(isset($aDeepTarget['childs'][$sTargetKey])) {
					$aParentTarget = &$aDeepTarget;
					$aDeepTarget = &$aDeepTarget['childs'][$sTargetKey];
				}
			}
			
			$aParentTarget['childs'][$sTargetKey]['value'] = '';
			
		} elseif(sizeof($this->aSearchRows) > 1) {
			$this->aSearchRows[$aParams['sys_event']['target']]['value'] = '';
		}
		
		foreach($this->aSearchRows as $sKey => $aSearchRow) {
			$this->aSearchRows[$sKey] = $this->makeRow($sKey, $aParams['sys_event']['searchrows'][$sKey]);			
		}

		return $this->majixRepaint();
	}
	
	function addSubquery($aParams) {
		return $this->addRow($aParams, TRUE);
	}
	
	function mayHaveChilds() {
		return TRUE;
	}
	
	function _searchable() {
		return FALSE;
	}
	
	function includeScripts($aConf = array()) {
		parent::includeScripts($aConf);
		
		$sAbsName = $this->_getElementHtmlIdWithoutFormId();

		$sInitScript =<<<INITSCRIPT
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").init();
INITSCRIPT;
				
		$this->oForm->attachPostInitTask(
			$sInitScript,
			"Post-init ADVSEARCHFORM initialization",
			$this->_getElementHtmlId()
		);
	}
	
	function isLoadedSearch() {
		$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];
		$iLoadedSearch = $aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["loadedsearch"];
		
		return ($iLoadedSearch === TRUE);
	}
	
	function shouldUpdateCriterias() {
		if($this->_isSubmitted() && !$this->isLoadedSearch()) {
			return TRUE;
		}
		
		return FALSE;
	}
	
	function &fetchData($aConfig = array()) {
		return $this->_fetchData($aConfig);
	}
	
	function &_fetchData($aConfig = array()) {
		if($this->defaultFalse('/keepalllistidinsession')) {
			$aListUidConfig = array(
				'page' => 0,
				'sortcolumn' => $aConfig['sortcolumn'],
				'sortdirection' => $aConfig['sortdirection'],
			);

			$aRecords = $this->oDataSource->_fetchData(
				$aListUidConfig,
				$this->_getFilters()
			);

			foreach($aRecords['results'] as $aRecord) {
				$aList[] = $aRecord['uid'];
			}

			$aData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["infos"];
			$aData["list"] = $aList;
		}
		
		
		$aResults = $this->processAfterSearch(
			$this->oDataSource->_fetchData(
				$aConfig,
				$this->_getFilters()
			)
		);

		if($this->defaultFalse("/keepresultsinsession") === TRUE) {
			$this->storeResultsInSession($aResults);			
		}

		reset($aResults);
		return $aResults;
	}
	
	function &_getFilters() {
		if($this->aFilters === FALSE) {
			$this->_initFilters();
		}
		reset($this->aFilters);
		return $this->aFilters;
	}
	
	function _initFilters() {
		$this->aFilters = array();
		
		foreach($this->aSearchRows as $aSearchRow) {
			if(!empty($this->aSearchFields[$aSearchRow['childs']['subject']['value']])) {
				$sMode = $this->aSearchFields[$aSearchRow['childs']['subject']['value']]['mode'];
			} else {
				$sMode = $this->aLogicFields[$aSearchRow['childs']['subject']['value']]['mode'];
			}

			if(trim($sMode) == '') {
				$sMode = 'default';
			}

			if(isset($aSearchRow['childs']['value'])) {
				if($sMode == 'date') {
					$mValue = $aSearchRow['childs']['value']['value.']['timestamp'];
				} else {
					$mValue = $aSearchRow['childs']['value']['value'];
				}
				
			} else {
				$mValue = $aSearchRow['childs'];
			}

			$this->aFilters[] = $this->constructSearchClause(
				$aSearchRow['childs']['subject']['value'],
				$aSearchRow['childs']['type']['value'],
				$mValue
			);									
		}
	//	debug($this->aFilters);
		reset($this->aFilters);
	}
	
	function constructSearchClause($sSubject, $sType, $mValue) {
		if(isset($this->aSearchFields[$sSubject])) {
			$aField = &$this->aSearchFields[$sSubject];
		} else {
			$aField = &$this->aLogicFields[$sSubject];
		}

		if(isset($aField[$sSubject]['format'])) {
			$this->sDateFormat = trim($aField[$sSubject]['format']);
		}

		switch(trim($aField['search_type'])) {
			case self::FORMIDABLE_ADVSEARCHFORM_LOCAL:
				return $this->constructLocalSearchClause(
					$aField['search_config'], 
					$sType, 
					$mValue
				);
				break;
			
			case self::FORMIDABLE_ADVSEARCHFORM_FOREIGN:
				return $this->constructForeignSearchClause(
					$aField['search_config'], 
					$sType, 
					$mValue
				);
				break;
			
			case self::FORMIDABLE_ADVSEARCHFORM_MMFOREIGN:
				//$this->oForm->mayday('RENDERLET ADVSEARCHFORM : no mm search for the moment');
				return $this->constructMMSearchClause(
					$aField['search_config'],
					$sType, 
					$mValue
				);
				break;
				
			case self::FORMIDABLE_ADVSEARCHFORM_CUSTOM:
				return $this->constructCustomSearchClause(
					$aField['search_config'],
					$sType, 
					$mValue
				);
				break;
			
			case self::FORMIDABLE_ADVSEARCHFORM_CURRENTCOL:
				return $this->constructCurrentColumnClause($sType, $mValue);
				break;
			
			case self::FORMIDABLE_ADVSEARCHFORM_OR:
				return $this->constructLogicClause($mValue, 'OR');
				break;
				
			case self::FORMIDABLE_ADVSEARCHFORM_AND:
				return $this->constructLogicClause($mValue, 'AND');
				break;
		}
		$this->sDateFormat = '%Y/%m/%d';
		return '(1=1)';
	}
	
	function constructLogicClause($aData, $sOperator) {
		$aQuery = array();
		foreach($aData as $sKey => $aChild) {
			if(strpos($sKey, 'searchrow_') !== FALSE) {

				if(isset($aChild['childs']['value'])) {
					$mValue = $aChild['childs']['value']['value'];
				} else {
					$mValue = $aChild['childs'];
				}
			
				$aQuery[] = $this->constructSearchClause(
					$aChild['childs']['subject']['value'],
					$aChild['childs']['type']['value'],
					$mValue
				);
			}
		}
		
		if(empty($aQuery)) {
			$sQuery = '(1=1)';
		} else {
			$sQuery = '( ( ';
			$sQuery.= implode(' ) ' . $sOperator . ' ( ', $aQuery);
			$sQuery.= ' ) )';
		}
		
		return $sQuery;
	}
	
	function constructLocalSearchClause($aConfig, $sType, $sValue) {
		$sQuery = '( ' . $aConfig['db_localtable'] . '.' . $aConfig['db_searchfield'];
		$sQuery.= $this->sqlClause($sType, $sValue, $aConfig['db_localtable']);
		$sQuery.= ' )';

		return $sQuery;
	}
	
	function constructForeignSearchClause($aConfig, $sType, $sValue) {
		$sQuery = '( ';
	
		$sQuery.= $aConfig['db_localtable'] . '.' . $aConfig['db_localkey'];
		$sQuery.= ' IN (';		
		$sQuery.= ' SELECT ' . $aConfig['db_foreigntable'] . '.' . $aConfig['db_foreignkey'] . 
			' FROM ' . $aConfig['db_foreigntable'] . 
			' WHERE ' . $aConfig['db_foreigntable'] . '.' . $aConfig['db_searchfield'];
		
		$sQuery.= $this->sqlClause($sType, $sValue, $aConfig['db_foreigntable']);
					
		$sQuery.= ' ) )';
		return $sQuery;
	}
	
	function constructMMSearchClause($aConfig, $sType, $sValue) {
		
		$sQuery = '( ';
	
		$sQuery.= $aConfig['db_localtable'] . '.' . $aConfig['db_localkey'];
		$sQuery.= ' IN (';		
		$sQuery.= ' SELECT ' . $aConfig['db_mmtable'] . '.' . $aConfig['db_mmlocalkey'] . 
			' FROM ' . $aConfig['db_mmtable'] . ',' . $aConfig['db_foreigntable'] . 
			' WHERE ' . 
				$aConfig['db_mmtable'] . '.' . $aConfig['db_mmforeignkey'] . '=' . $aConfig['db_foreigntable'] . '.' . $aConfig['db_foreignkey'] . ' AND ' .  
				$aConfig['db_foreigntable'] . '.' . $aConfig['db_searchfield'];
				
		$sQuery.= $this->sqlClause($sType, $sValue, $aConfig['db_foreigntable']);
	
		$sQuery.= ' ) )';
		
		return $sQuery;
	}
	
	function constructCustomSearchClause($aConfig, $sType, $sValue) {
		if($this->oForm->isRunneable($aConfig)) {
			$sQuery = $this->oForm->callRunneable($aConfig, array(
				'type' => $sType,
				'value' => $sValue
			));
		}
		
		return $sQuery;
	}
	
	function sqlClause($sType, $sValue, $sTable) {
		switch($sType) {
			case self::FORMIDABLE_ADVSEARCHFORM_EXACTLY:
				$sQuery = ' = \'' . $GLOBALS["TYPO3_DB"]->quoteStr($sValue, $sTable) . '\'';
				break;
			
			case self::FORMIDABLE_ADVSEARCHFORM_APPROXIMATELY:
				$sQuery = ' LIKE \'%' . $GLOBALS["TYPO3_DB"]->quoteStr($sValue, $sTable) . '%\'';
				break;
			
			case self::FORMIDABLE_ADVSEARCHFORM_NOTEXACTLY:
				$sQuery = ' <> \'' . $GLOBALS["TYPO3_DB"]->quoteStr($sValue, $sTable) . '\'';
				break;
			
			case self::FORMIDABLE_ADVSEARCHFORM_NOTAPPROXIMATELY:
				$sQuery = ' NOT LIKE \'%' . $GLOBALS["TYPO3_DB"]->quoteStr($sValue, $sTable) . '%\'';
				break;
				
			case self::FORMIDABLE_ADVSEARCHFORM_DATEEXACTLY:
				if(trim($sValue) == '') {
					return ' LIKE \'%%\'';
				}
				$sQuery = ' BETWEEN ' .
					$this->formatDate($sValue, 'begin') . ' AND ' . 
					$this->formatDate($sValue, 'end');
				break;
				
			case self::FORMIDABLE_ADVSEARCHFORM_DATENOTEXACTLY:
				if(trim($sValue) == '') {
					return ' LIKE \'%%\'';
				}
				$sQuery = ' NOT BETWEEN ' .
					$this->formatDate($sValue, 'begin') . ' AND ' . 
					$this->formatDate($sValue, 'end');
				break;
			
			case self::FORMIDABLE_ADVSEARCHFORM_DATESUP:
				if(trim($sValue) == '') {
					return ' LIKE \'%%\'';
				}
				$sQuery = ' > ' . $this->formatDate($sValue, 'end');
				break;
					
			case self::FORMIDABLE_ADVSEARCHFORM_DATEINF:
				if(trim($sValue) == '') {
					return ' LIKE \'%%\'';
				}
				$sQuery = ' < ' . $this->formatDate($sValue, 'begin');
				break;
		}
		
		return $sQuery;
	}
	
	function constructCurrentColumnClause($sType, $sValue) {
		$oListerRdt = FALSE;
		foreach($this->oForm->aORenderlets as $oRdt) {
			if(trim($oRdt->_navConf('/type')) === 'LISTER') {
				if($oRdt->oDataStream->getAbsName() == $this->getAbsName()) {
					$oListerRdt = $oRdt;
				}
			}
		}
		
		$aQuery = array();
		if($oListerRdt !== FALSE) {
			foreach($oListerRdt->getSearchableColumns() as $sColumn) {
				//if(!in_array($sColumn, $aExcludeColumn)) {
					switch($sType) {
						case self::FORMIDABLE_ADVSEARCHFORM_APPROXIMATELY:
							$aQuery[] = '(' . 
								$sColumn . 
								' LIKE \'%' . 
								$GLOBALS["TYPO3_DB"]->quoteStr($sValue, '') . 
								'%\')';
							break;
		
						case self::FORMIDABLE_ADVSEARCHFORM_NOTAPPROXIMATELY:
							$aQuery[] = '(' . 
								$sColumn . 
								' NOT LIKE \'%' . 
								$GLOBALS["TYPO3_DB"]->quoteStr($sValue, '') . 
								'%\')';
							break;
					}
				//}
			}
		}
		
		if(empty($aQuery)) {
			$aQuery[] = '1=1';
		}
		
		$sQuery = '( ' . implode(' OR ', $aQuery) . ' )';
		return $sQuery;
	}
		
	function processAfterSearch($aResults) {	
		if(($aAfterSearch = $this->_navConf("/aftersearch")) !== FALSE && $this->oForm->isRunneable($aAfterSearch)) {
			$aResults = $this->callRunneable($aAfterSearch, $aResults);
		}

		if(!is_array($aResults)) {
			$aResults = array();
		}

		return $aResults;
	}
	
	function majixResetSearchForm() {
		$this->aSearchRows = FALSE;
		$this->aFilters = FALSE;
		
		$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];
		$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["criterias"] = array();
		$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["childs"] = array();
		$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["loadedsearch"] = true;
		$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["infos"]["lastsearchtime"] = time();
		
		return array(
			$this->majixRepaint(),
			$this->majixSubmitSearch()
		);
	}
	
	function logicoperatorIsEnable() {
		return $this->defaultTrue('/enablelogicsearchoperator');
	}
	
	function searchInCurrentColumnIsEnable() {
		return $this->defaultFalse('/enableseachincurrentcolumn');
	}
	
	function formatDate($mDate, $sMode = 'begin') {
		if(strpos($mDate, '/') === FALSE) {
			$mDate = strftime($this->sDateFormat, $mDate);
		} 
		
		$aDate = strptime($mDate, $this->sDateFormat);
		if($sMode == 'begin') {
			return mktime(0, 0, 0, $aDate['tm_mon'] + 1, $aDate['tm_mday'], $aDate['tm_year'] + 1900);
		} else {
			return mktime(23, 59, 59, $aDate['tm_mon'] + 1, $aDate['tm_mday'], $aDate['tm_year'] + 1900);
		}
		
		return $mDate;
	}
	
	function getNewindex() {
		return t3lib_div::shortMD5(microtime() . '-' . mt_rand(), 4);
	}
	
	function getAutoLabelForField($sField, $sTable) {
		// locallang
		$sAutoMap = "LLL:" . $this->getAbsName() . ".fields." . $sField . ".caption";
		if($this->oForm->sDefaultLLLPrefix !== FALSE && (($sAutoLabel = trim($this->oForm->_getLLLabel($sAutoMap))) !== "")) {
			return $sAutoLabel;
		}
					
		// TCA
		$sAutoLabel = trim($this->oForm->_getLLLabel($GLOBALS["TCA"][$sTable]['columns'][$sField]['label']));
		if($sAutoLabel !== '') {
			return $sAutoLabel;
		}
	
		return $sField;	
	}
	
	function getClass($sClass, $oParent = FALSE) {
		$iDeepLevel = 0;
		if($oParent !== FALSE) {
			while($oParent->hasParent()) {
				$oParent = $oParent->oRdtParent;
				$iDeepLevel++;
			}
		} 
		
		if($sClass === 'row') {
			$iDeepLevel++;
		}
		
		return $this->getAbsName() . '-' . $sClass . ' level-' . $iDeepLevel;
	}
	
	function getSearch() {
		$aSearch = array();
		$aEnableField = array('subject', 'type', 'value');
		//debug($this->aSearchRows);
		foreach($this->aSearchRows as $sKey => $aValue) {
			foreach($aValue['childs'] as $sChildName => $aChild) {
				if(in_array($sChildName, $aEnableField)) {
					$aSearch[$sKey][$sChildName] = $aChild['value'];
				}
			
				if(strpos($sChildName, 'searchrow_') !== FALSE) {
					$sChildKey = str_replace('searchrow_', '', $sChildName);
					$aSearch[$sKey]['childs'][$sChildKey] = $this->_getChildSearch($aChild['childs']);
				}
			}
		}

		return $aSearch;		
	}
	
	function _getChildSearch($aChild) {
		$aSearch = array();
		$aEnableField = array('subject', 'type', 'value');
			
		foreach($aChild as $sChildName => $aValue) {
			if(in_array($sChildName, $aEnableField)) {
				$aSearch[$sChildName] = $aValue['value'];
			}
		
			if(strpos($sChildName, 'searchrow_') !== FALSE) {
				$sChildKey = str_replace('searchrow_', '', $sChildName);
				$aSearch['childs'][$sChildKey] = $this->_getChildSearch($aValue['childs']);
			}
		}

		
		return $aSearch;
	}
	
	function searchManagementIsEnable() {
		return ($this->_navConf('/searchmanagement') !== FALSE);
	}
	
	function _initSearchmanagement() {
		if(($aGlobalConfig = $this->_navConf('/searchmanagement/globalsearch')) !== FALSE) {
			$this->initSaveSearchGlobal($aGlobalConfig);
		}
		
		if(($aConfigs = $this->_navConf('/searchmanagement')) !== FALSE) {
			foreach($aConfigs as $sKey => $aConfig) {
				if($sKey[0] == 's' && $sKey[1] == 'a' && $sKey[2] == 'v') {
					if(trim($aConfig['for']) === 'connecteduser' && $this->oForm->userIsAuthentified()) {
						switch(trim($aConfig['mode'])) {
							case 'database':
								$this->initSaveSearchFromDb($aConfig);
								break;

							case 'cookie':
								$this->initSaveSearchFromCookie();
								break;
						}
					}
				
					if(trim($aConfig['for']) === 'notconnecteduser' && !$this->oForm->userIsAuthentified()) {
						switch(trim($aConfig['mode'])) {
							case 'database':
								$this->initSaveSearchFromDb($aConfig);
								break;
				
							case 'cookie':
								$this->initSaveSearchFromCookie();
								break;
						}
					}
				}
			}
		}

		$this->aRdtSearchmangement['managesearchbtn'] = $this->_makeSearchManagementButton();
		$this->aRdtSearchmangement['managesearchbox'] = $this->_makeSearchManagementBox();
	}	

	function initSaveSearchGlobal($aConfig) {
		$rSql = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $aConfig['table'], '1');
		while(($aSearch = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rSql)) !== FALSE) {
			$this->aSaveSearch['global:' . $aSearch['uid']] = array(
				'name'  => $aSearch[$aConfig['namefield']],
				'value' => unserialize($aSearch[$aConfig['searchfield']])
			);
		}
	}
	
	function initSaveSearchFromCookie() {
		if(isset($_COOKIE['formidablesearch'][$this->oForm->formid])) {
			$sSearch = str_replace("\\", "", $_COOKIE['formidablesearch'][$this->oForm->formid]);
			$aSearch = unserialize($sSearch);

			foreach($aSearch as $sName => $aValue) {
				$sKey = t3lib_div::shortMD5(serialize($aValue));
				$this->aSaveSearch[$sKey]['name'] = $sName;
				$this->aSaveSearch[$sKey]['value'] = $aValue;
			}
		}
	}
	
	function initSaveSearchFromDb($aConfig) {
		if($aConfig['key'] == 'userkey') {
			$sWhereClause = 'uid=' . $GLOBALS['TSFE']->fe_user->user['uid'];
		} else {
			if($this->oForm->isRunneable($aConfig['key']['value'])) {
				$aConfig['key']['value'] = $this->callRunneable($aConfig['key']['value']);
			}
			
			$sWhereClause = $aConfig['key']['name'] . '=' . $aConfig['key']['value'];
		}		
		
		if(isset($aConfig['key']['value']) && $aConfig['key']['value'] !== 'new') {
			// update
			$aCurrent = array_shift($GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				$aConfig['field'],
				$aConfig['table'],
				$sWhereClause
			));
			
			$aSearch = unserialize($aCurrent[$aConfig['field']]);
			if(is_array($aSearch[$this->oForm->formid])) {
				foreach($aSearch[$this->oForm->formid] as $sName => $aValue) {
					$sKey = t3lib_div::shortMD5(serialize($aValue));
					$this->aSaveSearch[$sKey]['name'] = $sName;
					$this->aSaveSearch[$sKey]['value'] = $aValue;
				}
			}
		}
	}
	
	function _makeSearchManagementButton() {
		$aConf = array(
			'type' => 'BUTTON',
			'name' => 'managesearchbtn',
			'label' => $this->oForm->getLLLabel('LLL:EXT:ameos_formidable/api/base/rdt_advsearchform/res/locallang/locallang.xml:searchmanagement.managesearchbtn.label'),
		);
				
		$sXPath = $this->sXPath . 'searchmanagement/managesearchbtn/';
		$oBtn = $this->oForm->_makeRenderlet($aConf, $sXPath, TRUE, $this, FALSE, FALSE);

		$this->oForm->aORenderlets[$oBtn->getAbsName()] =& $oBtn;
		return $oBtn->_render();
	}
	
	function _makeSearchManagementBox() {
		$this->oForm->additionalHeaderDataLocalStylesheet(
			$this->sExtPath . 'res/css/default-css.css',
			"rdt_advsearchform_css"
		);
		
		if(($aTemplate = $this->_navConf('/searchmanagement/template')) === FALSE) {
			$aTemplate = array(
				'path' => $this->sExtPath . 'res/html/default-template.html',
				'subpart' => '###DEFAULT_ADVSEARCHFORM_BOX###'
			);
		}
		
		// box
		$aConf = array(
			'type' => 'BOX',
			'name' => 'managesearchbox',
			'childs' => array(
				'template' => $aTemplate,
				'renderlet' => array(
					'type' => 'TEXT', 
					'name' => 'savesearchname',
					'label' => $this->oForm->getLLLabel($this->sPrefixLLL . '.savesearchname.label')
				)
			),
			'class' => 'formidable_managesearchbox'
		);
		
		$sXPath = $this->sXPath . 'childs/managesearchbox/';
		$oBox = $this->oForm->_makeRenderlet($aConf, $sXPath, TRUE, $this, FALSE, FALSE);
		$oBox->initChilds();
		
		$this->oForm->aORenderlets[$oBox->getAbsName()] =& $oBox;
		
		$aOChilds = $this->_initSeachManagementBoxChilds($oBox);
		foreach($aOChilds as $oChild) {
			$oBox->aChilds[$oChild->_getNameWithoutPrefix()] = $oChild;
		}
		
		return $oBox->_render();
	}
	
	function getSearchItems() {
		$aItems = array();
		if(is_array($this->aSaveSearch)) {
			foreach($this->aSaveSearch as $sKey => $aSearch) {
				$aItems[$sKey] = array(
					'value' => $sKey,
					'caption' => $aSearch['name']
				);
			}
		}
		return $aItems;
	}
	
	function getSearchmanagementLabel($sName) {
		$sDefaultPrefixLLL = 'LLL:EXT:ameos_formidable/api/base/rdt_advsearchform/res/locallang/locallang.xml:searchmanagement';
		
		if(($sLabel = $this->_navConf('/searchmanagement/labels/' . $sName)) === FALSE) {
			if($this->oForm->getLLLabel('LLL:' . $this->getAbsName() . '.searchmanagement.' . $sName . '.label') == '') {
				$sLabel = $this->oForm->getLLLabel($sDefaultPrefixLLL . '.' . $sName . '.label');
			} else {
				$sLabel = $this->oForm->getLLLabel('LLL:' . $this->getAbsName() . '.searchmanagement.' . $sName . '.label');
			}
		}
		
		return $sLabel;
	}
	
	function _initSeachManagementBoxChilds(&$oBox) {

		// save search
		$aSearchsavename = array(
			'type' => 'TEXT', 
			'name' => 'savesearchname',
			'label' => $this->getSearchmanagementLabel('savesearchname'),
		);
		$sXPath = $oBox->sXPath . 'childs/savesearchname/';
		$oSearchsavename = $this->oForm->_makeRenderlet($aSearchsavename, $sXPath, TRUE, $oBox, FALSE, FALSE);
		
		$aSearchsavebtn = array(
			'type' => 'BUTTON', 
			'name' => 'savesearchbtn',
			'label' => $this->getSearchmanagementLabel('savesearchbtn'),
		);
		$sXPath = $oBox->sXPath . 'childs/savesearchbtn/';
		$oSearchsavebtn = $this->oForm->_makeRenderlet($aSearchsavebtn, $sXPath, TRUE, $oBox, FALSE, FALSE);
		
		
		$aGlobalsearchbtn = array(
			'type' => 'CHECKSINGLE', 
			'name' => 'globalsearchbtn',
			'label' => $this->getSearchmanagementLabel('globalsearchbtn'),
		);
		$sXPath = $oBox->sXPath . 'childs/globalsearchbtn/';
		$oGlobalsearchbtn = $this->oForm->_makeRenderlet($aGlobalsearchbtn, $sXPath, TRUE, $oBox, FALSE, FALSE);
	
		// load search
		$aSearchItems = $this->getSearchItems();
		$aSearchloadname = array(
			'type' => 'LISTBOX', 
			'name' => 'loadsearchname',
			'label' => $this->getSearchmanagementLabel('loadsearchname'),
			'addblank' => true,
			'data' => array(
				'items' => $aSearchItems
			)
		);
		$sXPath = $oBox->sXPath . 'childs/loadsearchname/';
		$oSearchloadname = $this->oForm->_makeRenderlet($aSearchloadname, $sXPath, TRUE, $oBox, FALSE, FALSE);
		
		
		$aSearchloadbtn = array(
			'type' => 'BUTTON', 
			'name' => 'loadsearchbtn',
			'label' => $this->getSearchmanagementLabel('loadsearchbtn'),
		);
		$sXPath = $oBox->sXPath . 'childs/loadsearchbtn/';
		$oSearchloadbtn = $this->oForm->_makeRenderlet($aSearchloadbtn, $sXPath, TRUE, $oBox, FALSE, FALSE);
		
		
		$aSearchremovebtn = array(
			'type' => 'BUTTON', 
			'name' => 'removesearchbtn',
			'label' => $this->getSearchmanagementLabel('removesearchbtn'),
		);
		$sXPath = $oBox->sXPath . 'childs/removesearchbtn/';
		$oSearchremovebtn = $this->oForm->_makeRenderlet($aSearchremovebtn, $sXPath, TRUE, $oBox, FALSE, FALSE);
		
		return array(
			'savesearchname' => &$oSearchsavename,
			'savesearchbtn' => &$oSearchsavebtn,
			'loadsearchname' => &$oSearchloadname,
			'loadsearchbtn' => &$oSearchloadbtn,
			'removesearchbtn' => &$oSearchremovebtn,
			'globalsearchbtn' => &$oGlobalsearchbtn
		);
	}
	
	function saveSearch($aParams) {
		if($aParams['sys_event']['globalsearch'] == 1) {
			$this->saveSearchInDbForAll($aParams['sys_event']['savesearch']);
		} else {		
			if(($aConfigs = $this->_navConf('/searchmanagement')) !== FALSE) {
				foreach($aConfigs as $aConfig) {
				
					if(trim($aConfig['for']) === 'connecteduser' && $this->oForm->userIsAuthentified()) {
						$this->_saveSearch($aConfig, $aParams['sys_event']['savesearch']);
					}
					
					if(trim($aConfig['for']) === 'notconnecteduser' && !$this->oForm->userIsAuthentified()) {
						$this->_saveSearch($aConfig, $aParams['sys_event']['savesearch']);
					}
				}		
			}
		}
		
		return $this->majixSubmitSearch();
	}
	
	function _saveSearch($aConfig, $sName) {
		switch(trim($aConfig['mode'])) {
			case 'database':
				$this->saveSearchInDbForUser($aConfig, $sName);
				break;
				
			case 'cookie':
				$this->saveSearchInCookie($aConfig, $sName);
				break;
		}
	}

	function saveSearchInDbForAll($sName) {
		if(($aConfig = $this->_navConf('/searchmanagement/globalsearch')) !== FALSE) {
			$aData = array(
				'cruser_id' => $GLOBALS['TSFE']->fe_user->user['uid'],
				'crdate'    => time(),
				'tstamp'    => time(),
				$aConfig['searchfield'] => serialize($this->getSearch()),
				$aConfig['namefield']   => $sName,
			);
			
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($aConfig['table'], $aData);
		}
	}
	
	function saveSearchInDbForUser($aConfig, $sName) {
		if($aConfig['key'] == 'userkey') {
			$sWhereClause = 'uid=' . $GLOBALS['TSFE']->fe_user->user['uid'];
		} else {
			if($this->oForm->isRunneable($aConfig['key']['value'])) {
				$aConfig['key']['value'] = $this->callRunneable($aConfig['key']['value']);
			}
			
			$sWhereClause = $aConfig['key']['name'] . '=' . $aConfig['key']['value'];
		}		
		
		if($aConfig['key']['value'] == 'new') {
			// new		
			$aNew = array();
			$aNew[$this->oForm->id][$sName] = $this->getSearch();
			$sNew = serialize($aNew);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				$aConfig['table'],
				array($aConfig['field'] => $sNew)
			);
		} else {
			// update
			$aCurrent = array_shift($GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				$aConfig['field'],
				$aConfig['table'],
				$sWhereClause
			));
			
			//debug($this->getSearch(), 'getSearch');die();
			
			$aSearch = unserialize($aCurrent[$aConfig['field']]);
			$aSearch[$this->oForm->formid][$sName] = $this->getSearch();
			$sSearch = serialize($aSearch);
			
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				$aConfig['table'],
				$sWhereClause,
				array($aConfig['field'] => $sSearch)
			);		
		}
	}
	
	function saveSearchInCookie($aConfig, $sName) {
		$sCurrent = $_COOKIE['formidablesearch'][$this->oForm->formid];
		$sCurrent = str_replace("\\", "", $sCurrent);
		$aCurrent = unserialize($sCurrent);
		
		$aCurrent[$sName] = $this->getSearch();
				
		$sCookiename  = 'formidablesearch[' . $this->oForm->formid . ']';
		$sCookievalue = serialize($aCurrent);
		$iCookieexpire= time()+60*60*24*30;
		
		setcookie($sCookiename, $sCookievalue, $iCookieexpire);
	}
	
	function loadSearch($aParams) {
		$aSearch = $this->aSaveSearch[$aParams["sys_event"]["loadsearch"]]["value"];
		
		$aAppData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"];
		$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["criterias"] = $aSearch;
		$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["loadedsearch"] = true;
		$aAppData["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["infos"]["lastsearchtime"] = time();
		
		return $this->majixSubmitSearch();
	}
	
	function removeSearch($aParams) {
		if(strpos($aParams['sys_event']['loadsearch'], 'global:') !== FALSE) {
			$this->removeSearchInDbForAll($aParams['sys_event']['loadsearch']);
		} else {
			if(($aConfigs = $this->_navConf('/searchmanagement')) !== FALSE) {
				foreach($aConfigs as $aConfig) {				
					if(trim($aConfig['for']) === 'connecteduser' && $this->oForm->userIsAuthentified()) {
						$this->_removeSearch($aConfig, $aParams['sys_event']['loadsearch']);
					}
					
					if(trim($aConfig['for']) === 'notconnecteduser' && !$this->oForm->userIsAuthentified()) {
						$this->_removeSearch($aConfig, $aParams['sys_event']['loadsearch']);
					}
				}		
			}
		}

		return $this->majixSubmitSearch();
	}
	
	function _removeSearch($aConfig, $sSearch) {
		switch(trim($aConfig['mode'])) {
			case 'database':
				$this->removeSearchInDbForUser($aConfig, $sSearch);
				break;
				
			case 'cookie':
				$this->removeSearchInCookie($aConfig, $sSearch);
				break;
		}
	}

	function removeSearchInDbForAll($sSearchkey) {
		if(($aConfig = $this->_navConf('/searchmanagement/globalsearch')) !== FALSE) {
			$iSearchUid = (int)str_replace('global:', '', $sSearchkey);
			$aData = array('deleted' => 1, 'tstamp'    => time());			
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('uid = ' . $iSearchUid, $aConfig['table'], $aData);
		}
	}
	
	function removeSearchInDbForUser($aConfig, $sSearchkey) {
		$sSearchname = $this->aSaveSearch[$sSearchkey]["name"];
		if($aConfig['key'] == 'userkey') {
			$sWhereClause = 'uid=' . $GLOBALS['TSFE']->fe_user->user['uid'];
		} else {
			if($this->oForm->isRunneable($aConfig['key']['value'])) {
				$aConfig['key']['value'] = $this->callRunneable($aConfig['key']['value']);
			}
			
			$sWhereClause = $aConfig['key']['name'] . '=' . $aConfig['key']['value'];
		}		
		
		if($aConfig['key']['value'] !== 'new') {
			// update
			$aCurrent = array_shift($GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				$aConfig['field'],
				$aConfig['table'],
				$sWhereClause
			));
			
			$aSearch = unserialize($aCurrent[$aConfig['field']]);
			unset($aSearch[$this->oForm->formid][$sSearchname]);
			$sSearch = serialize($aSearch);
			
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				$aConfig['table'],
				$sWhereClause,
				array($aConfig['field'] => $sSearch)
			);		
		}
	}
	
	function removeSearchInCookie($aConfig, $sSearchkey) {
		$sSearchname = $this->aSaveSearch[$sSearchkey]["name"];
		
		$sCurrent = $_COOKIE['formidablesearch'][$this->oForm->formid];
		$sCurrent = str_replace("\\", "", $sCurrent);
		$aCurrent = unserialize($sCurrent);
		
		unset($aCurrent[$sSearchname]);
				
		$sCookiename  = 'formidablesearch[' . $this->oForm->formid . ']';
		$sCookievalue = serialize($aCurrent);
		$iCookieexpire= time()+60*60*24*30;
		
		setcookie($sCookiename, $sCookievalue, $iCookieexpire);
	}
	
	function storeResultsInSession($aResults) {
		$aData =& $GLOBALS["_SESSION"]["ameos_formidable"]["applicationdata"]["rdt_lister"][$this->getSearchHash()][$this->getAbsName()]["infos"];
		$aData["results"] = $aResults["results"];
		$aData["numrows"] = $aResults["numrows"];
	}
	
	function getSearchHash() {
		if($this->oForm->useFHash()) {
			return $this->oForm->getFHash();
		} else {
			return $this->oForm->formid;
		}
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_advsearchform/api/class.tx_rdtadvsearchform.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_advsearchform/api/class.tx_rdtadvsearchform.php"]);
}

?>
