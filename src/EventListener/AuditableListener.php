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

use App\Entity\AuditLog;
use App\Entity\AuditLogChange;
use App\Entity\AuditLogChangeValues;
use App\Enum\AuditableMode;
use App\Enum\AuditLogChangeType;
use App\Model\AuditableInterface;
use App\Serializer\Normalizer\AuditableNormalizer;
use App\Service\AuditableManager;
use App\Service\Helper\EntityManagerTrait;
use Carve\ApiBundle\Helper\Arr;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * TODO Describe issue with onDelete: 'CASCADE' without cascade: ['remove'] on the other side will not create delete audit logs.
 * TODO Prepare minimal repeatable test case for getId() === null in getDeleteDiff().
 */
class AuditableListener
{
    use EntityManagerTrait;

    /**
     * Array of AuditLogChange objects.
     */
    public array $changes = [];

    public function __construct(
        private SerializerInterface $serializer,
        private ClassMetadataFactoryInterface $serializerMetadata,
    ) {
    }

    public function flushChanges(): void
    {
        if (0 === count($this->changes)) {
            return;
        }

        // When $log is flushed with $changes the $changes are in inversed order. Not sure why. Seems not worth the hassle.
        $log = new AuditLog();
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        foreach ($this->changes as $key => $change) {
            $change->setLog($log);
            $this->entityManager->persist($change);

            $changeValues = new AuditLogChangeValues();
            $changeValues->setAuditLogChange($change);
            $changeValues->setOldValues($change->getOldValues());
            $changeValues->setNewValues($change->getNewValues());
            $this->entityManager->persist($changeValues);

            $change->setAuditLogChangeValues($changeValues);
            $this->entityManager->persist($change);
        }

        $this->entityManager->flush();

        $this->changes = [];
    }

    public function postPersist(PostPersistEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$this->isAware($entity)) {
            return;
        }

        $objectManager = $args->getObjectManager();

        $change = $this->getLogChange($entity, $objectManager);
        $change->setNewValues($this->getSerializedValues($entity, AuditableMode::NEW));
        $change->setType(AuditLogChangeType::CREATE);

