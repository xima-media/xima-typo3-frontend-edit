..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

..  _requirements:

Requirements
============

-   PHP 8.1 - 8.4
-   TYPO3 11.5 LTS - 13.4 LTS

..  _steps:

Installation
============

Require the extension via Composer (recommended):

..  code-block:: bash

    composer require xima/xima-typo3-frontend-edit

Or download it from the
`TYPO3 extension repository <https://extensions.typo3.org/extension/xima_typo3_frontend_edit>`__.

Configuration
============

Include the static TypoScript template "Frontend edit" or directly import it in your sitepackage:


..  code-block:: typoscript
    :caption: Configuration/TypoScript/setup.typoscript

    @import 'EXT:xima_typo3_frontend_edit/Configuration/TypoScript/setup.typoscript'

..  note::

    **TYPO3 13**: Include the Frontend Edit (:code:`xima/xima-typo3-frontend-edit`) site set.
