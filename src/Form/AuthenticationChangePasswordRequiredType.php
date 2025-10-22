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

use App\Validator\Constraints\Password;
use Carve\ApiBundle\Validator\Constraints\NotBlank;
use Carve\ApiBundle\Validator\Constraints\PasswordIdenticalCompare;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthenticationChangePasswordRequiredType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('newPlainPassword', PasswordType::class, [
            'constraints' => [
                new NotBlank(),
                new Password(user: $options['user']),
            ],
        ]);
        $builder->add('newPlainPasswordRepeat', PasswordType::class, [
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
            'csrf_protection' => false,
            'constraints' => [
                new PasswordIdenticalCompare(
                    propertyPath1: '[newPlainPassword]',
                    propertyPath2: '[newPlainPasswordRepeat]',
                ),
            ],
            'validation_groups' => [
                'Default',
            ],
        ]);
    }
}
