..  include:: /Includes.rst.txt

..  _developer-corner:

================
Developer corner
================

Three opportunities exist to extend the Edit Menu with custom entries:

- Use an :ref:`event <events>` to modify the Edit Menu directly
- Use a :ref:`viewhelper <data-attributes>` to extend the Edit Menu with data entries
- Use :ref:`column target markers <empty-columns>` to add "Create new content" buttons for empty columns and at the end of filled columns

Additionally, you can provide a :ref:`custom css <custom-styling>` file to adjust the styling.

For UI that needs to run in the browser, see the :ref:`JavaScript API <javascript-api>`.

..  toctree::
    :maxdepth: 3

    Events
    DataAttributes
    EmptyColumns
    CustomStyling
    JavaScriptApi
