<?php

declare(strict_types=1);

namespace AawTeam\Pagenotfoundhandling\ErrorHandler;

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

use AawTeam\Pagenotfoundhandling\ErrorHandler\ErrorPageLoading\HttpHandlerInterface;
use AawTeam\Pagenotfoundhandling\ErrorHandler\ErrorPageLoading\RequestOptionsGeneratorInterface;
use AawTeam\Pagenotfoundhandling\ErrorHandler\ErrorPageLoading\UriGeneratorInterface;
use AawTeam\Pagenotfoundhandling\ErrorHandler\Exception\InvalidOrNoSiteException;
use AawTeam\Pagenotfoundhandling\Utility\StatisticsUtility;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * ConcreteErrorHandler
 *
 * @internal
 */
class ConcreteErrorHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var int
     */
    protected $statusCode = 0;

    /**
     * @var array
     */
    protected $errorHandlerConfiguration = [];

    /**
     * @var HttpHandlerInterface
     */
    protected $httpHandler;

    /**
     * @var RequestOptionsGeneratorInterface
     */
    protected $requestOptionsGenerator;

    /**
     * @var UriGeneratorInterface
     */
    protected $uriGenerator;

    /**
     * @var Psr7Typo3Connector
     */
    protected $psr7Typo3Connector;

    /**
     * @var StatisticsUtility
     */
    protected $statisticsUtility;

    public function __construct(
        UriGeneratorInterface $uriGenerator,
        HttpHandlerInterface $httpHandlerInterface,
        RequestOptionsGeneratorInterface $requestOptionsGenerator,
        Psr7Typo3Connector $psr7Typo3Connector,
        StatisticsUtility $statisticsUtility
    ) {
        $this->uriGenerator = $uriGenerator;
        $this->httpHandler = $httpHandlerInterface;
        $this->requestOptionsGenerator = $requestOptionsGenerator;
        $this->psr7Typo3Connector = $psr7Typo3Connector;
        $this->statisticsUtility = $statisticsUtility;
    }

    public function run(
        ServerRequestInterface $request,
        int $statusCode,
        array $errorHandlerConfiguration,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        // ---> Formerly in PageErrorHandler::__construct()
        $this->statusCode = $statusCode;
        $this->errorHandlerConfiguration = $errorHandlerConfiguration;

        // ---> Formerly in PageErrorHandler::handlePageError()
        // Infinite loop detection
        if ($this->httpHandler->isInfiniteLoopRequest($request)) {
            $this->logger->error('Detected infinite loop', [
                'requestURI' => (string)$request->getUri(),
                'referer' => $request->getServerParams()['HTTP_REFERER'],
            ]);
            return $this->httpHandler->getInfiniteLoopDetectedResponse();
        }

        // Get site
        try {
            $site = $this->psr7Typo3Connector->getSite($request);
        } catch (InvalidOrNoSiteException $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'requestURI' => (string)$request->getUri(),
                    'referer' => $request->getServerParams()['HTTP_REFERER'] ?? '',
                ]
            );
            return $this->httpHandler->getInvalidOrNoSiteResponse();
        }

        // Record the request
        if (!$site->getConfiguration()['disableStatisticsRecording']) {
            $this->statisticsUtility->recordRequest($request, $this->statusCode, $reasons['code'] ?? null);
        }

        $this->logger->debug('Startup', [
            'site' => $site->getIdentifier(),
            'requestURI' => (string)$request->getUri(),
            'message' => $message,
            'reasons' => $reasons,
            'statusCode' => $this->statusCode,
            'errorHandlerConfiguration' => $this->errorHandlerConfiguration,
        ]);

        // Generate the errorPage URI
        $errorPageURI = $this->uriGenerator->generate($request, $this->errorHandlerConfiguration);
        $this->logger->notice('Fetching error page', [
            'currentURI' => (string)$request->getUri(),
            'errorPageURI' => (string)$errorPageURI,
        ]);

        // Generate request options (@see http://docs.guzzlephp.org/en/stable/request-options.html)
        $errorPageRequestOptions = $this->requestOptionsGenerator->generate($request, $errorPageURI);
        $this->logger->debug('Generate error page request options', [
            'errorPageRequestOptions' => $errorPageRequestOptions,
        ]);

        // Fetch the error page
        $errorPageResponse = $this->httpHandler->sendErrorPageRequest($errorPageURI, $errorPageRequestOptions);
        $errorPageContents = $errorPageResponse->getBody()->getContents();

        // Replace old-style markers
        $errorPageContents = str_replace('###REASON###', htmlspecialchars($message), $errorPageContents);
        $errorPageContents = str_replace('###CURRENT_URL###', htmlspecialchars((string)$request->getUri()), $errorPageContents);

        // Create the response
        $response = $this->httpHandler->createResponse($errorPageContents, $this->statusCode);

        // Passthrough the 'Content-Type' header
        if ($site->getConfiguration()['passthroughContentTypeHeader'] && $errorPageResponse->hasHeader('content-type')) {
            $response = $response->withHeader('Content-Type', $errorPageResponse->getHeaderLine('content-type'));
        }

        return $response;
    }

    /**
     * @param UriInterface $errorPageURI
     * @param array $errorPageRequestOptions
     * @param Site $site
     * @param \Throwable $e
     * @return ResponseInterface
     */
    protected function getDebugErrorPageRequestExceptionResponse(UriInterface $errorPageURI, array $errorPageRequestOptions, Site $site, \Throwable $e): ResponseInterface
    {
        $debugArray = [
            'siteConfiguration' => $site->getConfiguration(),
            'errorHandlerConfiguration' => $this->errorHandlerConfiguration,
            'errorPageURI' => (string)$errorPageURI,
            'errorPageRequestOptions' => $errorPageRequestOptions,
            'exception' => [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ],
        ];
        if ($e instanceof RequestException) {
            $debugArray['exception']['request'] = $e->getRequest();
            $debugArray['exception']['response'] = $e->getResponse();
        }
        if ($e->getPrevious()) {
            $debugArray['exception']['previous'] = [
                'type' => get_class($e->getPrevious()),
                'message' => $e->getPrevious()->getMessage(),
                'code' => $e->getPrevious()->getCode(),
            ];
        }
        $content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>EXT:pagenotfoundhandling DEBUG</title>
</head>
<body>
    <h1>Exception: ' . htmlspecialchars(get_class($e)) . '</h1>
    ' . \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($debugArray, 'EXT:pagenotfoundhandling DEBUG', 8, false, true, true) . '
</body>
</html>';
        return $this->createResponse($content, 500);
    }
}
