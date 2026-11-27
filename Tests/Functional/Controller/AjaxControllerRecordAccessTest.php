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
use TYPO3\CMS\Core\Http\{ServerRequest, Stream};
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Xima\XimaTypo3FrontendEdit\Controller\AjaxController;

/**
 * AjaxControllerRecordAccessTest.
 *
 * The record and element payloads come from client-supplied uids and tables, so
 * the response must follow the backend user's real permissions and never carry
 * whole database rows.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class AjaxControllerRecordAccessTest extends FunctionalTestCase
{
    private const ADMIN = 1;

    private const EDITOR = 2;

    private const ALLOWED_ELEMENT_KEYS = [
        'uid', 'pid', 'header', 'CType', 'colPos', 'tx_container_parent', 'l18n_parent', 'l10n_source',
        'ctypeLabel', 'ctypeIcon', '_table', '_ext', 'newBeforeUrl', 'newAfterUrl',
    ];

    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__.'/Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/pages_without_edit_permission.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/tt_content_without_edit_permission.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/fe_users.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/sys_template.csv');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);

        parent::tearDown();
    }

    #[Test]
    public function recordsOfAdminOnlyTablesAreOmittedForEditor(): void
    {
        $payload = $this->request(self::EDITOR, ['_records' => ['be_users:1']]);

        self::assertArrayNotHasKey('be_users:1', $payload['records']);
    }

    #[Test]
    public function recordsOfAdminOnlyTablesOnEditablePagesAreOmittedForEditor(): void
    {
        // The editor may modify the table and edit the page, only adminOnly keeps the record out.
        $payload = $this->request(self::EDITOR, ['_records' => ['sys_template:1']]);

        self::assertArrayNotHasKey('sys_template:1', $payload['records']);
    }

    #[Test]
    public function contentElementsOnPagesWithoutEditPermissionAreOmitted(): void
    {
        $payload = $this->request(self::EDITOR, ['_uids' => [10, 20]]);

        self::assertArrayHasKey(10, $payload['contentElements']);
        self::assertArrayNotHasKey(20, $payload['contentElements']);
    }

    #[Test]
    public function additionalDataLinkIsKeptForEditorWhoMayEditTheRecord(): void
    {
        // Like <xfe:data>, the entry carries no pid: access has to follow the stored record.
        $payload = $this->request(self::EDITOR, ['10' => [['label' => 'Visitor', 'table' => 'fe_users', 'uid' => 1, 'url' => null, 'icon' => null]]]);

        self::assertArrayHasKey('data_0', $payload['contentElements'][10]['menu']['children']);
    }

    #[Test]
    public function additionalDataLinkIgnoresPidSuppliedByTheClient(): void
    {
        $payload = $this->request(self::EDITOR, ['10' => [['label' => 'Locked', 'table' => 'fe_users', 'uid' => 2, 'pid' => 1, 'url' => null, 'icon' => null]]]);

        self::assertArrayNotHasKey('data_0', $payload['contentElements'][10]['menu']['children']);
    }

    #[Test]
    public function contentElementPayloadOnlyContainsAllowedFields(): void
    {
        $payload = $this->request(self::ADMIN, ['_uids' => [10, 20]]);

        self::assertNotEmpty($payload['contentElements']);
        foreach ($payload['contentElements'] as $contentElement) {
            self::assertSame([], array_diff(array_keys($contentElement['element']), self::ALLOWED_ELEMENT_KEYS));
        }
    }

    #[Test]
    public function recordPayloadOnlyContainsAllowedFields(): void
    {
        $payload = $this->request(self::ADMIN, ['_records' => ['be_users:2']]);

        self::assertArrayHasKey('be_users:2', $payload['records']);
        self::assertSame([], array_diff(array_keys($payload['records']['be_users:2']['element']), self::ALLOWED_ELEMENT_KEYS));
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function request(int $backendUserUid, array $body): array
    {
        $backendUser = $this->setUpBackendUser($backendUserUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $stream = new Stream('php://temp', 'rw');
        $stream->write((string) json_encode($body));
        $stream->rewind();
        $request = (new ServerRequest('https://example.com/', 'POST'))
            ->withQueryParams(['pid' => '1', 'returnUrl' => 'https://example.com/'])
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);

        $response = $this->get(AjaxController::class)->editInformationAction($request);
        $response->getBody()->rewind();

        return json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
