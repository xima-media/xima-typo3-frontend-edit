/**
 * TYPO3 Backend Stubs for frontend editing iframe (TYPO3 v13 only).
 *
 * Provides the minimum set of global TYPO3.* objects and AJAX endpoints
 * required to boot TYPO3 backend JavaScript modules inside our editing
 * iframe without throwing.
 *
 * ─────────────────────────────────────────────────────────────────────
 * STUB POLICY — read this before adding or removing anything
 * ─────────────────────────────────────────────────────────────────────
 *
 * This file follows a strict "minimum viable stub" policy:
 *
 *   1. Every stub below has a DOCUMENTED TRIGGER — a specific console
 *      error observed in a real v13.4 edit flow without that stub.
 *   2. NEVER add a stub "just in case". Defensive stubbing creates the
 *      illusion of API support, while backend JS silently calls the
 *      no-op method and fails downstream (e.g. regular-event.js
 *      "Delegating event failed" warnings).
 *   3. To add a new stub:
 *        a. Reproduce the console error in a real edit flow (v13.4).
 *        b. Add the minimum implementation that silences the error.
 *        c. Document the trigger (module name + error message) in a
 *           comment directly above the stub block.
 *   4. To remove a stub:
 *        a. Comment it out.
 *        b. Walk through every editing flow: open modal, edit text,
 *           open RTE, link browser, file picker, Save, Save & Close,
 *           info / move / history, new content after.
 *        c. If no error appears, delete the stub.
 *
 * Dynamic data (ajaxUrls, lang labels) is supplied from PHP via a
 * <script type="application/json" id="frontend-edit-backend-stubs-data">
 * block rendered by BackendSettingsService.
 *
 * Helpers (ensureReturnUrl, openWizardOverlay) are exposed on
 * window.XimaFrontendEdit so iframe_edit.js can reuse them without
 * duplication.
 */
