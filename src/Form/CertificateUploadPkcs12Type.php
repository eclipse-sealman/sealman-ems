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

use App\Model\CertificateUploadPkcs12Model;
use App\Service\Helper\CertificateManagerTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CertificateUploadPkcs12Type extends AbstractType
{
    use CertificateManagerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Could not find a way to attach property descriptions directly to App\Model\CertificateUploadPkcs12Model
        $builder->add('pkcs12', null, [
            'documentation' => [
                'description' => 'PKCS#12. TUS uploaded file.',
            ],
        ]);
        $builder->add('password', null, [
            'documentation' => [
                'description' => 'PKCS#12 password.',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CertificateUploadPkcs12Model::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'certificate:common',
            ],
            'constraints' => [
                new Callback([$this, 'validateCertificate']),
            ],
        ]);
    }

    public function validateCertificate(CertificateUploadPkcs12Model $protocol, ExecutionContextInterface $context)
    {
        $validationResult = $this->certificateManager->validatePkcs12Model($protocol);

        if (true !== $validationResult) {
            $context->buildViolation($validationResult)->atPath('pkcs12')->addViolation();

            return;
        }
    }
}
