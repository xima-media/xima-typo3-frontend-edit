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

use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use Xima\XimaTypo3FrontendEdit\Service\Menu\AbstractMenuGenerator;

/**
 * AbstractMenuGeneratorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(AbstractMenuGenerator::class)]
final class AbstractMenuGeneratorTest extends TestCase
{
    /**
     * @return array<string, array{config: array<string, mixed>, expected: bool}>
     */
    public static function linkTargetBlankDataProvider(): array
    {
        return [
            'string one enables' => ['config' => ['linkTargetBlank' => '1'], 'expected' => true],
            'string true enables' => ['config' => ['linkTargetBlank' => 'true'], 'expected' => true],
            'boolean true enables' => ['config' => ['linkTargetBlank' => true], 'expected' => true],
            'auto disables' => ['config' => ['linkTargetBlank' => 'auto'], 'expected' => false],
            'string zero disables' => ['config' => ['linkTargetBlank' => '0'], 'expected' => false],
            'missing key defaults to auto' => ['config' => [], 'expected' => false],
        ];
    }

    #[Test]
    #[DataProvider('linkTargetBlankDataProvider')]
    public function isLinkTargetBlankReturnsExpectedResult(array $config, bool $expected): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn($config);

        $generator = $this->createGenerator($extensionConfiguration);

        self::assertSame($expected, $generator->exposeIsLinkTargetBlank());
    }

    #[Test]
    public function isLinkTargetBlankReturnsFalseOnException(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willThrowException(new RuntimeException('missing'));

        $generator = $this->createGenerator($extensionConfiguration);

        self::assertFalse($generator->exposeIsLinkTargetBlank());
    }

    private function createGenerator(ExtensionConfiguration $extensionConfiguration): AbstractMenuGenerator
    {
        return new class($extensionConfiguration) extends AbstractMenuGenerator {
            public function exposeIsLinkTargetBlank(): bool
            {
                return $this->isLinkTargetBlank();
            }
        };
    }
}
