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

use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;

/**
 * PageErrorHandlerTest
 */
class PageErrorHandlerTest extends UnitTestCase
{
    protected function getTestSubject(): PageErrorHandler
    {
        $subject = new PageErrorHandler(404, []);
        return $subject;
    }

    /**
     * @test
     */
    public function implementsCorrectInterface()
    {
        self::assertInstanceOf(PageErrorHandlerInterface::class, $this->getTestSubject());
    }
}
