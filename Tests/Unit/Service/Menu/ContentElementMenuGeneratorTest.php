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

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Content\ContentElementFilter;
use Xima\XimaTypo3FrontendEdit\Service\Menu\{AdditionalDataHandler, ContentElementButtonBuilder, ContentElementMenuGenerator};
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

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
        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->method('buildUriFromRoute')->willReturn(new Uri('/typo3/mock'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);

        for ($i = 0; $i < 10; ++$i) {
            $versionMock = $this->createMock(Typo3Version::class);
            $versionMock->method('getMajorVersion')->willReturn(13);
            GeneralUtility::addInstance(Typo3Version::class, $versionMock);
        }
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TCA']);
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

    #[Test]
    public function getDropdownReturnsEmptyArrayWhenNoContentElementsFound(): void
    {
        $this->setUpAuthenticatedBackendUser();
        $this->setUpLanguageService();
        $this->setUpTca();

        $connectionPool = $this->createConnectionPoolReturning([]);
        $generator = $this->createGenerator($connectionPool);

        $result = $generator->getDropdown(1, '/return', 0, $this->createRequestMock());

        self::assertSame([], $result);
    }

    #[Test]
    public function getDropdownReturnsRenderedMenuForContentElement(): void
    {
        $this->setUpAuthenticatedBackendUser();
        $this->setUpLanguageService();
        $this->setUpTca();

        $element = $this->createContentElementRecord(42);
        $connectionPool = $this->createConnectionPoolReturning([$element]);
        $generator = $this->createGenerator($connectionPool);

        $result = $generator->getDropdown(1, '/return#c99', 0, $this->createRequestMock());

        self::assertArrayHasKey(42, $result);
        self::assertArrayHasKey('element', $result[42]);
        self::assertArrayHasKey('menu', $result[42]);
        self::assertIsArray($result[42]['menu']);
        self::assertSame('Text & Media', $result[42]['element']['ctypeLabel']);
        self::assertArrayHasKey('ctypeIcon', $result[42]['element']);
    }

    #[Test]
    public function getDropdownFallsBackToCtypeWhenLabelIsEmpty(): void
    {
        $this->setUpAuthenticatedBackendUser();
        $this->setUpLanguageService(emptyLabels: true);
        $this->setUpTca(label: '');

        $element = $this->createContentElementRecord(7);
        $connectionPool = $this->createConnectionPoolReturning([$element]);
        $generator = $this->createGenerator($connectionPool);

        $result = $generator->getDropdown(1, '/return', 0, $this->createRequestMock());

        self::assertSame('textmedia', $result[7]['element']['ctypeLabel']);
    }

    #[Test]
    public function getDropdownSkipsElementWithoutMatchingTcaConfig(): void
    {
        $this->setUpAuthenticatedBackendUser();
        $this->setUpLanguageService();
        $this->setUpTca(ctypeValue: 'some_other_type');

        $element = $this->createContentElementRecord(11);
        $connectionPool = $this->createConnectionPoolReturning([$element]);
        $generator = $this->createGenerator($connectionPool);

        $result = $generator->getDropdown(1, '/return', 0, $this->createRequestMock());

        self::assertSame([], $result);
    }

    #[Test]
    public function getDropdownUsesUidBasedFetchingWhenUidsProvided(): void
    {
        $this->setUpAuthenticatedBackendUser();
        $this->setUpLanguageService();
        $this->setUpTca();

        $element = $this->createContentElementRecord(55);
        $connectionPool = $this->createConnectionPoolReturning([$element]);
        $generator = $this->createGenerator($connectionPool);

        $result = $generator->getDropdown(1, '/return', 0, $this->createRequestMock(), ['_uids' => [55, 55, 'invalid', -3]]);

        self::assertArrayHasKey(55, $result);
    }

    #[Test]
    public function getDropdownEmptyUidsFallsBackToPidBasedFetching(): void
    {
        $this->setUpAuthenticatedBackendUser();
        $this->setUpLanguageService();
        $this->setUpTca();

        $element = $this->createContentElementRecord(60);
        $connectionPool = $this->createConnectionPoolReturning([$element]);
        $generator = $this->createGenerator($connectionPool);

        $result = $generator->getDropdown(1, '/return', 0, $this->createRequestMock(), ['_uids' => ['not-an-int']]);

        self::assertArrayHasKey(60, $result);
    }

    #[Test]
    public function getDropdownFiltersOutElementWithoutEditAccess(): void
    {
        $this->setUpAuthenticatedBackendUser(hasEditAccess: false);
        $this->setUpLanguageService();
        $this->setUpTca();

        $element = $this->createContentElementRecord(70);
        $connectionPool = $this->createConnectionPoolReturning([$element]);
        $generator = $this->createGenerator($connectionPool);

        $result = $generator->getDropdown(1, '/return', 0, $this->createRequestMock());

        self::assertSame([], $result);
    }

    #[Test]
    public function getDropdownRespectsEventListenerModifiedButton(): void
    {
        $this->setUpAuthenticatedBackendUser();
        $this->setUpLanguageService();
        $this->setUpTca();

        $element = $this->createContentElementRecord(88);
        $connectionPool = $this->createConnectionPoolReturning([$element]);

        $replacementButton = new Button('replaced', \Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType::Link, '/replaced');
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (FrontendEditDropdownModifyEvent $event) use ($replacementButton): FrontendEditDropdownModifyEvent {
                $event->setMenuButton($replacementButton);

                return $event;
            },
        );

        $generator = $this->createGenerator($connectionPool, $eventDispatcher);

        $result = $generator->getDropdown(1, '/return', 0, $this->createRequestMock());

        self::assertArrayHasKey(88, $result);
        self::assertSame('/replaced', $result[88]['menu']['url']);
    }

    private function createRequestMock(): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn(null);

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function createContentElementRecord(int $uid): array
    {
        return [
            'uid' => $uid,
            'pid' => 1,
            'CType' => 'textmedia',
            'list_type' => '',
            'header' => 'Some header',
            'colPos' => 0,
            'sorting' => 100,
            'tx_container_parent' => 0,
            'l10n_source' => 0,
            'sys_language_uid' => 0,
        ];
    }

    private function setUpAuthenticatedBackendUser(bool $hasEditAccess = true): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1, 'admin' => 1];
        $backendUser->uc = [];
        $backendUser->method('recordEditAccessInternals')->willReturn($hasEditAccess);
        $GLOBALS['BE_USER'] = $backendUser;
    }

    private function setUpLanguageService(bool $emptyLabels = false): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(
            static function (string $input) use ($emptyLabels): string {
                if ($emptyLabels) {
                    return '';
                }

                return match ($input) {
                    'my_label' => 'Text & Media',
                    default => $input,
                };
            },
        );
        $GLOBALS['LANG'] = $languageService;
    }

    private function setUpTca(string $ctypeValue = 'textmedia', string $label = 'my_label'): void
    {
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'columns' => [
                    'CType' => [
                        'config' => [
                            'items' => [
                                [
                                    'value' => $ctypeValue,
                                    'label' => $label,
                                    'icon' => 'content-textmedia',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $records
     */
    private function createConnectionPoolReturning(array $records): ConnectionPool
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($records);

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->method('eq')->willReturn('eq');
        $expressionBuilder->method('in')->willReturn('in');
        $expressionBuilder->method('or')->willReturn(
            $this->createMock(\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression::class),
        );

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('createNamedParameter')->willReturn('param');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        return $connectionPool;
    }

    private function createGenerator(
        ?ConnectionPool $connectionPool = null,
        ?EventDispatcher $eventDispatcher = null,
    ): ContentElementMenuGenerator {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIcon')->willReturn($this->createMock(Icon::class));
        $iconService = new IconService($iconFactoryMock);
        $urlBuilderService = new UrlBuilderService();
        $backendUserService = new BackendUserService();
        $contentElementButtonBuilder = new ContentElementButtonBuilder($iconService, $urlBuilderService);

        $settingsService = (new ReflectionClass(SettingsService::class))->newInstanceWithoutConstructor();

        $contentElementRepository = new ContentElementRepository($connectionPool ?? $this->createMock(ConnectionPool::class));

        $contentElementFilter = new ContentElementFilter($settingsService, $contentElementRepository);
        $additionalDataHandler = (new ReflectionClass(AdditionalDataHandler::class))->newInstanceWithoutConstructor();

        return new ContentElementMenuGenerator(
            $eventDispatcher ?? $this->createDefaultEventDispatcher(),
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

    private function createDefaultEventDispatcher(): EventDispatcher
    {
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        return $eventDispatcher;
    }
}
