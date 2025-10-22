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

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\NotifyPropertyChanged;
use Gedmo\Blameable\BlameableListener as GedmoBlameableListener;
use Proxy\__CG__\App\Entity\User as ProxyUser;

/**
 * TODO Verify if this class is still needed after ActorProvider is used.
 */
class BlameableListener extends GedmoBlameableListener
{
    /**
     * Updates a field.
     *
     * @param object           $object
     * @param AdapterInterface $eventAdapter
     * @param ClassMetadata    $meta
     * @param string           $field
     *
     * @return void
     */
    protected function updateField($object, $eventAdapter, $meta, $field)
    {
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);
        $newValue = $this->getFieldValue($meta, $field, $eventAdapter);

        // if field value is reference, persist object
        if ($meta->hasAssociation($field) && is_object($newValue) && !$eventAdapter->getObjectManager()->contains($newValue)) {
            $uow = $eventAdapter->getObjectManager()->getUnitOfWork();

            // Check to persist only when the object isn't already managed, always persists for MongoDB
            if (!($uow instanceof UnitOfWork) || UnitOfWork::STATE_MANAGED !== $uow->getEntityState($newValue)) {
                // !!!! THIS CONDITION IS ADDED TO PREVENT TESTS FREEZES
                // TODO DOUBLE CHECK IF EVERYTHING ELSE WORKS AS EXPECTED - during manual tests - automatic tests works fine
                if ($newValue instanceof User && ('createdBy' == $field || 'updatedBy' == $field)) {
                    $newValueProxy = $uow->tryGetByIdHash($newValue->getId(), User::class);
                    if ($newValueProxy && $newValueProxy instanceof ProxyUser) {
                        $newValueProxy->__load();
                        $newValue = $newValueProxy;
                    }
                }

                $eventAdapter->getObjectManager()->persist($newValue);
            }
        }

        $property->setValue($object, $newValue);

        if ($object instanceof NotifyPropertyChanged) {
            $uow = $eventAdapter->getObjectManager()->getUnitOfWork();
            $uow->propertyChanged($object, $field, $oldValue, $newValue);
        }
    }
}
