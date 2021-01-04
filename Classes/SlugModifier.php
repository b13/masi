<?php
declare(strict_types = 1);
namespace B13\Masi;

/*
 * This file is part of TYPO3 CMS-extension masi by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class containing the hooks necessary for some magic
 */
class SlugModifier
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var int
     */
    protected $workspaceId;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * Defines whether the slug field should start with "/".
     * For pages (due to rootline functionality), this is a must have, otherwise the root level page
     * would have an empty value.
     *
     * @var bool
     */
    protected $prependSlashInSlug;

    /**
     * @var int
     */
    protected $pid;
    /**
     * @var array
     */
    protected $recordData;

    /**
     * Hooks into after a page URL was generated.
     *
     * @param array $parameters
     * @param SlugHelper $helper
     * @return string
     */
    public function modifyGeneratedSlugForPage(array $parameters, SlugHelper $helper): string
    {
        $this->resolveHookParameters(
            $parameters['configuration'],
            $parameters['tableName'],
            $parameters['fieldName'],
            $parameters['pid'],
            $parameters['workspaceId'],
            $parameters['record']
        );
        return $this->regenerateSlug($helper);
    }

    /**
     * Take over hook values to our own class.
     *
     * @param array $configuration
     * @param $tableName
     * @param $fieldName
     * @param $pid
     * @param $workspaceId
     * @param $record
     */
    protected function resolveHookParameters(array $configuration, $tableName, $fieldName, $pid, $workspaceId, $record)
    {
        $overrides = BackendUtility::getPagesTSconfig($pid)['TCEMAIN.'][$tableName . '.'][$fieldName . '.'] ?? [];
        if ($overrides) {
            $typoscriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $overrides = $typoscriptService->convertTypoScriptArrayToPlainArray(
                $overrides
            );
            if (isset($overrides['generatorOptions']['fields'])) {
                $overrides['generatorOptions']['fields'] = array_unique(
                    GeneralUtility::trimExplode(',', $overrides['generatorOptions']['fields'], true)
                );
            }
        }
        $this->configuration = array_replace_recursive($configuration, $overrides);
        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
        $this->pid = $pid;
        $this->workspaceId = $workspaceId;
        $this->recordData = $record;

        if ($tableName === 'pages' && $fieldName === 'slug') {
            $this->prependSlashInSlug = true;
        } else {
            $this->prependSlashInSlug = $this->configuration['prependSlash'] ?? false;
        }
    }

    /**
     * Re-creates the slug like core, however, uses our custom "resolveParentPageRecord" method.
     *
     * @param SlugHelper $helper
     * @return string
     */
    protected function regenerateSlug(SlugHelper $helper): string
    {
        $prefix = $this->configuration['generatorOptions']['prefix'] ?? '';
        if ($this->configuration['generatorOptions']['prefixParentPageSlug'] ?? false) {
            $languageFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['languageField'] ?? null;
            $languageId = (int)($this->recordData[$languageFieldName] ?? 0);
            $parentPageRecord = $this->resolveParentPageRecord($this->pid, $languageId);
            if (is_array($parentPageRecord)) {
                // If the parent page has a slug, use that instead of "re-generating" the slug from the parents' page title
                if (!empty($parentPageRecord['slug'])) {
                    $rootLineItemSlug = $parentPageRecord['slug'];
                } else {
                    $rootLineItemSlug = $helper->generate($parentPageRecord, (int)$parentPageRecord['pid']);
                }
                $rootLineItemSlug = trim($rootLineItemSlug, '/');
                if (!empty($rootLineItemSlug)) {
                    $prefix .= $rootLineItemSlug;
                }
            }
        }

        $fieldSeparator = $this->configuration['generatorOptions']['fieldSeparator'] ?? '/';
        $slugParts = [];

        $replaceConfiguration = $this->configuration['generatorOptions']['replacements'] ?? [];
        foreach ($this->configuration['generatorOptions']['fields'] ?? [] as $fieldNameParts) {
            if (is_string($fieldNameParts)) {
                $fieldNameParts = GeneralUtility::trimExplode(',', $fieldNameParts);
            }
            foreach ($fieldNameParts as $fieldName) {
                if (!empty($this->recordData[$fieldName])) {
                    $pieceOfSlug = $this->recordData[$fieldName];
                    $pieceOfSlug = str_replace(
                        array_keys($replaceConfiguration),
                        array_values($replaceConfiguration),
                        $pieceOfSlug
                    );
                    $slugParts[] = $pieceOfSlug;
                    break;
                }
            }
        }
        $slug = implode($fieldSeparator, $slugParts);
        $slug = $helper->sanitize($slug);
        // No valid data found
        if ($slug === '' || $slug === '/') {
            $slug = 'default-' . GeneralUtility::shortMD5(json_encode($this->recordData));
        }
        if ($this->prependSlashInSlug && ($slug[0] ?? '') !== '/') {
            $slug = '/' . $slug;
        }
        if (!empty($prefix)) {
            $slug = $prefix . $slug;
        }

        return (string)$helper->sanitize($slug);
    }

    /**
     * Similar to core logic, but a bit different:
     * Fetches the parent page, but only respects recyclers! includes sysfolders
     *
     * @param int $pid
     * @param int $languageId
     * @return array|null
     */
    protected function resolveParentPageRecord(int $pid, int $languageId): ?array
    {
        $parentPageRecord = null;
        $rootLine = BackendUtility::BEgetRootLine($pid, '', true, ['nav_title', 'exclude_slug_for_subpages']);
        do {
            $parentPageRecord = array_shift($rootLine) ?? [];
            $parentPageRecord = $this->tryRecordOverlay($parentPageRecord, $languageId);
            $excludeThisPageRecordForSubpages = (bool)$parentPageRecord['exclude_slug_for_subpages'];
        } while (!empty($rootLine) && ((int)$parentPageRecord['doktype'] === 255 || $excludeThisPageRecordForSubpages));
        return $parentPageRecord;
    }

    /**
     * Fetches a record translation if there is one and returns that one instead.
     *
     * @param array $page
     * @param int $languageId
     * @return array
     */
    protected function tryRecordOverlay(array $page, int $languageId): array
    {
        if ($languageId > 0) {
            $localizedParentPageRecord = BackendUtility::getRecordLocalization('pages', $page['uid'], $languageId);
            if (!empty($localizedParentPageRecord)) {
                $page = reset($localizedParentPageRecord);
            }
        }
        return $page;
    }
}
