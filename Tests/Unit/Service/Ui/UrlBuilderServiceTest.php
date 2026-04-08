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
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Service\Ui\UrlBuilderService;

/**
 * UrlBuilderServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(UrlBuilderService::class)]
final class UrlBuilderServiceTest extends TestCase
{
    private UriBuilder $uriBuilderMock;

    protected function setUp(): void
    {
        $this->uriBuilderMock = $this->createMock(UriBuilder::class);
        GeneralUtility::setSingletonInstance(UriBuilder::class, $this->uriBuilderMock);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function buildEditUrlReturnsCorrectUrl(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('record_edit', self::callback(static fn(array $params): bool => isset($params['edit']['tt_content'][1]) && 'edit' === $params['edit']['tt_content'][1]))
            ->willReturn(new Uri('/typo3/record/edit'));

        $service = new UrlBuilderService();
        $result = $service->buildEditUrl(1, 'tt_content', 0, '/return');

        self::assertSame('/typo3/record/edit', $result);
    }

    #[Test]
    public function buildPageLayoutUrlReturnsCorrectUrl(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('web_layout', self::callback(static fn(array $params): bool => 1 === $params['id'] && 0 === $params['language']))
            ->willReturn(new Uri('/typo3/module/web/layout'));

        $service = new UrlBuilderService();
        $result = $service->buildPageLayoutUrl(1, 0, '/return');

        self::assertSame('/typo3/module/web/layout', $result);
    }

    #[Test]
    public function buildPageLayoutUrlIncludesScrollToElement(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('web_layout', self::callback(static fn(array $params): bool => isset($params['scrollToElement']) && 42 === $params['scrollToElement']))
            ->willReturn(new Uri('/typo3/module/web/layout'));

        $service = new UrlBuilderService();
        $result = $service->buildPageLayoutUrl(1, 0, '/return', 42);

        self::assertSame('/typo3/module/web/layout', $result);
    }

    #[Test]
    public function buildHideUrlReturnsCorrectUrl(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('tce_db', self::anything())
            ->willReturn(new Uri('/typo3/tce_db'));

        $service = new UrlBuilderService();
        $result = $service->buildHideUrl(1, '/return');

        self::assertSame('/typo3/tce_db', $result);
    }

    #[Test]
    public function buildInfoUrlReturnsCorrectUrl(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('show_item', self::callback(static fn(array $params): bool => 1 === $params['uid'] && 'tt_content' === $params['table']))
            ->willReturn(new Uri('/typo3/show_item'));

        $service = new UrlBuilderService();
        $result = $service->buildInfoUrl(1, 'tt_content', '/return');

        self::assertSame('/typo3/show_item', $result);
    }

    #[Test]
    public function buildMoveUrlReturnsCorrectUrl(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('move_element', self::anything())
            ->willReturn(new Uri('/typo3/move_element'));

        $service = new UrlBuilderService();
        $result = $service->buildMoveUrl(1, 'tt_content', 1, '/return');

        self::assertSame('/typo3/move_element', $result);
    }

    #[Test]
    public function buildHistoryUrlReturnsCorrectUrl(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('record_history', self::callback(static fn(array $params): bool => 'tt_content:1' === $params['element']))
            ->willReturn(new Uri('/typo3/record_history'));

        $service = new UrlBuilderService();
        $result = $service->buildHistoryUrl(1, 'tt_content', '/return');

        self::assertSame('/typo3/record_history', $result);
    }

    #[Test]
    public function buildNewContentAfterUrlReturnsCorrectUrl(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('record_edit', self::callback(static fn(array $params): bool => isset($params['edit']['tt_content'][-1]) && 'new' === $params['edit']['tt_content'][-1]))
            ->willReturn(new Uri('/typo3/record/edit'));

        $service = new UrlBuilderService();
        $result = $service->buildNewContentAfterUrl(1, '/return');

        self::assertSame('/typo3/record/edit', $result);
    }

    #[Test]
    public function isContextualEditRouteAvailableReturnsTrueWhenRouteExists(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('record_edit_contextual')
            ->willReturn(new Uri('/typo3/record/edit/contextual'));

        $service = new UrlBuilderService();

        self::assertTrue($service->isContextualEditRouteAvailable());
    }

    #[Test]
    public function isContextualEditRouteAvailableReturnsFalseWhenRouteNotFound(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('record_edit_contextual')
            ->willThrowException(new RouteNotFoundException('Route not found'));

        $service = new UrlBuilderService();

        self::assertFalse($service->isContextualEditRouteAvailable());
    }

    #[Test]
    public function buildContextualEditUrlReturnsUrlWhenRouteExists(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('record_edit_contextual', self::anything())
            ->willReturn(new Uri('/typo3/record/edit/contextual'));

        $service = new UrlBuilderService();
        $result = $service->buildContextualEditUrl(1, 'tt_content', 0, '/return');

        self::assertSame('/typo3/record/edit/contextual', $result);
    }

    #[Test]
    public function buildContextualEditUrlReturnsNullWhenRouteNotFound(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->willThrowException(new RouteNotFoundException('Route not found'));

        $service = new UrlBuilderService();
        $result = $service->buildContextualEditUrl(1, 'tt_content', 0);

        self::assertNull($result);
    }

    #[Test]
    public function buildToggleUrlReturnsCorrectUrl(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('ajax_frontendEdit_toggle', [])
            ->willReturn(new Uri('/typo3/ajax/frontend-edit/toggle'));

        $service = new UrlBuilderService();
        $result = $service->buildToggleUrl();

        self::assertSame('/typo3/ajax/frontend-edit/toggle', $result);
    }

    #[Test]
    public function buildEditInformationUrlReturnsCorrectUrl(): void
    {
        $this->uriBuilderMock->method('buildUriFromRoute')
            ->with('ajax_frontendEdit_editInformation', [])
            ->willReturn(new Uri('/typo3/ajax/frontend-edit/edit-information'));

        $service = new UrlBuilderService();
        $result = $service->buildEditInformationUrl();

        self::assertSame('/typo3/ajax/frontend-edit/edit-information', $result);
    }
}
