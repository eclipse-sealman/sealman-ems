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

namespace App\Serializer\Normalizer;

use App\Enum\AuditableMode;
use App\Model\AuditableInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer is used by App\EventListener\AuditableListener to serialize entity values for AuditLogChange.
 *
 * Any entities that are not the auditable entity are serialized using getId(). This covers OneToOne and ManyToOne relations.
 * This also ensures maximum serialization depth of 0.
 *
 * Doctrine\ORM\PersistentCollection are normalized to an array manually using getId() of each element. This covers ManyToMany relations.
 * This also ensures that collections are also serialized to an array instead of object.
 *
 * Serializing to an object can happen when there are two elements in collection and first one gets removed.
 * By default it would be serialized as {"1": ID} instead of [ID].
 *
 * Serialization of Doctrine\ORM\PersistentCollection has two modes.
 * AuditableMode::NEW normalizes elements to an array manually using getId() of each element
 * AuditableMode::OLD uses getSnapshot() which returns previous values in collection which are normalized manually using getId()
 *
 * OneToMany relations are not covered here.
 * In case they should be auditable they should implement App\Model\AuditableInterface which will trigger their serialization.
 */
class AuditableNormalizer implements NormalizerInterface
{
    /**
     * Entity that is serialized for auditable values.
     */
    public const AUDITABLE_ENTITY = 'auditable_entity';

    /**
     * Auditable mode App\Enum\AuditableMode.
     */
    public const AUDITABLE_MODE = 'auditable_mode';

    /**
     * Return supresses following deprecation message.
     *
     * Method "Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()" might add "array|string|int|float|bool|\ArrayObject|null" as a native return type declaration in the future.
     *
     * @return mixed
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($object instanceof PersistentCollection) {
            $object->initialize();

            $mode = $context[self::AUDITABLE_MODE];
            switch ($mode) {
                case AuditableMode::OLD:
                    $serialized = [];
                    foreach ($object->getSnapshot() as $element) {
                        $serialized[] = $element->getId();
                    }

                    sort($serialized);

                    return $serialized;
                case AuditableMode::NEW:
                    $serialized = [];
                    foreach ($object->getValues() as $element) {
                        $serialized[] = $element->getId();
                    }

                    sort($serialized);

                    return $serialized;
                default:
                    throw new \Exception('Unsupported mode "'.$mode ? $mode->value : $mode.'"');
            }
        }

        return $object->getId();
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        $auditableEntity = $context[self::AUDITABLE_ENTITY] ?? null;
        if (null === $auditableEntity) {
            return false;
        }

        $auditableMode = $context[self::AUDITABLE_MODE] ?? null;
        if (!$auditableMode instanceof AuditableMode) {
            return false;
        }

        $groups = $context['groups'] ?? [];
        if (!in_array(AuditableInterface::GROUP, $groups)) {
            return false;
        }

        if (!is_object($data)) {
            return false;
        }

        if (!$data instanceof PersistentCollection && !\method_exists($data, 'getId')) {
            return false;
        }

        // This normalizer should not affect auditable entity and let it be normalized normally
        // Except when auditable entity is referenced inside auditable entity (i.e. User::$updatedBy) which should be normalized by this class
        // To detect such case AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT_COUNTERS is used
        $objectHash = \spl_object_hash($data);
        // AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT_COUNTERS is protected so we use hardcoded value
        if (isset($context['circular_reference_limit_counters'][$objectHash])) {
            return true;
        }

        if ($objectHash === $auditableEntity) {
            return false;
        }

        return true;
    }

    public function getSupportedTypes(?string $format): array
    {
        // In Symfony 5.4 results where not cached by default. Adjust when needed.
        return [
            'object' => false,
            '*' => false,
        ];
    }
}
