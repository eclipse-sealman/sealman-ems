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

use App\Entity\User;
use App\Form\Helper\FormShaper;
use App\Service\Helper\AuthorizationCheckerTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserEditType extends AbstractType
{
    use AuthorizationCheckerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('username');
        $builder->add('enabled');
        $builder->add('enabledExpireAt');
        $builder->add('disablePasswordExpire');
        $builder->add('totpEnabled');
        $builder->add('roleAdmin');
        $builder->add('roleSmartems');
        $builder->add('accessTags');

        if ($this->isGranted('ROLE_ADMIN_VPN') || $this->isGranted('ROLE_DOCS_ADMIN_VPN')) {
            $builder->add('roleVpn');
            $builder->add('roleVpnEndpointDevices');
        }

        if ($this->isGranted('ROLE_ADMIN_SCEP') || $this->isGranted('ROLE_DOCS_ADMIN_SCEP')) {
            $builder->add('certificateBehaviours', CollectionType::class, [
                'entry_type' => UseableCertificateType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
                'empty_data' => new ArrayCollection(),
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $shaper = new FormShaper($event);

            if (!$shaper->isFieldValueTrue('roleVpn')) {
                $shaper->removeField('roleVpnEndpointDevices');
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $user = $event->getData();
            if (!$user->getRoleVpn()) {
                // Forcefully set roleVpnEndpointDevices to false when roleVpn is false to avoid validation errors.
                $user->setRoleVpnEndpointDevices(false);
            }
        // Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener is called with 0 priority. Our code needs to be called before it.
        }, 100);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'user:certificateBehaviours',
                'user:webUser',
            ],
        ]);
    }
}
