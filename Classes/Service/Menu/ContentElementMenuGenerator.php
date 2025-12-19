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
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
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

use function array_key_exists;
use function array_slice;
use function count;
use function is_array;

/**
 * ContentElementMenuGenerator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ContentElementMenuGenerator extends AbstractMenuGenerator
{
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly BackendUserService $backendUserService,
        private readonly ContentElementFilter $contentElementFilter,
        private readonly ContentElementButtonBuilder $contentElementButtonBuilder,
        private readonly ContentElementRepository $contentElementRepository,
        private readonly AdditionalDataHandler $additionalDataHandler,
        private readonly IconService $iconService,
        private readonly SettingsService $settingsService,
        ExtensionConfiguration $extensionConfiguration,
    ) {
        parent::__construct($extensionConfiguration);
    }

    /**
     * @param array<int|string, mixed> $data
     *
     * @return array<int, array{element: array<string, mixed>, menu: array<string, mixed>}>
     *
     * @throws Exception
     * @throws RouteNotFoundException
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

        // Try UID-based fetching first (onepager support)
        // Falls back to PID-based fetching if no UIDs provided (backwards compatibility)
        $uids = $this->extractValidatedUids($data);
        if ([] !== $uids) {
            $contentElements = $this->contentElementRepository->fetchContentElementsByUids($uids, $languageUid);
        } else {
            $contentElements = $this->contentElementRepository->fetchContentElements($pid, $languageUid);
        }

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

            /** @var FrontendEditDropdownModifyEvent $event */
            $event = $this->eventDispatcher->dispatch(new FrontendEditDropdownModifyEvent($contentElement, $menuButton, $returnUrlAnchor));

            // Add ctypeLabel and ctypeIcon for frontend display
            $ctypeLabel = $GLOBALS['LANG']->sL($contentElementConfig['label'] ?? '');
            $contentElement['ctypeLabel'] = '' !== $ctypeLabel ? $ctypeLabel : $contentElement['CType'];

            // Render CType icon as HTML
            $iconIdentifier = $contentElementConfig['icon'] ?? 'content-textpic';
            $contentElement['ctypeIcon'] = (string) $this->iconService->getIcon($iconIdentifier);

            // Use potentially modified button from event listeners
            $result[$contentElement['uid']] = [
                'element' => $contentElement,
                'menu' => $event->getMenuButton(),
            ];
        }

        return $this->renderMenuButtons($result);
    }

    /**
     * @param array<string, mixed> $contentElement
     * @param array<string, mixed> $contentElementConfig
     *
     * @throws RouteNotFoundException
     */
    private function createMenuButton(
        array $contentElement,
        int $languageUid,
        int $pid,
        string $returnUrlAnchor,
        array $contentElementConfig,
        ServerRequestInterface $request,
    ): Button {
        if (!$this->settingsService->isShowContextMenu($request)) {
            return $this->contentElementButtonBuilder->createSimpleEditButton(
                $contentElement,
                $languageUid,
                $returnUrlAnchor,
                $this->isLinkTargetBlank(),
            );
        }

        $menuButton = $this->contentElementButtonBuilder->createFullMenuButton();

        $this->contentElementButtonBuilder->addInfoSection($menuButton, $contentElement, $contentElementConfig);
        $this->contentElementButtonBuilder->addEditSection($menuButton, $contentElement, $languageUid, $pid, $returnUrlAnchor);
        $this->contentElementButtonBuilder->addActionSection($menuButton, $contentElement, $returnUrlAnchor);

        return $menuButton;
    }

    /**
     * @param array<string, mixed>     $contentElement
     * @param array<string, mixed>     $contentElementConfig
     * @param array<int|string, mixed> $data
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
     * @param array<string, mixed>     $contentElement
     * @param array<int|string, mixed> $data
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
     * @param array<int, array{element: array<string, mixed>, menu: Button}> $result
     *
     * @return array<int, array{element: array<string, mixed>, menu: array<string, mixed>}>
     */
    private function renderMenuButtons(array $result): array
    {
        foreach ($result as $uid => $contentElement) {
            $result[$uid]['menu'] = $contentElement['menu']->render();
        }

        return $result;
    }

    /**
     * Extract and validate UIDs from request data.
     *
     * @param array<int|string, mixed> $data Request data from frontend
     *
     * @return array<int> Validated array of unique UIDs
     */
    private function extractValidatedUids(array $data): array
    {
        if (!isset($data['_uids']) || !is_array($data['_uids'])) {
            return [];
        }

        $uids = [];
        foreach ($data['_uids'] as $uid) {
            $validated = filter_var($uid, \FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if (false !== $validated) {
                $uids[] = $validated;
            }
        }

        $uids = array_unique($uids);

        // Limit to 500 UIDs to prevent DoS attacks
        if (count($uids) > 500) {
            $uids = array_slice($uids, 0, 500);
        }

        return array_values($uids);
    }
}