        $this->addChange($change);
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$this->isAware($entity)) {
            return;
        }

        $objectManager = $args->getObjectManager();
        $classMetadata = $objectManager->getClassMetadata($entity::class);
        $oldEntity = clone $entity;

        // ManyToMany associations are generally not present in $args->getEntityChangeSet()
        // They are handled by App\Serializer\Normalizer\AuditableNormalizer
        foreach ($args->getEntityChangeSet() as $field => $changeSet) {
            if ($classMetadata->isCollectionValuedAssociation($field)) {
                // When setting any collection manually i.e. $user->setAccessTags(new ArrayCollection())
                // Doctrine is passing PersistentCollection as $changeSet. This case is handled by App\Serializer\Normalizer\AuditableNormalizer
                continue;
            }

            $setter = 'set'.ucfirst($field);

            if ($classMetadata->isSingleValuedAssociation($field)) {
                $oldEntity->$setter($changeSet[0]);
                continue;
            }

            if (!$classMetadata->hasField($field)) {
                throw new \Exception('Unknown way of handling auditable field "'.$field.'" for class "'.$entity::class.'"');
            }

            $mapping = $classMetadata->getFieldMapping($field);
            switch ($mapping['type']) {
                case Types::STRING:
                    if (null !== Arr::get($mapping, 'enumType')) {
                        $enumClass = $mapping['enumType'];
                        // tryFrom will return null when it fails to create enum from value
                        $oldValue = null !== $changeSet[0] ? $enumClass::tryFrom($changeSet[0]) : null;
                        $oldEntity->$setter($oldValue);
                    } else {
                        $oldEntity->$setter($changeSet[0]);
                    }

                    break;
                default:
                    $oldEntity->$setter($changeSet[0]);
                    break;
            }
        }

        $oldValues = $this->getSerializedValues($oldEntity, AuditableMode::OLD, $entity);
        $newValues = $this->getSerializedValues($entity, AuditableMode::NEW, $oldEntity);

        if ($oldValues === $newValues) {
            // Avoid adding logs without any changes. This can happen when a value that is not auditable is changed in auditable entity
            return;
        }

        $change = $this->getLogChange($entity, $objectManager);
        $change->setType(AuditLogChangeType::UPDATE);
        $change->setOldValues($oldValues);
        $change->setNewValues($newValues);

        $this->addChange($change);
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $objectManager = $eventArgs->getObjectManager();
        $uow = $objectManager->getUnitOfWork();

        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            if (count($collection->getDeleteDiff()) > 0) {
                $mapping = $collection->getMapping();
                if (ClassMetadata::ONE_TO_MANY !== $mapping['type']) {
                    // Only work with OneToMany relations here
                    continue;
                }

                $ownerField = $mapping['inversedBy'] ?: $mapping['mappedBy'];
                $setter = 'set'.ucfirst($ownerField);

                foreach ($collection->getDeleteDiff() as $entity) {
                    if (!$this->isAware($entity)) {
                        continue;
                    }

                    if (null === $entity->getId()) {
                        // Entity could already be deleted and id is null.
                        // No idea why it still is present in collection (which is "dirty") in getDeleteDiff()
                        // Skip it
                        continue;
                    }

                    // This entity could already be processed by another collection update
                    // Skip it in such case
                    if ($this->hasDeleteChange($entity)) {
                        continue;
                    }

                    $oldEntity = clone $entity;
                    $oldEntity->$setter($collection->getOwner());

                    $change = $this->getLogChange($entity, $objectManager);
                    $change->setOldValues($this->getSerializedValues($oldEntity, AuditableMode::OLD));
                    $change->setType(AuditLogChangeType::DELETE);

                    $this->addChange($change);
                }
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$this->isAware($entity)) {
                continue;
            }

            // getScheduledCollectionUpdates() could already process this $entity as a part of collection
            // Skip it here as we are missing owner of $entity
            if ($this->hasDeleteChange($entity)) {
                continue;
            }

            $change = $this->getLogChange($entity, $objectManager);
            $change->setOldValues($this->getSerializedValues($entity, AuditableMode::OLD));
            $change->setType(AuditLogChangeType::DELETE);

            $this->addChange($change);
        }
    }

    protected function isAware($entity): bool
    {
        return $entity instanceof AuditableInterface;
    }

    protected function addChange(AuditLogChange $change): void
    {
        $identifier = $this->getChangeIdentifier($change);

        // We can have multiple updates on one entity during one session
        if (AuditLogChangeType::UPDATE === $change->getType() && isset($this->changes[$identifier])) {
            $this->changes[$identifier]->setNewValues($change->getNewValues());

            return;
        }

        $this->changes[$identifier] = $change;
    }

    protected function hasDeleteChange(AuditableInterface $entity): bool
    {
        $identifier = AuditableManager::getEntityName($entity).'_'.$entity->getId().'_'.AuditLogChangeType::DELETE->value;

        return isset($this->changes[$identifier]) ? true : false;
    }

    protected function getChangeIdentifier(AuditLogChange $change): string
    {
        return $change->getEntityName().'_'.$change->getEntityId().'_'.$change->getType()->value;
    }

    protected function getLogChange(AuditableInterface $entity, ObjectManager $objectManager): AuditLogChange
    {
        $change = new AuditLogChange();
        $change->setEntityName(AuditableManager::getEntityName($entity));
        $change->setEntityId($entity->getId());

        return $change;
    }

    protected function getSerializedValues($entity, AuditableMode $mode, $comparableEntity = null): string
    {
        return $this->serializer->serialize($entity, 'json', [
            'groups' => [
                AuditableInterface::GROUP,
                AuditableInterface::ENCRYPTED_GROUP,
            ],
            AuditableNormalizer::AUDITABLE_ENTITY => \spl_object_hash($entity),
            AuditableNormalizer::AUDITABLE_MODE => $mode,
            AbstractNormalizer::CALLBACKS => $this->getEncryptedCallbacks($entity, $comparableEntity),
            // Max depth check and handler is not needed due to use of App\Serializer\Normalizer\AuditableNormalizer
        ]);
    }

    /**
     * Encrypted callback is created for each encrypted property.
     * Callback will return AuditableInterface::VALUE_ENCRYPTED_UNCHANGED, AuditableInterface::VALUE_ENCRYPTED_CHANGED or null when $data is null.
     */
    protected function getEncryptedCallbacks($entity, $comparableEntity = null): array
    {
        $callbacks = [];

        $classMetadata = $this->serializerMetadata->getMetadataFor($entity::class);
        foreach ($classMetadata->getAttributesMetadata() as $attributeMetadata) {
            if (in_array(AuditableInterface::ENCRYPTED_GROUP, $attributeMetadata->getGroups())) {
                $getter = 'get'.\ucfirst($attributeMetadata->getName());
                // Verify whether encrypted values has been changed
                $hasChanged = null !== $comparableEntity && $entity->$getter() !== $comparableEntity->$getter() ? true : false;

                $callbacks[$attributeMetadata->getName()] = function ($data) use ($hasChanged) {
                    if (null === $data) {
                        return null;
                    }

                    if (!$hasChanged) {
                        return AuditableInterface::VALUE_ENCRYPTED_UNCHANGED;
                    } else {
                        return AuditableInterface::VALUE_ENCRYPTED_CHANGED;
                    }
                };
            }
        }

        return $callbacks;
    }
}
