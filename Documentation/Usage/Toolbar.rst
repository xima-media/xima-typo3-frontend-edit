..  include:: /Includes.rst.txt

..  _toolbar:

=======
Toolbar
=======

..  figure:: /Images/page.gif
    :alt: Toolbar Screencast

The Toolbar provides quick access to page-level actions. It appears
at a configurable position on the screen (default: bottom-right).

..  figure:: /Images/toolbar.jpg
    :alt: Toolbar

Actions
=======

The Toolbar includes:

- **Toggle** - (eye icon) Enable or disable frontend editing for the current session
- **Edit page properties** - Open the current page properties
- **Edit page** - Open the page module in the backend
- **Info** - Display page information
- **Move** - Reorder page

..  figure:: /Images/toolbar-dropdown.jpg
    :alt: Toolbar Menu


Position
========

You can configure the Toolbar position via :ref:`site-settings`:

.. code-block:: yaml

   frontendEdit:
     toolbarPosition: 'bottom-right'

Available positions:

- ``top-left``, ``top-center``, ``top-right``
- ``bottom-left``, ``bottom-center``, ``bottom-right``
- ``left-top``, ``left-center``, ``left-bottom``
- ``right-top``, ``right-center``, ``right-bottom``

Disabling Frontend Edit
=======================

You can temporarily disable frontend editing by clicking the toggle button
in the Toolbar. This setting is stored per user and persists across sessions.

To disable the Toolbar entirely, set ``showStickyToolbar: false`` in the
:ref:`site-settings`.
