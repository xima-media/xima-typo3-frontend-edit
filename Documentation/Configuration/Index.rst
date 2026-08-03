..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

After the site set is included, the extension works without further configuration — everything below is optional.

There are three configuration layers, each answering a different question:

..  list-table::
    :header-rows: 1
    :widths: 25 30 45

    *   -   Layer
        -   Scope
        -   Use it for
    *   -   :ref:`Site Settings <site-settings>`
        -   Per site
        -   Almost everything: switching the feature on or off, appearance,
            toolbar position, and filters for pages, doktypes, CTypes and UIDs.
    *   -   :ref:`UserTSconfig <user-tsconfig>`
        -   Per backend user or group
        -   Withholding frontend editing from certain editors entirely.
    *   -   :ref:`Extension configuration <extension-configuration>`
        -   Global, per installation
        -   How edit links behave: return URL handling, target blank, redirect
            into the full backend, and debug logging.

..  tip::

    Looking for a specific option? All site settings are listed as
    :ref:`confvals <site-settings>` and can be searched from the sidebar.

..  toctree::
    :maxdepth: 3

    SiteSettings
    UserTSconfig
    ExtensionConfiguration
    SetupRequirementsAndLimits
