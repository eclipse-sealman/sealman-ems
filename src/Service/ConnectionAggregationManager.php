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
use App\Entity\Device;
use App\Entity\DeviceConnectionAggregation;
use App\Entity\DeviceType;
use App\Enum\AuditLogChangeType;
use App\Service\Helper\EntityManagerTrait;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\QueryBuilder;

class ConnectionAggregationManager
{
    use EntityManagerTrait;

    // TIME_FRAME_MINUTES has to be divider of 60: 1, 2, 3, 4, 5, 6, 10, 12, 15, 20, 30, 60
    // TIME_FRAME_MINUTES is used to calculate time frames in minutes, hours are not managed due to optimization. So there has to be certain amount of time frames that fill whole hour - hence divider of 60 (minutes)
    public const TIME_FRAME_MINUTES = 15;

    public function incrementDeviceConnectionAmount(Device $device)
    {
        if (!$device->getDeviceType()->getEnableConnectionAggregation()) {
            return;
        }

        $this->entityManager->flush(); // This flush is added to make sure that DQL statements will have current data in DB

        $date = new \DateTime();

        $device->setConnectionAmount($device->getConnectionAmount() + 1);

        $this->entityManager->persist($device);
        $this->entityManager->flush();

        $deviceConnectionAggregationQuery = $this->getRepository(DeviceConnectionAggregation::class)->createQueryBuilder('dca');
        $deviceConnectionAggregationQuery->andWhere('dca.device = :device');
        $deviceConnectionAggregationQuery->setParameter('device', $device);
        $deviceConnectionAggregationQuery->andWhere('dca.startAt <= :startAt');
        $deviceConnectionAggregationQuery->setParameter('startAt', $date);
        $deviceConnectionAggregationQuery->andWhere('dca.endAt > :endAt');
        $deviceConnectionAggregationQuery->setParameter('endAt', $date);

        $deviceConnectionAggregation = $deviceConnectionAggregationQuery->getQuery()->getOneOrNullResult();

        if (!$deviceConnectionAggregation) {
            $deviceConnectionAggregation = $this->createDeviceConnectionAggregation($date);
            $deviceConnectionAggregation->setDevice($device);
            if (!$device->getConnectionAmountFrom()) {
                $device->setConnectionAmountFrom($deviceConnectionAggregation->getStartAt());
            }

            $deviceConnectionAggregation->setConnectionAmount($deviceConnectionAggregation->getConnectionAmount() + 1);

            $this->entityManager->persist($device);
            $this->entityManager->persist($deviceConnectionAggregation);
            $this->entityManager->flush();

            $this->updateDeviceConnectionAmount($device);

            return;
        }

        $deviceConnectionAggregation->setConnectionAmount($deviceConnectionAggregation->getConnectionAmount() + 1);

        $this->entityManager->persist($device);
        $this->entityManager->persist($deviceConnectionAggregation);
        $this->entityManager->flush();
    }

    public function createDeviceConnectionAggregation(\DateTime $date)
    {
        $deviceConnectionAggregation = new DeviceConnectionAggregation();

        $hour = intval($date->format('H'));
        $minute = intval($date->format('i'));

        $startMinute = intval(floor($minute / self::TIME_FRAME_MINUTES) * self::TIME_FRAME_MINUTES);

        $startDate = new \DateTime($date->format('c'));
        $startDate->setTime($hour, $startMinute, 0, 0);
        $deviceConnectionAggregation->setStartAt($startDate);

        $endDate = new \DateTime($startDate->format('c'));
        $endDate->modify('+'.self::TIME_FRAME_MINUTES.' minutes');

        $deviceConnectionAggregation->setEndAt($endDate);

        return $deviceConnectionAggregation;
    }

    public function updateDeviceConnectionAmount(Device $device)
    {
        $this->executeUpdateDeviceConnectionAmount($device->getDeviceType(), $device);
    }

    public function updateAllDevicesConnectionAmount()
    {
        $deviceTypes = $this->getRepository(DeviceType::class)->findBy(['enableConnectionAggregation' => true]);
        foreach ($deviceTypes as $deviceType) {
            if (!$deviceType->getIsAvailable()) {
                continue;
            }
            $this->executeUpdateDeviceConnectionAmount($deviceType);
        }
    }

