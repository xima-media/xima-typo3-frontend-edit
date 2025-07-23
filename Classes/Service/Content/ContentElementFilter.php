<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service\Content;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;

final class ContentElementFilter
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly ContentElementRepository $contentElementRepository
    ) {
    }

    public function filterContentElements(array $contentElements, BackendUserAuthentication $backendUser): array
    {
        $ignoredCTypes = $this->settingsService->getIgnoredCTypes();
        $ignoredListTypes = $this->settingsService->getIgnoredListTypes();
        $ignoredUids = $this->settingsService->getIgnoredUids();

        $filteredElements = [];

        foreach ($contentElements as $contentElement) {
            if (!$this->shouldIncludeElement($contentElement, $backendUser, $ignoredCTypes, $ignoredListTypes, $ignoredUids)) {
                continue;
            }

            $filteredElements[] = $contentElement;
        }

        return $filteredElements;
    }

    public function isPageIgnored(int $pid): bool
    {
        $ignoredPids = $this->settingsService->getIgnoredPids();

        return $this->contentElementRepository->isSubpageOfAny($pid, $ignoredPids);
    }

    private function shouldIncludeElement(
        array $contentElement,
        BackendUserAuthentication $backendUser,
        array $ignoredCTypes,
        array $ignoredListTypes,
        array $ignoredUids
    ): bool {
        // Check edit permissions
        if (!$backendUser->recordEditAccessInternals('tt_content', $contentElement)) {
            return false;
        }

        // Check if UID is ignored
        if (in_array($contentElement['uid'], $ignoredUids, true)) {
            return false;
        }

        // Check if CType is ignored
        if (in_array($contentElement['CType'], $ignoredCTypes, true)) {
            return false;
        }

        // Check if list_type is ignored for list content elements
        if ($contentElement['CType'] === 'list' &&
            in_array($contentElement['list_type'], $ignoredListTypes, true)) {
            return false;
        }

        return true;
    }
}
