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

The rendered Edit Menu links to the corresponding edit views in the TYPO3 backend.

..  note::

    The script is only injected if the current backend user is logged in.

..  figure:: /Images/intro.gif
    :alt: Frontend Edit Screencast
