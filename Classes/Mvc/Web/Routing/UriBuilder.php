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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * An URI Builder
 *
 * @api
 */
class UriBuilder extends \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder {

	/**
	 * @var bool
	 */
	protected $forceFrontend = FALSE;

	/**
	 * @param bool $forceFrontend
	 */
	public function setForceFrontend($forceFrontend) {
		$this->forceFrontend = $forceFrontend;
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
	public function build() {
		if ($this->environmentService->isEnvironmentInBackendMode() && !$this->forceFrontend) {
			return $this->buildBackendUri();
		} else {
			return $this->buildFrontendUri();
		}
	}


}
