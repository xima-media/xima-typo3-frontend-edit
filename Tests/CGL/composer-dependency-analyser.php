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

use Composer\Autoload;
use ShipMonk\ComposerDependencyAnalyser;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$rootPath = dirname(__DIR__, 2);

/** @var Autoload\ClassLoader $loader */
$loader = require $rootPath.'/vendor/autoload.php';
$loader->register();

$configuration = new ComposerDependencyAnalyser\Config\Configuration();
$configuration
    ->addPathToScan($rootPath.'/Configuration', false)
    ->addPathsToExclude([
        $rootPath.'/Tests/CGL',
    ])
    // AccessCheckResult only exists in TYPO3 v14.2+. The unit test doubles reference
    // it behind method_exists() guards, so it is legitimately unavailable on v13.
    ->ignoreUnknownClasses([
        'TYPO3\CMS\Core\Authentication\AccessCheckResult',
    ])
    // b13/container is a deliberately optional integration: ContainerContextResolver
    // takes a nullable Registry (Configuration/Services.yaml wires it via the Symfony
    // "@?" optional service reference), so a site without EXT:container still boots and
    // simply rejects container-column moves. Moving it to "require" would force every
    // consumer to install EXT:container even when they never use it.
    ->ignoreErrorsOnPackage('b13/container', [ErrorType::DEV_DEPENDENCY_IN_PROD])
;

return $configuration;
