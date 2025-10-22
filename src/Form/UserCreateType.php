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

use App\Entity\User;
use App\Validator\Constraints\Password;
use Carve\ApiBundle\Validator\Constraints\NotBlank;
use Carve\ApiBundle\Validator\Constraints\PasswordIdenticalCompare;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserCreateType extends UserEditType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('plainPassword', PasswordType::class, [
            'constraints' => [
                new NotBlank(),
                new Password(),
            ],
        ]);
        $builder->add('plainPasswordRepeat', PasswordType::class, [
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
            'constraints' => [
                new PasswordIdenticalCompare(
                    propertyPath1: 'plainPassword',
                    propertyPath2: 'plainPasswordRepeat',
                ),
            ],
            'validation_groups' => [
                'Default',
                'user:certificateBehaviours',
                'user:webUser',
                'user:create',
            ],
        ]);
    }
}
