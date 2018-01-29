<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

use MbhSoftware\SolrFluidIndexer\IndexQueue\FrontendHelper\PageFieldFluidIndexer;

$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Extbase\Object\Container\Container::class);
$extbaseObjectContainer->registerImplementation(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class, \MbhSoftware\SolrFluidIndexer\Mvc\Web\Routing\UriBuilder::class);
unset($extbaseObjectContainer);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['Indexer']['indexPageSubstitutePageDocument'][PageFieldFluidIndexer::class] = PageFieldFluidIndexer::class;
