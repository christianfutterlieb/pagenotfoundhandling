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

use AawTeam\Pagenotfoundhandling\ErrorHandler\Exception\InvalidOrNoSiteException;
use AawTeam\Pagenotfoundhandling\ErrorHandler\Exception\InvalidOrNoSiteLanguageException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Psr7Typo3Connector
 */
class Psr7Typo3Connector
{
    public function getSite(ServerRequestInterface $request): Site
    {
        $site = $request->getAttribute('site', null);
        if (!$site instanceof Site) {
            throw new InvalidOrNoSiteException(
                $site,
                ($site instanceof SiteInterface) ? 'Unsupported site object found in request' : 'No site object found in request'
            );
        }
        return $site;
    }

    public function getSiteLanguage(ServerRequestInterface $request): SiteLanguage
    {
        $language = $request->getAttribute('language', null);
        if (!$language instanceof SiteLanguage) {
            throw new InvalidOrNoSiteLanguageException(
                $language,
                'No site language object found in request'
            );
        }
        return $language;
    }
}
