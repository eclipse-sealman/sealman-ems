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

use App\Model\RouterModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RouterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('Serial');
        $builder->add('Firmware');
        $builder->add('Model');
        $builder->add('CellID');
        $builder->add('RSRP');
        $builder->add('IMEI');
        $builder->add('IMSI');
        $builder->add('config');
        $builder->add('IPv6Prefix');

        $builder->add('RouterUptime');
        $builder->add('OperatorCode');
        $builder->add('Band');
        $builder->add('Cellular1_IP');
        $builder->add('Cellular1_uptime');
        $builder->add('Cellular2_IP');
        $builder->add('Cellular2_uptime');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RouterModel::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            //Validation groups provided in controller
        ]);
    }
}
