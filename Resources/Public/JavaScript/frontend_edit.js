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
    add: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
    check: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 111.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/></svg>',
    warning: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
    error: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0-1.4A5.6 5.6 0 1 0 8 2.4a5.6 5.6 0 0 0 0 11.2zM7.3 5h1.4v4.2H7.3V5zm0 5.6h1.4V12H7.3v-1.4z"/></svg>',
    info: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0-1.4A5.6 5.6 0 1 0 8 2.4a5.6 5.6 0 0 0 0 11.2zM7.3 7h1.4v4.2H7.3V7zm0-2.1h1.4v1.4H7.3V4.9z"/></svg>',
    close: '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.75.75 0 1 1 1.06 1.06L9.06 8l3.22 3.22a.75.75 0 1 1-1.06 1.06L8 9.06l-3.22 3.22a.75.75 0 0 1-1.06-1.06L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06z"/></svg>',
    drag: '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="6" cy="3" r="1.3"/><circle cx="10" cy="3" r="1.3"/><circle cx="6" cy="8" r="1.3"/><circle cx="10" cy="8" r="1.3"/><circle cx="6" cy="13" r="1.3"/><circle cx="10" cy="13" r="1.3"/></svg>'
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
   * Icon registry - resolves the short keys the backend substitutes for
   * repeated icon markup in the editInformation response (element.ctypeIcon,
   * menu button .icon) back to their actual SVG markup. See issue #217:
   * without this, the same handful of icons (edit, info, history, ...) are
   * repeated once per button per content element, dominating the payload
   * on any page with more than a handful of elements.
   */
  const Icons = {
    map: {},

    populate(icons) {
      Object.assign(this.map, icons || {});
    },

    resolve(key) {
      return this.map[key] || '';
    }
  };

  /**
   * DOM attribute helpers.
   *
   * Read id/class from the content attribute instead of the IDL property to
   * avoid two footguns that can make the property a non-string:
   * - DOM clobbering: a <form> containing a control named or id'd "id" shadows
   *   `form.id` with that control (HTMLFormElement named-property getter), so
   *   `element.id.match(...)` throws "match is not a function".
   * - SVG: `svgElement.className` is an SVGAnimatedString object, not a string,
   *   so reading `.className` directly and calling string methods throws.
   * getAttribute() always returns a string (or null) and sidesteps both.
   */
  const Dom = {
    id(element) {
      return element && typeof element.getAttribute === 'function'
        ? (element.getAttribute('id') || '')
        : '';
    }
  };

  /**
   * Finds (or lazily creates) the badge slot for one corner of a content
   * element's overlay - see PublicApi.registerBadge. The slot is a plain DOM
   * child of the overlay, so it automatically inherits the overlay's
   * position tracking (scroll, resize, nested elements) with no extra code.
   */
  function getBadgeSlot(overlay, position) {
    const slotClass = 'frontend-edit__badge-slot--' + position;
    let slot = Array.from(overlay.children).find(child => child.classList.contains(slotClass));
    if (!slot) {
      slot = document.createElement('div');
      slot.className = 'frontend-edit__badge-slot ' + slotClass;
      overlay.appendChild(slot);
    }
    return slot;
  }

  /**
   * Dispatches the public `xfe:*` lifecycle events (see PublicApi below) on
   * `document`, so third-party listeners can use standard event delegation.
   */
  const Events = {
    dispatch(name, detail) {
      document.dispatchEvent(new CustomEvent(name, { detail }));
    }
  };

  /**
   * Registry - Tracks rendered content elements for the public API
   * (PublicApi.getElementInfo/registerBadge/registerToolbarItem) and the
   * `xfe:ready` element snapshot.
   *
   * Entries are stored under both the DOM anchor uid and the record's own
   * uid (see Renderer.render()'s translation mapping) so consumers can look
   * elements up by either.
   */
  const Registry = {
    entries: new Map(), // Map<number uid, {uid, element, payload, overlay, toolbar, dropdown}>

    set(uid, entry) {
      this.entries.set(Number(uid), entry);
    },

    get(uid) {
      return this.entries.get(Number(uid)) || null;
    },

    /**
     * Plain-object snapshot for `xfe:ready`, keyed by the record's own uid and
     * deduplicated so translation-mapped entries (registered under two uids)
     * appear once.
     */
    snapshot() {
      const seen = new Set();
      const out = {};
      this.entries.forEach((entry) => {
        if (seen.has(entry)) return;
        seen.add(entry);
        out[entry.uid] = { uid: entry.uid, element: entry.element, payload: entry.payload };
      });
      return out;
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
        // Only fire xfe:dropdown-close for dropdowns that were actually open.
        // closeAll() also runs on every hover switch (OverlayManager.updateActiveFromPointer),
        // so firing unconditionally would spam the event on plain mouse movement.
        if (d.style.display === 'block') {
          Events.dispatch('xfe:dropdown-close', { uid: Number(d.dataset.cid) });
        }
        d.style.display = 'none';
      });
      document.querySelectorAll('.frontend-edit__btn--kebab').forEach(btn => {
        btn.setAttribute('aria-expanded', 'false');
      });
    },

    setupGlobalHandler() {
      document.addEventListener('click', (e) => {
        if (!e.target.closest('.frontend-edit__btn--kebab') &&
            !e.target.closest('.frontend-edit__dropdown')) {
          this.closeAll();
        }
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          this.closeAll();
        }
      });
    }
  };

  /**
   * Delete Handler - Confirmation dialog + fetch-based deletion
   * Labels are provided by ResourceRendererService via window.FRONTEND_EDIT_DELETE_LABELS
   */
  const DeleteHandler = {
    labels() {
      return window.FRONTEND_EDIT_DELETE_LABELS || {};
    },

    init() {
      document.addEventListener('click', (e) => {
        const link = e.target.closest('.frontend-edit__dropdown a.delete');
        if (!link?.href) return;

        e.preventDefault();
        e.stopPropagation();
        Dropdown.closeAll();

        // Read record info from data attributes (set in createDropdown)
        const recordTitle = link.dataset.recordTitle || '';
        const table = link.dataset.recordTable || 'tt_content';
        const uid = link.dataset.recordUid || '';

        this.confirm(uid, table, recordTitle).then((confirmed) => {
          if (confirmed) this.execute(link.href);
        });
      });
    },

    confirm(uid, table, recordTitle) {
      const l = this.labels();
      return new Promise((resolve) => {
        const previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        const overlay = document.createElement('div');
        overlay.className = 'frontend-edit__dialog-overlay';

        const modalDialog = document.createElement('div');
        modalDialog.className = 'frontend-edit__dialog modal-dialog';
        modalDialog.setAttribute('role', 'alertdialog');
        modalDialog.setAttribute('aria-modal', 'true');
        modalDialog.setAttribute('aria-labelledby', 'fe-dialog-title');

        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';

        const modalHeader = document.createElement('div');
        modalHeader.className = 'modal-header';
        const title = document.createElement('h4');
        title.id = 'fe-dialog-title';
        title.className = 'modal-title';
        title.textContent = l.title || 'Delete this record?';
        const closeBtn = document.createElement('button');
        closeBtn.className = 'btn-close';
        closeBtn.type = 'button';
        closeBtn.innerHTML = ICONS.close;
        closeBtn.setAttribute('aria-label', 'Close');
        modalHeader.appendChild(title);
        modalHeader.appendChild(closeBtn);

        const modalBody = document.createElement('div');
        modalBody.className = 'modal-body';
        const p = document.createElement('p');
        const recordInfo = recordTitle
          ? `${recordTitle} [${table}:${uid}]`
          : `[${table}:${uid}]`;
        p.textContent = (l.message || "Are you sure you want to delete the record '%s'?").replace('%s', recordInfo);
        modalBody.appendChild(p);

        const modalFooter = document.createElement('div');
        modalFooter.className = 'modal-footer';
        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'btn btn-default';
        cancelBtn.type = 'button';
        cancelBtn.textContent = l.cancel || 'Cancel';
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-warning';
        deleteBtn.type = 'button';
        deleteBtn.textContent = l.delete || 'Delete record (!)';
        modalFooter.appendChild(cancelBtn);
        modalFooter.appendChild(deleteBtn);

        modalContent.appendChild(modalHeader);
        modalContent.appendChild(modalBody);
        modalContent.appendChild(modalFooter);
        modalDialog.appendChild(modalContent);
        overlay.appendChild(modalDialog);
        document.body.appendChild(overlay);
        requestAnimationFrame(() => overlay.classList.add('frontend-edit__dialog-overlay--show'));

        const onEsc = (e) => { if (e.key === 'Escape') close(false); };
        const close = (result) => {
          document.removeEventListener('keydown', onEsc);
          overlay.classList.remove('frontend-edit__dialog-overlay--show');
          setTimeout(() => overlay.remove(), 200);
          if (previousFocus && document.contains(previousFocus)) {
            previousFocus.focus();
          }
          resolve(result);
        };

        closeBtn.addEventListener('click', () => close(false));
        cancelBtn.addEventListener('click', () => close(false));
        deleteBtn.addEventListener('click', () => close(true));
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(false); });
        document.addEventListener('keydown', onEsc);

        deleteBtn.focus();
      });
    },

    execute(url) {
      const l = this.labels();
      fetch(url, { method: 'GET', credentials: 'include', redirect: 'manual' })
        .then((response) => {
          if (response.type === 'opaqueredirect' || response.ok) {
            Notification.show({ title: l.success || 'Record deleted', message: l.successMessage || '', severity: 'ok' });
            setTimeout(() => window.location.reload(), 1500);
          } else {
            Notification.show({ title: l.error || 'Could not delete the record', message: '', severity: 'error' });
          }
        })
        .catch(() => {
          Notification.show({ title: l.error || 'Could not delete the record', message: '', severity: 'error' });
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
      // If the previous action was a "new content" create flow, relabel the
      // backend's generic success flash ("Record saved") to a create-specific
      // message. Consumed unconditionally so the flag never leaks to a later load.
      // Only OK-severity messages are relabelled; warnings/errors pass through.
      let createdFlow = false;
      try {
        if (sessionStorage.getItem('xfe-content-created')) {
          sessionStorage.removeItem('xfe-content-created');
          createdFlow = true;
        }
      } catch (_) { /* sessionStorage unavailable */ }

      const dataElement = document.querySelector('.frontend-edit-flash-messages');
      if (!dataElement) return;

      try {
        let messages = JSON.parse(dataElement.textContent || '[]');
        if (createdFlow) {
          const labels = window.FRONTEND_EDIT_NOTIFICATION_LABELS || {};
          messages = messages.map(msg => String(msg.severity || '').toUpperCase() === 'OK'
            ? { title: labels.contentCreated || 'Content element created', message: msg.message, severity: msg.severity }
            : msg);
        }
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
        this.container.setAttribute('role', 'status');
        this.container.setAttribute('aria-live', 'polite');
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
      if (message.severity.toLowerCase() === 'error') {
        notification.setAttribute('role', 'alert');
      }

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
      closeBtn.setAttribute('aria-label', 'Dismiss notification');
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
        const siblingClass = sibling.getAttribute('class') || '';
        Logger.log(`Anchor pattern detected: Using next sibling for #${Dom.id(idElement)}`, {
          anchor: idElement.outerHTML.substring(0, 50),
          sibling: sibling.tagName + (siblingClass ? '.' + siblingClass.split(' ')[0] : '')
        });
        return sibling;
      }

      // Fallback to original element
      return idElement;
    },

    /**
     * Locate the DOM anchor for a content element uid: the id="c{uid}"
     * pattern first (existing behavior, unchanged), falling back to
     * data-frontend-edit="tt_content:{uid}" - a direct target, since a
     * hand-placed data attribute is never an anchor-sibling placeholder the
     * way an empty <a id="c123"></a> is.
     *
     * @returns {{element: Element, isDirectTarget: boolean}|null}
     */
    findAnchor(uid) {
      const idElement = document.querySelector(`#c${uid}`);
      if (idElement) return { element: idElement, isDirectTarget: false };

      const dataElement = document.querySelector(`[data-frontend-edit="tt_content:${uid}"]`);
      if (dataElement) return { element: dataElement, isDirectTarget: true };

      return null;
    }
  };

  /**
   * Overlay Manager - Handles toolbar positioning as fixed overlays
   */
  const OverlayManager = {
    container: null,
    overlays: new Map(), // Map<targetElement, {toolbar, outline}>
    scrollRAF: null,
    activeElement: null,
    pointerRAF: null,
    lastPointer: { x: 0, y: 0 },

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

      // Single source of truth for which element is highlighted: the element the
      // cursor is geometrically inside. This avoids mouseenter/leave races between
      // adjacent elements and their overlay buttons — the highlight stays put
      // until the cursor actually crosses into another element's box.
      this.setupPointerTracking();
    },

    setupPointerTracking() {
      const onMove = (e) => {
        this.lastPointer = { x: e.clientX, y: e.clientY };
        if (this.pointerRAF) return;
        this.pointerRAF = requestAnimationFrame(() => {
          this.pointerRAF = null;
          this.updateActiveFromPointer();
        });
      };
      document.addEventListener('mousemove', onMove, { passive: true });
    },

    updateActiveFromPointer() {
      const { x, y } = this.lastPointer;
      const el = document.elementFromPoint(x, y);

      // While the cursor is over an overlay control (toolbar, insert button,
      // badge) or an open dropdown, keep the current highlight — same as
      // Edit/More Actions. Without the badge here, hovering an interactive
      // badge (registerBadge's onClick) would drop the outline/toolbar
      // highlight the instant the cursor entered it.
      if (el && el.closest('.frontend-edit__toolbar, .frontend-edit__insert-btn, .frontend-edit__dropdown, .frontend-edit__badge')) {
        return;
      }

      const next = this.resolveRegisteredElement(el);
      if (next === this.activeElement) return;

      // Hysteresis: keep the current element highlighted while the cursor is
      // still within its box (≈ the dashed outline), so the highlight only
      // switches when the cursor actually crosses the outline — symmetrically in
      // every direction. (Without this, the wide top toolbar bridges upward hover
      // but the narrow bottom button does not, so downward switched too early.)
      // Moving into a nested child element still takes over immediately.
      const enteringNested = next && this.activeElement && this.activeElement.contains(next);
      if (!enteringNested && this.activeElement && this.rectContains(this.activeElement, x, y)) {
        return;
      }

      if (this.activeElement) {
        this.setActive(this.activeElement, false);
        Dropdown.closeAll();
      }
      if (next) {
        this.setActive(next, true);
      }
      this.activeElement = next;
    },

    rectContains(el, x, y, margin = 2) {
      const r = el.getBoundingClientRect();
      return x >= r.left - margin && x <= r.right + margin && y >= r.top - margin && y <= r.bottom + margin;
    },

    /**
     * Walk up from a node to the nearest registered content element (innermost
     * first, so nested elements highlight correctly). Returns null if none.
     */
    resolveRegisteredElement(node) {
      while (node) {
        if (this.overlays.has(node)) return node;
        node = node.parentElement;
      }
      return null;
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

      // Hover insert buttons (before/after), placed in the overlay layer so they
      // render above the dashed outline and stay reachable via the hover bridge.
      const insertButtons = UI.createInsertButtons(contentElement);
      insertButtons.forEach(btn => overlay.appendChild(btn));

      this.container.appendChild(overlay);

      // Store reference
      this.overlays.set(targetElement, { overlay, toolbar, outline, uid, insertButtons });

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
      toolbar.setAttribute('role', 'toolbar');
      toolbar.setAttribute('aria-label', 'Edit content element ' + uid);

      toolbar.appendChild(this.createLabel(uid, contentElement));
      toolbar.appendChild(this.createActions(uid, contentElement, showContextMenu));

      const buttons = toolbar.querySelectorAll('.frontend-edit__btn');
      buttons.forEach((btn, i) => {
        btn.setAttribute('tabindex', i === 0 ? '0' : '-1');
      });
      toolbar.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
        const btns = Array.from(toolbar.querySelectorAll('.frontend-edit__btn'));
        const idx = btns.indexOf(document.activeElement);
        if (idx === -1) return;
        e.preventDefault();
        const next = e.key === 'ArrowRight'
          ? (idx + 1) % btns.length
          : (idx - 1 + btns.length) % btns.length;
        btns[idx].setAttribute('tabindex', '-1');
        btns[next].setAttribute('tabindex', '0');
        btns[next].focus();
      });

      return toolbar;
    },

    createLabel(uid, contentElement) {
      const container = document.createElement('div');
      container.className = 'frontend-edit__toolbar-label';

      // Icons are trusted HTML from TYPO3 backend (IconFactory), delivered as
      // a dedup key resolved via Icons - see the Icons registry above.
      const ctypeIconMarkup = Icons.resolve(contentElement.element.ctypeIcon);
      if (ctypeIconMarkup) {
        const iconWrapper = document.createElement('span');
        iconWrapper.className = 'frontend-edit__toolbar-icon';
        iconWrapper.innerHTML = ctypeIconMarkup;
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
        container.appendChild(this.createSeparator());
        const kebabBtn = this.createKebabButton(uid);
        Tooltip.attach(kebabBtn);
        container.appendChild(kebabBtn);
      }

      return container;
    },

    createSeparator() {
      const separator = document.createElement('div');
      separator.className = 'frontend-edit__toolbar-separator';
      separator.setAttribute('aria-hidden', 'true');
      return separator;
    },

    createEditButton(contentElement) {
      const btn = document.createElement('a');
      btn.className = 'frontend-edit__btn frontend-edit__btn--edit';
      btn.dataset.tooltip = 'Edit';
      btn.innerHTML = ICONS.edit;
      const ctypeLabel = contentElement.element.ctypeLabel || contentElement.element.CType || 'Content';
      btn.setAttribute('aria-label', 'Edit ' + ctypeLabel + ' ' + (contentElement.element.uid || ''));

      const editAction = contentElement.menu.children?.edit;
      if (editAction?.url && this.isValidUrl(editAction.url)) {
        btn.href = editAction.url;
        if (editAction.targetBlank) btn.target = '_blank';

        // Contextual editing: intercept click to open sidebar
        if (editAction.contextualUrl && UI.isValidUrl(editAction.contextualUrl)) {
          const contextualUrl = editAction.contextualUrl;
          const uid = contentElement.element?.uid;
          btn.addEventListener('click', (e) => {
            if (e.ctrlKey || e.metaKey || e.shiftKey) return; // Allow Ctrl+Click to open in new tab
            if (UI.openContextualEdit(contextualUrl, editAction.url, uid, editAction.targetBlank)) {
              e.preventDefault();
            }
          });
        }
      } else if (contentElement.menu.url && this.isValidUrl(contentElement.menu.url)) {
        btn.href = contentElement.menu.url;
        if (contentElement.menu.targetBlank) btn.target = '_blank';

        // Contextual editing for simple edit button (no context menu)
        if (contentElement.menu.contextualUrl && UI.isValidUrl(contentElement.menu.contextualUrl)) {
          const contextualUrl = contentElement.menu.contextualUrl;
          const uid = contentElement.element?.uid;
          btn.addEventListener('click', (e) => {
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;
            if (UI.openContextualEdit(contextualUrl, contentElement.menu.url, uid, contentElement.menu.targetBlank)) {
              e.preventDefault();
            }
          });
        }
      }

      return btn;
    },

    /**
     * Build the hover "insert" buttons (before/after) for a content element,
     * to be placed in the overlay layer. Returns [] when the feature is off or
     * the backend supplied no URLs.
     *
     * @returns {HTMLAnchorElement[]}
     */
    createInsertButtons(contentElement) {
      if (window.FRONTEND_EDIT_SHOW_INSERT_BUTTONS === false) return [];
      const data = contentElement.element || {};
      const labels = window.FRONTEND_EDIT_COLUMN_LABELS || {};
      const tooltips = {
        before: labels.insertBefore || 'Create new content before',
        after: labels.insertAfter || 'Create new content after',
      };

      const make = (url, position) => {
        const link = document.createElement('a');
        link.href = url;
        link.className = `frontend-edit__insert-btn frontend-edit__insert-btn--${position}`;
        // pointer-events are gated by CSS (none when the overlay is inactive,
        // auto when active) so hidden buttons of other elements aren't hoverable.
        link.setAttribute('aria-label', tooltips[position]);
        link.dataset.tooltip = tooltips[position];
        link.innerHTML = ICONS.add;
        Tooltip.attach(link);
        return link;
      };

      const buttons = [];
      if (data.newBeforeUrl && this.isValidUrl(data.newBeforeUrl)) buttons.push(make(data.newBeforeUrl, 'before'));
      if (data.newAfterUrl && this.isValidUrl(data.newAfterUrl)) buttons.push(make(data.newAfterUrl, 'after'));
      return buttons;
    },

    createKebabButton(uid) {
      const btn = document.createElement('button');
      btn.className = 'frontend-edit__btn frontend-edit__btn--kebab';
      btn.dataset.tooltip = 'More actions';
      btn.type = 'button';
      btn.dataset.cid = uid;
      btn.innerHTML = ICONS.kebab;
      btn.setAttribute('aria-haspopup', 'menu');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'More actions for ' + uid);
      return btn;
    },

    createDropdown(uid, contentElement) {
      const dropdown = document.createElement('div');
      dropdown.className = 'frontend-edit__dropdown';
      dropdown.dataset.cid = uid;
      dropdown.setAttribute('role', 'menu');

      const skipActions = ['header'];

      for (const [name, action] of Object.entries(contentElement.menu.children)) {
        if (skipActions.includes(name)) continue;

        const el = document.createElement(action.type === 'link' ? 'a' : 'div');

        if (action.type === 'link') {
          if (action.url && this.isValidUrl(action.url)) {
            el.href = action.url;
          }
          if (action.targetBlank) el.target = '_blank';

          if (name === 'edit' && action.contextualUrl && UI.isValidUrl(action.contextualUrl)) {
            const contextualUrl = action.contextualUrl;
            const ceUid = contentElement.element?.uid;
            el.addEventListener('click', (e) => {
              if (e.ctrlKey || e.metaKey || e.shiftKey) return;
              if (UI.openContextualEdit(contextualUrl, action.url, ceUid, action.targetBlank)) {
                e.preventDefault();
                Dropdown.closeAll();
              }
            });
          }
        }

        if (action.type === 'divider') {
          el.className = 'frontend-edit__divider';
          el.setAttribute('role', 'separator');
        } else if (action.type === 'info') {
          el.className = 'frontend-edit__info';
          el.setAttribute('role', 'presentation');
        } else {
          el.setAttribute('role', 'menuitem');
          el.setAttribute('tabindex', '-1');
        }

        const safeName = this.escapeClassName(name);
        if (safeName) {
          el.classList.add(safeName);
        }

        // Store record title on delete link for the confirmation dialog
        if (name === 'delete' && action.type === 'link') {
          const recordTitle = (contentElement.element?.header || '').replace(/<[^>]*>/g, '');
          el.dataset.recordTitle = recordTitle;
          el.dataset.recordTable = 'tt_content';
          el.dataset.recordUid = contentElement.element?.uid || uid;
        }

        const actionIconMarkup = Icons.resolve(action.icon);
        if (actionIconMarkup) {
          const iconWrapper = document.createElement('span');
          iconWrapper.innerHTML = actionIconMarkup;
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

      dropdown.addEventListener('keydown', (e) => {
        const items = Array.from(dropdown.querySelectorAll('[role="menuitem"]'));
        const idx = items.indexOf(document.activeElement);

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          const next = idx < items.length - 1 ? idx + 1 : 0;
          items[next].focus();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          const prev = idx > 0 ? idx - 1 : items.length - 1;
          items[prev].focus();
        } else if (e.key === 'Home') {
          e.preventDefault();
          items[0]?.focus();
        } else if (e.key === 'End') {
          e.preventDefault();
          items[items.length - 1]?.focus();
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (document.activeElement && document.activeElement.click) {
            document.activeElement.click();
          }
        } else if (e.key === 'Escape') {
          Dropdown.closeAll();
          const kebab = document.querySelector('.frontend-edit__btn--kebab[data-cid="' + uid + '"]');
          if (kebab) kebab.focus();
        }
      });

      // Roving focus (tabindex="-1" on every item) keeps Tab out of the menu
      // by design (APG menu-button pattern) - but the menu must then close
      // itself once focus actually leaves it, or it stays open with no
      // visible focus inside.
      dropdown.addEventListener('focusout', (e) => {
        // If focus moves to a kebab trigger (the user clicking it to toggle the
        // menu shut), leave the close decision to that button's click handler -
        // closing here first would make the click read a closed menu and reopen it.
        if (e.relatedTarget && e.relatedTarget.closest('.frontend-edit__btn--kebab')) {
          return;
        }
        if (!dropdown.contains(e.relatedTarget)) {
          Dropdown.closeAll();
        }
      });

      return dropdown;
    },

    /**
     * Opens a contextual edit URL, either in the sidebar (if available) or via direct navigation.
     */
    openContextualEdit(contextualUrl, fallbackUrl, uid, targetBlank) {
      if (window.FRONTEND_EDIT_CONTEXTUAL_EDITING && contextualUrl && window.ContextualEdit && window.ContextualEdit.sidebar) {
        window.ContextualEdit.open(contextualUrl, uid, targetBlank);
        return true;
      }
      return false;
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
   * Public API - the stable surface exposed on window.XimaFrontendEdit for
   * third-party extensions (see Documentation/DeveloperCorner/JavaScriptApi.rst).
   * Internal refactors of this file must not change these method signatures
   * or the `xfe:*` event detail shapes.
   */
  const PublicApi = {
    /**
     * Resolves target element + payload for a uid, so consumers never re-do
     * DOM resolution. Returns null if the uid is unknown (not rendered, or
     * frontend editing is disabled).
     */
    getElementInfo(uid) {
      const entry = Registry.get(uid);
      if (!entry) return null;
      return { uid: entry.uid, element: entry.element, payload: entry.payload };
    },

    /**
     * Shows a toast notification, reusing the internal Notification manager.
     */
    notify(message) {
      Notification.show(message || {});
    },

    /**
     * Adds a button to a content element's hover toolbar actions.
     * buttonSpec: { html, label, href, onClick }
     * Returns the created button, or null if the uid is unknown.
     */
    registerToolbarItem(uid, buttonSpec) {
      const entry = Registry.get(uid);
      const actions = entry?.toolbar?.querySelector('.frontend-edit__toolbar-actions');
      if (!actions) return null;

      const spec = buttonSpec || {};
      const btn = document.createElement(spec.href ? 'a' : 'button');
      btn.className = 'frontend-edit__btn frontend-edit__btn--custom';
      if (!spec.href) btn.type = 'button';
      if (spec.html) btn.innerHTML = spec.html;
      if (spec.label) {
        btn.setAttribute('aria-label', spec.label);
        btn.dataset.tooltip = spec.label;
        Tooltip.attach(btn);
      }
      if (spec.href && UI.isValidUrl(spec.href)) btn.href = spec.href;
      if (typeof spec.onClick === 'function') btn.addEventListener('click', spec.onClick);

      actions.appendChild(btn);
      return btn;
    },

    /**
     * Renders a persistent, hover-independent indicator on a content element's
     * overlay. spec: { html | element, position, id, onClick }:
     * - position: one of 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right'
     *   (default 'top-right'). Multiple badges in the same corner stack in a
     *   row, in registration order - each corner is its own slot, created on
     *   first use (see getBadgeSlot below).
     * - id: when given, a later registerBadge() call with the same uid+id
     *   replaces this badge in place (same slot position) instead of adding a
     *   duplicate - needed because xfe:element-rendered can fire more than
     *   once for the same element (e.g. re-fetched data).
     * Returns the created badge, or null if the uid is unknown.
     */
    registerBadge(uid, spec) {
      const entry = Registry.get(uid);
      if (!entry) return null;

      const badgeSpec = spec || {};
      const slot = getBadgeSlot(entry.overlay, badgeSpec.position || 'top-right');

      const badge = document.createElement('span');
      badge.className = 'frontend-edit__badge';
      if (badgeSpec.id) badge.dataset.badgeId = String(badgeSpec.id);
      if (badgeSpec.element instanceof Element) {
        badge.appendChild(badgeSpec.element);
      } else if (badgeSpec.html) {
        badge.innerHTML = badgeSpec.html;
      }
      if (typeof badgeSpec.onClick === 'function') {
        badge.style.pointerEvents = 'auto';
        badge.style.cursor = 'pointer';
        badge.addEventListener('click', badgeSpec.onClick);
      }

      // A fresh node (rather than mutating an existing one) means no stale
      // event listeners survive a replace.
      const existing = badgeSpec.id
        ? Array.from(slot.children).find(child => child.dataset.badgeId === String(badgeSpec.id))
        : null;
      if (existing) {
        existing.replaceWith(badge);
      } else {
        slot.appendChild(badge);
      }
      return badge;
    },

    /**
     * Sets the badge display-mode hook: 'subtle' (default) or 'prominent',
     * exposed as document.documentElement's `data-xfe-badge-mode` attribute.
     * The extension ships no badge content itself (see registerBadge), so it
     * has no opinion on what "subtle" vs "prominent" should look like -
     * consumer CSS reacts to this attribute, e.g.
     * `[data-xfe-badge-mode="subtle"] .my-badge-label { display: none }`.
     */
    setBadgeMode(mode) {
      document.documentElement.setAttribute('data-xfe-badge-mode', 'prominent' === mode ? 'prominent' : 'subtle');
    },

    /**
     * Opens a backend URL in the version-appropriate container: the contextual
     * sidebar (v14.2+, when enabled and available), the v13 iframe modal (via
     * the export iframe_edit.js attaches to this namespace), or a new tab as
     * a fallback. options:
     * - target: 'tab' forces a new tab regardless of version
     * - title, width: container chrome (width accepts a CSS length, e.g. '600px')
     * - onClose({ reason }): called when the container closes
     * - reloadOnClose: reload the parent page on close (default true)
     * - linkPolicy: a rule or array of rules `{ match: string|RegExp, action }`,
     *   action one of 'stay' | 'close' | 'ignore' | 'external', evaluated
     *   against link clicks inside the embedded document. Only 'stay' (the
     *   default when no rule matches) is honored for the built-in save/close
     *   buttons of an actual edit form - those keep working unchanged.
     */
    openBackendView(url, options) {
      if (!UI.isValidUrl(url)) return false;
      const opts = options || {};

      if (opts.target === 'tab') {
        window.open(url, '_blank');
        return true;
      }

      // Apply the same returnUrl flash-deferral marker record_edit forms get
      // (see backend_stubs.js/ensureReturnUrl) to every view opened through
      // this primitive, not only edit forms.
      const finalUrl = typeof window.XimaFrontendEdit?.ensureReturnUrl === 'function'
        ? window.XimaFrontendEdit.ensureReturnUrl(url)
        : url;
      const containerOptions = {
        title: opts.title,
        width: opts.width,
        onClose: opts.onClose,
        linkPolicy: opts.linkPolicy,
        reloadOnClose: opts.reloadOnClose,
      };

      // FRONTEND_EDIT_SIDEBAR_EDIT (not the broader "contextual editing
      // enabled" setting) is the correct gate: it is only true when the
      // contextual sidebar route is actually available (v14.2+). On v13 it
      // stays false even with contextual editing enabled, so the iframe
      // modal below handles it instead - see
      // ResourceRendererService::addSettingsConfig().
      if (window.FRONTEND_EDIT_SIDEBAR_EDIT === true && window.ContextualEdit && window.ContextualEdit.sidebar) {
        window.ContextualEdit.open(finalUrl, null, false, containerOptions);
        return true;
      }
      if (typeof window.XimaFrontendEdit?.openModal === 'function') {
        window.XimaFrontendEdit.openModal(finalUrl, containerOptions);
        return true;
      }
      window.open(url, '_blank');
      return true;
    }
  };

  // Merge onto window.XimaFrontendEdit rather than assigning: backend_stubs.js
  // (loaded first when the v13 iframe modal is enabled) already sets internal
  // helpers (IFRAME_ID, ensureReturnUrl, openWizardOverlay) on this namespace -
  // those are not part of the public API but must survive.
  window.XimaFrontendEdit = Object.assign(window.XimaFrontendEdit || {}, PublicApi);

  /**
   * Data Service
   */
  const DataService = {
    /**
     * Walks up from a .frontend-edit__data element to its owning content
     * element - either the id="c{uid}" anchor or a data-frontend-edit="tt_content:{uid}"
     * element - so <xfe:data> additional-data entries also resolve on content
     * elements that only expose the data attribute.
     */
    getClosestContentElement(element) {
      if (!element) return null;
      const isContentElement = (el) =>
        /^c\d+$/.test(Dom.id(el)) || /^tt_content:\d+$/.test(el.getAttribute('data-frontend-edit') || '');
      while (element && !isContentElement(element)) {
        element = element.parentElement;
      }
      return element;
    },

    collectDataItems() {
      const dataItems = {};
      const allUids = new Set();

      // Scan DOM for all content elements by id="c{uid}" pattern
      // This enables editing content from other pages (onepager scenarios).
      // Narrow the candidate set to ids starting with "c" (the regex below still
      // validates the exact "c{digits}" shape) instead of scanning every [id].
      // The attribute selector reads the real attribute, so it stays safe against
      // the id clobbering that Dom.id() guards against.
      document.querySelectorAll('[id^="c"]').forEach(element => {
        const match = Dom.id(element).match(/^c(\d+)$/);
        if (match) {
          const uid = parseInt(match[1], 10);
          if (uid > 0) {
            allUids.add(uid);
          }
        }
      });

      Logger.log(`Found ${allUids.size} content elements in DOM with id="c{uid}" pattern`);

      // Second matching channel: data-frontend-edit="{table}:{uid}" - for
      // templates that cannot carry the id="c{uid}" anchor (DCE, custom Fluid
      // templates; see the <xfe:editable> ViewHelper). tt_content matches join
      // the same _uids list as the anchor pattern (Renderer.render() skips
      // anchor-sibling resolution for them - they are direct targets); any
      // other table is a foreign record (see issue #216) collected separately,
      // since the backend resolves and renders those through a different,
      // deliberately thin edit+info+history-only path (Renderer.renderRecords()).
      const beforeDataAttributeScan = allUids.size;
      const recordRefs = new Set();
      document.querySelectorAll('[data-frontend-edit]').forEach(element => {
        const match = (element.getAttribute('data-frontend-edit') || '').match(/^([a-z][a-z0-9_]*):(\d+)$/);
        if (!match) return;

        const [, table, uidString] = match;
        const uid = parseInt(uidString, 10);
        if (uid <= 0) return;

        if ('tt_content' === table) {
          allUids.add(uid);
        } else {
          recordRefs.add(`${table}:${uid}`);
        }
      });

      if (allUids.size > beforeDataAttributeScan) {
        Logger.log(`Found ${allUids.size - beforeDataAttributeScan} additional content element(s) via data-frontend-edit attribute`);
      }
      if (recordRefs.size > 0) {
        dataItems._records = Array.from(recordRefs).sort();
        Logger.log(`Found ${recordRefs.size} foreign record(s) via data-frontend-edit attribute`, { records: dataItems._records });
      }

      // Collect additional data from .frontend-edit__data elements
      const dataElements = document.querySelectorAll('.frontend-edit__data');

      Logger.log(`Found ${dataElements.length} custom additional data elements on page`);

      dataElements.forEach((element, index) => {
        const closestElement = this.getClosestContentElement(element);
        if (!closestElement) return;

        // The owning element was found via either channel (see
        // getClosestContentElement) - resolve the uid from whichever matched.
        const idMatch = Dom.id(closestElement).match(/^c(\d+)$/);
        const dataMatch = idMatch ? null : (closestElement.getAttribute('data-frontend-edit') || '').match(/^tt_content:(\d+)$/);
        const uid = parseInt((idMatch || dataMatch)?.[1] ?? '', 10);
        if (!(uid > 0)) return;

        if (!dataItems[uid]) dataItems[uid] = [];

        const parsedData = JSON.parse(element.value);
        dataItems[uid].push(parsedData);
        allUids.add(uid);

        Logger.log(`Additional data element ${index + 1}: Found content element c${uid}`, { parsedData });
      });

      // Add UIDs array for backend to fetch content elements
      dataItems._uids = Array.from(allUids).sort((a, b) => a - b);

      // Scan for empty column markers (placed by integrator in Fluid templates)
      // Collect per-container colPos info so the backend knows which columns to check
      const containerMarkers = {};
      const pageColPositions = [];
      document.querySelectorAll('[data-xfe-colpos]').forEach(marker => {
        const colPos = parseInt(marker.dataset.xfeColpos, 10);
        if (!Number.isFinite(colPos)) return;

        const containerUid = marker.dataset.xfeContainer;
        if (containerUid) {
          const uid = parseInt(containerUid, 10);
          if (uid > 0) {
            if (!containerMarkers[uid]) containerMarkers[uid] = [];
            containerMarkers[uid].push(colPos);
          }
        } else {
          pageColPositions.push(colPos);
        }
      });

      if (Object.keys(containerMarkers).length > 0) {
        dataItems._containerMarkers = containerMarkers;
        Logger.log(`Found container markers`, containerMarkers);
      }
      if (pageColPositions.length > 0) {
        dataItems._pageColPositions = pageColPositions;
        Logger.log(`Found page column markers`, pageColPositions);
      }

      Logger.log(`Collected ${allUids.size} unique content element UIDs for backend request`, {
        uids: dataItems._uids
      });

      return dataItems;
    },

    async fetchContentElements(dataItems) {
      const config = document.getElementById('frontend-edit-toolbar-config');
      if (!config) {
        Logger.log('Frontend edit configuration element not found', null, 'warn');
        return {};
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

      try {
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

        // Handle wrapped response { contentElements, columnTargets }
        if (data.contentElements !== undefined) {
          Logger.log(`Backend response received with ${Object.keys(data.contentElements).length} content element(s) and ${(data.columnTargets || []).length} column target(s)`);
          return data;
        }

        // Fallback for legacy response (plain content elements object)
        Logger.log(`Backend response received with ${Object.keys(data).length} content element(s)`);
        return { contentElements: data, columnTargets: [] };
      } catch (error) {
        Notification.show({ title: 'Frontend Edit', message: 'Failed to load edit information', severity: 'error' });
        Logger.log('Failed to fetch content elements', { error: error.message }, 'error');
        return {};
      }
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
        if (!contentElement || !contentElement.menu || !contentElement.element) {
          Logger.log(`Skipping content element c${uid}: missing menu or element data`, null, 'warn');
          failed++;
          continue;
        }

        // id="c{uid}" anchor first, falling back to
        // data-frontend-edit="tt_content:{uid}" (see ElementResolver.findAnchor).
        let resolved = ElementResolver.findAnchor(uid);

        // Handle translation mapping. In connected/overlay mode the DOM anchor
        // carries the default-language uid (l18n_parent), so the response uid
        // (the translation uid) has no anchor of its own. Fall back to the L0
        // anchor even when the primary lookup missed. Prefer l18n_parent (the
        // canonical connected-mode pointer) over l10n_source (chained translations).
        // Coerce to number: DB values may arrive as strings, and "0" is truthy in JS.
        // The empty-anchor check only applies to the id="c{uid}" pattern - a
        // data-frontend-edit direct target is never a placeholder anchor.
        const anchorUid = Number(contentElement.element.l18n_parent) || Number(contentElement.element.l10n_source);
        const needsTranslationFallback = !resolved || (!resolved.isDirectTarget && resolved.element.tagName.toLowerCase() === 'a');
        if (anchorUid > 0 && needsTranslationFallback) {
          const fallbackResolved = ElementResolver.findAnchor(anchorUid);
          if (fallbackResolved) {
            Logger.log(`Translation mapping: c${uid} → c${anchorUid}`);
            uid = anchorUid;
            resolved = fallbackResolved;
          }
        }

        if (!resolved) {
          failed++;
          Logger.log(`DOM assignment failed: Element c${uid} not found`, null, 'warn');
          continue;
        }

        // Resolve actual content element (handles the anchor pattern; a
        // data-frontend-edit match is already the direct target).
        const targetElement = resolved.isDirectTarget ? resolved.element : ElementResolver.resolveContentElement(resolved.element);

        successful++;
        this.setupContentElement(targetElement, uid, contentElement, showContextMenu, enableOutline);

        Logger.log(`DOM assignment successful: c${uid}`, {
          CType: contentElement.element.CType,
          ctypeLabel: contentElement.element.ctypeLabel,
          showContextMenu,
          enableOutline,
          usedSibling: targetElement !== resolved.element
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
      const { overlay, toolbar } = OverlayManager.createOverlay(uid, targetElement, contentElement, effectiveShowContextMenu, enableOutline);

      // Create dropdown if needed
      let dropdown = null;
      if (effectiveShowContextMenu) {
        dropdown = UI.createDropdown(uid, contentElement);
        document.body.appendChild(dropdown);
        this.setupKebabEvents(toolbar, dropdown);
      }
      // Hover highlighting is handled centrally by OverlayManager's pointer
      // tracking (no per-element mouseenter/leave needed).

      // Register under both the DOM anchor uid and the record's own uid (they
      // differ when Renderer.render() applied translation mapping) so the
      // public API can resolve elements by either.
      const domUid = Number(uid);
      const recordUid = Number(contentElement.element.uid) || domUid;
      const entry = { uid: recordUid, element: targetElement, payload: contentElement, overlay, toolbar, dropdown };
      Registry.set(domUid, entry);
      if (recordUid !== domUid) Registry.set(recordUid, entry);

      Events.dispatch('xfe:element-rendered', { uid: recordUid, element: targetElement, payload: contentElement, overlay, toolbar, dropdown });
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
          kebabBtn.setAttribute('aria-expanded', 'true');
          await Dropdown.position(kebabBtn, dropdown);
          const firstItem = dropdown.querySelector('[role="menuitem"]');
          if (firstItem) firstItem.focus();
          Events.dispatch('xfe:dropdown-open', { uid: Number(toolbar.dataset.cid) });
        }
      });
    },

    /**
     * Renders the deliberately thin edit+info+history menu for foreign
     * records (see issue #216), matched exclusively via
     * data-frontend-edit="{table}:{uid}" - never via the id="c{uid}" anchor
     * pattern, so there is no anchor-sibling/translation-uid resolution to
     * do here (the backend already resolved the correct-language record;
     * the response key is always the same "{table}:{uid}" string the
     * template rendered, so a direct attribute lookup is enough).
     *
     * Deliberately NOT routed through setupContentElement()/Registry: the
     * Registry keys elements by Number(uid), which would collapse every
     * "table:uid" string to the same NaN key. Reuses OverlayManager/UI
     * directly instead - both already operate on the DOM element and a
     * plain identifier, with no assumption that the identifier is numeric.
     */
    renderRecords(records) {
      if (!records || 0 === Object.keys(records).length) return;

      // Always the full kebab menu, regardless of FRONTEND_EDIT_SHOW_CONTEXT_MENU:
      // unlike tt_content, a foreign record has no "simple edit href" shortcut -
      // RecordMenuGenerator (PHP) always returns the full edit+info+history tree.
      const enableOutline = window.FRONTEND_EDIT_ENABLE_OUTLINE !== false;

      Object.entries(records).forEach(([key, record]) => {
        if (!record || !record.menu || !record.element) return;

        const targetElement = document.querySelector(`[data-frontend-edit="${key}"]`);
        if (!targetElement) {
          Logger.log(`Foreign record DOM assignment failed: no element for ${key}`, null, 'warn');
          return;
        }

        const { toolbar } = OverlayManager.createOverlay(key, targetElement, record, true, enableOutline);
        const dropdown = UI.createDropdown(key, record);
        document.body.appendChild(dropdown);
        this.setupKebabEvents(toolbar, dropdown);

        Logger.log(`Foreign record DOM assignment successful: ${key}`);
      });
    }
  };

  /**
   * Column Target Renderer - Matches AJAX columnTargets data against
   * [data-xfe-colpos] markers placed by the integrator in Fluid templates
   * and injects "+" buttons client-side. Empty columns show the full label;
   * filled columns get a compact "+" at the column end.
   */
  const ColumnTargetRenderer = {
    render(columnTargets) {
      if (!columnTargets || columnTargets.length === 0) return;

      Logger.log(`Processing ${columnTargets.length} column target(s)`);
      let rendered = 0;

      const columnLabels = window.FRONTEND_EDIT_COLUMN_LABELS || {};
      const buttonLabel = columnLabels.createContent || 'Create new content';

      columnTargets.forEach(col => {
        let selector = `[data-xfe-colpos="${col.colPos}"]`;
        if (col.containerUid) {
          selector += `[data-xfe-container="${col.containerUid}"]`;
        }

        const marker = document.querySelector(selector);
        if (!marker) {
          Logger.log(`No marker found for colPos ${col.colPos}` + (col.containerUid ? ` container ${col.containerUid}` : ''), null, 'warn');
          return;
        }

        if (!col.newContentUrl || !UI.isValidUrl(col.newContentUrl)) {
          Logger.log(`Invalid URL for colPos ${col.colPos}`, null, 'warn');
          return;
        }

        const tooltipText = col.name
          ? (columnLabels.createContentIn || 'Create new content in "%s" column').replace('%s', col.name)
          : buttonLabel;

        const link = document.createElement('a');
        link.href = col.newContentUrl;
        link.className = 'frontend-edit__column-btn'
          + (col.isEmpty ? ' frontend-edit__column-btn--empty' : ' frontend-edit__column-btn--append');
        link.setAttribute('aria-label', tooltipText);
        link.dataset.tooltip = tooltipText;

        const icon = document.createElement('span');
        icon.className = 'frontend-edit__column-btn-icon';
        icon.innerHTML = ICONS.add;
        link.appendChild(icon);

        if (col.isEmpty) {
          const label = document.createElement('span');
          label.className = 'frontend-edit__column-btn-label';
          label.textContent = buttonLabel;
          link.appendChild(label);
        }

        marker.innerHTML = '';
        marker.appendChild(link);
        marker.hidden = false;
        Tooltip.attach(link);

        rendered++;
        Logger.log(`Column target button rendered for colPos ${col.colPos}`, { name: col.name, isEmpty: col.isEmpty, url: col.newContentUrl });
      });

      Logger.log(`Column target rendering complete: ${rendered}/${columnTargets.length} buttons placed`);
    }
  };

  /**
   * Drag & Drop Reordering (MVP)
   *
   * Reorders content elements within and between page-level columns and
   * EXT:container columns by delegating to the core DataHandler via the move
   * endpoint. Scope is limited to the default language and columns the
   * integrator marked with [data-xfe-colpos]; container columns additionally
   * carry [data-xfe-container] with the container's uid. Translated elements
   * keep the classic backend move dialog.
   */
  const DragReorder = {
    moveUrl: null,
    labels: {},
    columns: [],
    dragging: null,
    draggingBlock: null,
    dragGhost: null,
    dropTarget: null,
    indicator: null,

    init() {
      if (!window.FRONTEND_EDIT_ENABLE_DND || !window.FRONTEND_EDIT_MOVE_URL) return;

      const config = document.getElementById('frontend-edit-toolbar-config');
      if (config && parseInt(config.dataset.language || '0', 10) > 0) {
        Logger.log('Drag & drop disabled for translated pages');
        return;
      }

      this.moveUrl = window.FRONTEND_EDIT_MOVE_URL;
      this.labels = window.FRONTEND_EDIT_DND_LABELS || {};

      this.collectColumns();
      if (!this.columns.length) {
        Logger.log('Drag & drop: no page column markers ([data-xfe-colpos]) found');
        return;
      }

      this.addHandles();
      document.addEventListener('dragover', (e) => this.onDragOver(e));
      document.addEventListener('drop', (e) => this.onDrop(e));
      Logger.log(`Drag & drop initialized for ${this.columns.length} column(s)`);
    },

    collectColumns() {
      // Container columns carry data-xfe-container with the container's uid; page
      // columns have no such attribute. Both are drop targets, but only the former
      // may set tx_container_parent.
      document.querySelectorAll('[data-xfe-colpos]').forEach(marker => {
        const colPos = parseInt(marker.dataset.xfeColpos, 10);
        const container = marker.parentElement;
        if (!Number.isFinite(colPos) || !container) return;
        const rawContainerUid = marker.dataset.xfeContainer;
        const containerUid = rawContainerUid === undefined
          ? null
          : parseInt(rawContainerUid, 10);
        if (containerUid !== null && !Number.isFinite(containerUid)) return;
        if (this.columns.some(c => c.container === container && c.colPos === colPos)) return;
        this.columns.push({ colPos, container, containerUid });
      });
    },

    /**
     * Top-level content elements of a column, ignoring nested/container children.
     * Resolves the anchor pattern (<a id="c1"></a><div>…</div>) to the visual block.
     */
    getColumnElements(container) {
      const items = [];
      const containers = this.columns.map(c => c.container);
      container.querySelectorAll('[id^="c"]').forEach(el => {
        const match = Dom.id(el).match(/^c(\d+)$/);
        if (!match) return;
        const uid = parseInt(match[1], 10);
        if (uid <= 0) return;

        // The element only belongs to this column if `container` is the closest
        // registered column container above it. Skip elements nested inside
        // another content element, and elements that actually live in a nested
        // sub-column (e.g. colPos 0/2 inside the wider colPos 8/9 wrapper) — a
        // deep querySelectorAll would otherwise leak them into the ancestor
        // column and push the drop indicator too far down.
        let parent = el.parentElement;
        let ownedByOther = false;
        while (parent && parent !== container) {
          if (parent.id && /^c\d+$/.test(parent.id)) { ownedByOther = true; break; }
          if (containers.includes(parent)) { ownedByOther = true; break; }
          parent = parent.parentElement;
        }
        if (ownedByOther || parent !== container) return;

        let block = el;
        if (el.getBoundingClientRect().height === 0 && el.nextElementSibling) {
          block = el.nextElementSibling;
        }
        items.push({ uid, block });
      });
      return items;
    },

    findColumnForUid(uid) {
      return this.columns.find(col =>
        this.getColumnElements(col.container).some(item => item.uid === uid)
      ) || null;
    },

    addHandles() {
      document.querySelectorAll('.frontend-edit__toolbar[data-cid]').forEach(toolbar => {
        if (toolbar.querySelector('.frontend-edit__btn--drag')) return;
        const uid = parseInt(toolbar.dataset.cid, 10);
        if (!Number.isFinite(uid) || !this.findColumnForUid(uid)) return;

        // Place the handle at the end of the label pill (left side), separated
        // by a divider — not in the actions group — so it sits next to the
        // element-type display rather than crowding the edit/kebab buttons.
        const labelGroup = toolbar.querySelector('.frontend-edit__toolbar-label');
        if (!labelGroup) return;

        const handle = document.createElement('button');
        handle.type = 'button';
        handle.className = 'frontend-edit__btn frontend-edit__btn--drag';
        handle.draggable = true;
        handle.innerHTML = ICONS.drag;
        const label = this.labels.handle || 'Drag to reorder';
        handle.dataset.tooltip = label;
        // Native drag & drop is pointer-only: this handle cannot be operated with
        // a keyboard. Keeping it in the tab order would offer a focusable control
        // that does nothing, so it is taken out of the tab order and hidden from
        // assistive technology. The "move" button in the edit menu remains the
        // keyboard-accessible way to reposition an element.
        handle.tabIndex = -1;
        handle.setAttribute('aria-hidden', 'true');
        handle.addEventListener('dragstart', (e) => this.onDragStart(e, uid));
        handle.addEventListener('dragend', () => this.onDragEnd());
        Tooltip.attach(handle);
        labelGroup.appendChild(UI.createSeparator());
        labelGroup.appendChild(handle);
      });
    },

    resolveBlock(uid) {
      const col = this.findColumnForUid(uid);
      if (col) {
        const item = this.getColumnElements(col.container).find(i => i.uid === uid);
        if (item) return item.block;
      }
      const el = document.getElementById('c' + uid);
      if (!el) return null;
      return (el.getBoundingClientRect().height === 0 && el.nextElementSibling) ? el.nextElementSibling : el;
    },

    elementLabel(block) {
      if (!block) return '';
      const heading = block.querySelector('h1, h2, h3, h4, h5, h6');
      return (heading ? heading.textContent : '').trim().replace(/\s+/g, ' ').slice(0, 80);
    },

    /**
     * The nearest ancestor's opaque background, so the drag snapshot sits on the
     * same colour as the real content. Falls back to white when everything up to
     * the root is transparent.
     */
    resolveBackground(el) {
      let node = el;
      while (node instanceof Element) {
        const bg = getComputedStyle(node).backgroundColor;
        if (bg && bg !== 'transparent' && !/^rgba\(\s*0\s*,\s*0\s*,\s*0\s*,\s*0\s*\)$/.test(bg)) {
          return bg;
        }
        node = node.parentElement;
      }
      return '#ffffff';
    },

    onDragStart(e, uid) {
      const sourceCol = this.findColumnForUid(uid);
      this.dragging = { uid, sourceColPos: sourceCol ? sourceCol.colPos : null };
      this.draggingBlock = this.resolveBlock(uid);

      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(uid));

        // Drag a translucent snapshot of the whole element (feels more natural
        // than dragging the tiny handle button).
        if (this.draggingBlock) {
          try {
            const rect = this.draggingBlock.getBoundingClientRect();
            const ghost = this.draggingBlock.cloneNode(true);
            ghost.classList.add('frontend-edit__drag-ghost');
            // Back the snapshot with the page's own background, not the extension
            // theme — otherwise a dark editor scheme paints a dark card behind the
            // page's dark-on-light content, making it unreadable.
            const pageBg = this.resolveBackground(this.draggingBlock);
            ghost.style.cssText = `position:fixed;top:-10000px;left:0;margin:0;width:${rect.width}px;opacity:0.85;pointer-events:none;background:${pageBg};`;
            document.body.appendChild(ghost);
            // Anchor the ghost at the exact point the cursor grabbed, so it
            // tracks the pointer instead of drifting off to one side.
            const offsetX = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
            const offsetY = Math.max(0, Math.min(rect.height, e.clientY - rect.top));
            e.dataTransfer.setDragImage(ghost, offsetX, offsetY);
            this.dragGhost = ghost;
          } catch (_) { /* fall back to the default drag image */ }
        }
      }

      // Dim the source in place — applied next frame so it is not captured by
      // the drag-image snapshot above.
      if (this.draggingBlock) {
        const block = this.draggingBlock;
        requestAnimationFrame(() => block.classList.add('frontend-edit--drag-source'));
      }

      document.body.classList.add('frontend-edit--dragging');
      Dropdown.closeAll();
    },

    columnFromPoint(x, y) {
      let node = document.elementFromPoint(x, y);
      while (node) {
        const col = this.columns.find(c => c.container === node);
        if (col) return col;
        node = node.parentElement;
      }
      return null;
    },

    onDragOver(e) {
      if (!this.dragging) return;

      const col = this.columnFromPoint(e.clientX, e.clientY);
      if (!col) { this.clearIndicator(); this.dropTarget = null; return; }

      e.preventDefault();
      if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';

      const elements = this.getColumnElements(col.container).filter(item => item.uid !== this.dragging.uid);
      let afterUid = 0;
      let indicatorTop = null;
      for (const item of elements) {
        const rect = item.block.getBoundingClientRect();
        if (e.clientY > rect.top + rect.height / 2) {
          afterUid = item.uid;
          indicatorTop = rect.bottom;
        } else {
          if (indicatorTop === null) indicatorTop = rect.top;
          break;
        }
      }

      this.dropTarget = { colPos: col.colPos, afterUid, containerUid: col.containerUid };
      this.showIndicator(col.container, indicatorTop);
    },

    onDrop(e) {
      if (!this.dragging || !this.dropTarget) { this.onDragEnd(); return; }
      e.preventDefault();
      const { uid } = this.dragging;
      const { colPos, afterUid, containerUid } = this.dropTarget;
      const header = this.elementLabel(this.draggingBlock);
      // Distinguish a reorder within the same column from a move to another one.
      // Unknown source (null) is treated as a cross-column move.
      const sameColumn = null != this.dragging.sourceColPos && this.dragging.sourceColPos === colPos;
      this.onDragEnd();
      void this.persistMove(uid, colPos, afterUid, header, sameColumn, containerUid);
    },

    rememberNotification(title, message, severity) {
      try {
        sessionStorage.setItem('xfe-pending-notification', JSON.stringify({ title, message, severity }));
      } catch (_) { /* storage unavailable — the reload just shows no toast */ }
    },

    // Picks the header-specific detail label (with "%s" substituted) when a
    // header is known, otherwise the generic label — falling back to English
    // defaults if the label itself is missing.
    formatMoveMessage(detail, detailGeneric, fallback, fallbackGeneric, header) {
      return header
        ? (detail || fallback).replace('%s', header)
        : (detailGeneric || fallbackGeneric);
    },

    async persistMove(uid, targetColPos, targetUid, header, sameColumn, targetContainerUid) {
      const config = document.getElementById('frontend-edit-toolbar-config');
      const language = config ? parseInt(config.dataset.language || '0', 10) : 0;
      const L = this.labels;
      // "OK" is the TYPO3 severity that maps to the green success styling.
      const successMsg = this.formatMoveMessage(
        sameColumn ? L.successDetail : L.successDetailMoved,
        sameColumn ? L.successDetailGeneric : L.successDetailMovedGeneric,
        sameColumn ? '“%s” was reordered within the column.' : '“%s” was moved to another column.',
        sameColumn ? 'The content element was reordered within the column.' : 'The content element was moved to another column.',
        header
      );
      const errorMsg = this.formatMoveMessage(
        L.errorDetail, L.errorDetailGeneric,
        '“%s” could not be moved. Please try again.',
        'The content element could not be moved. Please try again.',
        header
      );
      const rejectedMsg = this.formatMoveMessage(
        L.rejectedDetail, L.rejectedDetailGeneric,
        '“%s” cannot be placed at this position. Use the move dialog in the backend instead.',
        'The content element cannot be placed at this position. Use the move dialog in the backend instead.',
        header
      );
      try {
        const response = await fetch(this.moveUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ uid, targetColPos, targetUid, language, targetContainerUid: targetContainerUid ?? null })
        });

        // 422 means the drop is refused for good: retrying changes nothing, so
        // say that instead of "please try again", and do not reload.
        if (response.status === 422) {
          Notification.show({
            title: L.rejected || 'Cannot be dropped here',
            message: rejectedMsg,
            severity: 'error'
          });
          return;
        }

        // 409 means the outcome is uncertain — reload so the editor sees reality.
        if (response.status === 409) {
          this.rememberNotification(L.error || 'Could not move the content element', errorMsg, 'error');
          window.location.reload();
          return;
        }

        if (!response.ok) throw new Error(`move failed (${response.status})`);

        this.rememberNotification(L.success || 'Content element moved', successMsg, 'ok');
        window.location.reload();
      } catch (error) {
        Logger.log('Drag & drop move failed', { error: error.message }, 'error');
        Notification.show({
          title: L.error || 'Could not move the content element',
          message: errorMsg,
          severity: 'error'
        });
      }
    },

    showIndicator(container, top) {
      if (!this.indicator) {
        this.indicator = document.createElement('div');
        this.indicator.className = 'frontend-edit__drop-indicator';
        document.body.appendChild(this.indicator);
      }
      const rect = container.getBoundingClientRect();
      this.indicator.style.left = `${rect.left}px`;
      this.indicator.style.width = `${rect.width}px`;
      this.indicator.style.top = `${null != top ? top : rect.top}px`;
      this.indicator.style.display = 'block';
    },

    clearIndicator() {
      if (this.indicator) this.indicator.style.display = 'none';
    },

    onDragEnd() {
      if (this.dragGhost) { this.dragGhost.remove(); this.dragGhost = null; }
      if (this.draggingBlock) { this.draggingBlock.classList.remove('frontend-edit--drag-source'); this.draggingBlock = null; }
      this.dragging = null;
      this.dropTarget = null;
      this.clearIndicator();
      document.body.classList.remove('frontend-edit--dragging');
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
        // Expose for contextual_edit.js (separate script, outside this IIFE)
        window.FrontendEditNotification = Notification;

        // Show queued notification from previous page (e.g. contextual sidebar save)
        try {
          const pending = sessionStorage.getItem('xfe-pending-notification');
          if (pending) {
            sessionStorage.removeItem('xfe-pending-notification');
            Notification.show(JSON.parse(pending));
          }
        } catch (_) { /* ignore */ }

        // Initialize contextual editing sidebar if loaded
        if (window.ContextualEdit) {
          window.ContextualEdit.init();
        }

        // Only initialize content element editing if not disabled
        if (!window.FRONTEND_EDIT_DISABLED) {
          OverlayManager.init();

          const dataItems = DataService.collectDataItems();
          const response = await DataService.fetchContentElements(dataItems);

          Icons.populate(response.icons);
          Renderer.render(response.contentElements || {});
          Renderer.renderRecords(response.records || {});
          ColumnTargetRenderer.render(response.columnTargets || []);
          Dropdown.setupGlobalHandler();
          DeleteHandler.init();
          DragReorder.init();
        }

        // Fired unconditionally (even when frontend editing is disabled, with an
        // empty element map) so consumers always get one reliable ready signal.
        Events.dispatch('xfe:ready', { elements: Registry.snapshot() });

        Logger.log(`Frontend Edit initialization completed in ${Math.round(performance.now() - startTime)}ms`);
      } catch (error) {
        Logger.log('Frontend Edit initialization failed', {
          error: error.message,
          stack: error.stack
        }, 'error');
        Notification.show({ title: 'Frontend Edit', message: 'Initialization error', severity: 'warning' });
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
