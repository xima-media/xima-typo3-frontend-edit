..  include:: /Includes.rst.txt

..  _integration:

===========
Integration
===========

What your site package has to provide — and what it can optionally provide to
unlock more frontend editing features.

..  card-grid::
    :columns: 1
    :columns-md: 3
    :gap: 4
    :card-height: 100

    ..  card::  Template requirements

        The one thing your templates must do: expose a content element ID.
        Free with ``fluid_styled_content``.

        ..  card-footer::   :ref:`View requirements <template-requirements>`
            :button-style: btn btn-primary stretched-link

    ..  card::  Column Targets

        Mark your columns to get "Create new content" buttons — and to enable
        drag & drop reordering.

        ..  card-footer::   :ref:`Mark your columns <empty-columns>`
            :button-style: btn btn-secondary stretched-link

    ..  card::  Custom Styling

        Load your own CSS or JavaScript alongside the frontend edit resources.

        ..  card-footer::   :ref:`Adjust the styling <custom-styling>`
            :button-style: btn btn-secondary stretched-link

..  toctree::
    :hidden:

    TemplateRequirements
    ColumnTargets
    CustomStyling
