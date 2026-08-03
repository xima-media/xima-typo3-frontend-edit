<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Security;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\{Site, SiteLanguage};
use TYPO3\CMS\Core\Site\SiteFinder;
use Xima\XimaTypo3FrontendEdit\Service\Security\ReturnUrlValidator;

/**
 * ReturnUrlValidatorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ReturnUrlValidator::class)]
final class ReturnUrlValidatorTest extends TestCase
{
    #[Test]
    public function isValidReturnsTrueForRelativeUrl(): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->expects(self::never())->method('getSiteByPageId');
        $validator = new ReturnUrlValidator($siteFinder);

        self::assertTrue($validator->isValid('/some/path', $this->createRequest('https://example.com/'), 1));
    }

    #[Test]
    public function isValidReturnsTrueForSameOriginAbsoluteUrl(): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->expects(self::never())->method('getSiteByPageId');
        $validator = new ReturnUrlValidator($siteFinder);

        self::assertTrue($validator->isValid('https://example.com/return', $this->createRequest('https://example.com/'), 1));
    }

    #[Test]
    public function isValidReturnsFalseForMalformedUrl(): void
    {
        $validator = new ReturnUrlValidator($this->createMock(SiteFinder::class));

        self::assertFalse($validator->isValid('https://example.com:-1/redirect', $this->createRequest('https://example.com/'), 1));
    }

    #[Test]
    public function isValidReturnsFalseWhenSiteCannotBeResolved(): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getSiteByPageId')->willThrowException(new SiteNotFoundException('none', 1));
        $validator = new ReturnUrlValidator($siteFinder);

        self::assertFalse($validator->isValid('https://evil.example/', $this->createRequest('https://example.com/'), 1));
    }

    #[Test]
    public function isValidReturnsFalseForForeignHostNotMatchingSiteBases(): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getSiteByPageId')->willReturn($this->createSite('example.com', ['example.de']));
        $validator = new ReturnUrlValidator($siteFinder);

        self::assertFalse($validator->isValid('https://evil.example/', $this->createRequest('https://example.com/'), 1));
    }

    #[Test]
    public function isValidReturnsTrueForSiteBaseHost(): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getSiteByPageId')->willReturn($this->createSite('example.org', []));
        $validator = new ReturnUrlValidator($siteFinder);

        self::assertTrue($validator->isValid('https://example.org/return', $this->createRequest('https://example.com/'), 1));
    }

    #[Test]
    public function isValidReturnsTrueForSiteLanguageBaseHost(): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getSiteByPageId')->willReturn($this->createSite('example.com', ['example.de']));
        $validator = new ReturnUrlValidator($siteFinder);

        self::assertTrue($validator->isValid('https://example.de/return', $this->createRequest('https://example.com/'), 1));
    }

    private function createRequest(string $uri): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn(new Uri($uri));

        return $request;
    }

    /**
     * @param list<string> $languageBaseHosts
     */
    private function createSite(string $baseHost, array $languageBaseHosts): Site
    {
        $site = $this->createMock(Site::class);
        $site->method('getBase')->willReturn(new Uri('https://'.$baseHost.'/'));

        $languages = array_map(function (string $host): SiteLanguage {
            $language = $this->createMock(SiteLanguage::class);
            $language->method('getBase')->willReturn(new Uri('https://'.$host.'/'));

            return $language;
        }, $languageBaseHosts);

        $site->method('getLanguages')->willReturn($languages);

        return $site;
    }
}
