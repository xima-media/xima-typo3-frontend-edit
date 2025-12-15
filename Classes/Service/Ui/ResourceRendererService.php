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
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\{GeneralUtility, PathUtility};
use TYPO3\CMS\Core\View\{ViewFactoryData, ViewFactoryInterface};
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
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
            $resources = ResourceUtility::getResources(['nonce' => $nonceValue]);

            $debugMode = $this->settingsService->isFrontendDebugModeEnabled();
            if ($debugMode) {
                $debugScript = sprintf(
                    '<script%s>window.FRONTEND_EDIT_DEBUG = true;</script>',
                    '' !== $nonceValue ? ' nonce="'.$nonceValue.'"' : '',
                );
                $resources['debug_config'] = $debugScript;
            }

            $values = [...$values, 'resources' => $resources];

            return $this->renderView($template, $values, $request);
        } catch (Throwable $exception) {
            throw new Exception('Failed to render template "'.$template.'": '.$exception->getMessage(), 1640000001, $exception);
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
