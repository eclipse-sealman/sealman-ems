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
use App\Enum\CommunicationProcedure;
use App\Enum\CommunicationProcedureRequirement;
use App\Form\Helper\FormShaper;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DeviceTypeType extends AbstractType
{
    use CertificateTypeHelperTrait;
    use DeviceCommunicationFactoryTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name');
        $builder->add('deviceName');
        $builder->add('icon');
        $builder->add('color');
        $builder->add('certificateCommonNamePrefix');
        $builder->add('enableConfigLogs');

        $builder->add('routePrefix');
        $builder->add('authenticationMethod');
        $builder->add('credentialsSource');
        $builder->add('deviceTypeSecretCredential');
        $builder->add('deviceTypeCertificateTypeCredential');
        $builder->add('communicationProcedure');
        $builder->add('enableConnectionAggregation');
        $builder->add('connectionAggregationPeriod');

        $builder->add('hasFirmware1');
        $builder->add('nameFirmware1');
        $builder->add('customUrlFirmware1');
        $builder->add('hasFirmware2');
        $builder->add('nameFirmware2');
        $builder->add('customUrlFirmware2');
        $builder->add('hasFirmware3');
        $builder->add('nameFirmware3');
        $builder->add('customUrlFirmware3');
        $builder->add('hasConfig1');
        $builder->add('hasAlwaysReinstallConfig1');
        $builder->add('nameConfig1');
        $builder->add('formatConfig1');
        $builder->add('hasConfig2');
        $builder->add('hasAlwaysReinstallConfig2');
        $builder->add('nameConfig2');
        $builder->add('formatConfig2');
        $builder->add('hasConfig3');
        $builder->add('hasAlwaysReinstallConfig3');
        $builder->add('nameConfig3');
        $builder->add('formatConfig3');

        $builder->add('hasTemplates');
        $builder->add('hasEndpointDevices');
        $builder->add('hasGsm');
        $builder->add('hasRequestConfig');
        $builder->add('hasRequestDiagnose');
        $builder->add('hasMasquerade');
        $builder->add('fieldRegistrationId');
        $builder->add('fieldEndorsementKey');
        $builder->add('fieldHardwareVersion');
        $builder->add('fieldModel');
        $builder->add('fieldSerialNumber');
        $builder->add('fieldImsi');
        $builder->add('hasDeviceToNetworkConnection');

        $builder->add('hasVariables');
        $builder->add('hasCertificates');
        $builder->add('hasVpn');
        $builder->add('virtualSubnetCidr');
        $builder->add('masqueradeType');
        $builder->add('hasDeviceCommands');
        $builder->add('deviceCommandMaxRetries');
        $builder->add('deviceCommandExpireDuration');
        $builder->add('enableConfigMinRsrp');
        $builder->add('configMinRsrp');
        $builder->add('enableFirmwareMinRsrp');
        $builder->add('firmwareMinRsrp');

        $builder->add('certificateTypes', CollectionType::class, [
            'entry_type' => DeviceTypeCertificateTypeType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'error_bubbling' => false,
            'constraints' => [new Callback([$this, 'validateCertificateTypesUniqueness'])],
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            // Not used hasXXX fields will/were removed during PRE_SUBMIT
            // Now code below sets not used hasXXX to false to make hasXXX sequence valid
            // Forcefully set fields to false that has to be false
            $deviceType = $event->getData();

            if (!$deviceType->getHasConfig1()) {
                $deviceType->setHasAlwaysReinstallConfig1(false);
            }

            if (!$deviceType->getHasConfig2()) {
                $deviceType->setHasAlwaysReinstallConfig2(false);
            }

            if (!$deviceType->getHasConfig3()) {
                $deviceType->setHasAlwaysReinstallConfig3(false);
            }

            if (!$deviceType->getHasVariables()) {
                $deviceType->setHasCertificates(false);
            }

            if (!$deviceType->getHasCertificates()) {
                $deviceType->setCertificateTypes(new ArrayCollection());
            }

            if (!$this->hasDeviceTypeDeviceVpnCertificate($deviceType)) {
                $deviceType->setHasVpn(false);
                $deviceType->setHasDeviceToNetworkConnection(false);
            }

            if (!$deviceType->getHasVpn()) {
                $deviceType->setHasEndpointDevices(false);
                $deviceType->setHasMasquerade(false);
            }

            $event->setData($deviceType);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $shaper = new FormShaper($event);

            $communicationProcedureName = CommunicationProcedure::NONE->value;

            if (isset($event->getData()['communicationProcedure'])) {
                $communicationProcedureName = $event->getData()['communicationProcedure'];
            } else {
                $normalizedData = $event->getForm()->getNormData();
                if ($normalizedData && \method_exists($normalizedData, 'getCommunicationProcedure')) {
                    if ($normalizedData->getCommunicationProcedure()) {
                        $communicationProcedureName = $normalizedData->getCommunicationProcedure()->value;
                    }
                }
            }

            $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByName($communicationProcedureName);
            foreach (CommunicationProcedureRequirement::cases() as $requirement) {
                $functionName = 'get'.ucfirst($requirement->value);
                if (!in_array($requirement, $communicationProcedure->getCommunicationProcedureRequirementsRequired()) &&
                        !in_array($requirement, $communicationProcedure->getCommunicationProcedureRequirementsOptional())) {
                    // Field is not used by communication procedure and should be removed from form
                    // Used and optional fields will be validated by DeviceTypeValidator
                    $shaper->removeField($requirement->value);
                }
            }

            if (
                in_array(
                    $communicationProcedureName,
                    [
                        CommunicationProcedure::NONE->value,
                        CommunicationProcedure::NONE_SCEP->value,
                        CommunicationProcedure::NONE_VPN->value,
                    ]
                )) {
                // if empty communication procedure not used fields will be removed
                $shaper->removeField('enableConfigLogs');
                $shaper->removeField('routePrefix');
                $shaper->removeField('authenticationMethod');
                $shaper->removeField('credentialsSource');
                $shaper->removeField('deviceTypeSecretCredential');
                $shaper->removeField('deviceTypeCertificateTypeCredential');
                $shaper->removeField('enableConnectionAggregation');
            }

            if (!$shaper->isFieldValueTrue('hasVariables')) {
                $shaper->removeField('hasCertificates');
            }

            if (!$shaper->isFieldValueTrue('hasCertificates')) {
                $shaper->removeField('certificateTypes');
            }

            if (!$this->hasFormDataDeviceVpnCertificate($event->getData())) {
                $shaper->removeField('hasVpn');
                $shaper->removeField('hasDeviceToNetworkConnection');
            }

            if (!$shaper->isFieldValueTrue('hasVpn')) {
                $shaper->removeField('hasEndpointDevices');
                $shaper->removeField('hasMasquerade');
            }

            if (!$shaper->isFieldValueTrue('hasFirmware1') && !$shaper->isFieldValueTrue('hasFirmware2') && !$shaper->isFieldValueTrue('hasFirmware3')) {
                $shaper->removeField('enableFirmwareMinRsrp');
            }

            if (!$shaper->isFieldValueTrue('hasConfig1') && !$shaper->isFieldValueTrue('hasConfig2') && !$shaper->isFieldValueTrue('hasConfig3')) {
                $shaper->removeField('enableConfigMinRsrp');
            }

            if (!$shaper->isFieldValueTrue('hasFirmware1')) {
                $shaper->removeField('nameFirmware1');
                $shaper->removeField('customUrlFirmware1');
            }

            if (!$shaper->isFieldValueTrue('hasFirmware2')) {
                $shaper->removeField('nameFirmware2');
                $shaper->removeField('customUrlFirmware2');
            }

            if (!$shaper->isFieldValueTrue('hasFirmware3')) {
                $shaper->removeField('nameFirmware3');
                $shaper->removeField('customUrlFirmware3');
            }

            if (!$shaper->isFieldValueTrue('hasConfig1')) {
                $shaper->removeField('hasAlwaysReinstallConfig1');
                $shaper->removeField('nameConfig1');
                $shaper->removeField('formatConfig1');
            }

            if (!$shaper->isFieldValueTrue('hasConfig2')) {
                $shaper->removeField('hasAlwaysReinstallConfig2');
                $shaper->removeField('nameConfig2');
                $shaper->removeField('formatConfig2');
            }

            if (!$shaper->isFieldValueTrue('hasConfig3')) {
                $shaper->removeField('hasAlwaysReinstallConfig3');
                $shaper->removeField('nameConfig3');
                $shaper->removeField('formatConfig3');
            }

            if (!$shaper->isFieldValueTrue('hasEndpointDevices')) {
                $shaper->removeField('virtualSubnetCidr');
            }

            if (!$shaper->isFieldValueTrue('hasMasquerade')) {
                $shaper->removeField('masqueradeType');
            }

            if (!$shaper->isFieldValueTrue('hasDeviceCommands')) {
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
            'data_class' => DeviceType::class,
            'csrf_protection' => false,
            'validation_groups' => [
                'Default',
                'deviceType:common',
            ],
        ]);
    }

    protected function hasFormDataDeviceVpnCertificate(array $formData): bool
    {
        if (!isset($formData['certificateTypes'])) {
            return false;
        }
        if (!is_array($formData['certificateTypes'])) {
            return false;
        }

        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            return false;
        }

        foreach ($formData['certificateTypes'] as $pkiCertificate) {
            if (isset($pkiCertificate['certificateType']) && $pkiCertificate['certificateType'] == $deviceVpnCertificateType->getId()) {
                return true;
            }
        }

        return false;
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
