<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Robert Lemke (robert@typo3.org)
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
 * Test case for checking the Formidable Branch 2 API 
 *
 * NOTE:    This test case assumes that you have installed Formidable Branch 2.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

require_once(PATH_formidableapi);

class tx_ameosformidable_testcase extends tx_t3unit_testcase {

	protected $oApi;

	public function __construct($name) {
		
		parent::__construct($name);
		$TYPO3_DB->debugOutput = TRUE;
		
		$this->oApi = new tx_ameosformidable();
	}

	public function test_UnitTests() {
		self::assertTrue("hello" !== "world", "Hello is not equal to world !");
	}
}

?>