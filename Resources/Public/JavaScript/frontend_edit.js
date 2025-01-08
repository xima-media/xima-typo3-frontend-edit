document.addEventListener('DOMContentLoaded', function () {
  const getContentElements = async () => {
    let dataItems = {};

    document.querySelectorAll('.xima-typo3-frontend-edit--data').forEach(function (element) {
      const data = element.value;
      let closestElement = element;
      while (closestElement && !closestElement.id.match(/c\d+/)) {
        closestElement = closestElement.parentElement;
      }

      if (closestElement) {
        const id = closestElement.id.replace('c', '');
        if (!(id in dataItems)) {
          dataItems[id] = [];
        }

        dataItems[id].push(JSON.parse(data));
      }
    });

    const url = new URL(window.location.href);
    url.searchParams.set('type', '1729341864');

    try {
      const response = await fetch(url.toString(), {
        cache: 'no-cache',
        method: 'POST',
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(dataItems),
      });
      if (!response.ok) return;

      const jsonResponse = await response.json();

      for (let uid in jsonResponse) {
        const contentElement = jsonResponse[uid];
        let element = document.querySelector(`#c${uid}`);
        if (!element) {
          // check for translated element
          if (contentElement.element.l10n_source) {
            element = document.querySelector(`#c${contentElement.element.l10n_source}`);
            if (element) {
              uid = contentElement.element.l10n_source;
            } else {
              continue;
            }
          } else {
            continue;
          }
        }

        const editButton = document.createElement('button');
        editButton.className = 'xima-typo3-frontend-edit--edit-button';
        editButton.title = contentElement.menu.label;
        editButton.innerHTML = contentElement.menu.icon;
        editButton.setAttribute('data-cid', uid);

        const dropdownMenu = document.createElement('div');
        dropdownMenu.className = 'xima-typo3-frontend-edit--dropdown-menu';
        dropdownMenu.setAttribute('data-cid', uid);

        const dropdownMenuInner = document.createElement('div');
        dropdownMenuInner.className = 'xima-typo3-frontend-edit--dropdown-menu-inner';
        dropdownMenu.appendChild(dropdownMenuInner);

        for (let actionName in contentElement.menu.children) {
          const action = contentElement.menu.children[actionName];
          let actionElement;

          if (action.type === 'link') {
            actionElement = document.createElement('a');
            actionElement.href = action.url;
          } else if (action.type === 'divider') {
            actionElement = document.createElement('div');
            actionElement.className = 'xima-typo3-frontend-edit--divider';
          } else {
            actionElement = document.createElement('div');
          }

          actionElement.classList.add(actionName);
          actionElement.innerHTML = `${action.icon ?? ''} <span>${action.label}</span>`;
          dropdownMenuInner.appendChild(actionElement);
        }

        editButton.addEventListener('click', function (event) {
          event.preventDefault();
          dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'visible' : 'block';
        });


        let wrapperElement = document.createElement('div');
        wrapperElement.className = 'xima-typo3-frontend-edit--wrapper';
        wrapperElement.appendChild(editButton);
        wrapperElement.appendChild(dropdownMenu);
        document.body.appendChild(wrapperElement);

        element.addEventListener('mouseover', function () {
          let rect = element.getBoundingClientRect();

          let defaultEditButtonMargin = 10;
          let defaultEditButtonHeight = 30;
          // if the element is too small, adjust the position of the edit button
          if (rect.height < 50) {
            defaultEditButtonMargin = (rect.height - defaultEditButtonHeight) / 2;
          }

          // if the dropdown menu is too close to the bottom of the page, move it to the top
          // currently it's not possible to fetch the height of the dropdown menu before it's visible once, so we have to use a fixed value
          if (document.documentElement.clientHeight - rect.top - rect.height < 500) {
            dropdownMenu.style.bottom = `19px`;
          } else {
            dropdownMenu.style.top = `${defaultEditButtonMargin + 30}px`;
          }

          wrapperElement.style.top = `${rect.top + document.documentElement.scrollTop}px`;
          wrapperElement.style.left = `${rect.right - 30}px`;
          editButton.style.top = `${defaultEditButtonMargin}px`;
          editButton.style.left = `-10px`;
          editButton.style.display = 'flex';
          element.classList.add('xima-typo3-frontend-edit--edit-container');
        });

        element.addEventListener('mouseout', function (event) {
          if (event.relatedTarget === editButton || event.relatedTarget === dropdownMenu) return;
          editButton.style.display = 'none';
          dropdownMenu.style.display = 'none';
          element.classList.remove('xima-typo3-frontend-edit--edit-container');
        });

        document.querySelectorAll('.xima-typo3-frontend-edit--dropdown-menu').forEach(function (menu) {
          menu.addEventListener('mouseleave', function (event) {
            const cid = menu.getAttribute('data-cid');
            menu.style.display = 'none';
            document.querySelector(`.xima-typo3-frontend-edit--edit-button[data-cid="${cid}"]`).style.display = 'none';
            document.querySelector(`#c${cid}`).classList.remove('xima-typo3-frontend-edit--edit-container');
          });
        });

        document.addEventListener('click', function (event) {
          document.querySelectorAll('.xima-typo3-frontend-edit--dropdown-menu').forEach(function (menu) {
            const button = menu.previousElementSibling;
            if (!menu.contains(event.target) && !button.contains(event.target)) {
              menu.style.display = 'none';
            }
          });
        });
      }
    } catch (error) {
      console.log(error);
    }
  };

  getContentElements();
});
