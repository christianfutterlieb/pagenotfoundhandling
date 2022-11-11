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

use AawTeam\Pagenotfoundhandling\ErrorHandler\Psr7Typo3Connector;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * RequestOptionsGenerator
 */
class RequestOptionsGenerator implements RequestOptionsGeneratorInterface
{
    protected const DEFAULT_USER_AGENT = 'TYPO3 EXT:pagenotfoundhandling';
    protected const DEFAULT_TIMEOUT = 30;
    protected const DEFAULT_CONNECT_TIMEOUT = 10;

    /**
     * @var Psr7Typo3Connector
     */
    protected $psr7Typo3Connector;

    public function __construct(Psr7Typo3Connector $psr7Typo3Connector)
    {
        $this->psr7Typo3Connector = $psr7Typo3Connector;
    }

    public function generate(ServerRequestInterface $request, UriInterface $errorPageURI): array
    {
        $site = $this->psr7Typo3Connector->getSite($request);

        // Compose request options
        $options = [
            RequestOptions::HEADERS => [
                'User-Agent' => $request->getServerParams()['HTTP_USER_AGENT'] ?? static::DEFAULT_USER_AGENT,
                'Referer' => $request->getUri()->__toString(),
            ],
        ];
        // Override default timeout / connect_timeout
        $this->generateTimeoutOptions($options, $request);

        // X-Forwarded-For header
        $this->generateXForwardedForHeader($options, $request);

        // Request trust
        $currentRequestIsTrusted = GeneralUtility::getIndpEnv('TYPO3_SSL') || $site->getConfiguration()['trustInsecureIncomingConnections'];
        $sendAuthInfoToErrorPage = $errorPageURI->getScheme() === 'https' || $site->getConfiguration()['passAuthinfoToInsecureConnections'];

        // Passthrough authentication data
        if ($currentRequestIsTrusted && $sendAuthInfoToErrorPage) {
            // Passthrough cookies
            $this->generateCookieJar($options, $request, $errorPageURI);

            // Passthrough HTTP Authorization
            $this->generateAuthorizationHeader($options, $request);
        }

        // Disable certificate verification
        if ($site->getConfiguration()['disableCertificateVerification']) {
            $options[RequestOptions::VERIFY] = false;
        }

        return $options;
    }

    protected function generateTimeoutOptions(array &$options, ServerRequestInterface $request): void
    {
        $site = $this->psr7Typo3Connector->getSite($request);

        // Override default timeout
        if ($site->getConfiguration()['requestTimeout'] > 0) {
            $options[RequestOptions::TIMEOUT] = $site->getConfiguration()['requestTimeout'];
        } elseif ($GLOBALS['TYPO3_CONF_VARS']['HTTP'][RequestOptions::TIMEOUT] < 1) {
            // Force a 30 sec timeout, when none is set at all
            $options[RequestOptions::TIMEOUT] = static::DEFAULT_TIMEOUT;
        }
        // Override default connect_timeout
        if ($site->getConfiguration()['connectTimeout'] > 0) {
            $options[RequestOptions::CONNECT_TIMEOUT] = $site->getConfiguration()['connectTimeout'];
        } elseif ($GLOBALS['TYPO3_CONF_VARS']['HTTP'][RequestOptions::CONNECT_TIMEOUT] < 1) {
            // Force a 10 sec connect_timeout, when none is set at all
            $options[RequestOptions::CONNECT_TIMEOUT] = static::DEFAULT_CONNECT_TIMEOUT;
        }
    }

    protected function generateXForwardedForHeader(array &$options, ServerRequestInterface $request): void
    {
        $remoteAddress = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        if (filter_var($remoteAddress, FILTER_VALIDATE_IP)) {
            $forwardedForHeader = trim($request->getHeaderLine('x-forwarded-for'));
            if ($forwardedForHeader) {
                $forwardedForHeader .= ', ' . $remoteAddress;
            } else {
                $forwardedForHeader = $remoteAddress;
            }
            $options[RequestOptions::HEADERS]['X-Forwarded-For'] = trim($forwardedForHeader);
        }
    }

    protected function generateCookieJar(array &$options, ServerRequestInterface $request, UriInterface $errorPageURI): void
    {
        $cookieParams = $request->getCookieParams();
        if (!empty($cookieParams)) {
            /** @var CookieJar $cookieJar */
            $cookieJar = GeneralUtility::makeInstance(CookieJar::class, true);
            foreach ($cookieParams as $name => $value) {
                $cookieJar->setCookie(GeneralUtility::makeInstance(SetCookie::class, [
                    'Domain' => $errorPageURI->getHost(),
                    'Name' => $name,
                    'Value' => $value,
                    'Discard' => true,
                    'HttpOnly' => true,
                    'Secure' => $errorPageURI->getScheme() === 'https',
                ]));
            }
            $options[RequestOptions::COOKIES] = $cookieJar;
        }
    }

    protected function generateAuthorizationHeader(array &$options, ServerRequestInterface $request): void
    {
        // 1. Get authorization header from PSR-7 request
        $authorizationHeader = '';
        if ($request->hasHeader('Authorization')) {
            $authorizationHeader = $request->getHeaderLine('Authorization');
        } elseif (isset($request->getServerParams()['HTTP_AUTHORIZATION'])) {
            $authorizationHeader = $request->getServerParams()['HTTP_AUTHORIZATION'];
        } elseif (getenv('HTTP_AUTHORIZATION')) {
            $authorizationHeader = (string)getenv('HTTP_AUTHORIZATION');
        } elseif (isset($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authorizationHeader = $request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif ($request->getServerParams()['AUTH_TYPE'] === 'Basic' && ($request->getServerParams()['PHP_AUTH_USER'] || $request->getServerParams()['PHP_AUTH_PW'])) {
            $authorizationHeader = 'Basic ' . base64_encode($request->getServerParams()['PHP_AUTH_USER'] . ':' . $request->getServerParams()['PHP_AUTH_PW']);
        }

        if (stripos($authorizationHeader, 'digest ') !== 0) {
            $options[RequestOptions::HEADERS]['Authorization'] = $authorizationHeader;
        }
    }
}
