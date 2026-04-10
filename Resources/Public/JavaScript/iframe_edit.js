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

  const MODAL_ID = 'xima-typo3-frontend-edit-modal';
  const IFRAME_ID = 'xima-typo3-frontend-edit-modal-iframe';
  const ANIMATION_DELAY_MS = 10;
  const ANIMATION_DURATION_MS = 300;
  const WIZARD_CLICK_DELAY_MS = 1000;
  const CONTENT_CONTAINER_POLL_MS = 5;
  const CONTENT_CONTAINER_MAX_POLLS = 1000;
  const WIZARD_PATCH_POLL_MS = 50;
  const WIZARD_PATCH_MAX_POLLS = 200;
  const WIZARD_SELECTORS = 'typo3-backend-new-record-wizard, typo3-backend-new-content-element-wizard';

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
   * 2. The tx_ximatypo3frontendedit marker so the Save & Close button is added
   */
  function ensureReturnUrl(url) {
    try {
      const u = new URL(url, window.location.origin);
      const existing = u.searchParams.get('returnUrl') || '';
      if (!existing || existing.includes('/typo3/')) {
        u.searchParams.set('returnUrl', window.location.href);
      }
      if (!u.searchParams.has('tx_ximatypo3frontendedit')) {
        u.searchParams.set('tx_ximatypo3frontendedit', '');
      }
      return u.toString();
    } catch (_) {
      return url;
    }
  }

  // ── Modal ──────────────────────────────────────────────────────────

  const Modal = {
    element: null,
    iframe: null,

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
      this.getOrCreate();
      IframeHandler._wizardAutoClicked = false;
      const { iframe } = this;
      const loader = this.element.querySelector('.frontend-edit__modal-loader');

      iframe.style.opacity = '0';
      if (loader) loader.style.display = 'flex';

      IframeHandler.overrideContentContainer(iframe);
      iframe.src = url;

      // Only show header for info modals
      const header = this.element.querySelector('.frontend-edit__modal-header');
      if (header) {
        header.style.display = url.includes('record/info') ? 'flex' : 'none';
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
      setTimeout(() => {
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
      this.autoClickWizardButton(iframe);
      this.patchWizardComponents(iframe);
      this.interceptIframeClicks(iframe);
      this.detectFrontendNavigation(iframe);
      this.hideUnnecessaryButtons(iframe);
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
     */
    overrideContentContainer(iframe) {
      let attempts = 0;
      const interval = setInterval(() => {
        attempts++;
        try {
          const cc = iframe.contentWindow?.TYPO3?.Backend?.ContentContainer;
          if (!cc || cc._overridden) { if (cc?._overridden) clearInterval(interval); return; }
          cc._overridden = true;
          const setUrl = (url) => {
            if (isFrontendUrl(url)) {
              IframeHandler.closeAndReload();
              return Promise.resolve();
            }
            if (isWizardUrl(url)) {
              Logger.log('Wizard URL in ContentContainer.setUrl — opening overlay', { url });
              IframeHandler.openWizardOverlay(iframe, url);
              return Promise.resolve();
            }
            iframe.src = ensureReturnUrl(url);
            return Promise.resolve();
          };
          try {
            Object.defineProperty(cc, 'setUrl', { value: setUrl, writable: true, configurable: true });
          } catch (_) {
            cc.setUrl = setUrl;
          }
          Logger.log('ContentContainer.setUrl overridden');
          clearInterval(interval);
        } catch (_) { /* cross-origin */ }
        if (attempts >= CONTENT_CONTAINER_MAX_POLLS) clearInterval(interval);
      }, CONTENT_CONTAINER_POLL_MS);
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

    patchWizardComponents(iframe) {
      try {
        const win = iframe.contentWindow;
        if (!win) return;

        let attempts = 0;
        const interval = setInterval(() => {
          attempts++;
          try {
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
          } catch (_) { /* ignore */ }
          if (attempts >= WIZARD_PATCH_MAX_POLLS) clearInterval(interval);
        }, WIZARD_PATCH_POLL_MS);

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

        if (doc.body) {
          new MutationObserver(() => this.installWizardClickInterception(win))
            .observe(doc.body, { childList: true, subtree: true });
        }
        Logger.log('Wizard click interception installed');
      } catch (_) { /* ignore */ }
    },

    // ── Iframe click routing ───────────────────────────────────────

    /**
     * Route clicks inside the iframe:
     *   1. File selector        → popup window
     *   2. Edit form close      → close modal, reload frontend
     *   3. Page layout nav      → close modal, reload frontend
     *   4. Frontend link        → close modal, reload frontend
     *   5. Any other backend <a>→ navigate iframe directly
     *      (bypasses TYPO3's content-container.js which breaks in nested iframes)
     */
    interceptIframeClicks(iframe) {
      try {
        const doc = iframe.contentWindow?.document;
        if (!doc) return;

        doc.addEventListener('click', (e) => {
          // 0. Save & close button → show loader (modal will close after save)
          const saveCloseBtn = e.target.closest('[data-js="save-close"], [name="_saveandclosedok"]');
          if (saveCloseBtn) {
            Logger.log('Save & Close button clicked, showing modal loader');
            this.showModalLoader();
            return; // Let the click through — TYPO3 handles the form submit
          }

          // 0b. Regular Save button → let TYPO3 handle without hiding the iframe
          const saveBtn = e.target.closest('[data-js="save"], [name="_savedok"]');
          if (saveBtn) {
            Logger.log('Save button clicked');
            return; // Let the click through — TYPO3 handles the form submit
          }

          // 1. File selector → popup
          const fileTarget = e.target.closest(
            'button[data-file-irre-object], a[href*="wizard/element/browser"], button[title*="Select"]'
          );
          if (fileTarget) { this.handleFileSelector(e, fileTarget); return; }

          // 1b. TYPO3 form engine controls (link popup, element browser, etc.) open
          //     their own Modal — skip so TYPO3's JS handles the click natively
          if (e.target.closest('.t3js-element-browser, a[href*="wizard/link"]')) return;

          const link = e.target.closest('a[href]');
          if (!link) return;
          const href = link.getAttribute('href') || '';

          // 2. Edit form close button
          if (link.matches('.t3js-editform-close')) {
            e.preventDefault();
            e.stopPropagation();
            Logger.log('Close button clicked');
            this.closeAndReload();
            return;
          }

          // 3. Direct navigation to page layout → return to frontend
          if (isPageLayoutPath(href, iframe.contentWindow.location.origin)) {
            e.preventDefault();
            e.stopPropagation();
            Logger.log('Page layout link clicked');
            this.closeAndReload();
            return;
          }

          // 4. Frontend link
          if (isFrontendUrl(href) && !href.includes('about:blank')) {
            e.preventDefault();
            e.stopPropagation();
            this.closeAndReload();
            return;
          }

          // 5. Wizard links (link browser, etc.) → open in wizard overlay
          if (isWizardUrl(href)) {
            e.preventDefault();
            e.stopPropagation();
            IframeHandler.openWizardOverlay(iframe, link.href);
            return;
          }

          // 6. Backend link → navigate iframe directly
          if (isBackendUrl(href)) {
            e.preventDefault();
            e.stopPropagation();
            Logger.log('Backend link → navigating iframe', { href });
            iframe.contentWindow.location.href = ensureReturnUrl(link.href);
          }
        }, true);
      } catch (_) { /* cross-origin */ }
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
     * Open a wizard URL (e.g. link browser) in an overlay inside the
     * iframe's document. This keeps `parent` pointing at the edit form
     * so TYPO3's callbacks (field value updates) work correctly.
     */
    openWizardOverlay(iframe, wizardUrl) {
      try {
        const doc = iframe.contentWindow.document;

        // Remove existing overlay
        const existing = doc.getElementById('fe-wizard-overlay');
        if (existing) existing.remove();

        const overlay = doc.createElement('div');
        overlay.id = 'fe-wizard-overlay';
        overlay.style.cssText =
          'position:fixed;top:0;left:0;width:100%;height:100%;z-index:99999;' +
          'background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;';

        const panel = doc.createElement('div');
        panel.style.cssText =
          'width:80%;max-width:900px;height:80%;background:#fff;border-radius:4px;' +
          'overflow:hidden;position:relative;display:flex;flex-direction:column;' +
          'box-shadow:0 4px 24px rgba(0,0,0,0.3);';

        const header = doc.createElement('div');
        header.style.cssText =
          'display:flex;justify-content:flex-end;padding:4px 8px;background:#eee;flex-shrink:0;';

        const closeBtn = doc.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText =
          'font-size:22px;background:none;border:none;cursor:pointer;padding:2px 8px;line-height:1;';
        closeBtn.onclick = () => overlay.remove();

        const wizIframe = doc.createElement('iframe');
        wizIframe.src = wizardUrl;
        wizIframe.style.cssText = 'flex:1;border:none;width:100%;';

        header.appendChild(closeBtn);
        panel.appendChild(header);
        panel.appendChild(wizIframe);
        overlay.appendChild(panel);

        // Close on backdrop click
        overlay.addEventListener('click', (e) => {
          if (e.target === overlay) overlay.remove();
        });

        doc.body.appendChild(overlay);
        Logger.log('Wizard overlay opened', { url: wizardUrl });

        // Patch Modal.dismiss so the link browser can close the overlay
        this._patchDismissForOverlay(iframe, overlay, wizIframe);

      } catch (err) {
        Logger.log('Failed to create wizard overlay — falling back to popup', err, 'error');
        try {
          iframe.contentWindow.open(wizardUrl, '_blank', 'width=1000,height=700');
        } catch (_) { /* ignore */ }
      }
    },

    /**
     * Patch TYPO3.Modal.dismiss on parent and iframe windows so the
     * link browser can close the wizard overlay after selecting a link.
     */
    _patchDismissForOverlay(iframe, overlay, wizIframe) {
      const patchDismiss = (modalObj) => {
        if (!modalObj) return;
        const orig = modalObj.dismiss;
        modalObj.dismiss = function () {
          if (overlay.parentNode) overlay.remove();
          modalObj.dismiss = orig || function () {};
          if (typeof orig === 'function') orig.call(this);
        };
      };

      // Parent window (for top.TYPO3.Modal.dismiss calls)
      patchDismiss(window.TYPO3?.Modal);

      // Main iframe (for parent.TYPO3.Modal.dismiss calls from wizard)
      try { patchDismiss(iframe.contentWindow?.TYPO3?.Modal); } catch (_) {}

      // Wizard iframe after it loads (for local Modal.dismiss calls)
      wizIframe.addEventListener('load', () => {
        try {
          const wizWin = wizIframe.contentWindow;
          // Try to patch immediately, then poll briefly
          const tryPatch = () => {
            if (wizWin.TYPO3?.Modal) { patchDismiss(wizWin.TYPO3.Modal); return true; }
            return false;
          };
          if (!tryPatch()) {
            let attempts = 0;
            const interval = setInterval(() => {
              if (tryPatch() || ++attempts > 40) clearInterval(interval);
            }, 100);
          }
        } catch (_) { /* cross-origin */ }
      });
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
     */
    closeAndReload(scrollToEdited = false) {
      let uid = null;
      if (scrollToEdited) uid = this.getEditedElementUid();

      // Show modal loader in case it wasn't shown yet (e.g. close without save)
      this.showModalLoader();

      // Stop the iframe immediately to prevent the frontend page from loading
      // inside it (GSAP, CKEditor, etc. break when running in an iframe context)
      if (Modal.iframe) {
        Modal.iframe.src = 'about:blank';
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