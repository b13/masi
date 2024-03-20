<?php

declare(strict_types=1);

namespace B13\Masi\Updates;

/*
 * This file is part of TYPO3 CMS-extension masi by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Command for migrating fields from "pages.tx_realurl_exclude"
 * into "pages.exclude_slug_for_subpages".
 */
class MigrateRealUrlExcludeField implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'masiMigrateRealUrlExclude';
    }

    public function getTitle(): string
    {
        return 'Masi - Migrate RealUrl exclude field';
    }

    public function getDescription(): string
    {
        return 'Masi - Migrate RealUrl pages.tx_realurl_exclude field to Masi pages.exclude_slug_for_subpages';
    }

    protected function getExistingExcludedPages(): array
    {
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $existingRows = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_realurl_exclude',
                    $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        
        return array_column($existingRows, 'uid'); 

    }
    
    public function executeUpdate(): bool
    {
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $existingPages = $this->getExistingExcludedPages();

        $conn->createQueryBuilder()
            ->update('pages')
            ->set('exclude_slug_for_subpages', 1)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    // do not use named parameter here as the list can get too long
                    $existingPages
                ),
                $queryBuilder->expr()->in(
                    'l10n_parent',
                    array_merge($existingPages, [0])
                )
            )
            ->execute();

        return true;
    }

    protected function doesRealurlFieldExist(): bool
    {
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $columns = $conn->getSchemaManager()->listTableColumns('pages');
        foreach ($columns as $column) {
            if (strtolower($column->getName()) === 'tx_realurl_exclude') {
                return true;
            }
        }
        return false;
    }

    /**
     * Upgrade is necessary if the environment has the "tx_realurl_exclude" column and
     * there is at least one page having this field set to "1", else skip the wizard
     */
    public function updateNecessary(): bool
    {
        return $this->doesRealurlFieldExist() && count($this->getExistingExcludedPages()) > 0;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
