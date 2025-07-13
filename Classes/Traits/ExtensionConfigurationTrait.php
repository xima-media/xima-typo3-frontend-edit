<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Traits;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use Xima\XimaTypo3FrontendEdit\Configuration;

/**
* Trait for consistent extension configuration access across classes
*/
trait ExtensionConfigurationTrait
{
    protected array $extensionConfig = [];
    protected readonly ExtensionConfiguration $extensionConfiguration;

    protected function getExtensionConfiguration(): array
    {
        if ($this->extensionConfig === []) {
            $this->extensionConfig = $this->extensionConfiguration->get(Configuration::EXT_KEY);
        }

        return $this->extensionConfig;
    }

    protected function isLinkTargetBlank(): bool
    {
        $config = $this->getExtensionConfiguration();

        return array_key_exists('linkTargetBlank', $config) && (bool)$config['linkTargetBlank'];
    }

    protected function shouldForceReturnUrlGeneration(): bool
    {
        $config = $this->getExtensionConfiguration();

        return array_key_exists('forceReturnUrlGeneration', $config) && (bool)$config['forceReturnUrlGeneration'];
    }

    protected function isSimpleMode(): bool
    {
        $config = $this->getExtensionConfiguration();

        return array_key_exists('simpleMode', $config) && (bool)$config['simpleMode'];
    }
}
