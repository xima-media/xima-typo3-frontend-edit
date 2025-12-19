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

use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * AbstractMenuButtonBuilder.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
abstract readonly class AbstractMenuButtonBuilder
{
    public function __construct(
        protected IconService $iconService,
        protected UrlBuilderService $urlBuilderService,
    ) {}

    public function addButton(
        Button $menuButton,
        string $identifier,
        ButtonType $type,
        ?string $label = null,
        ?string $url = null,
        ?string $icon = null,
    ): void {
        $finalLabel = $label ?? 'LLL:EXT:'.Configuration::EXT_KEY."/Resources/Private/Language/locallang.xlf:$identifier";
        $finalIcon = null !== $icon ? $this->iconService->getIcon($icon) : null;

        $button = new Button(
            $finalLabel,
            $type,
            $url,
            $finalIcon,
            false, // Target blank will be handled at a higher level
        );

        $menuButton->appendChild($button, $identifier);
    }
}
