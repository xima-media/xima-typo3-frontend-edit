..  include:: /Includes.rst.txt

..  _delineation:

============
Delineation
============

There are several TYPO3 extensions that aim to improve the editing experience
between frontend and backend. This page provides an overview of related
extensions and how they differ from **xima_typo3_frontend_edit**.

At a glance
===========

..  list-table::
    :header-rows: 1
    :widths: 26 22 26 26

    *   -   Extension
        -   Where it runs
        -   Editing model
        -   Template changes
    *   -   **xima_typo3_frontend_edit**
        -   Regular frontend
        -   Links to backend forms; optionally :ref:`inline <inline-editing>`
            in a panel
        -   None with ``fluid_styled_content``
    *   -   visual_editor
        -   Backend module (frontend in an iframe)
        -   Inline WYSIWYG (CKEditor 5)
        -   ViewHelper integration required
    *   -   feedit
        -   Regular frontend
        -   Server-side injected edit icons
        -   None (core integration removed)
    *   -   frontend_editing
        -   Regular frontend
        -   Own editing overlay
        -   Depends on setup
    *   -   content_preview
        -   Backend page module
        -   Preview only, no frontend editing
        -   None

..  note::

    The comparison below reflects our understanding at the time of writing.
    Please refer to each extension's own documentation for authoritative and
    current information.

..  _delineation-visual-editor:

visual_editor (FriendsOfTYPO3)
==============================

`visual_editor <https://github.com/FriendsOfTYPO3/visual_editor>`__ provides
inline WYSIWYG editing using Web Components and CKEditor 5. Editors can modify
text fields in place, reorder content elements via drag-and-drop, and save
changes without leaving the page. It runs exclusively within a dedicated
backend module that embeds the frontend page in an iframe — it does not inject
editing capabilities into the regular frontend. It also requires ViewHelper
integration in Fluid templates. In contrast, this extension works directly in
the frontend, requires no template changes, and links to standard backend forms
for editing.

..  _delineation-feedit:

feedit
======

`feedit <https://extensions.typo3.org/extension/feedit>`__ was the original
TYPO3 core extension for frontend editing. It injected edit icons directly into
the rendered HTML on the server side. The core integration it relied on has
since been removed. This extension uses a different approach with PSR-15
middleware and client-side AJAX menus.

..  _delineation-frontend-editing:

frontend_editing
================

`frontend_editing <https://extensions.typo3.org/extension/frontend_editing>`__
is an older extension that provided frontend editing capabilities for TYPO3.
This extension is **not** a further development of frontend_editing. While the
general goal is similar, the technical approach is entirely different — this
extension uses lightweight JavaScript injection and links to backend forms.

..  _delineation-content-preview:

content_preview
===============

`content_preview <https://github.com/T3-UX/content_preview>`__ provides a
split-view with a live frontend preview within the TYPO3 backend Page module.
It enhances the **backend** interface, while this extension enhances the
**frontend**. Both approaches can be used together.
