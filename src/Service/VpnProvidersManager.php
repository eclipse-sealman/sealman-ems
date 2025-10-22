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

namespace App\Service;

use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\User;
use App\Entity\VpnConnection;
use App\Exception\LogsException;
use App\Exception\ProviderException;
use App\Provider\Interface\VpnProviderInterface;
use App\Provider\Model\FirewallRuleConfiguration;
use App\Provider\Model\FirewallRuleConfigurationCollection;
use App\Provider\Model\VpnConnectedClientsCollection;
use App\Provider\Model\VpnCscConfiguration;
use App\Provider\OpnSenseVpnProvider;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\HttpClientTrait;
use App\Service\Helper\SystemUserTrait;
use App\Service\Helper\VpnAddressManagerTrait;
use App\Service\Helper\VpnLogManagerTrait;
use App\Service\Helper\VpnManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;

// For now manager only handles OpnSenseVpnProvider - since there is no configuration differentiation no switches are implemented
class VpnProvidersManager
{
    use CertificateTypeHelperTrait;
    use ConfigurationManagerTrait;
    use VpnAddressManagerTrait;
    use VpnLogManagerTrait;
    use VpnManagerTrait;
    use HttpClientTrait;
    use SystemUserTrait;

    public function getVpnConnectedClients(): VpnConnectedClientsCollection
    {
        $provider = $this->getVpnProvider();

        try {
            $vpnConnectedClientsCollection = $provider->getVpnConnectedClients();

            $this->vpnLogManager->createLogs($provider->getLogs());
        } catch (ProviderException $providerException) {
            $this->vpnLogManager->createLogs($provider->getLogs());

            throw new LogsException($providerException);
        }

        return $vpnConnectedClientsCollection;
    }

    public function updateVpnServerCrl(Device|User $target, string $crlContentPem): void
    {
        $serverDescription = $this->getVpnServerDescription($target);

        if (!$serverDescription) {
            throw new LogsException($this->vpnLogManager->createLogError('log.vpnProviders.updateCrl.cannotUpdateCrlNoServerDescription', target: $target));
        }

        $provider = $this->getVpnProvider();

        try {
            $vpnConnectedClientsCollection = $provider->updateVpnServerCrl($serverDescription, $crlContentPem);

            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target);

            // If no exception CRL was updated
            $this->vpnLogManager->createLogInfo('log.vpnProviders.updateCrl.crlUpdated', ['serverDescription' => $serverDescription], target: $target);
        } catch (ProviderException $providerException) {
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target);

