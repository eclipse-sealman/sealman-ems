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

namespace App\DeviceCommunication\Trait;

use App\Entity\DeviceType;
use App\Model\EdgeGatewayModel;
use App\Model\ResponseModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface EdgeGatewayCommunicationInterface
{
    public function getDeviceTypeValidationGroups(DeviceType $deviceType): array;

    public function processEdgeGatewayRequest(DeviceType $deviceType, Request $request, EdgeGatewayModel $edgeGatewayModel): ResponseModel;

    public function prepareErrorResponse(DeviceType $deviceType, Request $request, FormInterface $form): Response|ResponseModel;
}
