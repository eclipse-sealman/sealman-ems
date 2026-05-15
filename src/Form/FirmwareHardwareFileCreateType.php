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

use App\Entity\FirmwareHardwareFile;
use App\Enum\SourceType;
use App\Form\Helper\FormShaper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FirmwareHardwareFileCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('hardware');
        $builder->add('sourceType');
        $builder->add('filepath');
        $builder->add('externalUrl');
        $builder->add('md5', null, ['required' => false]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $shaper = new FormShaper($event);

            if (!$shaper->isFieldValueEqual('sourceType', SourceType::UPLOAD->value)) {
                $shaper->removeField('filepath');
            }

            if (!$shaper->isFieldValueEqual('sourceType', SourceType::EXTERNAL_URL->value)) {
                $shaper->removeField('externalUrl');
                $shaper->removeField('md5');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FirmwareHardwareFile::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'firmwareHardwareFile:common',
                'firmwareHardwareFile:create',
            ],
        ]);
    }
}
