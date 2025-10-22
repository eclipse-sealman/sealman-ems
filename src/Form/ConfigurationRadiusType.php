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
use App\Form\Helper\FormShaper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationRadiusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('radiusEnabled');
        $builder->add('radiusAuth');
        $builder->add('radiusServer');
        $builder->add('radiusSecret');
        $builder->add('radiusNasAddress');
        $builder->add('radiusNasPort');
        $builder->add('radiusWelotecGroupMappingEnabled');
        $builder->add('radiusWelotecTagMappingEnabled');
        $builder->add('radiusWelotecGroupMappings', CollectionType::class, [
            'entry_type' => ConfigurationRadiusWelotecGroupMappingsType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'error_bubbling' => false,
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            // Make sure Radius group mapping and tag mapping are set correctly
            $configuration = $event->getData();

            if (!$configuration->getRadiusEnabled()) {
                $configuration->setRadiusWelotecGroupMappingEnabled(false);
            }

            if (!$configuration->getRadiusWelotecGroupMappingEnabled()) {
                $configuration->setRadiusWelotecTagMappingEnabled(false);
            }

            $event->setData($configuration);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $shaper = new FormShaper($event);

            if (!$shaper->isFieldValueTrue('radiusEnabled')) {
                $shaper->removeField('radiusAuth');
                $shaper->removeField('radiusServer');
                $shaper->removeField('radiusSecret');
                $shaper->removeField('radiusNasAddress');
                $shaper->removeField('radiusNasPort');
                $shaper->removeField('radiusWelotecGroupMappingEnabled');
                $shaper->removeField('radiusWelotecTagMappingEnabled');
                $shaper->removeField('radiusWelotecGroupMappings');
            }

            if (!$shaper->isFieldValueTrue('radiusWelotecGroupMappingEnabled')) {
                $shaper->removeField('radiusWelotecTagMappingEnabled');
                $shaper->removeField('radiusWelotecGroupMappings');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'configuration:radius',
            ],
        ]);
    }
}
