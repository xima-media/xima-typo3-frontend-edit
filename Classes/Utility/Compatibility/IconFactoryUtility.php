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

namespace Xima\XimaTypo3FrontendEdit\Utility\Compatibility;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * IconFactoryUtility.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class IconFactoryUtility
{
    /**
     * TYPO3 v14 added a required third `TcaSchema` argument to
     * IconFactory::mapRecordTypeToIconIdentifier(); v13 has no such parameter.
     *
     * @param array<string, mixed> $row
     *
     * @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Breaking-104010-TcaSchemaAwareIconFactory.html
     */
    public static function mapRecordTypeToIconIdentifier(IconFactory $iconFactory, string $table, array $row): string
    {
        if (!VersionUtility::is14OrHigher()) {
            return $iconFactory->mapRecordTypeToIconIdentifier($table, $row);
        }

        $tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        if (!$tcaSchemaFactory->has($table)) {
            return 'mimetypes-other-other';
        }

        // @phpstan-ignore arguments.count (3-arg signature only exists on TYPO3 v14.0+)
        return $iconFactory->mapRecordTypeToIconIdentifier($table, $row, $tcaSchemaFactory->get($table));
    }
}
