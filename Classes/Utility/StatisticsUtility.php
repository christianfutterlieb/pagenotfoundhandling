<?php
declare(strict_types=1);
namespace AawTeam\Pagenotfoundhandling\Utility;

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

use AawTeam\Pagenotfoundhandling\ErrorHandler\Exception\InvalidOrNoSiteException;
use AawTeam\Pagenotfoundhandling\ErrorHandler\Psr7Typo3Connector;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Database\Connection;

/**
 * StatisticsUtility
 */
class StatisticsUtility
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Psr7Typo3Connector
     */
    protected $psr7Typo3Connector;

    public function __construct(Connection $connection, Psr7Typo3Connector $psr7Typo3Connector)
    {
        $this->connection = $connection;
        $this->psr7Typo3Connector = $psr7Typo3Connector;
    }

    /**
     * @param RequestInterface $request
     * @param int $status
     * @param string|null $failureReasonCode (see \TYPO3\CMS\Frontend\Page\PageAccessFailureReasons)
     */
    public function recordRequest(RequestInterface $request, int $status, ?string $failureReasonCode = null)
    {
        try {
            $siteIdentifier = $this->psr7Typo3Connector->getSite($request)->getIdentifier();
        } catch (InvalidOrNoSiteException $e) {
            $siteIdentifier = null;
        }

        $this->connection->insert(
            'tx_pagenotfoundhandling_history',
            [
                'time' => time(),
                'site_identifier' => $siteIdentifier,
                'status_code' => $status,
                'failure_reason' => $failureReasonCode,
                'request_uri' => (string)$request->getUri(),
                'referer_uri' => $request->hasHeader('referer') ? $request->getHeaderLine('referer') : '',
                'user_agent' => $request->hasHeader('user-agent') ? $request->getHeaderLine('user-agent') : '',
            ],
            [
                \PDO::PARAM_INT,
                $siteIdentifier === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                $failureReasonCode === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
            ]
        );
    }
}
