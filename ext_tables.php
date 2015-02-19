<?php
	
	if (!defined ("TYPO3_MODE"))     die ("Access denied.");

	t3lib_div::loadTCA('tt_content');
	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
	t3lib_extMgm::addPlugin(Array('FORMIDABLE cObj (cached)', $_EXTKEY.'_pi1'),'list_type');

	
	$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:' . $_EXTKEY . '/pi1/flexform.xml');


	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2']='layout,select_key';
	t3lib_extMgm::addPlugin(Array('FORMIDABLE_INT cObj (not cached)', $_EXTKEY.'_pi2'),'list_type');

	
	$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi2']='pi_flexform';
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi2', 'FILE:EXT:' . $_EXTKEY . '/pi2/flexform.xml');


?>
