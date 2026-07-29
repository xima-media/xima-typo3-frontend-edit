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
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\{ApplicationContext, Environment};
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\{PackageInterface, PackageManager};
use TYPO3\CMS\Core\Settings\SettingsInterface;
use TYPO3\CMS\Core\Site\Entity\{Site, SiteSettings};
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\{ExtensionManagementUtility, GeneralUtility};
use TYPO3\CMS\Core\View\{ViewFactoryInterface, ViewInterface};
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Menu\PageMenuGenerator;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{BackendSettingsService, ResourceRendererService, UrlBuilderService};

use function dirname;

/**
 * ResourceRendererServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ResourceRendererService::class)]
final class ResourceRendererServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->method('buildUriFromRoute')->willReturn(new Uri('/typo3/mock'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilder);

        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackagePath')->willReturn(dirname(__DIR__, 4).'/');

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('resolvePackagePath')
            ->willReturnCallback(static fn (string $path): string => '/var/www/html/public/'.ltrim(str_replace('EXT:', 'typo3conf/ext/', $path), '/'));
        $packageManager->method('isPackageActive')->willReturn(true);
        $packageManager->method('getPackage')->willReturn($package);
        $packageManager->method('getActivePackages')->willReturn([]);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManager);
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
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
    }

    #[Test]
    public function renderReturnsRenderedTemplate(): void
    {
        $view = $this->createMock(ViewInterface::class);
        $view->method('render')->willReturn('<rendered/>');
        $viewFactory = $this->createMock(ViewFactoryInterface::class);
        $viewFactory->method('create')->willReturn($view);
        GeneralUtility::addInstance(ViewFactoryInterface::class, $viewFactory);

        $service = $this->createService(false);

        $result = $service->render($this->createRequest([]));

        self::assertSame('<rendered/>', $result);
    }

    #[Test]
    public function renderWithContextualEditingEnabledAddsBackendStubs(): void
    {
        GeneralUtility::addInstance(ViewFactoryInterface::class, $this->createViewFactoryCapturingResources($captured));

        $service = $this->createService(true);

        $service->render($this->createRequest(['frontendEdit.enableContextualEditing' => true]));

        self::assertArrayHasKey('backend_stubs', $captured);
        self::assertArrayHasKey('iframe_edit', $captured);
        self::assertArrayHasKey('contextual_edit', $captured);
    }

    #[Test]
    public function renderAddsCoreResources(): void
    {
        GeneralUtility::addInstance(ViewFactoryInterface::class, $this->createViewFactoryCapturingResources($captured));

        $service = $this->createService(false);

        $service->render($this->createRequest([]));

        self::assertArrayHasKey('floating_ui', $captured);
        self::assertArrayHasKey('settings_config', $captured);
        self::assertArrayHasKey('toolbar_config', $captured);
        self::assertArrayHasKey('sticky_toolbar_config', $captured);
        self::assertArrayHasKey('sticky_toolbar', $captured);
    }

    #[Test]
    public function renderAddsDebugConfigWhenDebugModeEnabled(): void
    {
        GeneralUtility::addInstance(ViewFactoryInterface::class, $this->createViewFactoryCapturingResources($captured));

        $service = $this->createService(false, debugMode: true);

        $service->render($this->createRequest([]));

        self::assertArrayHasKey('debug_config', $captured);
    }

    #[Test]
    public function renderOmitsStickyToolbarWhenDisabled(): void
    {
        GeneralUtility::addInstance(ViewFactoryInterface::class, $this->createViewFactoryCapturingResources($captured));

        $service = $this->createService(false);

        $service->render($this->createRequest(['frontendEdit.showStickyToolbar' => false]));

        self::assertArrayNotHasKey('sticky_toolbar', $captured);
        self::assertArrayNotHasKey('sticky_toolbar_config', $captured);
    }

    #[Test]
    public function renderAddsFlashMessagesConfigWhenProvided(): void
    {
        GeneralUtility::addInstance(ViewFactoryInterface::class, $this->createViewFactoryCapturingResources($captured));

        $service = $this->createService(false);

        $flashMessages = [
            ['title' => 'Saved', 'message' => 'Done', 'severity' => 'OK'],
        ];
        $service->render($this->createRequest([]), $flashMessages);

        self::assertArrayHasKey('flash_messages_config', $captured);
    }

    #[Test]
    public function renderMarksToolbarDisabledWhenFrontendEditDisabled(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = ['frontendEditDisabled' => true];
        $GLOBALS['BE_USER'] = $backendUser;

        GeneralUtility::addInstance(ViewFactoryInterface::class, $this->createViewFactoryCapturingResources($captured));

        $service = $this->createService(false);

        $service->render($this->createRequest([]));

        self::assertStringContainsString('data-disabled="true"', $captured['toolbar_config']);
    }

    /**
     * @param array<string, string>|null $captured
     */
    private function createViewFactoryCapturingResources(?array &$captured): ViewFactoryInterface
    {
        $view = $this->createMock(ViewInterface::class);
        $view->method('assignMultiple')->willReturnCallback(
            static function (array $values) use (&$captured, &$view): ViewInterface {
                $captured = $values['resources'] ?? [];

                return $view;
            },
        );
        $view->method('render')->willReturn('<rendered/>');

        $viewFactory = $this->createMock(ViewFactoryInterface::class);
        $viewFactory->method('create')->willReturn($view);

        return $viewFactory;
    }

    private function createService(bool $contextualEditing, bool $debugMode = false): ResourceRendererService
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn(['frontendDebugMode' => $debugMode]);

        $settingsService = new SettingsService($extensionConfiguration, $this->createMock(SiteFinder::class));
        $backendUserService = new BackendUserService();
        $urlBuilderService = new UrlBuilderService();
        $pageMenuGenerator = (new ReflectionClass(PageMenuGenerator::class))->newInstanceWithoutConstructor();
        $backendSettingsService = new BackendSettingsService();

        return new ResourceRendererService(
            $settingsService,
            $backendUserService,
            $urlBuilderService,
            $pageMenuGenerator,
            $backendSettingsService,
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createRequest(array $settings): ServerRequestInterface
    {
        $settingsInterface = $this->createMock(SettingsInterface::class);
        $settingsInterface->method('has')->willReturn(false);
        $settingsInterface->method('getIdentifiers')->willReturn([]);
        $siteSettings = new SiteSettings($settingsInterface, [], $settings);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturnCallback(
            static fn (string $name): mixed => 'site' === $name ? $site : null,
        );
        $request->method('getUri')->willReturn(new Uri('https://example.com/page'));

        return $request;
    }
}
