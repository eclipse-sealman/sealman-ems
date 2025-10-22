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
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationSsoType extends AbstractType
{
    use AuthorizationCheckerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('singleSignOn');
        $builder->add('ssoAllowCustomRedirectUrl');
        $builder->add('microsoftOidcAppId');
        $builder->add('microsoftOidcCredential');
        $builder->add('microsoftOidcClientSecret');
        $builder->add('microsoftOidcTimeout');
        $builder->add('microsoftOidcUploadedCertificatePublic');
        $builder->add('microsoftOidcUploadedCertificatePrivate');
        $builder->add('microsoftOidcGenerateCertificate');
        $builder->add('microsoftOidcGenerateCertificateExpiryDays');
        $builder->add('microsoftOidcDirectoryId');
        $builder->add('microsoftOidcRoleMappings', CollectionType::class, [
            'required' => false,
            'entry_type' => ConfigurationMicrosoftOidcRoleMappingType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'error_bubbling' => false,
        ]);

        if ($this->isGranted('ROLE_ADMIN_VPN') || $this->isGranted('ROLE_DOCS_ADMIN_VPN')) {
            $builder->add('ssoRoleVpnCertificateAutoGenerate');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'configuration:sso',
            ],
        ]);
    }
}
