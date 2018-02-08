<?php
namespace MbhSoftware\SolrFluidIndexer\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 *
 *
 * @api
 */
class ForToArrayViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface
{

    use CompileWithRenderStatic;

    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('each', 'array', 'The array or \SplObjectStorage to iterated over', true);
        $this->registerArgument('as', 'string', 'The name of the iteration variable', true);
        $this->registerArgument('key', 'string', 'Variable to assign array key to', false);
        $this->registerArgument('reverse', 'boolean', 'If TRUE, iterates in reverse', false, false);
        $this->registerArgument('iteration', 'string', 'The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return array
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $templateVariableContainer = $renderingContext->getTemplateVariableContainer();
        if ($arguments['each'] === null) {
            return '';
        }
        if (is_object($arguments['each']) && !$arguments['each'] instanceof \Traversable) {
            throw new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception('ForViewHelper only supports arrays and objects implementing \Traversable interface', 1248728393);
        }

        if ($arguments['reverse'] === true) {
            // array_reverse only supports arrays
            if (is_object($arguments['each'])) {
                $arguments['each'] = iterator_to_array($arguments['each']);
            }
            $arguments['each'] = array_reverse($arguments['each']);
        }
        $iterationData = [
            'index' => 0,
            'cycle' => 1,
            'total' => count($arguments['each'])
        ];

        $output = [];
        foreach ($arguments['each'] as $keyValue => $singleElement) {
            $templateVariableContainer->add($arguments['as'], $singleElement);
            if ($arguments['key'] !== '') {
                $templateVariableContainer->add($arguments['key'], $keyValue);
            }
            if ($arguments['iteration'] !== null) {
                $iterationData['isFirst'] = $iterationData['cycle'] === 1;
                $iterationData['isLast'] = $iterationData['cycle'] === $iterationData['total'];
                $iterationData['isEven'] = $iterationData['cycle'] % 2 === 0;
                $iterationData['isOdd'] = !$iterationData['isEven'];
                $templateVariableContainer->add($arguments['iteration'], $iterationData);
                $iterationData['index']++;
                $iterationData['cycle']++;
            }
            $output[] = $renderChildrenClosure();
            $templateVariableContainer->remove($arguments['as']);
            if ($arguments['key'] !== '') {
                $templateVariableContainer->remove($arguments['key']);
            }
            if ($arguments['iteration'] !== null) {
                $templateVariableContainer->remove($arguments['iteration']);
            }
        }
        return $output;
    }
}
