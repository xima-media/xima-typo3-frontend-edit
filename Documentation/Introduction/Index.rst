..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  _what-it-does:

What does it do?
================

This extension adds editing tools to the frontend, allowing backend users to
edit, hide, delete, reorder and create content elements and pages without
leaving the site.

..  figure:: /Images/screenshot.png
    :alt: Frontend Edit Preview
    :class: with-shadow

    Editing actions appear directly on the content element

TYPO3 editors normally switch to the backend to find and change the right
content element. The extension closes that gap: a small JavaScript is injected
into the frontend, asks the backend which elements the current user may edit,
and renders the matching actions right where the content is displayed.

..  _features:

Features
========

Editing content elements
------------------------

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Feature
        -   What it does
    *   -   :ref:`Edit Menu <edit-menu>`
        -   Edit, hide, delete, move, info and history for every content
            element, with a confirmation dialog before deleting.
    *   -   :ref:`Inline Editing <inline-editing>` *(experimental)*
        -   Edit content elements and page properties in a panel next to the
            page, without navigating to the backend.
    *   -   :ref:`New content <empty-columns>`
        -   Insert buttons on hover and per column, opening TYPO3's native
            New Content Element Wizard.
    *   -   :ref:`Drag & Drop <drag-and-drop>` *(experimental)*
        -   Reorder elements within a column or move them into another column
            of the same page, including EXT:container columns.

Editing pages
-------------

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Feature
        -   What it does
    *   -   :ref:`Toolbar <toolbar>`
        -   Page-level actions and a toggle to switch frontend editing on and
            off, at one of 12 configurable positions.
    *   -   :ref:`Save & Close <extconf-enableSaveAndCloseButton>`
        -   An extra button in backend edit forms that returns straight to the
            frontend.
    *   -   :confval:`Flash messages <frontendEdit.enableFlashMessages>`
        -   Backend save confirmations are shown as toast notifications in the
            frontend.

Configuration and extensibility
-------------------------------

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Feature
        -   What it does
    *   -   :ref:`Site Settings <site-settings>`
        -   Per-site configuration via YAML or the site module, including
            filters for pages, doktypes, CTypes and UIDs.
    *   -   :ref:`UserTSconfig <user-tsconfig>`
        -   Disable frontend editing per backend user or user group.
    *   -   :ref:`PSR-14 Events <events>`
        -   Add, remove or modify menu entries and attach custom data to
            elements.
    *   -   :ref:`ViewHelpers <data-attributes>`
        -   Edit links for related records (e.g. news items) and column
            markers for new-content buttons.
    *   -   :confval:`Dark / Light mode <frontendEdit.colorScheme>`
        -   Follows the system preference or is pinned to a fixed scheme.

..  note::
    This is **not** a further development of the "original" extension
    `frontend_editing <https://extensions.typo3.org/extension/frontend_editing>`_.
    It is an independent implementation with a different approach, similar in
    some ways to `feedit <https://extensions.typo3.org/extension/feedit>`_.
    See :ref:`delineation` for a detailed comparison with related extensions
    like `visual_editor <https://github.com/FriendsOfTYPO3/visual_editor>`_ and
    `content_preview <https://github.com/T3-UX/content_preview>`_.

..  _support:

Support
=======

There are several ways to get support for this extension:

*   GitHub issues: https://github.com/xima-media/xima-typo3-frontend-edit/issues
*   :ref:`FAQ <faq>` — answers to the most common problems

Security policy
===============

Please read our
`security policy <https://github.com/xima-media/xima-typo3-frontend-edit/blob/main/SECURITY.md>`__
if you discover a security vulnerability in this extension.

License
=======

This extension is licensed under
`GNU General Public License 2.0 (or later) <https://www.gnu.org/licenses/old-licenses/gpl-2.0.html>`_.
