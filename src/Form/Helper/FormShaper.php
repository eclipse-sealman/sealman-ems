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

namespace App\Form\Helper;

use Symfony\Component\Form\FormEvent;

class FormShaper
{
    /**
     * @var FormEvent
     */
    public $event;

    public function __construct(FormEvent $event)
    {
        $this->event = $event;
    }

    public function hasFieldValue(string $name): bool
    {
        if (!$this->event->getForm()->has($name)) {
            return false;
        }

        if (isset($this->event->getData()[$name])) {
            return true;
        }

        return false;
    }

    public function getFieldValue(string $name)
    {
        if (!$this->hasFieldValue($name)) {
            throw new \Exception('Cannot get field value. Field "'.$name.'" is missing in form');
        }

        return $this->event->getData()[$name];
    }

    public function isFieldValueTrue(string $name): bool
    {
        if (!$this->hasFieldValue($name)) {
            return false;
        }

        if ($this->event->getData()[$name]) {
            return true;
        }

        return false;
    }

    public function isFieldValueEqual(string $name, $value): bool
    {
        if (!$this->hasFieldValue($name)) {
            return false;
        }

        if ($this->event->getData()[$name] === $value) {
            return true;
        }

        return false;
    }

    public function removeField(string $name): void
    {
        if ($this->event->getForm()->has($name)) {
            $this->event->getForm()->remove($name);
        }
    }
}
