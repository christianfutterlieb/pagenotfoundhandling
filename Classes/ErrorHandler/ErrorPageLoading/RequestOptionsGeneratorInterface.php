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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * RequestOptionsGeneratorInterface
 */
interface RequestOptionsGeneratorInterface
{
    /**
     * Generate the request options for the Guzzle HTTP client
     *
     * @param ServerRequestInterface $request
     * @param UriInterface $errorPageURI
     * @return array
     */
    public function generate(ServerRequestInterface $request, UriInterface $errorPageURI): array;
}
