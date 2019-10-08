<?php
defined('TYPO3_MODE') or die();


$GLOBALS['TCA']['pages']['columns']['slug']['config']['generatorOptions']['postModifiers'][] = \B13\Masi\SlugModifier::class . '->modifyGeneratedSlugForPage';


$additionalColumns = [
    'exclude_slug_for_subpages' => [
        'exclude' => true,
        'label' => 'LLL:EXT:masi/Resources/Private/Language/locallang.xlf:pages.exclude_slug_for_subpages',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'items' => [
                [
                    0 => '',
                    1 => '',
                ]
            ],
            'behaviour' => [
                'allowLanguageSynchronization' => true
            ]
        ]
    ]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $additionalColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'title', 'exclude_slug_for_subpages', 'after:slug');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'titleonly', 'exclude_slug_for_subpages', 'after:slug');

