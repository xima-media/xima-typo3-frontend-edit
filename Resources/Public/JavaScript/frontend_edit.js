document.addEventListener('DOMContentLoaded', function () {
  const getContentElements = async () => {
    if (!pid) return;

    const url = encodeURIComponent(window.location.href);
    const base = baseUrl ? baseUrl : '';
    const endpoint = `${base}/typo3/editable-content-elements?pid=${pid}&returnUrl=${url}`.replace(/([^:]\/)\/+/g, "$1");

    try {
      const response = await fetch(endpoint, {cache: 'no-cache'});
      if (!response.ok) return;

      const jsonResponse = await response.json();

      for (let uid in jsonResponse) {
        const contentElement = jsonResponse[uid];
        const element = document.querySelector(`#c${uid}`);
        if (!element) continue;

        const editButton = document.createElement('button');
        editButton.className = 'xima-typo3-frontend-edit--edit-button';
        editButton.title = contentElement.label;
        editButton.innerHTML = contentElement.icon;
        editButton.setAttribute('data-cid', uid);

        const dropdownMenu = document.createElement('div');
        dropdownMenu.className = 'xima-typo3-frontend-edit--dropdown-menu';
        dropdownMenu.setAttribute('data-cid', uid);

        const dropdownMenuInner = document.createElement('div');
        dropdownMenuInner.className = 'xima-typo3-frontend-edit--dropdown-menu-inner';
        dropdownMenu.appendChild(dropdownMenuInner);

        for (let actionName in contentElement.actions) {
          const action = contentElement.actions[actionName];
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

          actionElement.innerHTML = `${action.icon ?? ''} <span>${action.label}</span>`;
          dropdownMenuInner.appendChild(actionElement);
        }

        editButton.addEventListener('click', function (event) {
          event.preventDefault();
          dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });

        document.body.appendChild(editButton);
        document.body.appendChild(dropdownMenu);

        element.addEventListener('mouseover', function () {
          let rect = element.getBoundingClientRect();
          editButton.style.top = `${rect.top + document.documentElement.scrollTop + 10}px`;
          editButton.style.left = `${rect.right - 40}px`;
          editButton.style.display = 'block';
          dropdownMenu.style.top = `${rect.top + document.documentElement.scrollTop + 40}px`;
          dropdownMenu.style.right = `${document.documentElement.clientWidth - rect.right +5}px`;
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
