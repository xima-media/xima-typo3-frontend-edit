..  include:: /Includes.rst.txt

..  _empty-columns:

=============
Empty Columns
=============

The extension can display "Create new content" buttons for empty columns directly in the frontend. This allows editors to add content without switching to the TYPO3 backend.

..  figure:: /Images/empty-column.jpg
    :alt: Empty column marker showing a "Create new content" button in the frontend
    :class: with-shadow

    A "Create new content" button appears in empty columns when frontend editing is enabled

It works in two steps:

1. The integrator places a lightweight marker in their Fluid templates
2. The extension detects empty columns via AJAX and injects the buttons client-side

ColumnTargetViewHelper
======================

The :php:`ColumnTargetViewHelper` renders a hidden :html:`<div>` element as a DOM marker. No content, no styling, no logic — just a marker that the JavaScript picks up after the page loads.

Register the namespace in your Fluid template:

..  code-block:: html

    {namespace xfe=Xima\XimaTypo3FrontendEdit\ViewHelpers}

Or use the XML namespace syntax:

..  code-block:: html

    <html xmlns:xfe="http://typo3.org/ns/Xima/XimaTypo3FrontendEdit/ViewHelpers"
          data-namespace-typo3-fluid="true">

Arguments
---------

.. list-table::
   :header-rows: 1
   :widths: 20 10 10 60

   * - Name
     - Type
     - Required
     - Description
   * - ``colPos``
     - int
     - yes
     - The column position from the backend layout
   * - ``containerUid``
     - int
     - no
     - The UID of the parent container element (for b13/container columns)


Page columns
------------

Place the marker after the content rendering for each column:

..  code-block:: html
    :caption: Page template (e.g. Default.html)

    {namespace xfe=Xima\XimaTypo3FrontendEdit\ViewHelpers}

    <f:cObject typoscriptObjectPath="lib.dynamicContent" data="{pageUid: '{data.uid}', colPos: '0'}" />
    <xfe:columnTarget colPos="0" />

    <f:cObject typoscriptObjectPath="lib.dynamicContent" data="{pageUid: '{data.uid}', colPos: '1'}" />
    <xfe:columnTarget colPos="1" />

When colPos 0 or 1 is empty, a "+" button appears at the marker position. Clicking it opens the TYPO3 record editor to create a new content element in that column.

When the column has content, the marker stays hidden and has no effect on the page.

Container columns
-----------------

For container elements (e.g. two-column layouts, tabs, accordions using `b13/container <https://github.com/b13/container>`__), add the ``containerUid`` argument:

..  code-block:: html
    :caption: Container template (e.g. TwoColumn.html)

    {namespace xfe=Xima\XimaTypo3FrontendEdit\ViewHelpers}

    <div class="column-left">
        <f:for each="{children_201}" as="record">
            <f:format.raw>{record.renderedContent}</f:format.raw>
        </f:for>
        <xfe:columnTarget colPos="201" containerUid="{data.uid}" />
    </div>

    <div class="column-right">
        <f:for each="{children_202}" as="record">
            <f:format.raw>{record.renderedContent}</f:format.raw>
        </f:for>
        <xfe:columnTarget colPos="202" containerUid="{data.uid}" />
    </div>

..  important::

    Make sure the marker is always rendered, even when the container column is empty. If your template wraps the column content in a condition like ``<f:if condition="{children_201}">``, the marker will not be in the DOM when the column is empty and the button cannot appear.


How it works
============

1. The ViewHelper renders :html:`<div data-xfe-colpos="0" hidden></div>` (invisible, no layout impact)
2. The extension's JavaScript scans the page for these markers after loading
3. An AJAX request to the backend checks which columns are empty
4. For each empty column that has a matching marker, a "+" button is injected
5. The button opens the TYPO3 record editor with the correct colPos pre-filled

Container support requires the `b13/container <https://github.com/b13/container>`__ extension. The service automatically detects whether it is installed. Without it, only page columns are supported and container markers are silently ignored.


..  note::

    The marker is only rendered when a backend user is logged in and frontend editing is enabled. For regular frontend visitors, nothing is output.


..  seealso::

    View the sources on GitHub:

    -   `ColumnTargetViewHelper <https://github.com/xima-media/xima-typo3-frontend-edit/blob/main/Classes/ViewHelpers/ColumnTargetViewHelper.php>`__
    -   `EmptyColumnService <https://github.com/xima-media/xima-typo3-frontend-edit/blob/main/Classes/Service/Content/EmptyColumnService.php>`__
