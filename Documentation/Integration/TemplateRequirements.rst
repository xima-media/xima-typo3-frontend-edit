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

..  _data-frontend-edit-attribute:

Alternative: the ``data-frontend-edit`` attribute
==================================================

For templates that cannot carry the c-id anchor - dynamic content element
extensions (DCE), other custom Fluid templates - a second matching channel
exists: a ``data-frontend-edit="tt_content:{uid}"`` attribute on the content
element's own wrapping HTML element.

..  code-block:: html
    :caption: Example HTML output using the data attribute instead

    <div data-frontend-edit="tt_content:10" class="my-custom-wrapper">
        ...
    </div>

The bundled ``<xfe:editable>`` ViewHelper renders this attribute for you:

..  code-block:: html
    :caption: Custom Fluid Template

    {namespace xfe=Xima\XimaTypo3FrontendEdit\ViewHelpers}

    <div class="my-custom-wrapper"<xfe:editable record="{data}" />>
        ...
    </div>

Both patterns can be mixed freely on the same page; an element only needs one
of them. Unlike the c-id anchor pattern, a ``data-frontend-edit`` element is
always treated as the content element itself - no sibling resolution is
attempted.

..  _editing-foreign-records:

Editing foreign records (news, addresses, ...)
================================================

The ``data-frontend-edit`` attribute also works for records from **any other
table** - not just ``tt_content`` - by adding a ``table`` prefix:
``data-frontend-edit="{table}:{uid}"``. This covers the classic case of
editing foreign records displayed on a detail page, e.g. a news detail page
rendered by EXT:news:

..  code-block:: html
    :caption: News detail template (Detail.html)

    {namespace xfe=Xima\XimaTypo3FrontendEdit\ViewHelpers}

    <div class="news-detail"<xfe:editable record="{newsItem}" table="tx_news_domain_model_news" />>
        <h1>{newsItem.title}</h1>
        ...
    </div>

This is deliberately thin: the menu offers exactly **edit, info and
history** - no hide, delete or move, since those are meaningful only for
tables this extension understands specifically (``tt_content``, ``pages``).
Permissions are checked the same way as everywhere else in the extension (the
backend user's actual edit rights on that record); a table the current user
cannot edit - or that TYPO3 does not know at all - never gets a menu.
Translated records resolve to the current frontend language automatically,
the same way ``tt_content`` does.

Extend the menu the same way as for content elements, via the
:ref:`FrontendEditDropdownModifyEvent <events>` - the record row carries a
``_table`` key so a listener can tell it apart from a ``tt_content`` row.

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
