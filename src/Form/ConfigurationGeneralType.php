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

use App\Entity\Configuration;
use App\Form\Helper\FormShaper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationGeneralType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('routerIdentifier');
        $builder->add('configGeneratorPhp');
        $builder->add('configGeneratorTwig');
        $builder->add('passwordExpireDays');
        $builder->add('passwordBlockReuseOldPasswordCount');
        $builder->add('passwordMinimumLength');
        $builder->add('passwordDigitRequired');
        $builder->add('passwordBigSmallCharRequired');
        $builder->add('passwordSpecialCharRequired');
        $builder->add('failedLoginAttemptsEnabled');
        $builder->add('failedLoginAttemptsLimit');
        $builder->add('failedLoginAttemptsDisablingDuration');
        $builder->add('autoRemoveBackupsAfter');
        $builder->add('diskUsageAlarm');

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $shaper = new FormShaper($event);

            if (!$shaper->isFieldValueTrue('failedLoginAttemptsEnabled')) {
                $shaper->removeField('failedLoginAttemptsLimit');
                $shaper->removeField('failedLoginAttemptsDisablingDuration');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'configuration:general',
            ],
        ]);
    }
}
