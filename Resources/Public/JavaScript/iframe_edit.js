/**
 * TYPO3 Frontend Edit - Modal Iframe Editor
 *
 * Opens TYPO3 backend editing forms inside a slide-in panel (iframe).
 * Backend stubs are initialized by BackendSettingsService (inline script)
 * which loads BEFORE this file.
 */
(function () {
  'use strict';

  // ── Constants ──────────────────────────────────────────────────────
  //
  // POLLING / MONKEY-PATCH POLICY (TYPO3 v13 only)
  // ──────────────────────────────────────────────
  // TYPO3 v13 does not expose a stable hook for embedded backend forms,
  // so we poll for internal objects (ContentContainer, wizard components)
  // and monkey-patch their methods to keep navigation inside our iframe.
  //
  // This is fragile by nature: any TYPO3 v13 patch release that renames
  // or refactors these internals silently breaks the feature. The
  // tradeoff is intentional and bounded by TYPO3 v13's lifetime — the
  // entire iframe modal is gated to v13 only (see ResourceRendererService).
  // On v14.2+ the contextual sidebar handles this natively, no polling.
  //
  // All timeouts/intervals are tuned to be just generous enough for slow
  // dev environments without burning CPU. We use waitFor() with a single
  // setTimeout-recursion + cap, not setInterval, so failed waits stop
  // cleanly instead of accumulating handles.

  const MODAL_ID = 'xima-typo3-frontend-edit-modal';
  const IFRAME_ID = 'xima-typo3-frontend-edit-modal-iframe';
  const ANIMATION_DELAY_MS = 10;
  const ANIMATION_DURATION_MS = 300;

  // ContentContainer is created by TYPO3 backend modules during iframe
  // boot. We need to override setUrl before any backend code calls it
  // (typically within ~50-200ms on a normal machine).
  const CONTENT_CONTAINER_POLL_MS = 25;       // every 25ms
  const CONTENT_CONTAINER_TIMEOUT_MS = 3000;  // give up after 3s

  // Wizard custom elements (<typo3-backend-new-content-element-wizard>)
  // are upgraded asynchronously after the iframe page renders.
  const WIZARD_PATCH_POLL_MS = 100;           // every 100ms
  const WIZARD_PATCH_TIMEOUT_MS = 5000;       // give up after 5s

  // Fixed wait before auto-clicking a wizard button (give the wizard
  // time to render its items after the page loads).
  const WIZARD_CLICK_DELAY_MS = 800;

  const WIZARD_SELECTORS = 'typo3-backend-new-record-wizard, typo3-backend-new-content-element-wizard';

  /**
   * Poll until predicate() returns truthy or timeout elapses.
   * Cleaner than raw setInterval — single timeout chain, no leaked
   * handles, automatic stop on success or timeout.
   *
   * @param {() => *} predicate Called repeatedly; truthy return = done.
   * @param {number} intervalMs Delay between attempts.
   * @param {number} timeoutMs  Total budget before giving up.
   */
  function waitFor(predicate, intervalMs, timeoutMs) {
    const deadline = Date.now() + timeoutMs;
    function tick() {
      try { if (predicate()) return; } catch (_) { /* ignore */ }
      if (Date.now() < deadline) setTimeout(tick, intervalMs);
    }
    tick();
  }

  // ── Helpers ─────────────────────────────────────────────────────────

  const Logger = {
    log(message, data = null, level = 'log') {
      if (!window.FRONTEND_EDIT_DEBUG) return;
      const prefix = '%c[frontend-edit-modal]%c';
      const styles = ['font-weight: bold;', 'font-weight: normal;'];
      data !== null
        ? console[level](prefix, ...styles, message, data)
        : console[level](prefix, ...styles, message);
    }
  };

  function isBackendUrl(url) {
    return url && url.includes('/typo3/');
  }

  function isFrontendUrl(url) {
    return url && !url.includes('/typo3/') && url.includes(window.location.hostname);
  }

  function isPageLayoutPath(href, origin) {
    try {
      const { pathname } = new URL(href, origin);
      return pathname.includes('web/layout') || pathname.includes('web_layout');
    } catch (_) {
      return false;
    }
  }

  function isWizardUrl(url) {
    return url && url.includes('/typo3/wizard/');
  }

  /**
   * Ensure a backend URL has:
   * 1. A returnUrl pointing to our frontend page (not a backend route)
   *    with tx_ximatypo3frontendedit_iframe=1 marker so the post-save
   *    redirect does not consume the flash queue inside the iframe
   * 2. The tx_ximatypo3frontendedit marker so the Save & Close button is added
   *
   * Single source of truth in backend_stubs.js. Both files are v13-only
   * and backend_stubs.js loads first, so window.XimaFrontendEdit is always
   * available. If it isn't, that's a real loading bug we want to surface
   * loudly rather than silently branch into a drift-prone copy.
   */
  function ensureReturnUrl(url) {
    if (window.XimaFrontendEdit?.ensureReturnUrl) {
      return window.XimaFrontendEdit.ensureReturnUrl(url);
    }
    Logger.log('window.XimaFrontendEdit.ensureReturnUrl missing — backend_stubs.js did not load', null, 'error');
    return url;
  }

  // ── Modal ──────────────────────────────────────────────────────────

  const Modal = {
    element: null,
    iframe: null,
    closeTimer: null,

    getOrCreate() {
      if (this.element) return this.element;

      const modal = document.createElement('div');
      modal.id = MODAL_ID;
      modal.className = 'frontend-edit__modal';
      modal.innerHTML =
        '<div class="frontend-edit__modal-overlay"></div>' +
        '<div class="frontend-edit__modal-panel">' +
          '<div class="frontend-edit__modal-header">' +
            '<span class="frontend-edit__modal-title"></span>' +
            '<button class="frontend-edit__modal-close" title="Close">&times;</button>' +
          '</div>' +
          '<div class="frontend-edit__modal-content">' +
            '<div class="frontend-edit__modal-loader">' +
              '<div class="frontend-edit__modal-spinner"></div>' +
            '</div>' +
            '<iframe id="' + IFRAME_ID + '" class="t3js-scaffold-content-module-iframe" allow="fullscreen"></iframe>' +
          '</div>' +
        '</div>';
      document.body.appendChild(modal);

      const close = () => this.close();
      modal.querySelector('.frontend-edit__modal-overlay').addEventListener('click', close);
      modal.querySelector('.frontend-edit__modal-close').addEventListener('click', close);
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

      this.element = modal;
      this.iframe = modal.querySelector('#' + IFRAME_ID);
      Logger.log('Modal created');
      return modal;
    },

    open(url) {
      // Ensure the returnUrl carries tx_ximatypo3frontendedit_iframe=1
      // so the post-save redirect to the frontend URL does not consume the
      // flash message queue inside the iframe — the parent reload picks
      // them up instead.
      url = ensureReturnUrl(url);

      // Cancel any pending close-timeout from a prior close() so it can't
      // clobber the fresh iframe we're about to open.
      if (this.closeTimer) {
        clearTimeout(this.closeTimer);
        this.closeTimer = null;
      }

      this.getOrCreate();
      IframeHandler._wizardAutoClicked = false;
      const { iframe } = this;
      const loader = this.element.querySelector('.frontend-edit__modal-loader');

      iframe.style.opacity = '0';
      if (loader) loader.style.display = 'flex';

      IframeHandler.overrideContentContainer(iframe);
      iframe.src = url;

      // Keep the close button always reachable as an escape route (ESC and
      // backdrop click aren't discoverable if the TYPO3-native close fails
      // to render). Only hide the title text for edit forms, where the
      // record title lives inside the iframe's own header.
      const showTitle = /record\/(info|history)|move_element/.test(url);
      const title = this.element.querySelector('.frontend-edit__modal-title');
      if (title) {
        title.style.display = showTitle ? '' : 'none';
      }

      setTimeout(() => this.element.classList.add('frontend-edit__modal--open'), ANIMATION_DELAY_MS);

      iframe.onload = () => {
        // Detect frontend URL early — stop scripts before they break
        try {
          const href = iframe.contentWindow?.location.href;
          if (href && isFrontendUrl(href)) {
            Logger.log('Frontend URL detected on load — aborting iframe');
            iframe.src = 'about:blank';
            IframeHandler.closeAndReload();
            return;
          }
        } catch (_) { /* cross-origin */ }

        if (loader) loader.style.display = 'none';
        iframe.style.display = '';
        iframe.style.opacity = '1';
        Logger.log('Iframe loaded');
        IframeHandler.onLoad(iframe);
      };

      iframe.onerror = () => {
        Logger.log('Iframe failed to load', null, 'error');
        setTimeout(() => this.close(), 2000);
      };

      Logger.log('Modal opened', { url });
    },

    close() {
      if (!this.element) return;
      Logger.log('Closing modal');
      this.element.classList.remove('frontend-edit__modal--open');

      // Replace any previous pending cleanup with a fresh one so we never
      // have two racing timeouts. The callback also double-checks that the
      // modal is still closed before wiping the iframe, so a re-open()
      // between schedule and fire is safe.
      if (this.closeTimer) {
        clearTimeout(this.closeTimer);
      }
      this.closeTimer = setTimeout(() => {
        this.closeTimer = null;
        if (!this.element || this.element.classList.contains('frontend-edit__modal--open')) return;
        if (this.iframe) {
          this.iframe.src = 'about:blank';
          this.iframe.style.opacity = '0.5';
        }
      }, ANIMATION_DURATION_MS);
    }
  };

  // ── IframeHandler ──────────────────────────────────────────────────

  const IframeHandler = {

    // ── Lifecycle ───────────────────────────────────────────────────

    onLoad(iframe) {
      this.overrideContentContainer(iframe);
      this.ensureBackendModules(iframe);
      this.autoClickWizardButton(iframe);
      this.patchWizardComponents(iframe);
      this.interceptIframeClicks(iframe);
      this.detectFrontendNavigation(iframe);
      this.hideUnnecessaryButtons(iframe);
    },

    /**
     * Ensure critical backend modules are loaded inside the iframe.
     *
     * In the standard TYPO3 backend, the scaffold (top frame) loads shared
     * modules like Bootstrap. Our iframe has no scaffold — the top frame is
     * the frontend page. Modules that the scaffold normally provides must
     * be imported explicitly. ES modules are idempotent, so duplicate
     * imports are harmless (resolved from cache immediately).
     *
     * Uses the page's CSP nonce so the injected script passes Content
     * Security Policy checks.
     */
    ensureBackendModules(iframe) {
      try {
        const doc = iframe.contentWindow?.document;
        if (!doc) return;

        // Read the importmap to resolve the actual Bootstrap URL.
        // Loading via src (not inline content) avoids CSP unsafe-inline
        // restrictions — the URL is same-origin so script-src 'self' allows it.
        const mapEl = doc.querySelector('script[type="importmap"]');
        if (!mapEl) return;
        const map = JSON.parse(mapEl.textContent || '{}');
        const bootstrapUrl = map.imports?.bootstrap;
        if (!bootstrapUrl) return;

        const script = doc.createElement('script');
        script.type = 'module';
        script.src = bootstrapUrl;
        doc.head.appendChild(script);
        Logger.log('Bootstrap loaded from importmap', { url: bootstrapUrl });
      } catch (_) { /* cross-origin */ }
    },

    /**
     * Hide buttons that are not useful inside the frontend-edit modal
     * (e.g. the "View" button which links back to the frontend page).
     */
    hideUnnecessaryButtons(iframe) {
      try {
        const doc = iframe.contentWindow?.document;
        if (!doc) return;
        doc.querySelectorAll('.t3js-editform-view').forEach(el => el.remove());
      } catch (_) { /* cross-origin */ }
    },

    /**
     * Override ContentContainer.setUrl inside the iframe so backend navigation
     * stays within our modal iframe instead of targeting the parent window.
     *
     * ContentContainer is created asynchronously by TYPO3 backend modules
     * during iframe boot, so we have to wait for it before patching.
     */
    overrideContentContainer(iframe) {
      waitFor(() => {
        const cc = iframe.contentWindow?.TYPO3?.Backend?.ContentContainer;
        if (!cc || cc._overridden) return cc?._overridden === true;
        cc._overridden = true;
        const setUrl = (url) => {
          if (isFrontendUrl(url)) {
            IframeHandler.closeAndReload();
            return Promise.resolve({});
          }
          if (isWizardUrl(url)) {
            Logger.log('Wizard URL in ContentContainer.setUrl — opening overlay', { url });
            IframeHandler.openWizardOverlay(iframe, url);
            return Promise.resolve({});
          }
          iframe.src = ensureReturnUrl(url);
          return Promise.resolve({});
        };
        try {
          Object.defineProperty(cc, 'setUrl', { value: setUrl, writable: true, configurable: true });
        } catch (_) {
          cc.setUrl = setUrl;
        }
        Logger.log('ContentContainer.setUrl overridden');
        return true;
      }, CONTENT_CONTAINER_POLL_MS, CONTENT_CONTAINER_TIMEOUT_MS);
    },

    // ── Wizard auto-click ──────────────────────────────────────────

    _wizardAutoClicked: false,

    autoClickWizardButton(iframe) {
      try {
        if (this._wizardAutoClicked) return;

        const currentUrl = (iframe.contentWindow?.location.href || iframe.src || '');
        const hashPart = currentUrl.split('#')[1];
        if (!hashPart) return;

        const params = new URLSearchParams(hashPart);
        const colPos = params.get('colPos');
        const container = params.get('container');
        const afterUid = params.get('afterUid');
        if (!colPos) return;

        Logger.log('Auto-click wizard detected', { colPos, container, afterUid });

        setTimeout(() => {
          try {
            const buttons = iframe.contentWindow.document.querySelectorAll(
              'typo3-backend-new-content-element-wizard-button'
            );
            for (const btn of buttons) {
              if (this.matchesWizardButton(btn.getAttribute('url') || '', colPos, container, afterUid)) {
                this._wizardAutoClicked = true;
                btn.click();
                return;
              }
            }
          } catch (e) { Logger.log('Error clicking wizard button', e, 'error'); }
        }, WIZARD_CLICK_DELAY_MS);
      } catch (_) { /* ignore */ }
    },

    matchesWizardButton(btnUrl, colPos, container, afterUid) {
      const colPosMatch = btnUrl.match(/colPos=(\d+)/);

      if (afterUid) {
        const m = btnUrl.match(/uid_pid=-(\d+)/);
        return m && m[1] === afterUid && colPosMatch && colPosMatch[1] === colPos;
      }
      if (container) {
        const m = btnUrl.match(/tx_container_parent=(\d+)/);
        return colPosMatch && colPosMatch[1] === colPos && m && m[1] === container;
      }
      return colPosMatch && colPosMatch[1] === colPos && !btnUrl.includes('tx_container_parent');
    },

    // ── Wizard component patching ──────────────────────────────────

    /**
     * Patch wizard custom elements inside the iframe so they don't escape
     * our iframe context. Wizards are upgraded asynchronously, so we poll.
     */
    patchWizardComponents(iframe) {
      try {
        const win = iframe.contentWindow;
        if (!win) return;

        // Poll for wizard components and patch any we haven't seen yet.
        // Returns false (keep polling) until the timeout elapses — wizards
        // can appear at different times as the form renders.
        waitFor(() => {
          for (const wizard of win.document.querySelectorAll(WIZARD_SELECTORS)) {
            if (wizard._frontendEditPatched) continue;
            wizard._frontendEditPatched = true;
            const original = wizard.handleItemClick;
            wizard.handleItemClick = function (item) {
              if (item?.url) { win.location.href = ensureReturnUrl(item.url); return; }
              if (original) return original.call(this, item);
            };
            Logger.log('Wizard component patched');
          }
          return false; // keep polling until timeout
        }, WIZARD_PATCH_POLL_MS, WIZARD_PATCH_TIMEOUT_MS);

        this.installWizardClickInterception(win);
      } catch (_) { /* ignore */ }
    },

    installWizardClickInterception(win) {
      try {
        const doc = win.document;
        if (!doc?.body || doc._wizardInterceptionInstalled) return;
        doc._wizardInterceptionInstalled = true;

        doc.addEventListener('click', (e) => {
          const wizard = e.target.closest(WIZARD_SELECTORS);
          if (!wizard?.items) return;
          const button = e.target.closest('button');
          if (!button) return;

          const identifier = button.getAttribute('data-identifier');
          const label = (button.querySelector('.item-label') || button).textContent.trim();

          for (const key in wizard.items) {
            const category = wizard.items[key];
            if (!category?.items) continue;
            for (const item of category.items) {
              const matches = (identifier && item.identifier === identifier)
                || (label && item.label?.includes(label));
              if (matches && item.url) {
                e.preventDefault();
                e.stopImmediatePropagation();
                win.location.href = ensureReturnUrl(item.url);
                return;
              }
            }
          }
        }, true);

        // Click handler is delegated at document level, so it catches wizards
        // rendered later (no MutationObserver needed — the observer re-fired
        // on every DOM mutation which was wasted work on big forms).
        Logger.log('Wizard click interception installed');
      } catch (_) { /* ignore */ }
    },

    // ── Iframe click routing ───────────────────────────────────────

    /**
     * Route clicks inside the iframe through an ordered list of handlers.
     * Each handler returns `true` if it fully handled the event (stop),
     * or `false` to let the next handler try.
     */
    interceptIframeClicks(iframe) {
      try {
        const doc = iframe.contentWindow?.document;
        if (!doc) return;

        const handlers = [
          this._handleSaveCloseClick.bind(this),
          this._handleSaveClick.bind(this),
          this._handleFileSelectorClick.bind(this),
          this._handleNativeBrowserModalClick.bind(this),
          this._handleEditFormCloseClick.bind(this),
          this._handlePageLayoutNavClick.bind(this),
          this._handleFrontendLinkClick.bind(this),
          this._handleWizardLinkClick.bind(this),
          this._handleBackendLinkClick.bind(this),
        ];

        doc.addEventListener('click', (e) => {
          for (const handler of handlers) {
            if (handler(e, iframe)) return;
          }
        }, true);
      } catch (_) { /* cross-origin */ }
    },

    // ── Click handlers (return true if handled) ─────────────────────

    _handleSaveCloseClick(e) {
      if (!e.target.closest('[data-js="save-close"], [name="_saveandclosedok"]')) return false;
      Logger.log('Save & Close button clicked, showing modal loader');
      this.showModalLoader();
      return true; // click passes through — TYPO3 handles the form submit
    },

    _handleSaveClick(e) {
      if (!e.target.closest('[data-js="save"], [name="_savedok"]')) return false;
      Logger.log('Save button clicked');
      // Don't show the loader here — it covers the iframe while TYPO3
      // re-initializes CKEditor on the new form, and CKEditor's
      // _removeDomSelection crashes when the iframe isn't visible/focused
      // (window.getSelection() returns null).
      return true; // click passes through — TYPO3 handles the form submit
    },

    _handleFileSelectorClick(e) {
      // Structural selectors only — no i18n-fragile title matching
      const fileTarget = e.target.closest(
        'button[data-file-irre-object], a[href*="wizard/element/browser"], [data-mode="file"], [data-toggle="formengine-inline"]'
      );
      if (!fileTarget) return false;
      this.handleFileSelector(e, fileTarget);
      return true;
    },

    _handleNativeBrowserModalClick(e) {
      // TYPO3 form engine controls (link popup, element browser, etc.) open
      // their own Modal natively — don't interfere.
      return !!e.target.closest('.t3js-element-browser, a[href*="wizard/link"]');
    },

    _handleEditFormCloseClick(e) {
      const link = e.target.closest('a[href]');
      if (!link || !link.matches('.t3js-editform-close')) return false;
      e.preventDefault();
      e.stopPropagation();
      Logger.log('Close button clicked');
      this.closeAndReload();
      return true;
    },

    _handlePageLayoutNavClick(e, iframe) {
      const link = e.target.closest('a[href]');
      if (!link) return false;
      const href = link.getAttribute('href') || '';
      if (!isPageLayoutPath(href, iframe.contentWindow.location.origin)) return false;
      e.preventDefault();
      e.stopPropagation();
      Logger.log('Page layout link clicked');
      this.closeAndReload();
      return true;
    },

    _handleFrontendLinkClick(e) {
      const link = e.target.closest('a[href]');
      if (!link) return false;
      const href = link.getAttribute('href') || '';
      if (!isFrontendUrl(href) || href.includes('about:blank')) return false;
      e.preventDefault();
      e.stopPropagation();
      this.closeAndReload();
      return true;
    },

    _handleWizardLinkClick(e, iframe) {
      const link = e.target.closest('a[href]');
      if (!link) return false;
      const href = link.getAttribute('href') || '';
      if (!isWizardUrl(href)) return false;
      e.preventDefault();
      e.stopPropagation();
      IframeHandler.openWizardOverlay(iframe, link.href);
      return true;
    },

    _handleBackendLinkClick(e, iframe) {
      const link = e.target.closest('a[href]');
      if (!link) return false;
      const href = link.getAttribute('href') || '';
      if (!isBackendUrl(href)) return false;
      e.preventDefault();
      e.stopPropagation();
      Logger.log('Backend link → navigating iframe', { href });
      iframe.contentWindow.location.href = ensureReturnUrl(link.href);
      return true;
    },

    handleFileSelector(e, target) {
      const rawUrl = target.getAttribute('href')
        || target.getAttribute('data-url')
        || target.getAttribute('onclick');
      if (!rawUrl) return;

      e.preventDefault();
      e.stopPropagation();

      let fileUrl = rawUrl;
      if (rawUrl.includes('window.open') || rawUrl.includes('location.href')) {
        const match = rawUrl.match(/['"]([^'"]+)['"]/);
        if (match) fileUrl = match[1];
      }
      Logger.log('Opening file selector in popup', fileUrl);
      window.open(fileUrl, '_blank', 'width=1200,height=800');
    },

    // ── Wizard overlay ────────────────────────────────────────────

    /**
     * Open a wizard URL (e.g. link browser) in a nested overlay inside
     * the iframe. Delegates to the single shared implementation in
     * backend_stubs.js (window.XimaFrontendEdit.openWizardOverlay) so
     * there's no divergent duplicate.
     */
    openWizardOverlay(iframe, wizardUrl) {
      if (window.XimaFrontendEdit?.openWizardOverlay) {
        window.XimaFrontendEdit.openWizardOverlay(wizardUrl);
        Logger.log('Wizard overlay opened', { url: wizardUrl });
        return;
      }
      Logger.log('window.XimaFrontendEdit.openWizardOverlay missing — backend_stubs.js did not load', null, 'error');
    },

    // ── Navigation detection ───────────────────────────────────────

    detectFrontendNavigation(iframe) {
      try {
        const currentHref = iframe.contentWindow.location.href;
        if (isFrontendUrl(currentHref)) {
          Logger.log('Detected frontend URL in iframe');
          this.closeAndReload();
          return;
        }
        // After a wizard auto-click + save cycle, navigating back to
        // web_layout means the record was saved — close and reload.
        if (this._wizardAutoClicked && isPageLayoutPath(currentHref, iframe.contentWindow.location.origin)) {
          Logger.log('Detected page layout after wizard save — closing modal');
          this.closeAndReload();
        }
      } catch (_) { /* cross-origin */ }
    },

    /**
     * Extract the content element UID from the iframe's edit form URL.
     * Pattern: edit[tt_content][{uid}]=edit (URL-encoded or plain).
     */
    getEditedElementUid() {
      try {
        const url = Modal.iframe?.contentWindow?.location.href;
        if (!url) return null;
        const match = url.match(/edit%5Btt_content%5D%5B(\d+)%5D=edit/i)
          || url.match(/edit\[tt_content\]\[(\d+)\]=edit/i);
        return match ? match[1] : null;
      } catch (_) {
        return null;
      }
    },

    /**
     * Show black overlay with spinner INSIDE the modal (covers the iframe).
     * Called on save button click — modal stays open, user sees spinner.
     */
    showModalLoader() {
      const loader = Modal.element?.querySelector('.frontend-edit__modal-loader');
      if (loader) {
        loader.style.display = 'flex';
      }
      if (Modal.iframe) {
        Modal.iframe.style.display = 'none';
      }
    },

    /**
     * Reload the page. The modal loader is already visible at this point.
     *
     * Fully destroys the iframe before reloading — otherwise the live iframe
     * can consume session-based flash messages (or trigger other side effects)
     * in the brief window between reload trigger and parent navigation.
     */
    closeAndReload(scrollToEdited = false) {
      let uid = null;
      if (scrollToEdited) uid = this.getEditedElementUid();

      // Show modal loader in case it wasn't shown yet (e.g. close without save)
      this.showModalLoader();

      // Fully remove the iframe from the DOM so it can no longer execute or
      // consume shared session state (flash messages, csrf tokens, etc.).
      if (Modal.iframe) {
        Modal.iframe.src = 'about:blank';
        if (Modal.iframe.parentNode) {
          Modal.iframe.parentNode.removeChild(Modal.iframe);
        }
        Modal.iframe = null;
      }

      if (uid) {
        const url = new URL(window.location.href);
        url.searchParams.set('scrollToContent', uid);
        url.hash = '';
        window.location.href = url.toString();
      } else {
        window.location.reload();
      }
    }
  };

  // ── LinkInterceptor (frontend page clicks → open modal) ─────────

  const LinkInterceptor = {
    init() {
      document.addEventListener('click', (e) => {
        const target =
          e.target.closest('.frontend-edit__btn--edit') ||
          e.target.closest('.frontend-edit__dropdown a:not(.hide):not(.delete)') ||
          e.target.closest('.frontend-edit__sticky-dropdown a') ||
          e.target.closest('.frontend-edit__column-btn') ||
          e.target.closest('.frontend-edit__open-modal');

        if (target?.href && isBackendUrl(target.href)) {
          e.preventDefault();
          e.stopPropagation();
          Modal.open(target.href);
          Logger.log('Link intercepted → modal', { href: target.href });
        }
      }, true);
    }
  };

  // ── Message Handler (save & close from iframe) ──────────────────

  window.addEventListener('message', (event) => {
    if (typeof event.data !== 'object' || !event.data) return;

    // Only accept messages from the active modal iframe's origin.
    // Compare against the iframe's src origin (not window.location.origin)
    // so setups where backend and frontend live on different domains still
    // work correctly (e.g. backend.example.com vs www.example.com).
    if (!Modal.iframe) return;
    let iframeOrigin;
    try {
      iframeOrigin = new URL(Modal.iframe.src, window.location.href).origin;
    } catch (_) {
      return;
    }
    if (event.origin !== iframeOrigin) return;

    const action = event.data.actionName;
    if (action === 'typo3:formengine:save-close') {
      Logger.log('Save & close triggered from iframe');
      IframeHandler.closeAndReload(true);
    } else if (action === 'typo3:formengine:close') {
      Logger.log('Close triggered from iframe');
      IframeHandler.closeAndReload(false);
    }
  });

  // ── Init ───────────────────────────────────────────────────────────

  LinkInterceptor.init();
  Logger.log('Modal edit system initialized');
})();