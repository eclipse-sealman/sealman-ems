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

namespace Tests\Utilities\Mock\VpnProvider;

use App\Model\VpnConnectedClientsModel;
use App\Provider\Interface\VpnProviderInterface;
use App\Provider\Model\FirewallRuleConfiguration;
use App\Provider\Model\FirewallRuleConfigurationCollection;
use App\Provider\Model\VpnConnectedClientsCollection;
use App\Provider\Model\VpnCscConfiguration;
use App\Trait\LogsCollectorTrait;

class MockVpnProvider implements VpnProviderInterface
{
    use LogsCollectorTrait;

    /**
     * Class is initialized multiple times. Keep static values inside to avoid clearing them between initializations.
     */
    public static array $cscCommonNames = [];
    public static ?FirewallRuleConfigurationCollection $firewallRules = null;
    public static ?VpnConnectedClientsCollection $connectedClients = null;

    public function __construct(
        public bool $autoConnectClients = true,
    ) {
        if (null === static::$firewallRules) {
            static::$firewallRules = new FirewallRuleConfigurationCollection();
        }
        if (null === static::$connectedClients) {
            static::$connectedClients = new VpnConnectedClientsCollection();
        }
    }

    public function getVpnConnectedClients(): VpnConnectedClientsCollection
    {
        return static::$connectedClients;
    }

    public function updateVpnServerCrl(string $serverDescription, string $crlContentPem): void
    {
        // Nothing to do. Assume that CRL has been updated successfully.
    }

    public function getVpnServerNameByDescription(string $serverDescription): string
    {
        return 'Mock VPN Server';
    }

    public function isCscInVpnServer(string $cscCommonName): bool
    {
        if (\in_array($cscCommonName, static::$cscCommonNames, true)) {
            return true;
        }

        return false;
    }

    public function deleteCscInVpnServer(string $cscCommonName): void
    {
        static::$cscCommonNames = array_filter(static::$cscCommonNames, function ($name) use ($cscCommonName) {
            return $name !== $cscCommonName;
        });

        if ($this->autoConnectClients) {
            $connectedClients = new VpnConnectedClientsCollection();

            foreach (static::$connectedClients as $connectedClient) {
                if ($connectedClient->getCommonName() !== $cscCommonName) {
                    $connectedClients->add($connectedClient);
                }
            }

            static::$connectedClients = $connectedClients;
        }
    }

    public function addCscInVpnServer(VpnCscConfiguration $vpnCscConfiguration): void
    {
        static::$cscCommonNames[] = $vpnCscConfiguration->getCscCommonName();

        if ($this->autoConnectClients) {
            list($vpnIp) = explode('/', $vpnCscConfiguration->getTunnelNetwork());

            $connectedClient = new VpnConnectedClientsModel();
            $connectedClient->setCommonName($vpnCscConfiguration->getCscCommonName());
            $connectedClient->setVpnIp($vpnIp);

            static::$connectedClients->add($connectedClient);
        }
    }

    public function getVpnCscConfigurationHash(VpnCscConfiguration $vpnCscConfiguration): string
    {
        $data = [
            'common_name' => $vpnCscConfiguration->getCscCommonName(),
            'ovpn_servers' => $vpnCscConfiguration->getCscServerName(),
            'tunnel_network' => $vpnCscConfiguration->getTunnelNetwork(),
        ];

        if (strlen($vpnCscConfiguration->getCommaDelimitedRemoteNetworks()) > 0) {
            $data['remote_network'] = $vpnCscConfiguration->getCommaDelimitedRemoteNetworks();
        }

        return \md5(\json_encode($data));
    }

    public function getFirewallRules(): FirewallRuleConfigurationCollection
    {
        // Implement when needed
        return new FirewallRuleConfigurationCollection();
    }

    public function addFirewallRule(FirewallRuleConfiguration $firewallRuleConfiguration): string
    {
        // Implement when needed
        return 'rule-identifier';
    }

    public function deleteFirewallRule(FirewallRuleConfiguration $firewallRuleConfiguration): void
    {
        // Implement when needed
    }
}
