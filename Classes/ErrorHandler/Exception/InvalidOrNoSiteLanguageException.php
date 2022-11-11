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

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * InvalidOrNoSiteLanguageException
 */
class InvalidOrNoSiteLanguageException extends \RuntimeException
{
    /**
     * @var SiteLanguage
     */
    protected $language;

    public function __construct(?SiteLanguage $language, string $message = null, int $code = null, \Throwable $previous = null)
    {
        $this->language = $language;
        parent::__construct($message, $code, $previous);
    }

    public function getSiteLanguage(): ?SiteLanguage
    {
        return $this->language;
    }
}
