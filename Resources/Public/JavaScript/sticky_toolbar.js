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
    edit: '<svg viewBox="0 0 24 24" fill="currentColor"><g transform="matrix(1,0,0,1,-180,-95)"><g transform="matrix(0.24,0,0,0.24,179.52,95.24)"><g transform="matrix(1.428572,0,0,1.428572,-22.285735,-21.000043)"><g transform="matrix(0.731827,0,0,0.731827,14.822171,13.071707)"><path d="M90.656,28.125C90.656,29.946 89.945,31.657 88.656,32.938L39.711,81.883C39.586,82 39.453,82.11 39.312,82.211L39.187,82.297C39.062,82.383 38.922,82.461 38.781,82.524L38.711,82.563C38.57,82.618 38.43,82.665 38.289,82.704L14.851,88.743C14.593,88.813 14.328,88.844 14.07,88.844C13.249,88.844 12.445,88.516 11.851,87.915C11.07,87.126 10.765,85.977 11.054,84.907L17.304,61.68C17.335,61.539 17.382,61.406 17.437,61.281L17.46,61.234C17.531,61.086 17.609,60.945 17.695,60.805L17.781,60.68C17.882,60.539 17.984,60.406 18.109,60.281L67.054,11.344C68.344,10.055 70.054,9.344 71.875,9.344C73.695,9.344 75.406,10.055 76.695,11.344L88.656,23.305C89.945,24.594 90.656,26.305 90.656,28.125ZM84.242,28.532C84.375,28.391 84.414,28.235 84.414,28.125C84.414,28.016 84.39,27.86 84.25,27.719L72.288,15.758C72.148,15.625 71.992,15.594 71.882,15.594C71.773,15.594 71.617,15.617 71.476,15.758L64.578,22.656L77.344,35.422L84.242,28.532ZM22.57,66.141L18.468,81.367L33.851,77.398L33.375,75L28.125,75C26.398,75 25,73.602 25,71.875L25,66.625L22.57,66.141ZM64.328,31.25L60.156,27.078L26.648,60.586L28.734,61C30.195,61.289 31.25,62.571 31.25,64.063L31.25,68.75L35.937,68.75C37.43,68.75 38.711,69.805 39.008,71.266L39.422,73.352L72.922,39.844L68.75,35.672L64.328,31.25Z"/></g><g transform="matrix(0.731827,0,0,0.731827,14.822171,13.071707)" opacity=".4"><path d="M68.75,35.672L39.711,64.711C39.101,65.32 38.297,65.625 37.5,65.625C36.703,65.625 35.899,65.32 35.289,64.711C34.07,63.492 34.07,61.508 35.289,60.289L64.328,31.25L68.75,35.672Z"/></g><g transform="matrix(4.166667,0,0,4.166667,-123,-1)" opacity=".4"><path d="M36,18L37,14L40,17L36,18Z"/></g></g></g></g></svg>',
    editOff: '<svg viewBox="0 0 24 24" fill="currentColor"><g transform="matrix(1,0,0,1,-180,-95)"><g transform="matrix(0.24,0,0,0.24,179.52,95.24)"><g transform="matrix(1.428572,0,0,1.428572,-22.285735,-21.000043)"><g transform="matrix(0.731827,0,0,0.731827,14.822171,13.071707)"><path d="M56.014,65.58L39.711,81.883C39.586,82 39.453,82.11 39.312,82.211L39.187,82.297C39.062,82.383 38.922,82.461 38.781,82.524L38.711,82.563C38.57,82.618 38.43,82.665 38.289,82.704L14.851,88.743C14.593,88.813 14.328,88.844 14.07,88.844C13.249,88.844 12.445,88.516 11.851,87.915C11.07,87.126 10.765,85.977 11.054,84.907L17.304,61.68C17.335,61.539 17.382,61.406 17.437,61.281L17.46,61.234C17.531,61.086 17.609,60.945 17.695,60.805L17.781,60.68C17.882,60.539 17.984,60.406 18.109,60.281L34.414,43.979L38.834,48.4L26.648,60.586L28.734,61C30.195,61.289 31.25,62.571 31.25,64.063L31.25,68.75L35.937,68.75C37.43,68.75 38.711,69.805 39.008,71.266L39.422,73.352L51.603,61.168L56.014,65.58ZM45.687,32.707L67.054,11.344C68.344,10.055 70.054,9.344 71.875,9.344C73.695,9.344 75.406,10.055 76.695,11.344L88.656,23.305C89.945,24.594 90.656,26.305 90.656,28.125C90.656,29.946 89.945,31.657 88.656,32.938L67.287,54.307L62.874,49.894L72.922,39.844L60.156,27.078L50.107,37.127L45.687,32.707ZM22.57,66.141L18.468,81.367L33.851,77.398L33.375,75L28.125,75C26.398,75 25,73.602 25,71.875L25,66.625L22.57,66.141ZM84.242,28.532C84.375,28.391 84.414,28.235 84.414,28.125C84.414,28.016 84.39,27.86 84.25,27.719L72.288,15.758C72.148,15.625 71.992,15.594 71.882,15.594C71.773,15.594 71.617,15.617 71.476,15.758L64.578,22.656L77.344,35.422L84.242,28.532Z"/></g><g transform="matrix(0.731827,0,0,0.731827,14.822171,13.071707)" opacity=".4"><path d="M54.279,41.299L64.328,31.25L68.75,35.672L58.701,45.721L54.279,41.299ZM47.428,56.994L39.711,64.711C39.101,65.32 38.297,65.625 37.5,65.625C36.703,65.625 35.899,65.32 35.289,64.711C34.07,63.492 34.07,61.508 35.289,60.289L43.006,52.572L47.428,56.994Z"/></g><g transform="matrix(4.166667,0,0,4.166667,-123,-1)" opacity=".4"><path d="M36,18L37,14L40,17L36,18Z"/></g><g transform="matrix(2.754669,-0.161996,-0.161996,2.754669,20.888651,17.888668)"><path d="M3.559,4.691C3.208,4.339 3.176,3.801 3.489,3.489C3.801,3.176 4.339,3.208 4.691,3.559L20.445,19.314C20.796,19.665 20.828,20.204 20.516,20.516C20.204,20.828 19.665,20.796 19.314,20.445L3.559,4.691Z"/></g></g></g></g></svg>',
    kebab: '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="2.5" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="13.5" r="1.5"/></svg>',
    // Matches the content element "Edit" button icon (frontend_edit.js ICONS.edit) for visual consistency.
    pageEdit: '<svg viewBox="0 0 32 32" fill="currentColor"><path d="M4.834,29.665L25.007,29.665C26.561,29.663 27.839,28.385 27.841,26.831L27.841,16.157C27.841,15.608 27.39,15.157 26.841,15.157C26.292,15.157 25.841,15.608 25.841,16.157L25.841,26.831C25.84,27.288 25.464,27.664 25.007,27.665L4.834,27.665C4.377,27.664 4.001,27.288 4,26.831L4,7.651C4.001,7.194 4.377,6.818 4.834,6.817L16,6.817C16.549,6.817 17,6.366 17,5.817C17,5.268 16.549,4.817 16,4.817L4.834,4.817C3.28,4.819 2.002,6.097 2,7.651L2,26.831C2.002,28.385 3.28,29.663 4.834,29.665Z" fill-rule="nonzero"/><path d="M8.582,19.343L7.912,22.691C7.894,22.781 7.885,22.873 7.885,22.965C7.885,23.726 8.51,24.352 9.271,24.352C9.363,24.352 9.454,24.343 9.544,24.325L12.895,23.655C13.539,23.527 14.131,23.211 14.595,22.747L28.845,8.494C29.473,7.825 29.823,6.941 29.823,6.024C29.823,4.044 28.195,2.416 26.215,2.416C25.298,2.416 24.414,2.766 23.745,3.394L9.49,17.645C9.025,18.108 8.709,18.699 8.582,19.343ZM10.543,19.734C10.594,19.478 10.72,19.244 10.904,19.059L25.157,4.806C25.458,4.509 25.864,4.343 26.286,4.343C27.168,4.343 27.894,5.069 27.894,5.951C27.894,6.373 27.728,6.779 27.431,7.08L13.178,21.332C12.993,21.517 12.758,21.643 12.502,21.694L10.054,22.184L10.543,19.734Z" fill-rule="nonzero"/></svg>'
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
    pageMenuData: null,
    isDisabled: false,
    isToggling: false,
    position: 'bottom-right',
    toggleUrl: '',
    // Tooltip translations (with English fallbacks)
    tooltips: {
      enable: 'Enable frontend editing mode',
      disable: 'Disable frontend editing mode',
      pageOptions: 'Page options',
      editPage: 'Edit page settings'
    },

    init() {
      // Read sticky toolbar configuration
      this.configElement = document.getElementById('frontend-edit-sticky-toolbar-config');
      if (!this.configElement) {
        Logger.log('Sticky toolbar disabled or config not found');
        return;
      }

      // Read general config for disabled state
      const generalConfig = document.getElementById('frontend-edit-toolbar-config');

      // Get page menu data (JSON)
      const pageMenuDataElement = document.getElementById('frontend-edit-page-menu-data');
      if (pageMenuDataElement) {
        try {
          this.pageMenuData = JSON.parse(pageMenuDataElement.textContent);
          Logger.log('Page menu data loaded', this.pageMenuData);
        } catch (e) {
          Logger.log('Failed to parse page menu data', { error: e.message }, 'error');
        }
      }

      this.position = this.configElement.dataset.position || 'bottom-right';
      this.isDisabled = generalConfig ? generalConfig.dataset.disabled === 'true' : false;
      this.toggleUrl = this.configElement.dataset.toggleUrl || '';

      // Read tooltip translations from data attributes (with fallbacks)
      if (this.configElement.dataset.tooltipEnable) {
        this.tooltips.enable = this.configElement.dataset.tooltipEnable;
      }
      if (this.configElement.dataset.tooltipDisable) {
        this.tooltips.disable = this.configElement.dataset.tooltipDisable;
      }
      if (this.configElement.dataset.tooltipPageOptions) {
        this.tooltips.pageOptions = this.configElement.dataset.tooltipPageOptions;
      }

      this.createToolbar();
      this.setupEventListeners();
      this.updateVisualState();
      Logger.log('Initialized', { position: this.position, disabled: this.isDisabled });
    },

    createToolbar() {
      this.container = document.createElement('div');
      this.container.className = `frontend-edit__sticky-toolbar frontend-edit__sticky-toolbar--${this.escapeClassName(this.position)}`;
      this.container.innerHTML = this.getToolbarHTML();

      // Populate dropdown with safely constructed DOM elements
      const dropdown = this.container.querySelector('.frontend-edit__sticky-dropdown');
      if (dropdown) {
        dropdown.appendChild(this.createDropdownItems());
      }

      document.body.appendChild(this.container);
    },

    getToolbarHTML() {
      const toggleTooltip = this.isDisabled ? this.tooltips.enable : this.tooltips.disable;
      // Icon reflects the action a click will perform (like a mute button), not the current
      // state: showing the plain pencil while editing is already on made it look like a
      // generic "Edit" button, indistinguishable from the edit-page button next to it.
      const toggleIcon = this.isDisabled ? ICONS.edit : ICONS.editOff;

      // Only show page menu when not disabled and data exists
      const showPageMenu = !this.isDisabled && this.pageMenuData && this.pageMenuData.children;
      const editPageItem = showPageMenu ? this.pageMenuData.children.edit_page_properties : null;

      let html = `
        <button class="frontend-edit__sticky-btn frontend-edit__sticky-btn--toggle" data-tooltip="${this.escapeAttribute(toggleTooltip)}" type="button">
          ${toggleIcon}
        </button>`;

      // Dedicated "Edit page settings" button, mirroring the content element Edit button
      if (editPageItem && editPageItem.url && this.isValidUrl(editPageItem.url)) {
        const targetBlankAttr = this.pageMenuData.targetBlank ? ' target="_blank"' : '';
        html += `
        <div class="frontend-edit__sticky-separator"></div>
        <a class="frontend-edit__sticky-btn frontend-edit__sticky-btn--edit-page" href="${this.escapeAttribute(editPageItem.url)}" data-tooltip="${this.escapeAttribute(editPageItem.label || this.tooltips.editPage)}"${targetBlankAttr}>
          ${ICONS.pageEdit}
        </a>`;
      }

      // Add page dropdown only when enabled (dropdown content added via DOM in createToolbar)
      if (showPageMenu) {
        html += `
        <div class="frontend-edit__sticky-separator"></div>
        <div class="frontend-edit__sticky-dropdown-container">
          <button class="frontend-edit__sticky-btn frontend-edit__sticky-btn--menu" data-tooltip="${this.escapeAttribute(this.tooltips.pageOptions)}" type="button">
            ${ICONS.kebab}
          </button>
          <div class="frontend-edit__sticky-dropdown"></div>
        </div>`;
      }

      return html;
    },

    /**
     * Escapes HTML attribute values to prevent XSS.
     */
    escapeAttribute(str) {
      if (!str || typeof str !== 'string') {
        return '';
      }
      return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    },

    /**
     * Creates dropdown content using safe DOM construction.
     * Returns a DocumentFragment with dropdown items.
     *
     * Security note: Icons (item.icon) are trusted HTML from the TYPO3 backend
     * (generated via IconFactory). Labels are safely inserted via textContent.
     */
    createDropdownItems() {
      const fragment = document.createDocumentFragment();

      if (!this.pageMenuData || !this.pageMenuData.children) {
        return fragment;
      }

      const targetBlank = this.pageMenuData.targetBlank || false;

      for (const [name, item] of Object.entries(this.pageMenuData.children)) {
        let element;

        if (item.type === 'divider') {
          element = document.createElement('div');
          element.className = `frontend-edit__divider ${this.escapeClassName(name)}`;
          const span = document.createElement('span');
          span.textContent = item.label || '';
          element.appendChild(span);
        } else if (item.type === 'info') {
          element = document.createElement('div');
          element.className = `frontend-edit__info ${this.escapeClassName(name)}`;
          // Icons are trusted HTML from TYPO3 backend (IconFactory)
          if (item.icon) {
            const iconWrapper = document.createElement('span');
            iconWrapper.innerHTML = item.icon;
            element.appendChild(iconWrapper);
          }
          const span = document.createElement('span');
          span.innerHTML = item.label || '';
          element.appendChild(span);
        } else if (item.type === 'link') {
          element = document.createElement('a');
          // Validate URL to prevent javascript: protocol attacks
          if (item.url && this.isValidUrl(item.url)) {
            element.href = item.url;
          }
          if (targetBlank) {
            element.target = '_blank';
          }

          // Contextual editing: open in sidebar if available
          if (name === 'edit_page_properties' && item.contextualUrl && this.isValidUrl(item.contextualUrl) && window.FRONTEND_EDIT_CONTEXTUAL_EDITING && window.ContextualEdit) {
            const contextualUrl = item.contextualUrl;
            element.addEventListener('click', (e) => {
              if (e.ctrlKey || e.metaKey || e.shiftKey) return;
              window.ContextualEdit.open(contextualUrl, null, targetBlank);
              e.preventDefault();
              // Close the sticky toolbar dropdown
              const dropdown = document.querySelector('.frontend-edit__sticky-dropdown');
              if (dropdown) dropdown.classList.remove('frontend-edit__sticky-dropdown--visible');
            });
          }
          // Icons are trusted HTML from TYPO3 backend (IconFactory)
          if (item.icon) {
            const iconWrapper = document.createElement('span');
            iconWrapper.innerHTML = item.icon;
            element.appendChild(iconWrapper);
          }
          const span = document.createElement('span');
          span.textContent = item.label || '';
          element.appendChild(span);
        }

        if (element) {
          fragment.appendChild(element);
        }
      }

      return fragment;
    },

    /**
     * Validates URL to prevent javascript: and other dangerous protocols.
     */
    isValidUrl(url) {
      if (!url || typeof url !== 'string') {
        return false;
      }
      const trimmed = url.trim().toLowerCase();
      // Block javascript:, data:, vbscript: protocols
      if (trimmed.startsWith('javascript:') ||
          trimmed.startsWith('data:') ||
          trimmed.startsWith('vbscript:')) {
        return false;
      }
      return true;
    },

    /**
     * Escapes class name to prevent injection via class attribute.
     */
    escapeClassName(name) {
      if (!name || typeof name !== 'string') {
        return '';
      }
      // Only allow alphanumeric, hyphens, and underscores
      return name.replace(/[^a-zA-Z0-9_-]/g, '');
    },

    setupEventListeners() {
      // Toggle button
      const toggleBtn = this.container.querySelector('.frontend-edit__sticky-btn--toggle');
      toggleBtn.addEventListener('click', () => this.handleToggle());
      Tooltip.attach(toggleBtn);

      // Edit page settings button (only if exists)
      const editPageBtn = this.container.querySelector('.frontend-edit__sticky-btn--edit-page');
      if (editPageBtn) {
        Tooltip.attach(editPageBtn);

        const editPageItem = this.pageMenuData?.children?.edit_page_properties;
        if (editPageItem?.contextualUrl && this.isValidUrl(editPageItem.contextualUrl) && window.FRONTEND_EDIT_CONTEXTUAL_EDITING && window.ContextualEdit) {
          const contextualUrl = editPageItem.contextualUrl;
          const targetBlank = this.pageMenuData.targetBlank || false;
          editPageBtn.addEventListener('click', (e) => {
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;
            window.ContextualEdit.open(contextualUrl, null, targetBlank);
            e.preventDefault();
          });
        }
      }

      // Dropdown (only if exists)
      const dropdownContainer = this.container.querySelector('.frontend-edit__sticky-dropdown-container');
      if (dropdownContainer) {
        const menuBtn = this.container.querySelector('.frontend-edit__sticky-btn--menu');
        const dropdown = this.container.querySelector('.frontend-edit__sticky-dropdown');

        // Move dropdown to document.body so position:fixed works correctly
        // (toolbar may have CSS transform which creates a new containing block)
        document.body.appendChild(dropdown);

        menuBtn.addEventListener('click', async (e) => {
          e.stopPropagation();
          const isVisible = dropdown.classList.contains('frontend-edit__sticky-dropdown--visible');
          if (isVisible) {
            dropdown.classList.remove('frontend-edit__sticky-dropdown--visible');
          } else {
            await this.positionDropdown(menuBtn, dropdown);
            dropdown.classList.add('frontend-edit__sticky-dropdown--visible');
          }
        });

        document.addEventListener('click', (e) => {
          if (!dropdownContainer.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('frontend-edit__sticky-dropdown--visible');
          }
        });

        Tooltip.attach(menuBtn);
      }
    },

    async positionDropdown(button, dropdown) {
      if (!computePosition) {
        Logger.log('Floating UI not available for dropdown positioning');
        return;
      }

      // Determine preferred placement based on toolbar position
      let preferredPlacement = 'top-start';
      let fallbackPlacements = ['top-end', 'bottom-start', 'bottom-end'];

      if (this.position.startsWith('top-')) {
        preferredPlacement = 'bottom-start';
        fallbackPlacements = ['bottom-end', 'top-start', 'top-end'];
      } else if (this.position.startsWith('left-')) {
        preferredPlacement = 'right-start';
        fallbackPlacements = ['right-end', 'left-start', 'left-end'];
      } else if (this.position.startsWith('right-')) {
        preferredPlacement = 'left-start';
        fallbackPlacements = ['left-end', 'right-start', 'right-end'];
      }

      const { x, y, placement } = await computePosition(button, dropdown, {
        strategy: 'fixed',
        placement: preferredPlacement,
        middleware: [
          offset(8),
          flip({
            fallbackPlacements: fallbackPlacements,
            padding: 10,
            crossAxis: true
          }),
          shift({
            padding: 10,
            crossAxis: true,
            limiter: {
              fn: ({ x, y }) => ({ x, y }),
              options: {}
            }
          })
        ]
      });

      // Ensure dropdown stays within viewport bounds
      const rect = dropdown.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      const viewportWidth = window.innerWidth;

      let adjustedY = y;
      let adjustedX = x;

      // Check if dropdown exceeds bottom of viewport
      if (y + rect.height > viewportHeight - 10) {
        adjustedY = viewportHeight - rect.height - 10;
      }
      // Check if dropdown exceeds top of viewport
      if (adjustedY < 10) {
        adjustedY = 10;
      }
      // Check if dropdown exceeds right of viewport
      if (x + rect.width > viewportWidth - 10) {
        adjustedX = viewportWidth - rect.width - 10;
      }
      // Check if dropdown exceeds left of viewport
      if (adjustedX < 10) {
        adjustedX = 10;
      }

      Logger.log('Dropdown positioned', { x: adjustedX, y: adjustedY, placement, preferredPlacement, originalX: x, originalY: y });

      Object.assign(dropdown.style, {
        left: `${adjustedX}px`,
        top: `${adjustedY}px`
      });
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
      toggleBtn.innerHTML = this.isDisabled ? ICONS.edit : ICONS.editOff;
      toggleBtn.dataset.tooltip = this.isDisabled ? this.tooltips.enable : this.tooltips.disable;
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
