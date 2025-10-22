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

use App\Entity\DeviceTypeCertificateType;
use App\Form\Helper\FormShaper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceTypeLimitedCertificateTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('certificateType');
        $builder->add('enableCertificatesAutoRenew');
        $builder->add('certificatesAutoRenewDaysBefore');
        $builder->add('enableSubjectAltName');
        $builder->add('subjectAltNameType');
        $builder->add('subjectAltNameValue');
        $builder->add('certificateEncoding');

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $shaper = new FormShaper($event);

            if (!$shaper->isFieldValueTrue('enableCertificatesAutoRenew')) {
                $shaper->removeField('certificatesAutoRenewDaysBefore');
            }

            if (!$shaper->isFieldValueTrue('enableSubjectAltName')) {
                $shaper->removeField('subjectAltNameType');
                $shaper->removeField('subjectAltNameValue');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeviceTypeCertificateType::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'deviceType:common',
            ],
        ]);
    }
}
