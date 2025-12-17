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

use Exception;
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Site\Entity\{Site, SiteSettings};
use Xima\XimaTypo3FrontendEdit\Configuration;

use function array_map;
use function explode;
use function in_array;
use function trim;

/**
 * SettingsService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class SettingsService
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function isEnabled(ServerRequestInterface $request): bool
    {
        $settings = $this->getSiteSettings($request);
        if (null === $settings) {
            return false;
        }

        return (bool) $settings->get('frontendEdit.enabled', true);
    }

    /**
     * @return array<int, string>
     */
    public function getIgnoredPids(ServerRequestInterface $request): array
    {
        return $this->parseCommaDelimitedSetting($request, 'frontendEdit.filter.ignorePids');
    }

    /**
     * @return array<int, string>
     */
    public function getIgnoredCTypes(ServerRequestInterface $request): array
    {
        return $this->parseCommaDelimitedSetting($request, 'frontendEdit.filter.ignoreCTypes');
    }

    /**
     * @return array<int, string>
     */
    public function getIgnoredListTypes(ServerRequestInterface $request): array
    {
        return $this->parseCommaDelimitedSetting($request, 'frontendEdit.filter.ignoreListTypes');
    }

    /**
     * @return array<int, int>
     */
    public function getIgnoredUids(ServerRequestInterface $request): array
    {
        $values = $this->parseCommaDelimitedSetting($request, 'frontendEdit.filter.ignoreUids');

        return array_map(intval(...), $values);
    }

    public function getColorScheme(ServerRequestInterface $request): string
    {
        $settings = $this->getSiteSettings($request);
        if (null === $settings) {
            return 'auto';
        }

        $scheme = (string) $settings->get('frontendEdit.colorScheme', 'auto');

        return in_array($scheme, ['auto', 'light', 'dark'], true) ? $scheme : 'auto';
    }

    public function isShowContextMenu(ServerRequestInterface $request): bool
    {
        $settings = $this->getSiteSettings($request);
        if (null === $settings) {
            return true;
        }

        return (bool) $settings->get('frontendEdit.showContextMenu', true);
    }

    public function isShowStickyToolbar(ServerRequestInterface $request): bool
    {
        $settings = $this->getSiteSettings($request);
        if (null === $settings) {
            return true;
        }

        return (bool) $settings->get('frontendEdit.showStickyToolbar', true);
    }

    public function getToolbarPosition(ServerRequestInterface $request): string
    {
        $validPositions = ['bottom-right', 'bottom-left', 'top-right', 'top-left', 'bottom', 'top', 'left', 'right'];

        $settings = $this->getSiteSettings($request);
        if (null === $settings) {
            return 'bottom-right';
        }

        $position = (string) $settings->get('frontendEdit.toolbarPosition', 'bottom-right');

        return in_array($position, $validPositions, true) ? $position : 'bottom-right';
    }

    public function isFrontendDebugModeEnabled(): bool
    {
        try {
            $configuration = $this->extensionConfiguration->get(Configuration::EXT_KEY);

            return (bool) ($configuration['frontendDebugMode'] ?? false);
        } catch (Exception) {
            return false;
        }
    }

    private function getSiteSettings(ServerRequestInterface $request): ?SiteSettings
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return null;
        }

        return $site->getSettings();
    }

    /**
     * @return array<int, string>
     */
    private function parseCommaDelimitedSetting(ServerRequestInterface $request, string $settingKey): array
    {
        $settings = $this->getSiteSettings($request);
        if (null === $settings) {
            return [];
        }

        $value = '';

        try {
            $value = (string) $settings->get($settingKey, '');
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface) {
        }

        if ('' === $value) {
            return [];
        }

        return array_filter(array_map(trim(...), explode(',', $value)), static fn (string $item): bool => '' !== $item);
    }
}
