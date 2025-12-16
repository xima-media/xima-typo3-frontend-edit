document.addEventListener('DOMContentLoaded', function () {
  // Floating UI import (loaded via separate script tag)
  const { computePosition, flip, shift, offset, arrow } = window.FloatingUIDOM || {};

  // Shared tooltip element
  let tooltipEl = null;
  let tooltipArrow = null;

  /**
   * Creates or returns the shared tooltip element.
   */
  const getTooltip = () => {
    if (!tooltipEl) {
      tooltipEl = document.createElement('div');
      tooltipEl.className = 'frontend-edit__tooltip';
      tooltipArrow = document.createElement('div');
      tooltipArrow.className = 'frontend-edit__tooltip-arrow';
      tooltipEl.appendChild(tooltipArrow);
      document.body.appendChild(tooltipEl);
    }
    return { tooltip: tooltipEl, arrow: tooltipArrow };
  };

  /**
   * Shows tooltip for an element.
   */
  const showTooltip = async (btn) => {
    const text = btn.dataset.tooltip;
    if (!text || !computePosition) return;

    const { tooltip, arrow: arrowEl } = getTooltip();

    // Set text (insert before arrow)
    tooltip.childNodes.forEach(node => {
      if (node !== arrowEl) tooltip.removeChild(node);
    });
    const textNode = document.createTextNode(text);
    tooltip.insertBefore(textNode, arrowEl);

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

    Object.assign(tooltip.style, {
      left: `${x}px`,
      top: `${y}px`,
    });

    tooltip.setAttribute('data-placement', placement);

    // Position arrow
    if (middlewareData.arrow) {
      const { x: arrowX, y: arrowY } = middlewareData.arrow;
      Object.assign(arrowEl.style, {
        left: arrowX != null ? `${arrowX}px` : '',
        top: arrowY != null ? `${arrowY}px` : '',
      });
    }

    tooltip.classList.add('frontend-edit__tooltip--visible');
  };

  /**
   * Hides the tooltip.
   */
  const hideTooltip = () => {
    if (tooltipEl) {
      tooltipEl.classList.remove('frontend-edit__tooltip--visible');
    }
  };

  /**
   * Sets up tooltip events for a button.
   */
  const setupTooltip = (btn) => {
    btn.addEventListener('mouseenter', () => showTooltip(btn));
    btn.addEventListener('mouseleave', hideTooltip);
    btn.addEventListener('focus', () => showTooltip(btn));
    btn.addEventListener('blur', hideTooltip);
  };

  /**
   * Debug logging utility - only logs when debug mode is enabled
   */
  const debugLog = (message, data = null, level = 'log') => {
    if (window.FRONTEND_EDIT_DEBUG) {
      const prefix = '%c[xima-typo3-frontend-edit]%c';
      if (data !== null) {
        console[level](prefix, 'font-weight: bold;', 'font-weight: normal;', message, data);
      } else {
        console[level](prefix, 'font-weight: bold;', 'font-weight: normal;', message);
      }
    }
  };

  /**
   * Initialize theme based on settings
   */
  const initializeTheme = () => {
    const colorScheme = window.FRONTEND_EDIT_COLOR_SCHEME || 'auto';
    document.documentElement.setAttribute('data-xfe-theme', colorScheme);
    debugLog(`Theme initialized: ${colorScheme}`);
  };

  /**
   * Finds the closest parent element with an ID matching the pattern "c\d+".
   */
  const getClosestElementWithId = (element) => {
    while (element && !element.id.match(/c\d+/)) {
      element = element.parentElement;
    }
    return element;
  };

  /**
   * Collects data items from elements with the class "frontend-edit__data".
   */
  const collectDataItems = () => {
    const dataItems = {};
    const dataElements = document.querySelectorAll('.frontend-edit__data');

    debugLog(`Found ${dataElements.length} custom additional data elements on page`);

    dataElements.forEach((element, index) => {
      const data = element.value;
      const closestElement = getClosestElementWithId(element);

      if (closestElement) {
        const id = closestElement.id.replace('c', '');
        if (!dataItems[id]) {
          dataItems[id] = [];
        }
        const parsedData = JSON.parse(data);
        dataItems[id].push(parsedData);

        debugLog(`Additional data element ${index + 1}: Found content element c${id}`, {
          parsedData: parsedData
        });
      }
    });

    return dataItems;
  };

  /**
   * Sends a POST request to fetch content elements.
   */
  const fetchContentElements = async (dataItems) => {
    const url = new URL(window.location.href);
    url.searchParams.set('type', '1729341864');

    debugLog('Sending request to backend', { url: url.toString() });

    const response = await fetch(url.toString(), {
      cache: 'no-cache',
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dataItems),
    });

    if (!response.ok) {
      throw new Error('Failed to fetch content elements');
    }

    const jsonResponse = await response.json();
    debugLog(`Backend response received with ${Object.keys(jsonResponse).length} content element(s)`);

    return jsonResponse;
  };

  /**
   * Creates the toolbar element for a content element.
   */
  const createToolbar = (uid, contentElement, showContextMenu) => {
    const toolbar = document.createElement('div');
    toolbar.className = 'frontend-edit__toolbar';
    toolbar.dataset.cid = uid;

    // Left side: Element label
    const labelContainer = document.createElement('div');
    labelContainer.className = 'frontend-edit__toolbar-label';

    // Icon from header if available
    const headerInfo = contentElement.menu.children?.header;
    if (headerInfo?.icon) {
      const iconWrapper = document.createElement('span');
      iconWrapper.innerHTML = headerInfo.icon;
      labelContainer.appendChild(iconWrapper);
    }

    // Label text: CType Label [UID]
    const labelText = document.createElement('span');
    const ctypeLabel = contentElement.element.ctypeLabel || contentElement.element.CType || 'Content';
    labelText.innerHTML = `${ctypeLabel} <code>${uid}</code>`;
    labelContainer.appendChild(labelText);

    // Right side: Actions
    const actionsContainer = document.createElement('div');
    actionsContainer.className = 'frontend-edit__toolbar-actions';

    // Edit button (always visible)
    const editBtn = createEditButton(contentElement);
    setupTooltip(editBtn);
    actionsContainer.appendChild(editBtn);

    // Kebab menu button (only if showContextMenu is true and has menu children)
    if (showContextMenu && contentElement.menu.children && Object.keys(contentElement.menu.children).length > 0) {
      const kebabBtn = createKebabButton(uid);
      setupTooltip(kebabBtn);
      actionsContainer.appendChild(kebabBtn);
    }

    toolbar.appendChild(labelContainer);
    toolbar.appendChild(actionsContainer);

    return toolbar;
  };

  /**
   * Creates the edit button for the toolbar.
   */
  const createEditButton = (contentElement) => {
    const editBtn = document.createElement('a');
    editBtn.className = 'frontend-edit__btn frontend-edit__btn--edit';
    editBtn.dataset.tooltip = 'Edit';

    // Always use pencil icon for edit button
    editBtn.innerHTML = '<svg viewBox="0 0 32 32" fill="currentColor"><path d="M4.834,29.665L25.007,29.665C26.561,29.663 27.839,28.385 27.841,26.831L27.841,16.157C27.841,15.608 27.39,15.157 26.841,15.157C26.292,15.157 25.841,15.608 25.841,16.157L25.841,26.831C25.84,27.288 25.464,27.664 25.007,27.665L4.834,27.665C4.377,27.664 4.001,27.288 4,26.831L4,7.651C4.001,7.194 4.377,6.818 4.834,6.817L16,6.817C16.549,6.817 17,6.366 17,5.817C17,5.268 16.549,4.817 16,4.817L4.834,4.817C3.28,4.819 2.002,6.097 2,7.651L2,26.831C2.002,28.385 3.28,29.663 4.834,29.665Z" fill-rule="nonzero"/><path d="M8.582,19.343L7.912,22.691C7.894,22.781 7.885,22.873 7.885,22.965C7.885,23.726 8.51,24.352 9.271,24.352C9.363,24.352 9.454,24.343 9.544,24.325L12.895,23.655C13.539,23.527 14.131,23.211 14.595,22.747L28.845,8.494C29.473,7.825 29.823,6.941 29.823,6.024C29.823,4.044 28.195,2.416 26.215,2.416C25.298,2.416 24.414,2.766 23.745,3.394L9.49,17.645C9.025,18.108 8.709,18.699 8.582,19.343ZM10.543,19.734C10.594,19.478 10.72,19.244 10.904,19.059L25.157,4.806C25.458,4.509 25.864,4.343 26.286,4.343C27.168,4.343 27.894,5.069 27.894,5.951C27.894,6.373 27.728,6.779 27.431,7.08L13.178,21.332C12.993,21.517 12.758,21.643 12.502,21.694L10.054,22.184L10.543,19.734Z" fill-rule="nonzero"/></svg>';

    // Set href from edit action or menu URL
    const editAction = contentElement.menu.children?.edit;
    if (editAction?.url) {
      editBtn.href = editAction.url;
      if (editAction.targetBlank) editBtn.target = '_blank';
    } else if (contentElement.menu.url) {
      editBtn.href = contentElement.menu.url;
      if (contentElement.menu.targetBlank) editBtn.target = '_blank';
    }

    return editBtn;
  };

  /**
   * Creates the kebab (three dots) button for the toolbar.
   */
  const createKebabButton = (uid) => {
    const kebabBtn = document.createElement('button');
    kebabBtn.className = 'frontend-edit__btn frontend-edit__btn--kebab';
    kebabBtn.dataset.tooltip = 'More actions';
    kebabBtn.type = 'button';
    kebabBtn.dataset.cid = uid;
    kebabBtn.innerHTML = `<svg viewBox="0 0 16 16" fill="currentColor">
      <circle cx="8" cy="2.5" r="1.5"/>
      <circle cx="8" cy="8" r="1.5"/>
      <circle cx="8" cy="13.5" r="1.5"/>
    </svg>`;

    return kebabBtn;
  };

  /**
   * Creates the dropdown menu for the kebab button.
   */
  const createDropdownMenu = (uid, contentElement) => {
    const dropdown = document.createElement('div');
    dropdown.className = 'frontend-edit__dropdown';
    dropdown.dataset.cid = uid;

    for (let actionName in contentElement.menu.children) {
      // Skip edit (in toolbar), header (info already visible in toolbar label), and div_info divider
      if (actionName === 'edit' || actionName === 'header' || actionName === 'div_info') continue;

      const action = contentElement.menu.children[actionName];
      const actionElement = document.createElement(action.type === 'link' ? 'a' : 'div');

      if (action.type === 'link') {
        actionElement.href = action.url;
        if (action.targetBlank) actionElement.target = '_blank';
      }

      if (action.type === 'divider') {
        actionElement.className = 'frontend-edit__divider';
      }

      actionElement.classList.add(actionName);
      actionElement.innerHTML = `${action.icon ?? ''} <span>${action.label}</span>`;
      dropdown.appendChild(actionElement);
    }

    return dropdown;
  };

  /**
   * Positions the dropdown using Floating UI or fallback.
   */
  const positionDropdown = async (kebabBtn, dropdown) => {
    if (computePosition) {
      // Use Floating UI for positioning
      const { x, y } = await computePosition(kebabBtn, dropdown, {
        placement: 'bottom-end',
        middleware: [
          offset(4),
          flip({ fallbackPlacements: ['top-end', 'bottom-start', 'top-start'] }),
          shift({ padding: 8 })
        ]
      });

      Object.assign(dropdown.style, {
        left: `${x}px`,
        top: `${y}px`,
      });
    } else {
      // Fallback positioning
      const btnRect = kebabBtn.getBoundingClientRect();
      const scrollTop = document.documentElement.scrollTop;
      const scrollLeft = document.documentElement.scrollLeft;

      dropdown.style.top = `${btnRect.bottom + scrollTop + 4}px`;
      dropdown.style.left = `${btnRect.right + scrollLeft - dropdown.offsetWidth}px`;

      // Flip to top if not enough space at bottom
      const viewportHeight = window.innerHeight;
      if (btnRect.bottom + 200 > viewportHeight) {
        dropdown.style.top = `${btnRect.top + scrollTop - dropdown.offsetHeight - 4}px`;
      }
    }
  };

  /**
   * Sets up kebab menu click events.
   */
  const setupKebabEvents = (kebabBtn, dropdown) => {
    kebabBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();

      const isVisible = dropdown.style.display === 'block';

      // Close all other dropdowns
      document.querySelectorAll('.frontend-edit__dropdown').forEach(d => {
        d.style.display = 'none';
      });

      if (!isVisible) {
        dropdown.style.display = 'block';
        await positionDropdown(kebabBtn, dropdown);
      }
    });
  };

  /**
   * Sets up global click handler to close dropdowns.
   */
  const setupGlobalClickHandler = () => {
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.frontend-edit__btn--kebab') &&
          !e.target.closest('.frontend-edit__dropdown')) {
        document.querySelectorAll('.frontend-edit__dropdown').forEach(d => {
          d.style.display = 'none';
        });
      }
    });
  };

  /**
   * Renders content elements with the new toolbar design.
   */
  const renderContentElements = (jsonResponse) => {
    debugLog(`Starting DOM assignment for ${Object.keys(jsonResponse).length} content element(s)`);

    const showContextMenu = window.FRONTEND_EDIT_SHOW_CONTEXT_MENU !== false;
    let successfulAssignments = 0;
    let failedAssignments = 0;

    for (let uid in jsonResponse) {
      const contentElement = jsonResponse[uid];
      let element = document.querySelector(`#c${uid}`);
      let originalUid = uid;

      // Handle translation mapping
      if (element && element.tagName.toLowerCase() === 'a' && contentElement.element.l10n_source) {
        let l10nElement = document.querySelector(`#c${contentElement.element.l10n_source}`);
        if (l10nElement) {
          uid = contentElement.element.l10n_source;
          element = l10nElement;
          debugLog(`Translation mapping: c${originalUid} → c${uid}`);
        }
      }

      if (!element) {
        failedAssignments++;
        debugLog(`DOM assignment failed: Element c${uid} not found`, null, 'warn');
        continue;
      }

      successfulAssignments++;

      // Ensure element has relative positioning for toolbar
      const computedStyle = window.getComputedStyle(element);
      if (computedStyle.position === 'static') {
        element.style.position = 'relative';
      }

      // Determine if we should show context menu (based on setting AND if menu has children)
      const hasMenuChildren = contentElement.menu.children && Object.keys(contentElement.menu.children).length > 0;
      const effectiveShowContextMenu = showContextMenu && hasMenuChildren && !contentElement.menu.url;

      // Create toolbar
      const toolbar = createToolbar(uid, contentElement, effectiveShowContextMenu);
      element.appendChild(toolbar);

      // Create dropdown if context menu is enabled
      let dropdown = null;
      if (effectiveShowContextMenu) {
        dropdown = createDropdownMenu(uid, contentElement);
        document.body.appendChild(dropdown);

        const kebabBtn = toolbar.querySelector('.frontend-edit__btn--kebab');
        if (kebabBtn) {
          setupKebabEvents(kebabBtn, dropdown);
        }
      }

      // Setup hover events
      element.addEventListener('mouseenter', () => {
        element.classList.add('frontend-edit--active');
      });

      element.addEventListener('mouseleave', (e) => {
        // Check if we're moving to the dropdown
        if (dropdown && dropdown.contains(e.relatedTarget)) {
          return;
        }
        element.classList.remove('frontend-edit--active');
        if (dropdown) {
          dropdown.style.display = 'none';
        }
      });

      // Handle dropdown mouseleave
      if (dropdown) {
        dropdown.addEventListener('mouseleave', (e) => {
          // Check if we're moving back to the element
          if (element.contains(e.relatedTarget)) {
            return;
          }
          dropdown.style.display = 'none';
          element.classList.remove('frontend-edit--active');
        });
      }

      debugLog(`DOM assignment successful: c${uid}`, {
        CType: contentElement.element.CType,
        ctypeLabel: contentElement.element.ctypeLabel,
        showContextMenu: effectiveShowContextMenu
      });
    }

    debugLog('DOM assignment summary', {
      totalProcessed: Object.keys(jsonResponse).length,
      successfulAssignments: successfulAssignments,
      failedAssignments: failedAssignments
    });
  };

  /**
   * Main initialization function.
   */
  const getContentElements = async () => {
    try {
      const startTime = performance.now();

      initializeTheme();

      const dataItems = collectDataItems();
      const jsonResponse = await fetchContentElements(dataItems);
      renderContentElements(jsonResponse);
      setupGlobalClickHandler();

      const endTime = performance.now();
      debugLog(`Frontend Edit initialization completed in ${Math.round(endTime - startTime)}ms`);

    } catch (error) {
      debugLog('Frontend Edit initialization failed', {
        error: error.message,
        stack: error.stack
      }, 'error');
    }
  };

  // Initialize
  if (window.FRONTEND_EDIT_DEBUG) {
    debugLog('Debug mode enabled');
  }

  getContentElements();
});
