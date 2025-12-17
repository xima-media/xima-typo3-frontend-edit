/**
 * TYPO3 Frontend Edit - Sticky Toolbar
 * Provides a fixed toolbar for toggling frontend edit mode and page actions.
 */
(function () {
  'use strict';

  // Floating UI imports - will be set when ready
  let computePosition, flip, shift, offset, arrow;

  function initFloatingUI() {
    if (window.FloatingUIDOM) {
      ({ computePosition, flip, shift, offset, arrow } = window.FloatingUIDOM);
    }
  }

  // SVG Icons
  const ICONS = {
    eyeOpen: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 3C4.5 3 1.5 5.5 0.5 8c1 2.5 4 5 7.5 5s6.5-2.5 7.5-5c-1-2.5-4-5-7.5-5zm0 8c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3zm0-5c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>',
    eyeClosed: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 4c1.8 0 3.3 1.5 3.3 3.3 0 .4-.1.8-.2 1.2l1.9 1.9c1-1 1.8-2 2.3-3.1C14.2 4.7 11.3 2.7 8 2.7c-.9 0-1.8.2-2.7.5L6.8 4.7c.4-.1.8-.2 1.2-.2zM1.3 2.6l1.5 1.5.3.3C1.9 5.3.9 6.5.3 7.8c1 2.6 4 4.5 7.4 4.5 1 0 2-.2 2.9-.6l.3.3 2 2 .8-.8L2.1 1.8l-.8.8zM5 6.4l1 1c0 .1 0 .3 0 .4 0 1.1.9 2 2 2 .1 0 .3 0 .4 0l1 1c-.4.2-.9.3-1.4.3C6.4 11 5 9.6 5 8c0-.5.2-1 .4-1.4l-.4-.2zm2.9-.5l2.1 2.1V8c0-1.1-.9-2-2-2l-.1 0z"/></svg>',
    kebab: '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="2.5" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="13.5" r="1.5"/></svg>'
  };

  /**
   * Simple Debug Logger
   */
  const Logger = {
    log(message, data = null, level = 'log') {
      if (!window.FRONTEND_EDIT_DEBUG) return;
      const prefix = '%c[sticky-toolbar]%c';
      const styles = ['font-weight: bold; color: #6366f1;', 'font-weight: normal;'];
      data !== null
        ? console[level](prefix, ...styles, message, data)
        : console[level](prefix, ...styles, message);
    }
  };

  /**
   * Tooltip Manager using Floating UI
   */
  const Tooltip = {
    element: null,
    arrow: null,

    getElements() {
      if (!this.element) {
        this.element = document.createElement('div');
        this.element.className = 'frontend-edit__tooltip';
        this.arrow = document.createElement('div');
        this.arrow.className = 'frontend-edit__tooltip-arrow';
        this.element.appendChild(this.arrow);
        document.body.appendChild(this.element);
      }
      return { tooltip: this.element, arrow: this.arrow };
    },

    async show(btn) {
      const text = btn.dataset.tooltip;
      if (!text || !computePosition) return;

      const { tooltip, arrow: arrowEl } = this.getElements();

      // Clear previous text but keep arrow
      Array.from(tooltip.childNodes).forEach(node => {
        if (node !== arrowEl) tooltip.removeChild(node);
      });
      tooltip.insertBefore(document.createTextNode(text), arrowEl);

      const { x, y, placement, middlewareData } = await computePosition(btn, tooltip, {
        placement: 'top',
        middleware: [
          offset(8),
          flip({ fallbackPlacements: ['bottom', 'left', 'right'] }),
          shift({ padding: 8 }),
          arrow({ element: arrowEl })
        ]
      });

      Object.assign(tooltip.style, { left: `${x}px`, top: `${y}px` });
      tooltip.setAttribute('data-placement', placement);

      if (middlewareData.arrow) {
        const { x: arrowX, y: arrowY } = middlewareData.arrow;
        Object.assign(arrowEl.style, {
          left: arrowX != null ? `${arrowX}px` : '',
          top: arrowY != null ? `${arrowY}px` : ''
        });
      }

      tooltip.classList.add('frontend-edit__tooltip--visible');
    },

    hide() {
      if (this.element) {
        this.element.classList.remove('frontend-edit__tooltip--visible');
      }
    },

    attach(btn) {
      btn.addEventListener('mouseenter', () => this.show(btn));
      btn.addEventListener('mouseleave', () => this.hide());
      btn.addEventListener('focus', () => this.show(btn));
      btn.addEventListener('blur', () => this.hide());
    }
  };

  /**
   * Sticky Toolbar
   */
  const StickyToolbar = {
    container: null,
    configElement: null,
    pageMenuTemplate: null,
    isDisabled: false,
    isToggling: false,
    position: 'bottom-right',
    toggleUrl: '',

    init() {
      // Read sticky toolbar configuration
      this.configElement = document.getElementById('frontend-edit-sticky-toolbar-config');
      if (!this.configElement) {
        Logger.log('Sticky toolbar disabled or config not found');
        return;
      }

      // Read general config for disabled state
      const generalConfig = document.getElementById('frontend-edit-toolbar-config');

      // Get page menu template (server-rendered)
      this.pageMenuTemplate = document.getElementById('frontend-edit-page-menu');

      this.position = this.configElement.dataset.position || 'bottom-right';
      this.isDisabled = generalConfig ? generalConfig.dataset.disabled === 'true' : false;
      this.toggleUrl = this.configElement.dataset.toggleUrl || '';

      this.createToolbar();
      this.setupEventListeners();
      this.updateVisualState();
      Logger.log('Initialized', { position: this.position, disabled: this.isDisabled });
    },

    createToolbar() {
      this.container = document.createElement('div');
      this.container.className = `frontend-edit__sticky-toolbar frontend-edit__sticky-toolbar--${this.position}`;
      this.container.innerHTML = this.getToolbarHTML();
      document.body.appendChild(this.container);
    },

    getToolbarHTML() {
      const toggleTooltip = this.isDisabled
        ? 'Enable frontend editing mode'
        : 'Disable frontend editing mode';
      const toggleIcon = this.isDisabled ? ICONS.eyeClosed : ICONS.eyeOpen;

      // Only show page menu when not disabled and template exists
      const showPageMenu = !this.isDisabled && this.pageMenuTemplate;
      const pageMenuContent = showPageMenu ? this.pageMenuTemplate.innerHTML : '';

      let html = `
        <button class="frontend-edit__sticky-btn frontend-edit__sticky-btn--toggle" data-tooltip="${toggleTooltip}" type="button">
          ${toggleIcon}
        </button>`;

      // Add page dropdown only when enabled
      if (showPageMenu && pageMenuContent) {
        html += `
        <div class="frontend-edit__sticky-separator"></div>
        <div class="frontend-edit__sticky-dropdown-container">
          <button class="frontend-edit__sticky-btn frontend-edit__sticky-btn--menu" data-tooltip="Page options" type="button">
            ${ICONS.kebab}
          </button>
          <div class="frontend-edit__sticky-dropdown">
            ${pageMenuContent}
          </div>
        </div>`;
      }

      return html;
    },

    setupEventListeners() {
      // Toggle button
      const toggleBtn = this.container.querySelector('.frontend-edit__sticky-btn--toggle');
      toggleBtn.addEventListener('click', () => this.handleToggle());
      Tooltip.attach(toggleBtn);

      // Dropdown (only if exists)
      const dropdownContainer = this.container.querySelector('.frontend-edit__sticky-dropdown-container');
      if (dropdownContainer) {
        const menuBtn = this.container.querySelector('.frontend-edit__sticky-btn--menu');
        const dropdown = this.container.querySelector('.frontend-edit__sticky-dropdown');

        menuBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          dropdown.classList.toggle('frontend-edit__sticky-dropdown--visible');
        });

        document.addEventListener('click', (e) => {
          if (!dropdownContainer.contains(e.target)) {
            dropdown.classList.remove('frontend-edit__sticky-dropdown--visible');
          }
        });

        Tooltip.attach(menuBtn);
      }
    },

    async handleToggle() {
      if (!this.toggleUrl) {
        Logger.log('Toggle URL not configured', {}, 'error');
        return;
      }

      // Prevent double-click
      if (this.isToggling) {
        return;
      }

      const toggleBtn = this.container.querySelector('.frontend-edit__sticky-btn--toggle');
      this.isToggling = true;
      toggleBtn.disabled = true;
      toggleBtn.style.opacity = '0.5';
      toggleBtn.style.cursor = 'wait';

      try {
        const response = await fetch(this.toggleUrl, {
          method: 'POST',
          cache: 'no-cache',
          credentials: 'same-origin',
        });

        if (!response.ok) {
          throw new Error('Toggle request failed');
        }

        const data = await response.json();
        Logger.log('Toggle response', data);

        // Reload page to reflect changes
        window.location.reload();
      } catch (error) {
        Logger.log('Toggle failed', { error: error.message }, 'error');
        // Re-enable button on error
        this.isToggling = false;
        toggleBtn.disabled = false;
        toggleBtn.style.opacity = '';
        toggleBtn.style.cursor = '';
      }
    },

    updateVisualState() {
      const toggleBtn = this.container.querySelector('.frontend-edit__sticky-btn--toggle');
      toggleBtn.innerHTML = this.isDisabled ? ICONS.eyeClosed : ICONS.eyeOpen;
      toggleBtn.dataset.tooltip = this.isDisabled
        ? 'Enable frontend editing mode'
        : 'Disable frontend editing mode';
      this.container.classList.toggle('frontend-edit__sticky-toolbar--disabled', this.isDisabled);
    }
  };

  // Initialize when both DOM and FloatingUI are ready
  function init() {
    initFloatingUI();
    StickyToolbar.init();
  }

  function waitForReady() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', checkAndInit);
    } else {
      checkAndInit();
    }
  }

  function checkAndInit() {
    if (window.FloatingUIDOM) {
      init();
    } else {
      window.addEventListener('floatingui:ready', init, { once: true });
    }
  }

  waitForReady();
})();
