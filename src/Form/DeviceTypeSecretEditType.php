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

use App\Entity\DeviceTypeSecret;
use App\Enum\SecretValueBehaviour;
use App\Form\Helper\FormShaper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceTypeSecretEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name');
        $builder->add('description');
        $builder->add('useAsVariable');
        $builder->add('variableNamePrefix');
        $builder->add('secretValueBehaviour');
        $builder->add('secretValueRenewAfterDays');
        $builder->add('manualForceRenewal');
        $builder->add('manualEdit');
        $builder->add('manualEditRenewReminder');
        $builder->add('manualEditRenewReminderAfterDays');
        $builder->add('secretMinimumLength');
        $builder->add('secretDigitsAmount');
        $builder->add('secretUppercaseLettersAmount');
        $builder->add('secretLowercaseLettersAmount');
        $builder->add('secretSpecialCharactersAmount');
        $builder->add('accessTags');

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $shaper = new FormShaper($event);

            if (!$shaper->isFieldValueTrue('useAsVariable')) {
                $shaper->removeField('variableNamePrefix');
                $shaper->removeField('secretValueBehaviour');
                $shaper->removeField('secretValueRenewAfterDays');
            }

            $secretValueBehaviour = $shaper->hasFieldValue('secretValueBehaviour') ? $shaper->getFieldValue('secretValueBehaviour') : null;
            if (!SecretValueBehaviour::isRenew($secretValueBehaviour)) {
                $shaper->removeField('secretValueRenewAfterDays');
            }

            if (SecretValueBehaviour::NONE === $secretValueBehaviour) {
                $shaper->removeField('manualForceRenewal');
            }

            if (!$shaper->isFieldValueTrue('manualEdit')) {
                $shaper->removeField('manualEditRenewReminder');
                $shaper->removeField('manualEditRenewReminderAfterDays');
            }

            if (!$shaper->isFieldValueTrue('manualEditRenewReminder')) {
                $shaper->removeField('manualEditRenewReminderAfterDays');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeviceTypeSecret::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'deviceTypeSecret:common',
            ],
        ]);
    }
}
