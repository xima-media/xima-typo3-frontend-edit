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

    * - ``plugin.tx_ximatypo3frontendedit.settings.defaultMenuStructure.*``
      - Removed — see :ref:`menu-structure-deprecation`

Example Migration
-----------------

**Before (TypoScript - version 1.x):**

..  code-block:: typoscript

    config.tx_ximatypo3frontendedit_enable = 1

    plugin.tx_ximatypo3frontendedit {
        settings {
            ignorePids = 1,2,3
            ignoreCTypes = html,div
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

..  _menu-structure-deprecation:

Breaking Change: Menu Structure Customization Removed
=====================================================

In version 2.x, the ``defaultMenuStructure`` configuration options have been removed.
This section explains why and how to migrate if you relied on this feature.

Why Was This Removed?
---------------------

The granular menu item configuration (``showEdit``, ``showHistory``, ``showInfo``, etc.)
was removed for the following reasons:

- **Low usage:** Analysis showed this feature was rarely used in practice
- **Complexity reduction:** Removing 8+ configuration options simplifies the extension
- **Maintainability:** Less configuration means fewer edge cases and easier testing
- **Consistency:** The dropdown menu now always shows all available actions

The ``frontendEdit.showContextMenu`` Site Setting still allows you to toggle between
the full dropdown menu and a simple edit button.

Migration Approaches
--------------------

Depending on your use case, choose one of the following approaches:

**Option A: Use the Context Menu Toggle (Recommended)**

If you previously disabled most menu items to show only the edit button,
use the new ``showContextMenu`` setting:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    frontendEdit:
      showContextMenu: false  # Shows only the edit button

**Option B: Use Event Listeners for Custom Menus**

For advanced customization, use the provided PSR-14 events to modify the menu:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ModifyFrontendEditDropdown.php

    <?php
    namespace MyVendor\MyExtension\EventListener;

    use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;

    final class ModifyFrontendEditDropdown
    {
        public function __invoke(FrontendEditDropdownModifyEvent $event): void
        {
            $menuButton = $event->getMenuButton();

            // Remove specific menu items
            $menuButton->removeChild('history');
            $menuButton->removeChild('info');

            // Or add custom items
            // $menuButton->appendChild($customButton, 'my_action');

            $event->setMenuButton($menuButton);
        }
    }

Register the listener in your extension's :file:`Configuration/Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      MyVendor\MyExtension\EventListener\ModifyFrontendEditDropdown:
        tags:
          - name: event.listener
            identifier: 'my-extension/modify-frontend-edit-dropdown'

**Option C: Override Templates**

For complete control over the menu rendering, override the Fluid templates:

..  code-block:: typoscript

    # In your site package TypoScript
    # Override the button partial
    [... template override configuration ...]

Migration Recipe
----------------

Follow these steps if you had custom ``defaultMenuStructure`` configuration:

1. **Identify your customizations**

   Review your old TypoScript configuration:

   ..  code-block:: typoscript

       plugin.tx_ximatypo3frontendedit.settings.defaultMenuStructure {
           history = 0
           info = 0
           move = 0
       }

2. **Choose your approach**

   - If you disabled *all* items except ``edit``: Use ``showContextMenu: false``
   - If you need specific items hidden: Implement an event listener
   - If you need complete control: Override templates

3. **Implement the solution**

   Apply the chosen approach from the options above.

4. **Remove old configuration**

   Delete all ``defaultMenuStructure`` TypoScript settings.

5. **Test thoroughly**

   - Verify the menu appears correctly in the frontend
   - Check that edit links work as expected
   - Test with different user permissions

Troubleshooting
---------------

**Menu shows all items when I want fewer**

Use an event listener to remove unwanted items. The ``showContextMenu: false``
setting only toggles between "full menu" and "edit button only".

**Event listener not being called**

- Ensure your Services.yaml is properly configured
- Clear all caches including the DI container cache
- Verify the event class name is correct

**Custom menu items not appearing**

When adding items via event listener:

- Use ``appendChild()`` with a unique identifier
- Ensure the Button component is properly configured
- Check that the item type (Link, Divider, etc.) is correct

**Need help?**

If you have complex menu customization needs that cannot be addressed with the
approaches above, please open an issue on the extension's GitHub repository.
