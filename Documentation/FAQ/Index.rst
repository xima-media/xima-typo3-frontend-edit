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

    ..  accordion-item:: The edit button is missing on DCE content elements
        :name: faqDce
        :header-level: 3

        `DCE <https://extensions.typo3.org/extension/dce>`__ elements do not
        provide the required "c-id" in their default templates. Customize the
        `DCE template <https://docs.typo3.org/p/t3/dce/main/en-us/UsersManual/Template.html>`__
        to include it:

        ..  code-block:: html
            :caption: DCE template

            <div class="dce" id="c{contentObject.uid}">
                Your template goes here...
            </div>

        ..  note::
            Styling problems may occur with nested content elements.

    ..  accordion-item:: The edit button is missing on EXT:container elements
        :name: faqContainer
        :header-level: 3

        `container <https://extensions.typo3.org/extension/container/>`__
        elements do not provide the required "c-id" in their default templates.
        Customize the
        `container template <https://github.com/b13/container?tab=readme-ov-file#template>`__:

        ..  code-block:: html
            :caption: Container template

            <div id="c{data.uid}">
               <f:for each="{children_200}" as="record">
                   {record.header} <br>
                   <f:format.raw>
                       {record.renderedContent}
                   </f:format.raw>
               </f:for>
            </div>

        Alternatively, use
        `fluid_styled_content <https://docs.typo3.org/c/typo3/cms-fluid-styled-content/main/en-us/Introduction/Index.html>`__
        in the template:

        ..  code-block:: html
            :caption: Container template using fluid_styled_content

            <f:layout name="Default" />

            <f:section name="Main">
              <f:for each="{children_200}" as="record">
                  {record.header} <br>
                  <f:format.raw>
                      {record.renderedContent}
                  </f:format.raw>
              </f:for>
            </f:section>

        ..  note::
            Styling problems may occur with nested content elements.

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
