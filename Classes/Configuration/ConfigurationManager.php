<?php
namespace MbhSoftware\SolrFluidIndexer\Configuration;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;

/**
 * A configuration manager following the strategy pattern (GoF315). It hides the concrete
 * implementation of the configuration manager and provides an unified acccess point.
 *
 * Use the shutdown() method to drop the concrete implementation.
 */
class ConfigurationManager extends \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager
     */
    protected $concreteConfigurationManagerBackup;

    /**
     */
    public function forceFrontend()
    {
        if (!$this->concreteConfigurationManager instanceof FrontendConfigurationManager) {
            $this->concreteConfigurationManagerBackup = $this->concreteConfigurationManager;
            $this->concreteConfigurationManager = $this->objectManager->get(FrontendConfigurationManager::class);
        }
    }

    public function reset()
    {
        if (is_object($this->concreteConfigurationManagerBackup)) {
            $this->concreteConfigurationManager = $this->concreteConfigurationManagerBackup;
            $this->concreteConfigurationManagerBackup = null;
        }
    }

}
