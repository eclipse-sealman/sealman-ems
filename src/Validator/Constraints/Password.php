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

use App\Entity\User;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Password extends Constraint
{
    public $messagePasswordMinimumLengthRequirementFailed = 'validation.password.minimumLengthRequirementFailed';
    public $messagePasswordDigitMissing = 'validation.password.digitMissing';
    public $messagePasswordSmallCharMissing = 'validation.password.smallCharMissing';
    public $messagePasswordBigCharMissing = 'validation.password.bigCharMissing';
    public $messagePasswordSpecialCharMissing = 'validation.password.specialCharMissing';
    public $messagePasswordRecentlyUsed = 'validation.password.recentlyUsed';

    public null|User $user = null;

    #[HasNamedArguments]
    public function __construct(
        null|User $user = null,
        null|string $messagePasswordMinimumLengthRequirementFailed = null,
        null|string $messagePasswordDigitMissing = null,
        null|string $messagePasswordSmallCharMissing = null,
        null|string $messagePasswordBigCharMissing = null,
        null|string $messagePasswordSpecialCharMissing = null,
        null|string $messagePasswordRecentlyUsed = null,
        null|array $groups = null,
        $payload = null)
    {
        $options = array_filter([
            'messagePasswordMinimumLengthRequirementFailed' => $messagePasswordMinimumLengthRequirementFailed ?? $this->messagePasswordMinimumLengthRequirementFailed,
            'messagePasswordDigitMissing' => $messagePasswordDigitMissing ?? $this->messagePasswordDigitMissing,
            'messagePasswordSmallCharMissing' => $messagePasswordSmallCharMissing ?? $this->messagePasswordSmallCharMissing,
            'messagePasswordBigCharMissing' => $messagePasswordBigCharMissing ?? $this->messagePasswordBigCharMissing,
            'messagePasswordSpecialCharMissing' => $messagePasswordSpecialCharMissing ?? $this->messagePasswordSpecialCharMissing,
            'messagePasswordRecentlyUsed' => $messagePasswordRecentlyUsed ?? $this->messagePasswordRecentlyUsed,
            'user' => $user ?? $this->user,
        ]);

        parent::__construct($options, $groups, $payload);
    }
}
