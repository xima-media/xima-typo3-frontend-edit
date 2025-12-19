<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xima\XimaTypo3FrontendEdit\Configuration;

/**
 * ConfigurationTest.
 *
 * @covers \Xima\XimaTypo3FrontendEdit\Configuration
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ConfigurationTest extends TestCase
{
    #[Test]
    public function extensionKeyConstantHasCorrectValue(): void
    {
        self::assertSame('xima_typo3_frontend_edit', Configuration::EXT_KEY);
    }

    #[Test]
    public function extensionNameConstantHasCorrectValue(): void
    {
        self::assertSame('XimaTypo3FrontendEdit', Configuration::EXT_NAME);
    }

    #[Test]
    public function pluginNameConstantHasCorrectValue(): void
    {
        self::assertSame('FrontendEdit', Configuration::PLUGIN_NAME);
    }

    #[Test]
    public function userConfigurationKeyDisabledConstantHasCorrectValue(): void
    {
        self::assertSame('frontendEditDisabled', Configuration::UC_KEY_DISABLED);
    }

    #[Test]
    public function allConstantsAreNonEmpty(): void
    {
        self::assertNotEmpty(Configuration::EXT_KEY);
        self::assertNotEmpty(Configuration::EXT_NAME);
        self::assertNotEmpty(Configuration::PLUGIN_NAME);
        self::assertNotEmpty(Configuration::UC_KEY_DISABLED);
    }
}
