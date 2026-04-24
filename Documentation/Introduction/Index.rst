..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  _what-it-does:

What does it do?
================

This extension adds editing tools to the frontend, allowing backend users to edit, hide, delete, and reorder content elements and pages without leaving the site.

..  figure:: /Images/screenshot.jpg
    :alt: Frontend Edit Preview

The extension injects a small JavaScript into the frontend that generates action links to the TYPO3 backend, bridging the gap between frontend preview and backend editing.

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
- :ref:`Inline Editing <contextual-editing>` *(experimental)* - Edit content directly in the frontend without navigating to the backend (TYPO3 v13: iframe modal, TYPO3 v14.2+: contextual sidebar)

..  versionadded:: 2.2.0

    **Contextual Editing Sidebar** — An experimental feature that allows editors
    to edit content elements and page properties in a sidebar panel directly in the
    frontend. Leverages TYPO3 v14.2's ``record_edit_contextual`` route.
    See :ref:`contextual-editing` for details.

    ..  figure:: /Images/sidebar.jpg
        :alt: Contextual editing sidebar
        :class: with-shadow

..  versionadded:: 2.3.0

    **Iframe Modal Editor** — An experimental slide-in iframe modal that brings
    inline editing to TYPO3 v13. Opens the backend edit form in a panel directly
    in the frontend — no configuration required.
    See :ref:`contextual-editing` for details.

..  note::
    This is **not** a further development of the "original" extension `frontend_editing <https://extensions.typo3.org/extension/frontend_editing>`_. It is similar in some ways to the realisation of the `feedit <https://extensions.typo3.org/extension/feedit>`_ extension. This extension is an independent implementation with a different approach. See :ref:`Delineation <delineation>` for a detailed comparison with related extensions like `visual_editor <https://github.com/FriendsOfTYPO3/visual_editor>`_ and `content_preview <https://github.com/T3-UX/content_preview>`_.

..  _support:

Support
=======

There are several ways to get support for this extension:

* GitHub: https://github.com/xima-media/xima-typo3-frontend-edit/issues

License
=======

This extension is licensed under
`GNU General Public License 2.0 (or later) <https://www.gnu.org/licenses/old-licenses/gpl-2.0.html>`_.
