..  include:: /Includes.rst.txt

..  _keyboard:

==============================
Keyboard and screen reader use
==============================

The editing UI is operable without a mouse. This page lists the keys that are
implemented and names the places where support is deliberately limited.

..  _keyboard-toolbar:

Content element toolbar
=======================

The buttons attached to a content element form a single tab stop. Once focused,
you move between the buttons with the arrow keys — the same pattern the TYPO3
backend uses.

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Key
        -   Action
    *   -   :kbd:`Tab`
        -   Move to the element toolbar; the first button receives focus
    *   -   :kbd:`←` / :kbd:`→`
        -   Move between the buttons of that toolbar, wrapping at both ends
    *   -   :kbd:`Enter`
        -   Activate the focused button

..  _keyboard-menu:

Edit Menu
=========

The three-dot button opens the menu. Inside it:

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Key
        -   Action
    *   -   :kbd:`↓` / :kbd:`↑`
        -   Move to the next / previous entry, wrapping at both ends
    *   -   :kbd:`Home` / :kbd:`End`
        -   Jump to the first / last entry
    *   -   :kbd:`Enter`
        -   Activate the focused entry
    *   -   :kbd:`Esc`
        -   Close the menu and return focus to the three-dot button

..  _keyboard-dialogs:

Dialogs and the inline editing panel
====================================

The delete confirmation dialog and the :ref:`inline editing <inline-editing>`
panel behave as modal dialogs:

*   :kbd:`Esc` closes them.
*   :kbd:`Tab` cycles **within** the dialog and does not escape to the page
    behind it.
*   Closing returns focus to the element that opened the dialog.

The delete dialog names the record it is about to remove — including its
identifier, e.g. ``My Element [tt_content:42]`` — so the target is
unambiguous when the dialog is announced.

..  _keyboard-limitations:

Known limitations
=================

Drag & drop reordering
    Native browser drag & drop is pointer-only. The drag handle is therefore
    kept out of the tab order and hidden from assistive technology on purpose,
    so it does not offer a focusable control that cannot be operated. Use the
    **move** entry in the Edit Menu instead — it opens the backend move dialog,
    which is fully keyboard-accessible. See :ref:`drag-and-drop`.

FormEngine inside the panel
    Inside the :ref:`inline editing <inline-editing>` panel the TYPO3 edit form
    runs outside its usual context. Context menus are not available; the
    **Expand** button opens the record in the regular backend when you need the
    full form.

..  _keyboard-preferences:

Respecting user preferences
===========================

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Preference
        -   Behavior
    *   -   Colour scheme
        -   Follows the operating system by default and can be pinned to light
            or dark via :confval:`frontendEdit.colorScheme`.
    *   -   Hover outline
        -   The outline drawn around the hovered element can be switched off
            entirely via :confval:`frontendEdit.enableOutline`.

..  tip::

    If the editing UI interferes with testing your site, switch it off with the
    eye icon in the :ref:`toolbar <toolbar>` rather than working around it.
