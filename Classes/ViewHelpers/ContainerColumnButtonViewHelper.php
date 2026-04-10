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
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Ui\UrlBuilderService;

use function htmlspecialchars;
use function sprintf;

/**
 * Renders a "Create new content" button for an empty container column.
 *
 * Use this inside container templates (e.g. 2-column, tabs, accordion)
 * to show a "+" button when a container column has no child elements.
 *
 * Only visible when:
 * - A backend user is logged in
 * - Frontend editing is not disabled by the user
 *
 * Usage in Fluid templates (e.g. Container templates):
 *
 *     <!-- Register namespace (or use ext_localconf.php registration) -->
 *     {namespace xfe=Xima\XimaTypo3FrontendEdit\ViewHelpers}
 *
 *     <!-- Inside a container template, show button when column is empty -->
 *     <f:if condition="{column_content -> f:count()} == 0">
 *         <xfe:containerColumnButton
 *             pageId="{data.pid}"
 *             containerUid="{data.uid}"
 *             colPos="201"
 *             columnName="Left Column"
 *         />
 *     </f:if>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class ContainerColumnButtonViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('pageId', 'int', 'The page ID', true);
        $this->registerArgument('containerUid', 'int', 'The UID of the container element', true);
        $this->registerArgument('colPos', 'int', 'The column position within the container (e.g. 201, 202)', true);
        $this->registerArgument('columnName', 'string', 'Display name shown on the button (uses locallang if omitted)', false, null);
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

        $pageId = (int) $arguments['pageId'];
        $containerUid = (int) $arguments['containerUid'];
        $colPos = (int) $arguments['colPos'];
        $columnName = $arguments['columnName'];

        // Build the container new content URL
        $urlBuilderService = GeneralUtility::makeInstance(UrlBuilderService::class);
        $returnUrl = (string) $renderingContext->getRequest()->getUri();

        try {
            $newContentUrl = $urlBuilderService->buildContainerNewContentUrl($pageId, $containerUid, $colPos, $returnUrl);
        } catch (\Exception) {
            return '';
        }

        $buttonLabel = (null === $columnName || '' === $columnName)
            ? self::translate('column.createContent', 'Create new content')
            : $columnName;

        return sprintf(
            '<div class="frontend-edit__container-column-buttons" data-colpos="%d" data-container-parent="%d" hidden>'
            .'<a href="%s" class="frontend-edit__new-content-link frontend-edit__open-modal">'
            .'<div class="frontend-edit__new-content-button">'
            .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
            .'<line x1="12" y1="5" x2="12" y2="19"/>'
            .'<line x1="5" y1="12" x2="19" y2="12"/>'
            .'</svg>'
            .'<span>%s</span>'
            .'</div>'
            .'</a>'
            .'</div>',
            $colPos,
            $containerUid,
            htmlspecialchars($newContentUrl),
            htmlspecialchars($buttonLabel),
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