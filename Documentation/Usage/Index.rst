..  include:: /Includes.rst.txt

..  _usage:

=====
Usage
=====

Once installed and configured, the extension provides the following editing
interfaces in the frontend for logged-in backend users.

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

    ..  card::  Inline Editing (experimental)

        Edit content elements and page properties in a panel next to the page,
        without navigating to the backend at all.

        ..  card-footer::   :ref:`Learn more <inline-editing>`
            :button-style: btn btn-secondary stretched-link

    ..  card::  Drag & Drop Reordering (experimental)

        Reorder content elements within a column or move them to another
        column of the same page by dragging them in the frontend.

        ..  card-footer::   :ref:`Learn more <drag-and-drop>`
            :button-style: btn btn-secondary stretched-link

..  toctree::
    :hidden:

    EditMenu
    Toolbar
    InlineEditing
    DragAndDrop
