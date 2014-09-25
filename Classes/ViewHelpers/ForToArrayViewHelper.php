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

/**
 *
 *
 * @api
 */
class ForToArrayViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface {

	/**
	 * Iterates through elements of $each and renders child nodes
	 *
	 * @param array $each The array or \TYPO3\CMS\Extbase\Persistence\ObjectStorage to iterated over
	 * @param string $as The name of the iteration variable
	 * @param string $key The name of the variable to store the current array key
	 * @param boolean $reverse If enabled, the iterator will start with the last element and proceed reversely
	 * @param string $iteration The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)
	 * @return array Rendered array
	 * @api
	 */
	public function render($each, $as, $key = '', $reverse = FALSE, $iteration = NULL) {
		return self::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
	}

	/**
	 * @param array $arguments
	 * @param \Closure $renderChildrenClosure
	 * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return array
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		$templateVariableContainer = $renderingContext->getTemplateVariableContainer();
		if ($arguments['each'] === NULL) {
			return '';
		}
		if (is_object($arguments['each']) && !$arguments['each'] instanceof \Traversable) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('ForViewHelper only supports arrays and objects implementing \Traversable interface', 1248728393);
		}

		if ($arguments['reverse'] === TRUE) {
			// array_reverse only supports arrays
			if (is_object($arguments['each'])) {
				$arguments['each'] = iterator_to_array($arguments['each']);
			}
			$arguments['each'] = array_reverse($arguments['each']);
		}
		$iterationData = array(
			'index' => 0,
			'cycle' => 1,
			'total' => count($arguments['each'])
		);

		$output = array();
		foreach ($arguments['each'] as $keyValue => $singleElement) {
			$templateVariableContainer->add($arguments['as'], $singleElement);
			if ($arguments['key'] !== '') {
				$templateVariableContainer->add($arguments['key'], $keyValue);
			}
			if ($arguments['iteration'] !== NULL) {
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
			if ($arguments['iteration'] !== NULL) {
				$templateVariableContainer->remove($arguments['iteration']);
			}
		}
		return $output;
	}
}
