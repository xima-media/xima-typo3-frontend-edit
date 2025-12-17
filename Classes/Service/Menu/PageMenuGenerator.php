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
use Throwable;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};

use function htmlspecialchars;
use function sprintf;

/**
 * PageMenuGenerator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class PageMenuGenerator extends AbstractMenuGenerator
{
    public function __construct(
        private readonly IconService $iconService,
        private readonly UrlBuilderService $urlBuilderService,
        ExtensionConfiguration $extensionConfiguration,
    ) {
        parent::__construct($extensionConfiguration);
    }

    /**
     * Generate the page dropdown menu HTML for the sticky toolbar.
     */
    public function getDropdown(ServerRequestInterface $request): string
    {
        $routing = $request->getAttribute('routing');
        $pid = $routing instanceof PageArguments ? $routing->getPageId() : 0;

        if (0 === $pid) {
            return '';
        }

        $language = $request->getAttribute('language');
        $languageUid = $language instanceof SiteLanguage ? $language->getLanguageId() : 0;
        $returnUrl = (string) $request->getUri();

        return $this->renderMenuHtml($pid, $languageUid, $returnUrl);
    }

    private function renderMenuHtml(int $pid, int $languageUid, string $returnUrl): string
    {
        $targetBlank = $this->isLinkTargetBlank();
        $targetAttr = $targetBlank ? ' target="_blank"' : '';

        $html = '';

        // Edit page properties
        try {
            $editUrl = $this->urlBuilderService->buildEditUrl($pid, 'pages', $languageUid, $returnUrl);
            $editIcon = $this->iconService->getIcon('actions-page-open')->getAlternativeMarkup('inline');
            $editLabel = $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_page_properties');
            $html .= sprintf(
                '<a href="%s" class="frontend-edit__sticky-dropdown-item"%s>%s<span>%s</span></a>',
                htmlspecialchars($editUrl, \ENT_QUOTES, 'UTF-8'),
                $targetAttr,
                $editIcon,
                htmlspecialchars((string) $editLabel, \ENT_QUOTES, 'UTF-8'),
            );
        } catch (Throwable) {
            // Skip if URL cannot be built
        }

        // Page layout (Backend)
        try {
            $layoutUrl = $this->urlBuilderService->buildPageLayoutUrl($pid, $languageUid, $returnUrl);
            $layoutIcon = $this->iconService->getIcon('actions-view-table-expand')->getAlternativeMarkup('inline');
            $layoutLabel = $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:page_layout');
            $html .= sprintf(
                '<a href="%s" class="frontend-edit__sticky-dropdown-item"%s>%s<span>%s</span></a>',
                htmlspecialchars($layoutUrl, \ENT_QUOTES, 'UTF-8'),
                $targetAttr,
                $layoutIcon,
                htmlspecialchars((string) $layoutLabel, \ENT_QUOTES, 'UTF-8'),
            );
        } catch (Throwable) {
            // Skip if URL cannot be built
        }

        return $html;
    }
}
