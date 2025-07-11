<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
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

        if (!is_array($menuStructure) || $menuStructure === []) {
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
        if ($this->configuration !== []) {
            return $this->configuration;
        }

        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '12.0.0', '<')) {
            $fullTypoScript = $this->getTypoScriptSetupArrayV11();
        } elseif (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '13.0.0', '<')) {
            $fullTypoScript = $this->getTypoScriptSetupArrayV12($GLOBALS['TYPO3_REQUEST']);
        } else {
            $fullTypoScript = $this->getTypoScriptSetupArrayV13($GLOBALS['TYPO3_REQUEST']);
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
        if ($GLOBALS['TSFE']->tmpl === null || ($GLOBALS['TSFE']->tmpl && $GLOBALS['TSFE']->tmpl->setup === [])) {
            /* @phpstan-ignore-next-line */
            $this->context->setAspect('typoscript', GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\TypoScriptAspect::class, true));
            $GLOBALS['TSFE']->getConfigArray();
        }
        return $GLOBALS['TSFE']->tmpl->setup;
    }

    private function getTypoScriptSetupArrayV12(ServerRequestInterface $request): array
    {
        try {
            $fullTypoScript = $request->getAttribute('frontend.typoscript')->getSetupArray();
        } catch (\Exception) {
            // An exception is thrown, when TypoScript setup array is not available. This is usually the case,
            // when the current page request is cached. Therefore, the TSFE TypoScript parsing is forced here.

            // ToDo: This workaround is not working for TYPO3 v13
            // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Breaking-102583-RemovedContextAspectTyposcript.html#breaking-102583-1701510037
            if (!class_exists(\TYPO3\CMS\Core\Context\TypoScriptAspect::class)) {
                return [];
            }

            // Set a TypoScriptAspect which forces template parsing
            /* @phpstan-ignore-next-line */
            $this->context->setAspect('typoscript', GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\TypoScriptAspect::class, true));
            $tsfe = $request->getAttribute('frontend.controller');
            $requestWithFullTypoScript = $tsfe->getFromCache($request); // @phpstan-ignore-line

            // Call TSFE getFromCache, which re-processes TypoScript respecting $forcedTemplateParsing property
            // from TypoScriptAspect
            $fullTypoScript = $requestWithFullTypoScript->getAttribute('frontend.typoscript')->getSetupArray();
        }
        return $fullTypoScript;
    }

    /**
    * @param ServerRequestInterface $request
    * @return array
    */
    private function getTypoScriptSetupArrayV13(ServerRequestInterface $request): array
    {
        try {
            return $request->getAttribute('frontend.typoscript')->getSetupArray();
        } catch (\Exception) {
            return [];
        }
    }
}
