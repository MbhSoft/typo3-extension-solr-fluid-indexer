<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

use MbhSoftware\SolrFluidIndexer\IndexQueue\FrontendHelper\PageFieldFluidIndexer;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class] = [
    'className' => \MbhSoftware\SolrFluidIndexer\Mvc\Web\Routing\UriBuilder::class
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class] = [
    'className' => \MbhSoftware\SolrFluidIndexer\Configuration\ConfigurationManager::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['Indexer']['indexPageSubstitutePageDocument']
    [PageFieldFluidIndexer::class] = PageFieldFluidIndexer::class;
