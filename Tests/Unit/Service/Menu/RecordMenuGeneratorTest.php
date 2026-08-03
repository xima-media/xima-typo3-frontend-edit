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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Menu;

use KonradMichalik\Ttt\Attribute\WithSingleton;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Menu\{RecordButtonBuilder, RecordMenuGenerator};
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures\FakeUriBuilder;

/**
 * RecordMenuGeneratorTest.
 *
 * The full happy-path (real record fetch + translation resolution) requires
 * a database and is covered by
 * Tests/Functional/Controller/AjaxControllerTest.php instead - this suite
 * only covers what's reachable without one: BackendUtility::getRecord() is
 * a static core call this class does not abstract away (same as
 * AdditionalDataHandler), so unit tests are limited to the guard that runs
 * before it.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(RecordMenuGenerator::class)]
#[WithSingleton(UriBuilder::class, new FakeUriBuilder())]
final class RecordMenuGeneratorTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['TCA']);
    }

    #[Test]
    public function getDropdownReturnsEmptyArrayForEmptyReferences(): void
    {
        $generator = $this->createGenerator();

        self::assertSame([], $generator->getDropdown([], 0, '/return'));
    }

    #[Test]
    public function getDropdownSkipsReferencesForTablesNotInTca(): void
    {
        $GLOBALS['TCA'] = [];

        $generator = $this->createGenerator();

        $result = $generator->getDropdown([['table' => 'tx_news_domain_model_news', 'uid' => 42]], 0, '/return');

        self::assertSame([], $result);
    }

    /**
     * BackendUserService and ContentElementRepository are both `final` and
     * cannot be doubled - neither is reached by the two guards under test
     * here (empty references / unknown TCA table), so real instances (with
     * a mocked ConnectionPool where one is required) are sufficient.
     */
    private function createGenerator(): RecordMenuGenerator
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIcon')->willReturn($this->createMock(Icon::class));
        $iconService = new IconService($iconFactoryMock);
        $urlBuilderService = new UrlBuilderService();
        $recordButtonBuilder = new RecordButtonBuilder($iconService, $urlBuilderService);

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        return new RecordMenuGenerator(
            $recordButtonBuilder,
            $eventDispatcher,
            new BackendUserService(),
            new ContentElementRepository($this->createMock(ConnectionPool::class)),
            $urlBuilderService,
            $iconService,
            $this->createMock(ExtensionConfiguration::class),
        );
    }
}
