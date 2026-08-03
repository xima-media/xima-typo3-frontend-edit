..  include:: /Includes.rst.txt

..  _events:

=======================
PSR-14 Events
=======================

The extension provides three PSR-14 events to customize the Edit Menu and Toolbar.

FrontendEditDropdownModifyEvent
===============================

Use the :code:`FrontendEditDropdownModifyEvent` to modify the Edit Menu for content elements.
You can add, remove or modify buttons for specific content elements.

**Available methods:**

- ``getContentElement()`` - Returns the content element data array
- ``getMenuButton()`` - Returns the current menu button
- ``setMenuButton()`` - Sets the modified menu button
- ``getReturnUrl()`` - Returns the return URL for edit links

**Example:**

..  code-block:: php
    :caption: Classes/EventListener/ModifyEditMenuListener.php

    <?php

    declare(strict_types=1);

    namespace Vendor\Package\EventListener;

    use TYPO3\CMS\Backend\Routing\UriBuilder;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Imaging\IconFactory;
    use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
    use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
    use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

    #[AsEventListener(
        identifier: 'my-extension/modify-edit-menu',
    )]
    class ModifyEditMenuListener
    {
        public function __construct(
            protected readonly IconFactory $iconFactory,
            protected readonly UriBuilder $uriBuilder
        ) {}

        public function __invoke(FrontendEditDropdownModifyEvent $event): void
        {
            $contentElement = $event->getContentElement();
            $menuButton = $event->getMenuButton();

            // Add a custom button for a specific plugin
            if ($contentElement['CType'] === 'list' && $contentElement['list_type'] === 'news_pi1') {
                $menuButton->appendAfterChild(new Button(
                    'Edit news settings',
                    ButtonType::Link,
                    $this->uriBuilder->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit' => ['tt_content' => [$contentElement['uid'] => 'edit']],
                            'returnUrl' => $event->getReturnUrl(),
                        ],
                    )->__toString(),
                    $this->iconFactory->getIcon('content-news', 'small')
                ),
                'edit_page',
                'edit_news_settings'
                );
            }

            // Remove a button
            $menuButton->removeChild('div_action');

            $event->setMenuButton($menuButton);
        }
    }

FrontendEditPageDropdownModifyEvent
===================================

Use the :code:`FrontendEditPageDropdownModifyEvent` to modify the Toolbar menu for page-level actions.

**Available methods:**

- ``getPageId()`` - Returns the current page ID
- ``getLanguageUid()`` - Returns the current language UID
- ``getMenuButton()`` - Returns the current menu button
- ``setMenuButton()`` - Sets the modified menu button
- ``getReturnUrl()`` - Returns the return URL for edit links

**Example:**

..  code-block:: php
    :caption: Classes/EventListener/ModifyToolbarListener.php

    <?php

    declare(strict_types=1);

    namespace Vendor\Package\EventListener;

    use TYPO3\CMS\Backend\Routing\UriBuilder;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Imaging\IconFactory;
    use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
    use Xima\XimaTypo3FrontendEdit\Event\FrontendEditPageDropdownModifyEvent;
    use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

    #[AsEventListener(
        identifier: 'my-extension/modify-toolbar',
    )]
    class ModifyToolbarListener
    {
        public function __construct(
            protected readonly IconFactory $iconFactory,
            protected readonly UriBuilder $uriBuilder
        ) {}

        public function __invoke(FrontendEditPageDropdownModifyEvent $event): void
        {
            $menuButton = $event->getMenuButton();

            // Add a custom page action
            $menuButton->appendChild(new Button(
                'Clear page cache',
                ButtonType::Link,
                $this->uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        'cacheCmd' => $event->getPageId(),
                        'redirect' => $event->getReturnUrl(),
                    ],
                )->__toString(),
                $this->iconFactory->getIcon('actions-system-cache-clear', 'small')
            ),
            'clear_cache'
            );

            $event->setMenuButton($menuButton);
        }
    }

FrontendEditDataEnrichmentEvent
================================

Use the :code:`FrontendEditDataEnrichmentEvent` to attach structured, serializable
data to content elements - e.g. a status color, an assignee, a comment count -
which the frontend JavaScript can then render, for example via a
:code:`registerBadge()` call. Since element data is delivered via the
uncached :code:`editInformation` AJAX request, this is also the only cache-safe
transport for volatile third-party data, and it rides along on the existing
request without an extra roundtrip.

The event is dispatched **once per request**, with the full filtered content
element list - not once per element - so a listener backed by a database can
resolve its data in a single query instead of being invoked N times with no
way to batch.

**Available methods:**

- ``getContentElements()`` - Returns all content element rows for this request, keyed by uid (read-only)
- ``getContentElementUids()`` - Returns just the uids, for a batched lookup
- ``getPageId()`` / ``getLanguageUid()`` / ``getReturnUrl()`` - Request context
- ``addElementData(int $uid, string $namespace, array $data)`` - Attaches data for one element under your namespace; :code:`$data` must be serializable (scalar, null, or arrays thereof)
- ``getElementData(int $uid)`` - Returns the data attached so far for one element (namespace => data)

Data attached via ``addElementData()`` is merged into the JSON payload under
:code:`element['_ext'][$namespace]` - namespacing prevents key collisions
between listeners, and :code:`_ext` is reserved for this purpose (core
frontend-edit keys are never exposed under it). Namespaces must be lowercase
letters, digits and underscores, starting with a letter (e.g. your extension key).

**Example:**

..  code-block:: php
    :caption: Classes/EventListener/EnrichCommentDataListener.php

    <?php

    declare(strict_types=1);

    namespace Vendor\Package\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDataEnrichmentEvent;

    #[AsEventListener(
        identifier: 'my-extension/enrich-comment-data',
    )]
    class EnrichCommentDataListener
    {
        public function __construct(
            protected readonly CommentRepository $commentRepository,
        ) {}

        public function __invoke(FrontendEditDataEnrichmentEvent $event): void
        {
            // One query for all elements on this page instead of N.
            $counts = $this->commentRepository->countByContentElementUids(
                $event->getContentElementUids(),
            );

            foreach ($counts as $uid => $count) {
                $event->addElementData($uid, 'my_extension', [
                    'color' => $count > 0 ? '#ff9800' : '#9e9e9e',
                    'count' => $count,
                ]);
            }
        }
    }

On the frontend, render it via ``registerBadge()`` when the element is rendered:

..  code-block:: javascript

    document.addEventListener('xfe:element-rendered', (event) => {
        const { uid, payload } = event.detail;
        const data = payload.element._ext?.my_extension;
        if (!data) return;

        window.XimaFrontendEdit.registerBadge(uid, {
            html: `<span style="background:${data.color}">${data.count}</span>`,
        });
    });
