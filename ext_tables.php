<?php
	
	if (!defined ("TYPO3_MODE"))     die ("Access denied.");

	
	$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
	TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(Array('FORMIDABLE cObj (cached)', $_EXTKEY.'_pi1'),'list_type');

	
	$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';
	TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:' . $_EXTKEY . '/pi1/flexform.xml');


	$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2']='layout,select_key';
	TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(Array('FORMIDABLE_INT cObj (not cached)', $_EXTKEY.'_pi2'),'list_type');

	
	$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi2']='pi_flexform';
	TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pi2', 'FILE:EXT:' . $_EXTKEY . '/pi2/flexform.xml');


?>
