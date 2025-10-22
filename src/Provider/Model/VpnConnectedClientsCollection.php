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

namespace App\Provider\Model;

use App\Model\VpnConnectedClientsModel;

class VpnConnectedClientsCollection extends \IteratorIterator
{
    public function __construct(VpnConnectedClientsModel ...$vpnConnectedClientsModels)
    {
        parent::__construct(new \ArrayIterator($vpnConnectedClientsModels));
    }

    public function current(): VpnConnectedClientsModel
    {
        return parent::current();
    }

    public function add(VpnConnectedClientsModel $vpnConnectedClientsModel): void
    {
        $this->getInnerIterator()->append($vpnConnectedClientsModel);
    }

    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }
}
