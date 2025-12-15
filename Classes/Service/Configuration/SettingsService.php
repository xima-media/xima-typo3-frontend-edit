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
use function trim;

/**
 * SettingsService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class SettingsService
{
    private const MENU_STRUCTURE_MAP = [
        'div_info' => 'frontendEdit.menu.showDivInfo',
        'header' => 'frontendEdit.menu.showHeader',
        'div_edit' => 'frontendEdit.menu.showDivEdit',
        'edit' => 'frontendEdit.menu.showEdit',
        'edit_page' => 'frontendEdit.menu.showEditPage',
        'div_action' => 'frontendEdit.menu.showDivAction',
        'hide' => 'frontendEdit.menu.showHide',
        'move' => 'frontendEdit.menu.showMove',
        'info' => 'frontendEdit.menu.showInfo',
        'history' => 'frontendEdit.menu.showHistory',
    ];

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

    public function checkDefaultMenuStructure(ServerRequestInterface $request, string $identifier): bool
    {
        $settings = $this->getSiteSettings($request);
        if (null === $settings) {
            return true;
        }

        $settingKey = self::MENU_STRUCTURE_MAP[$identifier] ?? null;
        if (null === $settingKey) {
            return true;
        }

        return (bool) $settings->get($settingKey, true);
    }

    public function checkSimpleModeMenuStructure(ServerRequestInterface $request): bool
    {
        $settings = $this->getSiteSettings($request);
        if (null === $settings) {
            return false;
        }

        foreach (self::MENU_STRUCTURE_MAP as $key => $settingKey) {
            $value = (bool) $settings->get($settingKey, true);
            if ('edit' === $key && !$value) {
                return false;
            }
            if ('edit' !== $key && $value) {
                return false;
            }
        }

        return true;
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
