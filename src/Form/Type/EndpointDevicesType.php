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

use App\Entity\DeviceEndpointDevice;
use App\Entity\Traits\InjectedEndpointDevicesInterface;
use App\Security\SecurityHelperTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * EndpointDevicesType injects NOT owned endpoint devices into submitted data. This only happens when user does NOT have $this->isAllDevicesGranted().
 *
 * This approach solves multiple issues that arises when filling endpoint devices manually after data is submitted. Couple of examples:
 * - Entity that has modified $endpointDevices collection would have non-empty Doctrine changeset which would trigger Gedmo timestampable and blameable behaviours ($updatedAt and $updatedBy would be updated).
 * - $endpointDevices collection would have to be manually adjusted
 * - Information about injected endpoint devices is carried in a Device and is used by validators
 *
 * PersistentCollection from Doctrine is hardcoded to use ArrayCollection thus we cannot easily create Collection used in $endpointDevices
 */
class EndpointDevicesType extends AbstractType
{
    use SecurityHelperTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // This event has to be called before Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener::preSubmit()
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            if ($this->isAllDevicesGranted()) {
                // No changes are required
                return;
            }

            $data = $event->getData() ?? [];
            $endpointDevices = $event->getForm()->getData();
            $user = $this->getUser();

            if (null === $endpointDevices) {
                // When collection of endpoint devices is initializing this can be null at first (it should be executed again with filled data)
                return;
            }

            $parent = $event->getForm()->getParent()->getData();
            if (!$parent instanceof InjectedEndpointDevicesInterface) {
                throw new \Exception(EndpointDevicesType::class.' requires parent data object to implement '.InjectedEndpointDevicesInterface::class);
            }

            if (!$user) {
                throw new \Exception(EndpointDevicesType::class.' does not support a case without authenticated user');
            }

            foreach ($endpointDevices as $endpointDevice) {
                $hasAccess = $this->hasIntersectingAccessTag($user, $endpointDevice);
                if ($hasAccess) {
                    continue;
                }

                $id = $endpointDevice->getId();
                $included = array_key_exists($id, $data);

                $injectData = false;
                if (!$hasAccess && $included) {
                    // ! Crucial for security
                    // Data has to be injected so validator has correct data
                    // Endpoint device should NOT be injected
                    $injectData = true;
                    // Mark as overridden (data is injected, user has no access)
                    $parent->getOverriddenEndpointDevices()->add($endpointDevice);
                }

                if (!$hasAccess && !$included) {
                    // Inject both data and endpoint device
                    $injectData = true;
                    // Mark as injected (data is injected, user has access)
                    $parent->getInjectedEndpointDevices()->add($endpointDevice);
                }

                if ($injectData) {
                    // Data structure based on App\Form\DeviceEndpointDeviceType
                    $data[$id] = [
                        'name' => $endpointDevice->getName(),
                        'accessTags' => $endpointDevice->getAccessTags()->map(function ($accessTag) {
                            return $accessTag->getId();
                        })->toArray(),
                        'physicalIp' => $endpointDevice->getPhysicalIp(),
                        'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
                        'description' => $endpointDevice->getDescription(),
                    ];
                }
            }

            $event->setData($data);
        }, 150);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => DeviceEndpointDevice::class,
        ]);
    }

    public function getParent(): string
    {
        return IndexedCollectionType::class;
    }
}