    // This function updates connection amount in DeviceConnectionAggregation table (adds new records, updates existing record, removes too old ones)
    // This function updates records of one DeviceType (for console command), additionally it can be limited to only one Device (while device connects to communicate)
    private function executeUpdateDeviceConnectionAmount(DeviceType $deviceType, ?Device $device = null)
    {
        if (!$deviceType->getEnableConnectionAggregation()) {
            return;
        }

        //$oldStates might contain values from all devices of given deviceType if used in console command
        $oldStates = $this->getDeviceConnectionStates($deviceType, $device);

        $this->entityManager->flush(); // This flush is added to make sure that DQL statements will have current data in DB

        $limitDate = new \DateTime();
        $limitDate->sub(new \DateInterval('PT'.$deviceType->getConnectionAggregationPeriod().'H'));

        $innerQuery = $this->getRepository(DeviceConnectionAggregation::class)->createQueryBuilder('dca');
        $innerQuery->select('sum(dca.connectionAmount)');
        $innerQuery->andWhere('d.id = dca.device');
        $innerQuery->andWhere('dca.endAt > :endAt');

        $updateQuery = $this->getRepository(Device::class)->createQueryBuilder('d');
        $updateQuery->update();
        $updateQuery->set('d.connectionAmount', '('.$innerQuery->getQuery()->getDQL().')');

        $this->applyDeviceConnectionAmountFilter($updateQuery, $deviceType, $device);

        $updateQuery->setParameter('endAt', $limitDate);

        $updateQuery->getQuery()->getSingleScalarResult();

        $updateQuery = $this->getRepository(Device::class)->createQueryBuilder('d');
        $updateQuery->update();
        $updateQuery->set('d.connectionAmount', '0');

        $this->applyDeviceConnectionAmountFilter($updateQuery, $deviceType, $device);

        $updateQuery->andWhere('d.connectionAmount IS NULL');

        $updateQuery->getQuery()->getSingleScalarResult();

        // Deleting expired records
        $deleteQuery = $this->getRepository(DeviceConnectionAggregation::class)->createQueryBuilder('dca');
        $deleteQuery->delete();

        $innerQuery = $this->getRepository(Device::class)->createQueryBuilder('d');
        $innerQuery->select('d.id');
        $innerQuery->andWhere('d.deviceType = :deviceType');
        $deleteQuery->setParameter('deviceType', $deviceType);

        if ($device) {
            $innerQuery->andWhere('d.id = :device');
            $deleteQuery->setParameter('device', $device);
        }

        $deleteQuery->andWhere('dca.device IN ('.$innerQuery->getQuery()->getDQL().')');

        $deleteQuery->andWhere('dca.endAt <= :endAt');
        $deleteQuery->setParameter('endAt', $limitDate);
        $deleteQuery->getQuery()->getSingleScalarResult();

        $innerQuery = $this->getRepository(DeviceConnectionAggregation::class)->createQueryBuilder('dca');
        $innerQuery->select('min(dca.startAt)');
        $innerQuery->andWhere('d.id = dca.device');
        $innerQuery->andWhere('dca.endAt > :endAt');

        $updateQuery = $this->getRepository(Device::class)->createQueryBuilder('d');
        $updateQuery->update();
        $updateQuery->set('d.connectionAmountFrom', '('.$innerQuery->getQuery()->getDQL().')');

        $this->applyDeviceConnectionAmountFilter($updateQuery, $deviceType, $device);

        $updateQuery->setParameter('endAt', $limitDate);

        $updateQuery->getQuery()->getSingleScalarResult();

        $updateQuery = $this->getRepository(Device::class)->createQueryBuilder('d');
        $updateQuery->update();
        $updateQuery->set('d.connectionAmountFrom', "'".$limitDate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')."'");
        $updateQuery->andWhere('d.connectionAmountFrom IS NULL');

        $this->applyDeviceConnectionAmountFilter($updateQuery, $deviceType, $device);

        $updateQuery->getQuery()->getSingleScalarResult();

        //$newStates might contain values from all devices of given deviceType if used in console command
        $newStates = $this->getDeviceConnectionStates($deviceType, $device);
        $deviceIds = array_keys($oldStates);
        $stateDifferences = [];

        foreach ($deviceIds as $deviceId) {
            $stateDifference = $this->getDeviceStateDifference($deviceId, $oldStates[$deviceId], $newStates[$deviceId]);

            unset($oldStates[$deviceId]);
            unset($newStates[$deviceId]);

            if (null === $stateDifference) {
                continue;
            }

            $stateDifferences[] = $stateDifference;
        }

        if (count($stateDifferences) > 0) {
            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();

            try {
                $auditLog = new AuditLog();
                $this->entityManager->persist($auditLog);
                $this->entityManager->flush();

                foreach ($stateDifferences as $stateDifference) {
                    $change = new AuditLogChange();
                    $change->setEntityId($stateDifference[0]);
                    $change->setEntityName(AuditableManager::getEntityName(Device::class));
                    $change->setLog($auditLog);
                    $change->setType(AuditLogChangeType::UPDATE);
                    $change->setOnlyChanges(true);

                    $values = new AuditLogChangeValues();
                    $values->setOldValues($stateDifference[1]);
                    $values->setNewValues($stateDifference[2]);

                    $values->setAuditLogChange($change);
                    $change->setAuditLogChangeValues($values);

                    $this->entityManager->persist($values);
                    $this->entityManager->persist($change);
                }

                unset($stateDifferences);

                $this->entityManager->flush();

                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }
    }

    private function applyDeviceConnectionAmountFilter(QueryBuilder $queryBuilder, DeviceType $deviceType, ?Device $device = null): void
    {
        $alias = $queryBuilder->getRootAlias();

        $queryBuilder->andWhere($alias.'.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceType);

        if ($device) {
            $queryBuilder->andWhere($alias.'.id = :deviceId');
            $queryBuilder->setParameter('deviceId', $device->getId());
        }
    }

    /**
     * Return difference array or null when there is no difference between states.
     *
     * [
     *   'DEVICE_ID',
     *   'OLD_VALUES_AS_JSON_STRING',
     *   'NEW_VALUES_AS_JSON_STRING',
     * ]
     */
    private function getDeviceStateDifference(int $deviceId, array $oldState, array $newState): ?array
    {
        $isConnectionAmountSame = $oldState[0] === $newState[0] ? true : false;

        $oldConnectionAmountFrom = $oldState[1] instanceof \DateTime ? $oldState[1]->format('c') : null;
        $newConnectionAmountFrom = $newState[1] instanceof \DateTime ? $newState[1]->format('c') : null;
        $isConnectionAmountFromSame = $oldConnectionAmountFrom === $newConnectionAmountFrom ? true : false;

        if ($isConnectionAmountSame && $isConnectionAmountFromSame) {
            return null;
        }

        $oldValues = [];
        $newValues = [];

        if (!$isConnectionAmountSame) {
            $oldValues['connectionAmount'] = $oldState[0];
            $newValues['connectionAmount'] = $newState[0];
        }

        if (!$isConnectionAmountFromSame) {
            $oldValues['connectionAmountFrom'] = $oldConnectionAmountFrom;
            $newValues['connectionAmountFrom'] = $newConnectionAmountFrom;
        }

        return [
            $deviceId,
            \json_encode($oldValues),
            \json_encode($newValues),
        ];
    }

    /**
     * Returns array with device connection state (connectionAmount and connectionAmountFrom).
     *
     * [
     *   'DEVICE_ID' => [connectionAmount, connectionAmountFrom]
     * ]
     */
    private function getDeviceConnectionStates(DeviceType $deviceType, ?Device $device = null): array
    {
        $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder('d');
        $queryBuilder->select('d.id, d.connectionAmount, d.connectionAmountFrom');

        $this->applyDeviceConnectionAmountFilter($queryBuilder, $deviceType, $device);

        $results = $queryBuilder->getQuery()->getArrayResult();
        $state = [];

        foreach ($results as $result) {
            $state[$result['id']] = [$result['connectionAmount'], $result['connectionAmountFrom']];
        }

        return $state;
    }
}
