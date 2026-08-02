..  include:: /Includes.rst.txt

..  _template-requirements:

=====================
Template requirements
=====================

The extension has exactly **one** requirement of your templates: every content
element must be identifiable in the rendered HTML.

..  _c-id:

The content element ID
======================

Each content element needs a "c-id" — its UID prefixed with ``c`` — on the
element that wraps it:

..  code-block:: html
    :caption: Rendered HTML of a content element

    <div id="c10" class="frame frame-default frame-type-textpic">
        ...
    </div>

This is how the injected JavaScript maps a DOM node to a database record. No
c-id means no edit button for that element.

..  tabs::

    ..  group-tab:: fluid_styled_content

        Nothing to do. ``fluid_styled_content`` renders the c-id out of the box,
        so the extension works immediately after :ref:`installation`.

    ..  group-tab:: Custom templates

        Add the UID to the wrapping element yourself:

        ..  code-block:: html
            :caption: EXT:my_sitepackage/Resources/Private/Templates/Content/Default.html

            <div id="c{data.uid}">
                ...
            </div>

    ..  group-tab:: EXT:container

        `container <https://extensions.typo3.org/extension/container/>`__
        templates do not render the c-id by default:

        ..  code-block:: html
            :caption: Container template

            <div id="c{data.uid}">
               <f:for each="{children_200}" as="record">
                   <f:format.raw>{record.renderedContent}</f:format.raw>
               </f:for>
            </div>

    ..  group-tab:: EXT:dce

        `DCE <https://extensions.typo3.org/extension/dce>`__ elements need the
        c-id added to the
        `DCE template <https://docs.typo3.org/p/t3/dce/main/en-us/UsersManual/Template.html>`__:

        ..  code-block:: html
            :caption: DCE template

            <div class="dce" id="c{contentObject.uid}">
                Your template goes here...
            </div>

..  note::

    Styling problems may occur with nested content elements, because the
    injected UI is positioned relative to the wrapping element.

..  _template-requirements-optional:

Optional markers
================

Two features need additional markers in your templates. Both are opt-in — omit
them and the corresponding feature simply does not appear.

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Marker
        -   Needed for
    *   -   :ref:`ColumnTargetViewHelper <empty-columns>`
        -   "Create new content" buttons per column, and
            :ref:`drag-and-drop` (drop targets cannot be resolved without it)
    *   -   :ref:`Data ViewHelper <data-attributes>`
        -   Edit links for related records inside a plugin, e.g. single news
            items in a list

..  _template-requirements-scope:

What cannot be edited
=====================

Only content elements belonging to the **current page** receive a menu.
Inherited content — a shared footer pulled in from another page, for example —
cannot be edited from the inheriting page. Use the
:ref:`toolbar <toolbar>` to jump to the page that owns the record.

..  seealso::

    *   :ref:`how-it-works` — what happens on page load
    *   :ref:`faq` — troubleshooting when no menu appears