(function () {
    'use strict';

    const IFRAME_ID = 'xima-typo3-frontend-edit-modal-iframe';
    const WIZARD_OVERLAY_ID = 'fe-wizard-overlay';

    function readStubData() {
        const dataEl = document.getElementById('frontend-edit-backend-stubs-data');
        if (!dataEl) return { settings: {}, lang: {} };
        try {
            return JSON.parse(dataEl.textContent || '{}');
        } catch (e) {
            return { settings: {}, lang: {} };
        }
    }

    function getIframe() {
        return document.querySelector('#' + IFRAME_ID);
    }

    /**
     * Build the returnUrl that TYPO3 redirects to after Save.
     *
     * Appends `tx_ximatypo3frontendedit_iframe=1` so the ToolRendererMiddleware
     * on that follow-up frontend request knows NOT to consume the flash message
     * queue — the parent page will consume them after it reloads.
     */
    function buildIframeReturnUrl() {
        const returnUrl = new URL(window.location.href);
        returnUrl.searchParams.set('tx_ximatypo3frontendedit_iframe', '1');
        return returnUrl.toString();
    }

    /**
     * Ensure a backend URL carries a frontend returnUrl and the extension marker
     * so the Save & Close button is wired into the iframe flow.
     */
    function ensureReturnUrl(url) {
        try {
            const u = new URL(url, window.location.origin);
            const existing = u.searchParams.get('returnUrl') || '';

            // Rebuild returnUrl: use existing if it's a frontend URL, otherwise
            // default to current page. Always add the iframe marker.
            let returnUrl;
            if (existing && existing.indexOf('/typo3/') === -1) {
                returnUrl = new URL(existing, window.location.origin);
            } else {
                returnUrl = new URL(window.location.href);
            }
            returnUrl.searchParams.set('tx_ximatypo3frontendedit_iframe', '1');
            u.searchParams.set('returnUrl', returnUrl.toString());

            if (!u.searchParams.has('tx_ximatypo3frontendedit')) {
                u.searchParams.set('tx_ximatypo3frontendedit', '');
            }
            return u.toString();
        } catch (e) {
            return url;
        }
    }

    /**
     * Open a wizard URL inside a nested overlay within the edit iframe.
     * Used for link browser, file picker, and other wizards that TYPO3 normally
     * opens via Modal.advanced().
     */
    function openWizardOverlay(url) {
        const iframe = getIframe();
        if (!iframe || !iframe.contentWindow || !iframe.contentWindow.document) return;

        const doc = iframe.contentWindow.document;
        const existing = doc.getElementById(WIZARD_OVERLAY_ID);
        if (existing) existing.remove();

        const overlay = doc.createElement('div');
        overlay.id = WIZARD_OVERLAY_ID;
        overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:99999;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;';

        const panel = doc.createElement('div');
        panel.style.cssText = 'width:80%;max-width:900px;height:80%;background:#fff;border-radius:4px;overflow:hidden;position:relative;display:flex;flex-direction:column;box-shadow:0 4px 24px rgba(0,0,0,0.3);';

        const header = doc.createElement('div');
        header.style.cssText = 'display:flex;justify-content:flex-end;padding:4px 8px;background:#eee;flex-shrink:0;';

        const closeBtn = doc.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = 'font-size:22px;background:none;border:none;cursor:pointer;padding:2px 8px;line-height:1;';
        closeBtn.onclick = function () { overlay.remove(); };

        const wizIframe = doc.createElement('iframe');
        wizIframe.src = url;
        wizIframe.style.cssText = 'flex:1;border:none;width:100%;';

        header.appendChild(closeBtn);
        panel.appendChild(header);
        panel.appendChild(wizIframe);
        overlay.appendChild(panel);
        overlay.addEventListener('click', function (ev) { if (ev.target === overlay) overlay.remove(); });
        doc.body.appendChild(overlay);
    }

    // Expose helpers used by iframe_edit.js
    window.XimaFrontendEdit = window.XimaFrontendEdit || {};
    window.XimaFrontendEdit.ensureReturnUrl = ensureReturnUrl;
    window.XimaFrontendEdit.openWizardOverlay = openWizardOverlay;

    // ── Global TYPO3 stubs ─────────────────────────────────────────
    //
    // Minimum viable stub set. Each stub below has a documented trigger —
    // ── Global TYPO3 stubs ─────────────────────────────────────────
    // See STUB POLICY comment at the top of this file.

    // Base namespaces. Required because every stub below attaches to them.
    if (!window.TYPO3) window.TYPO3 = {};
    if (!window.TYPO3.settings) window.TYPO3.settings = {};
    if (!window.TYPO3.lang) window.TYPO3.lang = {};
    if (!window.TYPO3.Backend) window.TYPO3.Backend = {};

    // Trigger: shortcut-menu.js reads top.TYPO3.Backend.Topbar.Toolbar
    //   unconditionally at load time.
    // Error without it:
    //   "TypeError: can't access property 'Toolbar', Topbar is undefined"
    //   (shortcut-menu.js line 13)
    if (!window.TYPO3.Backend.Topbar) {
        window.TYPO3.Backend.Topbar = {
            Toolbar: {
                registerEvent: function (cb) { try { cb(); } catch (e) {} },
                refresh: function () {}
            }
        };
    }

    // Trigger: TYPO3 backend modules call ContentContainer.setUrl() /
    //   refresh() / get() to navigate "the main content frame" after
    //   certain actions (save redirects, inline edits, field updates).
    //   We override setUrl to keep navigation INSIDE our iframe, and
    //   divert wizard URLs into our nested overlay instead of the
    //   native backend layout.
    if (!window.TYPO3.Backend.ContentContainer) {
        window.TYPO3.Backend.ContentContainer = {
            setUrl: function (url) {
                if (url && url.indexOf('/typo3/wizard/') !== -1) {
                    if (window.TYPO3.Modal && window.TYPO3.Modal.advanced) {
                        window.TYPO3.Modal.advanced({ content: url });
                    }
                    return Promise.resolve();
                }
                const iframe = getIframe();
                if (iframe) iframe.src = ensureReturnUrl(url);
                return Promise.resolve();
            },
            refresh: function () {
                const iframe = getIframe();
                if (iframe && iframe.contentWindow) iframe.contentWindow.location.reload();
                return Promise.resolve();
            },
            get: function () {
                return getIframe();
            }
        };
    }

    // Trigger: link browser, element browser, and file picker wizards
    //   inside the edit form call TYPO3.Modal.advanced({ content: wizardUrl })
    //   to open themselves. Without a stub they crash; without routing
    //   through our overlay they escape the iframe sandbox.
    // Also used: Modal.dismiss() when the wizard closes.
    // confirm() is kept because TCA delete/inline confirmations call it.
    if (!window.TYPO3.Modal) {
        window.TYPO3.Modal = {
            advanced: function (config) {
                const url = config && (config.content || config.url || '');
                if (url && url.indexOf('/typo3/wizard/') !== -1) {
                    openWizardOverlay(url);
                }
                return { addEventListener: function () {}, on: function () {} };
            },
            confirm: function (title, content) { return Promise.resolve(confirm(content)); },
            dismiss: function () {
                const iframe = getIframe();
                if (iframe && iframe.contentWindow) {
                    const overlay = iframe.contentWindow.document.getElementById(WIZARD_OVERLAY_ID);
                    if (overlay) overlay.remove();
                }
            },
            currentModal: null
        };
    }

    // Hydrate TYPO3.settings and TYPO3.lang from the JSON data block
    // rendered by BackendSettingsService.
    //
    // Triggers (all resolved by this hydration):
    //   - tree.js (v13.4 line 13): "can't access property 'tree_networkError',
    //     top.TYPO3.lang is undefined"
    //   - date-time-picker.js (line 13): "can't access property 'DateFormat',
    //     TYPO3.settings.DateTimePicker is undefined"
    //   - file-storage-browser.js (line 13): "can't access property
    //     'filestorage_tree_data', top.TYPO3.settings.ajaxUrls is undefined"
    //
    // The _frontendEditContext flag is a marker our own code can check to
    // detect it's running inside the iframe.
    const stubData = readStubData();
    Object.assign(window.TYPO3.settings, stubData.settings || {});
    Object.assign(window.TYPO3.lang, stubData.lang || {});
    window.TYPO3.Backend._frontendEditContext = true;

    // Trigger: TYPO3 backend uses a CustomEvent "typo3:import-javascript-module"
    //   as a dynamic-import indirection layer. Any module that does
    //   `top.document.dispatchEvent(new CustomEvent("typo3:import-javascript-module", ...))`
    //   waits for us to resolve `e.detail.importPromise`.
    // Without this listener: the module silently never loads (no error, but
    //   broken UI — e.g. CKEditor plugins, date pickers, tree components).
    document.addEventListener('typo3:import-javascript-module', function (e) {
        if (e.detail && e.detail.specifier) {
            e.detail.importPromise = import(e.detail.specifier).catch(function () { return {}; });
        }
    });
})();
