document.addEventListener('DOMContentLoaded', function () {
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
    document.querySelectorAll('.xima-typo3-frontend-edit--data').forEach((element) => {
      const data = element.value;
      const closestElement = getClosestElementWithId(element);

      if (closestElement) {
        const id = closestElement.id.replace('c', '');
        if (!dataItems[id]) {
          dataItems[id] = [];
        }
        dataItems[id].push(JSON.parse(data));
      }
    });
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

    const response = await fetch(url.toString(), {
      cache: 'no-cache',
      method: 'POST',
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify(dataItems),
    });

    if (!response.ok) throw new Error('Failed to fetch content elements');
    return response.json();
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
    for (let uid in jsonResponse) {
      const contentElement = jsonResponse[uid];
      let element = document.querySelector(`#c${uid}`);

      if (contentElement.element.l10n_source) {
        element = document.querySelector(`#c${contentElement.element.l10n_source}`);
        if (!element) continue;
        uid = contentElement.element.l10n_source;
      }

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
  };

  /**
  * Main function to collect data, fetch content elements, and render them.
  * Handles errors during the process.
  */
  const getContentElements = async () => {
    try {
      const dataItems = collectDataItems();
      const jsonResponse = await fetchContentElements(dataItems);
      renderContentElements(jsonResponse);
      setupDropdownMenuEvents();
    } catch (error) {
      console.error(error);
    }
  };

  getContentElements();
});
