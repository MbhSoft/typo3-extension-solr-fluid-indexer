<?php
namespace MbhSoftware\SolrFluidIndexer\ViewHelpers\Uri;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * A view helper for creating URIs to TYPO3 pages.
 *
 * = Examples =
 *
 * <code title="URI to the current page">
 * <f:uri.page>page link</f:uri.page>
 * </code>
 * <output>
 * index.php?id=123
 * (depending on the current page and your TS configuration)
 * </output>
 *
 * <code title="query parameters">
 * <f:uri.page pageUid="1" additionalParams="{foo: 'bar'}" />
 * </code>
 * <output>
 * index.php?id=1&foo=bar
 * (depending on your TS configuration)
 * </output>
 *
 * <code title="query parameters for extensions">
 * <f:uri.page pageUid="1" additionalParams="{extension_key: {foo: 'bar'}}" />
 * </code>
 * <output>
 * index.php?id=1&extension_key[foo]=bar
 * (depending on your TS configuration)
 * </output>
 */
class PageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Uri\PageViewHelper
{

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('forceFrontend', 'boolean', '', false, false);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string Rendered page URI
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $pageUid = $arguments['pageUid'];
        $additionalParams = $arguments['additionalParams'];
        $pageType = $arguments['pageType'];
        $noCache = $arguments['noCache'];
        $noCacheHash = $arguments['noCacheHash'];
        $section = $arguments['section'];
        $linkAccessRestrictedPages = $arguments['linkAccessRestrictedPages'];
        $absolute = $arguments['absolute'];
        $addQueryString = $arguments['addQueryString'];
        $argumentsToBeExcludedFromQueryString = $arguments['argumentsToBeExcludedFromQueryString'];
        $addQueryStringMethod = $arguments['addQueryStringMethod'];

        $uriBuilder = $renderingContext->getControllerContext()->getUriBuilder();
        if ($arguments['forceFrontend']) {
            $uriBuilder->setForceFrontend(true);
        }
        $uri = $uriBuilder->setTargetPageUid($pageUid)->setTargetPageType($pageType)->setNoCache($noCache)->setUseCacheHash(!$noCacheHash)->setSection($section)->setLinkAccessRestrictedPages($linkAccessRestrictedPages)->setArguments($additionalParams)->setCreateAbsoluteUri($absolute)->setAddQueryString($addQueryString)->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)->setAddQueryStringMethod($addQueryStringMethod)->build();
        return $uri;
    }
}
