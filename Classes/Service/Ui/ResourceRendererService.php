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

namespace Xima\XimaTypo3FrontendEdit\Service\Ui;

use JsonException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\{GeneralUtility, PathUtility};
use TYPO3\CMS\Core\View\{ViewFactoryData, ViewFactoryInterface};
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Menu\PageMenuGenerator;
use Xima\XimaTypo3FrontendEdit\Utility\ResourceUtility;

use function sprintf;

/**
 * ResourceRendererService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class ResourceRendererService
{
    public function __construct(
        private SettingsService $settingsService,
        private BackendUserService $backendUserService,
        private UrlBuilderService $urlBuilderService,
        private PageMenuGenerator $pageMenuGenerator,
    ) {}

    /**
     * @param array<string, mixed>                                           $values
     * @param array<array{title: string, message: string, severity: string}> $flashMessages
     *
     * @throws Exception
     */
    public function render(string $template = 'FrontendEdit.html', array $values = [], ?ServerRequestInterface $request = null, array $flashMessages = []): string
    {
        try {
            $nonceValue = $this->resolveNonceValue();
            $nonceAttribute = '' !== $nonceValue ? ' nonce="'.$nonceValue.'"' : '';
            $resources = ResourceUtility::getResources(['nonce' => $nonceValue]);

            $this->addFloatingUiResource($resources, $nonceAttribute);
            $this->addSettingsConfig($resources, $nonceAttribute, $request);
            $this->addDebugConfig($resources, $nonceAttribute);
            $this->addToolbarConfig($resources, $request);
            $this->addStickyToolbarResourcesIfEnabled($resources, $request, $nonceAttribute);
            $this->addFlashMessagesConfig($resources, $nonceAttribute, $flashMessages);

            $values = [...$values, 'resources' => $resources];

            return $this->renderView($template, $values, $request);
        } catch (Throwable $exception) {
            throw new Exception('Failed to render template "'.$template.'": '.$exception->getMessage(), 1640000001, $exception);
        }
    }

    /**
     * @param array<string, string> $resources
     */
    private function addFloatingUiResource(array &$resources, string $nonceAttribute): void
    {
        $floatingUiPath = PathUtility::getAbsoluteWebPath(
            GeneralUtility::getFileAbsFileName('EXT:'.Configuration::EXT_KEY.'/Resources/Public/JavaScript/vendor/floating-ui.dom.bundle.js'),
        );
        $resources['floating_ui'] = sprintf(
            '<script%s type="module">import * as FloatingUIDOM from "%s"; window.FloatingUIDOM = FloatingUIDOM; window.dispatchEvent(new Event("floatingui:ready"));</script>',
            $nonceAttribute,
            $floatingUiPath,
        );
    }

    /**
     * @param array<string, string> $resources
     */
    private function addSettingsConfig(array &$resources, string $nonceAttribute, ?ServerRequestInterface $request): void
    {
        $colorScheme = null !== $request ? $this->settingsService->getColorScheme($request) : 'auto';
        $showContextMenu = (null !== $request && $this->settingsService->isShowContextMenu($request)) ? 'true' : 'false';
        $enableOutline = (null !== $request && $this->settingsService->isEnableOutline($request)) ? 'true' : 'false';
        $enableScrollToElement = (null !== $request && $this->settingsService->isEnableScrollToElement($request)) ? 'true' : 'false';
        $resources['settings_config'] = sprintf(
            '<script%s>window.FRONTEND_EDIT_COLOR_SCHEME = "%s"; window.FRONTEND_EDIT_SHOW_CONTEXT_MENU = %s; window.FRONTEND_EDIT_ENABLE_OUTLINE = %s; window.FRONTEND_EDIT_ENABLE_SCROLL_TO_ELEMENT = %s;</script>',
            $nonceAttribute,
            $colorScheme,
            $showContextMenu,
            $enableOutline,
            $enableScrollToElement,
        );
    }

    /**
     * @param array<string, string> $resources
     */
    private function addDebugConfig(array &$resources, string $nonceAttribute): void
    {
        if (!$this->settingsService->isFrontendDebugModeEnabled()) {
            return;
        }

        $resources['debug_config'] = sprintf(
            '<script%s>window.FRONTEND_EDIT_DEBUG = true;</script>',
            $nonceAttribute,
        );
    }

    /**
     * Add flash messages configuration for frontend notifications.
     *
     * @param array<string, string>                                          $resources
     * @param array<array{title: string, message: string, severity: string}> $flashMessages
     */
    private function addFlashMessagesConfig(array &$resources, string $nonceAttribute, array $flashMessages): void
    {
        if ([] === $flashMessages) {
            return;
        }

        $resources['flash_messages_config'] = sprintf(
            '<script%s type="application/json" class="frontend-edit-flash-messages">%s</script>',
            $nonceAttribute,
            json_encode($flashMessages, \JSON_THROW_ON_ERROR | \JSON_HEX_TAG | \JSON_HEX_AMP),
        );
    }

    /**
     * @param array<string, string> $resources
     *
     * @throws RouteNotFoundException
     */
    private function addToolbarConfig(array &$resources, ?ServerRequestInterface $request): void
    {
        $isDisabled = $this->backendUserService->isFrontendEditDisabled();
        $editInfoUrl = $this->urlBuilderService->buildEditInformationUrl();
        $pageInfo = $this->getPageInfo($request);
        $resources['toolbar_config'] = sprintf(
            '<div id="frontend-edit-toolbar-config" data-disabled="%s" data-edit-info-url="%s" data-pid="%d" data-language="%d" hidden></div>',
            $isDisabled ? 'true' : 'false',
            htmlspecialchars($editInfoUrl, \ENT_QUOTES, 'UTF-8'),
            $pageInfo['pid'],
            $pageInfo['language'],
        );
    }

    /**
     * @param array<string, string> $resources
     *
     * @throws JsonException
     */
    private function addStickyToolbarResourcesIfEnabled(array &$resources, ?ServerRequestInterface $request, string $nonceAttribute): void
    {
        if (null === $request || !$this->settingsService->isShowStickyToolbar($request)) {
            return;
        }

        $this->addStickyToolbarResources($resources, $request, $nonceAttribute);
    }

    /**
     * @param array<string, string> $resources
     *
     * @throws JsonException
     */
    private function addStickyToolbarResources(array &$resources, ServerRequestInterface $request, string $nonceAttribute): void
    {
        $toolbarPosition = $this->settingsService->getToolbarPosition($request);
        $toggleUrl = $this->urlBuilderService->buildToggleUrl();

        // Get translated tooltip strings
        $tooltipEnable = $this->translate('tooltip.enable', 'Enable frontend editing mode');
        $tooltipDisable = $this->translate('tooltip.disable', 'Disable frontend editing mode');
        $tooltipPageOptions = $this->translate('tooltip.pageOptions', 'Page options');

        $resources['sticky_toolbar_config'] = sprintf(
            '<div id="frontend-edit-sticky-toolbar-config" data-position="%s" data-toggle-url="%s" data-tooltip-enable="%s" data-tooltip-disable="%s" data-tooltip-page-options="%s" hidden></div>',
            $toolbarPosition,
            htmlspecialchars($toggleUrl, \ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($tooltipEnable, \ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($tooltipDisable, \ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($tooltipPageOptions, \ENT_QUOTES, 'UTF-8'),
        );

        // Add page menu data as JSON (rendered client-side like content element menus)
        $pageMenuData = $this->pageMenuGenerator->getDropdown($request);
        if ([] !== $pageMenuData) {
            $resources['page_menu_data'] = sprintf(
                '<script%s type="application/json" id="frontend-edit-page-menu-data">%s</script>',
                $nonceAttribute,
                json_encode($pageMenuData, \JSON_THROW_ON_ERROR | \JSON_HEX_TAG | \JSON_HEX_AMP),
            );
        }

        // Add sticky toolbar script
        $stickyToolbarPath = PathUtility::getAbsoluteWebPath(
            GeneralUtility::getFileAbsFileName('EXT:'.Configuration::EXT_KEY.'/Resources/Public/JavaScript/sticky_toolbar.js'),
        );
        $resources['sticky_toolbar'] = sprintf(
            '<script%s src="%s"></script>',
            $nonceAttribute,
            $stickyToolbarPath,
        );
    }

    /**
     * @return array{pid: int, language: int}
     */
    private function getPageInfo(?ServerRequestInterface $request): array
    {
        $pid = 0;
        $language = 0;

        if (null !== $request) {
            $routing = $request->getAttribute('routing');
            if ($routing instanceof PageArguments) {
                $pid = $routing->getPageId();
            }

            $siteLanguage = $request->getAttribute('language');
            if ($siteLanguage instanceof SiteLanguage) {
                $language = $siteLanguage->getLanguageId();
            }
        }

        return ['pid' => $pid, 'language' => $language];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function renderView(string $template, array $values, ?ServerRequestInterface $request = null): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:'.Configuration::EXT_KEY.'/Resources/Private/Templates/'],
            partialRootPaths: ['EXT:'.Configuration::EXT_KEY.'/Resources/Private/Partials/'],
            layoutRootPaths: ['EXT:'.Configuration::EXT_KEY.'/Resources/Private/Layouts/'],
            request: $request,
        );

        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        $view = $viewFactory->create($viewFactoryData);
        $view->assignMultiple($values);

        if (PathUtility::isExtensionPath($template)) {
            $template = GeneralUtility::getFileAbsFileName($template);
        }

        return $view->render($template);
    }

    private function resolveNonceValue(): string
    {
        try {
            $requestId = GeneralUtility::makeInstance(RequestId::class);

            return $requestId->nonce->consume();
        } catch (Throwable) {
            // Silently fail and return empty string for nonce
        }

        return '';
    }

    private function translate(string $key, string $fallback): string
    {
        $languageService = $this->getLanguageService();
        if (null === $languageService) {
            return $fallback;
        }

        return $languageService->sL(
            sprintf(
                'LLL:EXT:%s/Resources/Private/Language/locallang.xlf:%s',
                Configuration::EXT_KEY,
                $key),
        ) ?: $fallback;
    }

    private function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
