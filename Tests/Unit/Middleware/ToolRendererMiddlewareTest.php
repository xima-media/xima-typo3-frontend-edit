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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\{Response, ServerRequest};
use TYPO3\CMS\Core\Site\Entity\Site;
use Xima\XimaTypo3FrontendEdit\Middleware\ToolRendererMiddleware;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{FlashMessageService, ResourceRendererService};

/**
 * ToolRendererMiddlewareTest.
 *
 * The renderer is created without constructor, so reaching it fails with an exception. That makes "returns early" observable.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ToolRendererMiddleware::class)]
final class ToolRendererMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $backendUser = $this->createStub(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getTSConfig')->willReturn([]);
        $GLOBALS['BE_USER'] = $backendUser;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function embeddedDestinationsProvider(): array
    {
        return [
            'iframe' => ['iframe'],
            'frame' => ['frame'],
        ];
    }

    #[Test]
    #[DataProvider('embeddedDestinationsProvider')]
    public function responseIsUntouchedWhenRequestComesFromEmbeddedFrame(string $destination): void
    {
        $response = new Response();

        $result = $this->createSubject()->process(
            $this->createRequest($destination),
            $this->createHandler($response),
        );

        self::assertSame($response, $result);
    }

    #[Test]
    public function toolsAreResolvedForTopLevelDocumentRequests(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to render template');

        $this->createSubject()->process(
            $this->createRequest('document'),
            $this->createHandler(new Response()),
        );
    }

    #[Test]
    public function toolsAreResolvedWhenBrowserSendsNoFetchMetadata(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to render template');

        $this->createSubject()->process(
            $this->createRequest(null),
            $this->createHandler(new Response()),
        );
    }

    private function createSubject(): ToolRendererMiddleware
    {
        return new ToolRendererMiddleware(
            (new ReflectionClass(ResourceRendererService::class))->newInstanceWithoutConstructor(),
            (new ReflectionClass(SettingsService::class))->newInstanceWithoutConstructor(),
            (new ReflectionClass(FlashMessageService::class))->newInstanceWithoutConstructor(),
            new BackendUserService(),
        );
    }

    private function createRequest(?string $destination): ServerRequestInterface
    {
        $site = new Site('test', 1, [
            'base' => 'https://example.com/',
            'settings' => ['frontendEdit' => ['enabled' => true, 'enableFlashMessages' => false]],
        ]);
        $request = (new ServerRequest('https://example.com/', 'GET'))->withAttribute('site', $site);

        return null === $destination ? $request : $request->withHeader('Sec-Fetch-Dest', $destination);
    }

    private function createHandler(Response $response): RequestHandlerInterface
    {
        $response->getBody()->write('<html><body></body></html>');
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        return $handler;
    }
}
