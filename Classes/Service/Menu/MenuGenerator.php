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

namespace Xima\XimaTypo3FrontendEdit\Service\Menu;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Exception;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Content\ContentElementFilter;
use Xima\XimaTypo3FrontendEdit\Service\Ui\IconService;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;
use Xima\XimaTypo3FrontendEdit\Traits\ExtensionConfigurationTrait;

use function array_key_exists;

/**
 * MenuGenerator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
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
        private readonly IconService $iconService,
        protected readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    /**
     * @param array<int, mixed> $data
     *
     * @return array<mixed>
     *
     * @throws Exception
     */
    public function getDropdown(
        int $pid,
        string $returnUrl,
        int $languageUid,
        ServerRequestInterface $request,
        array $data = [],
    ): array {
        if ($this->contentElementFilter->isPageIgnored($pid, $request)) {
            return [];
        }

        $backendUser = $this->backendUserService->getBackendUser();
        if (null === $backendUser || $this->backendUserService->isFrontendEditDisabled()) {
            return [];
        }

        $contentElements = $this->contentElementRepository->fetchContentElements($pid, $languageUid);
        $filteredElements = $this->contentElementFilter->filterContentElements($contentElements, $backendUser, $request);

        $result = [];
        foreach ($filteredElements as $contentElement) {
            $contentElementConfig = $this->contentElementRepository->getContentElementConfig(
                $contentElement['CType'],
                $contentElement['list_type'] ?? '',
            );
            $returnUrlAnchor = $returnUrl.'#c'.$contentElement['uid'];

            if (false === $contentElementConfig) {
                continue;
            }

            $menuButton = $this->createMenuButton($contentElement, $languageUid, $pid, $returnUrlAnchor, $contentElementConfig, $request);
            $this->handleAdditionalData($menuButton, $contentElement, $contentElementConfig, $data, $languageUid, $returnUrlAnchor);

            $this->eventDispatcher->dispatch(new FrontendEditDropdownModifyEvent($contentElement, $menuButton, $returnUrlAnchor));

            // Add ctypeLabel and ctypeIcon for frontend display
            $ctypeLabel = $GLOBALS['LANG']->sL($contentElementConfig['label'] ?? '');
            $contentElement['ctypeLabel'] = '' !== $ctypeLabel ? $ctypeLabel : $contentElement['CType'];

            // Render CType icon as HTML
            $iconIdentifier = $contentElementConfig['icon'] ?? 'content-textpic';
            $contentElement['ctypeIcon'] = (string) $this->iconService->getIcon($iconIdentifier);

            $result[$contentElement['uid']] = [
                'element' => $contentElement,
                'menu' => $menuButton,
            ];
        }

        return $this->renderMenuButtons($result);
    }

    /**
     * @param array<string, mixed> $contentElement
     * @param array<string, mixed> $contentElementConfig
     */
    private function createMenuButton(
        array $contentElement,
        int $languageUid,
        int $pid,
        string $returnUrlAnchor,
        array $contentElementConfig,
        ServerRequestInterface $request,
    ): Button {
        $showContextMenu = $this->isShowContextMenu() && !$this->settingsService->isOnlyEditEnabled($request);

        if (!$showContextMenu) {
            return $this->menuButtonBuilder->createSimpleEditButton(
                $contentElement,
                $languageUid,
                $returnUrlAnchor,
                $this->isLinkTargetBlank(),
            );
        }

        $menuButton = $this->menuButtonBuilder->createFullMenuButton();

        $this->menuButtonBuilder->addEditSection($menuButton, $contentElement, $languageUid, $pid, $returnUrlAnchor, $request);
        $this->menuButtonBuilder->addActionSection($menuButton, $contentElement, $returnUrlAnchor, $request);

        return $menuButton;
    }

    /**
     * @param array<string, mixed> $contentElement
     * @param array<string, mixed> $contentElementConfig
     * @param array<int, mixed>    $data
     */
    private function handleAdditionalData(
        Button $button,
        array $contentElement,
        array $contentElementConfig,
        array $data,
        int $languageUid,
        string $returnUrlAnchor,
    ): void {
        $uid = $this->resolveDataUid($contentElement, $data);
        if (null === $uid) {
            return;
        }

        $this->additionalDataHandler->handleData($button, $data[$uid], $contentElementConfig, $languageUid, $returnUrlAnchor);
    }

    /**
     * @param array<string, mixed> $contentElement
     * @param array<int, mixed>    $data
     */
    private function resolveDataUid(array $contentElement, array $data): ?int
    {
        // Check if data exists for current content element
        if (array_key_exists($contentElement['uid'], $data) && [] !== $data[$contentElement['uid']]) {
            return (int) $contentElement['uid'];
        }

        // Check if data exists for l10n_source (translation parent)
        if (array_key_exists('l10n_source', $contentElement)
            && array_key_exists($contentElement['l10n_source'], $data)
            && [] !== $data[$contentElement['l10n_source']]
            && 0 !== $contentElement['l10n_source']) {
            return (int) $contentElement['l10n_source'];
        }

        return null;
    }

    /**
     * @param array<mixed> $result
     *
     * @return array<mixed>
     */
    private function renderMenuButtons(array $result): array
    {
        foreach ($result as $uid => $contentElement) {
            $result[$uid]['menu'] = $contentElement['menu']->render();
        }

        return $result;
    }
}
