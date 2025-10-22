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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SecretValueValidator extends ConstraintValidator
{
    public function validate($deviceSecret, Constraint $constraint): void
    {
        if (!$deviceSecret) {
            return;
        }
        if (!$deviceSecret->getDeviceTypeSecret()) {
            return;
        }

        $lowercaseSet = mb_str_split('abcdefghjkmnpqrstuvwxyz');
        $uppercaseSet = mb_str_split('ABCDEFGHJKMNPQRSTUVWXYZ');
        $digitsSet = mb_str_split('123456789');
        $specialCharsSet = mb_str_split('!@#$%^&*?()-+_=;\'":[]{},./<>?\|');

        $minimumLength = $deviceSecret->getDeviceTypeSecret()->getSecretMinimumLength();
        if ($minimumLength > 0 && mb_strlen($deviceSecret->getSecretValue()) < $minimumLength) {
            $this->context->buildViolation($constraint->messageSecretValueMinimumLengthRequirementFailed)
            ->setParameter('requiredAmount', $minimumLength)
            ->atPath('secretValue')->addViolation();

            return;
        }

        $secretValueSet = mb_str_split($deviceSecret->getSecretValue());

        $secretLowercaseCharsAmount = $deviceSecret->getDeviceTypeSecret()->getSecretLowercaseLettersAmount();
        if ($secretLowercaseCharsAmount > 0 && $secretLowercaseCharsAmount > count(\array_intersect($secretValueSet, $lowercaseSet))) {
            $this->context->buildViolation($constraint->messageSecretValueLowercaseRequirementFailed)
            ->setParameter('requiredAmount', $secretLowercaseCharsAmount)
            ->atPath('secretValue')->addViolation();

            return;
        }

        $secretUppercaseCharsAmount = $deviceSecret->getDeviceTypeSecret()->getSecretUppercaseLettersAmount();
        if ($secretUppercaseCharsAmount > 0 && $secretUppercaseCharsAmount > count(\array_intersect($secretValueSet, $uppercaseSet))) {
            $this->context->buildViolation($constraint->messageSecretValueUppercaseRequirementFailed)
            ->setParameter('requiredAmount', $secretUppercaseCharsAmount)
            ->atPath('secretValue')->addViolation();

            return;
        }

        $secretDigitsAmount = $deviceSecret->getDeviceTypeSecret()->getSecretDigitsAmount();
        if ($secretDigitsAmount > 0 && $secretDigitsAmount > count(\array_intersect($secretValueSet, $digitsSet))) {
            $this->context->buildViolation($constraint->messageSecretValueDigitRequirementFailed)
            ->setParameter('requiredAmount', $secretDigitsAmount)
            ->atPath('secretValue')->addViolation();

            return;
        }

        $secretSpecialCharsAmount = $deviceSecret->getDeviceTypeSecret()->getSecretSpecialCharactersAmount();
        if ($secretSpecialCharsAmount > 0 && $secretSpecialCharsAmount > count(\array_intersect($secretValueSet, $specialCharsSet))) {
            $this->context->buildViolation($constraint->messageSecretValueSpecialCharRequirementFailed)
            ->setParameter('requiredAmount', $secretSpecialCharsAmount)
            ->atPath('secretValue')->addViolation();

            return;
        }
    }
}
