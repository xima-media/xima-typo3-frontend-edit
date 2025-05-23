<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

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

    public function checkDefaultMenuStructure(string $identifier): bool
    {
        $configuration = $this->getConfiguration();

        if (!array_key_exists('defaultMenuStructure', $configuration)) {
            return true;
        }

        return array_key_exists($identifier, $configuration['defaultMenuStructure']) && $configuration['defaultMenuStructure'][$identifier];
    }

    public function checkSimpleModeMenuStructure(): bool
    {
        $configuration = $this->getConfiguration();

        if (!array_key_exists('defaultMenuStructure', $configuration)) {
            return true;
        }

        $menuStructure = $configuration['defaultMenuStructure'];
        foreach ($menuStructure as $key => $value) {
            if ($key === 'edit' && $value !== 1) {
                return false;
            }
            if ($key !== 'edit' && $value !== 0) {
                return false;
            }
        }

        return true;
    }

    private function getConfiguration(): array
    {
        if (!empty($this->configuration)) {
            return $this->configuration;
        }
        $request = $GLOBALS['TYPO3_REQUEST'];

        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '12.0.0', '<')) {
            $fullTypoScript = $GLOBALS['TSFE']->tmpl->setup;
        } else {
            $fullTypoScript = $request->getAttribute('frontend.typoscript')->getSetupArray();
        }

        $settings = $fullTypoScript['plugin.']['tx_ximatypo3frontendedit.']['settings.'] ?? [];
        $this->configuration = GeneralUtility::removeDotsFromTS($settings);

        return $this->configuration;
    }
}
