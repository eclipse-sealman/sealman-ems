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

namespace App\Attribute;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted as SymfonyIsGranted;

/**
 * Grant access when user has at least one of passed roles.
 *
 * Usage:
 * use App\Attribute\IsGrantedOr;
 *
 * #[IsGrantedOr('ROLE_ADMIN')]
 * #[IsGrantedOr(['ROLE_ADMIN', 'ROLE_SMARTEMS'])]
 *
 * Replaces:
 *
 * use Symfony\Component\Security\Http\Attribute\IsGranted;
 * use Symfony\Component\ExpressionLanguage\Expression;
 *
 * #[IsGranted('ROLE_ADMIN')]
 * #[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')"))]
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class IsGrantedOr extends SymfonyIsGranted
{
    public function __construct(string|array $role)
    {
        $roles = is_array($role) ? $role : [$role];
        $IsGrantedOrExpressions = array_map(fn ($role) => "is_granted('$role')", $roles);

        parent::__construct(attribute: new Expression(implode(' or ', $IsGrantedOrExpressions)));
    }
}
