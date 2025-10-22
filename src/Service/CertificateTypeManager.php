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
use App\Entity\User;
use App\Enum\CertificateBehavior;
use App\Enum\CertificateCategory;
use App\Event\DevicePreRemoveEvent;
use App\Event\DeviceUpdatedEvent;
use App\Event\UserPreRemoveEvent;
use App\Event\UserUpdatedEvent;
use App\Exception\LogsException;
use App\Model\UseableCertificate;
use App\Service\Helper\CertificateManagerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\VpnLogManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class CertificateTypeManager
{
    use CertificateManagerTrait;
    use CertificateTypeHelperTrait;
    use ConfigurationManagerTrait;
    use EntityManagerTrait;
    use VpnLogManagerTrait;

    // Event listener might generate or revoke certificate
    #[AsEventListener()]
     public function onDeviceUpdatedEvent(DeviceUpdatedEvent $event)
     {
         $this->executeCertificateTypeAutomaticBehavior($event->getDevice());
     }

    // Event listener might generate or revoke certificate
    #[AsEventListener()]
     public function onUserUpdatedEvent(UserUpdatedEvent $event)
     {
         $this->executeCertificateTypeAutomaticBehavior($event->getUser());
     }

    #[AsEventListener()]
     public function onDevicePreRemoveEvent(DevicePreRemoveEvent $event)
     {
         $this->onPreRemove($event->getDevice());
     }

    #[AsEventListener()]
     public function onUserPreRemoveEvent(UserPreRemoveEvent $event)
     {
         $this->onPreRemove($event->getUser());
     }

    // use on every deleted device and user- onDelete
    public function onPreRemove(Device|User $target)
    {
        $aggregatedException = new LogsException();

        foreach ($target->getCertificates() as $certificate) {
            try {
                if ($certificate->hasCertificate() && $certificate->getCertificateGenerated()) {
                    // Connections and CSC will be handle on preRevoke event
                    $this->certificateManager->revokeCertificate($certificate);
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

    // Use on every updated device and user (onCreate onEdit onEnable onSubnetSizeChange, onEndpointDevice change)
    public function executeCertificateTypeAutomaticBehavior(Device|User $target): void
    {
        if ($this->configurationManager->isScepBlocked()) {
            // no log message because this method is executed in all licenses
            return;
        }

        $aggregatedException = new LogsException();

        // Automatic certificate behaviors
        foreach ($target->getUseableCertificates() as $useableCertificate) {
            try {
                // Check if any automatic behavoir can be executed
                if (!$useableCertificate->getCertificateType()->getIsAvailable()) {
                    if ($useableCertificate->getCertificateType()->getEnabled()) {
                        $this->vpnLogManager->createLogError(
                            'log.certificateType.notAvailable',
                            ['certificateType' => $useableCertificate->getCertificateType()->getRepresentation()],
                            target: $target
                        );
                    }
                    continue;
                }

                $this->validateCertificateEntity($useableCertificate->getCertificateType(), $target);

                $this->updateUsableCertificateWithCertificateBehaviour($useableCertificate, $target);

                if ($target->getEnabled()) {
                    $this->executeEnabledCertificateTypeAutomaticBehavior($target, $useableCertificate);
                } else {
                    $this->executeDisabledCertificateTypeAutomaticBehavior($target, $useableCertificate);
                }
            } catch (LogsException $logsException) {
                $this->entityManager->flush();

                $aggregatedException->merge($logsException);
            }
        }

        $this->entityManager->flush();

        if ($aggregatedException->hasErrors()) {
            throw $aggregatedException;
        }
    }

    protected function executeEnabledCertificateTypeAutomaticBehavior(Device|User $target, UseableCertificate $useableCertificate): void
    {
        switch ($useableCertificate->getCertificateType()->getEnabledBehaviour()) {
            case CertificateBehavior::NONE:
                // Added because batch actions values are not validated
                $useableCertificate->setGenerateCertificate(false);
                break;
            case CertificateBehavior::ON_DEMAND:
                // flag value as user requested
                break;
            case CertificateBehavior::AUTO:
                $useableCertificate->setGenerateCertificate(true);
                break;
            case CertificateBehavior::SPECIFIC:
                $useableCertificate->setGenerateCertificate($this->getSpecificGenerateCertificate($target, $useableCertificate));
                break;
        }
        // getGenerateCertificate is updated - ready to execute generation if needed
        if ($useableCertificate->getGenerateCertificate()) {
            if (!$useableCertificate->getCertificate()) {
                $certificate = new Certificate();
                if ($target instanceof User) {
                    $certificate->setUser($target);
                }

                if ($target instanceof Device) {
                    $certificate->setDevice($target);
                }

                $certificate->setCertificateType($useableCertificate->getCertificateType());

                $useableCertificate->setCertificate($certificate);
            }

            if (!$useableCertificate->getCertificate()->hasAnyCertificatePart()) {
                $this->certificateManager->generateCertificate($useableCertificate->getCertificate());
            }
        }
    }

    protected function executeDisabledCertificateTypeAutomaticBehavior(Device|User $target, UseableCertificate $useableCertificate): void
    {
        switch ($useableCertificate->getCertificateType()->getDisabledBehaviour()) {
            case CertificateBehavior::NONE:
                // Added because batch actions values are not validated
                $useableCertificate->setRevokeCertificate(false);
                break;
            case CertificateBehavior::ON_DEMAND:
                // flag value as user requested
                break;
            case CertificateBehavior::AUTO:
                $useableCertificate->setRevokeCertificate(true);
                break;
            case CertificateBehavior::SPECIFIC:
                $useableCertificate->setRevokeCertificate($this->getSpecificRevokeCertificate($target, $useableCertificate));
                break;
        }
        // getRevokeCertificate is updated - ready to execute revocation if needed
        if ($useableCertificate->getRevokeCertificate() && $useableCertificate->getCertificate() && $useableCertificate->getCertificate()->hasCertificate()) {
            $this->certificateManager->revokeCertificate($useableCertificate->getCertificate());
        }
    }

    // Method provides generate certificate flag value for specific automatic behaviors based on current state
    protected function getSpecificGenerateCertificate(Device|User $target, UseableCertificate $useableCertificate): ?bool
    {
        switch ($useableCertificate->getCertificateType()->getCertificateCategory()) {
            case CertificateCategory::DEVICE_VPN:
                if ($this->configurationManager->isVpnSecuritySuiteAvailable()) {
                    return true;
                }
                break;

            case CertificateCategory::TECHNICIAN_VPN:
                if ($this->configurationManager->isVpnSecuritySuiteAvailable()) {
                    if ($target->getRoleVpn()) {
                        return true;
                    }
                    if ($target->getRoleSmartems() && !$target->getRoleVpn()) {
                        return false;
                    }

                    // admin role is on demand
                    return $useableCertificate->getGenerateCertificate();
                }
                break;
        }

        // Default behavior none
        return false;
    }

    // Method provides revoke certificate flag value for specific automatic behaviors based on current state
    protected function getSpecificRevokeCertificate(Device|User $target, UseableCertificate $useableCertificate): ?bool
    {
        switch ($useableCertificate->getCertificateType()->getCertificateCategory()) {
            case CertificateCategory::DEVICE_VPN:
                if ($this->configurationManager->isVpnSecuritySuiteAvailable()) {
                    // In this case it's specific behavior is on demand
                    return $useableCertificate->getRevokeCertificate();
                }
                break;

            case CertificateCategory::TECHNICIAN_VPN:
                if ($this->configurationManager->isVpnSecuritySuiteAvailable()) {
                    if ($target->getRoleVpn()) {
                        return true;
                    }
                    // other roles are false
                    if (!$target->getRoleAdmin() && !$target->getRoleVpn()) {
                        return false;
                    }
                    // admin role is on demand
                    return $useableCertificate->getRevokeCertificate();
                }
                break;
        }

        // Default behavior none
        return false;
    }

    protected function updateUsableCertificateWithCertificateBehaviour(UseableCertificate $useableCertificate, Device|User $target): void
    {
        if (!$target->getCertificateBehaviours()) {
            return;
        }

        foreach ($target->getCertificateBehaviours() as $certificateBehaviour) {
            if ($useableCertificate->getCertificateType()->getId() === $certificateBehaviour->getCertificateType()->getId()) {
                $useableCertificate->setGenerateCertificate($certificateBehaviour->getGenerateCertificate());
                $useableCertificate->setRevokeCertificate($certificateBehaviour->getRevokeCertificate());

                return;
            }
        }
    }
}
