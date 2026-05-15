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

namespace Tests\Utilities\Feature\AuditLog;

use App\Entity\AuditLogChange;
use App\Enum\AuditLogChangeType;
use Carve\ApiBundle\Helper\Arr;

trait AssertAuditLogTrait
{
    protected function assertChange(AuditLogChange $change, AuditLogChangeType $type, ?array $expectedOldValues = null, ?array $expectedNewValues = null): void
    {
        switch ($type) {
            case AuditLogChangeType::CREATE:
                if (null !== $expectedOldValues) {
                    throw new \Exception('$expectedOldValues should be null for "'.$type->value.'" type');
                }
                if (null === $expectedNewValues) {
                    throw new \Exception('$expectedNewValues should be an array for "'.$type->value.'" type');
                }
                break;
            case AuditLogChangeType::UPDATE:
                if (null === $expectedOldValues) {
                    throw new \Exception('$expectedOldValues should be an array for "'.$type->value.'" type');
                }
                if (null === $expectedNewValues) {
                    throw new \Exception('$expectedNewValues should be an array for "'.$type->value.'" type');
                }
                break;
            case AuditLogChangeType::DELETE:
                if (null === $expectedOldValues) {
                    throw new \Exception('$expectedOldValues should be an array for "'.$type->value.'" type');
                }
                if (null !== $expectedNewValues) {
                    throw new \Exception('$expectedNewValues should be null for "'.$type->value.'" type');
                }
                break;
            default:
                throw new \Exception('Unsupported type');
        }

        $this->assertSameEnum($type, $change->getType());

        switch ($type) {
            case AuditLogChangeType::CREATE:
                $this->assertNull($change->getOldValues());
                $this->assertNotNull($change->getNewValues());
                break;
            case AuditLogChangeType::UPDATE:
                $this->assertNotNull($change->getOldValues());
                $this->assertNotNull($change->getNewValues());
                break;
            case AuditLogChangeType::DELETE:
                $this->assertNotNull($change->getOldValues());
                $this->assertNull($change->getNewValues());
                break;
        }

        if (null !== $expectedOldValues) {
            $values = $this->decode($change->getOldValues());

            foreach ($expectedOldValues as $key => $expectedValue) {
                $hasValue = Arr::has($values, $key);
                $this->assertTrue($hasValue, 'Expected value for key "'.$key.'" is missing');

                $value = Arr::get($values, $key);
                if ($expectedValue instanceof AssertDateTime) {
                    $acceptedDates = $expectedValue->getAcceptedDatesFormatted('c');
                    $this->assertContains($value, $acceptedDates, 'Old value for key "'.$key.'" is not in expected dates "'.implode(', ', $acceptedDates).'"');
                } else {
                    $this->assertSame($expectedValue, $value, 'Old value for key "'.$key.'" is not the same');
                }
            }
        }

        if (null !== $expectedNewValues) {
            $values = $this->decode($change->getNewValues());

            foreach ($expectedNewValues as $key => $expectedValue) {
                $hasValue = Arr::has($values, $key);
                $this->assertTrue($hasValue, 'Expected value for key "'.$key.'" is missing');

                $value = Arr::get($values, $key);
                if ($expectedValue instanceof AssertDateTime) {
                    $acceptedDates = $expectedValue->getAcceptedDatesFormatted('c');
                    $this->assertContains($value, $acceptedDates, 'New value for key "'.$key.'" is not in expected dates "'.implode(', ', $acceptedDates).'"');
                } else {
                    $this->assertSame($expectedValue, $value, 'New value for key "'.$key.'" is not the same');
                }
            }
        }
    }

    protected function decode(string $json): ?array
    {
        $this->assertTrue(\json_validate($json), 'Invalid JSON');

        return \json_decode($json, true);
    }

    protected function assertNoChange(string $entity, int $entityId): void
    {
        $reflection = new \ReflectionClass($entity);
        $entityName = $reflection->getShortName();

        $change = $this->getRepository(AuditLogChange::class)->findOneBy([
            'entityName' => $entityName,
            'entityId' => $entityId,
        ], [
            'id' => 'desc',
        ]);

        $this->assertNull($change);
    }

    protected function getLastChange(string $entity, array $criteria): ?AuditLogChange
    {
        $reflection = new \ReflectionClass($entity);
        $entityName = $reflection->getShortName();

        $change = $this->getRepository(AuditLogChange::class)->findOneBy([
            'entityName' => $entityName,
            ...$criteria,
        ], [
            'id' => 'desc',
        ]);

        if ($change && $change->getAuditLogChangeValues()) {
            $change->setOldValues($change->getAuditLogChangeValues()->getOldValues());
            $change->setNewValues($change->getAuditLogChangeValues()->getNewValues());
        }

        return $change;
    }

    protected function findChange(string $entity, int $entityId, ?string $message = null): AuditLogChange
    {
        $change = $this->getLastChange($entity, [
            'entityId' => $entityId,
        ]);

        $this->assertInstanceOf(AuditLogChange::class, $change, $message ?? "AuditLogChange for entity '{$entity}' with id '{$entityId}' does not exist");

        return $change;
    }

    protected function findChanges(string $entity, int $limit)
    {
        $reflection = new \ReflectionClass($entity);
        $entityName = $reflection->getShortName();

        $changes = $this->getRepository(AuditLogChange::class)->findBy([
            'entityName' => $entityName,
        ], [
            'id' => 'desc',
        ], $limit);

        foreach ($changes as $change) {
            if ($change->getAuditLogChangeValues()) {
                $change->setOldValues($change->getAuditLogChangeValues()->getOldValues());
                $change->setNewValues($change->getAuditLogChangeValues()->getNewValues());
            }
        }

        return $changes;
    }

    protected function assertChangeCount(int $expectedCount, ?string $entity = null): void
    {
        if (null === $entity) {
            $count = $this->getRepository(AuditLogChange::class)->count([]);
        } else {
            $reflection = new \ReflectionClass($entity);
            $entityName = $reflection->getShortName();

            $count = $this->getRepository(AuditLogChange::class)->count([
                'entityName' => $entityName,
            ]);
        }

        $this->assertSame($expectedCount, $count, 'Expected AuditLogChange count is not the same');
    }
}
