..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  _what-it-does:

What does it do?
================

This extension provides an edit menu for editors within the frontend regarding content elements and pages.

..  figure:: /Images/screenshot.jpg
    :alt: Frontend Edit Preview

The extension has been developed to provide a simple and lightweight solution to easily start the editing of content elements from the frontend and thus reduce the gap between frontend and backend. Therefore a simple javascript is injected into the frontend, which generates action links to the TYPO3 backend with the corresponding edit views.

Features
========

- :ref:`Edit Menu <edit-menu>` - Quick access to edit, hide, delete, and move content elements
- :ref:`Toolbar <toolbar>` - Page-level actions and toggle for frontend editing
- :ref:`Site Settings <site-settings>` - Per-site configuration via YAML
- :ref:`PSR-14 Events <events>` - Customize menus with custom actions
- :ref:`Data ViewHelper <data-attributes>` - Add edit links for related records (e.g., news items)
- Dark/Light Mode - Automatic or manual color scheme selection
- Configurable Position - 12 toolbar positions available
- :ref:`Save & Close <extconf-enableSaveAndCloseButton>` - Quick return to frontend after editing

..  note::
    This is **not** a further development of the "original" extension `frontend_editing <https://extensions.typo3.org/extension/frontend_editing>`_. It is similar in some ways to the realisation of the `feedit <https://extensions.typo3.org/extension/feedit>`_ extension. This extension is an independent implementation with a different approach.

    Unlike `content_preview <https://github.com/T3-UX/content_preview>`_, which provides a split-view with live frontend preview within the backend Page module, this extension works directly in the frontend and does not modify the backend interface.

..  _support:

Support
=======

There are several ways to get support for this extension:

* GitHub: https://github.com/xima-media/xima-typo3-frontend-edit/issues

License
=======

This extension is licensed under
`GNU General Public License 2.0 (or later) <https://www.gnu.org/licenses/old-licenses/gpl-2.0.html>`_.
