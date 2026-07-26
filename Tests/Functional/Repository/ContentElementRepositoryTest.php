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

namespace Xima\XimaTypo3FrontendEdit\Tests\Functional\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;

/**
 * ContentElementRepositoryTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ContentElementRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
    ];

    private ContentElementRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__.'/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__.'/Fixtures/tt_content.csv');

        $this->subject = $this->get(ContentElementRepository::class);
    }

    #[Test]
    public function fetchContentElementsReturnsVisibleDefaultLanguageAndAllLanguageRecords(): void
    {
        $result = $this->subject->fetchContentElements(2, 0);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);
        sort($uids);

        self::assertSame([1, 2, 6], $uids);
    }

    #[Test]
    public function fetchContentElementsExcludesHiddenDeletedAndForeignPageRecords(): void
    {
        $result = $this->subject->fetchContentElements(2, 0);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);

        self::assertNotContains(4, $uids);
        self::assertNotContains(5, $uids);
        self::assertNotContains(7, $uids);
    }

    #[Test]
    public function fetchContentElementsReturnsTranslatedRecordForGivenLanguage(): void
    {
        $result = $this->subject->fetchContentElements(2, 1);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);
        sort($uids);

        self::assertSame([3, 6], $uids);
    }

    #[Test]
    public function fetchContentElementsReturnsEmptyArrayForPageWithoutContent(): void
    {
        self::assertSame([], $this->subject->fetchContentElements(999, 0));
    }

    #[Test]
    public function fetchContentElementsByUidsReturnsEmptyArrayForEmptyInput(): void
    {
        self::assertSame([], $this->subject->fetchContentElementsByUids([], 0));
    }

    #[Test]
    public function fetchContentElementsByUidsReturnsMatchingRecordsAcrossPages(): void
    {
        $result = $this->subject->fetchContentElementsByUids([1, 7], 0);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);
        sort($uids);

        self::assertSame([1, 7], $uids);
    }

    #[Test]
    public function fetchContentElementsByUidsWithoutMultilingualContentReturnsOnlyGivenLanguage(): void
    {
        $result = $this->subject->fetchContentElementsByUids([1, 6], 0, false);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);

        self::assertSame([1], $uids);
    }

    #[Test]
    public function fetchContentElementsByUidsResolvesTranslationForConnectedModeAnchor(): void
    {
        // Connected mode: the DOM anchor carries the L0 uid (1); the request must
        // resolve to the translation record (uid 3) so the edit URL targets it.
        $result = $this->subject->fetchContentElementsByUids([1], 1);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);

        self::assertSame([3], $uids);
    }

    #[Test]
    public function fetchContentElementsByUidsReturnsL0FallbackWhenNoTranslationExists(): void
    {
        // Fallback-rendered L0 element on a translated page (uid 2 has no DE
        // translation) must still receive a record/menu.
        $result = $this->subject->fetchContentElementsByUids([2], 1);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);

        self::assertSame([2], $uids);
    }

    #[Test]
    public function fetchContentElementsByUidsPrefersTranslationOverDefaultWithoutDuplicates(): void
    {
        // uid 1 has a DE translation (3), uid 2 does not. Each anchor must yield
        // exactly one record: the translation for 1, the L0 fallback for 2.
        $result = $this->subject->fetchContentElementsByUids([1, 2], 1);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);
        sort($uids);

        self::assertSame([2, 3], $uids);
    }

    #[Test]
    public function fetchContentElementsByUidsResolvesChainedTranslationViaParent(): void
    {
        // uid 8 (FR) is translated from the DE record (l10n_source = 3) but its
        // l18n_parent still points to the L0 uid (1). Requesting the L0 anchor for
        // language 2 must resolve to the chained translation via l18n_parent.
        $result = $this->subject->fetchContentElementsByUids([1], 2);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);

        self::assertSame([8], $uids);
    }

    #[Test]
    public function fetchContentElementsByUidsReturnsAllLanguageRecordForTranslatedRequest(): void
    {
        $result = $this->subject->fetchContentElementsByUids([6], 1);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);

        self::assertSame([6], $uids);
    }

    #[Test]
    public function fetchContentElementsByUidsHandlesOnepagerMixedLanguagesAcrossPages(): void
    {
        // Mixed onepager: L0 anchor with translation (1 → 3) plus a foreign-page
        // L0 element without translation (7).
        $result = $this->subject->fetchContentElementsByUids([1, 7], 1);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);
        sort($uids);

        self::assertSame([3, 7], $uids);
    }

    #[Test]
    public function fetchContentElementsByUidsReturnsDefaultLanguageRecordUnchanged(): void
    {
        $result = $this->subject->fetchContentElementsByUids([1], 0);

        $uids = array_map(static fn (array $row): int => (int) $row['uid'], $result);

        self::assertSame([1], $uids);
    }

    #[Test]
    public function fetchContentElementsByUidsExcludesHiddenAndDeletedRecords(): void
    {
        self::assertSame([], $this->subject->fetchContentElementsByUids([4, 5], 0));
    }

    #[Test]
    public function getTranslatedRecordReturnsTranslationRow(): void
    {
        $record = $this->subject->getTranslatedRecord('pages', 2, 1);

        self::assertIsArray($record);
        self::assertSame(5, (int) $record['uid']);
    }

    #[Test]
    public function getTranslatedRecordReturnsFalseWhenNoTranslationExists(): void
    {
        self::assertFalse($this->subject->getTranslatedRecord('pages', 3, 1));
    }

    #[Test]
    public function getTranslatedRecordResolvesTtContentViaTransOrigPointerField(): void
    {
        $record = $this->subject->getTranslatedRecord('tt_content', 1, 1);

        self::assertIsArray($record);
        self::assertSame(3, (int) $record['uid']);
        self::assertSame('Translated DE', $record['header']);
    }

    #[Test]
    public function getTranslatedRecordReturnsFalseForUntranslatedTtContent(): void
    {
        self::assertFalse($this->subject->getTranslatedRecord('tt_content', 2, 1));
    }

    #[Test]
    public function getPageDoktypeReturnsDoktype(): void
    {
        self::assertSame(254, $this->subject->getPageDoktype(4));
    }

    #[Test]
    public function getPageDoktypeReturnsNullForMissingPage(): void
    {
        self::assertNull($this->subject->getPageDoktype(999));
    }

    #[Test]
    public function isSubpageOfReturnsTrueForAncestor(): void
    {
        self::assertTrue($this->subject->isSubpageOf(3, 1));
    }

    #[Test]
    public function isSubpageOfReturnsFalseForUnrelatedPage(): void
    {
        self::assertFalse($this->subject->isSubpageOf(2, 4));
    }

    #[Test]
    public function isSubpageOfUsesCacheOnRepeatedCalls(): void
    {
        self::assertTrue($this->subject->isSubpageOf(3, 1));
        self::assertTrue($this->subject->isSubpageOf(3, 1));
    }

    #[Test]
    public function isSubpageOfAnyReturnsTrueWhenOneMatches(): void
    {
        self::assertTrue($this->subject->isSubpageOfAny(3, [99, 1]));
    }

    #[Test]
    public function isSubpageOfAnyReturnsFalseWhenNoneMatch(): void
    {
        self::assertFalse($this->subject->isSubpageOfAny(3, [98, 99]));
    }

    #[Test]
    public function getContentElementConfigReturnsFalseForUnknownCType(): void
    {
        self::assertFalse($this->subject->getContentElementConfig('does_not_exist', ''));
    }

    #[Test]
    public function getContentElementConfigReturnsItemForKnownCType(): void
    {
        $config = $this->subject->getContentElementConfig('text', '');

        self::assertIsArray($config);
        self::assertSame('text', $config['value']);
    }

    #[Test]
    public function getContentElementConfigUsesCacheOnRepeatedCalls(): void
    {
        $first = $this->subject->getContentElementConfig('text', '');
        $second = $this->subject->getContentElementConfig('text', '');

        self::assertSame($first, $second);
    }
}
