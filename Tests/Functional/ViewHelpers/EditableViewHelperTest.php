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

namespace Xima\XimaTypo3FrontendEdit\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\View\{ViewFactoryData, ViewFactoryInterface};
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * EditableViewHelperTest.
 *
 * Renders through a real Fluid template, so the argument type validation that
 * the unit test bypasses via setArguments() takes part.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class EditableViewHelperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__.'/../Controller/Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);

        parent::tearDown();
    }

    #[Test]
    public function inlineNotationAcceptsAModelObjectAsRecord(): void
    {
        $news = new class {
            public function getUid(): int
            {
                return 7;
            }
        };

        self::assertSame(
            '<div data-frontend-edit="tx_news_domain_model_news:7"></div>',
            $this->render($news, 'tx_news_domain_model_news'),
        );
    }

    #[Test]
    public function inlineNotationAcceptsARecordArray(): void
    {
        self::assertSame(
            '<div data-frontend-edit="tt_content:10"></div>',
            $this->render(['uid' => 10], 'tt_content'),
        );
    }

    private function render(mixed $record, string $table): string
    {
        $view = $this->get(ViewFactoryInterface::class)->create(
            new ViewFactoryData(templateRootPaths: [__DIR__.'/Fixtures/']),
        );
        $view->assignMultiple(['record' => $record, 'table' => $table]);

        return trim($view->render('Editable'));
    }
}
