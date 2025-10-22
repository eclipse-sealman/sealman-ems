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

use App\Entity\User;
use Doctrine\Common\Collections\Collection;

interface TemplateComponentInterface
{
    public function getCreatedBy(): ?User;

    public function setCreatedBy(?User $createdBy);

    public function getUpdatedBy(): ?User;

    public function setUpdatedBy(?User $updatedBy);

    public function getTemplates1(): Collection;

    public function setTemplates1(Collection $templates1);

    public function getTemplates2(): Collection;

    public function setTemplates2(Collection $templates2);

    public function getTemplates3(): Collection;

    public function setTemplates3(Collection $templates3);
}
