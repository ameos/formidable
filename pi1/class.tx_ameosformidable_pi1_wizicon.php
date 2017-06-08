<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Manuel Rego Casasnovas <mrego@igalia.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/




/**
 * Class that adds the wizard icon.
 *
 * @author	Manuel Rego Casasnovas <mrego@igalia.com>
 * @package	TYPO3
 * @subpackage	ameos_formidable
 */
class ameos_formidable_pi1_wizicon {

					/**
					 * Processing the wizard items array
					 *
					 * @param	array		$wizardItems: The wizard items
					 * @return	Modified array with wizard items
					 */
					function proc($wizardItems)	{
						global $LANG;

						$LL = $this->includeLocalLang();

						$wizardItems['plugins_ameos_formidable_pi1'] = array(
							'icon'=>\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ameos_formidable').'pi1/ce_wiz.gif',
							'title'=>'FORMIDABLE cObj (cached)',
							'description'=>'Formidable standard plugins to invoke and run your XML application (cached)',
							'params'=>'&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=ameos_formidable_pi1'
						);

						return $wizardItems;
					}

					/**
					 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
					 *
					 * @return	The array with language labels
					 */
					function includeLocalLang()	{
						$llFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ameos_formidable').'locallang.xml';
						if(function_exists("\TYPO3\CMS\Core\Utility\GeneralUtility::readLLXMLfile")) {
							$LOCAL_LANG = \TYPO3\CMS\Core\Utility\GeneralUtility::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);
						}
						
						return $LOCAL_LANG;
					}
				}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/pi1/class.ameos_formidable_pi1_wizicon.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/pi1/class.ameos_formidable_pi1_wizicon.php']);
}

?>
