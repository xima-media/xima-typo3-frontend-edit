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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditPageDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * PageMenuGenerator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class PageMenuGenerator extends AbstractMenuGenerator
{
    public function __construct(
        private readonly MenuButtonBuilder $menuButtonBuilder,
        private readonly EventDispatcher $eventDispatcher,
        ExtensionConfiguration $extensionConfiguration,
    ) {
        parent::__construct($extensionConfiguration);
    }

    /**
     * Generate the page dropdown menu data for the sticky toolbar.
     *
     * @return array<string, mixed>
     */
    public function getDropdown(ServerRequestInterface $request): array
    {
        $routing = $request->getAttribute('routing');
        $pid = $routing instanceof PageArguments ? $routing->getPageId() : 0;

        if (0 === $pid) {
            return [];
        }

        $language = $request->getAttribute('language');
        $languageUid = $language instanceof SiteLanguage ? $language->getLanguageId() : 0;
        $returnUrl = (string) $request->getUri();

        $menuButton = new Button(
            'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:page_menu',
            ButtonType::Menu,
        );

        $this->menuButtonBuilder->addPageEditSection($menuButton, $pid, $languageUid, $returnUrl);
        $this->menuButtonBuilder->addPageActionSection($menuButton, $pid, $returnUrl);

        $this->eventDispatcher->dispatch(
            new FrontendEditPageDropdownModifyEvent($pid, $languageUid, $menuButton, $returnUrl),
        );

        $rendered = $menuButton->render();
        $rendered['targetBlank'] = $this->isLinkTargetBlank();

        return $rendered;
    }
}
