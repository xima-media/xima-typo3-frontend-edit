..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

..  _requirements:

Requirements
============

*   PHP 8.2 - 8.5
*   TYPO3 13.4 LTS - 14.x

..  _version-matrix:

Version matrix
--------------

..  list-table::
    :header-rows: 1
    :widths: 20 40 40

    *   -   Version
        -   TYPO3
        -   PHP
    *   -   2.x
        -   13.4 LTS - 14.x
        -   8.2 - 8.5
    *   -   1.x
        -   11 - 13
        -   8.1 - 8.5

..  _steps:

Step 1: Install the extension
=============================

..  tabs::

    ..  group-tab:: Composer

        ..  code-block:: bash

            composer require xima/xima-typo3-frontend-edit

    ..  group-tab:: TER

        Download the extension from the
        `TYPO3 extension repository <https://extensions.typo3.org/extension/xima_typo3_frontend_edit>`__
        and install it via :guilabel:`Admin Tools > Extensions`.

..  _site-set:

Step 2: Include the site set
============================

The extension ships a site set that registers its settings and TypoScript.
Without it, nothing is injected into the frontend.

..  tabs::

    ..  group-tab:: YAML

        Add the set to your site configuration:

        ..  code-block:: yaml
            :caption: config/sites/my-site/config.yaml

            dependencies:
              - xima/xima-typo3-frontend-edit

    ..  group-tab:: Backend

        Go to :guilabel:`Site Management > Sites`, edit your site and add
        **Frontend Edit** under :guilabel:`Sets for this Site`.

..  _verify:

Step 3: Verify the setup
========================

#.  Log into the TYPO3 backend.
#.  Open a page of your site in the frontend **in the same browser**.
#.  The :ref:`toolbar <toolbar>` appears in the bottom-right corner, and
    hovering a content element reveals its edit button.

..  tip::

    Nothing visible? The :ref:`faq` walks through the six usual causes — the
    most common one is missing content element IDs in custom Fluid templates.

Next steps
==========

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :card-height: 100

    ..  card::  Start editing

        See what editors can do in the frontend: edit menu, toolbar, inline
        editing and drag & drop.

        ..  card-footer::   :ref:`View usage guide <usage>`
            :button-style: btn btn-secondary stretched-link

    ..  card::  Adapt it

        Restrict frontend editing to certain pages, content types or user
        groups, and change its appearance.

        ..  card-footer::   :ref:`View configuration options <configuration>`
            :button-style: btn btn-secondary stretched-link
