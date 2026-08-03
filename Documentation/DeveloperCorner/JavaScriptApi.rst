..  include:: /Includes.rst.txt

..  _javascript-api:

=========================
Extending the frontend UI
=========================

For UI that needs to run in the browser - status badges, comment indicators,
custom toolbar buttons - the extension exposes a small, stable facade on
:code:`window.XimaFrontendEdit` and dispatches DOM :code:`CustomEvent`\ s at
defined lifecycle points. Internal refactors of the underlying JavaScript do
not change these method signatures or event :code:`detail` shapes - this is
the one part of the frontend JavaScript covered by a semver guarantee.

..  note::
    Everything described here requires frontend editing to be active for the
    current backend user (see :ref:`Introduction <introduction>`). When
    editing is disabled, :code:`xfe:ready` still fires once with an empty
    element map, but no elements are ever registered.

Facade methods
===============

``getElementInfo(uid)``
------------------------

Returns the resolved target element and payload for a content element uid, so
consumers never have to re-do DOM resolution (anchor pattern, translation
mapping). Returns :code:`null` if the uid was not rendered.

..  code-block:: javascript

    const info = window.XimaFrontendEdit.getElementInfo(42);
    // { uid: 42, element: HTMLElement, payload: { element: {...}, menu: {...} } }

``notify({ title, message, severity })``
------------------------------------------

Shows a toast notification using the extension's own notification manager.
:code:`severity` is one of :code:`ok`, :code:`info`, :code:`warning`,
:code:`error`.

..  code-block:: javascript

    window.XimaFrontendEdit.notify({
        title: 'Comment added',
        message: 'A new comment was posted on this element.',
        severity: 'info',
    });

``registerToolbarItem(uid, buttonSpec)``
------------------------------------------

Adds a button to a content element's hover toolbar, next to the built-in
Edit/More actions buttons. :code:`buttonSpec`:

- ``html`` - inner HTML of the button (e.g. an inline SVG icon)
- ``label`` - accessible label, also used as the tooltip
- ``href`` - renders an :code:`<a>` instead of a :code:`<button>`
- ``onClick`` - click handler

..  code-block:: javascript

    window.XimaFrontendEdit.registerToolbarItem(42, {
        html: '<svg>...</svg>',
        label: 'Show comments',
        onClick: () => openCommentsPanel(42),
    });

``registerBadge(uid, spec)``
------------------------------

Renders a persistent, hover-independent indicator on a content element's
overlay - useful for "live annotating" a page, where a marker must be
visible at a glance rather than only on hover. :code:`spec`:

- ``html`` or ``element`` - the badge content (HTML string or a DOM element)
- ``position`` - one of ``top-left``, ``top-right`` (default), ``bottom-left``, ``bottom-right``
- ``onClick`` - click handler; the badge only receives pointer events when this is set

..  code-block:: javascript

    window.XimaFrontendEdit.registerBadge(42, {
        html: '<span class="my-badge" title="3 comments">3</span>',
        position: 'top-left',
        onClick: () => openCommentsPanel(42),
    });

``openBackendView(url, options)``
------------------------------------

Opens a backend URL in the version-appropriate container: the contextual
sidebar (v14.2+, when enabled and available), the v13 iframe modal, or a new
tab as a fallback - no version checks needed in consumer code. The returnUrl
flash-message deferral mechanism used by edit forms is applied automatically.
:code:`options`:

- ``target`` - :code:`'tab'` forces a new tab regardless of version
- ``title`` - shown in the container's header
- ``width`` - a CSS length (e.g. :code:`'600px'`, :code:`'50%'`); invalid values are ignored
- ``onClose`` - called with :code:`{ reason }` when the container closes (:code:`reason` is :code:`'close'`, or :code:`'saved'` for the sidebar's own save flow)
- ``reloadOnClose`` - reload the parent page on close (default :code:`true`)
- ``linkPolicy`` - a rule or array of rules :code:`{ match: string | RegExp, action }`, evaluated against link clicks inside the embedded document. :code:`action` is one of:

  - ``stay`` (default when nothing matches) - keep navigation inside the container, same as today's built-in behavior
  - ``close`` - close the container
  - ``ignore`` - swallow the click, do nothing
  - ``external`` - open the link in a new tab

  The policy only ever governs plain link clicks - the built-in save/close buttons of an actual edit form keep working exactly as before.

..  code-block:: javascript

    window.XimaFrontendEdit.openBackendView(commentsBackendUrl, {
        title: 'Comments',
        width: '480px',
        linkPolicy: { match: '/typo3/module/web/layout', action: 'close' },
        onClose: ({ reason }) => console.log('comments panel closed:', reason),
    });

Lifecycle events
==================

All events are dispatched on :code:`document`.

``xfe:ready``
--------------

Fired once, after the initial AJAX render (or immediately, with an empty map,
when frontend editing is disabled).

- ``detail.elements`` - plain object keyed by content element uid, each value shaped like the ``getElementInfo()`` return value

``xfe:element-rendered``
--------------------------

Fired once per content element, right after its overlay/toolbar is built.

- ``detail.uid``, ``detail.element``, ``detail.payload`` - same as ``getElementInfo()``
- ``detail.overlay``, ``detail.toolbar``, ``detail.dropdown`` - the underlying DOM nodes (``dropdown`` is ``null`` when the element has no context menu)

``xfe:dropdown-open`` / ``xfe:dropdown-close``
-------------------------------------------------

Fired when a content element's "More actions" dropdown opens or closes.
:code:`detail.uid` identifies the element. Only fires for an actual
open/close interaction - hovering between elements does not trigger a
spurious :code:`xfe:dropdown-close` for a dropdown that was never open.

..  code-block:: javascript

    document.addEventListener('xfe:element-rendered', (event) => {
        const { uid, payload } = event.detail;
        if (payload.element.CType === 'list' && payload.element.list_type === 'news_pi1') {
            window.XimaFrontendEdit.registerBadge(uid, { html: '<span>News</span>' });
        }
    });

Minimal integration example
==============================

..  code-block:: javascript
    :caption: A third-party extension listening for rendered elements

    document.addEventListener('xfe:ready', (event) => {
        console.log('Frontend Edit ready with', Object.keys(event.detail.elements).length, 'element(s)');
    });

    document.addEventListener('xfe:element-rendered', (event) => {
        const { uid } = event.detail;
        window.XimaFrontendEdit.registerToolbarItem(uid, {
            label: 'Open comments',
            onClick: () => window.XimaFrontendEdit.openBackendView('/comments/' + uid),
        });
    });
