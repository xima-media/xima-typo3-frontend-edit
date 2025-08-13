document.addEventListener('DOMContentLoaded', function () {
  /**
   * Debug logging utility - only logs when debug mode is enabled
   * @param {string} message - The debug message
   * @param {*} data - Optional data to log
   * @param {string} level - Log level (log, warn, error)
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
   * Finds the closest parent element with an ID matching the pattern "c\d+".
   * @param {HTMLElement} element - The starting element.
   * @returns {HTMLElement|null} - The closest matching element or null if not found.
   */
  const getClosestElementWithId = (element) => {
    while (element && !element.id.match(/c\d+/)) {
      element = element.parentElement;
    }
    return element;
  };

  /**
   * Collects data items from elements with the class "xima-typo3-frontend-edit--data".
   * Groups the data by the closest element's ID.
   * @returns {Object} - A dictionary of data items grouped by ID.
   */
  const collectDataItems = () => {
    const dataItems = {};
    const dataElements = document.querySelectorAll('.xima-typo3-frontend-edit--data');

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
          elementPosition: element.getBoundingClientRect(),
          parsedData: parsedData,
          closestElementId: closestElement.id
        });
      } else {
        debugLog(`Additional data element ${index + 1}: No matching content element found`, {
          element: element,
          data: data
        });
      }
    });

    if (Object.keys(dataItems).length > 0) {
      debugLog(`Collected additional data items summary:`, {
        totalContentElements: Object.keys(dataItems).length,
        contentElementIds: Object.keys(dataItems),
        dataItems: dataItems
      });
    }

    return dataItems;
  };

  /**
   * Sends a POST request to fetch content elements based on the provided data items.
   * @param {Object} dataItems - The data items to send in the request body.
   * @returns {Promise<Object>} - The JSON response from the server.
   * @throws {Error} - If the request fails.
   */
  const fetchContentElements = async (dataItems) => {
    const url = new URL(window.location.href);
    url.searchParams.set('type', '1729341864');

    debugLog('Sending request to backend for content element information', {
      url: url.toString(),
      requestData: dataItems
    });

    const response = await fetch(url.toString(), {
      cache: 'no-cache',
      method: 'POST',
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify(dataItems),
    });

    if (!response.ok) {
      debugLog(`Backend request failed with status ${response.status}`, {
        status: response.status,
        statusText: response.statusText,
        url: url.toString()
      }, 'error');
      throw new Error('Failed to fetch content elements');
    }

    const jsonResponse = await response.json();
    debugLog(`Backend response received with ${Object.keys(jsonResponse).length} content element(s)`, {
      responseData: jsonResponse,
      contentElementCount: Object.keys(jsonResponse).length
    });

    return jsonResponse;
  };

  /**
   * Creates an edit button for a content element.
   * @param {string} uid - The unique ID of the content element.
   * @param {Object} contentElement - The content element data.
   * @returns {HTMLButtonElement} - The created edit button.
   */
  const createEditButton = (uid, contentElement) => {
    const editButton = contentElement.menu.url ? document.createElement('a') : document.createElement('button');
    editButton.className = 'xima-typo3-frontend-edit--edit-button';
    editButton.title = contentElement.menu.label;
    editButton.innerHTML = contentElement.menu.icon;
    editButton.setAttribute('data-cid', uid);
    if (contentElement.menu?.type === 'link' && contentElement.menu?.url) {
      editButton.href = contentElement.menu.url;
      if (contentElement.menu.targetBlank) editButton.target = '_blank';
    }
    return editButton;
  };

  /**
   * Creates a dropdown menu for a content element.
   * @param {string} uid - The unique ID of the content element.
   * @param {Object} contentElement - The content element data.
   * @returns {HTMLDivElement} - The created dropdown menu.
   */
  const createDropdownMenu = (uid, contentElement) => {
    const dropdownMenu = document.createElement('div');
    dropdownMenu.className = 'xima-typo3-frontend-edit--dropdown-menu';
    dropdownMenu.setAttribute('data-cid', uid);

    const dropdownMenuInner = document.createElement('div');
    dropdownMenuInner.className = 'xima-typo3-frontend-edit--dropdown-menu-inner';
    dropdownMenu.appendChild(dropdownMenuInner);

    for (let actionName in contentElement.menu.children) {
      const action = contentElement.menu.children[actionName];
      const actionElement = document.createElement(action.type === 'link' ? 'a' : 'div');
      if (action.type === 'link') {
        actionElement.href = action.url;
        if (action.targetBlank) actionElement.target = '_blank';
      }
      if (action.type === 'divider') actionElement.className = 'xima-typo3-frontend-edit--divider';

      actionElement.classList.add(actionName);
      actionElement.innerHTML = `${action.icon ?? ''} <span>${action.label}</span>`;
      dropdownMenuInner.appendChild(actionElement);
    }

    return dropdownMenu;
  };

  /**
   * Positions the edit button and dropdown menu relative to the target element.
   * @param {HTMLElement} element - The target element.
   * @param {HTMLElement} wrapperElement - The wrapper element containing the button and menu.
   * @param {HTMLElement} editButton - The edit button.
   * @param {HTMLElement} dropdownMenu - The dropdown menu.
   */
  const positionElements = (element, wrapperElement, editButton, dropdownMenu) => {
    const rect = element.getBoundingClientRect();
    const rectInPageContext = {
      top: rect.top + document.documentElement.scrollTop,
      left: rect.left + document.documentElement.scrollLeft,
      width: rect.width,
      height: rect.height,
    };

    let defaultEditButtonMargin = 10;
    // if the element is too small, adjust the position of the edit button
    if (rect.height < 50) {
      defaultEditButtonMargin = (rect.height - 30) / 2;
    }

    // if the dropdown menu is too close to the bottom of the page, move it to the top
    // currently it's not possible to fetch the height of the dropdown menu before it's visible once, so we have to use a fixed value
    if (document.documentElement.scrollHeight - rectInPageContext.top - rect.height < 500 &&
      rect.height < 700 &&
      rectInPageContext.top > 500
    ) {
      dropdownMenu.style.bottom = `19px`;
    } else {
      dropdownMenu.style.top = `${defaultEditButtonMargin + 30}px`;
    }

    wrapperElement.style.top = `${rect.top + document.documentElement.scrollTop}px`;
    wrapperElement.style.left = `${rect.right - 30}px`;
    editButton.style.top = `${defaultEditButtonMargin}px`;
    editButton.style.left = `-10px`;
    editButton.style.display = 'flex';
  };

  /**
   * Sets up hover events for the target element, edit button, and dropdown menu.
   * @param {HTMLElement} element - The target element.
   * @param {HTMLElement} wrapperElement - The wrapper element containing the button and menu.
   * @param {HTMLElement} editButton - The edit button.
   * @param {HTMLElement} dropdownMenu - The dropdown menu.
   */
  const setupHoverEvents = (element, wrapperElement, editButton, dropdownMenu) => {
    element.addEventListener('mouseover', () => {
      positionElements(element, wrapperElement, editButton, dropdownMenu);
      element.classList.add('xima-typo3-frontend-edit--edit-container');
    });

    element.addEventListener('mouseout', (event) => {
      if (event.relatedTarget === editButton || event.relatedTarget === dropdownMenu) return;
      editButton.style.display = 'none';
      dropdownMenu.style.display = 'none';
      element.classList.remove('xima-typo3-frontend-edit--edit-container');
    });
  };

  /**
   * Sets up events for dropdown menus to handle mouse leave and click outside.
   */
  const setupDropdownMenuEvents = () => {
    document.querySelectorAll('.xima-typo3-frontend-edit--dropdown-menu').forEach((menu) => {
      menu.addEventListener('mouseleave', (event) => {
        const cid = menu.getAttribute('data-cid');
        menu.style.display = 'none';
        document.querySelector(`.xima-typo3-frontend-edit--edit-button[data-cid="${cid}"]`).style.display = 'none';
        document.querySelector(`#c${cid}`).classList.remove('xima-typo3-frontend-edit--edit-container');
      });
    });

    document.addEventListener('click', (event) => {
      document.querySelectorAll('.xima-typo3-frontend-edit--dropdown-menu').forEach((menu) => {
        const button = menu.previousElementSibling;
        if (!menu.contains(event.target) && !button.contains(event.target)) {
          menu.style.display = 'none';
        }
      });
    });
  };

  /**
   * Renders content elements by creating edit buttons and dropdown menus for each.
   * @param {Object} jsonResponse - The JSON response containing content element data.
   */
  const renderContentElements = (jsonResponse) => {
    debugLog(`Starting DOM assignment for ${Object.keys(jsonResponse).length} content element(s)`);

    let successfulAssignments = 0;
    let failedAssignments = 0;
    let translationMappings = 0;

    for (let uid in jsonResponse) {
      const contentElement = jsonResponse[uid];
      let element = document.querySelector(`#c${uid}`);
      let originalUid = uid;
      let translationInfo = null;

      /*
       *
       * Handle translation mapping
       *
       * This is a little bit tricky, cause the correct container detection depends on the language overlay mode, especially for extbase models:
       * https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Localization/Index.html
       *
       * We assume the default fluid_styled_content rendering, where both ids (of the original and the translation) are present in the DOM.
       *
       *  <div id="c{data.uid}">
       *     <f:if condition="{data._LOCALIZED_UID}">
       *         <a id="c{data._LOCALIZED_UID}"></a>
       *     </f:if>
       *     ...
       * </div>
       *
       * By default, the `data.uid` is the original uid, and `data._LOCALIZED_UID` is the translation uid.
       * So when we get the translated content element, we check if the c-ID is within an a-tag (so just an anchor for
       * the translated uid) and if it has a `l10n_source` property.
       * If so, we try to find the wrapper element with the original uid regarding the `l10n_source` property.
       */
      if (element.tagName.toLowerCase() === 'a' && contentElement.element.l10n_source) {
        let l10nElement = document.querySelector(`#c${contentElement.element.l10n_source}`);
        if (l10nElement) {
          translationInfo = {
            originalUid: uid,
            translationSourceUid: contentElement.element.l10n_source,
            language: contentElement.element.sys_language_uid || 'default'
          };
          uid = contentElement.element.l10n_source;
          element = l10nElement;
          translationMappings++;

          debugLog(`Translation mapping: c${originalUid} → c${uid}`, translationInfo);
        } else {
          debugLog(`Translation source element c${contentElement.element.l10n_source} not found in DOM for c${uid}`, {
            originalUid: uid,
            l10nSource: contentElement.element.l10n_source,
            contentElement: contentElement.element
          }, 'warn');
        }
      }

      if (!element) {
        failedAssignments++;
        debugLog(`DOM assignment failed: Element c${uid} not found`, {
          originalUid: originalUid,
          uid: uid,
          contentElement: contentElement.element,
          translationInfo: translationInfo
        }, 'warn');
        continue;
      }

      successfulAssignments++;
      debugLog(`DOM assignment successful: c${uid}`, {
        originalUid: originalUid,
        element: {
          id: element.id,
          className: element.className,
          tagName: element.tagName,
          position: element.getBoundingClientRect()
        },
        contentElement: {
          type: contentElement.element.CType,
          pid: contentElement.element.pid,
          sys_language_uid: contentElement.element.sys_language_uid,
          l10n_source: contentElement.element.l10n_source
        },
        menuStructure: {
          type: contentElement.menu.type,
          hasChildren: Object.keys(contentElement.menu.children || {}).length,
          isSimpleMode: !!contentElement.menu.url
        },
        translationInfo: translationInfo
      });

      const simpleMode = contentElement.menu.url;
      const editButton = createEditButton(uid, contentElement);
      const dropdownMenu = createDropdownMenu(uid, contentElement);

      editButton.addEventListener('click', (event) => {
        if (!simpleMode) {
          event.preventDefault();
          dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'visible' : 'block';
        }
      });

      const wrapperElement = document.createElement('div');
      wrapperElement.className = 'xima-typo3-frontend-edit--wrapper';
      wrapperElement.appendChild(editButton);
      if (!simpleMode) wrapperElement.appendChild(dropdownMenu);
      document.body.appendChild(wrapperElement);

      setupHoverEvents(element, wrapperElement, editButton, dropdownMenu);
    }

    debugLog('DOM assignment summary', {
      totalProcessed: Object.keys(jsonResponse).length,
      successfulAssignments: successfulAssignments,
      failedAssignments: failedAssignments,
      translationMappings: translationMappings,
      assignmentRate: `${Math.round((successfulAssignments / Object.keys(jsonResponse).length) * 100)}%`
    });

    if (failedAssignments > 0) {
      debugLog(`${failedAssignments} content elements could not be assigned to DOM elements`, null, 'warn');
    }
  };

  /**
   * Main function to collect data, fetch content elements, and render them.
   * Handles errors during the process.
   */
  const getContentElements = async () => {
    try {
      const startTime = performance.now();

      const dataItems = collectDataItems();
      const jsonResponse = await fetchContentElements(dataItems);
      renderContentElements(jsonResponse);
      setupDropdownMenuEvents();

      const endTime = performance.now();
      debugLog(`Frontend Edit initialization completed successfully in ${Math.round(endTime - startTime)}ms`, {
        processingTime: `${Math.round(endTime - startTime)}ms`,
        contentElementsFound: Object.keys(dataItems).length,
        contentElementsProcessed: Object.keys(jsonResponse).length
      });

    } catch (error) {
      debugLog('Frontend Edit initialization failed', {
        error: error.message,
        stack: error.stack
      }, 'error');
    }
  };

  // Initialize when debug mode is enabled
  if (window.FRONTEND_EDIT_DEBUG) {
    debugLog('Debug mode enabled - detailed logging active');
  }

  getContentElements();
});
