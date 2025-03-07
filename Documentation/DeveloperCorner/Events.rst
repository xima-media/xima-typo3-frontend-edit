..  include:: /Includes.rst.txt

..  _events:

=======================
PSR-14 Events
=======================

Use the :code:`FrontendEditDropdownModifyEvent` to modify the edit menu to your needs. You can add, remove or modify buttons for specific content elements. See the example below:

..  code-block:: php
    :caption: Classes/EventListener/ModifyFrontendEditListener.php

    <?php

    declare(strict_types=1);

    namespace Vendor\Package\EventListener;

    use TYPO3\CMS\Backend\Routing\UriBuilder;
    use TYPO3\CMS\Core\Imaging\IconFactory;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
    use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
    use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

    class ModifyFrontendEditListener
    {
        public function __construct(protected readonly IconFactory $iconFactory, protected readonly UriBuilder $uriBuilder)
        {
        }

        public function __invoke(FrontendEditDropdownModifyEvent $event): void
        {
            $contentElement = $event->getContentElement();
            $menuButton = $event->getMenuButton();

            // Example 1
            // Append a custom button (after the existing edit_page button) for your plugin to e.g. edit the referenced entity
            if ($contentElement['CType'] === 'list' && $contentElement['list_type'] === 'custom_plugin_name') {
                $menuButton->appendAfterChild(new Button(
                    'Edit entity',
                    ButtonType::Link,
                    $this->uriBuilder->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit' => [
                                'custom_entity' => [
                                    $contentElement['custom_entity_uid'] => 'edit',
                                ],
                            ],
                            'returnUrl' => $event->getReturnUrl(),
                        ],
                    )->__toString(),
                    $this->iconFactory->getIcon('content-idea', 'small')
                ),
                'edit_page',
                'edit_custom_entity'
                );
            }

            // Example 2
            // Remove existing buttons
            $menuButton->removeChild('div_action');

            $event->setMenuButton($menuButton);
        }
    }

Don't forget to register your event listener via PHP attributes (TYPO3 >= 13):


..  code-block:: php
    :caption: Classes/EventListener/ModifyFrontendEditListener.php

    #[AsEventListener(
        identifier: 'ext-some-extension/modify-frontend-edit-listener',
    )]

or register the event listener in your :code:`Services.yaml`:

..  code-block:: yaml
    :caption: Configuration/Services.yaml

    services:
    Vendor\Package\EventListener\ModifyFrontendEditListener:
        tags:
            - name: event.listener
                identifier: 'ext-some-extension/modify-frontend-edit-listener'
