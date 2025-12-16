/**
 * TYPO3 Frontend Edit
 * Provides inline editing capabilities for content elements in the frontend.
 */
(function () {
  'use strict';

  // Floating UI imports
  const { computePosition, flip, shift, offset, arrow } = window.FloatingUIDOM || {};

  // SVG Icons
  const ICONS = {
    edit: '<svg viewBox="0 0 32 32" fill="currentColor"><path d="M4.834,29.665L25.007,29.665C26.561,29.663 27.839,28.385 27.841,26.831L27.841,16.157C27.841,15.608 27.39,15.157 26.841,15.157C26.292,15.157 25.841,15.608 25.841,16.157L25.841,26.831C25.84,27.288 25.464,27.664 25.007,27.665L4.834,27.665C4.377,27.664 4.001,27.288 4,26.831L4,7.651C4.001,7.194 4.377,6.818 4.834,6.817L16,6.817C16.549,6.817 17,6.366 17,5.817C17,5.268 16.549,4.817 16,4.817L4.834,4.817C3.28,4.819 2.002,6.097 2,7.651L2,26.831C2.002,28.385 3.28,29.663 4.834,29.665Z" fill-rule="nonzero"/><path d="M8.582,19.343L7.912,22.691C7.894,22.781 7.885,22.873 7.885,22.965C7.885,23.726 8.51,24.352 9.271,24.352C9.363,24.352 9.454,24.343 9.544,24.325L12.895,23.655C13.539,23.527 14.131,23.211 14.595,22.747L28.845,8.494C29.473,7.825 29.823,6.941 29.823,6.024C29.823,4.044 28.195,2.416 26.215,2.416C25.298,2.416 24.414,2.766 23.745,3.394L9.49,17.645C9.025,18.108 8.709,18.699 8.582,19.343ZM10.543,19.734C10.594,19.478 10.72,19.244 10.904,19.059L25.157,4.806C25.458,4.509 25.864,4.343 26.286,4.343C27.168,4.343 27.894,5.069 27.894,5.951C27.894,6.373 27.728,6.779 27.431,7.08L13.178,21.332C12.993,21.517 12.758,21.643 12.502,21.694L10.054,22.184L10.543,19.734Z" fill-rule="nonzero"/></svg>',
    kebab: '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="2.5" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="13.5" r="1.5"/></svg>'
  };

  /**
   * Debug Logger - Only logs when debug mode is enabled
   */
  const Logger = {
    log(message, data = null, level = 'log') {
      if (!window.FRONTEND_EDIT_DEBUG) return;
      const prefix = '%c[xima-typo3-frontend-edit]%c';
      const styles = ['font-weight: bold;', 'font-weight: normal;'];
      data !== null
        ? console[level](prefix, ...styles, message, data)
        : console[level](prefix, ...styles, message);
    }
  };

  /**
   * Tooltip Manager - Handles tooltip creation and positioning
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

      // Clear and set text
      Array.from(tooltip.childNodes).forEach(node => {
        if (node !== arrowEl) tooltip.removeChild(node);
      });
      tooltip.insertBefore(document.createTextNode(text), arrowEl);

      // Position with Floating UI
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
   * Dropdown Manager - Handles dropdown positioning and visibility
   */
  const Dropdown = {
    async position(trigger, dropdown) {
      if (computePosition) {
        const { x, y } = await computePosition(trigger, dropdown, {
          placement: 'bottom-end',
          middleware: [
            offset(4),
            flip({ fallbackPlacements: ['top-end', 'bottom-start', 'top-start'] }),
            shift({ padding: 8 })
          ]
        });
        Object.assign(dropdown.style, { left: `${x}px`, top: `${y}px` });
      } else {
        // Fallback positioning
        const rect = trigger.getBoundingClientRect();
        const scrollTop = document.documentElement.scrollTop;
        const scrollLeft = document.documentElement.scrollLeft;

        dropdown.style.top = `${rect.bottom + scrollTop + 4}px`;
        dropdown.style.left = `${rect.right + scrollLeft - dropdown.offsetWidth}px`;

        if (rect.bottom + 200 > window.innerHeight) {
          dropdown.style.top = `${rect.top + scrollTop - dropdown.offsetHeight - 4}px`;
        }
      }
    },

    closeAll() {
      document.querySelectorAll('.frontend-edit__dropdown').forEach(d => {
        d.style.display = 'none';
      });
    },

    setupGlobalHandler() {
      document.addEventListener('click', (e) => {
        if (!e.target.closest('.frontend-edit__btn--kebab') &&
            !e.target.closest('.frontend-edit__dropdown')) {
          this.closeAll();
        }
      });
    }
  };

  /**
   * UI Factory - Creates toolbar and menu elements
   */
  const UI = {
    createToolbar(uid, contentElement, showContextMenu) {
      const toolbar = document.createElement('div');
      toolbar.className = 'frontend-edit__toolbar';
      toolbar.dataset.cid = uid;

      toolbar.appendChild(this.createLabel(uid, contentElement));
      toolbar.appendChild(this.createActions(uid, contentElement, showContextMenu));

      return toolbar;
    },

    createLabel(uid, contentElement) {
      const container = document.createElement('div');
      container.className = 'frontend-edit__toolbar-label';

      // CType icon
      if (contentElement.element.ctypeIcon) {
        const iconWrapper = document.createElement('span');
        iconWrapper.className = 'frontend-edit__toolbar-icon';
        iconWrapper.innerHTML = contentElement.element.ctypeIcon;
        container.appendChild(iconWrapper);
      }

      // Label text
      const label = document.createElement('span');
      const ctypeLabel = contentElement.element.ctypeLabel || contentElement.element.CType || 'Content';
      label.innerHTML = `${ctypeLabel} <code>${uid}</code>`;
      container.appendChild(label);

      return container;
    },

    createActions(uid, contentElement, showContextMenu) {
      const container = document.createElement('div');
      container.className = 'frontend-edit__toolbar-actions';

      // Edit button
      const editBtn = this.createEditButton(contentElement);
      Tooltip.attach(editBtn);
      container.appendChild(editBtn);

      // Kebab menu button
      if (showContextMenu && contentElement.menu.children && Object.keys(contentElement.menu.children).length > 0) {
        const kebabBtn = this.createKebabButton(uid);
        Tooltip.attach(kebabBtn);
        container.appendChild(kebabBtn);
      }

      return container;
    },

    createEditButton(contentElement) {
      const btn = document.createElement('a');
      btn.className = 'frontend-edit__btn frontend-edit__btn--edit';
      btn.dataset.tooltip = 'Edit';
      btn.innerHTML = ICONS.edit;

      const editAction = contentElement.menu.children?.edit;
      if (editAction?.url) {
        btn.href = editAction.url;
        if (editAction.targetBlank) btn.target = '_blank';
      } else if (contentElement.menu.url) {
        btn.href = contentElement.menu.url;
        if (contentElement.menu.targetBlank) btn.target = '_blank';
      }

      return btn;
    },

    createKebabButton(uid) {
      const btn = document.createElement('button');
      btn.className = 'frontend-edit__btn frontend-edit__btn--kebab';
      btn.dataset.tooltip = 'More actions';
      btn.type = 'button';
      btn.dataset.cid = uid;
      btn.innerHTML = ICONS.kebab;
      return btn;
    },

    createDropdown(uid, contentElement) {
      const dropdown = document.createElement('div');
      dropdown.className = 'frontend-edit__dropdown';
      dropdown.dataset.cid = uid;

      const skipActions = ['edit', 'header', 'div_info'];

      for (const [name, action] of Object.entries(contentElement.menu.children)) {
        if (skipActions.includes(name)) continue;

        const el = document.createElement(action.type === 'link' ? 'a' : 'div');

        if (action.type === 'link') {
          el.href = action.url;
          if (action.targetBlank) el.target = '_blank';
        }

        if (action.type === 'divider') {
          el.className = 'frontend-edit__divider';
        }

        el.classList.add(name);
        el.innerHTML = `${action.icon ?? ''} <span>${action.label}</span>`;
        dropdown.appendChild(el);
      }

      return dropdown;
    }
  };

  /**
   * Data Service - Handles data collection and API communication
   */
  const DataService = {
    getClosestContentElement(element) {
      while (element && !element.id.match(/c\d+/)) {
        element = element.parentElement;
      }
      return element;
    },

    collectDataItems() {
      const dataItems = {};
      const dataElements = document.querySelectorAll('.frontend-edit__data');

      Logger.log(`Found ${dataElements.length} custom additional data elements on page`);

      dataElements.forEach((element, index) => {
        const closestElement = this.getClosestContentElement(element);
        if (!closestElement) return;

        const id = closestElement.id.replace('c', '');
        if (!dataItems[id]) dataItems[id] = [];

        const parsedData = JSON.parse(element.value);
        dataItems[id].push(parsedData);

        Logger.log(`Additional data element ${index + 1}: Found content element c${id}`, { parsedData });
      });

      return dataItems;
    },

    async fetchContentElements(dataItems) {
      const url = new URL(window.location.href);
      url.searchParams.set('type', '1729341864');

      Logger.log('Sending request to backend', { url: url.toString() });

      const response = await fetch(url.toString(), {
        cache: 'no-cache',
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataItems)
      });

      if (!response.ok) {
        throw new Error('Failed to fetch content elements');
      }

      const data = await response.json();
      Logger.log(`Backend response received with ${Object.keys(data).length} content element(s)`);

      return data;
    }
  };

  /**
   * Content Element Renderer - Renders toolbars for content elements
   */
  const Renderer = {
    render(jsonResponse) {
      Logger.log(`Starting DOM assignment for ${Object.keys(jsonResponse).length} content element(s)`);

      const showContextMenu = window.FRONTEND_EDIT_SHOW_CONTEXT_MENU !== false;
      let successful = 0;
      let failed = 0;

      for (let [uid, contentElement] of Object.entries(jsonResponse)) {
        let element = document.querySelector(`#c${uid}`);

        // Handle translation mapping
        if (element?.tagName.toLowerCase() === 'a' && contentElement.element.l10n_source) {
          const l10nElement = document.querySelector(`#c${contentElement.element.l10n_source}`);
          if (l10nElement) {
            Logger.log(`Translation mapping: c${uid} → c${contentElement.element.l10n_source}`);
            uid = contentElement.element.l10n_source;
            element = l10nElement;
          }
        }

        if (!element) {
          failed++;
          Logger.log(`DOM assignment failed: Element c${uid} not found`, null, 'warn');
          continue;
        }

        successful++;
        this.setupContentElement(element, uid, contentElement, showContextMenu);

        Logger.log(`DOM assignment successful: c${uid}`, {
          CType: contentElement.element.CType,
          ctypeLabel: contentElement.element.ctypeLabel,
          showContextMenu
        });
      }

      Logger.log('DOM assignment summary', {
        totalProcessed: Object.keys(jsonResponse).length,
        successfulAssignments: successful,
        failedAssignments: failed
      });
    },

    setupContentElement(element, uid, contentElement, showContextMenu) {
      // Ensure relative positioning
      if (window.getComputedStyle(element).position === 'static') {
        element.style.position = 'relative';
      }

      const hasMenuChildren = contentElement.menu.children && Object.keys(contentElement.menu.children).length > 0;
      const effectiveShowContextMenu = showContextMenu && hasMenuChildren && !contentElement.menu.url;

      // Create and append toolbar
      const toolbar = UI.createToolbar(uid, contentElement, effectiveShowContextMenu);
      element.appendChild(toolbar);

      // Create dropdown if needed
      let dropdown = null;
      if (effectiveShowContextMenu) {
        dropdown = UI.createDropdown(uid, contentElement);
        document.body.appendChild(dropdown);
        this.setupKebabEvents(toolbar, dropdown);
      }

      // Setup hover events
      this.setupHoverEvents(element, dropdown);
    },

    setupKebabEvents(toolbar, dropdown) {
      const kebabBtn = toolbar.querySelector('.frontend-edit__btn--kebab');
      if (!kebabBtn) return;

      kebabBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();

        const isVisible = dropdown.style.display === 'block';
        Dropdown.closeAll();

        if (!isVisible) {
          dropdown.style.display = 'block';
          await Dropdown.position(kebabBtn, dropdown);
        }
      });
    },

    setupHoverEvents(element, dropdown) {
      element.addEventListener('mouseenter', () => {
        element.classList.add('frontend-edit--active');
      });

      element.addEventListener('mouseleave', (e) => {
        if (dropdown?.contains(e.relatedTarget)) return;
        element.classList.remove('frontend-edit--active');
        if (dropdown) dropdown.style.display = 'none';
      });

      if (dropdown) {
        dropdown.addEventListener('mouseleave', (e) => {
          if (element.contains(e.relatedTarget)) return;
          dropdown.style.display = 'none';
          element.classList.remove('frontend-edit--active');
        });
      }
    }
  };

  /**
   * Main Application
   */
  const FrontendEdit = {
    init() {
      document.addEventListener('DOMContentLoaded', () => this.bootstrap());
    },

    async bootstrap() {
      try {
        const startTime = performance.now();

        if (window.FRONTEND_EDIT_DEBUG) {
          Logger.log('Debug mode enabled');
        }

        this.initTheme();

        const dataItems = DataService.collectDataItems();
        const contentElements = await DataService.fetchContentElements(dataItems);

        Renderer.render(contentElements);
        Dropdown.setupGlobalHandler();

        Logger.log(`Frontend Edit initialization completed in ${Math.round(performance.now() - startTime)}ms`);
      } catch (error) {
        Logger.log('Frontend Edit initialization failed', {
          error: error.message,
          stack: error.stack
        }, 'error');
      }
    },

    initTheme() {
      const colorScheme = window.FRONTEND_EDIT_COLOR_SCHEME || 'auto';
      document.documentElement.setAttribute('data-xfe-theme', colorScheme);
      Logger.log(`Theme initialized: ${colorScheme}`);
    }
  };

  // Start application
  FrontendEdit.init();
})();
