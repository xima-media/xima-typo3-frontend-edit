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

use TYPO3\CMS\Core\Imaging\{Icon, IconFactory, IconSize};
use Xima\XimaTypo3FrontendEdit\Utility\Compatibility\IconFactoryUtility;

/**
 * IconService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class IconService
{
    /**
     * @var array<string, Icon>
     */
    private array $iconCache = [];

    public function __construct(
        private readonly IconFactory $iconFactory,
    ) {}

    public function getIcon(string $identifier): Icon
    {
        if (!isset($this->iconCache[$identifier])) {
            $this->iconCache[$identifier] = $this->iconFactory->getIcon(
                $identifier,
                IconSize::SMALL,
            );
        }

        return $this->iconCache[$identifier];
    }

    /**
     * Resolves the icon identifier TYPO3 would use for a record's own table/type
     * (TCA `ctrl.typeicon_classes`/`iconfile`) - for tables this extension has no
     * built-in icon convention for, unlike tt_content's CType-driven lookup.
     *
     * @param array<string, mixed> $record
     */
    public function getIconIdentifierForRecord(string $table, array $record): string
    {
        return IconFactoryUtility::mapRecordTypeToIconIdentifier($this->iconFactory, $table, $record);
    }
}
