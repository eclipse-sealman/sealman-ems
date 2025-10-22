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

class FirewallRuleConfigurationCollection extends \IteratorIterator
{
    public function __construct(FirewallRuleConfiguration ...$firewallRuleConfigurationCollection)
    {
        parent::__construct(new \ArrayIterator($firewallRuleConfigurationCollection));
    }

    public function current(): FirewallRuleConfiguration
    {
        return parent::current();
    }

    public function add(FirewallRuleConfiguration $firewallRuleConfiguration): void
    {
        $this->getInnerIterator()->append($firewallRuleConfiguration);
    }

    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    // Searches for firewall rule with provided  $sourceIp and $destinationIp. Returns firewallRuleConfiguration or null if not found
    public function searchFirewallRuleByIp(string $sourceIp, string $destinationIp): ?FirewallRuleConfiguration
    {
        foreach ($this as $firewallRule) {
            if ($firewallRule->getSourceIp() === $sourceIp && $firewallRule->getDestinationIp() === $destinationIp) {
                return $firewallRule;
            }
        }

        return null;
    }

    // Searches for firewall rule with provided  $sourceIp and $destinationIp. Returns firewallRuleConfiguration or null if not found
    public function searchFirewallRuleByIdentifier(string $ruleIdentifier): ?FirewallRuleConfiguration
    {
        foreach ($this as $firewallRule) {
            if ($firewallRule->getRuleIdentifier() === $ruleIdentifier) {
                return $firewallRule;
            }
        }

        return null;
    }
}
