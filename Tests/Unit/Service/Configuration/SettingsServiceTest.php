<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Configuration;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use TYPO3\CMS\Core\Context\Context;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\{SettingsService, VersionCompatibilityService};

/**
 * SettingsServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
class SettingsServiceTest extends TestCase
{
    public function testSettingsServiceCanBeInstantiated(): void
    {
        $contextMock = $this->createMock(Context::class);
        $versionCompatibilityService = new VersionCompatibilityService();

        $subject = new SettingsService($contextMock, $versionCompatibilityService);

        self::assertInstanceOf(SettingsService::class, $subject);
    }

    public function testSettingsServiceHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(SettingsService::class);

        self::assertTrue($reflection->hasMethod('getIgnoredPids'));
        self::assertTrue($reflection->hasMethod('getIgnoredCTypes'));
        self::assertTrue($reflection->hasMethod('getIgnoredListTypes'));
        self::assertTrue($reflection->hasMethod('getIgnoredUids'));
        self::assertTrue($reflection->hasMethod('checkDefaultMenuStructure'));
        self::assertTrue($reflection->hasMethod('checkSimpleModeMenuStructure'));
        self::assertTrue($reflection->hasMethod('isFrontendDebugModeEnabled'));
    }

    public function testSettingsServiceMethodsArePublic(): void
    {
        $reflection = new ReflectionClass(SettingsService::class);

        self::assertTrue($reflection->getMethod('getIgnoredPids')->isPublic());
        self::assertTrue($reflection->getMethod('getIgnoredCTypes')->isPublic());
        self::assertTrue($reflection->getMethod('getIgnoredListTypes')->isPublic());
        self::assertTrue($reflection->getMethod('getIgnoredUids')->isPublic());
        self::assertTrue($reflection->getMethod('checkDefaultMenuStructure')->isPublic());
        self::assertTrue($reflection->getMethod('checkSimpleModeMenuStructure')->isPublic());
        self::assertTrue($reflection->getMethod('isFrontendDebugModeEnabled')->isPublic());
    }

    public function testSettingsServiceHasCorrectConstructorParameters(): void
    {
        $reflection = new ReflectionClass(SettingsService::class);
        $constructor = $reflection->getConstructor();

        self::assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        self::assertCount(2, $parameters);

        self::assertEquals('context', $parameters[0]->getName());
        self::assertEquals('versionCompatibilityService', $parameters[1]->getName());
    }

    public function testClassIsFinal(): void
    {
        $reflection = new ReflectionClass(SettingsService::class);
        self::assertTrue($reflection->isFinal());
    }

    public function testArrayMethodsReturnArrayType(): void
    {
        $reflection = new ReflectionClass(SettingsService::class);

        $methods = ['getIgnoredPids', 'getIgnoredCTypes', 'getIgnoredListTypes', 'getIgnoredUids'];

        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();

            self::assertNotNull($returnType, "Method {$methodName} should have return type");
            self::assertInstanceOf(ReflectionNamedType::class, $returnType, "Method {$methodName} should have named return type");
            self::assertEquals('array', $returnType->getName(), "Method {$methodName} should return array");
        }
    }

    public function testBooleanMethodsReturnBoolType(): void
    {
        $reflection = new ReflectionClass(SettingsService::class);

        $methods = ['checkDefaultMenuStructure', 'checkSimpleModeMenuStructure', 'isFrontendDebugModeEnabled'];

        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();

            self::assertNotNull($returnType, "Method {$methodName} should have return type");
            self::assertInstanceOf(ReflectionNamedType::class, $returnType, "Method {$methodName} should have named return type");
            self::assertEquals('bool', $returnType->getName(), "Method {$methodName} should return bool");
        }
    }

    public function testCheckDefaultMenuStructureHasStringParameter(): void
    {
        $reflection = new ReflectionClass(SettingsService::class);
        $method = $reflection->getMethod('checkDefaultMenuStructure');
        $parameters = $method->getParameters();

        self::assertCount(1, $parameters);

        $param = $parameters[0];
        $paramType = $param->getType();
        self::assertEquals('identifier', $param->getName());
        self::assertTrue($param->hasType());
        self::assertInstanceOf(ReflectionNamedType::class, $paramType);
        self::assertEquals('string', $paramType->getName());
    }

    public function testPrivatePropertiesExist(): void
    {
        $reflection = new ReflectionClass(SettingsService::class);

        $expectedProperties = ['configuration', 'ignoredPids', 'ignoredCTypes', 'ignoredListTypes', 'ignoredUids'];

        foreach ($expectedProperties as $propertyName) {
            self::assertTrue($reflection->hasProperty($propertyName), "Property {$propertyName} should exist");

            $property = $reflection->getProperty($propertyName);
            self::assertTrue($property->isPrivate(), "Property {$propertyName} should be private");
        }
    }
}
