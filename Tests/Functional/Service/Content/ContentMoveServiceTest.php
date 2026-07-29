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
    public function moveRejectsContainerChildWithoutTouchingTheRecord(): void
    {
        $before = $this->readRecord(4);

        // Reordering within its own container column — the case that silently
        // orphaned the record before the guard existed: EXT:container forces
        // tx_container_parent to 0 whenever a move command sets colPos without
        // also setting tx_container_parent.
        $result = $this->subject->move(4, 201, 5);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);

        $after = $this->readRecord(4);

        self::assertSame(3, (int) $after['tx_container_parent'], 'container binding must survive a rejected move');
        self::assertSame(201, (int) $after['colPos']);
        self::assertSame((int) $before['sorting'], (int) $after['sorting'], 'sorting must not change');
    }

    #[Test]
    public function moveRejectsTranslatedRecord(): void
    {
        $result = $this->subject->move(7, 0, 1);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);
    }

    #[Test]
    public function moveRejectsContainerChildAsDropTarget(): void
    {
        $before = $this->readRecord(1);

        // A plain element must not be dropped next to a container child, which
        // would place it in a container column without a container parent.
        $result = $this->subject->move(1, 201, 4);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['statusCode']);

        $after = $this->readRecord(1);

        self::assertSame(0, (int) $after['colPos'], 'rejected element must stay in its page column');
        self::assertSame((int) $before['sorting'], (int) $after['sorting']);
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
