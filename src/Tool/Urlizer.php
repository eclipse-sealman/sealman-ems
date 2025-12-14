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

namespace App\Tool;

use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Class replaces deprecated Gedmo\Sluggable\Util\Urlizer which was essentially Behat\Transliterator\Transliterator
 * "behat/transliterator" package has been deprecated and abandoned.
 */
class Urlizer
{
    public static function urlize(string $text, string $separator = '-'): string
    {
        return (new AsciiSlugger())
            ->slug($text, $separator)
            ->lower()
            ->toString()
        ;
    }
}
