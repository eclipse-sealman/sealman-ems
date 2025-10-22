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

use App\Entity\Label;
use Carve\ApiBundle\Form\BatchQueryType;
use Carve\ApiBundle\Validator\Constraints\Count;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

class BatchLabelsType extends BatchQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('labels', EntityType::class, [
            'mapped' => false,
            'multiple' => true,
            'class' => Label::class,
            'constraints' => [
                new Count(['min' => 1]),
            ],
        ]);
    }
}
