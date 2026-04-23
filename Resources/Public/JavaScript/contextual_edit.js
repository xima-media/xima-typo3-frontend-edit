/**
 * TYPO3 Frontend Edit - Contextual Editing Sidebar
 * Experimental feature for TYPO3 v14.2+ that enables inline editing
 * via an iframe-based sidebar panel.
 *
 * Loaded conditionally when frontendEdit.enableContextualEditing is enabled.
 * Exposes window.ContextualEdit for use by frontend_edit.js and sticky_toolbar.js.
 */
(function () {
  'use strict';

  var CLOSE_ICON = '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.75.75 0 1 1 1.06 1.06L9.06 8l3.22 3.22a.75.75 0 1 1-1.06 1.06L8 9.06l-3.22 3.22a.75.75 0 0 1-1.06-1.06L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06z"/></svg>';

  function log(msg, data, level) {
    if (!window.FRONTEND_EDIT_DEBUG) return;
    var method = level === 'warn' ? 'warn' : level === 'error' ? 'error' : 'log';
    if (data) {
      console[method]('[ContextualEdit] ' + msg, data);
    } else {
      console[method]('[ContextualEdit] ' + msg);
    }
  }

  var ContextualEdit = {
    sidebar: null,
    iframe: null,
    backdrop: null,
    loader: null,
    resetTimeout: null,
    closeTimeout: null,
    hasSaved: false,
    savedRecordTitle: null,
    targetBlank: false,

    init: function () {
      this.provideTopLevelStubs();
      this.createSidebarDOM();
      window.addEventListener('message', this.handleMessage.bind(this));
      log('Module initialized');
    },

    provideTopLevelStubs: function () {
      // TYPO3 backend modules in the iframe access top.TYPO3 (parent window).
      // Merge minimal stubs into existing object — window.TYPO3 may already
      // be partially initialized by frontend plugins or TYPO3 core modules.
      if (!window.TYPO3) window.TYPO3 = {};
      if (!window.TYPO3.InfoWindow) window.TYPO3.InfoWindow = { showItem: function () {} };
      if (!window.TYPO3.settings) window.TYPO3.settings = {};
      if (!window.TYPO3.settings.DateConfiguration) {
        window.TYPO3.settings.DateConfiguration = {
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
          formats: { date: 'yyyy-MM-dd', time: 'HH:mm', datetime: 'yyyy-MM-dd HH:mm' }
        };
      }
      if (!window.TYPO3.Backend) window.TYPO3.Backend = {};
      if (!window.TYPO3.Backend.consumerScope) {
        window.TYPO3.Backend.consumerScope = { attach: function () {}, detach: function () {}, invoke: function () {} };
      }
      if (!window.TYPO3.Backend.ContentContainer) {
        window.TYPO3.Backend.ContentContainer = { refresh: function () {}, setUrl: function () {} };
      }

      // Handle top-level module import requests from iframe backend modules.
      document.addEventListener('typo3:import-javascript-module', function (e) {
        if (e.detail && e.detail.specifier) {
          e.detail.importPromise = Promise.resolve({});
        }
      });
    },

    createSidebarDOM: function () {
      // Backdrop
      this.backdrop = document.createElement('div');
      this.backdrop.className = 'frontend-edit__sidebar-backdrop';
      this.backdrop.addEventListener('click', this.requestClose.bind(this));

      // Sidebar container with dialog semantics
      this.sidebar = document.createElement('div');
      this.sidebar.className = 'frontend-edit__sidebar';
      this.sidebar.setAttribute('role', 'dialog');
      this.sidebar.setAttribute('aria-modal', 'true');
      this.sidebar.setAttribute('aria-label', 'Edit content');

      // Close button
      this.closeButton = document.createElement('button');
      this.closeButton.type = 'button';
      this.closeButton.className = 'frontend-edit__sidebar-close';
      this.closeButton.setAttribute('aria-label', 'Close editor');
      this.closeButton.innerHTML = CLOSE_ICON;
      this.closeButton.addEventListener('click', this.requestClose.bind(this));
      this.sidebar.appendChild(this.closeButton);

      // Loading spinner
      this.loader = document.createElement('div');
      this.loader.className = 'frontend-edit__sidebar-loader';
      this.loader.innerHTML = '<div class="frontend-edit__sidebar-spinner"></div>';
      this.sidebar.appendChild(this.loader);

      // Iframe
      this.iframe = document.createElement('iframe');
      this.iframe.className = 'frontend-edit__sidebar-iframe';
      this.iframe.setAttribute('title', 'Edit content');
      this.sidebar.appendChild(this.iframe);

      // Focus trap
      var sidebar = this.sidebar;
      sidebar.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab') return;
        var focusable = sidebar.querySelectorAll('button, [href], iframe, input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable.length === 0) return;
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (e.shiftKey) {
          if (document.activeElement === first) {
            e.preventDefault();
            last.focus();
          }
        } else {
          if (document.activeElement === last) {
            e.preventDefault();
            first.focus();
          }
        }
      });

      document.body.appendChild(this.backdrop);
      document.body.appendChild(this.sidebar);

      // Close on Escape key
      var self = this;
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && self.sidebar.classList.contains('frontend-edit__sidebar--open')) {
          self.requestClose();
        }
      });

      // Monitor iframe loads
      this.iframe.addEventListener('load', this._onIframeLoad.bind(this));
    },

    _onIframeLoad: function () {
      if (!this.sidebar.classList.contains('frontend-edit__sidebar--open')) return;
      this.injectBackendStubs();
      this.loader.classList.remove('frontend-edit__sidebar-loader--visible');
      this.iframe.classList.add('frontend-edit__sidebar-iframe--loaded');
      try {
        var iframeUrl = this.iframe.contentWindow && this.iframe.contentWindow.location.href;
        if (iframeUrl) {
          var params = new URL(iframeUrl).searchParams;
          if (params.get('justSaved') === '1') {
            this.hasSaved = true;
            this.captureSavedRecordTitle();
          }
          if (params.get('closed') === '1') {
            log('Close detected via iframe URL');
            this.close();
            return;
          }
        }
        this.enhanceIframeUI();
      } catch (e) {
        // Cross-origin, access error, or invalid URL
      }
    },

    open: function (contextualUrl, uid, targetBlank) {
      log('Opening contextual edit for uid ' + uid);
      this._triggerElement = document.activeElement;
      clearTimeout(this.resetTimeout);
      this.hasSaved = false;
      this.savedRecordTitle = null;
      this.targetBlank = targetBlank || false;
      this.closeButton.style.display = '';
      this.loader.classList.add('frontend-edit__sidebar-loader--visible');
      this.iframe.classList.remove('frontend-edit__sidebar-iframe--loaded');
      this.backdrop.classList.add('frontend-edit__sidebar-backdrop--visible');
      this.sidebar.classList.add('frontend-edit__sidebar--open');
      document.body.classList.add('frontend-edit__sidebar-active');
      this.iframe.src = contextualUrl;
      this.iframe.focus();
    },

    close: function () {
      clearTimeout(this.closeTimeout);
      this.sidebar.classList.remove('frontend-edit__sidebar--open');
      this.backdrop.classList.remove('frontend-edit__sidebar-backdrop--visible');

      // Capture UID before destroying iframe
      var uid = this.hasSaved ? this.getEditedElementUid() : null;

      // Destroy iframe immediately to prevent flash message consumption
      if (this.iframe) {
        this.iframe.src = 'about:blank';
        if (this.iframe.parentNode) {
          this.iframe.parentNode.removeChild(this.iframe);
        }
      }

      if (this.hasSaved) {
        this.queueNotification(this.savedRecordTitle);
        if (uid) {
          var url = new URL(window.location.href);
          url.searchParams.set('scrollToContent', uid);
          url.hash = '';
          window.location.href = url.toString();
        } else {
          window.location.reload();
        }
      } else {
        document.body.classList.remove('frontend-edit__sidebar-active');
        this.recreateIframe();
        if (this._triggerElement && this._triggerElement.focus) {
          this._triggerElement.focus();
          this._triggerElement = null;
        }
      }
    },

    injectBackendStubs: function () {
      try {
        var iframeWin = this.iframe.contentWindow;
        if (!iframeWin || !iframeWin.TYPO3) return;
        var iframeSettings = iframeWin.TYPO3.settings;
        if (iframeSettings && window.TYPO3 && window.TYPO3.settings) {
          Object.keys(iframeSettings).forEach(function (key) {
            window.TYPO3.settings[key] = iframeSettings[key];
          });
          log('Synced iframe TYPO3.settings to parent', { keys: Object.keys(iframeSettings) });
        }
      } catch (e) {
        log('Could not inject backend stubs', { error: e.message }, 'warn');
      }
    },

    enhanceIframeUI: function () {
      var self = this;
      try {
        var iframeDoc = this.iframe.contentDocument;
        if (!iframeDoc) return;

        var actionsBar = iframeDoc.querySelector('.contextual-record-edit-actions');
        if (!actionsBar) {
          setTimeout(function () {
            try {
              var doc = self.iframe.contentDocument;
              if (doc && doc.querySelector('.contextual-record-edit-actions')) {
                self.enhanceIframeUI();
              }
            } catch (e) { /* ignore */ }
          }, 200);
          return;
        }

        if (actionsBar.querySelector('[name="_saveandclosedok"]')) return;

        var saveBtn = actionsBar.querySelector('[name="_savedok"]');
        var closeBtn = actionsBar.querySelector('.t3js-contextual-close');
        log('enhanceIframeUI: found elements', { saveBtn: !!saveBtn, closeBtn: !!closeBtn });
        if (!saveBtn) return;

        // Create "Save & Close" button
        var saveCloseBtn = iframeDoc.createElement('button');
        saveCloseBtn.type = 'submit';
        saveCloseBtn.name = '_saveandclosedok';
        saveCloseBtn.value = '1';
        saveCloseBtn.setAttribute('form', 'ContextualRecordEditController');
        saveCloseBtn.className = 'btn btn-default';

        var iconEl = iframeDoc.createElement('typo3-backend-icon');
        iconEl.setAttribute('identifier', 'actions-document-save-close');
        iconEl.setAttribute('size', 'small');
        saveCloseBtn.appendChild(iconEl);

        // Localized label from existing buttons
        var closeBtnLabel = closeBtn && closeBtn.querySelector('.contextual-record-edit-button-label');
        var saveBtnLabel = saveBtn.querySelector('.contextual-record-edit-button-label');
        var closeText = closeBtnLabel ? closeBtnLabel.textContent.trim() : '';
        var saveText = saveBtnLabel ? saveBtnLabel.textContent.trim() : '';
        var saveCloseLabel = (saveText && closeText) ? saveText + ' & ' + closeText : 'Save & Close';

        var labelSpan = iframeDoc.createElement('span');
        labelSpan.className = 'contextual-record-edit-button-label';
        labelSpan.textContent = saveCloseLabel;
        saveCloseBtn.appendChild(labelSpan);

        if (closeBtn) {
          actionsBar.insertBefore(saveCloseBtn, closeBtn);

          // Wire backend close button to close the sidebar
          closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            self.requestClose();
          });

          // Hide extension's own close button to avoid overlap
          self.closeButton.style.display = 'none';
        } else {
          actionsBar.appendChild(saveCloseBtn);
        }

        // Override fullscreen button
        var fullscreenBtn = iframeDoc.querySelector('.t3js-contextual-fullscreen');
        if (fullscreenBtn) {
          fullscreenBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var href = fullscreenBtn.getAttribute('href');
            if (href) {
              self.close();
              if (self.targetBlank) {
                window.open(href, '_blank');
              } else {
                window.location.href = href;
              }
            }
          });
        }

        log('Enhanced iframe UI');
      } catch (e) {
        log('Could not enhance iframe UI', { error: e.message }, 'warn');
      }
    },

    requestClose: function () {
      try {
        if (this.iframe && this.iframe.contentWindow) {
          this.iframe.contentWindow.postMessage(
            { actionName: 'typo3:editform:requestclose' },
            window.location.origin
          );
        }
        var self = this;
        this.closeTimeout = setTimeout(function () { self.close(); }, 500);
      } catch (e) {
        this.close();
      }
    },

    handleMessage: function (event) {
      if (event.origin !== window.location.origin) return;
      var data = event.data;
      if (!data || typeof data !== 'object') return;

      switch (data.actionName) {
        case 'typo3:editform:saved':
          this.onSaved(data);
          break;
        case 'typo3:editform:closed':
          this.close();
          break;
        case 'typo3:editform:navigate':
          this.close();
          break;
      }
    },

    onSaved: function (data) {
      this.hasSaved = true;
      this.savedRecordTitle = data.recordTitle || null;
      this.captureSavedRecordTitle();
    },

    captureSavedRecordTitle: function () {
      if (this.savedRecordTitle) return;
      try {
        var titleEl = this.iframe.contentDocument && this.iframe.contentDocument.querySelector('.contextual-record-edit-title');
        this.savedRecordTitle = titleEl ? titleEl.textContent.trim() : null;
      } catch (e) { /* ignore */ }
    },

    queueNotification: function (recordTitle) {
      try {
        var name = recordTitle || 'Record';
        sessionStorage.setItem('xfe-pending-notification', JSON.stringify({
          title: 'Record updated',
          message: '"' + name + '" has been saved.',
          severity: 'ok'
        }));
      } catch (e) { /* sessionStorage unavailable */ }
    },

    getEditedElementUid: function () {
      try {
        var iframeUrl = this.iframe && this.iframe.src;
        if (!iframeUrl) return null;
        var match = iframeUrl.match(/edit%5Btt_content%5D%5B(\d+)%5D|edit\[tt_content\]\[(\d+)\]/);
        return match ? (match[1] || match[2]) : null;
      } catch (e) {
        return null;
      }
    },

    recreateIframe: function () {
      var newIframe = document.createElement('iframe');
      newIframe.className = 'frontend-edit__sidebar-iframe';
      newIframe.setAttribute('title', 'Edit content');
      this.sidebar.appendChild(newIframe);
      this.iframe = newIframe;
      this.iframe.addEventListener('load', this._onIframeLoad.bind(this));
    }
  };

  window.ContextualEdit = ContextualEdit;
})();
