<?php
/** 
 * Plugin 'ds_php' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

class tx_dscontentrepository extends formidable_maindatasource {

	var $sKey = FALSE;
	var $oRepo = FALSE;

	function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = FALSE) {
		parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);
		
		if(!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded("extbase")) {
			$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] The Content Repository API is <b>not loaded</b>, and should be (<b>EXT:extbase</b>).");
		}

		$this->loadContentRepositoryFramework();
		$this->loadAggregates();
		$this->loadRepository();
	}

	function loadContentRepositoryFramework() {
		$sExtBasePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath("extbase");
		require_once($sExtBasePath . "Classes/Utility/Strings.php");
		require_once($sExtBasePath . "Classes/Exception.php");
		require_once($sExtBasePath . "Classes/Persistence/Session.php");
		require_once($sExtBasePath . "Classes/Persistence/ObjectStorage.php");
		require_once($sExtBasePath . "Classes/Persistence/Mapper/DataMap.php");
		require_once($sExtBasePath . "Classes/Persistence/Mapper/ColumnMap.php");
		require_once($sExtBasePath . "Classes/Persistence/Mapper/ObjectRelationalMapper.php");
		require_once($sExtBasePath . "Classes/Persistence/RepositoryInterface.php");
		require_once($sExtBasePath . "Classes/Persistence/Repository.php");
		require_once($sExtBasePath . "Classes/DomainObject/DomainObjectInterface.php");
		require_once($sExtBasePath . "Classes/DomainObject/AbstractDomainObject.php");
	}

	function loadRepository() {
		if($this->oRepo === FALSE) {
			if($this->_navConf("/repository") === FALSE) {
				$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] You have to provide <b>/repository</b>.");
			}

			if(($sClassFile = $this->_navConf("/repository/classfile")) === FALSE) {
				$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] You have to provide <b>/repository/classFile</b>.");
			} else {
				$sClassFile = $this->oForm->toServerPath($sClassFile);
				
				if(!file_exists($sClassFile)) {
					$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] The given <b>/repository/classFile</b> given (" . $sClassFile . ") does not exist.");
				}

				if(!is_readable($sClassFile)) {
					$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] The given <b>/repository/classFile</b> given (" . $sClassFile . ") exists, but is not readable.");
				}

				require_once($sClassFile);
			}

			if(($sClassName = $this->_navConf("/repository/classname")) === FALSE) {
				$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] You have to provide <b>/repository/className</b>.");
			} else {
				if(!class_exists($sClassName)) {
					$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] The given <b>/repository/className</b> (" . $sClassName . ") does not exist.");
				}
			}

			$this->oRepo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($sClassName);
		}
	}

	function loadAggregates() {
		reset($this->aElement);
		while(list($sElementName, ) = each($this->aElement)) {

			if($sElementName{0} === "a" && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($sElementName, "aggregate")) {

				if(($sClassFile = $this->_navConf("/" . $sElementName . "/classfile")) === FALSE) {
					$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] You have to provide <b>/aggregate/classFile</b>.");
				} else {
					$sClassFile = $this->oForm->toServerPath($sClassFile);
					
					if(!file_exists($sClassFile)) {
						$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] The given <b>/aggregate/classFile</b> given (" . $sClassFile . ") does not exist.");
					}

					if(!is_readable($sClassFile)) {
						$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] The given <b>/aggregate/classFile</b> given (" . $sClassFile . ") exists, but is not readable.");
					}

					require_once($sClassFile);
				}

				if(($sClassName = $this->_navConf("/" . $sElementName . "/classname")) === FALSE) {
					$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] You have to provide <b>/aggregate/className</b>.");
				} else {
					if(!class_exists($sClassName)) {
						$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] The given <b>/aggregate/className</b> (" . $sClassName . ") does not exist.");
					}
				}
			}
		}
	}

	function writable() {
		return $this->defaultTrue("/writable");
	}

	function initDataSet($sKey) {
		$sSignature = FALSE;
		$oDataSet = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("formidable_maindataset");
		
		if($sKey === "new") {
			// new record to create
			$oDataSet->initFloating($this);
		} else {
			// existing record to grab

			if(($aDataSet = $this->getSyncData($sKey)) !== FALSE) {
				$oDataSet->initAnchored(
					$this,
					$aDataSet,
					$sKey
				);
			} else {
				$this->oForm->mayday("datasource:CONTENTREPOSITORY[name='" . $this->getName() . "'] No dataset matching key '" . $sKey . "' was found.");
			}
		}
		
		$sSignature = $oDataSet->getSignature();
		$this->aODataSets[$sSignature] =& $oDataSet;
		
		return $sSignature;
	}

	function getSyncData($sKey) {
		$oObject = $this->getObject($sKey);
		return get_object_vars($oObject);
	}

	function setSyncData($sSignature, $sKey, $aData) {
		$oObject = $this->getObject($sKey);
		reset($aData);
		while(list($sKey,) = each($aData)) {
			$oObject->$sKey = $aData[$sKey];
		}

		$oSession = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("TX_EXTBASE_Persistence_Session");
		$oSession->registerAddedObject($oObject);
		$oSession->commit();
	}

	function &getObject($sKey) {
		return $this->oRepo->findOneByUid($sKey);
	}
}


	if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/ds_php/api/class.tx_dscontentrepository.php"])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/ds_php/api/class.tx_dscontentrepository.php"]);
	}
?>
