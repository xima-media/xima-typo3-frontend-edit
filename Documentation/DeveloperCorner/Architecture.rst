..  include:: /Includes.rst.txt

..  _how-it-works:
..  _architecture:

============
How it works
============

Frontend Edit deliberately keeps the client dumb: the server decides what may be
edited, the browser only renders it.

..  figure:: /Images/intro.gif
    :alt: Frontend Edit Screencast

Request flow
============

#.  A PSR-15 **middleware** injects the CSS and JavaScript before ``</body>`` —
    but only if a backend user is logged in. For regular visitors the response
    is untouched.

#.  On page load the script collects the :ref:`content element IDs
    <template-requirements>` from the DOM and calls the AJAX endpoint
    :code:`/typo3/ajax/xima-frontend-edit/edit-information`.

#.  The **server** filters that list — backend user permissions, the
    :ref:`site settings filters <site-settings>` (pages, doktypes, CTypes,
    UIDs) and translation resolution (see :ref:`languages`) — and returns only
    the elements the current user may actually edit, together with the menu for
    each of them.

#.  The script **assigns** each menu to its DOM element and renders the edit
    button, the context menu and, where enabled, insert buttons and drag
    handles. The menu entries link to the corresponding edit views in the
    TYPO3 backend.

..  note::

    Because the menus are delivered through an uncached AJAX request, frontend
    editing works on fully cached pages without polluting the page cache.

Permissions are decided server-side
===================================

Permissions are never evaluated in the browser. Every action link is generated
server-side from the backend user's actual permissions, and write operations
such as :ref:`drag & drop <drag-and-drop>` re-check those permissions before
touching a record.

What the client needs
=====================

The only hard requirement is that content elements are identifiable in the
rendered HTML — ``fluid_styled_content`` already covers this:

..  code-block:: html
    :caption: Example HTML output of a content element

    <div id="c10" class="frame frame-default frame-type-textpic frame-layout-0">
        ...
    </div>

..  seealso::

    :ref:`template-requirements` covers custom templates, EXT:container,
    EXT:dce and the optional markers.
