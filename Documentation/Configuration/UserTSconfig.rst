..  include:: /Includes.rst.txt

..  _user-tsconfig:

============
UserTSconfig
============

..  versionadded:: 2.1.0

You can disable the frontend editing feature for specific backend users or
backend user groups via UserTSconfig.

When disabled via UserTSconfig, the entire frontend editing feature is hidden —
including the sticky toolbar. The user cannot re-enable it via the toggle button.

This is useful for restricting frontend editing to certain editor groups while
keeping other backend users unaffected.

..  note::
    This setting is different from the per-user toggle in the sticky toolbar.
    The toggle allows users to temporarily hide the editing UI themselves.
    The UserTSconfig setting is an administrative control that completely
    prevents access to the feature.

Disable frontend editing
========================

..  confval:: tx_ximatypo3frontendedit.disabled

    :type: bool
    :Default: false

    Set to ``1`` to disable frontend editing for the affected backend users.

    ..  code-block:: typoscript

        tx_ximatypo3frontendedit.disabled = 1

By default, frontend editing is **enabled** for all backend users.

Examples
========

Disable for a specific backend user group
------------------------------------------

In the **TSconfig** field of a backend user group record:

..  code-block:: typoscript

    tx_ximatypo3frontendedit.disabled = 1

All users in this group will no longer see the frontend editing UI.

Disable for a specific backend user
------------------------------------

In the **TSconfig** field of a backend user record:

..  code-block:: typoscript

    tx_ximatypo3frontendedit.disabled = 1

Disable globally via Page TSconfig
-----------------------------------

You can also set this in Page TSconfig (e.g. via
:file:`Configuration/page.tsconfig`), which will then apply to all backend
users on the affected pages. However, **UserTSconfig is recommended** as it
targets users directly regardless of which page they visit.
