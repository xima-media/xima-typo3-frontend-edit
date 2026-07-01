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
        unset($GLOBALS['BE_USER']);

        parent::tearDown();
    }

    #[Test]
    public function toggleActionReturns403WithoutBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $response = $this->subject->toggleAction();

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($this->decode($response)['success']);
    }

    #[Test]
    public function toggleActionEnablesDisabledStateOnFirstCall(): void
    {
        $this->setUpBackendUser(1);

        $response = $this->subject->toggleAction();

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

        $payload = $this->decode($this->subject->toggleAction());

        self::assertTrue($payload['success']);
        self::assertFalse($payload['disabled']);
    }

    #[Test]
    public function toggleActionPersistsStateToUserConfiguration(): void
    {
        $backendUser = $this->setUpBackendUser(1);

        $this->subject->toggleAction();

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
    }

    /**
     * @param array<string, string> $queryParams
     */
    private function createRequest(array $queryParams): ServerRequestInterface
    {
        return (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams($queryParams);
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
