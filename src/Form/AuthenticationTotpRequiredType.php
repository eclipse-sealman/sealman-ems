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

use App\Service\Helper\TotpManagerTrait;
use App\Service\Helper\UserTrait;
use Carve\ApiBundle\Validator\Constraints\Length;
use Carve\ApiBundle\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AuthenticationTotpRequiredType extends AbstractType
{
    use UserTrait;
    use TotpManagerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('totp', TextType::class, [
            'constraints' => [
                new NotBlank(),
                new Length(['max' => 255]),
                new Callback([$this, 'validateTotp']),
            ],
        ]);
    }

    public function validateTotp($protocol, ExecutionContextInterface $context)
    {
        if (!$this->totpManager->validateTotp($this->getUser(), $protocol)) {
            $context->buildViolation('validation.totp')->atPath('totp')->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
            ],
        ]);
    }
}
