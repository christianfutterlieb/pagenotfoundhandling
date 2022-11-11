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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * PageErrorHandler
 */
class PageErrorHandler implements PageErrorHandlerInterface
{
    /**
     * @var int
     */
    protected $statusCode = 0;

    /**
     * @var array
     */
    protected $errorHandlerConfiguration = [];

    /**
     * @param int $statusCode
     * @param array $errorHandlerConfiguration
     */
    public function __construct(int $statusCode, array $errorHandlerConfiguration)
    {
        $this->statusCode = $statusCode;
        $this->errorHandlerConfiguration = $errorHandlerConfiguration;
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface::handlePageError()
     */
    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $concreteErrorHandler = GeneralUtility::makeInstance(ConcreteErrorHandler::class);
        return $concreteErrorHandler->run(
            $request,
            $this->statusCode,
            $this->errorHandlerConfiguration,
            $message,
            $reasons
        );
    }
}
