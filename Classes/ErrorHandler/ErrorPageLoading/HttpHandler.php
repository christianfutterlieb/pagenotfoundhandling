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

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * HttpHandler
 */
class HttpHandler implements HttpHandlerInterface
{
    protected const HTTP_HEADER_XGENERATEDBY = 'EXT:pagenotfoundhandling';
    protected const HTTP_HEADER_XERRORREASON_INFINITELOOP = 'Infinite loop detected';
    protected const HTTP_HEADER_XERRORREASON_INVALIDORNOSITE = 'Invalid or no Site object found';

    /**
     * @var RequestFactoryInterface
     */
    protected $requestFactory;

    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var ClientInterface
     */
    protected $httpClient;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        ResponseFactoryInterface $responseFactory,
        ClientInterface $httpClient
    ) {
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->httpClient = $httpClient;
    }

    public function sendErrorPageRequest(UriInterface $errorPageURI, array $options): ResponseInterface
    {
        $errorPageRequest = $this->requestFactory->createRequest('GET', $errorPageURI);

        try {
            $response = $this->httpClient->send($errorPageRequest, $options);
        } catch (ClientException $e) {
            if ($this->isInfiniteLoopDetectedResponse($e->getResponse())) {
                // Note: this event is logged at the 'incoming' side
                return $this->getInfiniteLoopDetectedResponse();
            }
            return $e->getResponse();
        } catch (GuzzleException $e) {
            $content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>500 Internal Server Error</title>
</head>
<body>
    <h1>500 Internal Server Error</h1>
    <p>Uncaught GuzzleException</p>
</body>
</html>';
            return $this->createResponse($content, 500)->withHeader(
                'X-Error-Reason',
                'Uncaught GuzzleException'
            );
        }

        return $response;
    }

    public function isInfiniteLoopRequest(ServerRequestInterface $request): bool
    {
        return (bool)($request->getQueryParams()['loopPrevention'] ?? false);
    }

    public function isInfiniteLoopDetectedResponse(ResponseInterface $response): bool
    {
        return
            $response->hasHeader('x-generated-by')
            && $response->getHeaderLine('x-generated-by') === self::HTTP_HEADER_XGENERATEDBY
            && $response->hasHeader('x-error-reason')
            && $response->getHeaderLine('x-error-reason') === self::HTTP_HEADER_XERRORREASON_INFINITELOOP;
    }

    public function getInfiniteLoopDetectedResponse(): ResponseInterface
    {
        $content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>508 Loop Detected</title>
</head>
<body>
    <h1>508 Loop Detected</h1>
    <p>An infinite loop has been detected.</p>
</body>
</html>';
        return $this->createResponse($content, 508)->withHeader(
            'X-Error-Reason',
            self::HTTP_HEADER_XERRORREASON_INFINITELOOP
        );
    }

    public function getInvalidOrNoSiteResponse(): ResponseInterface
    {
        $content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>500 Internal Server Error</title>
</head>
<body>
    <h1>500 Internal Server Error</h1>
    <p>Invalid or no Site object found.</p>
</body>
</html>';
        return $this->createResponse($content, 500)->withHeader(
            'X-Error-Reason',
            self::HTTP_HEADER_XERRORREASON_INVALIDORNOSITE
        );
    }

    public function createResponse(string $content, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)
            ->withHeader('X-Generated-By', self::HTTP_HEADER_XGENERATEDBY)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($content);

        return $response;
    }
}
