..  include:: /Includes.rst.txt

..  _inline-editing:
..  _contextual-editing:

==============
Inline Editing
==============

..  versionadded:: 2.2.0

..  note::

    Inline editing is **experimental**. Details of the interface may change in
    future releases.

Inline editing lets editors change content elements and page properties without
leaving the frontend. Clicking an edit button opens the TYPO3 FormEngine edit
form in a panel next to the page — no detour through the backend.

..  figure:: /Images/contextual-sidebar.gif
    :alt: Screencast of inline editing
    :class: with-shadow

    Editing a content element without leaving the frontend

..  _inline-editing-setup:

Setup
=====

Inline editing is **off by default**. Enable it in your site settings:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    frontendEdit:
      enableContextualEditing: true

Or via the TYPO3 backend:
:guilabel:`Site Management > Sites > Edit site > Settings > Frontend Edit > Appearance`.

..  important::

    The setting is required on **all supported TYPO3 versions**. What differs is
    only *how* the edit form is presented — see :ref:`inline-editing-variants`.
    The setting name kept its technical spelling
    (:confval:`frontendEdit.enableContextualEditing`) for backwards
    compatibility.

..  _inline-editing-variants:

How the form is presented
=========================

The extension picks the presentation automatically, based on whether the TYPO3
core route ``record_edit_contextual`` is available. There is nothing to
configure beyond the setting above.

..  tabs::

    ..  group-tab:: TYPO3 v14.2 and newer

        Edit links open a **sidebar panel** that slides in from the right,
        driven by the native ``record_edit_contextual`` route.

        Creating new content still uses the slide-in modal, because the
        New Content Element Wizard is hosted in the page module.

        ..  figure:: /Images/sidebar.jpg
            :alt: Inline editing sidebar on TYPO3 v14.2+
            :class: with-shadow

            The sidebar panel on TYPO3 v14.2+

    ..  group-tab:: TYPO3 v13 and v14.0 / v14.1

        Edit links open a **slide-in modal** that loads the standard backend
        edit form in an iframe. The route ``record_edit_contextual`` does not
        exist on these versions, so the modal takes over both editing and the
        New Content Element Wizard.

        From an editor's perspective the workflow is the same: open, edit,
        save, close.

..  _inline-editing-usage:

Usage
=====

With inline editing enabled:

*   Clicking an **edit button** opens the panel instead of navigating to the
    backend.
*   The **page properties** link in the :ref:`toolbar <toolbar>` opens in the
    panel as well.
*   **Ctrl+Click** (or **Cmd+Click** on macOS) bypasses the panel and opens the
    full backend editor directly.

Controls
--------

Save
    Saves the record and keeps the panel open.

Save & Close
    Saves and closes the panel. The page reloads to reflect the changes.

Close
    Closes the panel. Unsaved changes trigger a confirmation prompt.

Expand
    Opens the full backend editor. Respects the
    :ref:`Target blank <extconf-linkTargetBlank>` setting (same window or new
    tab).

**Escape** and clicking the **backdrop** also close the panel.

..  _inline-editing-limitations:

Limitations
===========

The FormEngine runs outside its intended context, so some advanced features are
limited:

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Area
        -   Behavior
    *   -   IRRE / relation browsers
        -   May have limited functionality.
    *   -   Context menus (three-dot menu)
        -   Not available inside the panel. The primary actions (edit, hide,
            delete) are available as direct buttons.
    *   -   Console errors
        -   Errors from backend JavaScript modules are expected and harmless.

..  tip::

    For the full editing experience use the **Expand** button, which opens the
    record in the regular backend.

..  _inline-editing-fallback:

Fallback behavior
=================

Setting disabled
    All edit links navigate to the backend, exactly as without this feature.

JavaScript disabled
    Edit links fall back to their ``href`` attribute, which points to the
    standard backend URL.

..  seealso::

    *   :confval:`frontendEdit.enableContextualEditing` — the setting reference
    *   :ref:`empty-columns` — creating new content in a column, which also
        relies on this setting
