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

namespace Xima\XimaTypo3FrontendEdit\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\{HtmlResponse, ServerRequest};
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Xima\XimaTypo3FrontendEdit\Middleware\ToolRendererMiddleware;

/**
 * ToolRendererMiddlewareTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ToolRendererMiddlewareTest extends FunctionalTestCase
{
    public const HTML = '<html><body><h1>Page</h1></body></html>';
    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
    ];

    private ToolRendererMiddleware $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__.'/Fixtures/be_users.csv');

        $this->subject = $this->get(ToolRendererMiddleware::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);

        parent::tearDown();
    }

    #[Test]
    public function processReturnsUnchangedResponseWhenFeatureDisabled(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->process(
            $this->createRequest(enabled: false),
            $this->createHandler(),
        );

        self::assertSame(self::HTML, $this->readBody($response));
    }

    #[Test]
    public function processReturnsUnchangedResponseWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $response = $this->subject->process(
            $this->createRequest(enabled: true),
            $this->createHandler(),
        );

        self::assertSame(self::HTML, $this->readBody($response));
    }

    #[Test]
    public function processReturnsUnchangedResponseWhenDisabledViaUserTsConfig(): void
    {
        $this->setUpBackendUser(2);

        $response = $this->subject->process(
            $this->createRequest(enabled: true),
            $this->createHandler(),
        );

        self::assertSame(self::HTML, $this->readBody($response));
    }

    #[Test]
    public function processInjectsResourcesBeforeBodyClosingTag(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->process(
            $this->createRequest(enabled: true),
            $this->createHandler(),
        );

        $body = $this->readBody($response);

        self::assertStringContainsString('</body>', $body);
        self::assertStringContainsString('<h1>Page</h1>', $body);
        self::assertNotSame(self::HTML, $body);
        self::assertStringEndsWith('</body></html>', $body);
    }

    private function createRequest(bool $enabled): ServerRequestInterface
    {
        $site = new Site('test', 1, [
            'base' => 'https://example.com/',
            'settings' => [
                'frontendEdit' => [
                    'enabled' => $enabled,
                    'enableFlashMessages' => false,
                    'enableContextualEditing' => false,
                ],
            ],
        ]);

        return (new ServerRequest('https://example.com/', 'GET'))
            ->withAttribute('site', $site);
    }

    private function createHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse(ToolRendererMiddlewareTest::HTML);
            }
        };
    }

    private function readBody(ResponseInterface $response): string
    {
        $response->getBody()->rewind();

        return $response->getBody()->getContents();
    }
}
