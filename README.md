<div align="center">

![Extension icon](Resources/Public/Icons/Extension.png)

# TYPO3 extension `xima_typo3_frontend_edit`

[![Latest Stable Version](https://typo3-badges.dev/badge/xima_typo3_frontend_edit/version/shields.svg)](https://extensions.typo3.org/extension/xima_typo3_frontend_edit)
![TYPO3](https://img.shields.io/badge/TYPO3-13.4%20%7C%2014.3-orange.svg)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/xima/xima-typo3-frontend-edit/php?logo=php)](https://packagist.org/packages/xima/xima-typo3-frontend-edit)
[![Coverage](https://img.shields.io/coverallsCoverage/github/xima-media/xima-typo3-frontend-edit?logo=coveralls)](https://coveralls.io/github/xima-media/xima-typo3-frontend-edit)
[![CGL](https://img.shields.io/github/actions/workflow/status/xima-media/xima-typo3-frontend-edit/cgl.yml?label=cgl&logo=github)](https://github.com/xima-media/xima-typo3-frontend-edit/actions/workflows/cgl.yml)
[![Tests](https://img.shields.io/github/actions/workflow/status/xima-media/xima-typo3-frontend-edit/tests.yml?label=tests&logo=github)](https://github.com/xima-media/xima-typo3-frontend-edit/actions/workflows/tests.yml)
[![License](https://poser.pugx.org/xima/xima-typo3-frontend-edit/license)](LICENSE.md)

</div>

This TYPO3 extension adds lightweight editing tools to the frontend, allowing backend users to edit, hide, delete, and reorder content elements and pages without leaving the site. It works out of the box with TYPO3's default `fluid_styled_content` templates; custom templates need to expose a content element ID (c-id). See [How it works](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/HowItWorks/Index.html) for details.

![Frontend Edit](./Documentation/Images/screenshot.jpg)

> [!NOTE]
> **Why?** TYPO3 editors often need to switch between the frontend and the backend to find and edit the right content element. This extension eliminates that context switch by providing editing actions directly where the content is displayed, making the editorial workflow faster and more intuitive.

## ✨ Features

- **Content Element Editing**
  - [Edit Dropdown Menu](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Usage/EditMenu.html) - Quick access to edit, hide, delete, and move content elements, with confirmation before deleting records
  - [Contextual Editing](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Usage/ContextualEditing.html) - Edit content directly in the frontend (experimental)
  - [Drag & Drop Reordering](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Usage/DragAndDrop.html) - Reorder content elements within and between columns, including EXT:container columns, directly in the frontend (experimental, requires `frontendEdit.enableDragAndDrop`)
  - New Content Elements - Create new content elements via TYPO3's native New Content Element Wizard, hosted in the slide-in iframe modal on both v13 and v14 (requires `frontendEdit.enableContextualEditing`)
- **Page Toolbar**
  - [Toolbar](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Usage/Toolbar.html) - Page-level actions and toggle for frontend editing
- **Configuration & Customization**
  - [Site Settings](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Configuration/SiteSettings.html) - Per-site configuration via YAML
  - [UserTSconfig](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Configuration/UserTSconfig.html) - Disable frontend editing per user or user group
  - [PSR-14 Events](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/DeveloperCorner/Events.html) - Customize menus with custom actions
  - ViewHelpers - [Data attributes](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/DeveloperCorner/DataAttributes.html) for related records, [column target buttons](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/DeveloperCorner/EmptyColumns.html) for new content (empty columns and end-of-column)

### [Inline Editing](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Usage/ContextualEditing.html)

> [!NOTE]
> New in **v2.2+**, thanks to [Violetta Digital Craft](https://www.violetta.ch/) for supporting this feature. This feature is still experimental and may change in future releases.

Edit content elements directly in the frontend without navigating to the backend. Enable via Site Settings: `frontendEdit.enableContextualEditing: true`

![Inline Editing](./Documentation/Images/inline-edit-screencast.gif)

> [!IMPORTANT]
> **Delineation and classification**: This is **not** a further development of the "original" extension [frontend_editing](https://extensions.typo3.org/extension/frontend_editing). It is similar in some ways to the realisation of the [feedit](https://extensions.typo3.org/extension/feedit) extension. This extension is an independent implementation with a different approach. See the [Delineation](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Delineation/Index.html) page in the documentation for a detailed comparison with related extensions like [visual_editor](https://github.com/FriendsOfTYPO3/visual_editor) and [content_preview](https://github.com/T3-UX/content_preview).

## 🔥 Installation

### Requirements

* TYPO3 >= 13.4
* PHP 8.2+

### Supports

| **Version** | **TYPO3** | **PHP** |
|-------------|-----------|---------|
| 2.x         | 13-14     | 8.2-8.5 |
| 1.x         | 11-13     | 8.1-8.5 |

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

## 📙 Documentation

Please have a look at the
[official extension documentation](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Index.html).

> [!NOTE]
> Facing trouble or issues? You may find help in the following sections:
> - [How it works](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/HowItWorks/Index.html)
> - [Migration](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Migration/Index.html)
> - [FAQ](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/FAQ/Index.html)

## 🧑‍💻 Contributing

Please take a look at [Contributing](CONTRIBUTING.md).

## 💎 Credits

Thanks to [move:elevator](https://www.move-elevator.de/) and [XIMA](https://www.xima.de/) for supporting the development of this extension.

## ⭐ License

This project is licensed
under [GNU General Public License 2.0 (or later)](LICENSE.md).
