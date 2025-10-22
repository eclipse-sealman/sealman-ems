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

use Carve\ApiBundle\Validator\Constraints\GreaterThanOrEqual;
use Carve\ApiBundle\Validator\Constraints\NotBlank;
use Carve\ApiBundle\Validator\Constraints\Url;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScepCrlContentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('url', null, [
            'constraints' => [
                new NotBlank(),
                new Url(),
            ],
        ]);
        $builder->add('verifyServerSslCertificate', CheckboxType::class);
        $builder->add('scepTimeout', IntegerType::class, [
            'constraints' => [
                new NotBlank(),
                new GreaterThanOrEqual(1),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
