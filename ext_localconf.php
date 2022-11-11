<?php
/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

defined('TYPO3_MODE') or die();

(function() {
    /** @var \AawTeam\Pagenotfoundhandling\Configuration\ExtensionConfiguration $extensionConfiguration */
    $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\AawTeam\Pagenotfoundhandling\Configuration\ExtensionConfiguration::class);

    // Add the statistics backend module configuration
    if ($extensionConfiguration->has('enableStatisticsModule') && $extensionConfiguration->get('enableStatisticsModule')) {
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'pagenotfoundhandling-module-statistics',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            [
                'source' => 'EXT:pagenotfoundhandling/Resources/Public/Icons/ModuleStatistics.svg'
            ]
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
module.tx_pagenotfoundhandling {
    view {
        templateRootPaths.10 = EXT:pagenotfoundhandling/Resources/Private/Backend/Templates/
        partialRootPaths.10 = EXT:pagenotfoundhandling/Resources/Private/Backend/Partials/
        layoutRootPaths.10 = EXT:pagenotfoundhandling/Resources/Private/Backend/Layouts/
    }
}'
        );
    }
})();
