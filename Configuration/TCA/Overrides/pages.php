<?php

defined('TYPO3') or die();

$GLOBALS['TCA']['pages']['columns']['slug']['config']['generatorOptions']['postModifiers'][] = \B13\Masi\SlugModifier::class . '->modifyGeneratedSlugForPage';

$additionalColumns = [
    'exclude_slug_for_subpages' => [
        'exclude' => true,
        'label' => 'LLL:EXT:masi/Resources/Private/Language/locallang.xlf:pages.exclude_slug_for_subpages',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'behaviour' => [
                'allowLanguageSynchronization' => true,
            ],
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $additionalColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'title', '--linebreak--,exclude_slug_for_subpages', 'after:slug');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'titleonly', '--linebreak--,exclude_slug_for_subpages', 'after:slug');
