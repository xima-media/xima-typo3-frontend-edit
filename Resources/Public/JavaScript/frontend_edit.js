/**
 * TYPO3 Frontend Edit
 * Provides inline editing capabilities for content elements in the frontend.
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
    edit: '<svg viewBox="0 0 32 32" fill="currentColor"><path d="M4.834,29.665L25.007,29.665C26.561,29.663 27.839,28.385 27.841,26.831L27.841,16.157C27.841,15.608 27.39,15.157 26.841,15.157C26.292,15.157 25.841,15.608 25.841,16.157L25.841,26.831C25.84,27.288 25.464,27.664 25.007,27.665L4.834,27.665C4.377,27.664 4.001,27.288 4,26.831L4,7.651C4.001,7.194 4.377,6.818 4.834,6.817L16,6.817C16.549,6.817 17,6.366 17,5.817C17,5.268 16.549,4.817 16,4.817L4.834,4.817C3.28,4.819 2.002,6.097 2,7.651L2,26.831C2.002,28.385 3.28,29.663 4.834,29.665Z" fill-rule="nonzero"/><path d="M8.582,19.343L7.912,22.691C7.894,22.781 7.885,22.873 7.885,22.965C7.885,23.726 8.51,24.352 9.271,24.352C9.363,24.352 9.454,24.343 9.544,24.325L12.895,23.655C13.539,23.527 14.131,23.211 14.595,22.747L28.845,8.494C29.473,7.825 29.823,6.941 29.823,6.024C29.823,4.044 28.195,2.416 26.215,2.416C25.298,2.416 24.414,2.766 23.745,3.394L9.49,17.645C9.025,18.108 8.709,18.699 8.582,19.343ZM10.543,19.734C10.594,19.478 10.72,19.244 10.904,19.059L25.157,4.806C25.458,4.509 25.864,4.343 26.286,4.343C27.168,4.343 27.894,5.069 27.894,5.951C27.894,6.373 27.728,6.779 27.431,7.08L13.178,21.332C12.993,21.517 12.758,21.643 12.502,21.694L10.054,22.184L10.543,19.734Z" fill-rule="nonzero"/></svg>',
    kebab: '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="2.5" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="13.5" r="1.5"/></svg>',
    check: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 111.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/></svg>',
    warning: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
    error: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0-1.4A5.6 5.6 0 1 0 8 2.4a5.6 5.6 0 0 0 0 11.2zM7.3 5h1.4v4.2H7.3V5zm0 5.6h1.4V12H7.3v-1.4z"/></svg>',
    info: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0-1.4A5.6 5.6 0 1 0 8 2.4a5.6 5.6 0 0 0 0 11.2zM7.3 7h1.4v4.2H7.3V7zm0-2.1h1.4v1.4H7.3V4.9z"/></svg>',
    close: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.75.75 0 1 1 1.06 1.06L9.06 8l3.22 3.22a.75.75 0 1 1-1.06 1.06L8 9.06l-3.22 3.22a.75.75 0 0 1-1.06-1.06L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06z"/></svg>'
  };

  /**
   * Debug Logger
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
   * Tooltip Manager
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
   * Dropdown Manager
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
   * Notification Manager - Shows toast notifications for flash messages
   */
  const Notification = {
    container: null,
    autoDismissDelay: 5000,

    /**
     * Initialize notifications by reading flash messages from DOM
     */
    init() {
      const dataElement = document.querySelector('.frontend-edit-flash-messages');
      if (!dataElement) return;

      try {
        const messages = JSON.parse(dataElement.textContent || '[]');
        if (messages.length > 0) {
          Logger.log(`Found ${messages.length} flash message(s) to display`);
          messages.forEach((msg, index) => {
            // Stagger notifications slightly for better UX
            setTimeout(() => this.show(msg), index * 150);
          });
        }
      } catch (error) {
        Logger.log('Failed to parse flash messages', { error: error.message }, 'error');
      }
    },

    /**
     * Get or create the notification container
     */
    getContainer() {
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.className = 'frontend-edit__notification-container';
        document.body.appendChild(this.container);
      }
      return this.container;
    },

    /**
     * Get icon for severity
     */
    getIcon(severity) {
      const severityLower = severity.toLowerCase();
      switch (severityLower) {
        case 'ok':
          return ICONS.check;
        case 'warning':
          return ICONS.warning;
        case 'error':
          return ICONS.error;
        case 'info':
        case 'notice':
        default:
          return ICONS.info;
      }
    },

    /**
     * Show a notification
     */
    show(message) {
      const container = this.getContainer();

      const notification = document.createElement('div');
      notification.className = 'frontend-edit__notification';
      notification.classList.add(`frontend-edit__notification--${message.severity.toLowerCase()}`);

      // Create icon
      const iconEl = document.createElement('span');
      iconEl.className = 'frontend-edit__notification-icon';
      iconEl.innerHTML = this.getIcon(message.severity);
      notification.appendChild(iconEl);

      // Create content
      const contentEl = document.createElement('div');
      contentEl.className = 'frontend-edit__notification-content';

      if (message.title) {
        const titleEl = document.createElement('div');
        titleEl.className = 'frontend-edit__notification-title';
        titleEl.textContent = message.title;
        contentEl.appendChild(titleEl);
      }

      if (message.message) {
        const messageEl = document.createElement('div');
        messageEl.className = 'frontend-edit__notification-message';
        messageEl.textContent = message.message;
        contentEl.appendChild(messageEl);
      }

      notification.appendChild(contentEl);

      // Create close button
      const closeBtn = document.createElement('button');
      closeBtn.className = 'frontend-edit__notification-close';
      closeBtn.type = 'button';
      closeBtn.innerHTML = ICONS.close;
      closeBtn.addEventListener('click', () => this.dismiss(notification));
      notification.appendChild(closeBtn);

      container.appendChild(notification);

      // Trigger animation
      requestAnimationFrame(() => {
        notification.classList.add('frontend-edit__notification--visible');
      });

      // Auto-dismiss
      setTimeout(() => this.dismiss(notification), this.autoDismissDelay);

      Logger.log('Showing notification', {
        severity: message.severity,
        title: message.title,
        message: message.message
      });
    },

    /**
     * Dismiss a notification
     */
    dismiss(notification) {
      if (!notification || !notification.parentNode) return;

      notification.classList.remove('frontend-edit__notification--visible');
      notification.classList.add('frontend-edit__notification--hiding');

      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }
  };

  /**
   * Element Resolver - Handles anchor patterns and finds the actual content element
   */
  const ElementResolver = {
    /**
     * Check if element is an empty anchor (just an ID carrier)
     */
    isEmptyAnchor(element) {
      if (element.tagName.toLowerCase() !== 'a') return false;

      // Check if anchor has no meaningful content
      const hasNoContent = element.children.length === 0 &&
                          element.textContent.trim() === '';

      // Check if anchor has no href or has empty href
      const hasNoHref = !element.href || element.getAttribute('href') === '';

      return hasNoContent || hasNoHref;
    },

    /**
     * Find the actual content element for a given ID element
     * Handles the pattern: <a id="c123"></a><div class="content">
     */
    resolveContentElement(idElement) {
      if (!this.isEmptyAnchor(idElement)) {
        return idElement;
      }

      // Look for next sibling that is an element (not text node)
      let sibling = idElement.nextElementSibling;

      // Skip empty text nodes or other anchors
      while (sibling && this.isEmptyAnchor(sibling)) {
        sibling = sibling.nextElementSibling;
      }

      if (sibling) {
        Logger.log(`Anchor pattern detected: Using next sibling for #${idElement.id}`, {
          anchor: idElement.outerHTML.substring(0, 50),
          sibling: sibling.tagName + (sibling.className ? '.' + sibling.className.split(' ')[0] : '')
        });
        return sibling;
      }

      // Fallback to original element
      return idElement;
    }
  };

  /**
   * Overlay Manager - Handles toolbar positioning as fixed overlays
   */
  const OverlayManager = {
    container: null,
    overlays: new Map(), // Map<targetElement, {toolbar, outline}>
    scrollRAF: null,

    /**
     * Check if a content element is nested inside another content element
     * Used to apply different toolbar positioning for nested elements
     */
    isNestedContentElement(targetElement) {
      let parent = targetElement.parentElement;
      while (parent) {
        // Check if parent has content element ID pattern
        if (parent.id && /^c\d+$/.test(parent.id)) {
          return true;
        }
        // Also check for anchor pattern: <a id="c123"></a><div>
        if (parent.previousElementSibling?.id && /^c\d+$/.test(parent.previousElementSibling.id)) {
          return true;
        }
        parent = parent.parentElement;
      }
      return false;
    },

    init() {
      // Create overlay container
      this.container = document.createElement('div');
      this.container.className = 'frontend-edit__overlay-container';
      this.container.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:10000;overflow:visible;';
      document.body.appendChild(this.container);

      // Setup scroll/resize handlers
      this.setupEventHandlers();
    },

    setupEventHandlers() {
      const updatePositions = () => {
        if (this.scrollRAF) return;
        this.scrollRAF = requestAnimationFrame(() => {
          this.updateAllPositions();
          this.scrollRAF = null;
        });
      };

      window.addEventListener('scroll', updatePositions, { passive: true });
      window.addEventListener('resize', updatePositions, { passive: true });
    },

    createOverlay(uid, targetElement, contentElement, showContextMenu, enableOutline = true) {
      const overlay = document.createElement('div');
      overlay.className = 'frontend-edit__overlay';
      overlay.dataset.cid = uid;
      overlay.style.cssText = 'position:absolute;pointer-events:none;';

      // Add nested modifier for elements inside other content elements
      if (this.isNestedContentElement(targetElement)) {
        overlay.classList.add('frontend-edit__overlay--nested');
      }

      // Create outline element (only if enabled)
      let outline = null;
      if (enableOutline) {
        outline = document.createElement('div');
        outline.className = 'frontend-edit__outline';
        overlay.appendChild(outline);
      }

      // Create toolbar
      const toolbar = UI.createToolbar(uid, contentElement, showContextMenu);
      toolbar.style.pointerEvents = 'auto';

      overlay.appendChild(toolbar);
      this.container.appendChild(overlay);

      // Store reference
      this.overlays.set(targetElement, { overlay, toolbar, outline, uid });

      // Initial position
      this.updatePosition(targetElement);

      return { overlay, toolbar };
    },

    updatePosition(targetElement) {
      const data = this.overlays.get(targetElement);
      if (!data) return;

      const rect = targetElement.getBoundingClientRect();
      const { overlay, outline } = data;

      // Update overlay position and size
      overlay.style.left = `${rect.left}px`;
      overlay.style.top = `${rect.top}px`;
      overlay.style.width = `${rect.width}px`;
      overlay.style.height = `${rect.height}px`;

      // Update outline to match (only if outline exists)
      if (outline) {
        outline.style.cssText = `
          position: absolute;
          inset: -1px;
          border-radius: 2px;
          pointer-events: none;
        `;
      }

      // Position toolbar at bottom if element is near top of viewport
      const toolbarHeight = 20; // Approximate height of toolbar
      if (rect.top < toolbarHeight) {
        overlay.classList.add('frontend-edit__overlay--bottom');
      } else {
        overlay.classList.remove('frontend-edit__overlay--bottom');
      }
    },

    updateAllPositions() {
      this.overlays.forEach((_, targetElement) => {
        this.updatePosition(targetElement);
      });
    },

    setActive(targetElement, active) {
      const data = this.overlays.get(targetElement);
      if (!data) return;

      if (active) {
        data.overlay.classList.add('frontend-edit__overlay--active');
        this.updatePosition(targetElement);
      } else {
        data.overlay.classList.remove('frontend-edit__overlay--active');
      }
    },

    getToolbar(targetElement) {
      const data = this.overlays.get(targetElement);
      return data?.toolbar;
    }
  };

  /**
   * UI Factory
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

      // Icons are trusted HTML from TYPO3 backend (IconFactory)
      if (contentElement.element.ctypeIcon) {
        const iconWrapper = document.createElement('span');
        iconWrapper.className = 'frontend-edit__toolbar-icon';
        iconWrapper.innerHTML = contentElement.element.ctypeIcon;
        container.appendChild(iconWrapper);
      }

      const label = document.createElement('span');
      const ctypeLabel = contentElement.element.ctypeLabel || contentElement.element.CType || 'Content';
      // Use textContent for label to prevent XSS, append code element separately
      label.textContent = ctypeLabel + ' ';
      const codeEl = document.createElement('code');
      codeEl.textContent = uid;
      label.appendChild(codeEl);
      container.appendChild(label);

      return container;
    },

    createActions(uid, contentElement, showContextMenu) {
      const container = document.createElement('div');
      container.className = 'frontend-edit__toolbar-actions';

      const editBtn = this.createEditButton(contentElement);
      Tooltip.attach(editBtn);
      container.appendChild(editBtn);

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
      if (editAction?.url && this.isValidUrl(editAction.url)) {
        btn.href = editAction.url;
        if (editAction.targetBlank) btn.target = '_blank';
      } else if (contentElement.menu.url && this.isValidUrl(contentElement.menu.url)) {
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

      const skipActions = ['header'];

      for (const [name, action] of Object.entries(contentElement.menu.children)) {
        if (skipActions.includes(name)) continue;

        const el = document.createElement(action.type === 'link' ? 'a' : 'div');

        if (action.type === 'link') {
          // Validate URL to prevent javascript: protocol attacks
          if (action.url && this.isValidUrl(action.url)) {
            el.href = action.url;
          }
          if (action.targetBlank) el.target = '_blank';
        }

        if (action.type === 'divider') {
          el.className = 'frontend-edit__divider';
        }

        if (action.type === 'info') {
          el.className = 'frontend-edit__info';
        }

        // Sanitize class name to prevent injection
        const safeName = this.escapeClassName(name);
        if (safeName) {
          el.classList.add(safeName);
        }

        // Icons are trusted HTML from TYPO3 backend (IconFactory)
        if (action.icon) {
          const iconWrapper = document.createElement('span');
          iconWrapper.innerHTML = action.icon;
          el.appendChild(iconWrapper);
        }

        const labelSpan = document.createElement('span');
        if (action.type === 'info') {
          labelSpan.innerHTML = action.label || '';
        } else {
          labelSpan.textContent = action.label || '';
        }
        el.appendChild(labelSpan);

        dropdown.appendChild(el);
      }

      return dropdown;
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
    }
  };

  /**
   * Data Service
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
      const allUids = new Set();

      // Scan DOM for all content elements by id="c{uid}" pattern
      // This enables editing content from other pages (onepager scenarios)
      document.querySelectorAll('[id]').forEach(element => {
        const match = element.id.match(/^c(\d+)$/);
        if (match) {
          const uid = parseInt(match[1], 10);
          if (uid > 0) {
            allUids.add(uid);
          }
        }
      });

      Logger.log(`Found ${allUids.size} content elements in DOM with id="c{uid}" pattern`);

      // Collect additional data from .frontend-edit__data elements
      const dataElements = document.querySelectorAll('.frontend-edit__data');

      Logger.log(`Found ${dataElements.length} custom additional data elements on page`);

      dataElements.forEach((element, index) => {
        const closestElement = this.getClosestContentElement(element);
        if (!closestElement) return;

        const id = closestElement.id.replace('c', '');
        const uid = parseInt(id, 10);

        if (!dataItems[uid]) dataItems[uid] = [];

        const parsedData = JSON.parse(element.value);
        dataItems[uid].push(parsedData);

        // Ensure this UID is included
        if (uid > 0) {
          allUids.add(uid);
        }

        Logger.log(`Additional data element ${index + 1}: Found content element c${uid}`, { parsedData });
      });

      // Add UIDs array for backend to fetch content elements
      dataItems._uids = Array.from(allUids).sort((a, b) => a - b);

      Logger.log(`Collected ${allUids.size} unique content element UIDs for backend request`, {
        uids: dataItems._uids
      });

      return dataItems;
    },

    async fetchContentElements(dataItems) {
      const config = document.getElementById('frontend-edit-toolbar-config');
      if (!config) {
        throw new Error('Frontend edit configuration not found');
      }

      const editInfoUrl = config.dataset.editInfoUrl;
      const pid = config.dataset.pid;
      const language = config.dataset.language;
      const returnUrl = window.location.href;

      const url = new URL(editInfoUrl, window.location.origin);
      url.searchParams.set('pid', pid);
      url.searchParams.set('language', language);
      url.searchParams.set('returnUrl', returnUrl);

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
   * Renderer
   */
  const Renderer = {
    render(jsonResponse) {
      Logger.log(`Starting DOM assignment for ${Object.keys(jsonResponse).length} content element(s)`);

      const showContextMenu = window.FRONTEND_EDIT_SHOW_CONTEXT_MENU !== false;
      const enableOutline = window.FRONTEND_EDIT_ENABLE_OUTLINE !== false;
      let successful = 0;
      let failed = 0;

      for (let [uid, contentElement] of Object.entries(jsonResponse)) {
        let idElement = document.querySelector(`#c${uid}`);

        // Handle translation mapping
        if (idElement?.tagName.toLowerCase() === 'a' && contentElement.element.l10n_source) {
          const l10nElement = document.querySelector(`#c${contentElement.element.l10n_source}`);
          if (l10nElement) {
            Logger.log(`Translation mapping: c${uid} → c${contentElement.element.l10n_source}`);
            uid = contentElement.element.l10n_source;
            idElement = l10nElement;
          }
        }

        if (!idElement) {
          failed++;
          Logger.log(`DOM assignment failed: Element c${uid} not found`, null, 'warn');
          continue;
        }

        // Resolve actual content element (handles anchor pattern)
        const targetElement = ElementResolver.resolveContentElement(idElement);

        successful++;
        this.setupContentElement(targetElement, uid, contentElement, showContextMenu, enableOutline);

        Logger.log(`DOM assignment successful: c${uid}`, {
          CType: contentElement.element.CType,
          ctypeLabel: contentElement.element.ctypeLabel,
          showContextMenu,
          enableOutline,
          usedSibling: targetElement !== idElement
        });
      }

      Logger.log('DOM assignment summary', {
        totalProcessed: Object.keys(jsonResponse).length,
        successfulAssignments: successful,
        failedAssignments: failed
      });
    },

    setupContentElement(targetElement, uid, contentElement, showContextMenu, enableOutline) {
      const hasMenuChildren = contentElement.menu.children && Object.keys(contentElement.menu.children).length > 0;
      const effectiveShowContextMenu = showContextMenu && hasMenuChildren && !contentElement.menu.url;

      // Create overlay with toolbar
      const { toolbar } = OverlayManager.createOverlay(uid, targetElement, contentElement, effectiveShowContextMenu, enableOutline);

      // Create dropdown if needed
      let dropdown = null;
      if (effectiveShowContextMenu) {
        dropdown = UI.createDropdown(uid, contentElement);
        document.body.appendChild(dropdown);
        this.setupKebabEvents(toolbar, dropdown);
      }

      // Setup hover events
      this.setupHoverEvents(targetElement, dropdown);
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

    setupHoverEvents(targetElement, dropdown) {
      targetElement.addEventListener('mouseenter', () => {
        OverlayManager.setActive(targetElement, true);
      });

      targetElement.addEventListener('mouseleave', (e) => {
        if (dropdown?.contains(e.relatedTarget)) return;

        const toolbar = OverlayManager.getToolbar(targetElement);
        if (toolbar?.contains(e.relatedTarget)) return;

        OverlayManager.setActive(targetElement, false);
        if (dropdown) dropdown.style.display = 'none';
      });

      // Keep active when hovering toolbar
      const toolbar = OverlayManager.getToolbar(targetElement);
      if (toolbar) {
        toolbar.addEventListener('mouseenter', () => {
          OverlayManager.setActive(targetElement, true);
        });

        toolbar.addEventListener('mouseleave', (e) => {
          if (targetElement.contains(e.relatedTarget)) return;
          if (dropdown?.contains(e.relatedTarget)) return;

          OverlayManager.setActive(targetElement, false);
          if (dropdown) dropdown.style.display = 'none';
        });
      }

      if (dropdown) {
        dropdown.addEventListener('mouseleave', (e) => {
          if (targetElement.contains(e.relatedTarget)) return;
          if (toolbar?.contains(e.relatedTarget)) return;

          dropdown.style.display = 'none';
          OverlayManager.setActive(targetElement, false);
        });
      }
    }
  };

  /**
   * Main Application
   */
  const FrontendEdit = {
    init() {
      // Wait for both DOM and FloatingUI to be ready
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => this.checkAndBootstrap());
      } else {
        this.checkAndBootstrap();
      }
    },

    checkAndBootstrap() {
      if (window.FloatingUIDOM) {
        initFloatingUI();
        this.bootstrap();
      } else {
        window.addEventListener('floatingui:ready', () => {
          initFloatingUI();
          this.bootstrap();
        }, { once: true });
      }
    },

    async bootstrap() {
      try {
        const startTime = performance.now();

        if (window.FRONTEND_EDIT_DEBUG) {
          Logger.log('Debug mode enabled');
        }

        this.initTheme();

        // Initialize flash message notifications (always, even when editing is disabled)
        Notification.init();

        // Only initialize content element editing if not disabled
        if (!window.FRONTEND_EDIT_DISABLED) {
          OverlayManager.init();

          const dataItems = DataService.collectDataItems();
          const contentElements = await DataService.fetchContentElements(dataItems);

          Renderer.render(contentElements);
          Dropdown.setupGlobalHandler();
        }

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

  FrontendEdit.init();
})();
