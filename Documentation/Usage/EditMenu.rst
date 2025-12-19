..  include:: /Includes.rst.txt

..  _edit-menu:

=========
Edit Menu
=========

Each content element on the page displays an edit button when you hover over it.

..  figure:: /Images/edit-button.jpg
    :alt: Edit Button

Clicking this button opens the Edit Menu with various actions:

- **Edit Content Element** - Open the content element in the backend editor
- **Edit page** - Open the current page properties
- **Hide/Unhide** - Toggle visibility of the content element
- **Info** - Display content element information
- **Move** - Reorder the content element
- **History** - View the content element's history
- **New content after** - Add a new content element after this one

..  figure:: /Images/edit-dropdown.jpg
    :alt: Edit Menu

..  note::
    The Edit Menu is only displayed if the backend user has the necessary
    permissions to edit the content element.

Requirements
============

The Edit Menu requires content elements to have a "c-id" (Content Element ID)
in their HTML output, e.g. ``id="c908"``.

This is automatically provided by the ``fluid_styled_content`` extension.
For custom templates, ensure the wrapping element includes the content element UID:

..  code-block:: html

    <div id="c{data.uid}">
        ...
    </div>

See also the :ref:`faq` for common issues with the Edit Menu.
