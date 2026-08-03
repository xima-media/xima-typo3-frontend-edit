..  include:: /Includes.rst.txt

..  _how-it-works:

================
How it works
================

On page load a script calls an ajax endpoint, to fetch information about all editable (by the current backend user) content elements on the current page.

The script then injects (if it's possible) an Edit Menu into the frontend for each editable content element.

This is **only possible**, if the content element "c-ids" (Content Element IDs) are available in the frontend template, e.g. "c908". By default the fluid styled content elements provide these ids.

..  code-block:: html
    :caption: Example HTML output of a content element

    <div id="c10" class="frame frame-default frame-type-textpic frame-layout-0">
        ...
    </div>

..  tip::

    This is automatically done by the `fluid_styled_content` extension. If you are using custom content element templates, make sure to include the "c-id" within the wrapping HTML element of the content element.

Alternative: the ``data-frontend-edit`` attribute
=====================================================

For templates that cannot carry the "c-id" anchor - dynamic content element
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

    <div class="my-custom-wrapper"<xfe:editable record="{data}" />>
        ...
    </div>

Both patterns can be mixed freely on the same page; an element only needs
one of them. Unlike the "c-id" anchor pattern, a ``data-frontend-edit``
element is always treated as the content element itself - no sibling
resolution is attempted.

..  note::
    Headless/SPA frontends are an explicit non-goal: the script that scans
    for either pattern is injected server-side into the rendered HTML (see
    below), so it never runs for a frontend that TYPO3 does not render HTML
    for in the first place.

Editing foreign records (news, addresses, ...)
===================================================

The ``data-frontend-edit`` attribute also works for records from **any other
table** - not just ``tt_content`` - by adding a ``table`` prefix:
``data-frontend-edit="{table}:{uid}"``. This covers the classic case of
editing foreign records displayed on a detail page, e.g. a news detail page
rendered by EXT:news:

..  code-block:: html
    :caption: News detail template (Detail.html)

    <div class="news-detail"<xfe:editable record="{newsItem}" table="tx_news_domain_model_news" />>
        <h1>{newsItem.title}</h1>
        ...
    </div>

This is deliberately thin: the menu offers exactly **edit, info and
history** - no hide, delete or move, since those are meaningful only for
tables this extension understands specifically (``tt_content``, ``pages``).
Permissions are checked the same way as everywhere else in the extension
(the backend user's actual edit rights on that record); a table the current
user cannot edit - or that TYPO3 does not know at all - never gets a menu.
Translated records resolve to the current frontend language automatically,
the same way ``tt_content`` does.

Extend the menu the same way as for content elements, via the
:ref:`FrontendEditDropdownModifyEvent <events>` - the record row carries a
``_table`` key so a listener can tell it apart from a ``tt_content`` row.

The rendered Edit Menu links to the corresponding edit views in the TYPO3 backend.

..  note::

    The script is only injected if the current backend user is logged in.

..  figure:: /Images/intro.gif
    :alt: Frontend Edit Screencast
