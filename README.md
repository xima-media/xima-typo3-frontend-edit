<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 extension `xima_typo3_frontend_edit`

[![Latest Stable Version](https://typo3-badges.dev/badge/xima_typo3_frontend_edit/version/shields.svg)](https://extensions.typo3.org/extension/xima_typo3_frontend_edit)
[![Supported TYPO3 versions](https://typo3-badges.dev/badge/xima_typo3_frontend_edit/typo3/shields.svg)](https://extensions.typo3.org/extension/xima_typo3_frontend_edit)
[![License](https://poser.pugx.org/xima/xima-typo3-frontend-edit/license)](LICENSE.md)

</div>

This extension provides an edit button for editors within frontend content elements.

![Frontend Edit](./Documentation/Images/frontendEdit.png)

## Note

> This is **not** a further development of the "original" extension [frontend_editing](https://extensions.typo3.org/extension/frontend_editing). It is similar in some ways to the realisation of the [feedit](https://extensions.typo3.org/extension/feedit) extension. This extension is an independent implementation with a different approach.

The extension has been developed to provide a simple and lightweight solution to easily start the editing of content elements from the frontend and thus reduce the gap between frontend and backend. Therefore a simple javascript is injected into the frontend, which generates action links to the TYPO3 backend with the corresponding edit views.

## Installation

### Composer

[![Packagist](https://img.shields.io/packagist/v/xima/xima-typo3-frontend-edit?label=version&logo=packagist)](https://packagist.org/packages/xima/xima-typo3-frontend-edit)
[![Packagist Downloads](https://img.shields.io/packagist/dt/xima/xima-typo3-frontend-edit?color=brightgreen)](https://packagist.org/packages/xima/xima-typo3-frontend-edit)

``` bash
composer require xima/xima-typo3-frontend-edit
```

### TER

[![TER version](https://typo3-badges.dev/badge/xima_typo3_frontend_edit/version/shields.svg)](https://extensions.typo3.org/extension/xima_typo3_frontend_edit)
[![TER downloads](https://typo3-badges.dev/badge/xima_typo3_frontend_edit/downloads/shields.svg)](https://extensions.typo3.org/extension/xima_typo3_frontend_edit)

Download the zip file from [TYPO3 extension repository (TER)](https://extensions.typo3.org/extension/xima_typo3_frontend_edit).

## Documentation

Please have a look at the
[official extension documentation](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Index.html).

## Development

Use the following ddev command to easily install all supported TYPO3 versions for locale development.

```bash
ddev install all
```

## License

This project is licensed
under [GNU General Public License 2.0 (or later)](LICENSE.md).
