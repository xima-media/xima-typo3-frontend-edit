..  include:: /Includes.rst.txt

..  _site-settings:

=============
Site Settings
=============

Since version 2.0, all configuration options are managed via TYPO3 Site Settings.
This provides site-specific configuration and better caching behavior.

Configuration is done in your site's :file:`config/sites/<site-identifier>/settings.yaml` file
or via the TYPO3 backend in :guilabel:`Site Management > Sites > Edit site > Settings`.

Available Settings
==================

Enable/Disable
--------------

..  confval:: frontendEdit.enabled

    :type: bool
    :Default: true

    Enable or disable the frontend editing functionality for this site.

    ..  code-block:: yaml

        frontendEdit:
          enabled: true

Filter Settings
---------------

..  confval:: frontendEdit.filter.ignorePids

    :type: string
    :Default: ''

    Comma-separated list of page IDs (and their subpages) where frontend editing should be disabled.

    ..  code-block:: yaml

        frontendEdit:
          filter:
            ignorePids: '1,2,3'

..  confval:: frontendEdit.filter.ignoreCTypes

    :type: string
    :Default: ''

    Comma-separated list of content types (CType) to exclude from frontend editing.

    ..  code-block:: yaml

        frontendEdit:
          filter:
            ignoreCTypes: 'html,div'

..  confval:: frontendEdit.filter.ignoreListTypes

    :type: string
    :Default: ''

    Comma-separated list of plugin types (list_type) to exclude from frontend editing.

    ..  code-block:: yaml

        frontendEdit:
          filter:
            ignoreListTypes: 'news_pi1'

..  confval:: frontendEdit.filter.ignoreUids

    :type: string
    :Default: ''

    Comma-separated list of specific content element UIDs to exclude from frontend editing.

    ..  code-block:: yaml

        frontendEdit:
          filter:
            ignoreUids: '100,200,300'

Menu Structure
--------------

You can customize which menu items are shown in the dropdown menu.

..  confval:: frontendEdit.menu.showDivInfo

    :type: bool
    :Default: true

    Show the info section divider in the dropdown menu.

..  confval:: frontendEdit.menu.showHeader

    :type: bool
    :Default: true

    Show the header with content element info in the dropdown menu.

..  confval:: frontendEdit.menu.showDivEdit

    :type: bool
    :Default: true

    Show the edit section divider in the dropdown menu.

..  confval:: frontendEdit.menu.showEdit

    :type: bool
    :Default: true

    Show the edit content element button in the dropdown menu.

..  confval:: frontendEdit.menu.showEditPage

    :type: bool
    :Default: true

    Show the edit page button in the dropdown menu.

..  confval:: frontendEdit.menu.showDivAction

    :type: bool
    :Default: true

    Show the action section divider in the dropdown menu.

..  confval:: frontendEdit.menu.showHide

    :type: bool
    :Default: true

    Show the hide/unhide content element button in the dropdown menu.

..  confval:: frontendEdit.menu.showMove

    :type: bool
    :Default: true

    Show the move content element button in the dropdown menu.

..  confval:: frontendEdit.menu.showInfo

    :type: bool
    :Default: true

    Show the info button in the dropdown menu.

..  confval:: frontendEdit.menu.showHistory

    :type: bool
    :Default: true

    Show the history button in the dropdown menu.

Example Configuration
=====================

Full example with all available options:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    frontendEdit:
      enabled: true
      filter:
        ignorePids: '1,2,3'
        ignoreCTypes: 'html,div'
        ignoreListTypes: ''
        ignoreUids: ''
      menu:
        showDivInfo: true
        showHeader: true
        showDivEdit: true
        showEdit: true
        showEditPage: true
        showDivAction: true
        showHide: true
        showMove: true
        showInfo: true
        showHistory: true

Simple Mode Example
-------------------

To show only the edit button (simple mode), disable all other menu items:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    frontendEdit:
      enabled: true
      menu:
        showDivInfo: false
        showHeader: false
        showDivEdit: false
        showEdit: true
        showEditPage: false
        showDivAction: false
        showHide: false
        showMove: false
        showInfo: false
        showHistory: false
