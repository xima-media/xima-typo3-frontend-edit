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

namespace Xima\XimaTypo3FrontendEdit\Service\Configuration;

use ArrayObject;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function array_slice;
use function is_array;

/**
 * SettingsService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class SettingsService
{
    private const MAX_CACHE_SIZE = 10;
    private const CACHE_CLEANUP_THRESHOLD = 8;

    /**
     * @var array<string, mixed>
     */
    private array $configuration = [];

    /**
     * @var array<int, string>
     */
    private array $ignoredPids = [];

    /**
     * @var array<int, string>
     */
    private array $ignoredCTypes = [];

    /**
     * @var array<int, string>
     */
    private array $ignoredListTypes = [];

    /**
     * @var array<int, int>
     */
    private array $ignoredUids = [];

    private ?bool $simpleModeMenuStructure = null;

    /**
     * @var ArrayObject<string, array<string, mixed>>
     */
    private readonly ArrayObject $typoScriptCache;

    public function __construct(
        private readonly Context $context,
    ) {
        $this->typoScriptCache = new ArrayObject();
    }

    /**
     * @return array<int, string>
     */
    public function getIgnoredPids(): array
    {
        if ([] === $this->ignoredPids) {
            $this->ignoredPids = $this->parseCommaDelimitedConfig('ignorePids');
        }

        return $this->ignoredPids;
    }

    /**
     * @return array<int, string>
     */
    public function getIgnoredCTypes(): array
    {
        if ([] === $this->ignoredCTypes) {
            $this->ignoredCTypes = $this->parseCommaDelimitedConfig('ignoreCTypes');
        }

        return $this->ignoredCTypes;
    }

    /**
     * @return array<int, string>
     */
    public function getIgnoredListTypes(): array
    {
        if ([] === $this->ignoredListTypes) {
            $this->ignoredListTypes = $this->parseCommaDelimitedConfig('ignoreListTypes');
        }

        return $this->ignoredListTypes;
    }

    /**
     * @return array<int, int>
     */
    public function getIgnoredUids(): array
    {
        if ([] === $this->ignoredUids) {
            $values = $this->parseCommaDelimitedConfig('ignoredUids');
            $this->ignoredUids = array_map(intval(...), $values);
        }

        return $this->ignoredUids;
    }

    public function checkDefaultMenuStructure(string $identifier): bool
    {
        $configuration = $this->getConfiguration();

        if (!array_key_exists('defaultMenuStructure', $configuration)) {
            return true;
        }

        return array_key_exists($identifier, $configuration['defaultMenuStructure']) && (bool) $configuration['defaultMenuStructure'][$identifier];
    }

    public function checkSimpleModeMenuStructure(): bool
    {
        if (null === $this->simpleModeMenuStructure) {
            $configuration = $this->getConfiguration();
            $this->simpleModeMenuStructure = $this->calculateSimpleModeMenuStructure($configuration);
        }

        return $this->simpleModeMenuStructure;
    }

    public function isFrontendDebugModeEnabled(): bool
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        try {
            $configuration = $extensionConfiguration->get('xima_typo3_frontend_edit');

            return (bool) ($configuration['frontendDebugMode'] ?? false);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function calculateSimpleModeMenuStructure(array $configuration): bool
    {
        if (!isset($configuration['defaultMenuStructure'])) {
            return false;
        }

        $menuStructure = $configuration['defaultMenuStructure'];

        if (!is_array($menuStructure) || [] === $menuStructure) {
            return false;
        }

        foreach ($menuStructure as $key => $value) {
            if ('edit' === $key && 1 !== (int) $value) {
                return false;
            }
            if ('edit' !== $key && 0 !== (int) $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfiguration(): array
    {
        if ([] !== $this->configuration) {
            return $this->configuration;
        }

        $fullTypoScript = $this->getTypoScriptSetupArray();
        $settings = $fullTypoScript['plugin.']['tx_ximatypo3frontendedit.']['settings.'] ?? [];
        $this->configuration = GeneralUtility::removeDotsFromTS($settings);

        return $this->configuration;
    }

    /**
     * @return array<int, string>
     */
    private function parseCommaDelimitedConfig(string $configKey): array
    {
        $configuration = $this->getConfiguration();

        return isset($configuration[$configKey])
            ? array_map(trim(...), explode(',', $configuration[$configKey]))
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getTypoScriptSetupArray(): array
    {
        $cacheKey = $this->generateTypoScriptCacheKey();

        if ($this->typoScriptCache->offsetExists($cacheKey)) {
            // @phpstan-ignore return.type (ArrayObject generic type inference limitation)
            return $this->typoScriptCache[$cacheKey];
        }

        $result = $this->getTypoScriptSetupArrayFromRequest($GLOBALS['TYPO3_REQUEST']);

        $this->manageCacheSize();
        $this->typoScriptCache->offsetSet($cacheKey, $result);

        return $result;
    }

    private function generateTypoScriptCacheKey(): string
    {
        $languageId = $this->context->getAspect('language')->getId();
        $pageId = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.page.information')->getId() ?? 0;

        return "typoscript:{$pageId}:{$languageId}";
    }

    private function manageCacheSize(): void
    {
        if ($this->typoScriptCache->count() >= self::MAX_CACHE_SIZE) {
            $keys = array_keys(iterator_to_array($this->typoScriptCache, true));
            $keysToRemove = array_slice($keys, 0, self::MAX_CACHE_SIZE - self::CACHE_CLEANUP_THRESHOLD);

            foreach ($keysToRemove as $key) {
                $this->typoScriptCache->offsetUnset($key);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getTypoScriptSetupArrayFromRequest(ServerRequestInterface $request): array
    {
        try {
            $frontendTypoScript = $request->getAttribute('frontend.typoscript');
            if (null === $frontendTypoScript) {
                return [];
            }

            return $frontendTypoScript->getSetupArray();
        } catch (Exception) {
            return [];
        }
    }
}
