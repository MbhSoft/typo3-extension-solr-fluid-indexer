<?php
namespace MbhSoftware\SolrFluidIndexer\ViewHelpers\Link;

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
/**
 * A view helper for creating links to TYPO3 pages.
 *
 * = Examples =
 *
 * <code title="link to the current page">
 * <f:link.page>page link</f:link.page>
 * </code>
 * <output>
 * <a href="index.php?id=123">page link</f:link.action>
 * (depending on the current page and your TS configuration)
 * </output>
 *
 * <code title="query parameters">
 * <f:link.page pageUid="1" additionalParams="{foo: 'bar'}">page link</f:link.page>
 * </code>
 * <output>
 * <a href="index.php?id=1&foo=bar">page link</f:link.action>
 * (depending on your TS configuration)
 * </output>
 *
 * <code title="query parameters for extensions">
 * <f:link.page pageUid="1" additionalParams="{extension_key: {foo: 'bar'}}">page link</f:link.page>
 * </code>
 * <output>
 * <a href="index.php?id=1&extension_key[foo]=bar">page link</f:link.action>
 * (depending on your TS configuration)
 * </output>
 */
class PageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\PageViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('forceFrontend', 'boolean', '', false, false);
    }

    /**
     * @param integer|NULL $pageUid target page. See TypoLink destination
     * @param array $additionalParams query parameters to be attached to the resulting URI
     * @param integer $pageType type of the target page. See typolink.parameter
     * @param boolean $noCache set this to disable caching for the target page. You should not need this.
     * @param boolean $noCacheHash set this to supress the cHash query parameter created by TypoLink. You should not need this.
     * @param string $section the anchor to be added to the URI
     * @param boolean $linkAccessRestrictedPages If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.
     * @param boolean $absolute If set, the URI of the rendered link is absolute
     * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
     * @param string $addQueryStringMethod Set which parameters will be kept. Only active if $addQueryString = TRUE
     * @return string Rendered page URI
     */
    public function render($pageUid = null, array $additionalParams = [], $pageType = 0, $noCache = false, $noCacheHash = false, $section = '', $linkAccessRestrictedPages = false, $absolute = false, $addQueryString = false, array $argumentsToBeExcludedFromQueryString = [], $addQueryStringMethod = null)
    {
        $uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();
        $uriBuilder->reset();
        if ($this->arguments['forceFrontend']) {
            $uriBuilder->setForceFrontend(true);
        }
        $uri = $uriBuilder->setTargetPageUid($pageUid)
            ->setTargetPageType($pageType)
            ->setNoCache($noCache)
            ->setUseCacheHash(!$noCacheHash)
            ->setSection($section)
            ->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
            ->setArguments($additionalParams)
            ->setCreateAbsoluteUri($absolute)
            ->setAddQueryString($addQueryString)
            ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
            ->setAddQueryStringMethod($addQueryStringMethod)
            ->build();
        if ((string)$uri !== '') {
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($this->renderChildren());
            $result = $this->tag->render();
        } else {
            $result = $this->renderChildren();
        }
        return $result;
    }
}
