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

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;
use Xima\XimaTypo3FrontendEdit\Utility\StringUtility;

use function array_key_exists;

/**
 * AdditionalDataHandler.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
final class AdditionalDataHandler
{
    public function __construct(
        private readonly BackendUserService $backendUserService,
        private readonly UrlBuilderService $urlBuilderService,
        private readonly IconService $iconService,
        private readonly ContentElementRepository $contentElementRepository,
    ) {}

    public function handleData(
        Button $button,
        array $dataEntries,
        array $contentElementConfig,
        int $languageUid,
        string $returnUrlAnchor,
    ): void {
        if ([] === $dataEntries) {
            return;
        }

        $this->addDataDivider($button);

        foreach ($dataEntries as $key => $dataEntry) {
            if (!$this->isValidDataEntry($dataEntry)) {
                continue;
            }

            $recordUid = $this->resolveRecordUid($dataEntry, $languageUid);
            if (null === $recordUid) {
                continue;
            }

            $this->addDataButton($button, $dataEntry, $recordUid, $contentElementConfig, $languageUid, $returnUrlAnchor, $key);
        }
    }

    private function addDataDivider(Button $button): void
    {
        $button->appendChild(
            new Button(
                'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:div_data',
                ButtonType::Divider,
            ),
            'div_data',
        );
    }

    private function isValidDataEntry(array $dataEntry): bool
    {
        if (null === $dataEntry['label'] || '' === $dataEntry['label']) {
            return false;
        }

        $hasTableAndUid = (null !== $dataEntry['table'] && null !== $dataEntry['uid']);
        $hasUrl = (null !== $dataEntry['url']);

        return $hasTableAndUid || $hasUrl;
    }

    /**
     * @throws Exception
     */
    private function resolveRecordUid(array $dataEntry, int $languageUid): ?int
    {
        if (null === $dataEntry['table'] || null === $dataEntry['uid']) {
            return null;
        }

        if (!$this->backendUserService->hasRecordEditAccess($dataEntry['table'], $dataEntry)) {
            return null;
        }

        $recordUid = $dataEntry['uid'];
        $record = BackendUtility::getRecord($dataEntry['table'], $recordUid);

        if (null === $record) {
            return null;
        }

        // Check if record needs translation
        if ($this->needsTranslation($record, $languageUid)) {
            $translatedRecord = $this->contentElementRepository->getTranslatedRecord(
                $dataEntry['table'],
                $recordUid,
                $languageUid,
            );

            if ($translatedRecord) {
                return $translatedRecord['uid'];
            }

            return null;
        }

        return $recordUid;
    }

    private function needsTranslation(array $record, int $languageUid): bool
    {
        return array_key_exists('sys_language_uid', $record)
            && $record['sys_language_uid'] !== $languageUid;
    }

    private function addDataButton(
        Button $button,
        array $dataEntry,
        int $recordUid,
        array $contentElementConfig,
        int $languageUid,
        string $returnUrlAnchor,
        string $key,
    ): void {
        $url = $this->buildDataUrl($dataEntry, $recordUid, $languageUid, $returnUrlAnchor);
        $icon = $this->resolveDataIcon($dataEntry, $contentElementConfig);

        $dataButton = new Button(
            StringUtility::shortenString($dataEntry['label']),
            ButtonType::Link,
            $url,
            $icon,
        );

        $button->appendChild($dataButton, 'data_'.$key);
    }

    private function buildDataUrl(array $dataEntry, int $recordUid, int $languageUid, string $returnUrlAnchor): string
    {
        if (null !== $dataEntry['url']) {
            return $dataEntry['url'];
        }

        return $this->urlBuilderService->buildEditUrl(
            $recordUid,
            $dataEntry['table'],
            $languageUid,
            $returnUrlAnchor,
        );
    }

    private function resolveDataIcon(array $dataEntry, array $contentElementConfig): \TYPO3\CMS\Core\Imaging\Icon
    {
        if (null !== $dataEntry['icon']) {
            return $this->iconService->getIcon($dataEntry['icon']);
        }

        return $this->iconService->getIcon($contentElementConfig['icon']);
    }
}
