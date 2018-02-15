<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Marc Bastian Heinrichs <mbh@mbh-software.de>
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

namespace MbhSoftware\SolrFluidIndexer\IndexQueue;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 */
class ExtbaseFluidIndexer extends \ApacheSolrForTypo3\Solr\IndexQueue\Indexer
{
    use FluidFieldMapper;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

    public function injectPersistenceManager()
    {
        $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->persistenceManager = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
    }

    /**
     * Constructor
     *
     * @param array array of indexer options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->injectPersistenceManager();
    }

    /**
     * Converts an item array (record) to a Solr document by mapping the
     * record's fields onto Solr document fields as configured in TypoScript.
     *
     * @param \ApacheSolrForTypo3\Solr\IndexQueue\Item $item An index queue item
     * @param integer $language Language Id
     * @return \Apache_Solr_Document The Solr document converted from the record
     */
    protected function itemToDocument(\ApacheSolrForTypo3\Solr\IndexQueue\Item $item, $language = 0)
    {
        $document = parent::itemToDocument($item, $language);

        if (!$document !== null) {
            $this->cObj = $GLOBALS['TSFE']->cObj;
            $indexingConfiguration = $this->getItemTypeAllConfiguration($item, $language);
            $object = $this->getItemObject($item, $indexingConfiguration, $language);

            if (!is_null($object) && isset($indexingConfiguration['fieldsFromSections.'])) {
                $this->initializeStandaloneView($indexingConfiguration['template.']);
                $document = $this->addDocumentFieldsFromFluid($document, $indexingConfiguration, $item, $object);
            }
        }

        return $document;
    }

    /**
     * @param \ApacheSolrForTypo3\Solr\IndexQueue\Item $item
     * @param array $indexingConfiguration
     * @param int $language
     * @return null|object
     */
    protected function getItemObject(\ApacheSolrForTypo3\Solr\IndexQueue\Item $item, array $indexingConfiguration, $language = 0)
    {
        $objectType = $indexingConfiguration['objectType'];
        $object = $this->persistenceManager->getObjectByIdentifier($item->getRecordUid(), $objectType);

        if ($language > 0) {
            //not supported ATM
            $object = null;
        }

        return $object;
    }


    /**
     * @param \Apache_Solr_Document $document
     * @param array $indexingConfiguration
     * @param \ApacheSolrForTypo3\Solr\IndexQueue\Item $item
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
     */
    protected function addDocumentFieldsFromFluid(\Apache_Solr_Document $document, array $indexingConfiguration, \ApacheSolrForTypo3\Solr\IndexQueue\Item $item, $object)
    {
        $this->view->assign($item->getIndexingConfigurationName(), $object);

        $mappedFields = $this->getMappedFields($indexingConfiguration);

        foreach ($mappedFields as $fieldName => $fieldValue) {
            if (isset($document->{$fieldName})) {
                // reset = overwrite, especially important to not make fields
                // multi valued where they may not accept multiple values
                unset($document->{$fieldName});
            }

            if (is_array($fieldValue)) {
                // multi value
                foreach ($fieldValue as $multiValue) {
                    if ($multiValue !== '' && $multiValue !== null) {
                        $document->addField($fieldName, $multiValue);
                    }
                }
            } else {
                if ($fieldValue !== '' && $fieldValue !== null) {
                    $document->setField($fieldName, $fieldValue);
                }
            }
        }

        return $document;
    }

    /**
     * Gets the configuration how to process an item's for indexing.
     *
     * @param   \ApacheSolrForTypo3\Solr\IndexQueue\Item    An index queue item
     * @param   integer Language ID
     * @return  array   Configuration array from TypoScript
     */
    protected function getItemTypeAllConfiguration(\ApacheSolrForTypo3\Solr\IndexQueue\Item $item, $language = 0)
    {
        $indexConfigurationName = $item->getIndexingConfigurationName();
        $solrConfiguration = \ApacheSolrForTypo3\Solr\Util::getSolrConfigurationFromPageId($item->getRootPageUid(), true, $language);

        return $solrConfiguration->getIndexQueueConfigurationByName($indexConfigurationName);
    }
}
