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

namespace App\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory as DoctrineClassMetadataFactory;

/**
 * ClassMetadataFactory is overidden to change tracking policy for all entities to ClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT.
 *
 * Main reason for changing the tracking policy is following scenario done in a single request:
 * 1. Find any Device via repository
 * 2. Create VpnLog connected to it
 * 3. Remove the Device
 * 4. Add new Label (persist + flush)
 *
 * This leads to "A new entity was found through the relationship (...)" error originating in VpnLog which still have relation to removed Device
 * This relation is still present because device relation in VpnLog is set to onDelete="SET NULL" (CASCADE would also trigger the error)
 * This means removing VpnLog when deleting Device is handled by database and Doctrine does not update managed VpnLog after removal of the Device
 * We cannot exchange onDelete="SET NULL" to cascade: ['remove'] due to performance issues.
 * Doctrine would have to load all VpnLogs to memory and process them. There could be too many VpnLogs and their processing would fail.
 *
 * Detailed information can be found here:
 * https://www.doctrine-project.org/projects/doctrine-orm/en/2.19/reference/change-tracking-policies.html#deferred-explicit
 */
class ClassMetadataFactory extends DoctrineClassMetadataFactory
{
    /**
     * {@inheritDoc}
     */
    protected function newClassMetadataInstance($className): ClassMetadata
    {
        $classMetadata = parent::newClassMetadataInstance($className);
        $classMetadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT);

        return $classMetadata;
    }
}
