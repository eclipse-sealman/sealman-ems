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

use App\Entity\Firmware;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FirmwareEditEnabledHardwareFilesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $allowEditRequiredFirmware = $options['allowEditRequiredFirmware'] ?? false;

        $builder->add('name');
        $builder->add('version', null, ['disabled' => true]);
        $builder->add('requiredFirmware', null, ['disabled' => !$allowEditRequiredFirmware]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Firmware::class,
            'csrf_protection' => false,
            'allowEditRequiredFirmware' => false,
            'requiredFirmwareId' => null,
            'validation_groups' => [
                'Default',
                'firmware:common',
            ],
        ]);

        $resolver->setAllowedTypes('allowEditRequiredFirmware', 'bool');
        $resolver->setAllowedTypes('requiredFirmwareId', ['null', 'int']);
    }
}
