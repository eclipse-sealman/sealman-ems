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

namespace App\Model;

use App\Enum\FieldRequirement;

class FieldRequirementsModel implements FieldRequirementsInterface
{
    /**
     * How serialNumber property is validated for device in this Device Type.
     */
    private ?FieldRequirement $fieldSerialNumber = FieldRequirement::UNUSED;

    /**
     * How imsi property is validated for device in this Device Type.
     */
    private ?FieldRequirement $fieldImsi = FieldRequirement::UNUSED;

    /**
     * How model property is validated for device in this Device Type.
     */
    private ?FieldRequirement $fieldModel = FieldRequirement::UNUSED;

    /**
     * How registrationId property is validated for device in this Device Type.
     */
    private ?FieldRequirement $fieldRegistrationId = FieldRequirement::UNUSED;

    /**
     * How endorsementKey property is validated for device in this Device Type.
     */
    private ?FieldRequirement $fieldEndorsementKey = FieldRequirement::UNUSED;

    /**
     * How hardwareVersion property is validated for device in this Device Type.
     */
    private ?FieldRequirement $fieldHardwareVersion = FieldRequirement::UNUSED;

    public function getFieldSerialNumber(): ?FieldRequirement
    {
        return $this->fieldSerialNumber;
    }

    public function setFieldSerialNumber(?FieldRequirement $fieldSerialNumber)
    {
        $this->fieldSerialNumber = $fieldSerialNumber;
    }

    public function getFieldImsi(): ?FieldRequirement
    {
        return $this->fieldImsi;
    }

    public function setFieldImsi(?FieldRequirement $fieldImsi)
    {
        $this->fieldImsi = $fieldImsi;
    }

    public function getFieldModel(): ?FieldRequirement
    {
        return $this->fieldModel;
    }

    public function setFieldModel(?FieldRequirement $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    public function getFieldRegistrationId(): ?FieldRequirement
    {
        return $this->fieldRegistrationId;
    }

    public function setFieldRegistrationId(?FieldRequirement $fieldRegistrationId)
    {
        $this->fieldRegistrationId = $fieldRegistrationId;
    }

    public function getFieldEndorsementKey(): ?FieldRequirement
    {
        return $this->fieldEndorsementKey;
    }

    public function setFieldEndorsementKey(?FieldRequirement $fieldEndorsementKey)
    {
        $this->fieldEndorsementKey = $fieldEndorsementKey;
    }

    public function getFieldHardwareVersion(): ?FieldRequirement
    {
        return $this->fieldHardwareVersion;
    }

    public function setFieldHardwareVersion(?FieldRequirement $fieldHardwareVersion)
    {
        $this->fieldHardwareVersion = $fieldHardwareVersion;
    }
}
