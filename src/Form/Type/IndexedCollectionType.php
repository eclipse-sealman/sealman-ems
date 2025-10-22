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

namespace App\Form\Type;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ! Important. This form type will not work with create action as it uses array in FormEvents::POST_SUBMIT for unknown yet reason.
 * Fix it when needed.
 */
class IndexedCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // This event has to be called before Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener::preSetData()
        // It will allow ResizeFormListener to properly attach fields based on keys as IDs
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function ($event): void {
            $collection = $event->getData();
            if (!$collection) {
                // Skip when null
                return;
            }

            if (!$collection instanceof Collection) {
                throw new UnexpectedTypeException($collection, Collection::class);
            }

            // Remove all elements from collection
            $elements = [];
            foreach ($collection as $key => $element) {
                $elements[] = $collection->remove($key);
            }

            // Add all elements with keys as IDs to collection
            foreach ($elements as $element) {
                if (!\method_exists($element, 'getId')) {
                    throw new \Exception('Elements in collection used as data by IndexedCollectionType are required to have getId() method');
                }

                $collection->set($element->getId(), $element);
            }
        }, 150);

        // This event has to be called before Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener
        $builder->addEventListener(FormEvents::POST_SUBMIT, function ($event): void {
            $collection = $event->getData();
            if (!$collection) {
                // Skip when null
                return;
            }

            if (!$collection instanceof PersistentCollection) {
                throw new UnexpectedTypeException($collection, PersistentCollection::class);
            }

            // Entity that owns this collection. Entity on OneToMany side of the relation.
            // Example: Device - 1:N - DeviceEndpointDevice (owner is Device, collection includes endpoint devices)
            $owner = $collection->getOwner();
            if (ClassMetadata::ONE_TO_MANY === !$collection->getMapping()['type']) {
                throw new \Exception('IndexedCollectionType supports only one-to-many relationships');
            }

            $mappedBy = $collection->getMapping()['mappedBy'];
            $ownerSetter = 'set'.ucfirst($mappedBy);

            // Ensure that owner is set for each element in the collection (important for new elements)
            foreach ($collection as $element) {
                $element->$ownerSetter($owner);
            }
        }, 150);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Using by_reference = true to ensure indexes in collection are preserved as they were submitted
            // This means that addEndpointDevice and removeEndpointDevice will NOT be executed and we need to handle this manually
            // This is done in FormEvents::POST_SUBMIT which fills owner for each element in the collection
            'by_reference' => true,
            'error_bubbling' => false,
            'prototype' => false,
        ]);
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }
}
