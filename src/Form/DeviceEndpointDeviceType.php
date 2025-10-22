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

namespace App\Form;

use App\Entity\DeviceEndpointDevice;
use App\Form\Type\AccessTagsType;
use App\OpenApi\OpenApiDocumentation;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuthorizationCheckerTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceEndpointDeviceType extends AbstractType
{
    use AuthorizationCheckerTrait;
    use SecurityHelperTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_DOCS_ADMIN') || $this->isGranted('ROLE_VPN_ENDPOINTDEVICES') || $this->isGranted('ROLE_DOCS_VPN')) {
            $builder->add('name', null, [
                'documentation' => [
                    'description' => $this->isGranted('ROLE_DOCS_VPN') ? OpenApiDocumentation::FIELD_ROLE_ENDPOINTDEVICES : null,
                ],
            ]);
            $builder->add('accessTags', AccessTagsType::class, [
                'documentation' => [
                    'description' => $this->isGranted('ROLE_DOCS_VPN') ? OpenApiDocumentation::FIELD_ROLE_ENDPOINTDEVICES : null,
                ],
            ]);
            $builder->add('physicalIp', null, [
                'documentation' => [
                    'description' => $this->isGranted('ROLE_DOCS_VPN') ? OpenApiDocumentation::FIELD_ROLE_ENDPOINTDEVICES : null,
                ],
            ]);
            $builder->add('virtualIpHostPart', null, [
                'documentation' => [
                    'description' => $this->isGranted('ROLE_DOCS_VPN') ? OpenApiDocumentation::FIELD_ROLE_ENDPOINTDEVICES : null,
                ],
            ]);
        }

        $builder->add('description');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeviceEndpointDevice::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'deviceEndpointDevice:common',
                'deviceEndpointDevice:lock',
            ],
        ]);
    }
}
