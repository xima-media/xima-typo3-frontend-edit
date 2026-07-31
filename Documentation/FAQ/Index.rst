..  include:: /Includes.rst.txt

..  _faq:

===
FAQ
===

Answers to the problems reported most often. If none of these help, please
`open an issue on GitHub <https://github.com/xima-media/xima-typo3-frontend-edit/issues>`__.

..  _faq-no-menu:

Nothing appears in the frontend
===============================

..  accordion::
    :name: faqNoMenu

    ..  accordion-item:: Why is the Edit Menu not displayed at all?
        :name: faqNoMenuChecklist
        :header-level: 3
        :show:

        Work through this checklist — the cause is almost always one of these
        six points.

        Backend user session
            Are you logged into the TYPO3 backend? Without an active backend
            session nothing is injected into the frontend.

        Backend user permissions
            Does your user have permission to edit both the page and the
            content elements on it?

        Site set
            Is the Frontend Edit site set included in your site configuration?
            See :ref:`installation`.

        Frontend editing switched off
            Check whether :confval:`frontendEdit.enabled` is ``true``, whether
            the page is excluded via :confval:`frontendEdit.filter.ignorePids`,
            and whether the toggle in the :ref:`toolbar <toolbar>` is switched
            off for your user. It can also be disabled administratively via
            :ref:`user-tsconfig`.

        Content element IDs
            The rendered HTML must expose a "c-id" per element, e.g.
            ``id="c908"``. See :ref:`how-it-works`.

        Content element on the current page
            Only elements belonging to the current page are editable. Inherited
            content (e.g. a shared footer) cannot be edited from the inheriting
            page.

        ..  tip::

            Check the network tab for the initial AJAX call to
            :code:`/typo3/ajax/xima-frontend-edit/edit-information`. Its
            response lists every element the extension considers editable.
            Enabling :ref:`Frontend Debug Mode <extconf-frontendDebugMode>`
            adds detailed console logging.

    ..  accordion-item:: The edit button is missing on a different (sub)domain
        :name: faqSubdomain
        :header-level: 3

        Frontend editing needs an active backend user session. On a different
        (sub)domain the session cookie is only valid for the backend domain and
        is therefore not available to the frontend.

        Two ways out:

        *   Configure a broader
            `cookieDomain <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Configuration/Typo3ConfVars/SYS.html#confval-globals-typo3-conf-vars-sys-cookiedomain>`__
            so the session cookie is shared between the domains.
        *   Use the
            `multisite_belogin <https://extensions.typo3.org/extension/multisite_belogin>`__
            extension, which provides backend login across multiple domains
            without a shared cookie domain.

    ..  accordion-item:: The edit button is missing on EXT:container or EXT:dce elements
        :name: faqThirdPartyCe
        :header-level: 3

        Neither `container <https://extensions.typo3.org/extension/container/>`__
        nor `DCE <https://extensions.typo3.org/extension/dce>`__ render the
        required content element ID in their default templates — you have to add
        it.

        :ref:`template-requirements` has a ready-to-copy snippet for both,
        including a ``fluid_styled_content``-based alternative for containers.

        ..  note::
            Styling problems may occur with nested content elements.

    ..  accordion-item:: The menu is missing on inherited content (e.g. a shared footer)
        :name: faqInheritedContent
        :header-level: 3

        Only content elements belonging to the **current page** are editable.
        Content pulled in from another page cannot be edited from the inheriting
        page — the record simply does not live there.

        Use the :ref:`toolbar <toolbar>` to navigate to the page that owns the
        record and edit it there.

..  _faq-edit-form:

Problems with the edit form
===========================

..  accordion::
    :name: faqEditForm

    ..  accordion-item:: After saving I end up on the wrong page (e.g. the root page)
        :name: faqReturnUrl
        :header-level: 3

        This is usually caused by a strict referer header. If the return URL
        cannot be determined from the request, force it to be generated from
        page ID and language via the
        :ref:`Return URL generation <extconf-forceReturnUrlGeneration>`
        extension setting.

    ..  accordion-item:: I cannot change the language inside a content element
        :name: faqLanguageSwitch
        :header-level: 3

        This is a TYPO3 backend limitation: the reduced edit form frame does not
        include the language switch.

        Use the :ref:`Redirect <extconf-useRedirect>` extension setting to open
        the edit form in the full TYPO3 backend, which does provide the language
        switch.

        ..  note::

            In connected mode the edit link already targets the *translated*
            record, so a manual language switch is usually unnecessary. See
            :ref:`languages`.
