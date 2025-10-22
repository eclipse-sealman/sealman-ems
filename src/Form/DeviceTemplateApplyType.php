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

use App\Entity\Template;
use App\Model\DeviceTemplateApply;
use App\Service\Helper\AuthorizationCheckerTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceTemplateApplyType extends AbstractType
{
    use AuthorizationCheckerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_DOCS_ADMIN')) {
            $builder->add('applyDeviceDescription', CheckboxType::class);
            $builder->add('applyEndpointDevices', CheckboxType::class);
            $builder->add('applyMasquerade', CheckboxType::class);
        }

        $builder->add('template', EntityType::class, ['class' => Template::class]);
        $builder->add('applyVariables', CheckboxType::class);
        $builder->add('applyLabels', CheckboxType::class);
        $builder->add('applyAccessTags', CheckboxType::class);

        $builder->add('reinstallConfig1', CheckboxType::class);
        $builder->add('reinstallConfig2', CheckboxType::class);
        $builder->add('reinstallConfig3', CheckboxType::class);
        $builder->add('reinstallFirmware1', CheckboxType::class);
        $builder->add('reinstallFirmware2', CheckboxType::class);
        $builder->add('reinstallFirmware3', CheckboxType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeviceTemplateApply::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'device:templateApply',
            ],
        ]);
    }
}
