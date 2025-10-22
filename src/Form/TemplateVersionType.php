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

use App\Entity\TemplateVersion;
use App\Form\Type\AccessTagsType;
use App\Security\SecurityHelperTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateVersionType extends AbstractType
{
    use SecurityHelperTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name');
        $builder->add('description');
        $builder->add('deviceLabels');
        $builder->add('accessTags', AccessTagsType::class);
        $builder->add('config1');
        $builder->add('config2');
        $builder->add('config3');
        $builder->add('firmware1');
        $builder->add('firmware2');
        $builder->add('firmware3');
        $builder->add('reinstallConfig1');
        $builder->add('reinstallConfig2');
        $builder->add('reinstallConfig3');
        $builder->add('reinstallFirmware1');
        $builder->add('reinstallFirmware2');
        $builder->add('reinstallFirmware3');
        $builder->add('variables', CollectionType::class, [
            'entry_type' => TemplateVersionVariableType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'error_bubbling' => false,
        ]);

        if ($this->isAllVpnDevicesGranted()) {
            $builder->add('deviceDescription');
            $builder->add('virtualSubnetCidr');
            $builder->add('masqueradeType');
            $builder->add('masquerades', CollectionType::class, [
                'entry_type' => TemplateVersionMasqueradeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ]);
            $builder->add('endpointDevices', CollectionType::class, [
                'entry_type' => TemplateVersionEndpointDeviceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TemplateVersion::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'templateVersion:common',
            ],
        ]);
    }
}
