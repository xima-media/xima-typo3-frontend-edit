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

**Edit, hide, delete, reorder and create TYPO3 content — without leaving the frontend.**

</div>

![Frontend Edit](./Documentation/Images/screenshot.jpg)

> [!NOTE]
> **Why?** TYPO3 editors constantly switch between frontend and backend just to find the right content element. This extension removes that context switch: the editing actions appear exactly where the content is displayed.

<details>
<summary><b>See it in action</b></summary>

Edit menu and page toolbar:

![Frontend Edit screencast](./Documentation/Images/intro.gif)

Inline editing — the edit form opens next to the page:

![Inline editing screencast](./Documentation/Images/inline-edit-screencast.gif)

</details>

## ✨ Features

| Feature | What it does |
|---|---|
| [Edit Menu](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Usage/EditMenu.html) | Edit, hide, delete, move, info and history per content element — with a confirmation dialog before deleting |
| [Toolbar](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Usage/Toolbar.html) | Page-level actions and a toggle to switch frontend editing on and off |
| [Inline Editing](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Usage/InlineEditing.html) 🧪 | Edit content and page properties in a panel next to the page — sidebar on TYPO3 v14.2+, slide-in modal on v13 |
| [New content](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/DeveloperCorner/EmptyColumns.html) | Insert buttons on hover and per column, opening TYPO3's native New Content Element Wizard |
| [Drag & Drop](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Usage/DragAndDrop.html) 🧪 | Reorder elements within a column or move them into another column — incl. `EXT:container` |
| [Site Settings](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Configuration/SiteSettings.html) | Per-site configuration via YAML: appearance, toolbar position, and filters for pages, doktypes, CTypes and UIDs |
| [UserTSconfig](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Configuration/UserTSconfig.html) | Disable frontend editing per backend user or user group |
| [PSR-14 Events](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/DeveloperCorner/Events.html) & [ViewHelpers](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/DeveloperCorner/DataAttributes.html) | Add custom menu entries, attach data to elements, add edit links for related records |

🧪 = experimental, opt-in

## 🚀 Quick Start

Requires **TYPO3 13.4 – 14.x** and **PHP 8.2+** ([version matrix](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Installation/Index.html#version-matrix)).

**1. Install**

```bash
composer require xima/xima-typo3-frontend-edit
```

<sub>Alternatively via the [TER](https://extensions.typo3.org/extension/xima_typo3_frontend_edit).</sub>

**2. Include the site set**

```yaml
# config/sites/my-site/config.yaml
dependencies:
  - xima/xima-typo3-frontend-edit
```

**3. Open your site while logged into the backend**

The toolbar appears in the bottom-right corner, and hovering a content element reveals its edit button. That's it — no TypoScript, no template changes when you use `fluid_styled_content`.

> [!TIP]
> Nothing showing up? Custom templates need to expose a content element ID (`id="c123"`). The [FAQ](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/FAQ/Index.html) walks through the six usual causes.

## 📙 Documentation

[**Read the full documentation**](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Index.html) — installation, all configuration options, usage guide and developer reference.

| | |
|---|---|
| 🧭 [How it works](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/HowItWorks/Index.html) | What gets injected and why |
| ❓ [FAQ](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/FAQ/Index.html) | Troubleshooting |
| 🔀 [Migration](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Migration/Index.html) | Upgrading from 1.x to 2.x |
| ⚖️ [Comparison](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Delineation/Index.html) | How it differs from similar extensions |

> [!IMPORTANT]
> This is **not** a further development of [frontend_editing](https://extensions.typo3.org/extension/frontend_editing). It is an independent implementation with a different approach, closer in spirit to [feedit](https://extensions.typo3.org/extension/feedit). See the [comparison](https://docs.typo3.org/p/xima/xima-typo3-frontend-edit/main/en-us/Delineation/Index.html) for details.

## 🧑‍💻 Contributing

Please take a look at [Contributing](CONTRIBUTING.md).

## 💎 Credits

Thanks to [move:elevator](https://www.move-elevator.de/) and [XIMA](https://www.xima.de/) for supporting the development of this extension, and to [Violetta Digital Craft](https://www.violetta.ch/) for supporting the inline editing feature.

## ⭐ License

This project is licensed under [GNU General Public License 2.0 (or later)](LICENSE.md).
