<?php
namespace MbhSoftware\SolrFluidIndexer\Mvc\Web\Routing;

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
 * An URI Builder
 *
 * @api
 */
class UriBuilder extends \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
{

    /**
     * @var bool
     */
    protected $forceFrontend = false;

    /**
     * @var string
     */
    protected $extensionName;

    /**
     * @var string
     */
    protected $pluginName;

    /**
     * @param bool $forceFrontend
     */
    public function setForceFrontend($forceFrontend)
    {
        $this->forceFrontend = $forceFrontend;
        return $this;
    }

    /**
     * Creates an URI used for linking to an Extbase action.
     * Works in Frontend and Backend mode of TYPO3.
     *
     * @param string $actionName Name of the action to be called
     * @param array $controllerArguments Additional query parameters. Will be "namespaced" and merged with $this->arguments.
     * @param string $controllerName Name of the target controller. If not set, current ControllerName is used.
     * @param string $extensionName Name of the target extension, without underscores. If not set, current ExtensionName is used.
     * @param string $pluginName Name of the target plugin. If not set, current PluginName is used.
     * @return string the rendered URI
     * @api
     * @see build()
     */
    public function uriFor($actionName = null, $controllerArguments = [], $controllerName = null, $extensionName = null, $pluginName = null)
    {
        $this->extensionName = $extensionName;
        $this->pluginName = $pluginName;
        return parent::uriFor($actionName, $controllerArguments, $controllerName, $extensionName, $pluginName);
    }

    /**
     * Builds the URI
     * Depending on the current context this calls buildBackendUri() or buildFrontendUri()
     *
     * @return string The URI
     * @api
     * @see buildBackendUri()
     * @see buildFrontendUri()
     */
    public function build()
    {
        if ($this->environmentService->isEnvironmentInBackendMode() && !$this->forceFrontend) {
            return $this->buildBackendUri();
        } else {
            $this->removeDefaultControllerAndActionBeforeBuildingFrontendUrl();
            return $this->buildFrontendUri();
        }
    }

    protected function removeDefaultControllerAndActionBeforeBuildingFrontendUrl()
    {
        if (!$this->forceFrontend) {
            return;
        }
        if ($this->extensionName === null) {
            $extensionName = $this->request->getControllerExtensionName();
        } else {
            $extensionName = $this->extensionName;
        }
        if ($this->pluginName === null) {
            $pluginName = $this->request->getPluginName();
        } else {
            $pluginName = $this->pluginName;
        }
        if (!empty($extensionName) && !empty($pluginName)) {
            $pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
            if (isset($this->arguments[$pluginNamespace]) && is_array($this->arguments[$pluginNamespace])) {
                $controllerArguments = $this->arguments[$pluginNamespace];
                $this->arguments[$pluginNamespace] = $this->removeDefaultControllerAndAction($controllerArguments, $extensionName, $pluginName);
            }
        }
    }

    /**
     * Resets all UriBuilder options to their default value
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     * @api
     */
    public function reset()
    {
        $this->forceFrontend = false;
        $this->pluginName = null;
        $this->extensionName = null;
        return parent::reset();
    }
}
