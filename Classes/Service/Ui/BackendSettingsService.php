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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * BackendSettingsService.
 *
 * Generates the inline TYPO3 backend settings and global-object stubs that
 * must be present before any backend JavaScript module runs inside the
 * frontend-editing iframe. Without these stubs, modules such as
 * shortcut-menu.js or top-level-module-import.js throw because they expect
 * top.TYPO3.Backend.Topbar.Toolbar and similar objects.
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
     * Generate a <script> tag with backend stubs and settings for the editing iframe.
     */
    public function getSettingsScript(string $nonce = ''): string
    {
        $settingsJson = json_encode($this->buildSettings(), \JSON_HEX_TAG | \JSON_HEX_AMP);
        $langJson = json_encode($this->buildLangLabels(), \JSON_HEX_TAG | \JSON_HEX_AMP);
        $nonceAttr = '' !== $nonce ? ' nonce="'.htmlspecialchars($nonce, \ENT_QUOTES, 'UTF-8').'"' : '';

        return sprintf(
            '<script%s>
(function() {
    "use strict";
    if (!window.TYPO3) window.TYPO3 = {};
    if (!window.TYPO3.settings) window.TYPO3.settings = {};
    if (!window.TYPO3.lang) window.TYPO3.lang = {};
    if (!window.TYPO3.Backend) window.TYPO3.Backend = {};

    if (!window.TYPO3.Backend.Topbar) {
        window.TYPO3.Backend.Topbar = {
            Toolbar: {
                registerEvent: function(cb) { try { cb(); } catch(e) {} },
                refresh: function() {}
            }
        };
    }
    if (!window.TYPO3.Backend.Topbar.Toolbar) {
        window.TYPO3.Backend.Topbar.Toolbar = {
            registerEvent: function(cb) { try { cb(); } catch(e) {} },
            refresh: function() {}
        };
    }

    if (!window.TYPO3.Backend.consumerScope) {
        window.TYPO3.Backend.consumerScope = {
            attach: function() {},
            detach: function() {},
            invoke: function() {}
        };
    }

    if (!window.TYPO3.Backend.ContentContainer) {
        window.TYPO3.Backend.ContentContainer = {
            setUrl: function(url) {
                if (url && url.indexOf("/typo3/wizard/") !== -1) {
                    if (window.TYPO3.Modal && window.TYPO3.Modal.advanced) {
                        window.TYPO3.Modal.advanced({ content: url });
                    }
                    return Promise.resolve();
                }
                var iframe = document.querySelector("#xima-typo3-frontend-edit-modal-iframe");
                if (iframe) {
                    try {
                        var u = new URL(url, window.location.origin);
                        var ret = u.searchParams.get("returnUrl") || "";
                        if (!ret || ret.indexOf("/typo3/") !== -1) {
                            u.searchParams.set("returnUrl", window.location.href);
                        }
                        if (!u.searchParams.has("tx_ximatypo3frontendedit")) {
                            u.searchParams.set("tx_ximatypo3frontendedit", "");
                        }
                        iframe.src = u.toString();
                    } catch(e) { iframe.src = url; }
                }
                return Promise.resolve();
            },
            refresh: function() {
                var iframe = document.querySelector("#xima-typo3-frontend-edit-modal-iframe");
                if (iframe && iframe.contentWindow) iframe.contentWindow.location.reload();
                return Promise.resolve();
            },
            get: function() {
                return document.querySelector("#xima-typo3-frontend-edit-modal-iframe");
            }
        };
    }

    if (!window.TYPO3.InfoWindow) {
        window.TYPO3.InfoWindow = { showItem: function() {} };
    }

    if (!window.TYPO3.HotkeyStorage) {
        window.TYPO3.HotkeyStorage = {
            register: function() {},
            unregister: function() {},
            getScopedHotkeyMap: function() { return new Map(); }
        };
    }

    if (!window.TYPO3.Modal) {
        window.TYPO3.Modal = {
            advanced: function(config) {
                var url = config && (config.content || config.url || "");
                if (url && url.indexOf("/typo3/wizard/") !== -1) {
                    var iframe = document.querySelector("#xima-typo3-frontend-edit-modal-iframe");
                    if (iframe && iframe.contentWindow && iframe.contentWindow.document) {
                        var doc = iframe.contentWindow.document;
                        var existing = doc.getElementById("fe-wizard-overlay");
                        if (existing) existing.remove();
                        var overlay = doc.createElement("div");
                        overlay.id = "fe-wizard-overlay";
                        overlay.style.cssText = "position:fixed;top:0;left:0;width:100%%;height:100%%;z-index:99999;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;";
                        var panel = doc.createElement("div");
                        panel.style.cssText = "width:80%%;max-width:900px;height:80%%;background:#fff;border-radius:4px;overflow:hidden;position:relative;display:flex;flex-direction:column;box-shadow:0 4px 24px rgba(0,0,0,0.3);";
                        var header = doc.createElement("div");
                        header.style.cssText = "display:flex;justify-content:flex-end;padding:4px 8px;background:#eee;flex-shrink:0;";
                        var closeBtn = doc.createElement("button");
                        closeBtn.innerHTML = "&times;";
                        closeBtn.style.cssText = "font-size:22px;background:none;border:none;cursor:pointer;padding:2px 8px;line-height:1;";
                        closeBtn.onclick = function() { overlay.remove(); };
                        var wizIframe = doc.createElement("iframe");
                        wizIframe.src = url;
                        wizIframe.style.cssText = "flex:1;border:none;width:100%%;";
                        header.appendChild(closeBtn);
                        panel.appendChild(header);
                        panel.appendChild(wizIframe);
                        overlay.appendChild(panel);
                        overlay.addEventListener("click", function(ev) { if (ev.target === overlay) overlay.remove(); });
                        doc.body.appendChild(overlay);
                    }
                }
                return { addEventListener: function() {}, on: function() {} };
            },
            confirm: function(title, content) { return Promise.resolve(confirm(content)); },
            dismiss: function() {
                var iframe = document.querySelector("#xima-typo3-frontend-edit-modal-iframe");
                if (iframe && iframe.contentWindow) {
                    var overlay = iframe.contentWindow.document.getElementById("fe-wizard-overlay");
                    if (overlay) overlay.remove();
                }
            },
            currentModal: null
        };
    }

    Object.assign(window.TYPO3.settings, %s);
    Object.assign(window.TYPO3.lang, %s);
    window.TYPO3.Backend._frontendEditContext = true;

    document.addEventListener("typo3:import-javascript-module", function(e) {
        if (e.detail && e.detail.specifier) {
            e.detail.importPromise = import(e.detail.specifier).catch(function() { return {}; });
        }
    });
})();
</script>',
            $nonceAttr,
            $settingsJson,
            $langJson,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSettings(): array
    {
        return [
            'ajaxUrls' => $this->buildAjaxUrls(),
            'DateTimePicker' => [
                'DateFormat' => ['d.m.Y', 'Y-m-d'],
            ],
        ];
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
            } catch (\Exception) {
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