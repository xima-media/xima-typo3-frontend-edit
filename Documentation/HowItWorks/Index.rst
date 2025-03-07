..  include:: /Includes.rst.txt

..  _how-it-works:

================
How it works
================

On page load a script calls an ajax endpoint, to fetch information about all editable (by the current backend user) content elements on the current page.

The script then injects (if it's possible) an edit menu into the frontend for each editable content element.

This is **only possible**, if the content element "c-ids" (Content Element IDs) are available in the frontend template, e.g. "c908". By default the fluid styled content elements provide these ids.

..  code-block:: html
    :caption: HTML output of a content element

    <div id="c10" class="frame frame-default frame-type-textpic frame-layout-0">
        ...
    </div>

The rendered dropdown menu links easily to the corresponding edit views in the TYPO3 backend.

..  note::

    The script is only injected if the current backend user is logged in.

..  figure:: /Images/screencast.gif
    :alt: Frontend Edit Screencast

For an easy edit workflow a "save and close" button is added to the edit form, which redirects the user directly back to the frontend. Disable this function within the extension settings, if you don't want this behaviour.
