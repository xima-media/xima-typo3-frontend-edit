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

namespace Xima\XimaTypo3FrontendEdit\Service\Ui;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\{GeneralUtility, PathUtility};
use TYPO3\CMS\Core\View\{ViewFactoryData, ViewFactoryInterface};
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Menu\PageMenuGenerator;
use Xima\XimaTypo3FrontendEdit\Utility\ResourceUtility;

use function array_key_exists;
use function in_array;
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
        private ExtensionConfiguration $extensionConfiguration,
        private BackendUserService $backendUserService,
        private UrlBuilderService $urlBuilderService,
        private PageMenuGenerator $pageMenuGenerator,
    ) {}

    /**
     * @param array<string, mixed> $values
     *
     * @throws Exception
     */
    public function render(string $template = 'FrontendEdit.html', array $values = [], ?ServerRequestInterface $request = null): string
    {
        try {
            $nonceValue = $this->resolveNonceValue();
            $nonceAttribute = '' !== $nonceValue ? ' nonce="'.$nonceValue.'"' : '';
            $resources = ResourceUtility::getResources(['nonce' => $nonceValue]);

            // Add Floating UI library - loaded as module, then signals ready
            $floatingUiPath = PathUtility::getAbsoluteWebPath(
                GeneralUtility::getFileAbsFileName('EXT:'.Configuration::EXT_KEY.'/Resources/Public/JavaScript/vendor/floating-ui.dom.bundle.js'),
            );
            $resources['floating_ui'] = sprintf(
                '<script%s type="module">import * as FloatingUIDOM from "%s"; window.FloatingUIDOM = FloatingUIDOM; window.dispatchEvent(new Event("floatingui:ready"));</script>',
                $nonceAttribute,
                $floatingUiPath,
            );

            // Add settings configuration (colorScheme and showContextMenu)
            $colorScheme = $this->getColorScheme();
            $showContextMenu = $this->isShowContextMenu() ? 'true' : 'false';
            $resources['settings_config'] = sprintf(
                '<script%s>window.FRONTEND_EDIT_COLOR_SCHEME = "%s"; window.FRONTEND_EDIT_SHOW_CONTEXT_MENU = %s;</script>',
                $nonceAttribute,
                $colorScheme,
                $showContextMenu,
            );

            // Add debug mode if enabled
            $debugMode = $this->settingsService->isFrontendDebugModeEnabled();
            if ($debugMode) {
                $resources['debug_config'] = sprintf(
                    '<script%s>window.FRONTEND_EDIT_DEBUG = true;</script>',
                    $nonceAttribute,
                );
            }

            // Add sticky toolbar configuration as data attributes (like Admin Panel pattern)
            $isDisabled = $this->backendUserService->isFrontendEditDisabled();
            $editInfoUrl = $this->getEditInformationUrl();
            $pageInfo = $this->getPageInfo($request);
            $resources['toolbar_config'] = sprintf(
                '<div id="frontend-edit-toolbar-config" data-disabled="%s" data-edit-info-url="%s" data-pid="%d" data-language="%d" hidden></div>',
                $isDisabled ? 'true' : 'false',
                htmlspecialchars($editInfoUrl, \ENT_QUOTES, 'UTF-8'),
                $pageInfo['pid'],
                $pageInfo['language'],
            );

            // Add sticky toolbar resources only if enabled
            if ($this->isShowStickyToolbar()) {
                $toolbarPosition = $this->getToolbarPosition();
                $toggleUrl = $this->getToggleUrl();
                $resources['sticky_toolbar_config'] = sprintf(
                    '<div id="frontend-edit-sticky-toolbar-config" data-position="%s" data-toggle-url="%s" hidden></div>',
                    $toolbarPosition,
                    htmlspecialchars($toggleUrl, \ENT_QUOTES, 'UTF-8'),
                );

                // Add page menu data as JSON (rendered client-side like content element menus)
                $pageMenuData = null !== $request ? $this->pageMenuGenerator->getDropdown($request) : [];
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

            $values = [...$values, 'resources' => $resources];

            return $this->renderView($template, $values, $request);
        } catch (Throwable $exception) {
            throw new Exception('Failed to render template "'.$template.'": '.$exception->getMessage(), 1640000001, $exception);
        }
    }

    private function isShowContextMenu(): bool
    {
        try {
            $config = $this->extensionConfiguration->get(Configuration::EXT_KEY);

            return !array_key_exists('showContextMenu', $config) || (bool) $config['showContextMenu'];
        } catch (Throwable) {
            return true;
        }
    }

    private function isShowStickyToolbar(): bool
    {
        try {
            $config = $this->extensionConfiguration->get(Configuration::EXT_KEY);

            return !array_key_exists('showStickyToolbar', $config) || (bool) $config['showStickyToolbar'];
        } catch (Throwable) {
            return true;
        }
    }

    private function getColorScheme(): string
    {
        try {
            $config = $this->extensionConfiguration->get(Configuration::EXT_KEY);
            $scheme = $config['colorScheme'] ?? 'auto';

            return in_array($scheme, ['auto', 'light', 'dark'], true) ? $scheme : 'auto';
        } catch (Throwable) {
            return 'auto';
        }
    }

    private function getToolbarPosition(): string
    {
        $validPositions = ['bottom-right', 'bottom-left', 'top-right', 'top-left', 'bottom', 'top', 'left', 'right'];

        try {
            $config = $this->extensionConfiguration->get(Configuration::EXT_KEY);
            $position = $config['toolbarPosition'] ?? 'bottom-right';

            return in_array($position, $validPositions, true) ? $position : 'bottom-right';
        } catch (Throwable) {
            return 'bottom-right';
        }
    }

    private function getToggleUrl(): string
    {
        try {
            // AJAX routes get 'ajax_' prefix automatically
            return $this->urlBuilderService->buildRoute('ajax_frontendEdit_toggle');
        } catch (Throwable) {
            return '';
        }
    }

    private function getEditInformationUrl(): string
    {
        try {
            // AJAX routes get 'ajax_' prefix automatically
            return $this->urlBuilderService->buildRoute('ajax_frontendEdit_editInformation');
        } catch (Throwable) {
            return '';
        }
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
            if ($routing instanceof \TYPO3\CMS\Core\Routing\PageArguments) {
                $pid = $routing->getPageId();
            }

            $siteLanguage = $request->getAttribute('language');
            if ($siteLanguage instanceof \TYPO3\CMS\Core\Site\Entity\SiteLanguage) {
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
}
