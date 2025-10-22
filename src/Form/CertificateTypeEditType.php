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

use App\Entity\CertificateType;
use App\Enum\PkiType;
use App\Form\Helper\FormShaper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CertificateTypeEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', null, ['disabled' => $options['predefinedCertificateCategory']]);
        $builder->add('commonNamePrefix', null, ['disabled' => $options['predefinedCertificateCategory']]);
        $builder->add('variablePrefix', null, ['disabled' => $options['predefinedCertificateCategory']]);
        $builder->add('enabled');
        $builder->add('uploadEnabled');
        $builder->add('downloadEnabled');
        $builder->add('deleteEnabled');
        $builder->add('pkiEnabled');
        $builder->add('enabledBehaviour', null, ['disabled' => $options['predefinedCertificateCategory']]);
        $builder->add('disabledBehaviour', null, ['disabled' => $options['predefinedCertificateCategory']]);
        $builder->add('pkiType');
        $builder->add('scepVerifyServerSslCertificate');
        $builder->add('scepUrl');
        $builder->add('scepCrlUrl');
        $builder->add('scepRevocationUrl');
        $builder->add('scepTimeout');
        $builder->add('scepRevocationBasicAuthUser');
        $builder->add('scepRevocationBasicAuthPassword');
        $builder->add('scepKeyLength');
        $builder->add('scepHashFunction');

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $shaper = new FormShaper($event);

            if (!$shaper->isFieldValueEqual('pkiType', PkiType::SCEP->value)) {
                $shaper->removeField('scepVerifyServerSslCertificate');
                $shaper->removeField('scepUrl');
                $shaper->removeField('scepCrlUrl');
                $shaper->removeField('scepRevocationUrl');
                $shaper->removeField('scepTimeout');
                $shaper->removeField('scepRevocationBasicAuthUser');
                $shaper->removeField('scepRevocationBasicAuthPassword');
                $shaper->removeField('scepKeyLength');
                $shaper->removeField('scepHashFunction');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'predefinedCertificateCategory' => false,
            'data_class' => CertificateType::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'certificateType:common',
            ],
        ]);

        $resolver->setAllowedTypes('predefinedCertificateCategory', 'bool');
    }
}
