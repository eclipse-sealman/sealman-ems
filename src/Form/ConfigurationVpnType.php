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

use App\Entity\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationVpnType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('vpnConnectionLimit');
        $builder->add('vpnConnectionDuration');
        $builder->add('opnsenseUrl');
        $builder->add('opnsenseApiKey');
        $builder->add('opnsenseApiSecret');
        $builder->add('opnsenseTimeout');
        $builder->add('verifyOpnsenseSslCertificate');
        $builder->add('techniciansOpenvpnServerDescription');
        $builder->add('devicesOpenvpnServerDescription');
        $builder->add('devicesVpnNetworks');
        $builder->add('devicesVpnNetworksRanges');
        $builder->add('devicesVirtualVpnNetworks');
        $builder->add('devicesVirtualVpnNetworksRanges');
        $builder->add('techniciansVpnNetworks');
        $builder->add('techniciansVpnNetworksRanges');
        $builder->add('devicesOvpnTemplate');
        $builder->add('techniciansOvpnTemplate');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'configuration:vpn',
            ],
        ]);
    }
}
