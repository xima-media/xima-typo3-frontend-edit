<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "xima_typo3_frontend_edit".
 *
 * Copyright (C) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Xima\XimaTypo3FrontendEdit\Service\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class SettingsService
{
    private array $configuration = [];
    private array $ignoredPids = [];
    private array $ignoredCTypes = [];
    private array $ignoredListTypes = [];
    private array $ignoredUids = [];
    private ?bool $simpleModeMenuStructure = null;

    public function __construct(
        private readonly Context $context,
        private readonly VersionCompatibilityService $versionCompatibilityService
    ) {}

    public function getIgnoredPids(): array
    {
        if ($this->ignoredPids === []) {
            $configuration = $this->getConfiguration();
            $this->ignoredPids = isset($configuration['ignorePids'])
                ? array_map('trim', explode(',', $configuration['ignorePids']))
                : [];
        }

        return $this->ignoredPids;
    }

    public function getIgnoredCTypes(): array
    {
        if ($this->ignoredCTypes === []) {
            $configuration = $this->getConfiguration();
            $this->ignoredCTypes = isset($configuration['ignoreCTypes'])
                ? array_map('trim', explode(',', $configuration['ignoreCTypes']))
                : [];
        }

        return $this->ignoredCTypes;
    }

    public function getIgnoredListTypes(): array
    {
        if ($this->ignoredListTypes === []) {
            $configuration = $this->getConfiguration();
            $this->ignoredListTypes = isset($configuration['ignoreListTypes'])
                ? array_map('trim', explode(',', $configuration['ignoreListTypes']))
                : [];
        }

        return $this->ignoredListTypes;
    }

    public function getIgnoredUids(): array
    {
        if ($this->ignoredUids === []) {
            $configuration = $this->getConfiguration();
            $this->ignoredUids = isset($configuration['ignoredUids'])
                ? array_map('trim', explode(',', $configuration['ignoredUids']))
                : [];
        }

        return $this->ignoredUids;
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
        if ($this->simpleModeMenuStructure === null) {
            $configuration = $this->getConfiguration();
            $this->simpleModeMenuStructure = $this->calculateSimpleModeMenuStructure($configuration);
        }

        return $this->simpleModeMenuStructure;
    }

    private function calculateSimpleModeMenuStructure(array $configuration): bool
    {
        if (!isset($configuration['defaultMenuStructure'])) {
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

        $fullTypoScript = $this->getTypoScriptSetupArray();
        $settings = $fullTypoScript['plugin.']['tx_ximatypo3frontendedit.']['settings.'] ?? [];
        $this->configuration = GeneralUtility::removeDotsFromTS($settings);

        return $this->configuration;
    }

    private function getTypoScriptSetupArray(): array
    {
        if ($this->versionCompatibilityService->isVersionBelow12()) {
            return $this->getTypoScriptSetupArrayV11();
        }
        if ($this->versionCompatibilityService->isVersionBelow13()) {
            return $this->getTypoScriptSetupArrayV12($GLOBALS['TYPO3_REQUEST']);
        }

        return $this->getTypoScriptSetupArrayV13($GLOBALS['TYPO3_REQUEST']);
    }

    /**
    * These methods need to handle the case that the TypoScript setup array is not available within full cached setup.
    * Workaround from https://github.com/derhansen/fe_change_pwd to ensure that the TypoScript setup is available.
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
