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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Ui;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\{ApplicationContext, Environment};
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\{ExtensionManagementUtility, GeneralUtility};
use Xima\XimaTypo3FrontendEdit\Service\Ui\BackendSettingsService;

/**
 * BackendSettingsServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(BackendSettingsService::class)]
final class BackendSettingsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->method('buildUriFromRoute')->willReturn(new Uri('/typo3/ajax/mock'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilder);

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('resolvePackagePath')
            ->willReturnCallback(static fn (string $path): string => '/var/www/html/public/'.ltrim(str_replace('EXT:', 'typo3conf/ext/', $path), '/'));
        ExtensionManagementUtility::setPackageManager($packageManager);

        Environment::initialize(
            new ApplicationContext('Testing'),
            true,
            true,
            '/var/www/html',
            '/var/www/html/public',
            '/var/www/html/var',
            '/var/www/html/config',
            '/var/www/html/public/index.php',
            'UNIX',
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['LANG'], $GLOBALS['TYPO3_CONF_VARS']);
    }

    #[Test]
    public function getSettingsScriptRendersJsonAndStubScriptTag(): void
    {
        $service = new BackendSettingsService();

        $result = $service->getSettingsScript();

        self::assertStringContainsString('id="frontend-edit-backend-stubs-data"', $result);
        self::assertStringContainsString('backend_stubs.js', $result);
        self::assertStringContainsString('ajaxUrls', $result);
    }

    #[Test]
    public function getSettingsScriptContainsResolvedAjaxUrls(): void
    {
        $service = new BackendSettingsService();

        $result = $service->getSettingsScript();

        self::assertStringContainsString('typo3', $result);
        self::assertStringContainsString('filestorage_tree_data', $result);
    }

    #[Test]
    public function getSettingsScriptAddsNonceAttribute(): void
    {
        $service = new BackendSettingsService();

        $result = $service->getSettingsScript('abc123');

        self::assertStringContainsString('nonce="abc123"', $result);
    }

    #[Test]
    public function getSettingsScriptOmitsNonceWhenEmpty(): void
    {
        $service = new BackendSettingsService();

        $result = $service->getSettingsScript('');

        self::assertStringNotContainsString('nonce=', $result);
    }

    #[Test]
    public function getSettingsScriptSkipsMissingAjaxRoutes(): void
    {
        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->method('buildUriFromRoute')
            ->willThrowException(new RouteNotFoundException('missing'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilder);

        $service = new BackendSettingsService();

        $result = $service->getSettingsScript();

        self::assertStringContainsString('"ajaxUrls":[]', $result);
    }

    #[Test]
    public function getSettingsScriptIncludesLanguageLabelsWhenLanguageServiceAvailable(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturn('Translated');
        $GLOBALS['LANG'] = $languageService;

        $service = new BackendSettingsService();

        $result = $service->getSettingsScript();

        self::assertStringContainsString('Translated', $result);
        self::assertStringContainsString('tree.networkError', $result);
    }

    #[Test]
    public function getSettingsScriptFallsBackToKeyWhenLabelEmpty(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturn('');
        $GLOBALS['LANG'] = $languageService;

        $service = new BackendSettingsService();

        $result = $service->getSettingsScript();

        self::assertStringContainsString('tree.loading', $result);
    }

    #[Test]
    public function getSettingsScriptReturnsEmptyLangWhenNoLanguageService(): void
    {
        unset($GLOBALS['LANG']);

        $service = new BackendSettingsService();

        $result = $service->getSettingsScript();

        self::assertStringContainsString('"lang":[]', $result);
    }

    #[Test]
    public function getSettingsScriptUsesConfiguredDateFormat(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'd.m.Y';

        $service = new BackendSettingsService();

        $result = $service->getSettingsScript();

        self::assertStringContainsString('d.m.Y', $result);
    }
}
