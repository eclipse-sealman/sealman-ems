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

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\AuditLogChange;
use App\Entity\AuditLogChangeValues;
use App\Enum\AuditLogChangeType;
use App\Service\Helper\EntityManagerTrait;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

/**
 * Custom logic for creating audit logs is used in following places:
 * - Everywhere createPartialBatchUpdate() is used
 * - App\Service\ConnectionAggregationManager::executeUpdateDeviceConnectionAmount().
 */
class AuditableManager
{
    use EntityManagerTrait;

    public function createPartialBatchUpdate(QueryBuilder $batchQueryBuilder, array $oldValues, array $newValues): void
    {
        $entities = $batchQueryBuilder->getRootEntities();

        if (1 !== count($entities)) {
            throw new \Exception('This function expects exacly one root entity in query builder');
        }

        $entity = $entities[0];

        if (!$this->hasResults($batchQueryBuilder)) {
            return;
        }

        $alias = $batchQueryBuilder->getRootAlias();

        $entityName = self::getEntityName($entity);
        $entityNameUniqueId = Uuid::v4()->toBase32().time(); // Is it unique enough? Should we try to get unique request ID from nginx or from php fpm thread?

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            /*
            Goal of code below is to insert AuditLog, AuditLogChange and AuditLogChangeValues records
            There will be one AuditLog record for all changes in this batch
            There will be one AuditLogChange record for each row returned by $batchQueryBuilder
            There will be one AuditLogChangeValues record for each AuditLogChange record
            AuditLogChange and AuditLogChangeValues records will be connected by AuditLogChange::auditLogChangeValues and AuditLogChangeValues::auditLogChange
            To be able to handle foreign keys correctly following order of operations is used:
            1. Insert AuditLog record
            2. Insert AuditLogChange records but entityName is used to mark inserted records. EntityName is set to unique value
                - Transformed $batchQueryBuilder is used to get entityId and generate values to be inserted into AuditLogChange
            3. Insert AuditLogChangeValues
                - Select query from AuditLogChange using entityName unique value is used to generate values to be inserted into AuditLogChangeValues
            4. Update AuditLogChange records
                - Update entityName to correct value
                - Update auditLogChangeValuesId to correct value
                - Using entityName unique value to find records to update
            */
            // ! Inserting AuditLog record to have ID for AuditLogChange records
            $auditLog = new AuditLog();
            $this->entityManager->persist($auditLog);
            $this->entityManager->flush();

            // ! Inserting AuditLogChange records
            $queryBuilder = clone $batchQueryBuilder;

            $queryBuilder->resetDQLPart('select');
            $queryBuilder->addSelect($alias.'.id');
            // :parameters has to be in parentheses. We want them as parameters for safety (i.e. SQL injection)
            $queryBuilder->addSelect('(:entityName)');
            $queryBuilder->addSelect('(:auditLogId)');
            $queryBuilder->addSelect('(:type)');
            $queryBuilder->addSelect('UTC_TIMESTAMP()');
            $queryBuilder->addSelect('(:createdById)');
            $queryBuilder->addSelect('(:onlyChanges)');

            $queryBuilder->setParameter('entityName', $entityNameUniqueId);
            $queryBuilder->setParameter('auditLogId', $auditLog->getId());
            $queryBuilder->setParameter('type', AuditLogChangeType::UPDATE->value);
            $queryBuilder->setParameter('createdById', $auditLog->getCreatedBy() ? $auditLog->getCreatedBy()->getId() : null);
            $queryBuilder->setParameter('onlyChanges', true);

            $query = $queryBuilder->getQuery();
            // $selectSql does NOT have filled parameters (just question marks)
            $selectSql = $query->getSQL();

            // Follow logic done by $query->_doExecute() which prepares params and their correct order
            $reflectionObject = new \ReflectionObject($query);
            $parserResult = $reflectionObject->getProperty('parserResult')->getValue($query);
            $parameterMappings = $parserResult->getParameterMappings();
            $reflection = new \ReflectionClass(get_class($query));
            $method = $reflection->getMethod('processParameterMappings');
            $method->setAccessible(true);
            [$sqlParams, $types] = $method->invokeArgs($query, [$parameterMappings]);

            $auditLogChangeTableName = $this->entityManager->getClassMetadata(AuditLogChange::class)->getTableName();
            $sql = 'INSERT INTO `'.$auditLogChangeTableName.'` (`entity_id`, `entity_name`, `log_id`, `type`, `created_at`, `created_by_id`, `only_changes`) '.$selectSql;
            $this->entityManager->getConnection()->executeQuery($sql, $sqlParams, $types);
            // After this statement AuditLogChange records are inserted, but AuditLogChangeValues are not inserted yet
            // DB is lacking AuditLogChangeValues::oldValues, AuditLogChangeValues::newValues, AuditLogChangeValues::auditLogChange, AuditLogChange::auditLogChangeValues
            // And AuditLogChange::entityName is invalid since $entityNameUniqueId was used to mark records above (they can be found by query)

            // ! Inserting AuditLogChangeValues records
            $queryBuilderSelectAuditLogChange = $this->getRepository(AuditLogChange::class)->createQueryBuilder('alc');
            $queryBuilderSelectAuditLogChange->resetDQLPart('select');
            $queryBuilderSelectAuditLogChange->addSelect('alc.id');
            // :parameters has to be in parentheses. We want them as parameters for safety (i.e. SQL injection)
            $queryBuilderSelectAuditLogChange->addSelect('(:oldValues)');
            $queryBuilderSelectAuditLogChange->addSelect('(:newValues)');
            $queryBuilderSelectAuditLogChange->setParameter('oldValues', \json_encode($oldValues));
            $queryBuilderSelectAuditLogChange->setParameter('newValues', \json_encode($newValues));

            $queryBuilderSelectAuditLogChange->andWhere('alc.entityName = :entityName');
            $queryBuilderSelectAuditLogChange->setParameter('entityName', $entityNameUniqueId);

            $querySelectAuditLogChange = $queryBuilderSelectAuditLogChange->getQuery();
            // $selectSql does NOT have filled parameters (just question marks)
            $selectAuditLogChangeSql = $querySelectAuditLogChange->getSQL();

            // Follow logic done by $querySelectAuditLogChange->_doExecute() which prepares params and their correct order
            $reflectionObject = new \ReflectionObject($querySelectAuditLogChange);
            $parserResult = $reflectionObject->getProperty('parserResult')->getValue($querySelectAuditLogChange);
            $parameterMappings = $parserResult->getParameterMappings();
            $reflection = new \ReflectionClass(get_class($querySelectAuditLogChange));
            $method = $reflection->getMethod('processParameterMappings');
            $method->setAccessible(true);
            [$sqlParams, $types] = $method->invokeArgs($querySelectAuditLogChange, [$parameterMappings]);

            $auditLogChangeValuesTableName = $this->entityManager->getClassMetadata(AuditLogChangeValues::class)->getTableName();
            $sql = 'INSERT INTO `'.$auditLogChangeValuesTableName.'` (`audit_log_change_id`, `old_values`, `new_values`) '.$selectAuditLogChangeSql;
            $this->entityManager->getConnection()->executeQuery($sql, $sqlParams, $types);
            // After this statement AuditLogChangeValues records are inserted, but AuditLogChange still have some fields with incorrect values
            // DB is lacking AuditLogChange::auditLogChangeValues
            // And AuditLogChange::entityName is invalid since $entityNameUniqueId was used to mark records above (they can be found by query)

            // ! Updating AuditLogChange records
            $updateAuditLogChangeSql = 'UPDATE '.$auditLogChangeTableName.' alc JOIN '.$auditLogChangeValuesTableName.' alcv ON alc.id = alcv.audit_log_change_id ';
            $updateAuditLogChangeSql .= 'SET alc.audit_log_change_values_id = alcv.id, alc.entity_name = :entityName WHERE alc.entity_name = :entityNameUniqueId';

            $statement = $connection->prepare($updateAuditLogChangeSql);
            $statement->bindValue('entityName', $entityName);
            $statement->bindValue('entityNameUniqueId', $entityNameUniqueId);
            $statement->execute();
            // After this statement AuditLogChange and AuditLogChangeValues records are updated and have correct values

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    protected function hasResults(QueryBuilder $queryBuilder): bool
    {
        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->select('COUNT('.$countQueryBuilder->getRootAlias().'.id)');

        $count = $countQueryBuilder->getQuery()->getSingleScalarResult();

        return $count > 0 ? true : false;
    }

    public static function getEntityName(string|object $entity): string
    {
        $reflectionClass = new \ReflectionClass($entity);

        return $reflectionClass->getShortName();
    }
}
