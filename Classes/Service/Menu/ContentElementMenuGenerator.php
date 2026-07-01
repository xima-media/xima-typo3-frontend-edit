<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2026 Konrad Michalik <hej@konradmichalik.dev>
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
use TYPO3\CMS\Core\Localization\LanguageService;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Content\ContentElementFilter;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
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
        private readonly UrlBuilderService $urlBuilderService,
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

        $enableScrollToElement = $this->settingsService->isEnableScrollToElement($request);

        // Remove existing URL fragment to prevent accumulation (e.g. page.html#c123#c456)
        $returnUrlWithoutFragment = strtok($returnUrl, '#') ?: $returnUrl;

        // Check once if the contextual edit route exists (TYPO3 v14.2+)
        $contextualRouteAvailable = $this->urlBuilderService->isContextualEditRouteAvailable();

        // Hover insert buttons (before/after each element). Precompute the
        // previous-sibling uid per column so "before" can target the position
        // after the previous element (first element → column top).
        $showInsertButtons = $this->settingsService->isShowInsertButtons($request);
        $previousSiblingByUid = $this->buildPreviousSiblingMap($filteredElements, $showInsertButtons);

        $result = [];
        foreach ($filteredElements as $contentElement) {
            $contentElementConfig = $this->contentElementRepository->getContentElementConfig(
                $contentElement['CType'],
                $contentElement['list_type'] ?? '',
            );
            $returnUrlAnchor = $enableScrollToElement ? $returnUrlWithoutFragment.'#c'.$contentElement['uid'] : $returnUrlWithoutFragment;
            $contextualUrl = $this->resolveContextualUrl($contextualRouteAvailable, (int) $contentElement['uid'], $languageUid, $returnUrlAnchor);

            if (false === $contentElementConfig) {
                continue;
            }

            $menuButton = $this->createMenuButton($contentElement, $languageUid, $pid, $returnUrlAnchor, $contentElementConfig, $request, $contextualUrl, $showInsertButtons);
            $this->handleAdditionalData($menuButton, $contentElement, $contentElementConfig, $data, $languageUid, $returnUrlAnchor);

            /** @var FrontendEditDropdownModifyEvent $event */
            $event = $this->eventDispatcher->dispatch(new FrontendEditDropdownModifyEvent($contentElement, $menuButton, $returnUrlAnchor));

            // Add ctypeLabel and ctypeIcon for frontend display
            $ctypeLabel = $this->getLanguageService()->sL($contentElementConfig['label'] ?? '');
            $contentElement['ctypeLabel'] = '' !== $ctypeLabel ? $ctypeLabel : $contentElement['CType'];

            // Render CType icon as HTML
            $iconIdentifier = $contentElementConfig['icon'] ?? 'content-textpic';
            $contentElement['ctypeIcon'] = (string) $this->iconService->getIcon($iconIdentifier);

            $this->addInsertButtonUrls($contentElement, $showInsertButtons, $previousSiblingByUid, $pid, $languageUid, $returnUrlAnchor);

            // Use potentially modified button from event listeners
            $result[$contentElement['uid']] = [
                'element' => $contentElement,
                'menu' => $event->getMenuButton(),
            ];
        }

        return $this->renderMenuButtons($result);
    }

    /**
     * Map each content element uid to the uid of its previous sibling within the
     * same column (and container). Used to position the "insert before" button.
     * Returns null for the first element of a column (→ insert at column top).
     *
     * @param array<int, array<string, mixed>> $elements
     *
     * @return array<int, int|null>
     */
    private function buildPreviousSiblingMap(array $elements, bool $enabled): array
    {
        if (!$enabled) {
            return [];
        }

        $groups = [];
        foreach ($elements as $element) {
            $key = (int) ($element['colPos'] ?? 0).'_'.(int) ($element['tx_container_parent'] ?? 0);
            $groups[$key][] = $element;
        }

        $previousByUid = [];
        foreach ($groups as $group) {
            usort($group, static fn (array $a, array $b): int => ((int) ($a['sorting'] ?? 0)) <=> ((int) ($b['sorting'] ?? 0)));
            $previousUid = null;
            foreach ($group as $element) {
                $previousByUid[(int) $element['uid']] = $previousUid;
                $previousUid = (int) $element['uid'];
            }
        }

        return $previousByUid;
    }

    /**
     * Add the "insert before" / "insert after" wizard URLs to a content element,
     * consumed by the frontend hover buttons. "before" targets the position after
     * the previous sibling (or the column top for the first element); "after"
     * targets the position after this element.
     *
     * @param array<string, mixed> $contentElement
     * @param array<int, int|null> $previousSiblingByUid
     */
    private function addInsertButtonUrls(array &$contentElement, bool $enabled, array $previousSiblingByUid, int $pid, int $languageUid, string $returnUrl): void
    {
        if (!$enabled) {
            return;
        }

        $uid = (int) $contentElement['uid'];
        $colPos = (int) ($contentElement['colPos'] ?? 0);
        $containerUid = (int) ($contentElement['tx_container_parent'] ?? 0) > 0
            ? (int) $contentElement['tx_container_parent']
            : null;
        $previousUid = $previousSiblingByUid[$uid] ?? null;

        try {
            $contentElement['newAfterUrl'] = $this->urlBuilderService->buildNewContentWizardUrl($pid, $colPos, $languageUid, $returnUrl, uidAfter: $uid, containerUid: $containerUid);
            $contentElement['newBeforeUrl'] = $this->urlBuilderService->buildNewContentWizardUrl($pid, $colPos, $languageUid, $returnUrl, uidAfter: $previousUid, containerUid: $containerUid);
        } catch (RouteNotFoundException) {
            // Leave the URLs unset; the frontend simply skips the buttons.
        }
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
        ?string $contextualUrl = null,
        bool $showInsertButtons = true,
    ): Button {
        if (!$this->settingsService->isShowContextMenu($request)) {
            return $this->contentElementButtonBuilder->createSimpleEditButton(
                $contentElement,
                $languageUid,
                $returnUrlAnchor,
                $this->isLinkTargetBlank(),
                $contextualUrl,
            );
        }

        $menuButton = $this->contentElementButtonBuilder->createFullMenuButton();

        $this->contentElementButtonBuilder->addInfoSection($menuButton, $contentElement, $contentElementConfig);
        $this->contentElementButtonBuilder->addEditSection($menuButton, $contentElement, $languageUid, $pid, $returnUrlAnchor, $contextualUrl);
        $this->contentElementButtonBuilder->addActionSection($menuButton, $contentElement, $languageUid, $returnUrlAnchor, $showInsertButtons);

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

    private function resolveContextualUrl(bool $contextualEditingEnabled, int $uid, int $languageUid, string $returnUrl): ?string
    {
        if (!$contextualEditingEnabled) {
            return null;
        }

        return $this->urlBuilderService->buildContextualEditUrl($uid, 'tt_content', $languageUid, $returnUrl);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
