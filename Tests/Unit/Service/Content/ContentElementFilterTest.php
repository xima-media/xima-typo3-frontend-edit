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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Content;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\{Expression\ExpressionBuilder, QueryBuilder};
use TYPO3\CMS\Core\Settings\SettingsInterface;
use TYPO3\CMS\Core\Site\Entity\{Site, SiteSettings};
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\{GeneralUtility, RootlineUtility};
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Content\ContentElementFilter;

/**
 * ContentElementFilterTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ContentElementFilter::class)]
final class ContentElementFilterTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function filterContentElementsIncludesAccessibleElements(): void
    {
        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(),
        );

        $backendUser = $this->createBackendUser(true);
        $elements = [
            ['uid' => 1, 'CType' => 'text', 'list_type' => ''],
            ['uid' => 2, 'CType' => 'html', 'list_type' => ''],
        ];

        $result = $filter->filterContentElements($elements, $backendUser, $this->createRequest([]));

        self::assertCount(2, $result);
    }

    #[Test]
    public function filterContentElementsExcludesElementsWithoutEditAccess(): void
    {
        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(),
        );

        $backendUser = $this->createBackendUser(false);
        $elements = [['uid' => 1, 'CType' => 'text', 'list_type' => '']];

        $result = $filter->filterContentElements($elements, $backendUser, $this->createRequest([]));

        self::assertSame([], $result);
    }

    #[Test]
    public function filterContentElementsExcludesIgnoredUids(): void
    {
        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(),
        );

        $backendUser = $this->createBackendUser(true);
        $elements = [
            ['uid' => 1, 'CType' => 'text', 'list_type' => ''],
            ['uid' => 2, 'CType' => 'text', 'list_type' => ''],
        ];

        $request = $this->createRequest(['frontendEdit.filter.ignoreUids' => '1']);
        $result = $filter->filterContentElements($elements, $backendUser, $request);

        self::assertCount(1, $result);
        self::assertSame(2, $result[0]['uid']);
    }

    #[Test]
    public function filterContentElementsExcludesIgnoredCTypes(): void
    {
        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(),
        );

        $backendUser = $this->createBackendUser(true);
        $elements = [
            ['uid' => 1, 'CType' => 'text', 'list_type' => ''],
            ['uid' => 2, 'CType' => 'html', 'list_type' => ''],
        ];

        $request = $this->createRequest(['frontendEdit.filter.ignoreCTypes' => 'html']);
        $result = $filter->filterContentElements($elements, $backendUser, $request);

        self::assertCount(1, $result);
        self::assertSame('text', $result[0]['CType']);
    }

    #[Test]
    public function filterContentElementsExcludesIgnoredListTypes(): void
    {
        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(),
        );

        $backendUser = $this->createBackendUser(true);
        $elements = [
            ['uid' => 1, 'CType' => 'list', 'list_type' => 'news_pi1'],
            ['uid' => 2, 'CType' => 'list', 'list_type' => 'other'],
        ];

        $request = $this->createRequest(['frontendEdit.filter.ignoreListTypes' => 'news_pi1']);
        $result = $filter->filterContentElements($elements, $backendUser, $request);

        self::assertCount(1, $result);
        self::assertSame('other', $result[0]['list_type']);
    }

    #[Test]
    public function isPageIgnoredReturnsTrueWhenSubpageOfIgnoredPid(): void
    {
        $this->registerRootline([['uid' => 10], ['uid' => 5]]);

        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(),
        );

        $request = $this->createRequest(['frontendEdit.filter.ignorePids' => '5']);
        self::assertTrue($filter->isPageIgnored(10, $request));
    }

    #[Test]
    public function isPageIgnoredReturnsTrueWhenDoktypeIgnored(): void
    {
        $this->registerRootline([['uid' => 10]]);

        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(['doktype' => 254]),
        );

        $request = $this->createRequest([
            'frontendEdit.filter.ignorePids' => '5',
            'frontendEdit.filter.ignoreDoktypes' => '254',
        ]);
        self::assertTrue($filter->isPageIgnored(10, $request));
    }

    #[Test]
    public function isPageIgnoredReturnsFalseWhenDoktypeNotIgnored(): void
    {
        $this->registerRootline([['uid' => 10]]);

        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(['doktype' => 1]),
        );

        $request = $this->createRequest([
            'frontendEdit.filter.ignorePids' => '5',
            'frontendEdit.filter.ignoreDoktypes' => '254',
        ]);
        self::assertFalse($filter->isPageIgnored(10, $request));
    }

    #[Test]
    public function isPageIgnoredReturnsFalseWhenDoktypeIsNull(): void
    {
        $this->registerRootline([['uid' => 10]]);

        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(false),
        );

        $request = $this->createRequest([
            'frontendEdit.filter.ignorePids' => '5',
            'frontendEdit.filter.ignoreDoktypes' => '254',
        ]);
        self::assertFalse($filter->isPageIgnored(10, $request));
    }

    #[Test]
    public function isPageIgnoredReturnsFalseWhenNoDoktypesConfigured(): void
    {
        $this->registerRootline([['uid' => 10]]);

        $filter = new ContentElementFilter(
            $this->createSettingsService(),
            $this->createRepository(),
        );

        self::assertFalse($filter->isPageIgnored(10, $this->createRequest([])));
    }

    private function createBackendUser(bool $hasAccess): BackendUserAuthentication
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        if (method_exists(BackendUserAuthentication::class, 'checkRecordEditAccess')) {
            $backendUser->method('checkRecordEditAccess')->willReturn(new \TYPO3\CMS\Core\Authentication\AccessCheckResult($hasAccess));
        }
        $backendUser->method('recordEditAccessInternals')->willReturn($hasAccess);

        return $backendUser;
    }

    private function createSettingsService(): SettingsService
    {
        return new SettingsService($this->createMock(ExtensionConfiguration::class), $this->createMock(SiteFinder::class));
    }

    /**
     * @param array<string, mixed>|false|null $doktypeRow
     */
    private function createRepository(array|false|null $doktypeRow = null): ContentElementRepository
    {
        $connectionPool = $this->createMock(ConnectionPool::class);

        if (null !== $doktypeRow) {
            $result = $this->createMock(Result::class);
            $result->method('fetchAssociative')->willReturn($doktypeRow);

            $queryBuilder = $this->createMock(QueryBuilder::class);
            $queryBuilder->method('select')->willReturnSelf();
            $queryBuilder->method('from')->willReturnSelf();
            $queryBuilder->method('where')->willReturnSelf();
            $queryBuilder->method('createNamedParameter')->willReturn(':dcValue');
            $queryBuilder->method('executeQuery')->willReturn($result);
            $queryBuilder->method('expr')->willReturn($this->createMock(ExpressionBuilder::class));

            $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        }

        return new ContentElementRepository($connectionPool);
    }

    /**
     * @param array<int, array<string, int>> $rootLine
     */
    private function registerRootline(array $rootLine): void
    {
        $rootlineUtility = $this->createMock(RootlineUtility::class);
        $rootlineUtility->method('get')->willReturn($rootLine);
        GeneralUtility::addInstance(RootlineUtility::class, $rootlineUtility);
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
        $request->method('getAttribute')->with('site')->willReturn($site);

        return $request;
    }
}
