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

namespace App\Validator\Constraints;

use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\PasswordManagerTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordValidator extends ConstraintValidator
{
    use ConfigurationManagerTrait;
    use PasswordManagerTrait;

    public function validate($value, Constraint $constraint): void
    {
        if (!$value) {
            return;
        }

        $passwordMinimumLength = $this->getConfiguration()->getPasswordMinimumLength();
        if ($passwordMinimumLength > 0 && mb_strlen($value) < $passwordMinimumLength) {
            $this->context->buildViolation($constraint->messagePasswordMinimumLengthRequirementFailed)->setParameter('passwordMinimumLength', $passwordMinimumLength)->atPath('newPassword')->addViolation();
        }

        if ($this->getConfiguration()->getPasswordDigitRequired() && !preg_match("/\d/", $value)) {
            $this->context->buildViolation($constraint->messagePasswordDigitMissing)->atPath('newPassword')->addViolation();
        }

        if ($this->getConfiguration()->getPasswordBigSmallCharRequired()) {
            if (!preg_match('/[a-z]/', $value)) {
                $this->context->buildViolation($constraint->messagePasswordSmallCharMissing)->atPath('newPassword')->addViolation();
            }

            if (!preg_match('/[A-Z]/', $value)) {
                $this->context->buildViolation($constraint->messagePasswordBigCharMissing)->atPath('newPassword')->addViolation();
            }
        }

        $specialCharString = "!\"#$%&'()*+,-./:;<=>?@[\]^_`{|}~";
        if ($this->getConfiguration()->getPasswordSpecialCharRequired() && !preg_match('/['.preg_quote($specialCharString, '/').']/', $value)) {
            $this->context->buildViolation($constraint->messagePasswordSpecialCharMissing)->atPath('newPassword')->addViolation();
        }

        $user = $constraint->user;
        if (!$user) {
            return;
        }

        if ($this->passwordManager->isPasswordCurrentlyUsed($user, $value)) {
            $this->context->buildViolation($constraint->messagePasswordRecentlyUsed)->addViolation();
        }

        if ($this->passwordManager->isPasswordRecentlyUsed($user, $value)) {
            $this->context->buildViolation($constraint->messagePasswordRecentlyUsed)->addViolation();
        }
    }
}
