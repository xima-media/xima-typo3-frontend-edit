..  include:: /Includes.rst.txt

..  _custom-styling:

=======================
Custom Styling
=======================

The dropdown was styled to not disturb the frontend layout. You can easily adjust the styling by providing an additional css or js file within your :code:`ext_localconf.php` which will be loaded together with the frontend edit resources:

..  code-block:: php
    :caption: ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['xima_typo3_frontend_edit']['registerAdditionalFrontendResources'][] = 'EXT:custom_extension/Resources/Public/Css/Custom.css';
