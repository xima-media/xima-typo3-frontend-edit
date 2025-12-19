<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Service\Menu;

use Doctrine\DBAL\Exception;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool};
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditPageDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * PageMenuGenerator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class PageMenuGenerator extends AbstractMenuGenerator
{
    public function __construct(
        private readonly PageButtonBuilder $pageButtonBuilder,
        private readonly EventDispatcher $eventDispatcher,
        ExtensionConfiguration $extensionConfiguration,
        private readonly ConnectionPool $connectionPool,
    ) {
        parent::__construct($extensionConfiguration);
    }

    /**
     * Generate the page dropdown menu data for the sticky toolbar.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function getDropdown(ServerRequestInterface $request): array
    {
        $routing = $request->getAttribute('routing');
        $pid = $routing instanceof PageArguments ? $routing->getPageId() : 0;

        if (0 === $pid) {
            return [];
        }

        $language = $request->getAttribute('language');
        $languageUid = $language instanceof SiteLanguage ? $language->getLanguageId() : 0;
        $returnUrl = (string) $request->getUri();

        $menuButton = new Button(
            'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:page_menu',
            ButtonType::Menu,
        );

        $pageRecord = $this->getPageRecord($pid);
        if (null !== $pageRecord) {
            $this->pageButtonBuilder->addInfoSection($menuButton, $pageRecord);
        }

        $this->pageButtonBuilder->addEditSection($menuButton, $pid, $languageUid, $returnUrl);
        $this->pageButtonBuilder->addActionSection($menuButton, $pid, $returnUrl);

        /** @var FrontendEditPageDropdownModifyEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new FrontendEditPageDropdownModifyEvent($pid, $languageUid, $menuButton, $returnUrl),
        );

        // Use potentially modified button from event listeners
        $rendered = $event->getMenuButton()->render();
        $rendered['targetBlank'] = $this->isLinkTargetBlank();

        return $rendered;
    }

    /**
     * Get page record directly from database.
     *
     * @return array<string, mixed>|null
     *
     * @throws Exception
     */
    private function getPageRecord(int $pid): ?array
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('pages');

        $result = $queryBuilder
            ->select('uid', 'title', 'doktype')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();

        return false !== $result ? $result : null;
    }
}
