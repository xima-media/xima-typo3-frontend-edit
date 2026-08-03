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

namespace Xima\XimaTypo3FrontendEdit\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\{ServerRequest, Stream};
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Controller\AjaxController;

/**
 * AjaxControllerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class AjaxControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
    ];

    private AjaxController $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__.'/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/pages.csv');

        $this->subject = $this->get(AjaxController::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);

        parent::tearDown();
    }

    #[Test]
    public function toggleActionReturns403WithoutBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $response = $this->subject->toggleAction($this->createToggleRequest());

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($this->decode($response)['success']);
    }

    #[Test]
    public function toggleActionRejectsNonPostRequest(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->toggleAction($this->createToggleRequest(method: 'GET'));

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($this->decode($response)['success']);
    }

    #[Test]
    public function toggleActionRejectsCrossSiteRequest(): void
    {
        $this->setUpBackendUser(1);

        $request = $this->createToggleRequest()->withHeader('Sec-Fetch-Site', 'cross-site');
        $response = $this->subject->toggleAction($request);

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($this->decode($response)['success']);
    }

    #[Test]
    public function toggleActionEnablesDisabledStateOnFirstCall(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->toggleAction($this->createToggleRequest());

        $payload = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertTrue($payload['disabled']);
    }

    #[Test]
    public function toggleActionTogglesBackWhenAlreadyDisabled(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->uc[Configuration::UC_KEY_DISABLED] = true;

        $payload = $this->decode($this->subject->toggleAction($this->createToggleRequest()));

        self::assertTrue($payload['success']);
        self::assertFalse($payload['disabled']);
    }

    #[Test]
    public function toggleActionPersistsStateToUserConfiguration(): void
    {
        $backendUser = $this->setUpBackendUser(1);

        $this->subject->toggleAction($this->createToggleRequest());

        self::assertTrue((bool) $backendUser->uc[Configuration::UC_KEY_DISABLED]);
    }

    #[Test]
    public function editInformationActionReturnsEmptyArrayWithoutBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $response = $this->subject->editInformationAction($this->createRequest(['pid' => '1', 'returnUrl' => '/']));

        self::assertSame([], $this->decode($response));
    }

    #[Test]
    public function editInformationActionReturns400OnInvalidPid(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->editInformationAction($this->createRequest(['pid' => '0', 'returnUrl' => '/']));

        self::assertSame(400, $response->getStatusCode());
        self::assertArrayHasKey('error', $this->decode($response));
    }

    #[Test]
    public function editInformationActionReturns400OnMissingPid(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->editInformationAction($this->createRequest(['returnUrl' => '/']));

        self::assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function editInformationActionReturns400OnInvalidLanguage(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->editInformationAction($this->createRequest([
            'pid' => '1',
            'language' => '-5',
            'returnUrl' => '/',
        ]));

        self::assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function editInformationActionReturns400OnMissingReturnUrl(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->editInformationAction($this->createRequest(['pid' => '1']));

        self::assertSame(400, $response->getStatusCode());
        self::assertArrayHasKey('error', $this->decode($response));
    }

    #[Test]
    public function editInformationActionReturns400OnForeignReturnUrlHost(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->editInformationAction($this->createRequest([
            'pid' => '1',
            'returnUrl' => 'https://evil.example/',
        ]));

        self::assertSame(400, $response->getStatusCode());
        self::assertArrayHasKey('error', $this->decode($response));
    }

    #[Test]
    public function editInformationActionAcceptsSameOriginAbsoluteReturnUrl(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->editInformationAction($this->createRequest([
            'pid' => '1',
            'returnUrl' => 'https://example.com/return',
        ]));

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function editInformationActionReturns400OnInvalidJsonBody(): void
    {
        $this->setUpBackendUser(1);

        $request = $this->createRequest(['pid' => '1', 'returnUrl' => '/'])
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->stream('{invalid json'));

        $response = $this->subject->editInformationAction($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid request data', $this->decode($response)['error']);
    }

    #[Test]
    public function editInformationActionReturnsEmptyArrayWhenPageAccessDenied(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->editInformationAction($this->createRequest(['pid' => '999', 'returnUrl' => '/']));

        self::assertSame([], $this->decode($response));
    }

    #[Test]
    public function editInformationActionReturnsDropdownStructureOnValidRequest(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->editInformationAction($this->createRequest(['pid' => '1', 'returnUrl' => '/']));

        $payload = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertArrayHasKey('contentElements', $payload);
        self::assertArrayHasKey('columnTargets', $payload);
        self::assertArrayHasKey('records', $payload);
    }

    #[Test]
    public function editInformationActionBuildsAThinMenuForAForeignRecord(): void
    {
        $this->importCSVDataSet(__DIR__.'/Fixtures/sys_category.csv');
        $backendUser = $this->setUpBackendUser(1);
        // RecordButtonBuilder resolves the table label via $GLOBALS['LANG'];
        // in a real backend AJAX request the middleware stack provides it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $response = $this->subject->editInformationAction(
            $this->createRecordsRequest(['sys_category:1']),
        );

        $payload = $this->decode($response);

        self::assertArrayHasKey('sys_category:1', $payload['records']);
        $menu = $payload['records']['sys_category:1']['menu'];
        $children = $menu['children'];
        self::assertArrayHasKey('edit', $children);
        self::assertArrayHasKey('info', $children);
        self::assertArrayHasKey('history', $children);
        self::assertArrayNotHasKey('hide', $children);
        self::assertArrayNotHasKey('delete', $children);
        self::assertArrayNotHasKey('move', $children);
    }

    #[Test]
    public function editInformationActionResolvesTheTranslatedForeignRecord(): void
    {
        $this->importCSVDataSet(__DIR__.'/Fixtures/sys_category.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = $this->createRecordsRequest(['sys_category:1'])
            ->withQueryParams(['pid' => '1', 'returnUrl' => '/', 'language' => '1']);
        $response = $this->subject->editInformationAction($request);

        $payload = $this->decode($response);

        // Requested uid 1 (default language) is the response key, but the
        // resolved element data is the language-1 translation (uid 2) - same
        // "translated analogous to tt_content" contract as the anchor pattern.
        self::assertSame(2, $payload['records']['sys_category:1']['element']['uid']);
    }

    #[Test]
    public function editInformationActionOmitsUnknownTableFromRecords(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->editInformationAction(
            $this->createRecordsRequest(['tx_unknown_extension_table:1']),
        );

        $payload = $this->decode($response);

        self::assertSame([], $payload['records']);
    }

    #[Test]
    public function editInformationActionOmitsTtContentFromRecords(): void
    {
        $this->setUpBackendUser(1);

        // tt_content stays on the existing contentElements path - _records
        // must not become a second way to reach it.
        $response = $this->subject->editInformationAction(
            $this->createRecordsRequest(['tt_content:1']),
        );

        $payload = $this->decode($response);

        self::assertSame([], $payload['records']);
    }

    #[Test]
    public function editInformationActionOmitsHideButtonForRestrictedEditor(): void
    {
        $this->importCSVDataSet(__DIR__.'/Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/tt_content.csv');
        $backendUser = $this->setUpBackendUser(2);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $response = $this->subject->editInformationAction($this->createRequest(['pid' => '1', 'returnUrl' => '/']));

        $payload = $this->decode($response);
        $menuChildren = $payload['contentElements'][10]['menu']['children'] ?? [];

        self::assertArrayNotHasKey('hide', $menuChildren);
        self::assertArrayHasKey('delete', $menuChildren);
        $menuChildren = $payload['contentElements'][10]['menu']['children'] ?? [];

        self::assertArrayNotHasKey('hide', $menuChildren);
        self::assertArrayHasKey('delete', $menuChildren);
    }

    #[Test]
    public function moveActionRejectsNonPostRequest(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->moveAction($this->createMoveRequest(method: 'GET'));

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($this->decode($response)['success']);
    }

    #[Test]
    public function moveActionRejectsCrossSiteRequest(): void
    {
        $this->setUpBackendUser(1);

        $request = $this->createMoveRequest()->withHeader('Sec-Fetch-Site', 'cross-site');
        $response = $this->subject->moveAction($request);

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($this->decode($response)['success']);
    }

    #[Test]
    public function moveActionPassesOriginGuardOnSameOriginPostRequest(): void
    {
        $this->setUpBackendUser(1);

        // The record does not exist in the fixture, so the guard must be passed
        // and the request must fail later on the record lookup, not with a 403.
        $response = $this->subject->moveAction($this->createMoveRequest());

        self::assertNotSame(403, $response->getStatusCode());
    }

    #[Test]
    public function moveActionRejectsInvalidContainerUid(): void
    {
        $this->setUpBackendUser(1);

        $request = $this->createMoveRequest()->withBody($this->stream((string) json_encode([
            'uid' => 9999,
            'targetColPos' => 201,
            'targetContainerUid' => -5,
            'language' => 0,
        ])));

        $response = $this->subject->moveAction($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('targetContainerUid', (string) $this->decode($response)['error']);
    }

    /**
     * @param list<string> $records
     */
    private function createRecordsRequest(array $records): ServerRequestInterface
    {
        return (new ServerRequest('https://example.com/', 'POST'))
            ->withQueryParams(['pid' => '1', 'returnUrl' => '/'])
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->stream((string) json_encode(['_records' => $records])));
    }

    /**
     * @param array<string, string> $queryParams
     */
    private function createRequest(array $queryParams): ServerRequestInterface
    {
        return (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams($queryParams);
    }

    private function createMoveRequest(string $method = 'POST'): ServerRequestInterface
    {
        return (new ServerRequest('https://example.com/', $method))
            ->withHeader('Sec-Fetch-Site', 'same-origin')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->stream((string) json_encode([
                'uid' => 9999,
                'targetColPos' => 0,
                'targetUid' => 0,
                'language' => 0,
            ])));
    }

    private function createToggleRequest(string $method = 'POST'): ServerRequestInterface
    {
        return (new ServerRequest('https://example.com/', $method))
            ->withHeader('Sec-Fetch-Site', 'same-origin');
    }

    private function stream(string $content): Stream
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write($content);
        $stream->rewind();

        return $stream;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(\Psr\Http\Message\ResponseInterface $response): array
    {
        $response->getBody()->rewind();

        return json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
