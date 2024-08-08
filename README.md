<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 extension `xima_typo3_frontend_edit`

[![Supported TYPO3 versions](https://badgen.net/badge/TYPO3/12%20&%2013/orange)]()

</div>

This extension provides an edit button for editors within frontend content elements.

![Frontend Edit](./Documentation/Images/frontendEdit.png)

## Note

This is **not** a further development of the "original" extension [frontend_editing](https://extensions.typo3.org/extension/frontend_editing). It is similar in some ways to the realisation of the [feedit](https://extensions.typo3.org/extension/feedit) extension. This extension is an independent implementation with a different approach.

The extension has been developed to provide a simple and lightweight solution to easily start the editing of content elements from the frontend and thus reduce the gap between frontend and backend. Therefore a simple javascript is injected into the frontend, which generates action links to the TYPO3 backend with the corresponding edit views.


## Installation

``` bash
composer require xima/xima-typo3-frontend-edit
```

## Configuration

Include the static TypoScript template "Frontend edit" or directly import it in your sitepackage:

``` typoscript
@import 'EXT:xima_typo3_frontend_edit/Configuration/TypoScript/setup.typoscript'
```

## How it works

On page load a script calls an ajax endpoint, to fetch information about all editable (by the current backend user) content elements on the current page. The script then injects an edit button into the frontend for each editable content element. The edit button links to the corresponding edit view in the TYPO3 backend.

> Hint: The script is only injected if the current backend user is logged in.


![Screencast](./Documentation/Images/screencast.gif)

## Extend

ToDo

## License

This project is licensed
under [GNU General Public License 2.0 (or later)](LICENSE.md).
