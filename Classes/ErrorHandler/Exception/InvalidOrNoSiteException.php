<?php

declare(strict_types=1);

namespace AawTeam\Pagenotfoundhandling\ErrorHandler\Exception;

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

use TYPO3\CMS\Core\Site\Entity\SiteInterface;

/**
 * InvalidOrNoSiteException
 */
class InvalidOrNoSiteException extends \RuntimeException
{
    /**
     * @var SiteInterface
     */
    protected $site;

    public function __construct(?SiteInterface $site, string $message = null, int $code = null, \Throwable $previous = null)
    {
        $this->site = $site;
        parent::__construct($message, $code, $previous);
    }

    public function getSite(): ?SiteInterface
    {
        return $this->site;
    }
}
