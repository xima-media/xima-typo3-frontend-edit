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
            ``id="c908"`` - or use the ``data-frontend-edit`` attribute instead.
            See :ref:`how-it-works` and :ref:`Setup requirements & limits
            <setup-requirements-and-limits>` (headless/SPA frontends are an
            explicit non-goal, for the same reason).

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

        ..  warning::

            A broader ``cookieDomain`` shares the backend session cookie with
            **every** matching subdomain of that deployment — restrict it to
            trusted subdomains served over HTTPS. It cannot bridge unrelated
            domains (a session is tied to a single TYPO3 instance); use
            ``multisite_belogin`` where a shared cookie is unsuitable.

        A cross-domain setup can also cause a :code:`returnUrl` to be
        rejected with an HTTP 400 error instead of silently redirecting to
        the root page: the extension only accepts a :code:`returnUrl` whose
        host matches the current request or one of the site's configured
        base URLs (including per-language bases). A rejected return url is a
        sign to check your site configuration's base URLs rather than a bug
        — see :ref:`Setup requirements & limits
        <setup-requirements-and-limits>` for the full picture.

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

        Alternatively, add the ``data-frontend-edit`` attribute instead (see
        :ref:`how-it-works`) - it avoids the "c-id" naming collision risk
        entirely and needs no sibling resolution:

        ..  code-block:: html
            :caption: DCE Template

            {namespace xfe=Xima\XimaTypo3FrontendEdit\ViewHelpers}

            <div class="dce"<xfe:editable uid="{contentObject.uid}" />>
                Your template goes here...
            </div>

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

        See :ref:`Setup requirements & limits <setup-requirements-and-limits>`
        for the full picture of multi-domain setups, including ``SameSite``
        and ``returnUrl`` behavior.

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
