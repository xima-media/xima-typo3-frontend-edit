<?php


defined('TYPO3') or die();

call_user_func(function () {

// TCA für tt_content überschreiben, um ein Feld für Kategorien hinzuzufügen
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'categories1' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:module.categoryList',
        'config' => [
            'type' => 'group',
            'allowed' => 'sys_category',  // Verknüpft das Feld mit der sys_category-Tabelle
            'prepend_tname' => true,      // Zeigt den Tabellennamen (sys_category) vor der Kategorie an
            'autoSizeMax' => 12,          // Maximale Anzahl an angezeigten Kategorien
            'minitems' => 0,              // Minimale Auswahl von Kategorien (keine Pflicht)
            'maxitems' => 100,            // Maximale Auswahl von 100 Kategorien
            'show_thumbs' => 1,           // Zeigt Thumbnails der Kategorien, falls verfügbar
            'wizards' => [
                'suggest' => [
                    'type' => 'suggest',  // Vorschlagsfunktion für die Auswahl der Kategorien
                ],
            ],
        ],
    ],
]);

// Hinzufügen des neuen Feldes 'categories' zur `tt_content`-Palette
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'categories1',  // Das neue Feld
    '',            // Hier kann ein bestimmtes Tab angegeben werden, z. B. '0', um es in einem spezifischen Tab zu platzieren
    'after:header' // Position des Feldes in der TCA (nach 'header')
);
});
