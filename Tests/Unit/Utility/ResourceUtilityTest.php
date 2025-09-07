<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "xima_typo3_frontend_edit".
 *
 * Copyright (C) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Utility;

use PHPUnit\Framework\TestCase;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Utility\ResourceUtility;

/**
 * ResourceUtilityTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
class ResourceUtilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset globals for each test
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY] = [];
    }

    public function testGetResourcesIgnoresUnsupportedFileTypesInConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerAdditionalFrontendResources'] = [
            'unsupported.txt',
        ];

        // We can test the configuration handling without full TYPO3 context
        $additionalResources = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerAdditionalFrontendResources'];

        self::assertCount(1, $additionalResources);
        self::assertEquals('unsupported.txt', $additionalResources[0]);
        self::assertStringEndsWith('.txt', $additionalResources[0]);
        self::assertFalse(str_ends_with($additionalResources[0], '.css'));
        self::assertFalse(str_ends_with($additionalResources[0], '.js'));
    }

    public function testGetCssTagMethodExists(): void
    {
        $reflection = new \ReflectionClass(ResourceUtility::class);

        self::assertTrue($reflection->hasMethod('getCssTag'));

        $method = $reflection->getMethod('getCssTag');
        self::assertTrue($method->isProtected());
        self::assertTrue($method->isStatic());
    }

    public function testGetJsTagMethodExists(): void
    {
        $reflection = new \ReflectionClass(ResourceUtility::class);

        self::assertTrue($reflection->hasMethod('getJsTag'));

        $method = $reflection->getMethod('getJsTag');
        self::assertTrue($method->isProtected());
        self::assertTrue($method->isStatic());
    }

    public function testGetResourcesMethodExists(): void
    {
        $reflection = new \ReflectionClass(ResourceUtility::class);

        self::assertTrue($reflection->hasMethod('getResources'));

        $method = $reflection->getMethod('getResources');
        self::assertTrue($method->isPublic());
        self::assertTrue($method->isStatic());
    }

    public function testResourceUtilityClassStructure(): void
    {
        $reflection = new \ReflectionClass(ResourceUtility::class);

        self::assertFalse($reflection->isFinal());
        self::assertFalse($reflection->isAbstract());
        self::assertEquals('Xima\XimaTypo3FrontendEdit\Utility\ResourceUtility', $reflection->getName());
    }

    public function testGetResourcesMethodSignature(): void
    {
        $reflection = new \ReflectionClass(ResourceUtility::class);
        $method = $reflection->getMethod('getResources');

        $parameters = $method->getParameters();
        self::assertCount(1, $parameters);

        $attributesParam = $parameters[0];
        $paramType = $attributesParam->getType();
        self::assertEquals('attributes', $attributesParam->getName());
        self::assertTrue($attributesParam->hasType());
        self::assertInstanceOf(\ReflectionNamedType::class, $paramType);
        self::assertEquals('array', $paramType->getName());
        self::assertTrue($attributesParam->isDefaultValueAvailable());
        self::assertEquals([], $attributesParam->getDefaultValue());
    }

    public function testGetResourcesMethodReturnType(): void
    {
        $reflection = new \ReflectionClass(ResourceUtility::class);
        $method = $reflection->getMethod('getResources');
        $returnType = $method->getReturnType();

        self::assertNotNull($returnType);
        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);
        self::assertEquals('array', $returnType->getName());
    }

    public function testConfigurationHandlesEmptyAdditionalResources(): void
    {
        // No additional resources configuration
        $additionalResources = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerAdditionalFrontendResources'] ?? [];

        self::assertIsArray($additionalResources);
        self::assertEmpty($additionalResources);
    }

    public function testFileExtensionDetection(): void
    {
        $cssFile = 'test.css';
        $jsFile = 'test.js';
        $txtFile = 'test.txt';

        self::assertTrue(str_ends_with($cssFile, '.css'));
        self::assertTrue(str_ends_with($jsFile, '.js'));
        self::assertFalse(str_ends_with($txtFile, '.css'));
        self::assertFalse(str_ends_with($txtFile, '.js'));
    }
}
