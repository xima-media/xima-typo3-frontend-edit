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

namespace Xima\XimaTypo3FrontendEdit\ViewHelpers;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Content\BackendLayoutService;
use Xima\XimaTypo3FrontendEdit\Service\Ui\UrlBuilderService;

use function htmlspecialchars;
use function sprintf;

/**
 * Renders a "Create new content" button for an empty page column.
 *
 * Only visible when:
 * - A backend user is logged in
 * - Frontend editing is not disabled by the user
 * - The column exists in the backend layout and has no content
 *
 * Usage in Fluid templates (e.g. Page templates):
 *
 *     <!-- Register namespace (or use ext_localconf.php registration) -->
 *     {namespace xfe=Xima\XimaTypo3FrontendEdit\ViewHelpers}
 *
 *     <!-- Show button for colPos 0 when empty -->
 *     <xfe:singleColumnButton pageId="{data.uid}" colPos="0" columnName="Main Content" />
 *
 *     <!-- With language support -->
 *     <xfe:singleColumnButton pageId="{data.uid}" colPos="1" columnName="Sidebar" languageUid="{data.sys_language_uid}" />
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class SingleColumnButtonViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('pageId', 'int', 'The page ID', true);
        $this->registerArgument('colPos', 'int', 'The column position (colPos)', true);
        $this->registerArgument('columnName', 'string', 'Column header label', false, null);
        $this->registerArgument('languageUid', 'int', 'The language UID', false, 0);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext,
    ): string {
        // Require authenticated backend user
        $context = GeneralUtility::makeInstance(Context::class);
        if (!$context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)) {
            return '';
        }

        // Respect user-level disable toggle
        if ($GLOBALS['BE_USER']->uc[Configuration::UC_KEY_DISABLED] ?? false) {
            return '';
        }

        // Only render in frontend context
        $request = $renderingContext->getRequest();
        $requestUri = (string) $request->getUri();
        if (str_contains($requestUri, '/typo3/')) {
            return '';
        }

        $pageId = (int) $arguments['pageId'];
        $colPos = (int) $arguments['colPos'];
        $columnName = $arguments['columnName'];
        $languageUid = (int) $arguments['languageUid'];

        // Auto-detect language from context if not explicitly set
        if (0 === $languageUid) {
            try {
                $context = GeneralUtility::makeInstance(Context::class);
                $languageUid = (int) $context->getPropertyFromAspect('language', 'id', 0);
            } catch (\Exception) {
                // Fallback to 0
            }
        }

        // Check if column exists and is empty
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $backendLayoutService = GeneralUtility::makeInstance(BackendLayoutService::class, $connectionPool);
        $columns = $backendLayoutService->getColumnsForPage($pageId, $languageUid);

        $columnExists = false;
        foreach ($columns as $column) {
            if ((int) $column['colPos'] === $colPos) {
                $columnExists = true;
                if ($column['contentCount'] > 0) {
                    return ''; // Column has content — no button needed
                }
                // Use backend layout column name if not provided via argument
                if (null === $columnName || '' === $columnName) {
                    $columnName = $column['name'] ?? 'Column '.$colPos;
                }
                break;
            }
        }

        if (!$columnExists) {
            return '';
        }

        // Build the new content wizard URL
        $urlBuilderService = GeneralUtility::makeInstance(UrlBuilderService::class);
        $returnUrl = (string) $request->getUri();

        try {
            $newContentUrl = $urlBuilderService->buildNewContentInColumnUrl($pageId, $colPos, $returnUrl);
        } catch (\Exception) {
            return '';
        }

        $createContentLabel = self::translate('column.createContent', 'Create new content');

        return sprintf(
            '<div class="frontend-edit__column-section" data-colpos="%d" hidden>'
            .'<div class="frontend-edit__column-header">'
            .'<span class="frontend-edit__column-name">%s</span>'
            .'</div>'
            .'<div class="frontend-edit__column-buttons">'
            .'<a href="%s" class="frontend-edit__column-btn frontend-edit__open-modal">'
            .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
            .'<line x1="12" y1="5" x2="12" y2="19"/>'
            .'<line x1="5" y1="12" x2="19" y2="12"/>'
            .'</svg>'
            .'<span>%s</span>'
            .'</a>'
            .'</div>'
            .'</div>',
            $colPos,
            htmlspecialchars($columnName),
            htmlspecialchars($newContentUrl),
            htmlspecialchars($createContentLabel),
        );
    }

    private static function translate(string $key, string $fallback): string
    {
        try {
            if (isset($GLOBALS['BE_USER']) && null !== $GLOBALS['BE_USER']) {
                $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
                    ->createFromUserPreferences($GLOBALS['BE_USER']);

                return $languageService->sL(
                    'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:'.$key,
                ) ?: $fallback;
            }
        } catch (\Throwable) {
        }

        return $fallback;
    }
}