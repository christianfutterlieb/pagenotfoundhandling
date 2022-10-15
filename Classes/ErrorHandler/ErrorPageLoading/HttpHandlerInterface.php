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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * HttpHandlerInterface
 */
interface HttpHandlerInterface
{
    public function sendErrorPageRequest(UriInterface $errorPageURI, array $options): ResponseInterface;

    public function isInfiniteLoopRequest(ServerRequestInterface $request): bool;
    public function isInfiniteLoopDetectedResponse(ResponseInterface $response): bool;

    public function getInfiniteLoopDetectedResponse(): ResponseInterface;
    public function getInvalidOrNoSiteResponse(): ResponseInterface;
    public function createResponse(string $content, int $status = 200): ResponseInterface;
}
