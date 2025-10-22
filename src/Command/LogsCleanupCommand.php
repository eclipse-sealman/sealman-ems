<?php

// Copyright (c) 2025 Contributors to the Eclipse Foundation.
//
// See the NOTICE file(s) distributed with this work for additional
// information regarding copyright ownership.
//
// This program and the accompanying materials are made available under the
// terms of the Apache License, Version 2.0 which is available at
// https://www.apache.org/licenses/LICENSE-2.0
//
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace App\Command;

use App\Entity\AuditLog;
use App\Entity\AuditLogChange;
use App\Entity\AuditLogChangeValues;
use App\Entity\CommunicationLog;
use App\Entity\CommunicationLogContent;
use App\Entity\ConfigLog;
use App\Entity\ConfigLogContent;
use App\Entity\DeviceCommand;
use App\Entity\DeviceFailedLoginAttempt;
use App\Entity\DiagnoseLog;
use App\Entity\DiagnoseLogContent;
use App\Entity\ImportFileRowLog;
use App\Entity\MaintenanceLog;
use App\Entity\UserLoginAttempt;
use App\Entity\VpnLog;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Carve\ApiBundle\Helper\Arr;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LogsCleanupCommand extends Command
{
    use EntityManagerTrait;
    use ConfigurationManagerTrait;

    protected null|SymfonyStyle $io = null;

    protected function configure(): void
    {
        $this->setName('app:logs:cleanup');
        $this->setDescription('Removes logs from database based on current configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $configuration = $this->getConfiguration();

        $this->cleanup(
            entityClass: CommunicationLog::class,
            duration: $configuration->getCommunicationLogsCleanupDuration(),
            size: $configuration->getCommunicationLogsCleanupSize(),
            relatedTableNames: [
                $this->getTableName(CommunicationLogContent::class),
                $this->getManyToManyTableName(CommunicationLog::class, 'accessTags'),
            ],
        );

        $this->cleanup(
            entityClass: DiagnoseLog::class,
            duration: $configuration->getDiagnoseLogsCleanupDuration(),
            size: $configuration->getDiagnoseLogsCleanupSize(),
            relatedTableNames: [
                $this->getTableName(DiagnoseLogContent::class),
                $this->getManyToManyTableName(DiagnoseLog::class, 'accessTags'),
            ],
        );

        $this->cleanup(
            entityClass: ConfigLog::class,
            duration: $configuration->getConfigLogsCleanupDuration(),
            size: $configuration->getConfigLogsCleanupSize(),
            relatedTableNames: [
                $this->getTableName(ConfigLogContent::class),
                $this->getManyToManyTableName(ConfigLog::class, 'accessTags'),
            ],
        );

        $this->cleanup(
            entityClass: VpnLog::class,
            duration: $configuration->getVpnLogsCleanupDuration(),
            size: $configuration->getVpnLogsCleanupSize()
        );

        $this->cleanup(
            entityClass: DeviceFailedLoginAttempt::class,
            duration: $configuration->getDeviceFailedLoginAttemptsCleanupDuration(),
            size: $configuration->getDeviceFailedLoginAttemptsCleanupSize()
        );

        $this->cleanup(
            entityClass: UserLoginAttempt::class,
            duration: $configuration->getUserLoginAttemptsCleanupDuration(),
            size: $configuration->getUserLoginAttemptsCleanupSize()
        );

        $this->cleanup(
            entityClass: DeviceCommand::class,
            duration: $configuration->getDeviceCommandsCleanupDuration(),
            size: $configuration->getDeviceCommandsCleanupSize()
        );

        $this->cleanup(
            entityClass: MaintenanceLog::class,
            duration: $configuration->getMaintenanceLogsCleanupDuration(),
            size: $configuration->getMaintenanceLogsCleanupSize()
        );

        $this->cleanup(
            entityClass: ImportFileRowLog::class,
            duration: $configuration->getImportFileRowLogsCleanupDuration(),
            size: $configuration->getImportFileRowLogsCleanupSize()
        );

        $this->cleanup(
            entityClass: AuditLog::class,
            duration: $configuration->getAuditLogsCleanupDuration(),
            size: $configuration->getAuditLogsCleanupSize(),
            relatedTableNames: [
                $this->getTableName(AuditLogChange::class),
                $this->getTableName(AuditLogChangeValues::class),
            ],
        );

        return Command::SUCCESS;
    }

    protected function cleanup(string $entityClass, int $duration, int $size, array $relatedTableNames = []): void
    {
        $executedCleanupByDuration = $this->cleanupByDuration($entityClass, $duration);

        if ($executedCleanupByDuration) {
            $this->optimizeTables($entityClass, $relatedTableNames);
            // Optimize table will lock whole table during execution. However some documentation states that lock is set briefly for starting and ending execution.
        }

        $executedCleanupBySize = $this->cleanupBySize($entityClass, $relatedTableNames, $size);

        if ($executedCleanupBySize) {
            $this->optimizeTables($entityClass, $relatedTableNames);
            // Optimize table will lock whole table during execution. However some documentation states that lock is set briefly for starting and ending execution.
        }
    }

    /**
     * Cleanup by duration is based on $createdAt field. Passing $entityClass without this field will result in an exception.
     * Returns true if cleanup was executed, false otherwise.
     */
    protected function cleanupByDuration(string $entityClass, int $duration): bool
    {
        $tableName = $this->getTableName($entityClass);

        if ($duration <= 0) {
            $this->text($tableName, 'Cleanup by duration is disabled');

            return false;
        }

        $createdAtLimit = new \DateTime('-'.$duration.' days');

        $queryBuilder = $this->getRepository($entityClass)->createQueryBuilder('q');
        $queryBuilder->select('COUNT(q.id)');
        $queryBuilder->andWhere('q.createdAt < :createdAtLimit');
        $queryBuilder->setParameter('createdAtLimit', $createdAtLimit);
        $rowsCount = $queryBuilder->getQuery()->getSingleScalarResult();

        if ($rowsCount <= 0) {
            $this->text($tableName, 'There are '.$rowsCount.' rows older then '.$createdAtLimit->format('Y-m-d H:i:s').' in the table. Cleanup by duration skipped');

            return false;
        }

        $this->text($tableName, 'Removing '.$rowsCount.' rows that are older then '.$createdAtLimit->format('Y-m-d H:i:s'));

        $queryBuilder = $this->getRepository($entityClass)->createQueryBuilder('q');
        $queryBuilder->delete();
        $queryBuilder->andWhere('q.createdAt < :createdAtLimit');
        $queryBuilder->setParameter('createdAtLimit', $createdAtLimit);
        $queryBuilder->getQuery()->execute();

        return true;
    }

    /**
     * Cleanup by size is based on total size of $entityClass and $relatedTableNames tables.
     * Amount of rows to delete will be calculated as percentage of $size to total size of tables.
     * Oldest amount of rows will be deleted from $entityClass table.
     * Code assumes that $relatedTableNames are related to $entityClass and will be removed as onDelete: CASCADE.
     * Returns true if cleanup was executed, false otherwise.
     */
    protected function cleanupBySize(string $entityClass, array $relatedTableNames, int $size): bool
    {
        $tableName = $this->getTableName($entityClass);

        if ($size <= 0) {
            $this->text($tableName, 'Cleanup by size is disabled');

            return false;
        }

        $tableSize = $this->getTableSize($tableName);
        if (null === $tableSize) {
            $this->warning($tableName, 'Could not determine table size. Cleanup by size skipped');

            return false;
        }

        $totalTableSize = $tableSize;
        foreach ($relatedTableNames as $relatedTableName) {
            $relatedTableSize = $this->getTableSize($relatedTableName);
            if (null === $relatedTableSize) {
                $this->warning($relatedTableName, 'Could not determine table size. Cleanup by size skipped');

                return false;
            }

            $totalTableSize += $relatedTableSize;
        }

        if ($totalTableSize <= $size) {
            if (count($relatedTableNames) > 0) {
                $this->text($tableName, 'Total table size is '.$totalTableSize.' MB. Cleanup size is set to '.$size.' MB. Cleanup by size skipped');
            } else {
                $this->text($tableName, 'Table size is '.$totalTableSize.' MB. Cleanup size is set to '.$size.' MB. Cleanup by size skipped');
            }

            return false;
        }

        if (count($relatedTableNames) > 0) {
            $this->text($tableName, 'Total table size is '.$totalTableSize.' MB. Cleanup size is set to '.$size.' MB. Cleanup by size will be executed');
        } else {
            $this->text($tableName, 'Table size is '.$totalTableSize.' MB. Cleanup size is set to '.$size.' MB. Cleanup by size will be executed');
        }

        $queryBuilder = $this->getRepository($entityClass)->createQueryBuilder('q');
        $queryBuilder->select('COUNT(q.id)');
        $rowsCount = $queryBuilder->getQuery()->getSingleScalarResult();

        if ($rowsCount <= 0) {
            $this->warning($tableName, 'There are '.$rowsCount.' rows in the table. Cleanup by size skipped');

            return false;
        }

        // Round up to always have at least one row to delete as we already established $totalTableSize > $size
        $deleteLimit = ceil($rowsCount * (1 - ($size / $totalTableSize)));
        $this->text($tableName, 'There are '.$rowsCount.' rows in the table. Removing '.$deleteLimit.' oldest rows');

        // Doctrine do not support LIMIT option when using delete with QueryBuilder. Deleting manually
        // Cannot bind $tableName as parameter
        $deleteQuery = 'DELETE FROM '.$tableName.' ORDER BY id ASC LIMIT :deleteLimit';

        $stmt = $this->entityManager->getConnection()->prepare($deleteQuery);
        $stmt->bindParam('deleteLimit', $deleteLimit, ParameterType::INTEGER);
        $stmt->executeQuery();

        return true;
    }

    /**
     * Executes OPTIMIZE TABLE for $entityClass and $relatedTableNames tables.
     */
    protected function optimizeTables(string $entityClass, array $relatedTableNames = []): void
    {
        $baseTableName = $this->getTableName($entityClass);

        $tableNames = array_merge([$baseTableName], $relatedTableNames);

        foreach ($tableNames as $tableName) {
            $this->text($tableName, 'Optimizing table');

            $optimizeQuery = 'OPTIMIZE TABLE '.$tableName;

            $stmt = $this->entityManager->getConnection()->prepare($optimizeQuery);
            $stmt->executeQuery();

            $this->text($tableName, 'Executing analyze table');
            $optimizeQuery = 'ANALYZE TABLE '.$tableName;

            $stmt = $this->entityManager->getConnection()->prepare($optimizeQuery);
            $stmt->executeQuery();
        }
    }

    protected function text(string $tableName, string $message): void
    {
        $this->io->text('Table \''.$tableName.'\': '.$message);
    }

    protected function warning(string $tableName, string $message): void
    {
        $this->io->warning('Table \''.$tableName.'\': '.$message);
    }

    protected function getTableName(string $entityClass): string
    {
        return $this->entityManager->getClassMetadata($entityClass)->getTableName();
    }

    protected function getManyToManyTableName(string $entityClass, string $fieldName): string
    {
        $classMetadataFactory = $this->entityManager->getMetadataFactory();
        $metadataClass = $classMetadataFactory->getMetadataFor($entityClass);

        if (ClassMetadataInfo::MANY_TO_MANY === Arr::get($metadataClass->associationMappings, $fieldName.'.type')) {
            if (Arr::has($metadataClass->associationMappings, $fieldName.'.joinTable.name')) {
                return Arr::get($metadataClass->associationMappings, $fieldName.'.joinTable.name');
            }
        }

        throw new \Exception('Many to Many table name not found. Entity: '.$entityClass.', field: '.$fieldName);
    }

    /**
     * Returns table size in megabytes.
     */
    protected function getTableSize(string $tableName): ?float
    {
        $connection = $this->entityManager->getConnection();
        $databaseName = $connection->getDatabase();

        $query = '
                SELECT table_name AS `table`,
                round(((data_length + index_length) / 1024 / 1024), 2) AS `size`
                FROM information_schema.TABLES 
                WHERE table_schema = :databaseName
                AND table_name = :tableName
                ';

        $stmt = $connection->prepare($query);
        $stmt->bindParam('databaseName', $databaseName);
        $stmt->bindParam('tableName', $tableName);
        $result = $stmt->executeQuery()->fetch();

        if (!isset($result['size'])) {
            return null;
        }

        return (float) $result['size'];
    }
}
