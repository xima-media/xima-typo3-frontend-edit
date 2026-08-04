..  include:: /Includes.rst.txt

..  _developer-corner:

================
Developer corner
================

How the extension works internally, and the APIs it offers to extend it.

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :card-height: 100

    ..  card::  How it works

        Request flow, why permissions are decided server-side, and what the
        client needs from your templates.

        ..  card-footer::   :ref:`Deep dive into concepts <how-it-works>`
            :button-style: btn btn-secondary stretched-link

    ..  card::  PSR-14 Events

        Add, remove or modify entries in the Edit Menu and the Toolbar.

        ..  card-footer::   :ref:`Customize the menus <events>`
            :button-style: btn btn-secondary stretched-link

    ..  card::  Data ViewHelper

        Add edit links for related records — e.g. every news item inside a
        list plugin.

        ..  card-footer::   :ref:`Extend the Edit Menu <data-attributes>`
            :button-style: btn btn-secondary stretched-link

    ..  card::  Language handling

        Which language modes are supported and how the extension resolves the
        record to edit.

        ..  card-footer::   :ref:`View language support <languages>`
            :button-style: btn btn-secondary stretched-link

..  seealso::

    Looking for the template side? :ref:`integration` covers the required
    content element IDs, column markers and custom styling.

For UI that needs to run in the browser, see the :ref:`JavaScript API <javascript-api>`.

..  toctree::
    :hidden:

    Architecture
    Events
    DataAttributes
    Languages
    JavaScriptApi
