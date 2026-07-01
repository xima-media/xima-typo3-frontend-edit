<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\{ButtonBar, ModifyButtonBarEvent};
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\EventListener\ModifyButtonBarEventListener;

/**
 * ModifyButtonBarEventListenerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ModifyButtonBarEventListener::class)]
final class ModifyButtonBarEventListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['TYPO3_REQUEST'], $GLOBALS['LANG']);
    }

    #[Test]
    public function invokeReturnsEarlyWhenSaveAndCloseButtonDisabled(): void
    {
        $listener = $this->createListener(['enableSaveAndCloseButton' => false]);
        $event = $this->createEvent([]);

        $listener($event);

        self::assertSame([], $event->getButtons());
    }

    #[Test]
    public function invokeReturnsEarlyWhenConfigurationKeyMissing(): void
    {
        $listener = $this->createListener([]);
        $event = $this->createEvent([]);

        $listener($event);

        self::assertSame([], $event->getButtons());
    }

    #[Test]
    public function invokeReturnsEarlyWhenRequestIsNotServerRequest(): void
    {
        $listener = $this->createListener(['enableSaveAndCloseButton' => true]);
        $GLOBALS['TYPO3_REQUEST'] = null;
        $event = $this->createEvent([]);

        $listener($event);

        self::assertSame([], $event->getButtons());
    }

    #[Test]
    public function invokeReturnsEarlyWhenQueryParameterMissing(): void
    {
        $listener = $this->createListener(['enableSaveAndCloseButton' => true]);
        $this->registerRequest([]);
        $event = $this->createEvent([]);

        $listener($event);

        self::assertSame([], $event->getButtons());
    }

    #[Test]
    public function invokeDoesNotAddButtonWhenSaveButtonMissing(): void
    {
        $listener = $this->createListener(['enableSaveAndCloseButton' => true]);
        $this->registerRequest(['tx_ximatypo3frontendedit' => '1']);
        $event = $this->createEvent([]);

        $listener($event);

        self::assertArrayNotHasKey(ButtonBar::BUTTON_POSITION_LEFT, $event->getButtons());
    }

    #[Test]
    public function invokeAddsSaveAndCloseButtonWhenSaveButtonPresent(): void
    {
        $listener = $this->createListener(['enableSaveAndCloseButton' => true]);
        $this->registerRequest(['tx_ximatypo3frontendedit' => '1']);
        $this->registerServices();

        $saveButton = (new InputButton())->setName('_savedok')->setValue('1')->setForm('EditDocumentController');
        $buttons = [ButtonBar::BUTTON_POSITION_LEFT => [2 => [0 => $saveButton]]];
        $buttonBar = $this->createMock(ButtonBar::class);
        $buttonBar->method('makeInputButton')->willReturn(new InputButton());
        $event = new ModifyButtonBarEvent($buttons, $buttonBar);

        $listener($event);

        $resultButtons = $event->getButtons()[ButtonBar::BUTTON_POSITION_LEFT][2];
        self::assertCount(2, $resultButtons);
        self::assertSame('_saveandclosedok', $resultButtons[1]->getName());
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function createListener(array $configuration): ModifyButtonBarEventListener
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn($configuration);

        return new ModifyButtonBarEventListener($extensionConfiguration);
    }

    /**
     * @param array<string, mixed> $buttons
     */
    private function createEvent(array $buttons): ModifyButtonBarEvent
    {
        return new ModifyButtonBarEvent($buttons, $this->createMock(ButtonBar::class));
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function registerRequest(array $queryParams): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    private function registerServices(): void
    {
        $iconFactory = $this->createMock(IconFactory::class);
        $iconFactory->method('getIcon')->willReturn($this->createMock(Icon::class));
        GeneralUtility::addInstance(IconFactory::class, $iconFactory);
        GeneralUtility::setSingletonInstance(PageRenderer::class, $this->createMock(PageRenderer::class));

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturn('Save and close');
        $GLOBALS['LANG'] = $languageService;
    }
}
