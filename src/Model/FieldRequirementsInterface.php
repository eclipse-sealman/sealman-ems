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

interface FieldRequirementsInterface
{
    public function getFieldSerialNumber(): ?FieldRequirement;

    public function setFieldSerialNumber(?FieldRequirement $fieldSerialNumber);

    public function getFieldImsi(): ?FieldRequirement;

    public function setFieldImsi(?FieldRequirement $fieldImsi);

    public function getFieldModel(): ?FieldRequirement;

    public function setFieldModel(?FieldRequirement $fieldModel);

    public function getFieldRegistrationId(): ?FieldRequirement;

    public function setFieldRegistrationId(?FieldRequirement $fieldRegistrationId);

    public function getFieldEndorsementKey(): ?FieldRequirement;

    public function setFieldEndorsementKey(?FieldRequirement $fieldEndorsementKey);

    public function getFieldHardwareVersion(): ?FieldRequirement;

    public function setFieldHardwareVersion(?FieldRequirement $fieldHardwareVersion);
}
