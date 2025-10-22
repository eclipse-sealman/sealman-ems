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

namespace App\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;

trait InjectedAccessTagsTrait
{
    /**
     * Injected access tags used by App\Form\Type\AccessTagsType.
     */
    private ?ArrayCollection $injectedAccessTags = null;

    public function getInjectedAccessTags(): ArrayCollection
    {
        if (null === $this->injectedAccessTags) {
            $this->injectedAccessTags = new ArrayCollection();
        }

        return $this->injectedAccessTags;
    }

    public function setInjectedAccessTags(ArrayCollection $injectedAccessTags)
    {
        $this->injectedAccessTags = $injectedAccessTags;
    }
}
