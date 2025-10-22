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

use App\Entity\DeviceType;
use App\Form\Helper\FormShaper;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DeviceTypeLimitedType extends AbstractType
{
    use DeviceCommunicationFactoryTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', null, ['disabled' => true]);
        $builder->add('deviceName', null, ['disabled' => true]);
        $builder->add('certificateCommonNamePrefix', null, ['disabled' => true]);
        $builder->add('icon');
        $builder->add('color');
        $builder->add('enableConfigLogs');

        $builder->add('authenticationMethod');
        $builder->add('credentialsSource');
        $builder->add('deviceTypeSecretCredential');
        $builder->add('deviceTypeCertificateTypeCredential');

        $builder->add('enableConnectionAggregation');
        $builder->add('connectionAggregationPeriod');

        $builder->add('virtualSubnetCidr');
        $builder->add('masqueradeType');
        $builder->add('deviceCommandMaxRetries');
        $builder->add('deviceCommandExpireDuration');
        $builder->add('enableConfigMinRsrp');
        $builder->add('configMinRsrp');
        $builder->add('enableFirmwareMinRsrp');
        $builder->add('firmwareMinRsrp');

        // CertificateType cannot be deleted if at one device has certificate in this certificateType (is in requiredCertificateTypes)
        $builder->add('certificateTypes', CollectionType::class, [
            'entry_type' => DeviceTypeLimitedCertificateTypeType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'error_bubbling' => false,
            'constraints' => [
                new Callback([$this, 'validateCertificateTypesUniqueness']),
                new Callback([$this, 'validateRequiredCertificateTypes']),
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $shaper = new FormShaper($event);

            if ($options['hasNoneCommunicationProcedure']) {
                // if empty communication procedure not used fields will be removed
                $shaper->removeField('enableConfigLogs');
                $shaper->removeField('enableConnectionAggregation');
                $shaper->removeField('authenticationMethod');
                $shaper->removeField('credentialsSource');
                $shaper->removeField('deviceTypeSecretCredential');
                $shaper->removeField('deviceTypeCertificateTypeCredential');
            }

            if (!$options['hasCertificates']) {
                $shaper->removeField('certificateTypes');
            }

            if (!$options['hasFirmware']) {
                $shaper->removeField('enableFirmwareMinRsrp');
            }

            if (!$options['hasConfig']) {
                $shaper->removeField('enableConfigMinRsrp');
            }

            if (!$options['hasVpn'] || !$options['hasEndpointDevices']) {
                $shaper->removeField('virtualSubnetCidr');
            }

            if (!$options['hasMasquerade']) {
                $shaper->removeField('masqueradeType');
            }

            if (!$options['hasDeviceCommands']) {
                $shaper->removeField('deviceCommandMaxRetries');
                $shaper->removeField('deviceCommandExpireDuration');
            }

            if (!$shaper->isFieldValueTrue('enableConnectionAggregation')) {
                $shaper->removeField('connectionAggregationPeriod');
            }

            if (!$shaper->isFieldValueTrue('enableFirmwareMinRsrp')) {
                $shaper->removeField('firmwareMinRsrp');
            }

            if (!$shaper->isFieldValueTrue('enableConfigMinRsrp')) {
                $shaper->removeField('configMinRsrp');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'hasCertificates' => false,
            'hasVpn' => false,
            'hasEndpointDevices' => false,
            'hasMasquerade' => false,
            'hasDeviceCommands' => false,
            'hasConfig' => false,
            'hasFirmware' => false,
            'hasNoneCommunicationProcedure' => false,
            'requiredCertificateTypes' => [],
            'data_class' => DeviceType::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'deviceType:common',
            ],
        ]);

        $resolver->setAllowedTypes('hasCertificates', 'bool');
        $resolver->setAllowedTypes('hasVpn', 'bool');
        $resolver->setAllowedTypes('hasEndpointDevices', 'bool');
        $resolver->setAllowedTypes('hasMasquerade', 'bool');
        $resolver->setAllowedTypes('hasDeviceCommands', 'bool');
        $resolver->setAllowedTypes('hasConfig', 'bool');
        $resolver->setAllowedTypes('hasFirmware', 'bool');
        $resolver->setAllowedTypes('hasNoneCommunicationProcedure', 'bool');
        $resolver->setAllowedTypes('requiredCertificateTypes', 'array');
    }

    public function validateRequiredCertificateTypes($protocol, ExecutionContextInterface $context)
    {
        $requiredCertificateTypes = $context->getRoot()->getConfig()->getOption('requiredCertificateTypes');
        foreach ($requiredCertificateTypes as $requiredCertificateType) {
            $found = false;
            foreach ($protocol as $deviceTypeCertificateType) {
                if ($deviceTypeCertificateType->getCertificateType() == $requiredCertificateType) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $context->buildViolation('validation.deviceType.certificateTypeRequired')->setParameter('{{ certificateType }}', $requiredCertificateType->getRepresentation())->atPath('certificateTypes')->addViolation();
                break;
            }
        }
    }

    /**
     * manually checking uniqueness
     * because it's very likely that CertificateTypes collection might have different order and UniqueEntity validator will generate invalid error.
     */
    public function validateCertificateTypesUniqueness($protocol, ExecutionContextInterface $context)
    {
        foreach ($protocol as $key => $deviceTypeCertificateType) {
            foreach ($protocol as $loopKey => $loopDeviceTypeCertificateType) {
                if ($key == $loopKey) {
                    continue;
                }
                if ($deviceTypeCertificateType->getCertificateType() == $loopDeviceTypeCertificateType->getCertificateType()) {
                    $context->buildViolation('validation.uniqueEntity')->atPath('certificateTypes')->addViolation();
                }
            }
        }
    }
}
