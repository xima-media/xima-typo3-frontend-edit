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

use PHPUnit\Framework\Attributes\{DataProvider, Test};
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Xima\XimaTypo3FrontendEdit\Controller\AjaxController;

/**
 * AjaxControllerSiteSettingsTest.
 *
 * The edit information request is a backend AJAX route: its "site" attribute is
 * resolved from the "id" parameter, which the frontend does not send. The
 * request used here carries no site attribute, like the real one, so the site
 * settings only take effect when the controller resolves the site by page.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class AjaxControllerSiteSettingsTest extends FunctionalTestCase
{
    private const SITE_IDENTIFIER = 'main';

    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__.'/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/tt_content.csv');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);

        parent::tearDown();
    }

    #[Test]
    public function contentElementIsPresentWithoutFilterSettings(): void
    {
        self::assertArrayHasKey(10, $this->requestContentElements([]));
    }

    /**
     * @param array<string, string> $filter
     */
    #[Test]
    #[DataProvider('filterSettingsDataProvider')]
    public function filterSettingsTakeEffect(array $filter): void
    {
        self::assertSame([], $this->requestContentElements($filter));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function filterSettingsDataProvider(): iterable
    {
        yield 'ignored content type' => [['ignoreCTypes' => 'text']];
        yield 'ignored uid' => [['ignoreUids' => '10']];
        yield 'ignored page' => [['ignorePids' => '1']];
        yield 'ignored doktype' => [['ignoreDoktypes' => '1']];
    }

    /**
     * @param array<string, string> $filter
     *
     * @return array<int|string, mixed>
     */
    private function requestContentElements(array $filter): array
    {
        $siteWriter = $this->get(SiteWriter::class);
        $siteWriter->write(self::SITE_IDENTIFIER, [
            'rootPageId' => 1,
            'base' => 'http://localhost/',
            'dependencies' => ['xima/xima-typo3-frontend-edit'],
            'languages' => [[
                'languageId' => 0,
                'title' => 'English',
                'locale' => 'en_US.UTF-8',
                'base' => '/',
            ]],
        ]);
        $siteWriter->writeSettings(self::SITE_IDENTIFIER, ['frontendEdit' => ['filter' => $filter]]);
        $this->get(CacheManager::class)->flushCaches();

        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withQueryParams(['pid' => '1', 'returnUrl' => '/']);
        $response = $this->get(AjaxController::class)->editInformationAction($request);
        $response->getBody()->rewind();
        $payload = json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);

        return $payload['contentElements'];
    }
}
