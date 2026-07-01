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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Configuration;

use Exception;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Settings\SettingsInterface;
use TYPO3\CMS\Core\Site\Entity\{Site, SiteSettings};
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;

/**
 * SettingsServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(SettingsService::class)]
final class SettingsServiceTest extends TestCase
{
    #[Test]
    public function isEnabledReturnsFalseWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertFalse($service->isEnabled($this->createRequestWithoutSite()));
    }

    #[Test]
    public function isEnabledReturnsSettingValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.enabled' => false]);

        self::assertFalse($service->isEnabled($request));
    }

    #[Test]
    public function isEnabledDefaultsToTrue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings([]);

        self::assertTrue($service->isEnabled($request));
    }

    #[Test]
    public function getIgnoredPidsReturnsEmptyArrayWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertSame([], $service->getIgnoredPids($this->createRequestWithoutSite()));
    }

    #[Test]
    public function getIgnoredPidsParsesCommaDelimitedString(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.filter.ignorePids' => '1, 2 ,3']);

        self::assertSame(['1', '2', '3'], array_values($service->getIgnoredPids($request)));
    }

    #[Test]
    public function getIgnoredPidsReturnsEmptyArrayForEmptyValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.filter.ignorePids' => '']);

        self::assertSame([], $service->getIgnoredPids($request));
    }

    #[Test]
    public function getIgnoredPidsFiltersEmptyItems(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.filter.ignorePids' => '1,,2']);

        self::assertSame(['1', '2'], array_values($service->getIgnoredPids($request)));
    }

    #[Test]
    public function getIgnoredPidsReturnsEmptyArrayWhenSettingThrows(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithThrowingSettings();

        self::assertSame([], $service->getIgnoredPids($request));
    }

    #[Test]
    public function getIgnoredDoktypesReturnsIntegers(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.filter.ignoreDoktypes' => '3,254']);

        self::assertSame([3, 254], array_values($service->getIgnoredDoktypes($request)));
    }

    #[Test]
    public function getIgnoredCTypesParsesValues(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.filter.ignoreCTypes' => 'text,html']);

        self::assertSame(['text', 'html'], array_values($service->getIgnoredCTypes($request)));
    }

    #[Test]
    public function getIgnoredListTypesParsesValues(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.filter.ignoreListTypes' => 'news_pi1']);

        self::assertSame(['news_pi1'], array_values($service->getIgnoredListTypes($request)));
    }

    #[Test]
    public function getIgnoredUidsReturnsIntegers(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.filter.ignoreUids' => '5,6']);

        self::assertSame([5, 6], array_values($service->getIgnoredUids($request)));
    }

    #[Test]
    public function getColorSchemeReturnsAutoWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertSame('auto', $service->getColorScheme($this->createRequestWithoutSite()));
    }

    #[Test]
    public function getColorSchemeReturnsValidScheme(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.colorScheme' => 'dark']);

        self::assertSame('dark', $service->getColorScheme($request));
    }

    #[Test]
    public function getColorSchemeFallsBackToAutoForInvalidValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.colorScheme' => 'neon']);

        self::assertSame('auto', $service->getColorScheme($request));
    }

    #[Test]
    public function isShowContextMenuReturnsTrueWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertTrue($service->isShowContextMenu($this->createRequestWithoutSite()));
    }

    #[Test]
    public function isShowContextMenuReturnsSettingValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.showContextMenu' => false]);

        self::assertFalse($service->isShowContextMenu($request));
    }

    #[Test]
    public function isShowStickyToolbarReturnsTrueWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertTrue($service->isShowStickyToolbar($this->createRequestWithoutSite()));
    }

    #[Test]
    public function isShowStickyToolbarReturnsSettingValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.showStickyToolbar' => false]);

        self::assertFalse($service->isShowStickyToolbar($request));
    }

    #[Test]
    public function isShowInsertButtonsReturnsTrueWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertTrue($service->isShowInsertButtons($this->createRequestWithoutSite()));
    }

    #[Test]
    public function isShowInsertButtonsReturnsSettingValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.showInsertButtons' => false]);

        self::assertFalse($service->isShowInsertButtons($request));
    }

    #[Test]
    public function getToolbarPositionReturnsDefaultWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertSame('bottom-right', $service->getToolbarPosition($this->createRequestWithoutSite()));
    }

    #[Test]
    public function getToolbarPositionReturnsValidPosition(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.toolbarPosition' => 'top-left']);

        self::assertSame('top-left', $service->getToolbarPosition($request));
    }

    #[Test]
    public function getToolbarPositionFallsBackForInvalidPosition(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.toolbarPosition' => 'middle']);

        self::assertSame('bottom-right', $service->getToolbarPosition($request));
    }

    #[Test]
    public function isEnableOutlineReturnsTrueWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertTrue($service->isEnableOutline($this->createRequestWithoutSite()));
    }

    #[Test]
    public function isEnableOutlineReturnsSettingValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.enableOutline' => false]);

        self::assertFalse($service->isEnableOutline($request));
    }

    #[Test]
    public function isEnableScrollToElementReturnsTrueWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertTrue($service->isEnableScrollToElement($this->createRequestWithoutSite()));
    }

    #[Test]
    public function isEnableScrollToElementReturnsSettingValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.enableScrollToElement' => false]);

        self::assertFalse($service->isEnableScrollToElement($request));
    }

    #[Test]
    public function isContextualEditingEnabledReturnsFalseWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertFalse($service->isContextualEditingEnabled($this->createRequestWithoutSite()));
    }

    #[Test]
    public function isContextualEditingEnabledReturnsSettingValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.enableContextualEditing' => true]);

        self::assertTrue($service->isContextualEditingEnabled($request));
    }

    #[Test]
    public function isEnableFlashMessagesReturnsTrueWhenNoSite(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));

        self::assertTrue($service->isEnableFlashMessages($this->createRequestWithoutSite()));
    }

    #[Test]
    public function isEnableFlashMessagesReturnsSettingValue(): void
    {
        $service = new SettingsService($this->createMock(ExtensionConfiguration::class));
        $request = $this->createRequestWithSettings(['frontendEdit.enableFlashMessages' => false]);

        self::assertFalse($service->isEnableFlashMessages($request));
    }

    #[Test]
    public function isFrontendDebugModeEnabledReturnsTrueWhenConfigured(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn(['frontendDebugMode' => true]);

        $service = new SettingsService($extensionConfiguration);

        self::assertTrue($service->isFrontendDebugModeEnabled());
    }

    #[Test]
    public function isFrontendDebugModeEnabledReturnsFalseByDefault(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn([]);

        $service = new SettingsService($extensionConfiguration);

        self::assertFalse($service->isFrontendDebugModeEnabled());
    }

    #[Test]
    public function isFrontendDebugModeEnabledReturnsFalseOnException(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willThrowException(new Exception('boom'));

        $service = new SettingsService($extensionConfiguration);

        self::assertFalse($service->isFrontendDebugModeEnabled());
    }

    private function createRequestWithoutSite(): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn(null);

        return $request;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createRequestWithSettings(array $settings): ServerRequestInterface
    {
        $settingsInterface = $this->createMock(SettingsInterface::class);
        $settingsInterface->method('has')->willReturn(false);
        $settingsInterface->method('getIdentifiers')->willReturn([]);
        $siteSettings = new SiteSettings($settingsInterface, [], $settings);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        return $request;
    }

    private function createRequestWithThrowingSettings(): ServerRequestInterface
    {
        $settingsInterface = $this->createMock(SettingsInterface::class);
        $settingsInterface->method('has')
            ->willThrowException(new ThrowingContainerException('missing'));

        $siteSettings = new SiteSettings($settingsInterface, [], []);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        return $request;
    }
}

/**
 * ThrowingContainerException.
 *
 * @internal
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ThrowingContainerException extends Exception implements NotFoundExceptionInterface {}
