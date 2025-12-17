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

namespace Xima\XimaTypo3FrontendEdit\Service\Menu;

use Throwable;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Traits\ExtensionConfigurationTrait;

use function array_key_exists;
use function in_array;

/**
 * AbstractMenuGenerator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
abstract class AbstractMenuGenerator
{
    use ExtensionConfigurationTrait;

    public function __construct(
        protected readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    protected function isShowContextMenu(): bool
    {
        try {
            $config = $this->extensionConfiguration->get(Configuration::EXT_KEY);

            return !array_key_exists('showContextMenu', $config) || (bool) $config['showContextMenu'];
        } catch (Throwable) {
            return true;
        }
    }

    protected function isLinkTargetBlank(): bool
    {
        try {
            $config = $this->extensionConfiguration->get(Configuration::EXT_KEY);
            $targetBlank = $config['linkTargetBlank'] ?? 'auto';

            return in_array($targetBlank, ['1', 'true', true], true);
        } catch (Throwable) {
            return false;
        }
    }
}
