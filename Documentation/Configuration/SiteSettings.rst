..  include:: /Includes.rst.txt

..  _site-settings:

=============
Site Settings
=============

All configuration options are managed via TYPO3 Site Settings.
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

Appearance Settings
-------------------

..  confval:: frontendEdit.colorScheme

    :type: string
    :Default: 'auto'

    Choose the color scheme for the frontend editing UI. Options: auto (follows system preference), light, dark.

    ..  code-block:: yaml

        frontendEdit:
          colorScheme: 'auto'

..  confval:: frontendEdit.showContextMenu

    :type: bool
    :Default: true

    Show the Edit Menu with additional actions (edit page, hide, move, history). Disable to show only the edit button.

    ..  code-block:: yaml

        frontendEdit:
          showContextMenu: true

..  confval:: frontendEdit.showStickyToolbar

    :type: bool
    :Default: true

    Show the Toolbar with page editing options and toggle functionality.

    ..  code-block:: yaml

        frontendEdit:
          showStickyToolbar: true

..  confval:: frontendEdit.toolbarPosition

    :type: string
    :Default: 'bottom-right'

    Choose the position for the Toolbar.

    Available options:

    - ``top-left``, ``top-center``, ``top-right``
    - ``bottom-left``, ``bottom-center``, ``bottom-right``
    - ``left-top``, ``left-center``, ``left-bottom``
    - ``right-top``, ``right-center``, ``right-bottom``

    ..  code-block:: yaml

        frontendEdit:
          toolbarPosition: 'bottom-right'

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

Example Configuration
=====================

Full example with all available options:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    frontendEdit:
      enabled: true
      colorScheme: 'auto'
      showContextMenu: true
      showStickyToolbar: true
      toolbarPosition: 'bottom-right'
      filter:
        ignorePids: '1,2,3'
        ignoreCTypes: 'html,div'
        ignoreListTypes: ''
        ignoreUids: ''
