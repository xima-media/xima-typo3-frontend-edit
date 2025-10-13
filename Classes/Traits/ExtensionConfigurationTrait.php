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

namespace Xima\XimaTypo3FrontendEdit\Traits;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use Xima\XimaTypo3FrontendEdit\Configuration;

use function array_key_exists;

/**
 * ExtensionConfigurationTrait.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
trait ExtensionConfigurationTrait
{
    protected array $extensionConfig = [];
    protected readonly ExtensionConfiguration $extensionConfiguration;

    protected function getExtensionConfiguration(): array
    {
        if ([] === $this->extensionConfig) {
            $this->extensionConfig = $this->extensionConfiguration->get(Configuration::EXT_KEY);
        }

        return $this->extensionConfig;
    }

    protected function isLinkTargetBlank(): bool
    {
        $config = $this->getExtensionConfiguration();

        return array_key_exists('linkTargetBlank', $config) && (bool) $config['linkTargetBlank'];
    }

    protected function shouldForceReturnUrlGeneration(): bool
    {
        $config = $this->getExtensionConfiguration();

        return array_key_exists('forceReturnUrlGeneration', $config) && (bool) $config['forceReturnUrlGeneration'];
    }

    protected function isSimpleMode(): bool
    {
        $config = $this->getExtensionConfiguration();

        return array_key_exists('simpleMode', $config) && (bool) $config['simpleMode'];
    }
}
