..  include:: /Includes.rst.txt

..  _delineation:

============
Delineation
============

There are several TYPO3 extensions that aim to improve the editing experience
between frontend and backend. This page provides an overview of related
extensions and how they differ from **xima_typo3_frontend_edit**.

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
