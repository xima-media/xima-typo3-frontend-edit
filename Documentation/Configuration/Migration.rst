..  include:: /Includes.rst.txt

..  _migration:

===============
Migration Guide
===============

This guide helps you migrate from version 1.x to version 2.x of the extension.

Version 2.0 Changes
===================

Breaking Changes
----------------

Configuration System
^^^^^^^^^^^^^^^^^^^^

The configuration has been completely migrated from **TypoScript** to **TYPO3 Site Settings**.

**Why?**

- Better caching behavior (no TypoScript parsing on each request)
- Site-specific configuration out of the box
- Modern TYPO3 best practice (v13+)
- Simpler YAML-based configuration

**What to do:**

1. Remove any TypoScript configuration for this extension
2. Configure settings via Site Settings (see :ref:`site-settings`)

TYPO3 Version Support
^^^^^^^^^^^^^^^^^^^^^

- **Added:** TYPO3 v14 support
- **Removed:** TYPO3 v12 support

PHP Version Support
^^^^^^^^^^^^^^^^^^^

- **Minimum:** PHP 8.2
- **Added:** PHP 8.5 support

Configuration Migration
=======================

Migrate your existing TypoScript configuration to Site Settings:

..  list-table:: Configuration Mapping
    :header-rows: 1
    :widths: 50 50

    * - TypoScript (1.x)
      - Site Settings (2.x)

    * - ``config.tx_ximatypo3frontendedit_enable``
      - ``frontendEdit.enabled``

    * - ``plugin.tx_ximatypo3frontendedit.settings.ignorePids``
      - ``frontendEdit.filter.ignorePids``

    * - ``plugin.tx_ximatypo3frontendedit.settings.ignoreCTypes``
      - ``frontendEdit.filter.ignoreCTypes``

    * - ``plugin.tx_ximatypo3frontendedit.settings.ignoreListTypes``
      - ``frontendEdit.filter.ignoreListTypes``

    * - ``plugin.tx_ximatypo3frontendedit.settings.ignoreUids``
      - ``frontendEdit.filter.ignoreUids``

    * - ``plugin.tx_ximatypo3frontendedit.settings.defaultMenuStructure.div_info``
      - ``frontendEdit.menu.showDivInfo``

    * - ``plugin.tx_ximatypo3frontendedit.settings.defaultMenuStructure.header``
      - ``frontendEdit.menu.showHeader``

    * - ``plugin.tx_ximatypo3frontendedit.settings.defaultMenuStructure.edit``
      - ``frontendEdit.menu.showEdit``

    * - ``plugin.tx_ximatypo3frontendedit.settings.defaultMenuStructure.history``
      - ``frontendEdit.menu.showHistory``

    * - (all other menu structure settings)
      - ``frontendEdit.menu.show*``

Example Migration
-----------------

**Before (TypoScript - version 1.x):**

..  code-block:: typoscript

    config.tx_ximatypo3frontendedit_enable = 1

    plugin.tx_ximatypo3frontendedit {
        settings {
            ignorePids = 1,2,3
            ignoreCTypes = html,div
            defaultMenuStructure {
                history = 0
                info = 0
            }
        }
    }

**After (Site Settings - version 2.x):**

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    frontendEdit:
      enabled: true
      filter:
        ignorePids: '1,2,3'
        ignoreCTypes: 'html,div'
      menu:
        showHistory: false
        showInfo: false

Step-by-Step Migration
======================

1. **Update the extension** to version 2.x

2. **Remove TypoScript configuration**

   Delete or comment out any TypoScript configuration related to this extension.

3. **Add Site Settings**

   Either edit your site's :file:`config/sites/<identifier>/settings.yaml` directly,
   or use the TYPO3 backend:

   :guilabel:`Site Management > Sites > Edit site > Settings`

4. **Clear caches**

   Clear all TYPO3 caches after the migration.

5. **Test**

   Verify that frontend editing works as expected on your site.
