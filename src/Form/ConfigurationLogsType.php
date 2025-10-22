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
use App\Service\Helper\AuthorizationCheckerTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationLogsType extends AbstractType
{
    use AuthorizationCheckerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('communicationLogsCleanupDuration');
        $builder->add('communicationLogsCleanupSize');
        $builder->add('diagnoseLogsCleanupDuration');
        $builder->add('diagnoseLogsCleanupSize');
        $builder->add('configLogsCleanupDuration');
        $builder->add('configLogsCleanupSize');

        if ($this->isGranted('ROLE_ADMIN_SCEP') || $this->isGranted('ROLE_DOCS_ADMIN_SCEP')) {
            $builder->add('vpnLogsCleanupDuration');
            $builder->add('vpnLogsCleanupSize');
        }

        $builder->add('deviceFailedLoginAttemptsCleanupDuration');
        $builder->add('deviceFailedLoginAttemptsCleanupSize');
        $builder->add('userLoginAttemptsCleanupDuration');
        $builder->add('userLoginAttemptsCleanupSize');
        $builder->add('deviceCommandsCleanupDuration');
        $builder->add('deviceCommandsCleanupSize');
        $builder->add('maintenanceLogsCleanupDuration');
        $builder->add('maintenanceLogsCleanupSize');
        $builder->add('importFileRowLogsCleanupDuration');
        $builder->add('importFileRowLogsCleanupSize');
        $builder->add('auditLogsCleanupDuration');
        $builder->add('auditLogsCleanupSize');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'configuration:logs',
            ],
        ]);
    }
}
