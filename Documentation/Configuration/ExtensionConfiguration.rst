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

..  _extconf-useRedirect:

..  confval:: Redirect
    :type: boolean
    :Default: 0

    Use the redirect option to redirect the edit links, so the full TYPO3 backend is loaded instead of only the edit form

    ..  warning::
        This option is useful if you want to use the full TYPO3 backend, e.g. language switch or modal popups for for inline group type.
        But keep in mind, that with this option the return to the frontend using the "close" button will not work anymore.
