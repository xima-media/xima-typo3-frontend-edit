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

namespace Xima\XimaTypo3FrontendEdit\Service\Menu;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Content\ContentElementFilter;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;
use Xima\XimaTypo3FrontendEdit\Traits\ExtensionConfigurationTrait;

/**
 * MenuGenerator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
final class MenuGenerator
{
    use ExtensionConfigurationTrait;

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly SettingsService $settingsService,
        private readonly BackendUserService $backendUserService,
        private readonly ContentElementFilter $contentElementFilter,
        private readonly MenuButtonBuilder $menuButtonBuilder,
        private readonly ContentElementRepository $contentElementRepository,
        private readonly AdditionalDataHandler $additionalDataHandler,
        protected readonly ExtensionConfiguration $extensionConfiguration
    ) {}

    public function getDropdown(int $pid, string $returnUrl, int $languageUid, array $data = []): array
    {
        if ($this->contentElementFilter->isPageIgnored($pid)) {
            return [];
        }

        $backendUser = $this->backendUserService->getBackendUser();
        if ($backendUser === null || $this->backendUserService->isFrontendEditDisabled()) {
            return [];
        }

        $contentElements = $this->contentElementRepository->fetchContentElements($pid, $languageUid);
        $filteredElements = $this->contentElementFilter->filterContentElements($contentElements, $backendUser);

        $result = [];
        foreach ($filteredElements as $contentElement) {
            $contentElementConfig = $this->contentElementRepository->getContentElementConfig(
                $contentElement['CType'],
                $contentElement['list_type']
            );
            $returnUrlAnchor = $returnUrl . '#c' . $contentElement['uid'];

            $menuButton = $this->createMenuButton($contentElement, $languageUid, $pid, $returnUrlAnchor, $contentElementConfig);
            $this->handleAdditionalData($menuButton, $contentElement, $contentElementConfig, $data, $languageUid, $returnUrlAnchor);

            $this->eventDispatcher->dispatch(new FrontendEditDropdownModifyEvent($contentElement, $menuButton, $returnUrlAnchor));
            $result[$contentElement['uid']] = [
                'element' => $contentElement,
                'menu' => $menuButton,
            ];
        }

        return $this->renderMenuButtons($result);
    }

    private function createMenuButton(
        array $contentElement,
        int $languageUid,
        int $pid,
        string $returnUrlAnchor,
        array $contentElementConfig
    ): Button {
        $simpleMode = $this->isSimpleMode() || $this->settingsService->checkSimpleModeMenuStructure();

        if ($simpleMode) {
            return $this->menuButtonBuilder->createSimpleEditButton(
                $contentElement,
                $languageUid,
                $returnUrlAnchor,
                $this->isLinkTargetBlank()
            );
        }

        $menuButton = $this->menuButtonBuilder->createFullMenuButton();

        $this->menuButtonBuilder->addInfoSection($menuButton, $contentElement, $contentElementConfig);
        $this->menuButtonBuilder->addEditSection($menuButton, $contentElement, $languageUid, $pid, $returnUrlAnchor);
        $this->menuButtonBuilder->addActionSection($menuButton, $contentElement, $returnUrlAnchor);

        return $menuButton;
    }

    private function handleAdditionalData(
        Button $button,
        array $contentElement,
        array $contentElementConfig,
        array $data,
        int $languageUid,
        string $returnUrlAnchor
    ): void {
        $uid = $this->resolveDataUid($contentElement, $data);
        if ($uid === null) {
            return;
        }

        $this->additionalDataHandler->handleData($button, $data[$uid], $contentElementConfig, $languageUid, $returnUrlAnchor);
    }

    private function resolveDataUid(array $contentElement, array $data): ?int
    {
        // Check if data exists for current content element
        if (array_key_exists($contentElement['uid'], $data) && $data[$contentElement['uid']] !== []) {
            return $contentElement['uid'];
        }

        // Check if data exists for l10n_source (translation parent)
        if (array_key_exists('l10n_source', $contentElement) &&
            array_key_exists($contentElement['l10n_source'], $data) &&
            $data[$contentElement['l10n_source']] !== [] &&
            $contentElement['l10n_source'] !== 0) {
            return $contentElement['l10n_source'];
        }

        return null;
    }

    private function renderMenuButtons(array $result): array
    {
        foreach ($result as $uid => $contentElement) {
            $result[$uid]['menu'] = $contentElement['menu']->render();
        }

        return $result;
    }
}
