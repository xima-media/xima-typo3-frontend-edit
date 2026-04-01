..  include:: /Includes.rst.txt

..  _usage:

=====
Usage
=====

Once installed and configured, the extension provides two main editing interfaces
in the frontend for logged-in backend users.

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :card-height: 100

    ..  card::  Edit Menu

        The Edit Menu appears on content elements and provides quick access
        to editing actions like edit, hide, delete, and move.

        ..  card-footer::   :ref:`Learn more <edit-menu>`
            :button-style: btn btn-secondary stretched-link

    ..  card::  Toolbar

        The Toolbar provides page-level actions and a toggle to enable or
        disable frontend editing.

        ..  card-footer::   :ref:`Learn more <toolbar>`
            :button-style: btn btn-secondary stretched-link

    ..  card::  Contextual Editing (Sidebar)

        Edit content elements and page properties in a sidebar panel without
        leaving the frontend. Experimental, requires TYPO3 v14.2+.

        ..  card-footer::   :ref:`Learn more <contextual-editing>`
            :button-style: btn btn-secondary stretched-link

..  toctree::
    :hidden:

    EditMenu
    Toolbar
    ContextualEditing
