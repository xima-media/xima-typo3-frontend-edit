<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

final class SettingsService
{
    protected array $configuration = [];

    public function getIgnoredPids(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignorePids', $configuration) ? explode(',', $configuration['ignorePids']) : [];
    }

    public function getIgnoredCTypes(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignoreCTypes', $configuration) ? explode(',', $configuration['ignoreCTypes']) : [];
    }

    public function getIgnoredListTypes(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignoreListTypes', $configuration) ? explode(',', $configuration['ignoreListTypes']) : [];
    }

    public function getIgnoredUids(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignoredUids', $configuration) ? explode(',', $configuration['ignoredUids']) : [];
    }

    private function getConfiguration(): array
    {
        if (!empty($this->configuration)) {
            return $this->configuration;
        }
        $request = $GLOBALS['TYPO3_REQUEST'];
        $fullTypoScript = $request->getAttribute('frontend.typoscript')->getSetupArray();
        $settings = $fullTypoScript['plugin.']['tx_ximatypo3frontendedit.']['settings.'] ?? [];
        $this->configuration = GeneralUtility::removeDotsFromTS($settings);

        return $this->configuration;
    }
}
