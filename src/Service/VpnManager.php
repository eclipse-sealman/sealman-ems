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

use App\Entity\Certificate;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\Traits\VpnEntityInteface;
use App\Entity\User;
use App\Entity\VpnConnection;
use App\Enum\CertificateCategory;
use App\Event\CertificatePostGenerateEvent;
use App\Event\CertificatePreRevokeEvent;
use App\Event\DeviceEndpointDevicePreRemoveEvent;
use App\Event\DeviceEndpointDeviceUpdatedEvent;
use App\Event\DevicePreRemoveEvent;
use App\Event\DeviceUpdatedEvent;
use App\Event\UserPreRemoveEvent;
use App\Exception\LogsException;
use App\Model\ConnectionStatus;
use App\Provider\Model\VpnConnectedClientsCollection;
use App\Service\Helper\AuditableManagerTrait;
use App\Service\Helper\CertificateManagerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\UserTrait;
use App\Service\Helper\VpnAddressManagerTrait;
use App\Service\Helper\VpnLogManagerTrait;
use App\Service\Helper\VpnProvidersManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class VpnManager
{
    use CertificateManagerTrait;
    use CertificateTypeHelperTrait;
    use ConfigurationManagerTrait;
    use EncryptionManagerTrait;
    use EntityManagerTrait;
    use UserTrait;
    use VpnAddressManagerTrait;
    use VpnLogManagerTrait;
    use VpnProvidersManagerTrait;
    use AuditableManagerTrait;

    // Event listener has to be executed after listeners that could generate certificate
    #[AsEventListener(priority: -200)]
    public function onDeviceUpdatedEvent(DeviceUpdatedEvent $event)
    {
        if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
            return;
        }

        $device = $event->getDevice();

        if (!$device->getDeviceType()->getIsVpnAvailable()) {
            // if DeviceType is not available do nothing
            return;
        }

        $vpnCertificate = $this->getVpnCertificate($device);
        if (!$vpnCertificate || !$vpnCertificate->hasCertificate() || !$vpnCertificate->getCertificateGenerated()) {
            // If no valid DEVICE_VPN certificate do nothing
            return;
        }

        // Might execute twice in onCertificatePostGenerateEvent and here, but in that case
        // Methods below will just go through tests and do nothing
        // Code is required in case virtualSubnetCidr or EndpointDevice virtualIpHostPart will change without certificate generation
        $this->vpnAddressManager->setupVpnAddresses($device);
        $this->vpnProvidersManager->updateVpnServerCsc($device);

        // If endpoint devices where removed during template apply onDeviceEndpointDevicePreRemoveEvent is dispatched and it will close VPN connections
        // If endpoint devices with opened VPN connection are to be removed during edit form - validation error will be thrown

        // Closing device-to-network connections for disabled device-to-network devices
        if ($device->getDeviceType()->getIsDeviceToNetworkConnectionAvailable()) {
            if ($device->getEnabled()) {
                // both methods are safe to over-use
                $this->vpnProvidersManager->openDeviceToNetworkConnection($device);
            } else {
                $this->vpnProvidersManager->closeDeviceToNetworkConnections($device);
            }
        }
    }

    #[AsEventListener()]
    public function onDeviceEndpointDeviceUpdatedEvent(DeviceEndpointDeviceUpdatedEvent $event)
    {
        if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
            return;
        }

        $this->vpnAddressManager->setupVpnAddresses($event->getDeviceEndpointDevice());
    }

    #[AsEventListener()]
    public function onDeviceEndpointDevicePreRemoveEvent(DeviceEndpointDevicePreRemoveEvent $event)
    {
        if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
            return;
        }

        $endpointDevice = $event->getDeviceEndpointDevice();

        $aggregatedException = new LogsException();

        // Close regular connections for endpointDevices of this Device
        foreach ($endpointDevice->getVpnConnections() as $connection) {
            try {
                if (!$connection->getPermanent()) {
                    $this->closeConnection($connection);
                }
            } catch (LogsException $logsException) {
                // still want to try close other connections
                $aggregatedException->merge($logsException);
            }
        }

        if ($aggregatedException->hasErrors()) {
            throw $aggregatedException;
        }
    }

    #[AsEventListener()]
    public function onCertificatePostGenerateEvent(CertificatePostGenerateEvent $event)
    {
        if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
            return;
        }

        $certificate = $event->getCertificate();

        if (!$certificate->hasCertificate() || !$certificate->getCertificateGenerated()) {
            return;
        }

        // method handles only DEVICE_VPN and TECHNICIAN_VPN certificate categories
        if (!$this->isVpnCertificate($certificate)) {
            return;
        }

        $target = $certificate->getTarget();

        if ($target instanceof Device) {
            if (!$target->getDeviceType()->getIsVpnAvailable()) {
                // if DeviceType is not available do nothing
                return;
            }
        }

        $this->vpnAddressManager->setupVpnAddresses($target);
        $this->vpnProvidersManager->updateVpnServerCsc($target, true);

        // Open device-to-network connections if device is enabled
        if ($target instanceof Device) {
            if ($target->getDeviceType()->getIsDeviceToNetworkConnectionAvailable()) {
                if ($target->getEnabled()) {
                    $this->vpnProvidersManager->openDeviceToNetworkConnection($target);
                }
            }
        }
    }

    #[AsEventListener()]
    public function onCertificatePreRevokeEvent(CertificatePreRevokeEvent $event)
    {
        if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
            return;
        }

        $certificate = $event->getCertificate();

        // method handles only DEVICE_VPN and TECHNICIAN_VPN certificate categories
        if (!$this->isVpnCertificate($certificate)) {
            return;
        }

        $target = $certificate->getTarget();

        if ($target instanceof Device) {
            if (!$target->getDeviceType()->getIsVpnAvailable()) {
                // if DeviceType is not available do nothing
                return;
            }
        }

        $this->closeConnections($target);

        // If closeConnections will throw exception CSC will not be deleted - by design
        $this->vpnProvidersManager->deleteVpnServerCsc($target, false);
    }

    // Certificate revocation will he handled by CertificateTypeManager
    #[AsEventListener(priority: -200)]
    public function onDevicePreRemoveEvent(DevicePreRemoveEvent $event)
    {
        $this->vpnAddressManager->removeVpnAddresses($event->getDevice());
    }

    // Certificate revocation will he handled by CertificateTypeManager
    #[AsEventListener(priority: -200)]
    public function onUserPreRemoveEvent(UserPreRemoveEvent $event)
    {
        $this->vpnAddressManager->removeVpnAddresses($event->getUser());
    }

    public function getOpenVpnConfigurationFilename(VpnEntityInteface $target): string
    {
        $certificate = $this->getVpnCertificate($target);

        if ($certificate && $certificate->getCertificateSubject()) {
            return $certificate->getCertificateSubject().'.ovpn';
        }

        return 'open-vpn-configuration.ovpn';
    }

    public function processCrlUpdate(Certificate $target, string $crlUpdated): void
    {
        // method handles only DEVICE_VPN and TECHNICIAN_VPN certificate categories
        if (!$this->isVpnCertificate($target)) {
            return;
        }

        $this->vpnProvidersManager->updateVpnServerCrl($target->getTarget(), $crlUpdated);
    }

    public function getDevicesVpnNetwork(): string
    {
        return $this->getConfiguration()->getDevicesVpnNetworks();
    }

    public function getTechniciansVpnNetwork(): string
    {
        return $this->getConfiguration()->getTechniciansVpnNetworks();
    }

    public function getDevicesVpnGateway(): string
    {
        list($subnet, $cidr) = explode('/', $this->getDevicesVpnNetwork());

        $gateway = long2ip(ip2long($subnet) + 1);

        return $gateway;
    }

    public function getTechniciansVpnGateway(): string
    {
        list($subnet, $cidr) = explode('/', $this->getTechniciansVpnNetwork());

        $gateway = long2ip(ip2long($subnet) + 1);

        return $gateway;
    }

    public function getExpiredConnections(): array
    {
        return $this->entityManager->getRepository(VpnConnection::class)
                        ->createQueryBuilder('c')
                        ->andWhere('c.connectionEndAt < :now')
                        ->setParameter('now', new \DateTime())
                        ->getQuery()
                        ->getResult()
        ;
    }

    public function getUserConnections(User $user): array|Collection
    {
        return $this->getRepository(VpnConnection::class)->findBy(['user' => $user]);
    }

    public function getUserConnectionStatus(): ConnectionStatus
    {
        // This method will throw LogsException if needed
        $result = $this->vpnProvidersManager->getVpnConnectedClients();

        $this->updateConnectedClients($result);

        $user = $this->getUser();

        $connectionStatus = new ConnectionStatus();
        $connectionStatus->setUser($user);
        $connectionStatus->setConnections($this->getUserConnections($user));

        return $connectionStatus;
    }

    // Returns destination IP from Device, DeviceEndpointDevice, User
    public function getVpnClientIp(Device|DeviceEndpointDevice|User $target): ?string
    {
        if ($this->hasVirtualIp($target)) {
            return $target->getVirtualIp();
        } else {
            return $target->getVpnIp();
        }
    }

    // Checks if Device, DeviceEndpointDevice, User can handle Virtual Adresses
    public function hasVirtualIp(Device|DeviceEndpointDevice|User $target): bool
    {
        if ($target instanceof DeviceEndpointDevice) {
            return true;
        }
        if ($target instanceof User) {
            return false;
        }
        if ($target instanceof Device) {
            if ($target->getDeviceType()->getIsEndpointDevicesAvailable()) {
                return true;
            } else {
                return false;
            }
        }

        return null;
    }

    // Checks if Device, DeviceEndpointDevice, User can handle Virtual Adresses And NAT - function written for convenience of change in future
    public function hasVpnNat(Device|DeviceEndpointDevice|User $target): bool
    {
        return $this->hasVirtualIp($target);
    }

    // Checks if Device, DeviceEndpointDevice, User can access all virtual networks- function written for convenience of change in future
    public function hasAllVirtualNetworksAccess(Device|DeviceEndpointDevice|User $target): bool
    {
        if ($target instanceof Device) {
            if ($target->getDeviceType()->getIsDeviceToNetworkConnectionAvailable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Opens connection between User and Target.
     */
    public function openConnection(User $user, Device|DeviceEndpointDevice $target): void
    {
        $this->vpnProvidersManager->openConnection($user, $target);
    }

    /**
     * Close connection.
     */
    public function closeConnection(VpnConnection $connection): void
    {
        $this->vpnProvidersManager->closeConnection($connection);
    }

    public function generateConfiguration(VpnEntityInteface $target): ?string
    {
        $logUser = null;
        $logDevice = null;
        switch (true) {
            case $target instanceof User:
                $certificate = $this->getCertificateByType($target, $this->getTechnicianVpnCertificateType());
                $logUser = $target;
                break;
            case $target instanceof Device:
                $certificate = $this->getCertificateByType($target, $this->getDeviceVpnCertificateType());
                $logDevice = $target;
                break;
            default:
                throw new \Exception('Unsupported object type: '.get_class($target).'. Must be one of: User, Device');
        }

        if (!$certificate || !$certificate->hasCertificate()) {
            $this->vpnLogManager->createLogError('log.deviceNoCertificate', device: $logDevice, user: $logUser);

            return null;
        }

        $conf = $this->getConfigurationTemplate($target);

        $conf = str_replace('$ca', $this->encryptionManager->decrypt($certificate->getCertificateCa()), $conf);
        $conf = str_replace('$certificate', $this->encryptionManager->decrypt($certificate->getCertificate()), $conf);
        $conf = str_replace('$privateKey', $this->encryptionManager->decrypt($certificate->getPrivateKey()), $conf);

        return $conf;
    }

    protected function getVpnCertificateCategories(): array
    {
        return [
            CertificateCategory::TECHNICIAN_VPN,
            CertificateCategory::DEVICE_VPN,
        ];
    }

    protected function isVpnCertificate(Certificate $certificate): bool
    {
        if (in_array($certificate->getCertificateCategory(), $this->getVpnCertificateCategories())) {
            return true;
        }

        return false;
    }

    protected function closeConnections(Device|DeviceEndpointDevice|User $target)
    {
        $aggregatedException = new LogsException();

        // Close permanent connections
        if ($target instanceof Device && $target->getDeviceType()->getIsDeviceToNetworkConnectionAvailable()) {
            try {
                $this->vpnProvidersManager->closeDeviceToNetworkConnections($target);
            } catch (LogsException $logsException) {
                // still want to try close other connections
                $aggregatedException->merge($logsException);
            }
        }

        // This code closes regular connections for Device|DeviceEndpointDevice|User
        foreach ($target->getVpnConnections() as $connection) {
            try {
                if (!$connection->getPermanent()) {
                    $this->closeConnection($connection);
                }
            } catch (LogsException $logsException) {
                // still want to try close other connections
                $aggregatedException->merge($logsException);
            }
        }

        // Close regular connections for endpointDevices of this Device
        if ($target instanceof Device && $this->hasVpnNat($target)) {
            foreach ($target->getEndpointDevices() as $endpointDevice) {
                foreach ($endpointDevice->getVpnConnections() as $connection) {
                    try {
                        if (!$connection->getPermanent()) {
                            $this->closeConnection($connection);
                        }
                    } catch (LogsException $logsException) {
                        // still want to try close other connections
                        $aggregatedException->merge($logsException);
                    }
                }
            }
        }

        if ($aggregatedException->hasErrors()) {
            throw $aggregatedException;
        }
    }

    /**
     * Updates connected Clients (maps OpenVPN IP Addresses to virtual_addr in firewall rules).
     */
    protected function updateConnectedClients(VpnConnectedClientsCollection $vpnConnectedClientsCollection)
    {
        $vpnAddresses = [];

        foreach ($vpnConnectedClientsCollection as $vpnConnectedClient) {
            $vpnAddresses[] = $vpnConnectedClient->getVpnIp();
        }

        $vpnAddresses = array_unique($vpnAddresses);

        $devices = $this->getRepository(Device::class)->findBy(['vpnIp' => $vpnAddresses]);
        $users = $this->getRepository(User::class)->findBy(['vpnIp' => $vpnAddresses]);

        $existingUserIds = [];
        $existingDeviceIds = [];

        foreach ($users as $user) {
            if ($this->hasActiveConnection($vpnConnectedClientsCollection, $user)) {
                $user->setVpnConnected(true);
                $this->entityManager->persist($user);
                $existingUserIds[] = $user->getId();
            }
        }

        foreach ($devices as $device) {
            if ($this->hasActiveConnection($vpnConnectedClientsCollection, $device)) {
                $device->setVpnConnected(true);
                $this->entityManager->persist($device);
                $existingDeviceIds[] = $device->getId();
            }
        }

        $this->entityManager->flush();

        $this->clearConnections(User::class, $existingUserIds);
        $this->clearConnections(Device::class, $existingDeviceIds);
    }

    protected function getConfigurationTemplate(VpnEntityInteface $target): string
    {
        switch (true) {
            case $target instanceof User:
                return $this->getConfiguration()->getTechniciansOvpnTemplate();
            case $target instanceof Device:
                return $this->getConfiguration()->getDevicesOvpnTemplate();
            default:
                throw new \Exception('Unsupported object type: '.get_class($target).'. Must be one of: User, Device');
        }
    }

    protected function hasActiveConnection(VpnConnectedClientsCollection $vpnConnectedClientsCollection, VpnEntityInteface $vpnClient): bool
    {
        $certificate = $this->getVpnCertificate($vpnClient);

        if (!$certificate) {
            return false;
        }

        foreach ($vpnConnectedClientsCollection as $vpnConnectedClient) {
            if ($vpnConnectedClient->getCommonName() === $certificate->getCertificateSubject()) {
                return $vpnConnectedClient->getVpnIp() === $vpnClient->getVpnIp();
            }
        }

        return false;
    }

    protected function clearConnections($repository, array $excludeIds)
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $queryBuilder = $this->getRepository($repository)->createQueryBuilder('c');
            $queryBuilder->update();
            $queryBuilder->set('c.vpnConnected', 0); // Cannot use false or a setParameter(). Have no idea why.
            $queryBuilder->andWhere('c.vpnConnected = :currentVpnConnected');
            $queryBuilder->setParameter('currentVpnConnected', true);

            if (count($excludeIds) > 0) {
                $queryBuilder->andWhere('c.id NOT IN (:ids)');
                $queryBuilder->setParameter('ids', $excludeIds);
            }

            $this->auditableManager->createPartialBatchUpdate($queryBuilder, ['vpnConnected' => true], ['vpnConnected' => false]);

            $queryBuilder->getQuery()->execute();

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
