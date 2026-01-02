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

..  confval:: frontendEdit.enableOutline

    :type: bool
    :Default: true

    Show an outline around content elements when hovering over them.

    ..  code-block:: yaml

        frontendEdit:
          enableOutline: true

..  confval:: frontendEdit.enableScrollToElement

    :type: bool
    :Default: true

    Automatically scroll to the edited content element after saving and returning to the frontend.

    ..  code-block:: yaml

        frontendEdit:
          enableScrollToElement: true

..  confval:: frontendEdit.enableFlashMessages

    :type: bool
    :Default: true

    Show TYPO3 flash messages (e.g., save confirmations) as toast notifications in the frontend
    after returning from the backend. When you use "Save & Close" in the backend, success or
    error messages will be displayed as notifications in the frontend.

    ..  figure:: /Images/flash-message.jpg
        :alt: Flash message notification in frontend
        :class: with-shadow

        Flash message notification after saving a content element

    ..  code-block:: yaml

        frontendEdit:
          enableFlashMessages: true

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

..  confval:: frontendEdit.filter.ignoreDoktypes

    :type: string
    :Default: ''

    Comma-separated list of page types (doktype) where frontend editing should be disabled.
    Common doktypes: 1 (Standard), 3 (External URL), 4 (Shortcut), 6 (Backend User Section), 7 (Mount Point), 199 (Menu Separator), 254 (Folder), 255 (Recycler).

    ..  code-block:: yaml

        frontendEdit:
          filter:
            ignoreDoktypes: '4,199,254'

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
      enableOutline: true
      enableScrollToElement: true
      enableFlashMessages: true
      filter:
        ignorePids: '1,2,3'
        ignoreDoktypes: '4,199,254'
        ignoreCTypes: 'html,div'
        ignoreListTypes: ''
        ignoreUids: ''
