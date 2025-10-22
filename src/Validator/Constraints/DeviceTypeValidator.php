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

namespace App\Validator\Constraints;

use App\Entity\DeviceType;
use App\Enum\AuthenticationMethod;
use App\Enum\CertificateEntity;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\CredentialsSource;
use App\Enum\FieldRequirement;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DeviceTypeValidator extends ConstraintValidator
{
    use CertificateTypeHelperTrait;
    use DeviceCommunicationFactoryTrait;
    use EntityManagerTrait;

    public array $reservedRoutePrefixes = [];

    public function __construct(array $reservedRoutePrefixes)
    {
        $this->reservedRoutePrefixes = $reservedRoutePrefixes;
    }

    public function validate($deviceType, Constraint $constraint): void
    {
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);
        if ($communicationProcedure) {
            // This should always be true in this case
            foreach (CommunicationProcedureRequirement::cases() as $requirement) {
                $functionName = 'get'.ucfirst($requirement->value);
                if (in_array($requirement, $communicationProcedure->getCommunicationProcedureRequirementsRequired())) {
                    // Required field is false
                    if (!$deviceType->$functionName()) {
                        $this->context->buildViolation($constraint->messageRequiredField)->atPath($requirement->value)->addViolation();
                    }
                } elseif (!in_array($requirement, $communicationProcedure->getCommunicationProcedureRequirementsOptional())) {
                    // Unused field is true
                    if ($deviceType->$functionName()) {
                        $this->context->buildViolation($constraint->messageNotUsedField)->atPath($requirement->value)->addViolation();
                    }
                }
            }

            // Validate if properties with requirements have equal or more requirements than communication procedure requires

            $communicationFieldRequirements = $communicationProcedure->getCommunicationProcedureFieldsRequirements();

            foreach ($communicationProcedure->getProperiesWithFieldRequirements() as $property) {
                $getFunctionName = 'get'.ucfirst($property);

                if (FieldRequirement::REQUIRED == $communicationFieldRequirements->$getFunctionName()) {
                    if (FieldRequirement::REQUIRED !== $deviceType->$getFunctionName()) {
                        $this->context->buildViolation($constraint->messagePropertyRequired)->atPath($property)->addViolation();
                    }
                }

                if (FieldRequirement::REQUIRED_IN_COMMUNICATION == $communicationFieldRequirements->$getFunctionName()) {
                    if (FieldRequirement::REQUIRED !== $deviceType->$getFunctionName() && FieldRequirement::REQUIRED_IN_COMMUNICATION !== $deviceType->$getFunctionName()) {
                        $this->context->buildViolation($constraint->messagePropertyRequiredInCommunication)->atPath($property)->addViolation();
                    }
                }
            }

            if ($deviceType->getHasCertificates()) {
                // Validate if certificate categories requirements according to communication procedure

                $communicationCertificateCategoryRequired = $communicationProcedure->getCommunicationProcedureCertificateCategoryRequired();

                foreach ($communicationCertificateCategoryRequired as $certificateCategory) {
                    $found = false;
                    foreach ($deviceType->getCertificateTypes() as $deviceTypeCertificateType) {
                        if (!$deviceTypeCertificateType->getCertificateType()) {
                            continue;
                        }
                        if ($deviceTypeCertificateType->getCertificateType()->getCertificateCategory() == $certificateCategory) {
                            $found = true;
                        }
                    }
                    // this should never happen in frontend form
                    if (!$found) {
                        $this->context->buildViolation($constraint->messageCertificateCategoryRequired)->atPath('certificateTypes')->addViolation();
                    }
                }

                // Validate if certificate can be enabled with this device type
                $communicationCertificateCategoryOptional = $communicationProcedure->getCommunicationProcedureCertificateCategoryOptional();

                $communicationCertificateCategoryAvailable = \array_merge($communicationCertificateCategoryRequired, $communicationCertificateCategoryOptional);

                foreach ($deviceType->getCertificateTypes() as $deviceTypeCertificateType) {
                    if (!$deviceTypeCertificateType->getCertificateType()) {
                        continue;
                    }

                    $certificateType = $deviceTypeCertificateType->getCertificateType();

                    if (!\in_array($certificateType->getCertificateCategory(), $communicationCertificateCategoryAvailable)) {
                        $this->context->buildViolation($constraint->messageCertificateInvalidCertificateCategory)->setParameter('{{ certificateType }}', $certificateType->getRepresentation())->atPath('certificateTypes')->addViolation();
                    }
                    // this is last to be shown on form validation errors
                    if (CertificateEntity::DEVICE !== $certificateType->getCertificateEntity()) {
                        $this->context->buildViolation($constraint->messageCertificateInvalidCertificateEntity)->setParameter('{{ certificateType }}', $certificateType->getRepresentation())->atPath('certificateTypes')->addViolation();
                    }
                }
            }
        }

        if (!$deviceType->getHasFirmware1() && !$deviceType->getHasFirmware2() && !$deviceType->getHasFirmware3()) {
            if ($deviceType->getEnableFirmwareMinRsrp()) {
                $this->context->buildViolation($constraint->messageFirmwareNotUsedCannotEnableMinRsrp)->atPath('enableFirmwareMinRsrp')->addViolation();
            }
        }

        if (!$deviceType->getHasConfig1() && !$deviceType->getHasConfig2() && !$deviceType->getHasConfig3()) {
            if ($deviceType->getEnableConfigMinRsrp()) {
                $this->context->buildViolation($constraint->messageConfigNotUsedCannotEnableMinRsrp)->atPath('enableConfigMinRsrp')->addViolation();
            }
        }

        if ($deviceType->getHasAlwaysReinstallConfig1() && !$deviceType->getHasConfig1()) {
            $this->context->buildViolation($constraint->messageAlwaysReinstallConfigNotAvailable)->atPath('hasAlwaysReinstallConfig1')->addViolation();
        }

        if ($deviceType->getHasAlwaysReinstallConfig2() && !$deviceType->getHasConfig2()) {
            $this->context->buildViolation($constraint->messageAlwaysReinstallConfigNotAvailable)->atPath('hasAlwaysReinstallConfig2')->addViolation();
        }

        if ($deviceType->getHasAlwaysReinstallConfig3() && !$deviceType->getHasConfig3()) {
            $this->context->buildViolation($constraint->messageAlwaysReinstallConfigNotAvailable)->atPath('hasAlwaysReinstallConfig3')->addViolation();
        }

        if ($deviceType->getHasCertificates() && !$deviceType->getHasVariables()) {
            $this->context->buildViolation($constraint->messageHasCertificatesNotAvailable)->atPath('hasCertificates')->addViolation();
        }
        if ($deviceType->getHasVpn() && !$this->hasDeviceVpnCertificate($deviceType)) {
            $this->context->buildViolation($constraint->messageHasVpnNotAvailable)->atPath('hasVpn')->addViolation();
        }
        if ($deviceType->getHasEndpointDevices() && !$deviceType->getHasVpn()) {
            $this->context->buildViolation($constraint->messageHasEndpointDevicesNotAvailable)->atPath('hasEndpointDevices')->addViolation();
        }
        if ($deviceType->getHasMasquerade() && !$deviceType->getHasVpn()) {
            $this->context->buildViolation($constraint->messageHasMasqueradesNotAvailable)->atPath('hasMasquerade')->addViolation();
        }

        if (\in_array($deviceType->getAuthenticationMethod(), [AuthenticationMethod::BASIC, AuthenticationMethod::DIGEST]) && !$deviceType->getCredentialsSource()) {
            $this->context->buildViolation($constraint->messageCredentialsSourceMissing)->atPath('credentialsSource')->addViolation();
        }

        if (\in_array($deviceType->getCredentialsSource(), [CredentialsSource::SECRET, CredentialsSource::BOTH, CredentialsSource::USER_IF_SECRET_MISSING]) &&
        !$deviceType->getDeviceTypeSecretCredential()) {
            $this->context->buildViolation($constraint->messageDeviceTypeSecretCredentialMissing)->atPath('deviceTypeSecretCredential')->addViolation();
        }
        
        if ($deviceType->getDeviceTypeSecretCredential() && $deviceType->getDeviceTypeSecretCredential()->getDeviceType() != $deviceType) {
            $this->context->buildViolation($constraint->messageDeviceTypeSecretCredentialInvalid)->atPath('deviceTypeSecretCredential')->addViolation();
        }

        if (AuthenticationMethod::X509 === $deviceType->getAuthenticationMethod() && !$deviceType->getDeviceTypeCertificateTypeCredential()) {
            $this->context->buildViolation($constraint->messageDeviceTypeCertificateTypeCredentialMissing)->atPath('deviceTypeCertificateTypeCredential')->addViolation();
        }
        if ($deviceType->getDeviceTypeCertificateTypeCredential()) {
            $found = false;
            foreach ($deviceType->getCertificateTypes() as $deviceTypeCertificateType) {
                if (!$deviceTypeCertificateType->getCertificateType()) {
                    continue;
                }
                if (!$deviceTypeCertificateType->getCertificateType()->getIsAvailable()) {
                    continue;
                }
                if ($deviceTypeCertificateType->getCertificateType()->getId() == $deviceType->getDeviceTypeCertificateTypeCredential()->getId()) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->context->buildViolation($constraint->messageDeviceTypeCertificateTypeCredentialInvalid)->atPath('deviceTypeCertificateTypeCredential')->addViolation();
            }
        }

        if ($deviceType->getRoutePrefix() && !str_starts_with($deviceType->getRoutePrefix(), '/')) {
            $this->context->buildViolation($constraint->messageRoutePrefixStart)->atPath('routePrefix')->addViolation();
        }

        if ($communicationProcedure) {
            // This should always be true in this case
            $routes = $communicationProcedure->getRoutes($deviceType);
            // loop prefix is not perfect, but we need to differentiate for better readability
            $loopDeviceTypes = $this->getRepository(DeviceType::class)->findAll();
            foreach ($loopDeviceTypes as $loopDeviceType) {
                if ($loopDeviceType->getId() === $deviceType->getId()) {
                    continue;
                }

                $loopCommunication = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($loopDeviceType);
                if ($loopCommunication) {
                    // This should always be true in this case
                    foreach ($loopCommunication->getRoutes($loopDeviceType) as $loopRoute) {
                        foreach ($routes as $route) {
                            if ($route->getPath() == $loopRoute->getPath()) {
                                $this->context->buildViolation($constraint->messageRoutePrefixUsed)->atPath('routePrefix')->addViolation();
                                break 3;
                            }
                        }
                    }
                }
            }

            // Checking against reserved route prefixes
            foreach ($routes as $route) {
                foreach ($this->reservedRoutePrefixes as $reservedRoutePrefix) {
                    if (str_starts_with($route->getPath(), $reservedRoutePrefix)) {
                        $this->context->buildViolation($constraint->messageRoutePrefixReserved)->atPath('routePrefix')->addViolation();
                        break;
                    }
                }
            }
        }
    }

    protected function hasDeviceVpnCertificate(DeviceType $deviceType): bool
    {
        if (!$deviceType->getHasCertificates()) {
            return false;
        }

        return $this->hasDeviceTypeDeviceVpnCertificate($deviceType);
    }
}