            throw new LogsException($providerException);
        }
    }

    // Method updates VPN server csc if needed and possible
    // Method requires vpn addresses present
    public function updateVpnServerCsc(Device|User $target, bool $forceCscUpdate = false): void
    {
        $provider = $this->getVpnProvider();

        try {
            // Method will get VpnServerName (Index) from Configuration or get it from OpnSense using VpnServerDescription or throw ProviderException
            $vpnServerName = $this->getVpnServerName($provider, $target);

            // Method will get generated certificate subject or throw ProviderException (if certificate doesn't exist)
            // $provider is required to collect log messages
            $cscCommonName = $this->getVpnClientCertificateSubject($provider, $target);

            // Method will get tunnelNetworkAddress or throw ProviderException
            // $provider is required to collect log messages
            $tunnelNetwork = $this->getTunnelNetworkAddress($provider, $target);

            // Method will get remoteNetworkAddresses or throw ProviderException
            $remoteNetworks = $this->getRemoteNetworkAddresses($target);

            $vpnCscConfiguration = new VpnCscConfiguration($vpnServerName, $cscCommonName, $tunnelNetwork, $remoteNetworks);

            if (!$forceCscUpdate && $this->isVpnCscConfigurationDeployed($provider, $vpnCscConfiguration, $target)) {
                $this->vpnLogManager->createLogs($provider->getLogs(), target: $target);

                return;
            }

            // Removing CSC just in case CSC exists in OpnSense - this method might throw LogsException
            $this->deleteVpnServerCsc($target, false);

            // logs has been saved by
            $provider->clearLogs();

            $provider->addCscInVpnServer($vpnCscConfiguration);

            $provider->addLogInfo(
                'log.vpnProviders.addCscSuccess',
                [
                    'commonName' => $vpnCscConfiguration->getCscCommonName(),
                    'tunnelNetwork' => $vpnCscConfiguration->getTunnelNetwork(),
                    'remoteNetwork' => $vpnCscConfiguration->hasRemoteNetworks() ? $vpnCscConfiguration->getCommaDelimitedRemoteNetworks() : 'N/A',
                ]
            );

            // Will flush $target
            $this->updateCscValues($provider, $vpnCscConfiguration, $target);

            // Handling logs
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target);
        } catch (LogsException $logsException) {
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target);

            // Logs are merged manually to keep their correct order
            $localLogsException = new LogsException($provider);
            $localLogsException->merge($logsException);
            throw $localLogsException;
        } catch (ProviderException $providerException) {
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target);

            throw new LogsException($providerException);
        }
    }

    public function deleteVpnServerCsc(Device|User $target, bool $throwIfCscNotExists = true): void
    {
        $cscCommonName = $target->getCscCertificateSubject();

        if (!$cscCommonName) {
            // CSC doesn't exist - nothing to do
            return;
        }

        $provider = $this->getVpnProvider();

        try {
            if (!$provider->isCscInVpnServer($cscCommonName)) {
                // Making sure logs are created in correct order
                $this->vpnLogManager->createLogs($provider->getLogs(), target: $target);
                $provider->addLogModel($this->vpnLogManager->createLogError('log.vpnProviders.deleteCsc.expectedCscMissing', ['cscCommonName' => $cscCommonName ?: 'N/A'], target: $target));

                // throwing exception, because User/Device might be not deleted if CSC is not removed
                if ($throwIfCscNotExists) {
                    throw new LogsException($provider);
                }

                return;
            }

            $provider->deleteCscInVpnServer($cscCommonName);

            // Updating User/Device state
            $this->clearCscValues($target);

            // Handling logs
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target);
            // If no exception CSC was succesfully deleted
            $this->vpnLogManager->createLogInfo('log.vpnProviders.deleteCsc.deleteCscSuccess', ['cscCommonName' => $cscCommonName], target: $target);
        } catch (ProviderException $providerException) {
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target);

            throw new LogsException($providerException);
        }
    }

    public function openConnection(User $user, Device|DeviceEndpointDevice $target): void
    {
        // Remove possiblity of creating duplicate connections
        foreach ($this->vpnManager->getUserConnections($user) as $connection) {
            if ($connection->getTarget() === $target) {
                throw new LogsException($this->vpnLogManager->createLogError('log.vpnProviders.openConnection.connectionExists', target: $target, user: $user));
            }
        }

        $destinationIp = $this->vpnManager->getVpnClientIp($target);
        $sourceIp = $this->vpnManager->getVpnClientIp($user);

        $provider = $this->getVpnProvider();

        try {
            $firewallRuleToBeAdded = new FirewallRuleConfiguration($sourceIp, $destinationIp);

            $ruleIdentifier = $this->addFirewallRule($provider, $firewallRuleToBeAdded);

            $connection = new VpnConnection();
            $connection->setUser($user);
            $connection->setTarget($target);
            $connection->setConnectionFirewallRules([$ruleIdentifier]);
            $connection->setConnectionStartAt(new \DateTime());

            if ($this->getConfiguration()->getVpnConnectionLimit()) {
                $endAt = new \DateTime();
                $endAt->modify($this->getConfiguration()->getVpnConnectionDuration());
                $connection->setConnectionEndAt($endAt);
            }

            $target->setVpnLastConnectionAt(new \DateTime());

            $this->entityManager->persist($target);
            $this->entityManager->persist($connection);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Handling logs
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target, user: $user);

            if ($connection->getConnectionEndAt()) {
                $this->vpnLogManager->createLogInfo(
                    'log.vpnProviders.openConnection.connectionOpened',
                    ['expiresAt' => $connection->getConnectionEndAt()->format('c')],
                    vpnConnection: $connection
                );
            } else {
                $this->vpnLogManager->createLogInfo('log.vpnProviders.openConnection.connectionOpenedUnlimited', vpnConnection: $connection);
            }
        } catch (ProviderException $providerException) {
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target, user: $user);

            throw new LogsException($providerException);
        }
    }

    public function closeConnection(VpnConnection $connection): void
    {
        $target = $connection->getTarget();
        $user = $connection->getUser();
        $destinationIp = $this->vpnManager->getVpnClientIp($target);
        $sourceIp = $this->vpnManager->getVpnClientIp($user);

        $provider = $this->getVpnProvider();

        try {
            $this->updateConnectionsTraffic($provider, $target);

            $this->deleteFirewallRule($provider, $connection, $sourceIp, $destinationIp);

            // Handling logs
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target, user: $user, vpnConnection: $connection);
            $this->vpnLogManager->createLogInfo('log.vpnProviders.closeConnection.connectionClosed', target: $target, user: $user, vpnConnection: $connection);

            $this->entityManager->persist($user);

            $this->entityManager->remove($connection);
            $this->entityManager->flush();

            $user->getVpnConnections()->removeElement($connection);
            $target->getVpnConnections()->removeElement($connection);
        } catch (ProviderException $providerException) {
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target, user: $user, vpnConnection: $connection);

            throw new LogsException($providerException);
        }
    }

    public function openDeviceToNetworkConnection(Device $target): void
    {
        if (!$target->getDeviceType()->getIsDeviceToNetworkConnectionAvailable()) {
            return;
        }

        // Checking if device has permanent connection for quick and safe over-use
        foreach ($target->getVpnConnections() as $connection) {
            if ($connection->getPermanent()) {
                // connection is already there - probably edit was executed
                return;
            }
        }

        $user = $this->getSystemUser();

        $provider = $this->getVpnProvider();

        $firewallRuleConfigurationCollectionToAdd = $this->getDeviceToNetworkFirewallRuleConfigurationCollection($target);

        try {
            foreach ($firewallRuleConfigurationCollectionToAdd as $firewallRuleToBeAdded) {
                $ruleIdentifier = $this->addFirewallRule($provider, $firewallRuleToBeAdded);

                $connection = new VpnConnection();
                $connection->setUser($user);
                $connection->setTarget($target);
                $connection->setPermanent(true);
                $connection->setSource($firewallRuleToBeAdded->getSourceIp().'/'.$firewallRuleToBeAdded->getSourceNetmask());
                $connection->setDestination($firewallRuleToBeAdded->getDestinationIp().'/'.$firewallRuleToBeAdded->getDestinationNetmask());
                $connection->setConnectionFirewallRules([$ruleIdentifier]);
                $connection->setConnectionStartAt(new \DateTime());
                $target->setVpnLastConnectionAt(new \DateTime());

                $target->addVpnConnection($connection);

                // Persisting $target is required to persist $connection (no cascade there by design)
                $this->entityManager->persist($target);
                $this->entityManager->persist($connection);

                $this->vpnLogManager->createLogs($provider->getLogs(), target: $target, user: $user);
                $provider->clearLogs();

                $this->vpnLogManager->createLogInfo('log.vpnProviders.openConnection.permanentConnectionOpenedUnlimited', vpnConnection: $connection);

                $this->entityManager->persist($user);
            }

            $this->entityManager->flush();

            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target, user: $user);
        } catch (ProviderException $providerException) {
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target, user: $user);

            throw new LogsException($providerException);
        }
    }

    /**
     * Close device to network connection.
     */
    public function closeDeviceToNetworkConnections(Device $target): void
    {
        if (!$target->getDeviceType()->getIsDeviceToNetworkConnectionAvailable()) {
            return;
        }

        // Checking if device has permanent connection for quick and safe over-use
        $hasPermanentConnections = false;
        foreach ($target->getVpnConnections() as $connection) {
            if ($connection->getPermanent()) {
                $hasPermanentConnections = true;
                break;
            }
        }

        if (!$hasPermanentConnections) {
            return;
        }

        $user = $this->getSystemUser();

        $provider = $this->getVpnProvider();

        try {
            $this->updateConnectionsTraffic($provider, $target);

            foreach ($target->getVpnConnections() as $connection) {
                if (!$connection->getPermanent()) {
                    continue;
                }

                $this->deleteFirewallRule($provider, $connection, $connection->getSource(), $connection->getDestination());

                $this->vpnLogManager->createLogs($provider->getLogs(), target: $target, user: $user, vpnConnection: $connection);
                $this->vpnLogManager->createLogInfo('log.vpnProviders.closeConnection.permanentConnectionClosed', vpnConnection: $connection);

                $provider->clearLogs();

                $this->entityManager->persist($connection->getUser());

                $this->entityManager->remove($connection);
            }

            $this->entityManager->flush();
        } catch (ProviderException $providerException) {
            $this->vpnLogManager->createLogs($provider->getLogs(), target: $target, user: $user);

            throw new LogsException($providerException);
        }
    }

    /**
     * Method creates FirewallRuleConfigurationCollection for deviceToNetwork connection.
     */
    protected function getDeviceToNetworkFirewallRuleConfigurationCollection(Device $target): FirewallRuleConfigurationCollection
    {
        $firewallRuleConfigurationCollectionToAdd = new FirewallRuleConfigurationCollection();

        if (!$target->getDeviceType()->getIsDeviceToNetworkConnectionAvailable()) {
            return $firewallRuleConfigurationCollectionToAdd;
        }

        $deviceIp = $target->getVpnIp();
        $deviceVirtualIp = $target->getDeviceType()->getIsEndpointDevicesAvailable() ? $target->getVirtualIp() : null;

        $deviceNetworks = array_merge($this->vpnAddressManager->getDevicesVpnNetworks(), $this->vpnAddressManager->getDevicesVirtualVpnNetworks());

        foreach ($deviceNetworks as $deviceNetwork) {
            list($networkIp, $networkNetmask) = explode('/', $deviceNetwork);

            $firewallRuleToBeAdded = new FirewallRuleConfiguration($deviceIp, $networkIp, 32, intval($networkNetmask));

            $firewallRuleConfigurationCollectionToAdd->add($firewallRuleToBeAdded);

            if ($deviceVirtualIp) {
                $firewallRuleToBeAdded = new FirewallRuleConfiguration($deviceVirtualIp, $networkIp, 32, intval($networkNetmask));

                $firewallRuleConfigurationCollectionToAdd->add($firewallRuleToBeAdded);
            }
        }

        return $firewallRuleConfigurationCollectionToAdd;
    }

    /**
     * Adds firewall rules from collection. First checks if rule exists (and removes them if exists).
     * Returns ruleIdentifier or throws ProviderException.
     */
    protected function addFirewallRule(VpnProviderInterface $provider, FirewallRuleConfiguration $firewallRuleToBeAdded): string
    {
        $firewallRuleConfigurationCollection = $provider->getFirewallRules();

        $obsoleteFirewallRule = $firewallRuleConfigurationCollection->searchFirewallRuleByIp($firewallRuleToBeAdded->getSourceIp(), $firewallRuleToBeAdded->getDestinationIp());

        if ($obsoleteFirewallRule) {
            $provider->addLogError(
                'log.vpnProviders.openConnection.unexpectedFirewallRule',
                [
                    'sourceIp' => $firewallRuleToBeAdded->getSourceIp().'/'.$firewallRuleToBeAdded->getSourceNetmask(),
                    'destinationIp' => $firewallRuleToBeAdded->getDestinationIp().'/'.$firewallRuleToBeAdded->getDestinationNetmask(),
                ]
            );

            // removing $obsoleteFirewallRule
            $provider->deleteFirewallRule($obsoleteFirewallRule);
        }

        $ruleIdentifier = $provider->addFirewallRule($firewallRuleToBeAdded);

        $provider->addLogInfo(
            'log.vpnProviders.openConnection.firewallRuleAdded',
            [
                'sourceIp' => $firewallRuleToBeAdded->getSourceIp().'/'.$firewallRuleToBeAdded->getSourceNetmask(),
                'destinationIp' => $firewallRuleToBeAdded->getDestinationIp().'/'.$firewallRuleToBeAdded->getDestinationNetmask(),
            ]
        );

        return $ruleIdentifier;
    }

    /**
     * Deletes firewall rules from connection.
     * Throws ProviderException.
     */
    protected function deleteFirewallRule(VpnProviderInterface $provider, VpnConnection $connection, string $sourceIp, string $destinationIp): void
    {
        foreach ($connection->getConnectionFirewallRules() as $ruleIdentifier) {
            $firewallRuleConfigurationCollection = $provider->getFirewallRules();
            $ruleToRemove = $firewallRuleConfigurationCollection->searchFirewallRuleByIdentifier($ruleIdentifier);
            if (null === $ruleToRemove) {
                $provider->addLogError(
                    'log.vpnProviders.closeConnection.firewallRuleNotFound',
                    [
                        'sourceIp' => $sourceIp,
                        'destinationIp' => $destinationIp,
                        'ruleIdentifier' => $ruleIdentifier,
                    ]
                );

                continue;
            }

            $provider->deleteFirewallRule($ruleToRemove);

            $provider->addLogInfo(
                'log.vpnProviders.openConnection.firewallRuleDeleted',
                [
                    'sourceIp' => $ruleToRemove->getSourceIp().'/'.$ruleToRemove->getSourceNetmask(),
                    'destinationIp' => $ruleToRemove->getDestinationIp().'/'.$ruleToRemove->getDestinationNetmask(),
                ]
            );
        }
    }

    /**
     * Updates bytes received and bytes send for active connection with provided target.
     * For endpoint devices - parent device is updated (as it is actual VPN Client).
     */
    protected function updateConnectionsTraffic(VpnProviderInterface $provider, Device|DeviceEndpointDevice $target): void
    {
        $vpnDevice = $target;
        if ($target instanceof DeviceEndpointDevice) {
            $vpnDevice = $target->getDevice();
        }

        // $provider is required to collect log messages
        $commonName = $this->getVpnClientCertificateSubject($provider, $vpnDevice);

        $vpnConnectedClients = $this->getVpnConnectedClients();

        foreach ($vpnConnectedClients as $vpnConnectedClient) {
            if ($vpnConnectedClient->getCommonName() === $commonName) {
                $vpnDevice->setVpnTrafficIn($vpnDevice->getVpnTrafficIn() + $vpnConnectedClient->getBytesReceived());
                $vpnDevice->setVpnTrafficOut($vpnDevice->getVpnTrafficOut() + $vpnConnectedClient->getBytesSent());
                $this->entityManager->persist($vpnDevice);
                break;
            }
        }
    }

    // checks if VpnCscConfiguration is same as persisted in database
    protected function isVpnCscConfigurationDeployed(VpnProviderInterface $provider, VpnCscConfiguration $vpnCscConfiguration, Device|User $target): bool
    {
        if (
            $target->getCscCertificateSubject() !== $vpnCscConfiguration->getCscCommonName() ||
            $target->getCscVpnIp() !== $vpnCscConfiguration->getTunnelNetwork() ||
            $target->getCscVirtualSubnet() !== $vpnCscConfiguration->getCommaDelimitedRemoteNetworks() ||
            $target->getCscHash() !== $provider->getVpnCscConfigurationHash($vpnCscConfiguration)
        ) {
            return false;
        }

        return true;
    }

    protected function updateCscValues(VpnProviderInterface $provider, VpnCscConfiguration $vpnCscConfiguration, Device|User $target): void
    {
        // Updating User/Device state
        $target->setCscCertificateSubject($vpnCscConfiguration->getCscCommonName());
        $target->setCscVpnIp($vpnCscConfiguration->getTunnelNetwork());
        $target->setCscVirtualSubnet($vpnCscConfiguration->getCommaDelimitedRemoteNetworks());
        $target->setCscHash($provider->getVpnCscConfigurationHash($vpnCscConfiguration));
        $this->entityManager->persist($target);
        $this->entityManager->flush();
    }

    protected function clearCscValues(Device|User $target): void
    {
        // Updating User/Device state
        $target->setCscCertificateSubject(null);
        $target->setCscVpnIp(null);
        $target->setCscVirtualSubnet(null);
        $target->setCscHash(null);
        $this->entityManager->persist($target);
        $this->entityManager->flush();
    }

    protected function getVpnServerDescription(Device|User $target): ?string
    {
        switch (true) {
            case $target instanceof User:
                return $this->getConfiguration()->getTechniciansOpenvpnServerDescription();
            case $target instanceof Device:
                return $this->getConfiguration()->getDevicesOpenvpnServerDescription();
            default:
                throw new \Exception('Unsupported object type: '.get_class($target).'. Must be one of: User, Device');
        }
    }

    protected function getVpnServerName(VpnProviderInterface $provider, Device|User $target): string
    {
        switch (true) {
            case $target instanceof User:
                $serverName = $this->getConfiguration()->getTechniciansOpenvpnServerIndex();
                if ($serverName) {
                    return $serverName;
                }
                break;
            case $target instanceof Device:
                $serverName = $this->getConfiguration()->getDevicesOpenvpnServerIndex();
                if ($serverName) {
                    return $serverName;
                }
                break;
            default:
                throw new \Exception('Unsupported object type: '.get_class($target).'. Must be one of: User, Device');
        }

        // No server name cached - need to get if via provider
        $serverDescription = $this->getVpnServerDescription($target);

        if (!$serverDescription) {
            throw new ProviderException($provider->addLogError('log.vpnProviders.noOpenVPNServerDescription'));
        }

        $serverName = $provider->getVpnServerNameByDescription($serverDescription);

        // Persist server name in cache
        switch (true) {
            case $target instanceof User:
                $this->getConfiguration()->setTechniciansOpenvpnServerIndex($serverName);
                break;
            case $target instanceof Device:
                $this->getConfiguration()->setDevicesOpenvpnServerIndex($serverName);
                break;
            default:
                throw new \Exception('Unsupported object type: '.get_class($target).'. Must be one of: User, Device');
        }

        $this->entityManager->persist($this->getConfiguration());
        $this->entityManager->flush();

        return $serverName;
    }

    // $provider is required to collect log messages
    protected function getVpnClientCertificateSubject(VpnProviderInterface $provider, User|Device $target): string
    {
        $certificate = $this->getVpnCertificate($target);

        if ($certificate && $certificate->hasCertificate() && $certificate->getCertificateSubject()) {
            return $certificate->getCertificateSubject();
        }

        throw new ProviderException($provider->addLogError('log.vpnProviders.noCertificate'));
    }

    // $provider is required to collect log messages
    protected function getTunnelNetworkAddress(VpnProviderInterface $provider, User|Device $target): string
    {
        if (!$target->getVpnIp()) {
            throw new ProviderException($provider->addLogError('log.vpnProviders.noVpnIp'));
        }

        $network = $this->vpnAddressManager->findNetwork(ip2long($target->getVpnIp()), 32);
        if (!$network) {
            throw new ProviderException($provider->addLogCritical('log.vpnProviders.vpnNetworkNotFound'));
        }

        list(, $cidr) = explode('/', $network);

        return $target->getVpnIp().'/'.$cidr;
    }

    protected function getRemoteNetworkAddresses(User|Device $target): array
    {
        if ($this->vpnManager->hasAllVirtualNetworksAccess($target)) {
            return $this->vpnAddressManager->getDevicesVirtualVpnNetworks();
        }

        if ($this->vpnManager->hasVpnNat($target)) {
            return [$target->getVirtualSubnet()];
        }

        return [];
    }

    protected function getVpnProvider(): VpnProviderInterface
    {
        $configuration = $this->getConfiguration();
        $url = $configuration->getOpnsenseUrl();

        if (!$configuration->getOpnsenseUrl()) {
            throw new LogsException($this->vpnLogManager->createLogError('log.vpnProviders.noOpnsenseUrl'));
        }

        return new OpnSenseVpnProvider(
            $this->httpClient,
            $url,
            $configuration->getOpnsenseTimeout(),
            $configuration->getVerifyOpnsenseSslCertificate(),
            $configuration->getOpnsenseApiKey(),
            $configuration->getOpnsenseApiSecret(),
        );
    }
}
