<?php
namespace MbhSoftware\SolrFluidIndexer\ViewHelpers\Format;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Marc Bastian Heinrichs <mbh@mbh-software.de>
 *
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * ### FlattenArray ViewHelper
 *
 */
class FlattenArrayViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('content', 'mixed', 'The array or Iterator that contains arrays of values');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $content = $renderChildrenClosure();
        try {
            if (false === is_array($content) && false === $content instanceof \Traversable) {
                throw new \Exception('Traversable object or array expected but received ' . gettype($content), 1547066710);
            }
            $result = static::flattenArray($content);
        } catch (\Exception $error) {
            GeneralUtility::sysLog($error->getMessage(), 'solr_fluid_indexer', GeneralUtility::SYSLOG_SEVERITY_WARNING);
            $result = [];
        }

        return $result;
    }

    /**
     * Flattens the content
     *
     * @param array $content
     * @param array $flatContent
     * @return array
     */
    protected static function flattenArray(array $content, $flatContent = [])
    {
        if (empty($content)) {
            return $flatContent;
        }

        foreach ($content as $child) {
            if (true === is_array($child)) {
                $flatContent = static::flattenArray($child, $flatContent);
            } else {
                $flatContent[] = $child;
            }
        }

        return $flatContent;
    }
}
