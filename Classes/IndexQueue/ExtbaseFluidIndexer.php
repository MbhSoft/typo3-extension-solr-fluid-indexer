<?php
namespace MbhSoftware\SolrFluidIndexer\IndexQueue;

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
use MbhSoftware\SolrFluidIndexer\View\StandaloneView;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 *
 */
class ExtbaseFluidIndexer extends \ApacheSolrForTypo3\Solr\IndexQueue\Indexer {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * @var \MbhSoftware\SolrFluidIndexer\View\StandaloneView
	 */
	protected $view = NULL;

	public function injectPersistenceManager() {
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->persistenceManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
	}

	/**
	 * Constructor
	 *
	 * @param array Array of indexer options
	 */
	public function __construct(array $options = array()) {
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
	protected function itemToDocument(\ApacheSolrForTypo3\Solr\IndexQueue\Item $item, $language = 0) {
		$document = NULL;

		$indexingConfiguration = $this->getItemTypeAllConfiguration($item, $language);

		$itemRecord = $this->getFullItemRecord($item, $language);

		if (!is_null($itemRecord)) {

			$object = $this->getItemObject($item, $indexingConfiguration, $language);

			if (!is_null($object)) {

				$document = $this->getBaseDocument($item, $itemRecord);

				if (isset($indexingConfiguration['fields.'])) {
					$document = $this->addDocumentFieldsFromTyposcript($document, $indexingConfiguration['fields.'], $itemRecord);
				}
				if (isset($indexingConfiguration['fieldsFromSections.'])) {
					$this->initializeStandaloneView($indexingConfiguration['template.']);
					$document = $this->addDocumentFieldsFromFluid($document, $indexingConfiguration, $itemRecord, $item, $object);
				}
			}
		}

		return $document;
	}


	/**
	 * @param \Apache_Solr_Document $document
	 * @param array $indexingConfiguration
	 * @param array $data
	 * @param TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 */
	protected function addDocumentFieldsFromFluid(\Apache_Solr_Document $document, array $indexingConfiguration, $data, \ApacheSolrForTypo3\Solr\IndexQueue\Item $item, $object) {

		$this->view->assign('data', $data);
		$this->view->assign($item->getIndexingConfigurationName(), $object);

		$fieldsFromSections = $indexingConfiguration['fieldsFromSections.'];

		foreach ($fieldsFromSections as $solrFieldName => $sectionName) {
			if (is_array($sectionName)) {
				// configuration for a section, skipping
				continue;
			}

			$backupWorkingDirectory = getcwd();
			chdir(PATH_site);
			$fieldValue = trim($this->view->renderStandaloneSection($sectionName));
			if (isset($fieldsFromSections[$solrFieldName . '.'])) {
				if ($fieldValue !== '' && isset($fieldsFromSections[$solrFieldName . '.']['unserialize']) && $fieldsFromSections[$solrFieldName . '.']['unserialize']) {
					$fieldValue = @unserialize($fieldValue);
					// failed - convert to NULL to not broke bool values
					if ($fieldValue === FALSE) {
						$fieldValue = NULL;
					}
				}
			}
			chdir($backupWorkingDirectory);

			if (is_array($fieldValue)) {
				// multi value
				foreach ($fieldValue as $multiValue) {
					if ($multiValue !== '' && $multiValue !== NULL) {
						$document->addField($solrFieldName, $multiValue);
					}
				}
			} else {
				if ($fieldValue !== '' && $fieldValue !== NULL) {
					$document->setField($solrFieldName, $fieldValue);
				}
			}
		}

		return $document;

	}


	protected function getItemObject(\ApacheSolrForTypo3\Solr\IndexQueue\Item $item, array $indexingConfiguration, $language = 0) {

		$objectType = $indexingConfiguration['objectType'];

		$object = $this->persistenceManager->getObjectByIdentifier($item->getRecordUid(), $objectType);

		if ($language > 0) {
			//not supported ATM
			$object = NULL;
		}

		return $object;
	}

	/**
	 * Gets the full item record.
	 *
	 * This general record indexer simply gets the record from the item. Other
	 * more specialized indexers may provide more data for their specific item
	 * types.
	 *
	 * @param \ApacheSolrForTypo3\Solr\IndexQueue\Item $item The item to be indexed
	 * @param integer $language Language Id (sys_language.uid)
	 * @return array|NULL The full record with fields of data to be used for indexing or NULL to prevent an item from being indexed
	 */
	protected function getFullItemRecord(\ApacheSolrForTypo3\Solr\IndexQueue\Item $item, $language = 0) {
		$rootPageUid = $item->getRootPageUid();
		$overlayIdentifier = $rootPageUid . '|' . $language;
		if (!isset($this->sysLanguageOverlay[$overlayIdentifier])) {
			\ApacheSolrForTypo3\Solr\Util::initializeTsfe($rootPageUid, $language);
			$this->sysLanguageOverlay[$overlayIdentifier] = $GLOBALS['TSFE']->sys_language_contentOL;
		}

		$itemRecord = $item->getRecord();

		if ($language > 0) {
			$page = t3lib_div::makeInstance('t3lib_pageSelect');
			$page->init(FALSE);

			$itemRecord = $page->getRecordOverlay(
				$item->getType(),
				$itemRecord,
				$language,
				$this->sysLanguageOverlay[$rootPageUid . '|' . $language]
			);
		}

		if (!$itemRecord) {
			$itemRecord = NULL;
		}

		/*
		 * Skip disabled records. This happens if the default language record
		 * is hidden but a certain translation isn't. Then the default language
		 * document appears here but must not be indexed.
		 */
		if (!empty($GLOBALS['TCA'][$item->getType()]['ctrl']['enablecolumns']['disabled'])
			&& $itemRecord[$GLOBALS['TCA'][$item->getType()]['ctrl']['enablecolumns']['disabled']]
		) {
			$itemRecord = NULL;
		}

		/*
		 * Skip translation mismatching records. Sometimes the requested language
		 * doesn't fit the returned language. This might happen with content fallback
		 * and is perfectly fine in general.
		 * But if the requested language doesn't match the returned language and
		 * the given record has no translation parent, the indexqueue_item most
		 * probably pointed to a non-translated language record that is dedicated
		 * to a very specific language. Now we have to avoid indexing this record
		 * into all language cores.
		 */
		$translationOriginalPointerField = 'l10n_parent';
		if (!empty($GLOBALS['TCA'][$item->getType()]['ctrl']['transOrigPointerField'])) {
			$translationOriginalPointerField = $GLOBALS['TCA'][$item->getType()]['ctrl']['transOrigPointerField'];
		}

		$languageField = $GLOBALS['TCA'][$item->getType()]['ctrl']['languageField'];
		if ($itemRecord[$translationOriginalPointerField] == 0
			&& $this->sysLanguageOverlay[$overlayIdentifier] != 1
			&& !empty($languageField)
			&& $itemRecord[$languageField] != $language
			&& $itemRecord[$languageField] != '-1'
		) {
			$itemRecord = NULL;
		}

		if (!is_null($itemRecord)) {
			$itemRecord['__solr_index_language'] =  $language;
		}

		return $itemRecord;
	}


	/**
	 * Gets the configuration how to process an item's for indexing.
	 *
	 * @param	\ApacheSolrForTypo3\Solr\IndexQueue\Item	An index queue item
	 * @param	integer	Language ID
	 * @return	array	Configuration array from TypoScript
	 */
	protected function getItemTypeAllConfiguration(\ApacheSolrForTypo3\Solr\IndexQueue\Item $item, $language = 0) {
		$solrConfiguration = \ApacheSolrForTypo3\Solr\Util::getSolrConfigurationFromPageId($item->getRootPageUid(), TRUE, $language);

		return $solrConfiguration['index.']['queue.'][$item->getIndexingConfigurationName() . '.'];
	}





	/**
	 * Rendering the cObject, FLUIDTEMPLATE
	 *
	 * Configuration properties:
	 * - file string+stdWrap The FLUID template file
	 * - layoutRootPath filepath+stdWrap Root path to layouts
	 * - partialRootPath filepath+stdWrap Root path to partial
	 * - variable array of cObjects, the keys are the variable names in fluid
	 * - extbase.pluginName
	 * - extbase.controllerExtensionName
	 * - extbase.controllerName
	 * - extbase.controllerActionName
	 *
	 * Example:
	 * 10 = FLUIDTEMPLATE
	 * 10.template = FILE
	 * 10.template.file = fileadmin/templates/mytemplate.html
	 * 10.partialRootPath = fileadmin/templates/partial/
	 * 10.variables {
	 *   mylabel = TEXT
	 *   mylabel.value = Label from TypoScript coming
	 * }
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string The HTML output
	 */
	public function initializeStandaloneView($conf = array()) {

		$this->initializeStandaloneViewInstance();

		if (!is_array($conf)) {
			$conf = array();
		}

		$this->setTemplate($conf);
		$this->setLayoutRootPath($conf);
		$this->setPartialRootPath($conf);
		$this->setFormat($conf);
		$this->setExtbaseVariables($conf);
		$this->assignSettings($conf);
		$this->assignContentObjectVariables($conf);
	}

	/**
	 * @return void
	 */
	protected function initializeStandaloneViewInstance() {
		$this->view = GeneralUtility::makeInstance('MbhSoftware\\SolrFluidIndexer\\View\\StandaloneView');
	}

	/**
	 * Set template
	 *
	 * @param array $conf With possibly set file resource
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	protected function setTemplate(array $conf) {
		// Fetch the Fluid template
		if (!empty($conf['template']) && !empty($conf['template.'])) {
			$templateSource = $this->cObj->cObjGetSingle($conf['template'], $conf['template.']);
			$this->view->setTemplateSource($templateSource);
		} else {
			$file = isset($conf['file.']) ? $this->cObj->stdWrap($conf['file'], $conf['file.']) : $conf['file'];
			/** @var $templateService \TYPO3\CMS\Core\TypoScript\TemplateService */
			$templateService = $GLOBALS['TSFE']->tmpl;
			$templatePathAndFilename = $templateService->getFileName($file);
			$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		}
	}

	/**
	 * Set layout root path if given in configuration
	 *
	 * @param array $conf Configuration array
	 * @return void
	 */
	protected function setLayoutRootPath(array $conf) {
		// Override the default layout path via typoscript
		$layoutRootPath = isset($conf['layoutRootPath.']) ? $this->cObj->stdWrap($conf['layoutRootPath'], $conf['layoutRootPath.']) : $conf['layoutRootPath'];
		if ($layoutRootPath) {
			$layoutRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($layoutRootPath);
			$this->view->setLayoutRootPath($layoutRootPath);
		}
	}

	/**
	 * Set partial root path if given in configuration
	 *
	 * @param array $conf Configuration array
	 * @return void
	 */
	protected function setPartialRootPath(array $conf) {
		$partialRootPath = isset($conf['partialRootPath.']) ? $this->cObj->stdWrap($conf['partialRootPath'], $conf['partialRootPath.']) : $conf['partialRootPath'];
		if ($partialRootPath) {
			$partialRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($partialRootPath);
			$this->view->setPartialRootPath($partialRootPath);
		}
	}

	/**
	 * Set different format if given in configuration
	 *
	 * @param array $conf Configuration array
	 * @return void
	 */
	protected function setFormat(array $conf) {
		$format = isset($conf['format.']) ? $this->cObj->stdWrap($conf['format'], $conf['format.']) : $conf['format'];
		if ($format) {
			$this->view->setFormat($format);
		}
	}

	/**
	 * Set some extbase variables if given
	 *
	 * @param array $conf Configuration array
	 * @return void
	 */
	protected function setExtbaseVariables(array $conf) {
		/** @var $request \TYPO3\CMS\Extbase\Mvc\Request */
		$requestPluginName = isset($conf['extbase.']['pluginName.']) ? $this->cObj->stdWrap($conf['extbase.']['pluginName'], $conf['extbase.']['pluginName.']) : $conf['extbase.']['pluginName'];
		if ($requestPluginName) {
			$this->view->getRequest()->setPluginName($requestPluginName);
		}
		$requestControllerExtensionName = isset($conf['extbase.']['controllerExtensionName.']) ? $this->cObj->stdWrap($conf['extbase.']['controllerExtensionName'], $conf['extbase.']['controllerExtensionName.']) : $conf['extbase.']['controllerExtensionName'];
		if ($requestControllerExtensionName) {
			$this->view->getRequest()->setControllerExtensionName($requestControllerExtensionName);
		}
		$requestControllerName = isset($conf['extbase.']['controllerName.']) ? $this->cObj->stdWrap($conf['extbase.']['controllerName'], $conf['extbase.']['controllerName.']) : $conf['extbase.']['controllerName'];
		if ($requestControllerName) {
			$this->view->getRequest()->setControllerName($requestControllerName);
		}
		$requestControllerActionName = isset($conf['extbase.']['controllerActionName.']) ? $this->cObj->stdWrap($conf['extbase.']['controllerActionName'], $conf['extbase.']['controllerActionName.']) : $conf['extbase.']['controllerActionName'];
		if ($requestControllerActionName) {
			$this->view->getRequest()->setControllerActionName($requestControllerActionName);
		}
	}

	/**
	 * Assign rendered content objects in variables array to view
	 *
	 * @param array $conf Configuration array
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	protected function assignContentObjectVariables(array $conf) {
		$reservedVariables = array('data', 'current');
		// Accumulate the variables to be replaced and loop them through cObjGetSingle
		$variables = (array)$conf['variables.'];
		foreach ($variables as $variableName => $cObjType) {
			if (is_array($cObjType)) {
				continue;
			}
			if (!in_array($variableName, $reservedVariables)) {
				$this->view->assign(
					$variableName,
					$this->cObj->cObjGetSingle($cObjType, $variables[$variableName . '.'])
				);
			} else {
				throw new \InvalidArgumentException(
					'Cannot use reserved name "' . $variableName . '" as variable name in FLUIDTEMPLATE.',
					1288095720
				);
			}
		}
	}

	/**
	 * Set any TypoScript settings to the view. This is similar to a
	 * default MVC action controller in extbase.
	 *
	 * @param array $conf Configuration
	 * @return void
	 */
	protected function assignSettings(array $conf) {
		if (array_key_exists('settings.', $conf)) {
			/** @var $typoScriptService \TYPO3\CMS\Extbase\Service\TypoScriptService */
			$typoScriptService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');
			$settings = $typoScriptService->convertTypoScriptArrayToPlainArray($conf['settings.']);
			$this->view->assign('settings', $settings);
		}
	}





}