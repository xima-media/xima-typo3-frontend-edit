..  include:: /Includes.rst.txt

..  _typoscript:

=======================
TypoScript
=======================

Adjust the typoscript constants to restrict the usage of the frontend edit:

..  code-block:: typoscript
    :caption: setup.typoscript

    plugin.tx_ximatypo3frontendedit {
        settings {
            # Ignore content elements by several criteria
            ignorePids =
            ignoreCTypes =
            ignoreListTypes =
            ignoreUids =

            # Adjust the default menu structure by setting an entry to "0" to disable it
            defaultMenuStructure {
                div_info =
                header =
                div_edit =
                edit =
                edit_page =
                div_action =
                hide =
                move =
                info =
                history =
            }
        }
    }
