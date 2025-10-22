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
use App\Service\Helper\TotpManagerTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationTotpType extends AbstractType
{
    use TotpManagerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isTotpSecretGenerated = $this->totpManager->isTotpSecretGenerated();

        $builder->add('totpEnabled');
        $builder->add('totpKeyRegeneration', null, ['disabled' => $isTotpSecretGenerated]);
        $builder->add('totpWindow');
        $builder->add('totpTokenLength', null, ['disabled' => $isTotpSecretGenerated]);
        $builder->add('totpSecretLength', null, ['disabled' => $isTotpSecretGenerated]);
        $builder->add('totpAlgorithm', null, ['disabled' => $isTotpSecretGenerated]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $shaper = new FormShaper($event);

            if (!$shaper->isFieldValueTrue('totpEnabled')) {
                $shaper->removeField('totpKeyRegeneration');
                $shaper->removeField('totpWindow');
                $shaper->removeField('totpTokenLength');
                $shaper->removeField('totpSecretLength');
                $shaper->removeField('totpAlgorithm');
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
                'configuration:totp',
            ],
        ]);
    }
}
