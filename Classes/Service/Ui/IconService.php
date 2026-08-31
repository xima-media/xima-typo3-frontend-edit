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

use function is_array;

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
        $this->iconCache[$identifier] ??= $this->iconFactory->getIcon(
            $identifier,
            IconSize::SMALL,
        );

        return $this->iconCache[$identifier];
    }

    /**
     * Resolves the icon identifier for a record's own table/type from TCA
     * `ctrl.typeicon_column`/`typeicon_classes` - the same convention
     * PageButtonBuilder::getDoktypeIcon() already reads directly for pages,
     * used here instead of IconFactory::mapRecordTypeToIconIdentifier() to
     * stay version-stable (that method's signature changed in TYPO3 v14.0 -
     * see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Breaking-104010-TcaSchemaAwareIconFactory.html -
     * and resolving the TcaSchema it now requires needs a fully bootstrapped
     * DI container, unavailable in this extension's bootstrap-free unit tests).
     * Deliberately a simplified subset (no pages-specific nav_hide/root/mask/
     * userFunc handling) - out of scope for foreign, non-page records.
     *
     * @param array<string, mixed> $record
     */
    public function getIconIdentifierForRecord(string $table, array $record): string
    {
        $ctrl = $GLOBALS['TCA'][$table]['ctrl'] ?? [];
        $typeIconClasses = is_array($ctrl['typeicon_classes'] ?? null) ? $ctrl['typeicon_classes'] : [];
        $typeIconColumn = $ctrl['typeicon_column'] ?? null;

        if (null !== $typeIconColumn) {
            $value = $record[$typeIconColumn] ?? '';
            $key = is_array($value) ? implode(',', $value) : (string) $value;
            if ('' !== $key && isset($typeIconClasses[$key])) {
                return $typeIconClasses[$key];
            }
        }

        return $typeIconClasses['default'] ?? 'mimetypes-other-other';
    }
}
