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

namespace App\Validator\Constraints\Trait;

use App\Entity\Traits\InjectedAccessTagsInterface;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\UserTrait;

trait AccessTagsValidatorTrait
{
    use AuthorizationCheckerTrait;
    use UserTrait;

    protected function validateAccessTags($protocol): bool
    {
        if (!$protocol instanceof InjectedAccessTagsInterface) {
            throw new \Exception('validateAccessTags supports only object that implements '.InjectedAccessTagsInterface::class);
        }

        $count = 0;

        foreach ($protocol->getAccessTags() as $accessTag) {
            // Read about injected access tags in App\Form\Type\AccessTagsType
            if ($protocol->getInjectedAccessTags()->contains($accessTag)) {
                continue;
            }

            if (!$this->getUser()->getAccessTags()->contains($accessTag) && !$this->getUser()->getRadiusUserAllDevicesAccess()) {
                $this->context->buildViolation('validation.endpointDevice.invalidAccessTag')->atPath('accessTags')->addViolation();

                return false;
            }

            ++$count;
        }

        if (0 === $count) {
            $this->context->buildViolation('validation.endpointDevice.oneAccessTagRequired')->atPath('accessTags')->addViolation();

            return false;
        }

        return true;
    }
}
