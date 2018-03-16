<?php
namespace MbhSoftware\SolrFluidIndexer\ViewHelpers;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This ViewHelper renders CObjects from the global TypoScript configuration.
 * NOTE: You have to ensure proper escaping (htmlspecialchars/intval/etc.) on your own!
 *
 * = Examples =
 *
 * <code title="Render lib object">
 * <f:cObject typoscriptObjectPath="lib.someLibObject" />
 * </code>
 * <output>
 * rendered lib.someLibObject
 * </output>
 *
 * <code title="Specify cObject data & current value">
 * <f:cObject typoscriptObjectPath="lib.customHeader" data="{article}" currentValueKey="title" />
 * </code>
 * <output>
 * rendered lib.customHeader. data and current value will be available in TypoScript
 * </output>
 *
 * <code title="inline notation">
 * {article -> f:cObject(typoscriptObjectPath: 'lib.customHeader')}
 * </code>
 * <output>
 * rendered lib.customHeader. data will be available in TypoScript
 * </output>
 */
class CObjectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper
{

    /** @var int */
    protected $cObjectDepthCounterBackup;

    /**
     * Sets the $TSFE->cObjectDepthCounter in Backend mode
     * This somewhat hacky work around is currently needed because the cObjGetSingle() function of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer relies on this setting
     */
    protected function simulateFrontendEnvironment()
    {
        if (isset($GLOBALS['TSFE'])) {
            $this->cObjectDepthCounterBackup = $GLOBALS['TSFE']->cObjectDepthCounter;
            $GLOBALS['TSFE']->cObjectDepthCounter = 100;
        } else {
            $GLOBALS['TSFE'] = new \stdClass();
            $GLOBALS['TSFE']->cObjectDepthCounter = 100;
        }
    }

    /**
     * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
     *
     * @see simulateFrontendEnvironment()
     */
    protected function resetFrontendEnvironment()
    {
        if ($this->cObjectDepthCounterBackup !== null) {
            $GLOBALS['TSFE']->cObjectDepthCounter = $this->cObjectDepthCounterBackup;
            $this->cObjectDepthCounterBackup = null;
        } elseif ($GLOBALS['TSFE'] instanceof \stdClass) {
            $GLOBALS['TSFE'] = null;
        }
    }
}
