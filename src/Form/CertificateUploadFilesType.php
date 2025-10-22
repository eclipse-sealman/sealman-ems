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

use App\Model\CertificateUploadFilesModel;
use App\Service\Helper\CertificateManagerTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CertificateUploadFilesType extends AbstractType
{
    use CertificateManagerTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Could not find a way to attach property descriptions directly to App\Model\CertificateUploadFilesModel
        $builder->add('certificate', null, [
            'documentation' => [
                'description' => 'SSL certificate. TUS uploaded file.',
            ],
        ]);
        $builder->add('privateKey', null, [
            'documentation' => [
                'description' => 'Certificate\'s private key. TUS uploaded file.',
            ],
        ]);
        $builder->add('certificateCa', null, [
            'documentation' => [
                'description' => 'Certificate\'s CA certificate. TUS uploaded file.',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CertificateUploadFilesModel::class,
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

    public function validateCertificate(CertificateUploadFilesModel $protocol, ExecutionContextInterface $context)
    {
        $validationResult = $this->certificateManager->validateFilesModelCertificate($protocol);

        if (true !== $validationResult) {
            $context->buildViolation($validationResult)->atPath('certificate')->addViolation();

            return;
        }
    }
}
