<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\Container\\Container');
$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder', 'MbhSoftware\\SolrFluidIndexer\\Mvc\\Web\\Routing\\UriBuilder');
unset($extbaseObjectContainer);

?>