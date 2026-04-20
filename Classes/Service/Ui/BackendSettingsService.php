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

use Exception;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\{GeneralUtility, PathUtility};
use Xima\XimaTypo3FrontendEdit\Configuration;

use function sprintf;

/**
 * BackendSettingsService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class BackendSettingsService
{
    /**
     * AJAX routes required by tree components, link browser, and file handling.
     *
     * @var array<string, string>
     */
    private const REQUIRED_AJAX_ROUTES = [
        'filestorage_tree_data' => 'ajax_filestorage_tree_data',
        'filestorage_tree_filter' => 'ajax_filestorage_tree_filter',
        'file_exists' => 'ajax_file_exists',
        'file_process' => 'ajax_file_process',
        'record_process' => 'ajax_record_process',
        'contextmenu' => 'ajax_contextmenu',
        'contextmenu_clipboard' => 'ajax_contextmenu_clipboard',
        'link_browser_encodetypolink' => 'ajax_link_browser_encodetypolink',
        'link_browser_page' => 'ajax_link_browser_page',
        'link_browser_file' => 'ajax_link_browser_file',
        'link_browser_folder' => 'ajax_link_browser_folder',
        'link_browser_url' => 'ajax_link_browser_url',
        'link_browser_mail' => 'ajax_link_browser_mail',
        'link_browser_telephone' => 'ajax_link_browser_telephone',
        'record_tree_data' => 'ajax_record_tree_data',
        'record_tree_filter' => 'ajax_record_tree_filter',
        'page_tree_data' => 'ajax_page_tree_data',
        'page_tree_rootline' => 'ajax_page_tree_rootline',
        'page_tree_filter' => 'ajax_page_tree_filter',
        'page_tree_configuration' => 'ajax_page_tree_configuration',
        'page_tree_browser_configuration' => 'ajax_page_tree_browser_configuration',
        'page_tree_set_temporary_mount_point' => 'ajax_page_tree_set_temporary_mount_point',
    ];

    /**
     * Language labels consumed by backend JavaScript modules.
     *
     * @var array<string, string>
     */
    private const REQUIRED_LANG_LABELS = [
        'tree.networkError' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:tree.networkError',
        'tree.noResults' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:tree.noResults',
        'tree.loading' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:tree.loading',
        'labels.datepicker.today' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.datepicker.today',
        'labels.datepicker.clear' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.datepicker.clear',
        'labels.datepicker.close' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.datepicker.close',
        'button.ok' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:button.ok',
        'button.cancel' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:button.cancel',
        'button.close' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:button.close',
        'labels.refresh' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.refresh',
        'labels.search' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.search',
        'file_upload.uploadedSuccessfully' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.uploadedSuccessfully',
        'file_upload.error' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.error',
    ];

    private UriBuilder $uriBuilder;

    public function __construct()
    {
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    /**
     * Generate the JSON data block + stubs script tag.
     *
     * The JSON block carries ajaxUrls and lang labels.
     * The script tag loads backend_stubs.js which consumes that data.
     */
    public function getSettingsScript(string $nonce = ''): string
    {
        $nonceAttr = '' !== $nonce ? ' nonce="'.htmlspecialchars($nonce, \ENT_QUOTES, 'UTF-8').'"' : '';

        $dataJson = json_encode([
            'settings' => [
                'ajaxUrls' => $this->buildAjaxUrls(),
                'DateTimePicker' => [
                    'DateFormat' => ['d.m.Y', 'Y-m-d'],
                ],
            ],
            'lang' => $this->buildLangLabels(),
        ], \JSON_HEX_TAG | \JSON_HEX_AMP) ?: '{}';

        $stubsPath = PathUtility::getAbsoluteWebPath(
            GeneralUtility::getFileAbsFileName('EXT:'.Configuration::EXT_KEY.'/Resources/Public/JavaScript/backend_stubs.js'),
        );

        return sprintf(
            '<script%s type="application/json" id="frontend-edit-backend-stubs-data">%s</script>'
            .'<script%s src="%s"></script>',
            $nonceAttr,
            $dataJson,
            $nonceAttr,
            $stubsPath,
        );
    }

    /**
     * @return array<string, string>
     */
    private function buildAjaxUrls(): array
    {
        $urls = [];
        foreach (self::REQUIRED_AJAX_ROUTES as $key => $routeIdentifier) {
            try {
                $urls[$key] = (string) $this->uriBuilder->buildUriFromRoute($routeIdentifier);
            } catch (Exception) {
                // Route may not exist in all TYPO3 versions
            }
        }

        return $urls;
    }

    /**
     * @return array<string, string>
     */
    private function buildLangLabels(): array
    {
        $languageService = $this->getLanguageService();
        if (null === $languageService) {
            return [];
        }

        $labels = [];
        foreach (self::REQUIRED_LANG_LABELS as $key => $llReference) {
            $labels[$key] = $languageService->sL($llReference) ?: $key;
        }

        return $labels;
    }

    private function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
