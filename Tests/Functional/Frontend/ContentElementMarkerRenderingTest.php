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

namespace Xima\XimaTypo3FrontendEdit\Tests\Functional\Frontend;

use PHPUnit\Framework\Attributes\{DataProvider, Test};
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\{InternalRequest, InternalRequestContext};
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * ContentElementMarkerRenderingTest.
 *
 * Renders real frontend requests through the Site Set, so the TypoScript
 * condition, the page cache and ContentElementMarkerEventListener are exercised
 * together. The unit test of the listener cannot see any of that.
 *
 * The fixture pages render without HTML skeleton, so ToolRendererMiddleware
 * finds no closing body tag and leaves the response alone. It needs a real
 * backend session, which the testing-framework backend user does not have.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ContentElementMarkerRenderingTest extends FunctionalTestCase
{
    private const SITE_IDENTIFIER = 'main';

    private const BACKEND_USER_ID = 1;

    private const MARKER_PREFIX = '<!--xfe:';

    private const MARKED_CACHED_ELEMENT = '<!--xfe:b:tt_content:10--><div class="site-wrap">cached-output</div><!--xfe:e:tt_content:10-->';

    private const MARKED_UNCACHED_ELEMENT = '<!--xfe:b:tt_content:11--><div class="site-wrap">uncached-output</div><!--xfe:e:tt_content:11-->';

    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
    ];

    /**
     * The testing framework replaces the page cache with a NullBackend. The cache
     * tests below would then pass without a single cache hit.
     */
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__.'/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/be_users.csv');
    }

    /**
     * The fixture sets renderObj.stdWrap.dataWrap on its tt_content reference, as
     * bk2k/bootstrap-package does. Both the site's wrap and the markers must
     * survive, with the markers outside, since they run after all stdWrap
     * functions.
     */
    #[Test]
    public function markersArePresentForBackendUserWithSettingEnabled(): void
    {
        $this->configureSite(enabled: true, markerBasedDetection: true);

        $html = $this->render(asBackendUser: true);

        self::assertStringContainsString(self::MARKED_CACHED_ELEMENT, $html);
    }

    #[Test]
    #[DataProvider('renderingWithoutMarkersDataProvider')]
    public function markersAreAbsent(bool $asBackendUser, bool $enabled, bool $markerBasedDetection, int $pageType): void
    {
        $this->configureSite(enabled: $enabled, markerBasedDetection: $markerBasedDetection);

        $html = $this->render(asBackendUser: $asBackendUser, pageType: $pageType);

        self::assertStringContainsString('cached-output', $html);
        self::assertStringNotContainsString(self::MARKER_PREFIX, $html);
    }

    /**
     * @return iterable<string, array{bool, bool, bool, int}>
     */
    public static function renderingWithoutMarkersDataProvider(): iterable
    {
        yield 'anonymous visitor' => [false, true, true, 0];
        yield 'backend user, setting disabled' => [true, true, false, 0];
        yield 'backend user, frontend edit disabled' => [true, false, true, 0];
        yield 'backend user, other page type' => [true, true, true, 99];
    }

    /**
     * The markers wrap the USER_INT placeholder, so the substituted uncached
     * output must land between them, on the first rendering and when the page
     * comes from the cache.
     */
    #[Test]
    public function uncachedElementIsWrappedInMarkersOnCachedAndUncachedRendering(): void
    {
        $this->configureSite(enabled: true, markerBasedDetection: true);

        $firstHtml = $this->render(asBackendUser: true);
        $cachedHtml = $this->render(asBackendUser: true);

        self::assertSame(1, $this->countPageCacheEntries());
        self::assertStringContainsString(self::MARKED_UNCACHED_ELEMENT, $firstHtml);
        self::assertStringContainsString(self::MARKED_UNCACHED_ELEMENT, $cachedHtml);
    }

    /**
     * Alternating requests would serve one variant's cached output to the other
     * if the condition verdict did not vary the page cache identifier.
     */
    #[Test]
    public function anonymousAndBackendUserVariantsNeverLeakIntoEachOther(): void
    {
        $this->configureSite(enabled: true, markerBasedDetection: true);

        $anonymousHtml = $this->render(asBackendUser: false);
        $backendUserHtml = $this->render(asBackendUser: true);
        $cachedAnonymousHtml = $this->render(asBackendUser: false);
        $cachedBackendUserHtml = $this->render(asBackendUser: true);

        self::assertSame(2, $this->countPageCacheEntries());
        self::assertStringNotContainsString(self::MARKER_PREFIX, $anonymousHtml);
        self::assertStringNotContainsString(self::MARKER_PREFIX, $cachedAnonymousHtml);
        self::assertStringContainsString(self::MARKED_CACHED_ELEMENT, $backendUserHtml);
        self::assertStringContainsString(self::MARKED_CACHED_ELEMENT, $cachedBackendUserHtml);
    }

    /**
     * With the setting off the condition is false for everyone, so enabling the
     * extension must not double the page cache for editors.
     */
    #[Test]
    public function backendUserSharesTheAnonymousCacheEntryWhenSettingIsDisabled(): void
    {
        $this->configureSite(enabled: true, markerBasedDetection: false);

        $this->render(asBackendUser: false);
        $this->render(asBackendUser: true);

        self::assertSame(1, $this->countPageCacheEntries());
    }

    /**
     * Extbase backend modules (log, extension list) evaluate the frontend
     * TypoScript of the site, conditions included, against a backend request.
     * That request carries no PageArguments, and a failing condition is
     * rethrown by the Core instead of being treated as false.
     */
    #[Test]
    public function conditionEvaluatesToFalseForBackendModuleRequest(): void
    {
        $this->configureSite(enabled: true, markerBasedDetection: true);
        $this->setUpBackendUser(self::BACKEND_USER_ID);

        $request = (new ServerRequest('http://localhost/typo3/module/system/log'))
            ->withQueryParams(['id' => 1])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationManager->setRequest($request);

        $setup = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        self::assertArrayNotHasKey('xfeMarkers', $setup['tt_content.']['stdWrap.'] ?? []);
    }

    private function configureSite(bool $enabled, bool $markerBasedDetection): void
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
        $siteWriter->writeSettings(self::SITE_IDENTIFIER, [
            'frontendEdit' => [
                'enabled' => $enabled,
                'markerBasedDetection' => $markerBasedDetection,
            ],
        ]);
        copy(
            __DIR__.'/Fixtures/setup.typoscript',
            $this->instancePath.'/typo3conf/sites/'.self::SITE_IDENTIFIER.'/setup.typoscript',
        );

        // Site configuration and the TypoScript include tree of the site are
        // cached, and survive from test to test within this class otherwise.
        $this->get(CacheManager::class)->flushCaches();
    }

    private function render(bool $asBackendUser, int $pageType = 0): string
    {
        $request = (new InternalRequest('http://localhost/'))->withPageId(1);
        if (0 !== $pageType) {
            $request = $request->withQueryParameter('type', $pageType);
        }

        $context = new InternalRequestContext();
        if ($asBackendUser) {
            // Without an explicit workspace the testing-framework backend user ends
            // up in an offline workspace, which forces preview mode and disables
            // the page cache. A real editor in the live workspace gets cached pages.
            $context = $context->withBackendUserId(self::BACKEND_USER_ID)->withWorkspaceId(0);
        }

        $response = $this->executeFrontendSubRequest($request, $context);
        self::assertSame(200, $response->getStatusCode());

        return (string) $response->getBody();
    }

    private function countPageCacheEntries(): int
    {
        return $this->getConnectionPool()->getConnectionForTable('cache_pages')->count('*', 'cache_pages', []);
    }
}
