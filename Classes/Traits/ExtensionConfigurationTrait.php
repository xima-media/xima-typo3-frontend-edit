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
use function in_array;

/**
 * ExtensionConfigurationTrait.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
trait ExtensionConfigurationTrait
{
    /** @var array<string, mixed> */
    protected array $extensionConfig = [];
    protected readonly ExtensionConfiguration $extensionConfiguration;

    /**
     * @return array<string, mixed>
     */
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

    protected function isShowContextMenu(): bool
    {
        $config = $this->getExtensionConfiguration();

        return !array_key_exists('showContextMenu', $config) || (bool) $config['showContextMenu'];
    }

    protected function getColorScheme(): string
    {
        $config = $this->getExtensionConfiguration();
        $scheme = $config['colorScheme'] ?? 'auto';

        return in_array($scheme, ['auto', 'light', 'dark'], true) ? $scheme : 'auto';
    }
}
