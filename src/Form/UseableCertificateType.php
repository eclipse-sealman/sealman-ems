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
use App\Model\UseableCertificate;
use App\Service\Helper\AuthorizationCheckerTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UseableCertificateType extends AbstractType
{
    use AuthorizationCheckerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('certificateType', EntityType::class, ['class' => CertificateType::class]);
        $builder->add('revokeCertificate', CheckboxType::class);
        $builder->add('generateCertificate', CheckboxType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UseableCertificate::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'device:common',
            ],
        ]);
    }
}
