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

use App\Entity\ConfigurationMicrosoftOidcRoleMapping;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationMicrosoftOidcRoleMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('roleName');
        $builder->add('microsoftOidcRole');
        $builder->add('roleVpnEndpointDevices');
        $builder->add('accessTags');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfigurationMicrosoftOidcRoleMapping::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'configuration:sso',
            ],
        ]);
    }
}
