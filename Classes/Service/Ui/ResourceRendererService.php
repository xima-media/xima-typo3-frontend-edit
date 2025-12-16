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
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
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

            // Add Floating UI library
            $floatingUiPath = PathUtility::getAbsoluteWebPath(
                GeneralUtility::getFileAbsFileName('EXT:'.Configuration::EXT_KEY.'/Resources/Public/JavaScript/vendor/floating-ui.dom.bundle.js'),
            );
            $resources['floating_ui'] = sprintf(
                '<script%s type="module">import * as FloatingUIDOM from "%s"; window.FloatingUIDOM = FloatingUIDOM;</script>',
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
