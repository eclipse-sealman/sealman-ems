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

use App\Entity\AccessTag;
use App\Entity\Traits\InjectedAccessTagsInterface;
use App\Security\SecurityHelperTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * AccessTagsType injects NOT owned access tags into submitted data. This only happens when user does NOT have $this->isAllDevicesGranted().
 *
 * This approach solves multiple issues that arises when filling access tags manually after data is submitted. Couple of examples:
 * - Entity that has modified $accessTags collection would have non-empty Doctrine changeset which would trigger Gedmo timestampable and blameable behaviours ($updatedAt and $updatedBy would be updated).
 * - $accessTags collection would have to be manually adjusted
 *
 * PersistentCollection from Doctrine is hardcoded to use ArrayCollection thus we cannot easily create Collection used in $accessTags
 */
class AccessTagsType extends AbstractType
{
    use SecurityHelperTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            if ($this->isAllDevicesGranted()) {
                // No changes are required
                return;
            }

            $data = $event->getData();
            $accessTags = $event->getForm()->getData();
            $user = $this->getUser();

            if (null === $accessTags) {
                // When collection of endpoint devices is initializing this can be null at first (it should be executed again with filled data)
                return;
            }

            $parent = $event->getForm()->getParent()->getData();
            if (!$parent instanceof InjectedAccessTagsInterface) {
                throw new \Exception(AccessTagsType::class.' requires parent data object to implement '.InjectedAccessTagsInterface::class);
            }

            if (!$user) {
                throw new \Exception(AccessTagsType::class.' does not support a case without authenticated user');
            }

            foreach ($accessTags->getSnapshot() as $accessTag) {
                $id = $accessTag->getId();
                if (in_array($id, $data)) {
                    continue;
                }

                if (!$user->getAccessTags()->contains($accessTag)) {
                    $data[] = $id;
                    $parent->getInjectedAccessTags()->add($accessTag);
                }
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => AccessTag::class,
            'multiple' => true,
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
