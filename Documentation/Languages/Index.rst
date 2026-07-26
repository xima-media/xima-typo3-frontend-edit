..  include:: /Includes.rst.txt

..  _languages:

=========
Languages
=========

Frontend Edit builds its Edit Menus from the content elements found on the
rendered page. Because TYPO3 renders translated pages differently depending on
the configured language mode, this page documents which modes are supported and
how the extension resolves the correct record to edit.

How translations are resolved
==============================

The injected script collects the content element ids (``c<uid>``) from the DOM
and sends them to the backend. In **connected mode** (language overlay) TYPO3
keeps the *default-language* uid on the overlaid row, so the DOM anchor carries
the default-language (L0) uid rather than the translation uid.

Frontend Edit therefore resolves translations on the server side via the
``l18n_parent`` pointer: for every requested L0 uid it also looks up the matching
translation and, when both exist, the translation wins. This guarantees that the
edit link targets the **translation uid** — TYPO3 FormEngine cannot switch the
edited record via a language parameter alone.

Mode support matrix
===================

..  list-table::
    :header-rows: 1
    :widths: 30 15 55

    *   -   Language mode
        -   Edit menu
        -   Behavior
    *   -   Default language (L0)
        -   ✅
        -   Elements are matched directly by their uid.
    *   -   Connected mode / overlay (``fallbackType: strict`` or ``fallback``)
        -   ✅
        -   Translations are resolved via ``l18n_parent``; the edit link targets
            the translation uid.
    *   -   Fallback-rendered default element
        -   ✅
        -   When no translation exists on a translated page, the default-language
            element still receives a menu (edits the L0 record).
    *   -   Free mode
        -   ✅
        -   Translated elements are standalone records with their own uid and are
            matched directly.
    *   -   All languages (``sys_language_uid = -1``)
        -   ✅
        -   Matched directly by uid, independent of the active language.
    *   -   Chained translation (``l10n_source`` ≠ ``l18n_parent``)
        -   ✅
        -   Resolved via the canonical ``l18n_parent`` pointer, not
            ``l10n_source``.

..  note::

    In connected mode the edit link always opens the translated record. Switching
    the edited language happens automatically through the resolved translation
    uid, so no manual language switch is required.
