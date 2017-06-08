<?php
namespace Ameos\AmeosFormidable\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class GeneralUtility extends \TYPO3\CMS\Core\Utility\GeneralUtility
{

    /**
     * AddSlash array
     * This function traverses a multidimensional array and adds slashes to the values.
     * NOTE that the input array is and argument by reference.!!
     * Twin-function to stripSlashesOnArray
     *
     * @param array $theArray Multidimensional input array, (REFERENCE!)
     * @return array
     */
    public static function addSlashesOnArray(array &$theArray)
    {
        foreach ($theArray as &$value) {
            if (is_array($value)) {
                self::addSlashesOnArray($value);
            } else {
                $value = addslashes($value);
            }
        }
        unset($value);
        reset($theArray);
    }

    /**
     * StripSlash array
     * This function traverses a multidimensional array and strips slashes to the values.
     * NOTE that the input array is and argument by reference.!!
     * Twin-function to addSlashesOnArray
     *
     * @param array $theArray Multidimensional input array, (REFERENCE!)
     * @return array
     */
    public static function stripSlashesOnArray(array &$theArray)
    {
        foreach ($theArray as &$value) {
            if (is_array($value)) {
                self::stripSlashesOnArray($value);
            } else {
                $value = stripslashes($value);
            }
        }
        unset($value);
        reset($theArray);
    }

}
