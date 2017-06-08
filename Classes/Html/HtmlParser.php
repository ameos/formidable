<?php
namespace Ameos\AmeosFormidable\Html;

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

class HtmlParser extends \TYPO3\CMS\Core\Html\HtmlParser
{

    /**
     * Returns the first subpart encapsulated in the marker, $marker
     * (possibly present in $content as a HTML comment)
     *
     * @param string $content Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
     * @param string $marker Marker string, eg. "###CONTENT_PART###
     *
     * @return string
     */
    public static function getSubpart($content, $marker)
    {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        return $templateService->getSubpart($content, $marker);
    }

    /**
     * Substitutes a subpart in $content with the content of $subpartContent.
     *
     * @param string $content Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
     * @param string $marker Marker string, eg. "###CONTENT_PART###
     * @param array $subpartContent If $subpartContent happens to be an array, it's [0] and [1] elements are wrapped around the content of the subpart (fetched by getSubpart())
     * @param bool $recursive If $recursive is set, the function calls itself with the content set to the remaining part of the content after the second marker. This means that proceding subparts are ALSO substituted!
     * @param bool $keepMarker If set, the marker around the subpart is not removed, but kept in the output
     *
     * @return string Processed input content
     */
    public static function substituteSubpart($content, $marker, $subpartContent, $recursive = true, $keepMarker = false)
    {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        return $templateService->substituteSubpart($content, $marker, $subpartContent, $recursive, $keepMarker);
    }

    /**
     * Substitues multiple subparts at once
     *
     * @param string $content The content stream, typically HTML template content.
     * @param array $subpartsContent The array of key/value pairs being subpart/content values used in the substitution. For each element in this array the function will substitute a subpart in the content stream with the content.
     *
     * @return string The processed HTML content string.
     */
    public static function substituteSubpartArray($content, array $subpartsContent)
    {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        return $templateService->substituteSubpartArray($content, $subpartsContent);
    }

    /**
     * Substitutes a marker string in the input content
     * (by a simple str_replace())
     *
     * @param string $content The content stream, typically HTML template content.
     * @param string $marker The marker string, typically on the form "###[the marker string]###
     * @param mixed $markContent The content to insert instead of the marker string found.
     *
     * @return string The processed HTML content string.
     */
    public static function substituteMarker($content, $marker, $markContent)
    {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        return $templateService->substituteMarker($content, $marker, $markContent);
    }

    /**
     * Traverses the input $markContentArray array and for each key the marker
     * by the same name (possibly wrapped and in upper case) will be
     * substituted with the keys value in the array. This is very useful if you
     * have a data-record to substitute in some content. In particular when you
     * use the $wrap and $uppercase values to pre-process the markers. Eg. a
     * key name like "myfield" could effectively be represented by the marker
     * "###MYFIELD###" if the wrap value was "###|###" and the $uppercase
     * boolean TRUE.
     *
     * @param string $content The content stream, typically HTML template content.
     * @param array $markContentArray The array of key/value pairs being marker/content values used in the substitution. For each element in this array the function will substitute a marker in the content stream with the content.
     * @param string $wrap A wrap value - [part 1] | [part 2] - for the markers before substitution
     * @param bool $uppercase If set, all marker string substitution is done with upper-case markers.
     * @param bool $deleteUnused If set, all unused marker are deleted.
     *
     * @return string The processed output stream
     */
    public static function substituteMarkerArray($content, $markContentArray, $wrap = '', $uppercase = false, $deleteUnused = false)
    {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        return $templateService->substituteMarkerArray($content, $markContentArray, $wrap, $uppercase, $deleteUnused);
    }

    /**
     * Replaces all markers and subparts in a template with the content provided in the structured array.
     *
     * The array is built like the template with its markers and subparts. Keys represent the marker name and the values the
     * content.
     * If the value is not an array the key will be treated as a single marker.
     * If the value is an array the key will be treated as a subpart marker.
     * Repeated subpart contents are of course elements in the array, so every subpart value must contain an array with its
     * markers.
     *
     * $markersAndSubparts = array (
     *    '###SINGLEMARKER1###' => 'value 1',
     *    '###SUBPARTMARKER1###' => array(
     *        0 => array(
     *            '###SINGLEMARKER2###' => 'value 2',
     *        ),
     *        1 => array(
     *            '###SINGLEMARKER2###' => 'value 3',
     *        )
     *    ),
     *    '###SUBPARTMARKER2###' => array(
     *    ),
     * )
     * Subparts can be nested, so below the 'SINGLEMARKER2' it is possible to have another subpart marker with an array as the
     * value, which in its turn contains the elements of the sub-subparts.
     * Empty arrays for Subparts will cause the subtemplate to be cleared.
     *
     * @static
     *
     * @param string $content The content stream, typically HTML template content.
     * @param array $markersAndSubparts The array of single markers and subpart contents.
     * @param string $wrap A wrap value - [part1] | [part2] - for the markers before substitution.
     * @param bool $uppercase If set, all marker string substitution is done with upper-case markers.
     * @param bool $deleteUnused If set, all unused single markers are deleted.
     *
     * @return string The processed output stream
     */
    public static function substituteMarkerAndSubpartArrayRecursive($content, array $markersAndSubparts, $wrap = '', $uppercase = false, $deleteUnused = false)
    {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        return $templateService->substituteMarkerAndSubpartArrayRecursive($content, $markersAndSubparts, $wrap, $uppercase, $deleteUnused);
    }
    
}
