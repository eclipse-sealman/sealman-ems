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

namespace App\DeviceCommunication;

use App\Entity\DeviceType;
use App\Enum\CertificateCategory;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\FieldRequirement;
use App\Model\FieldRequirementsModel;
use App\Model\VariableInterface;
use Symfony\Component\Routing\RouteCollection;

class EmptyScepCommunication extends AbstractDeviceCommunication
{
    public function getRoutes(DeviceType $deviceType): RouteCollection
    {
        return new RouteCollection();
    }

    public function getCommunicationProcedureRequirementsOptional(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_TEMPLATES,
        ];
    }

    public function getCommunicationProcedureRequirementsRequired(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_VARIABLES,
            CommunicationProcedureRequirement::HAS_CERTIFICATES,
        ];
    }

    public function getCommunicationProcedureFieldsRequirements(): FieldRequirementsModel
    {
        $fieldRequirements = new FieldRequirementsModel();

        $fieldRequirements->setFieldSerialNumber(FieldRequirement::OPTIONAL);
        $fieldRequirements->setFieldModel(FieldRequirement::OPTIONAL);

        return $fieldRequirements;
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getCommunicationProcedureCertificateCategoryRequired
     */
    public function getCommunicationProcedureCertificateCategoryRequired(): array
    {
        return [];
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getCommunicationProcedureCertificateCategoryOptional
     */
    public function getCommunicationProcedureCertificateCategoryOptional(): array
    {
        return [
            CertificateCategory::CUSTOM,
        ];
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getOrderedListOfPredefinedVariablesNames
     */
    protected function getOrderedListOfPredefinedVariablesNames(): ?array
    {
        if (!$this->getDeviceType()) {
            return [];
        }

        if ($this->getDeviceType()->getHasVariables()) {
            $variableNames = [
                VariableInterface::VARIABLE_NAME_SERIALNUMBER,
                VariableInterface::VARIABLE_NAME_NAME,
            ];

            if ($this->getDeviceType()->getHasCertificates()) {
                $certificateVariableNames = $this->getDeviceTypeCertificateVariableNames();

                $variableNames = array_merge($variableNames, $certificateVariableNames);
            }

            return $variableNames;
        }

        return [];
    }
}
