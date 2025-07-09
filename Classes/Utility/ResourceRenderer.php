<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;

class ResourceRenderer
{
    /**
    * @param array<string, mixed> $values
    */
    public static function render(string $template = 'FrontendEdit.html', array $values = [], ?ServerRequestInterface $request = null): string
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();

        $values = [...$values, 'resources' => ResourceUtility::getResources(['nonce' => self::resolveNonceValue()])];

        if ($typo3Version >= 13) {
            return self::renderView13($template, $values, $request);
        }

        return self::renderView12($template, $values);
    }

    /**
    * @param array<string, mixed> $values
    */
    private static function renderView12(string $template, array $values): string
    {
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
        $view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class); // @phpstan-ignore classConstant.deprecatedClass
        $view->setFormat('html'); // @phpstan-ignore method.deprecatedClass
        $view->setTemplateRootPaths(['EXT:' . Configuration::EXT_KEY . '/Resources/Private/Templates/']); // @phpstan-ignore method.deprecatedClass
        $view->setPartialRootPaths(['EXT:' . Configuration::EXT_KEY . '/Resources/Private/Partials/']); // @phpstan-ignore method.deprecatedClass
        if (PathUtility::isExtensionPath($template)) {
            $template = GeneralUtility::getFileAbsFileName($template);
            $view->setTemplatePathAndFilename($template); // @phpstan-ignore method.deprecatedClass
        } else {
            $view->setTemplate($template); // @phpstan-ignore method.deprecatedClass
        }
        $view->assignMultiple($values);

        return $view->render();
    }

    /**
    * @param array<string, mixed> $values
    */
    private static function renderView13(string $template, array $values, ?ServerRequestInterface $request = null): string
    {
        $viewFactoryData = new \TYPO3\CMS\Core\View\ViewFactoryData(
            templateRootPaths: ['EXT:' . Configuration::EXT_KEY . '/Resources/Private/Templates/'],
            partialRootPaths: ['EXT:' . Configuration::EXT_KEY . '/Resources/Private/Partials/'],
            layoutRootPaths: ['EXT:' . Configuration::EXT_KEY . '/Resources/Private/Layouts/'],
            request: $request,
        );

        $viewFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\View\ViewFactoryInterface::class);
        $view = $viewFactory->create($viewFactoryData);
        $view->assignMultiple($values);

        if (PathUtility::isExtensionPath($template)) {
            $template = GeneralUtility::getFileAbsFileName($template);
        }

        return $view->render($template);
    }

    private static function resolveNonceValue(): string
    {
        return property_exists(RequestId::class, 'nonce') ? GeneralUtility::makeInstance(RequestId::class)->nonce->consume() : '';
    }
}
