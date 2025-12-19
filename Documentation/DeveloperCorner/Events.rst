..  include:: /Includes.rst.txt

..  _events:

=======================
PSR-14 Events
=======================

The extension provides two PSR-14 events to customize the Edit Menu and Toolbar.

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
