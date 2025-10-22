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

namespace App\Service\Helper;

use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

trait TranslatorTrait
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    #[Required]
    public function setTranslatorInterface(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    protected function trans($label, $parameters = [], $domain = null, bool $adjustParameterNames = false)
    {
        if ($adjustParameterNames) {
            $adjustedParameters = [];

            foreach ($parameters as $key => $value) {
                $adjustedParameters['{{ '.$key.' }}'] = $value;
            }

            $parameters = $adjustedParameters;
        }

        return $this->translator->trans($label, $parameters, $domain);
    }
}
