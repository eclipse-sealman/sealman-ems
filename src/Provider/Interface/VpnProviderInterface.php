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

namespace App\Provider\Interface;

use App\Provider\Model\FirewallRuleConfiguration;
use App\Provider\Model\FirewallRuleConfigurationCollection;
use App\Provider\Model\VpnConnectedClientsCollection;
use App\Provider\Model\VpnCscConfiguration;
use App\Trait\LogsCollectorInterface;

interface VpnProviderInterface extends LogsCollectorInterface
{
    public function getVpnConnectedClients(): VpnConnectedClientsCollection;

    public function updateVpnServerCrl(string $serverDescription, string $crlContentPem): void;

    public function getVpnServerNameByDescription(string $serverDescription): string;

    public function isCscInVpnServer(string $cscCommonName): bool;

    public function deleteCscInVpnServer(string $cscCommonName): void;

    public function addCscInVpnServer(VpnCscConfiguration $vpnCscConfiguration): void;

    public function getVpnCscConfigurationHash(VpnCscConfiguration $vpnCscConfiguration): string;

    public function getFirewallRules(): FirewallRuleConfigurationCollection;

    public function addFirewallRule(FirewallRuleConfiguration $firewallRuleConfiguration): string;

    public function deleteFirewallRule(FirewallRuleConfiguration $firewallRuleConfiguration): void;
}
