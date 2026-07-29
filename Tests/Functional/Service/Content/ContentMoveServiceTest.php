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

namespace Xima\XimaTypo3FrontendEdit\Tests\Functional\Service\Content;

use B13\Container\Tca\{ContainerConfiguration, Registry};
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Xima\XimaTypo3FrontendEdit\Service\Content\ContentMoveService;

use function sprintf;

/**
 * ContentMoveServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ContentMoveServiceTest extends FunctionalTestCase
{
    /**
     * EXT:container is loaded on purpose: without it tt_content has no
     * tx_container_parent column, so the container scope guard could not be
     * exercised against a real record at all.
     */
    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
        'b13/container',
    ];

    private ContentMoveService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeDragAndDropSiteConfiguration();
        $this->registerTestContainer();

        $this->importCSVDataSet(__DIR__.'/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/tt_content_container.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/be_users.csv');

        $backendUser = $this->setUpBackendUser(1);

        // DataHandler resolves its messages via $GLOBALS['LANG']; in a real backend
        // AJAX request the middleware stack provides it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)
            ->createFromUserPreferences($backendUser);

        $this->subject = $this->get(ContentMoveService::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['LANG']);

        parent::tearDown();
    }

    #[Test]
    public function moveReordersWithinContainerColumn(): void
    {
        $result = $this->subject->move(4, 201, 5, 3);

        self::assertTrue($result['success']);

        $moved = $this->readRecord(4);
        self::assertSame(201, (int) $moved['colPos']);
        self::assertSame(3, (int) $moved['tx_container_parent'], 'container binding must survive');
        self::assertGreaterThan((int) $this->readRecord(5)['sorting'], (int) $moved['sorting']);
    }

    #[Test]
    public function moveBetweenColumnsOfSameContainer(): void
    {
        $result = $this->subject->move(4, 202, 6, 3);

        self::assertTrue($result['success']);

        $moved = $this->readRecord(4);
        self::assertSame(202, (int) $moved['colPos']);
        self::assertSame(3, (int) $moved['tx_container_parent']);
    }

    #[Test]
    public function moveOutOfContainerIntoPageColumn(): void
    {
        $result = $this->subject->move(4, 0, 1, null);

        self::assertTrue($result['success']);

        $moved = $this->readRecord(4);
        self::assertSame(0, (int) $moved['colPos']);
        self::assertSame(0, (int) $moved['tx_container_parent'], 'must leave the container');
    }

    #[Test]
    public function moveFromPageColumnIntoEmptyContainerColumn(): void
    {
        // Column 202 of container 8 holds nothing, so there is no neighbour.
        $result = $this->subject->move(1, 202, null, 8);

        self::assertTrue($result['success']);

        $moved = $this->readRecord(1);
        self::assertSame(202, (int) $moved['colPos']);
        self::assertSame(8, (int) $moved['tx_container_parent']);
    }

    #[Test]
    public function moveBetweenDifferentContainers(): void
    {
        $result = $this->subject->move(4, 201, 9, 8);

        self::assertTrue($result['success']);

        $moved = $this->readRecord(4);
        self::assertSame(201, (int) $moved['colPos']);
        self::assertSame(8, (int) $moved['tx_container_parent']);
    }

    #[Test]
    public function moveRejectsContainerIntoContainer(): void
    {
        $result = $this->subject->move(8, 201, null, 3);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);
        self::assertSame(0, (int) $this->readRecord(8)['tx_container_parent']);
    }

    #[Test]
    public function moveRejectsDisallowedContentTypeInRestrictedColumn(): void
    {
        $before = $this->readRecord(1);

        $result = $this->subject->move(1, 203, null, 3);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);

        $after = $this->readRecord(1);
        self::assertSame(0, (int) $after['colPos'], 'must stay in its page column');
        self::assertSame((int) $before['sorting'], (int) $after['sorting']);
    }

    #[Test]
    public function moveRejectsContainerOnAnotherPage(): void
    {
        $result = $this->subject->move(1, 201, null, 11);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);
        self::assertSame(0, (int) $this->readRecord(1)['colPos']);
    }

    #[Test]
    public function moveRejectsContainerColumnWithoutContainerUid(): void
    {
        $before = $this->readRecord(1);

        // The orphan path: colPos 201 with no container would leave a container
        // column number without a container binding.
        $result = $this->subject->move(1, 201, null, null);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);

        $after = $this->readRecord(1);
        self::assertSame(0, (int) $after['colPos']);
        self::assertSame(0, (int) $after['tx_container_parent']);
    }

    #[Test]
    public function moveRejectsSoftDeletedContainerAsDropTarget(): void
    {
        // Container 12 exists (uid matches) but is soft-deleted; the resolver's
        // "deleted = 0" filter must reject it same as a missing container,
        // distinct from a plain uid mismatch.
        $result = $this->subject->move(1, 201, null, 12);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);
        self::assertSame(0, (int) $this->readRecord(1)['colPos']);
    }

    #[Test]
    public function moveRejectsTranslatedRecord(): void
    {
        $result = $this->subject->move(7, 0, 1);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);
    }

    #[Test]
    public function moveRejectsContainerChildAsDropTargetWithoutContainerUid(): void
    {
        $before = $this->readRecord(1);

        // Neighbour 4 lives in container column 201; without a containerUid the
        // resolver must refuse rather than orphan the element.
        $result = $this->subject->move(1, 201, 4, null);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);
        self::assertSame((int) $before['colPos'], (int) $this->readRecord(1)['colPos']);
    }

    #[Test]
    public function moveReordersPlainElementWithinPageColumn(): void
    {
        $result = $this->subject->move(1, 0, 2);

        self::assertTrue($result['success']);
        self::assertSame(200, $result['statusCode']);

        $moved = $this->readRecord(1);

        self::assertSame(0, (int) $moved['colPos']);
        self::assertSame(0, (int) $moved['tx_container_parent'], 'a plain element must stay outside any container');
        self::assertGreaterThan(
            (int) $this->readRecord(2)['sorting'],
            (int) $moved['sorting'],
            'element must now sort after its drop neighbour',
        );
    }

    /**
     * Registers a container CType via the public b13/container API. The fixture
     * cannot use bootstrap-package's container_2_columns: that package is only
     * installed in the ddev demo, not in require-dev.
     */
    private function registerTestContainer(): void
    {
        $configuration = new ContainerConfiguration(
            'test_container',
            'Test container',
            'Two columns plus a restricted one',
            [
                [
                    ['name' => 'Left', 'colPos' => 201],
                    ['name' => 'Right', 'colPos' => 202],
                    ['name' => 'Images only', 'colPos' => 203, 'allowedContentTypes' => 'image'],
                ],
            ],
        );

        $this->get(Registry::class)->configureContainer($configuration);
    }

    /**
     * @return array<string, mixed>
     */
    private function readRecord(int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $record = $queryBuilder
            ->select('uid', 'colPos', 'tx_container_parent', 'sorting')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $uid))
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($record, sprintf('record %d must exist', $uid));

        return $record;
    }

    private function writeDragAndDropSiteConfiguration(): void
    {
        $path = $this->instancePath.'/typo3conf/sites/testing';
        GeneralUtility::mkdir_deep($path);

        file_put_contents($path.'/config.yaml', <<<'YAML'
            rootPageId: 1
            base: 'https://example.com/'
            languages:
              - title: 'English'
                enabled: true
                languageId: 0
                base: '/'
                locale: 'en_US.UTF-8'
                flag: 'gb'
            settings:
              frontendEdit:
                enableDragAndDrop: true
            YAML);
    }
}
