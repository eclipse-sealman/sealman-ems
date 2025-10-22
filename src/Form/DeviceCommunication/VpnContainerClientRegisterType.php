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

namespace App\Form\DeviceCommunication;

use App\Model\VpnContainerClientModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class VpnContainerClientRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name');
        $builder->add('uuid');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => VpnContainerClientModel::class,
             // Validation groups provided in controller
            'constraints' => [
                new Callback([$this, 'validateFields']),
            ],
        ]);
    }

    public function validateFields($protocol, ExecutionContextInterface $context)
    {
        if (!$protocol->getName() && !$protocol->getUuid()) {
            $context->buildViolation('validation.device.vpnContainerClientRegisterNameOrUuidRequired')->atPath('name')->addViolation();
            $context->buildViolation('validation.device.vpnContainerClientRegisterNameOrUuidRequired')->atPath('uuid')->addViolation();
        }
    }
}
