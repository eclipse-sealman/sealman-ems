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

use App\Enum\EdgeGatewayCommandName;
use App\Enum\EdgeGatewayCommandStatus;
use App\Model\EdgeGatewayModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EdgeGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('registrationId');
        $builder->add('endorsementKey');
        $builder->add('hardwareVersion');
        $builder->add('firmwareVersion');
        $builder->add('serialNumber');
        $builder->add('imsi');
        $builder->add('imei');
        $builder->add('networkGeneration');
        $builder->add('commandName', EnumType::class, ['class' => EdgeGatewayCommandName::class]);
        $builder->add('commandTransactionId');
        $builder->add('commandStatus', EnumType::class, ['class' => EdgeGatewayCommandStatus::class]);
        $builder->add('commandStatusErrorCategory');
        $builder->add('commandStatusErrorPid');
        $builder->add('commandStatusErrorMessage');
        $builder->add('config', CollectionType::class, [
            'entry_type' => EdgeGatewayConfigurationConfigType::class,
            'error_bubbling' => false,
            'allow_add' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EdgeGatewayModel::class,
            'csrf_protection' => false,
            //Validation groups provided in controller
        ]);
    }
}
