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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Menu;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Content\ContentElementFilter;
use Xima\XimaTypo3FrontendEdit\Service\Menu\{AdditionalDataHandler, ContentElementButtonBuilder, ContentElementMenuGenerator};
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};

/**
 * ContentElementMenuGeneratorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ContentElementMenuGenerator::class)]
final class ContentElementMenuGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->method('buildUriFromRoute')->willReturn(new Uri('/typo3/mock'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        parent::tearDown();
    }

    #[Test]
    public function getDropdownReturnsEmptyArrayWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $generator = $this->createGenerator();

        $request = $this->createRequestMock();
        $result = $generator->getDropdown(1, '/return', 0, $request);

        self::assertSame([], $result);
    }

    #[Test]
    public function getDropdownReturnsEmptyArrayWhenBackendUserIsNull(): void
    {
        $GLOBALS['BE_USER'] = null;

        $generator = $this->createGenerator();

        $request = $this->createRequestMock();
        $result = $generator->getDropdown(1, '/return', 0, $request);

        self::assertSame([], $result);
    }

    #[Test]
    public function getDropdownReturnsEmptyArrayWhenFrontendEditDisabled(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = [Configuration::UC_KEY_DISABLED => true];
        $GLOBALS['BE_USER'] = $backendUser;

        $generator = $this->createGenerator();

        $request = $this->createRequestMock();
        $result = $generator->getDropdown(1, '/return', 0, $request);

        self::assertSame([], $result);
    }

    #[Test]
    public function getDropdownReturnsEmptyArrayWhenBackendUserHasNoUserData(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = null;
        $GLOBALS['BE_USER'] = $backendUser;

        $generator = $this->createGenerator();

        $request = $this->createRequestMock();
        $result = $generator->getDropdown(1, '/return', 0, $request);

        self::assertSame([], $result);
    }

    private function createRequestMock(): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn(null);

        return $request;
    }

    private function createGenerator(): ContentElementMenuGenerator
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIcon')->willReturn($this->createMock(Icon::class));
        $iconService = new IconService($iconFactoryMock);
        $urlBuilderService = new UrlBuilderService();
        $backendUserService = new BackendUserService();
        $contentElementButtonBuilder = new ContentElementButtonBuilder($iconService, $urlBuilderService);

        $settingsService = (new ReflectionClass(SettingsService::class))->newInstanceWithoutConstructor();
        $contentElementRepository = (new ReflectionClass(ContentElementRepository::class))->newInstanceWithoutConstructor();
        $contentElementFilter = new ContentElementFilter($settingsService, $contentElementRepository);
        $additionalDataHandler = (new ReflectionClass(AdditionalDataHandler::class))->newInstanceWithoutConstructor();

        return new ContentElementMenuGenerator(
            $this->createMock(EventDispatcher::class),
            $backendUserService,
            $contentElementFilter,
            $contentElementButtonBuilder,
            $contentElementRepository,
            $additionalDataHandler,
            $iconService,
            $settingsService,
            $urlBuilderService,
            $this->createMock(ExtensionConfiguration::class),
        );
    }
}
