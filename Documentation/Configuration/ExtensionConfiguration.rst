..  include:: /Includes.rst.txt

..  _extension-configuration:

=======================
Extension configuration
=======================

#. Go to :guilabel:`Admin Tools > Settings > Extension Configuration`
#. Choose :guilabel:`xima_typo3_frontend_edit`

The extension currently provides the following configuration options:

..  _extconf-enableSaveAndCloseButton:

..  confval:: enableSaveAndCloseButton
    :type: boolean
    :Default: 1

    Enable the save and close button within edit forms

..  _extconf-forceReturnUrlGeneration:

..  confval:: recordEditHeaderInfo
    :type: boolean
    :Default: 0

    Enable this option to ignore the referer header for the return url and force the url generation

..  _extconf-linkTargetBlank:

..  confval:: linkTargetBlank
    :type: boolean
    :Default: 0

    Enable the target blank option for all links to open in a new tab
