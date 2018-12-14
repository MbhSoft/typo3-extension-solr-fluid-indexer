<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Marc Bastian Heinrichs <mbh@mbh-software.de>
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
 *
 ***************************************************************/

namespace MbhSoftware\SolrFluidIndexer\IndexQueue\FrontendHelper;

use MbhSoftware\SolrFluidIndexer\IndexQueue\FluidFieldMapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use ApacheSolrForTypo3\Solr\SubstitutePageIndexer;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\Util;
use ApacheSolrForTypo3\Solr\IndexQueue\AbstractIndexer;
use ApacheSolrForTypo3\Solr\IndexQueue\InvalidFieldNameException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 *
 */
class PageFieldFluidIndexer implements SubstitutePageIndexer
{
    use \MbhSoftware\SolrFluidIndexer\IndexQueue\FluidFieldMapper;

    /**
     * @var \ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $pageIndexingConfigurationName = 'pages';

    /**
     * @param TypoScriptConfiguration $configuration
     */
    public function __construct(TypoScriptConfiguration $configuration = null)
    {
        $this->configuration = $configuration == null ? Util::getSolrConfiguration() : $configuration;
    }

    /**
     * @param string $pageIndexingConfigurationName
     */
    public function setPageIndexingConfigurationName($pageIndexingConfigurationName)
    {
        $this->pageIndexingConfigurationName = $pageIndexingConfigurationName;
    }

    /**
     * Returns a substitute document for the currently being indexed page.
     *
     * Uses the original document and adds fields as defined in
     * plugin.tx_solr.index.queue.pages.fields.
     *
     * @param \Apache_Solr_Document $pageDocument The original page document.
     * @return \Apache_Solr_Document A Apache_Solr_Document object that replace the default page document
     */
    public function getPageDocument(\Apache_Solr_Document $pageDocument)
    {
        $indexingConfiguration = $this->configuration->getIndexQueueConfigurationByName(
            $this->pageIndexingConfigurationName
        );

        if (empty($indexingConfiguration['template.']) || empty($indexingConfiguration['fieldsFromSections.'])
            || empty($indexingConfiguration['objectType'])) {
            return $pageDocument;
        }

        $this->settings = $this->getSettings($indexingConfiguration['template.']);

        $substitutePageDocument = clone $pageDocument;

        $pageRecord = $GLOBALS['TSFE']->page;

        $this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->cObj->start($pageRecord, 'pages');

        $this->initializeStandaloneView($indexingConfiguration['template.']);

        $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $dataMapper = $objectManager->get(\MbhSoftware\MbhExtbase\Persistence\Mapper\DataMapper::class);
        $page = $dataMapper->createObject($pageRecord, $indexingConfiguration['objectType']);

        $this->view->assign('pages', $page);

        $mappedFields = $this->getMappedFields($indexingConfiguration);
        foreach ($mappedFields as $fieldName => $fieldValue) {
            if (isset($substitutePageDocument->{$fieldName})) {
                // reset = overwrite, especially important to not make fields
                // multi valued where they may not accept multiple values
                unset($substitutePageDocument->{$fieldName});
            }

            // add new field / overwrite field if it was set before
            if ($fieldValue !== '' && $fieldValue !== null) {
                $substitutePageDocument->setField($fieldName, $fieldValue);
            }
        }

        return $substitutePageDocument;
    }
}
