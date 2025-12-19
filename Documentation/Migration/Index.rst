..  include:: /Includes.rst.txt

..  _migration:

===============
Migration Guide
===============

This guide helps you migrate between major versions of the extension.

Version 2.0
===========

Migration from version 1.x to version 2.x.

Breaking Changes
----------------

.. list-table::
   :header-rows: 1
   :widths: 30 70

   * - Area
     - Change

   * - Configuration
     - TypoScript → Site Settings (see :ref:`site-settings`)

   * - TYPO3
     - v14 added, v12 removed

   * - PHP
     - Minimum 8.2, added 8.5 support

   * - Menu Structure
     - ``defaultMenuStructure`` options removed

Configuration Mapping
---------------------

.. list-table::
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

Quick Migration
---------------

1. **Update** the extension to version 2.x

2. **Remove** TypoScript configuration for this extension

3. **Add** Site Settings in :file:`config/sites/<identifier>/settings.yaml`:

   .. code-block:: yaml

      frontendEdit:
        enabled: true
        filter:
          ignorePids: '1,2,3'
          ignoreCTypes: 'html,div'

4. **Clear** all TYPO3 caches

Menu Customization
------------------

The ``defaultMenuStructure`` configuration has been removed.

**Simple edit button only:**

.. code-block:: yaml

   frontendEdit:
     showContextMenu: false

**Custom menu items:** Use PSR-14 events (see :ref:`events`)

.. code-block:: php

   use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;

   #[AsEventListener]
   final class ModifyMenu
   {
       public function __invoke(FrontendEditDropdownModifyEvent $event): void
       {
           $event->getMenuButton()->removeChild('history');
       }
   }

Need Help?
==========

- Check the :ref:`faq` for common issues
- Open an issue on `GitHub <https://github.com/xima-media/xima-typo3-frontend-edit/issues>`__
