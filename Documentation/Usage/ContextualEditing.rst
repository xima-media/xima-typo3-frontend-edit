..  include:: /Includes.rst.txt

..  _contextual-editing:

==============================
Contextual Editing (Sidebar)
==============================

..  versionadded:: 2.2.0

..  versionchanged:: 2.3.0
    On TYPO3 v13, an iframe modal editor is now used automatically — no
    configuration required. The previous fallback (navigating to the backend)
    no longer applies.

..  note::

    All inline editing features (sidebar and iframe modal) are
    **experimental**. The contextual editing sidebar requires
    **TYPO3 v14.2+**. On TYPO3 v13, the extension provides a slide-in
    iframe modal as an alternative inline editing experience.

The contextual editing sidebar allows editors to edit content elements and page
properties directly in the frontend without leaving the page. A sidebar panel
slides in from the right, displaying the TYPO3 FormEngine edit form inside an
iframe.

..  figure:: /Images/sidebar.jpg
    :alt: Contextual editing sidebar showing the edit form for a content element
    :class: with-shadow

    The contextual editing sidebar opens directly in the frontend

Enabling Contextual Editing
============================

Enable the feature in your site settings:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    frontendEdit:
      enableContextualEditing: true

Or via the TYPO3 backend in
:guilabel:`Site Management > Sites > Edit site > Settings > Frontend Edit > Appearance`.

How It Works
============

When contextual editing is enabled:

- Clicking the **edit button** on a content element opens the sidebar instead
  of navigating to the backend.
- The **page properties** link in the sticky toolbar also opens in the sidebar.
- The sidebar loads the TYPO3 ``record_edit_contextual`` route in an iframe.

Sidebar Controls
----------------

The sidebar provides the following controls (rendered by TYPO3's backend):

Save
    Saves the record and keeps the sidebar open for further editing.

Save & Close
    Saves the record and closes the sidebar. The page reloads automatically
    to reflect the changes.

Close
    Closes the sidebar. If there are unsaved changes, TYPO3 prompts for
    confirmation.

Expand (fullscreen icon)
    Opens the full backend editor. Respects the ``linkTargetBlank`` extension
    setting (same window or new tab).

Keyboard & Mouse Shortcuts
--------------------------

- **Escape** closes the sidebar.
- **Ctrl+Click** (or **Cmd+Click** on macOS) on an edit button opens the full
  backend editor directly, bypassing the sidebar.
- Clicking the **backdrop** (darkened area) closes the sidebar.

Known Limitations
=================

Since the sidebar runs the TYPO3 backend FormEngine in an iframe outside its
intended context, some advanced form features may have limited functionality:

- **Complex field types** like inline relational record editing (IRRE) or
  relation browsers may not fully work in all cases.
- **Browser console errors** from TYPO3 backend JavaScript modules are expected
  and do not affect core editing functionality.
- For full editing capabilities, use the **expand button** to open the complete
  backend editor.

Fallback Behavior
=================

The inline editing features degrade gracefully:

- **TYPO3 v13 / v14.0-v14.1**: The ``record_edit_contextual`` route does not
  exist. The extension uses a slide-in iframe modal that loads the standard
  backend edit form. This works automatically without any configuration.
- **TYPO3 v14.2+ with setting disabled**: When ``enableContextualEditing`` is
  ``false`` (default), all edit links navigate to the backend as before.
- **JavaScript disabled**: The ``href`` attribute on edit links still points to
  the standard backend URL, ensuring basic functionality.
