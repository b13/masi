<?php
declare(strict_types = 1);
namespace B13\Masi\Command;

/*
 * This file is part of TYPO3 CMS-extension masi by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for migrating fields from "pages.tx_realurl_exclude"
 * into "pages.exclude_slug_for_subpages".
 */
class MigrateFieldsCommand extends Command
{
    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this->setDescription('Migrates pages.tx_realurl_exclude to pages.exclude_slug_for_subpages.');
        $this->setHelp('This command is only needed once when RealURL database fields are still available.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');

        if ($this->doesRealurlFieldExist($conn)) {
            $queryBuilder = $conn->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $existingRows = $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('tx_realurl_exclude', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetchAll();

            $existingPages = array_column($existingRows, 'uid');

            $updateQueryBuilder = $conn->createQueryBuilder();
            $affectedRows = $updateQueryBuilder
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
                        $existingPages
                    )
                )
                ->execute();

            $io->success('Migrated ' . $affectedRows . ' pages (incl. translations)');
        } else {
            $io->warning('Nothing done, as the database field "pages.tx_realurl_exclude" does not exist.');
        }
    }

    /**
     * Checks if we even need the update wizard.
     *
     * @param Connection $conn
     * @return bool
     */
    protected function doesRealurlFieldExist(Connection $conn): bool
    {
        $columns = $conn->getSchemaManager()->listTableColumns('pages');
        foreach ($columns as $column) {
            if (strtolower($column->getName()) === 'tx_realurl_exclude') {
                return true;
            }
        }
        return false;
    }
}
