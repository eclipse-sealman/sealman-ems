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

use App\Service\UploadManager;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class TusX509CheckPrivateKeyValidator extends ConstraintValidator
{
    private $propertyAccessor;

    public function validate($value, Constraint $constraint): void
    {
        if (!$value) {
            return;
        }

        $path = $constraint->propertyPath;
        $object = $this->context->getObject();

        if (null === $object) {
            return;
        }

        try {
            $propertyPathValue = $this->getPropertyAccessor()->getValue($object, $path);
        } catch (NoSuchPropertyException $e) {
            throw new ConstraintDefinitionException(sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, get_debug_type($constraint)).$e->getMessage(), 0, $e);
        }

        if (!$propertyPathValue) {
            return;
        }

        try {
            $x509Content = $this->getTusFileContent($value);
            $privateKeyContent = $this->getTusFileContent($propertyPathValue);

            if (null === $x509Content || null === $privateKeyContent) {
                $this->context->buildViolation($constraint->messageInvalid)->addViolation();

                return;
            }

            $check = \openssl_x509_check_private_key($x509Content, $privateKeyContent);
            if (false === $check) {
                $this->context->buildViolation($constraint->messageInvalid)->addViolation();
            }
        } catch (\Throwable $e) {
            $this->context->buildViolation($constraint->messageInvalid)->addViolation();

            return;
        }
    }

    private function getTusFileContent(string $value): ?string
    {
        $tusFile = UploadManager::getTusFile($value);
        $tusFile = $tusFile['file_path'] ?? null;
        if (!$tusFile) {
            return null;
        }

        $fileContent = \file_get_contents($tusFile);
        if (!$fileContent) {
            return null;
        }

        return $fileContent;
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
