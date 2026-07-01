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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Template\Components\{ButtonBar, ModifyButtonBarEvent};
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\EventListener\PageLayoutScrollEventListener;

/**
 * PageLayoutScrollEventListenerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(PageLayoutScrollEventListener::class)]
final class PageLayoutScrollEventListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['TYPO3_REQUEST']);
    }

    #[Test]
    public function invokeReturnsEarlyWhenRequestIsNotServerRequest(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = null;
        $pageRenderer = $this->registerPageRenderer();
        $pageRenderer->expects(self::never())->method('loadJavaScriptModule');

        (new PageLayoutScrollEventListener())($this->createEvent());
    }

    #[Test]
    public function invokeReturnsEarlyWhenScrollToElementParameterMissing(): void
    {
        $this->registerRequest([], null);
        $pageRenderer = $this->registerPageRenderer();
        $pageRenderer->expects(self::never())->method('loadJavaScriptModule');

        (new PageLayoutScrollEventListener())($this->createEvent());
    }

    #[Test]
    public function invokeReturnsEarlyWhenRouteIsNotRoute(): void
    {
        $this->registerRequest(['scrollToElement' => '5'], new stdClass());
        $pageRenderer = $this->registerPageRenderer();
        $pageRenderer->expects(self::never())->method('loadJavaScriptModule');

        (new PageLayoutScrollEventListener())($this->createEvent());
    }

    #[Test]
    public function invokeReturnsEarlyWhenRouteIsNotWebLayout(): void
    {
        $route = $this->createMock(Route::class);
        $route->method('getOption')->with('_identifier')->willReturn('web_list');
        $this->registerRequest(['scrollToElement' => '5'], $route);
        $pageRenderer = $this->registerPageRenderer();
        $pageRenderer->expects(self::never())->method('loadJavaScriptModule');

        (new PageLayoutScrollEventListener())($this->createEvent());
    }

    #[Test]
    public function invokeLoadsJavaScriptModuleOnWebLayoutRoute(): void
    {
        $route = $this->createMock(Route::class);
        $route->method('getOption')->with('_identifier')->willReturn('web_layout');
        $this->registerRequest(['scrollToElement' => '5'], $route);
        $pageRenderer = $this->registerPageRenderer();
        $pageRenderer->expects(self::once())
            ->method('loadJavaScriptModule')
            ->with('@xima/ximatypo3frontendedit/scroll_to_element.js');

        (new PageLayoutScrollEventListener())($this->createEvent());
    }

    private function createEvent(): ModifyButtonBarEvent
    {
        return new ModifyButtonBarEvent([], $this->createMock(ButtonBar::class));
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function registerRequest(array $queryParams, mixed $route): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);
        $request->method('getAttribute')->with('route')->willReturn($route);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    private function registerPageRenderer(): PageRenderer
    {
        $pageRenderer = $this->createMock(PageRenderer::class);
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);

        return $pageRenderer;
    }
}
