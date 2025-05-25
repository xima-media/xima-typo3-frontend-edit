..  include:: /Includes.rst.txt

..  _extension-configuration:

=======================
Extension configuration
=======================

#. Go to :guilabel:`Admin Tools > Settings > Extension Configuration`
#. Choose :guilabel:`xima_typo3_frontend_edit`

The extension currently provides the following configuration options:

..  _extconf-enableSaveAndCloseButton:

..  confval:: Save and Close
    :type: boolean
    :Default: 1

    Enable this option to render a save and close button in the header of edit forms

..  _extconf-forceReturnUrlGeneration:

..  confval:: Return URL generation
    :type: boolean
    :Default: 0

    Enable this option to ignore the referer header for the return url and force the url generation

..  _extconf-linkTargetBlank:

..  confval:: Target blank
    :type: boolean
    :Default: 0

    Enable the target blank option for all dropdown links to open in a new tab

..  _extconf-simpleMode:

..  confval:: Simple mode
    :type: boolean
    :Default: 0

    This mode will disable the menu dropdown and use the edit icon button directly as edit link instead
