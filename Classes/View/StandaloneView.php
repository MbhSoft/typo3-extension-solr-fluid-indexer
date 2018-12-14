<?php
namespace MbhSoftware\SolrFluidIndexer\View;

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

/**
 * Class StandaloneView
 */
class StandaloneView extends \TYPO3\CMS\Fluid\View\StandaloneView
{

    /**
     * Renders a section from the specified template w/o requring a call to the
     * main render() method - allows for cherry-picking sections to render.
     * @param string $sectionName
     * @param array $variables
     * @param boolean $optional
     * @return string
     */
    public function renderStandaloneSection($sectionName, $ignoreUnknown = true)
    {
        $variables = $this->baseRenderingContext->getTemplateVariableContainer()->getAll();
        $content = $this->renderSection($sectionName, $variables, $ignoreUnknown);
        return $content;
    }
}
