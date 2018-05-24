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

namespace MbhSoftware\SolrFluidIndexer\IndexQueue;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use ApacheSolrForTypo3\Solr\IndexQueue\AbstractIndexer;
use ApacheSolrForTypo3\Solr\IndexQueue\InvalidFieldNameException;

trait FluidFieldMapper
{

    /**
     * @var ContentObjectRenderer
     */
    protected $cObj;


    /**
     * @var \MbhSoftware\SolrFluidIndexer\View\StandaloneView
     */
    protected $view = null;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * Gets the mapped fields as an array mapping field names to values.
     *
     * @throws InvalidFieldNameException
     * @return array An array mapping field names to their values.
     */
    protected function getMappedFields($indexingConfiguration)
    {
        $mappedFields = [];
        $fieldsFromSections = $indexingConfiguration['fieldsFromSections.'];

        foreach ($fieldsFromSections as $solrFieldName => $sectionName) {
            if (is_array($sectionName)) {
                // configuration for a section, skipping
                continue;
            }

            if (!AbstractIndexer::isAllowedToOverrideField($solrFieldName)) {
                throw new InvalidFieldNameException(
                    'Must not overwrite field "type".',
                    1435441863
                );
            }
            $fieldValue = $this->resolveFieldValueFromSection($solrFieldName, $sectionName);
            if ($fieldValue !== '' && !empty($fieldsFromSections[$solrFieldName . '.']['unserialize'])) {
                $fieldValue = @unserialize($fieldValue);
                // failed - convert to null to not broke bool values
                if ($fieldValue === false) {
                    $fieldValue = null;
                }
            }
            $mappedFields[$solrFieldName] = $fieldValue;
        }

        return $mappedFields;
    }

    protected function resolveFieldValueFromSection($solrFieldName, $sectionName)
    {
        $backupWorkingDirectory = getcwd();
        chdir(PATH_site);
        $fieldValue = trim($this->view->renderStandaloneSection($sectionName));
        chdir($backupWorkingDirectory);

        return $fieldValue;
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
    public function initializeStandaloneView($conf = [])
    {
        $this->view = GeneralUtility::makeInstance(\MbhSoftware\SolrFluidIndexer\View\StandaloneView::class);

        if (!is_array($conf)) {
            $conf = [];
        }

        $this->setTemplate($conf);
        $this->setLayoutRootPath($conf);
        $this->setPartialRootPath($conf);
        $this->setFormat($conf);
        $this->setExtbaseVariables($conf);
        $this->assignSettings();
        $this->assignContentObjectVariables($conf);
    }

    /**
     * Set template
     *
     * @param array $conf With possibly set file resource
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function setTemplate(array $conf)
    {
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
    protected function setLayoutRootPath(array $conf)
    {
        // Override the default layout path via typoscript
        $layoutPaths = [];
        if (isset($conf['layoutRootPath']) || isset($conf['layoutRootPath.'])) {
            $layoutRootPath = isset($conf['layoutRootPath.'])
                ? $this->cObj->stdWrap($conf['layoutRootPath'], $conf['layoutRootPath.'])
                : $conf['layoutRootPath'];
            $layoutPaths[] = GeneralUtility::getFileAbsFileName($layoutRootPath);
        }
        if (isset($conf['layoutRootPaths.'])) {
            $layoutPaths = array_replace($layoutPaths, $this->applyStandardWrapToFluidPaths($conf['layoutRootPaths.']));
        }
        if (!empty($layoutPaths)) {
            $this->view->setLayoutRootPaths($layoutPaths);
        }
    }

    /**
     * Set partial root path if given in configuration
     *
     * @param array $conf Configuration array
     * @return void
     */
    protected function setPartialRootPath(array $conf)
    {
        $partialPaths = [];
        if (isset($conf['partialRootPath']) || isset($conf['partialRootPath.'])) {
            $partialRootPath = isset($conf['partialRootPath.'])
                ? $this->cObj->stdWrap($conf['partialRootPath'], $conf['partialRootPath.'])
                : $conf['partialRootPath'];
            $partialPaths[] = GeneralUtility::getFileAbsFileName($partialRootPath);
        }
        if (isset($conf['partialRootPaths.'])) {
            $partialPaths = array_replace($partialPaths, $this->applyStandardWrapToFluidPaths($conf['partialRootPaths.']));
        }
        if (!empty($partialPaths)) {
            $this->view->setPartialRootPaths($partialPaths);
        }
    }

    /**
     * Set different format if given in configuration
     *
     * @param array $conf Configuration array
     * @return void
     */
    protected function setFormat(array $conf)
    {
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
    protected function setExtbaseVariables(array $conf)
    {
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
    protected function assignContentObjectVariables(array $conf)
    {
        $reservedVariables = ['data', 'current'];
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
                    'Cannot use reserved name "' . $variableName . '" as variable name.',
                    1288095720
                );
            }
        }
    }

    protected function getSettings(array $conf) {
        $settings = [];
        if (array_key_exists('settings.', $conf)) {
            /** @var $typoScriptService \TYPO3\CMS\Core\TypoScript\TypoScriptService */
            $typoScriptService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\TypoScriptService::class);
            $settings = $typoScriptService->convertTypoScriptArrayToPlainArray($conf['settings.']);
        }
        return $settings;
    }

    /**
     * Set any TypoScript settings to the view. This is similar to a
     * default MVC action controller in extbase.
     *
     * @param array $conf Configuration
     * @return void
     */
    protected function assignSettings()
    {
        $this->view->assign('settings', $this->settings);
    }
}
