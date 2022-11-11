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

use AawTeam\Pagenotfoundhandling\ErrorHandler\ErrorPageLoading\HttpHandler;
use AawTeam\Pagenotfoundhandling\ErrorHandler\ErrorPageLoading\RequestOptionsGenerator;
use AawTeam\Pagenotfoundhandling\ErrorHandler\ErrorPageLoading\UriGenerator;
use AawTeam\Pagenotfoundhandling\Utility\StatisticsUtility;
use GuzzleHttp\Client;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\Logger;

/**
 * ConcreteErrorHandlerTest
 */
class ConcreteErrorHandlerTest extends UnitTestCase
{
    protected function getTestSubject(): ConcreteErrorHandler
    {
        $statisticsUtilityMock = $this->createMock(StatisticsUtility::class);
        $psr7Typo3Connector = new Psr7Typo3Connector();
        $subject = new ConcreteErrorHandler(
            new UriGenerator($psr7Typo3Connector, new LinkService()),
            new HttpHandler(new RequestFactory(), new ResponseFactory(), new Client()),
            new RequestOptionsGenerator($psr7Typo3Connector),
            $psr7Typo3Connector,
            $statisticsUtilityMock
        );
        $subject->setLogger(new Logger(__CLASS__));
        return $subject;
    }

    /**
     * @test
     */
    public function detectAndHandleInfiniteLoops()
    {
        $queryParams = [
            'loopPrevention' => '1',
        ];
        $uri = (new Uri('https://example.org/'))->withQuery(http_build_query($queryParams));
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->method('getQueryParams')->willReturn($queryParams);
        $serverRequest->method('getUri')->willReturn($uri);
        $serverRequest->method('getServerParams')->willReturn(['HTTP_REFERER' => '']);

        $response = $this->getTestSubject()->run(
            $serverRequest,
            404,
            [],
            'Unit testing',
            []
        );
        self::assertEquals(508, $response->getStatusCode());
        self::assertTrue($response->hasHeader('X-Error-Reason'));
        self::assertNotEmpty($response->getHeaderLine('X-Error-Reason'));
    }

    /**
     * @todo move this test to Psr7Typo3Connector test, as is now responsible
     *       for managing the TYPO3 Site object
     */
    public function noSiteObjectLeadsToServerError()
    {
        $queryParams = [
            'loopPrevention' => '0',
        ];
        $uri = (new Uri('https://example.org/'))->withQuery(http_build_query($queryParams));
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->method('getQueryParams')->willReturn($queryParams);
        $serverRequest->method('getUri')->willReturn($uri);
        $serverRequest->method('getServerParams')->willReturn(['HTTP_REFERER' => '']);

        $serverRequest->method('getAttribute')->with('site')->willReturn(null);

        $response = $this->getTestSubject()->run(
            $serverRequest,
            404,
            [],
            'Unit testing',
            []
        );
        self::assertEquals(500, $response->getStatusCode());
        self::assertTrue($response->hasHeader('X-Error-Reason'));
        self::assertNotEmpty($response->getHeaderLine('X-Error-Reason'));
    }
}
