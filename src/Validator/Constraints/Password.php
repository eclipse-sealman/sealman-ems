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
    public ?User $user = null;

    public string $messagePasswordMinimumLengthRequirementFailed = 'validation.password.minimumLengthRequirementFailed';
    public string $messagePasswordDigitMissing = 'validation.password.digitMissing';
    public string $messagePasswordSmallCharMissing = 'validation.password.smallCharMissing';
    public string $messagePasswordBigCharMissing = 'validation.password.bigCharMissing';
    public string $messagePasswordSpecialCharMissing = 'validation.password.specialCharMissing';
    public string $messagePasswordRecentlyUsed = 'validation.password.recentlyUsed';

    #[HasNamedArguments]
    public function __construct(
        ?User $user = null,
        ?string $messagePasswordMinimumLengthRequirementFailed = null,
        ?string $messagePasswordDigitMissing = null,
        ?string $messagePasswordSmallCharMissing = null,
        ?string $messagePasswordBigCharMissing = null,
        ?string $messagePasswordSpecialCharMissing = null,
        ?string $messagePasswordRecentlyUsed = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->user = $user ?? $this->user;
        $this->messagePasswordMinimumLengthRequirementFailed = $messagePasswordMinimumLengthRequirementFailed ?? $this->messagePasswordMinimumLengthRequirementFailed;
        $this->messagePasswordDigitMissing = $messagePasswordDigitMissing ?? $this->messagePasswordDigitMissing;
        $this->messagePasswordSmallCharMissing = $messagePasswordSmallCharMissing ?? $this->messagePasswordSmallCharMissing;
        $this->messagePasswordBigCharMissing = $messagePasswordBigCharMissing ?? $this->messagePasswordBigCharMissing;
        $this->messagePasswordSpecialCharMissing = $messagePasswordSpecialCharMissing ?? $this->messagePasswordSpecialCharMissing;
        $this->messagePasswordRecentlyUsed = $messagePasswordRecentlyUsed ?? $this->messagePasswordRecentlyUsed;
    }
}
