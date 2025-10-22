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

use App\Validator\Constraints\VariableName;
use Carve\ApiBundle\Form\BatchQueryType;
use Carve\ApiBundle\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormBuilderInterface;

class BatchVariableAddType extends BatchQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('name', null, [
            'mapped' => false,
            'constraints' => [
                new NotBlank(),
                new VariableName(),
            ],
            // Mark this as required for OpenAPI documentation
            'required' => true,
            'documentation' => [
                'description' => 'Variable name',
            ],
        ]);

        $builder->add('variableValue', null, [
            'mapped' => false,
            'trim' => false,
            'constraints' => [
                new NotBlank(),
            ],
            // Mark this as required for OpenAPI documentation
            'required' => true,
            'documentation' => [
                'description' => 'Variable value',
            ],
        ]);
    }
}
