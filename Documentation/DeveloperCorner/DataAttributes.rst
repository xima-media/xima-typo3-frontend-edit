..  include:: /Includes.rst.txt

..  _data-attributes:

=======================
Data Attributes
=======================

Additionally, there is an option to extend your fluid template to provide data for extra dropdown menu entries, e.g. edit links to all news entries within a list plugin.

..  code-block:: html
    :caption: Custom Fluid Template

    <div class="news-item">
        ...
        <xtfe:data label="{news.title}" uid="{news.uid}" table="tx_news_domain_model_news" icon="content-news" />
    </div>

This generates a hidden input element with the provided data (only if the frontend edit is enabled). Within the parent content element (e.g. the whole list plugin), a new "data" section will show up on the dropdown menu to list all edit links.

..  figure:: /Images/data.png
    :alt: Frontend Edit Extended Data Entries

Keep in mind, that this only works if the parent content element has a c-id and offer one of the following data combinations:

- Edit record link (provide :code:`uid` and :code:`table` of the desired record, the link to the TYPO3 backend will be generated automatically)
- Custom edit url (provide a custom :code:`url`)


..  note::
    Keep in mind, that this option will add additional html elements to your dom, which can causes style issues.

..  seealso::

    View the sources on GitHub:

    -   `DataViewHelper <https://github.com/xima-media/xima-typo3-frontend-edit/blob/main/Classes/ViewHelpers/DataViewHelper.php>`__
