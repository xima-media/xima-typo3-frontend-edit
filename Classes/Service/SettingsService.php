<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

final class SettingsService
{
    protected array $configuration = [];
    public function __construct(private Context $context)
    {
    }

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
            return false;
        }

        $menuStructure = $configuration['defaultMenuStructure'];

        if (!is_array($menuStructure) || empty($menuStructure)) {
            return false;
        }

        foreach ($menuStructure as $key => $value) {
            if ($key === 'edit' && (int)$value !== 1) {
                return false;
            }
            if ($key !== 'edit' && (int)$value !== 0) {
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

        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '12.0.0', '<')) {
            $fullTypoScript = $this->getTypoScriptSetupArrayV11();
        } else {
            $fullTypoScript = $this->getTypoScriptSetupArrayV12($GLOBALS['TYPO3_REQUEST']);
        }

        $settings = $fullTypoScript['plugin.']['tx_ximatypo3frontendedit.']['settings.'] ?? [];
        $this->configuration = GeneralUtility::removeDotsFromTS($settings);

        return $this->configuration;
    }

    /**
    * These methods need to handle the case that the TypoScript setup array is not available within full cached setup.
    * I used this workaround from https://github.com/derhansen/fe_change_pwd to ensure that the TypoScript setup is available.
    *
    * @return array
    */
    private function getTypoScriptSetupArrayV11(): array
    {
        // Ensure, TSFE setup is loaded for cached pages
        if ($GLOBALS['TSFE']->tmpl === null || ($GLOBALS['TSFE']->tmpl && empty($GLOBALS['TSFE']->tmpl->setup))) {
            $this->context
                ->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));
            $GLOBALS['TSFE']->getConfigArray();
        }
        return $GLOBALS['TSFE']->tmpl->setup;
    }

    private function getTypoScriptSetupArrayV12(ServerRequestInterface $request): array
    {
        try {
            $fullTypoScript = $request->getAttribute('frontend.typoscript')->getSetupArray();
        } catch (\Exception $e) {
            // An exception is thrown, when TypoScript setup array is not available. This is usually the case,
            // when the current page request is cached. Therefore, the TSFE TypoScript parsing is forced here.

            // Set a TypoScriptAspect which forces template parsing
            $this->context
                ->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));
            $tsfe = $request->getAttribute('frontend.controller');
            $requestWithFullTypoScript = $tsfe->getFromCache($request);

            // Call TSFE getFromCache, which re-processes TypoScript respecting $forcedTemplateParsing property
            // from TypoScriptAspect
            $fullTypoScript = $requestWithFullTypoScript->getAttribute('frontend.typoscript')->getSetupArray();
        }
        return $fullTypoScript;
    }
}
