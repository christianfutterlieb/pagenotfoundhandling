<?php
declare(strict_types=1);
namespace AawTeam\Pagenotfoundhandling\ErrorHandler\ErrorPageLoading;

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

use AawTeam\Pagenotfoundhandling\ErrorHandler\Exception\InvalidOrNoSiteLanguageException;
use AawTeam\Pagenotfoundhandling\ErrorHandler\Psr7Typo3Connector;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * UriGenerator
 */
class UriGenerator implements UriGeneratorInterface
{
    /**
     * @var Psr7Typo3Connector
     */
    protected $psr7Typo3Connector;

    /**
     * @var LinkService
     */
    protected $linkService;

    public function __construct(Psr7Typo3Connector $psr7Typo3Connector, LinkService $linkService)
    {
        $this->psr7Typo3Connector = $psr7Typo3Connector;
        $this->linkService = $linkService;
    }

    public function generate(ServerRequestInterface $request, array $configuration): UriInterface
    {
        $site = $this->psr7Typo3Connector->getSite($request);

        // Analyze error page
        $urlParams = $this->linkService->resolve($configuration['errorPage']);
        if ($urlParams['type'] !== 'page') {
            throw new \InvalidArgumentException('errorPage must be a TYPO3 page URL t3://page..');
        }

        // Build additional GET params
        $queryString = '';
        if ($site->getConfiguration()['additionalGetParams']) {
            $queryString .= '&' . trim($site->getConfiguration()['additionalGetParams'], '&');
        }
        if ($configuration['additionalGetParams']) {
            $queryString .= '&' . trim($configuration['additionalGetParams'], '&');
        }
        if (strpos($queryString, '###CURRENT_URL###') !== false) {
            $queryString = str_replace('###CURRENT_URL###', (string)$request->getUri(), $queryString);
        }
        // Setup query parameters
        $requestUriParameters = [];
        parse_str($queryString, $requestUriParameters);
        // Remove reserved names from query string
        $requestUriParameters = array_filter($requestUriParameters, function ($key) {
            return !in_array(strtolower($key), ['id', 'chash', 'l', 'mp']);
        }, ARRAY_FILTER_USE_KEY);

        // Determine language to request:
        // 1. Force a language
        // 2. Use currently requested language
        // 3. Fallback to default language
        $language = null;
        if ($site->getConfiguration()['forceLanguage'] > -1) {
            try {
                $language = $site->getLanguageById($site->getConfiguration()['forceLanguage']);
            } catch (\InvalidArgumentException $e) {
                if ($e->getCode() !== 1522960188) {
                    throw $e;
                }
            }
        } elseif ($site->getConfiguration()['forceLanguage'] == -1) {
            try {
                $language = $this->psr7Typo3Connector->getSiteLanguage($request);
            } catch (InvalidOrNoSiteLanguageException $e) {
            }
        }
        // Fallback to default if language could not be found
        if (!$language) {
            $language = $site->getDefaultLanguage();
        }

        // Add the required GET params
        $defaultRequestUriParameters = [
            '_language' => $language,
            'loopPrevention' => 1,
        ];
        ArrayUtility::mergeRecursiveWithOverrule($requestUriParameters, $defaultRequestUriParameters);

        // Create the PSR URI object
        $requestUri = $site->getRouter()->generateUri(
            (int)$urlParams['pageuid'],
            $requestUriParameters
        );
        return $requestUri;
    }
}
