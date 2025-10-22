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

use App\Entity\Device;
use App\Form\Type\AccessTagsType;
use App\Form\Type\EndpointDevicesType;
use App\OpenApi\OpenApiDocumentation;
use App\Service\Helper\AuthorizationCheckerTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceEditType extends AbstractType
{
    use AuthorizationCheckerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->isGranted('ROLE_ADMIN_SCEP') || $this->isGranted('ROLE_DOCS_ADMIN_SCEP')) {
            $builder->add('certificateBehaviours', CollectionType::class, [
                'entry_type' => UseableCertificateType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
                'empty_data' => new ArrayCollection(),
            ]);
        }

        if ($this->isGranted('ROLE_ADMIN_VPN') || $this->isGranted('ROLE_DOCS_ADMIN_VPN')) {
            $builder->add('virtualSubnetCidr');
            $builder->add('masqueradeType');
            $builder->add('masquerades', CollectionType::class, [
                'entry_type' => DeviceMasqueradeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ]);
        }

        if ($this->isGranted('ROLE_ADMIN_VPN') || $this->isGranted('ROLE_DOCS_ADMIN_VPN')) {
            $builder->add('endpointDevices', CollectionType::class, [
                'entry_type' => DeviceEndpointDeviceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ]);
        } elseif ($this->isGranted('ROLE_VPN_ENDPOINTDEVICES') || $this->isGranted('ROLE_DOCS_VPN')) {
            $builder->add('endpointDevices', EndpointDevicesType::class, [
                'entry_type' => DeviceEndpointDeviceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'documentation' => [
                    'description' => OpenApiDocumentation::join([
                        OpenApiDocumentation::FIELD_ROLE_ENDPOINTDEVICES,
                        OpenApiDocumentation::FIELD_INDEXED_COLLECTION,
                    ]),
                    'example' => [
                        22 => [
                            'name' => 'string',
                            'accessTags' => [
                                'string',
                            ],
                            'physicalIp' => 'string',
                            'virtualIpHostPart' => 0,
                            'description' => 'string',
                        ],
                        34 => [
                            'name' => 'string',
                            'accessTags' => [
                                'string',
                            ],
                            'physicalIp' => 'string',
                            'virtualIpHostPart' => 0,
                            'description' => 'string',
                        ],
                        'new_1' => [
                            'name' => 'string',
                            'accessTags' => [
                                'string',
                            ],
                            'physicalIp' => 'string',
                            'virtualIpHostPart' => 0,
                            'description' => 'string',
                        ],
                    ],
                ],
            ]);
        }

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_SMARTEMS') || $this->isGranted('ROLE_DOCS_ADMIN') || $this->isGranted('ROLE_DOCS_SMARTEMS')) {
            $builder->add('template');
            $builder->add('name');
            $builder->add('serialNumber');
            $builder->add('imsi');
            $builder->add('imei');
            $builder->add('registrationId');
            $builder->add('endorsementKey');
            $builder->add('hardwareVersion');
            $builder->add('model');
            $builder->add('reinstallFirmware1');
            $builder->add('reinstallFirmware2');
            $builder->add('reinstallFirmware3');
            $builder->add('reinstallConfig1');
            $builder->add('reinstallConfig2');
            $builder->add('reinstallConfig3');
            $builder->add('requestDiagnoseData');
            $builder->add('requestConfigData');

            $builder->add('variables', CollectionType::class, [
                'entry_type' => DeviceVariableType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ]);

            $builder->add('staging');
            $builder->add('enabled');
            $builder->add('accessTags', AccessTagsType::class);
        }

        $builder->add('description');
        $builder->add('labels');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Device::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'device:common',
                'device:lock',
                'deviceEndpointDevice:lock',
            ],
        ]);
    }
}
