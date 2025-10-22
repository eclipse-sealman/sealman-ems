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

use App\Entity\AccessTag;
use Doctrine\Common\Collections\Collection;

interface AccessTagsInterface
{
    public function getAccessTags(): Collection;

    public function setAccessTags(Collection $accessTags);

    public function addAccessTag(AccessTag $accessTag);

    public function removeAccessTag(AccessTag $accessTag);
}
