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

namespace Tests\DataFixtures\Configuration;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\AccessTag;
use App\Entity\Configuration;
use App\Entity\ConfigurationMicrosoftOidcRoleMapping;
use App\Enum\MicrosoftOidcCredential;
use App\Enum\MicrosoftOidcRole;
use App\Enum\SingleSignOn;
use App\Service\Helper\EncryptionManagerTrait;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SsoMicrosoftOidcFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use EncryptionManagerTrait;

    public function load(ObjectManager $manager): void
    {
        $at1 = new AccessTag();
        $at1->setName('AT SSO 1');
        $manager->persist($at1);

        $at2 = new AccessTag();
        $at2->setName('AT SSO 2');
        $manager->persist($at2);

        $at3 = new AccessTag();
        $at3->setName('AT SSO 3');
        $manager->persist($at3);

        $manager->flush();

        $configuration = $this->getReference(ProdFixtures\ConfigurationFixtures::CONFIGURATION_REFERENCE, Configuration::class);

        $configuration->setSingleSignOn(SingleSignOn::MICROSOFT_OIDC);

        $configuration->setMicrosoftOidcAppId('fake-app-id');
        $configuration->setMicrosoftOidcDirectoryId('fake-directory-id');
        $configuration->setMicrosoftOidcCredential(MicrosoftOidcCredential::CLIENT_SECRET);
        $configuration->setMicrosoftOidcClientSecret($this->encryptionManager->encrypt('fake-client-secret'));

        $mappingAdmin = new ConfigurationMicrosoftOidcRoleMapping();
        $mappingAdmin->setRoleName('admin');
        $mappingAdmin->setMicrosoftOidcRole(MicrosoftOidcRole::ADMIN);
        $configuration->addMicrosoftOidcRoleMapping($mappingAdmin);

        $mappingSmartems = new ConfigurationMicrosoftOidcRoleMapping();
        $mappingSmartems->setRoleName('smartems');
        $mappingSmartems->setMicrosoftOidcRole(MicrosoftOidcRole::SMARTEMS);
        $mappingSmartems->addAccessTag($at1);
        $configuration->addMicrosoftOidcRoleMapping($mappingSmartems);

        $mappingSmartems2 = new ConfigurationMicrosoftOidcRoleMapping();
        $mappingSmartems2->setRoleName('smartems2');
        $mappingSmartems2->setMicrosoftOidcRole(MicrosoftOidcRole::SMARTEMS);
        $mappingSmartems2->addAccessTag($at1);
        $mappingSmartems2->addAccessTag($at2);
        $configuration->addMicrosoftOidcRoleMapping($mappingSmartems2);

        $mappingVpn = new ConfigurationMicrosoftOidcRoleMapping();
        $mappingVpn->setRoleName('vpn');
        $mappingVpn->setMicrosoftOidcRole(MicrosoftOidcRole::VPN);
        $mappingVpn->addAccessTag($at2);
        $configuration->addMicrosoftOidcRoleMapping($mappingVpn);

        $mappingVpn = new ConfigurationMicrosoftOidcRoleMapping();
        $mappingVpn->setRoleName('vpn_ed');
        $mappingVpn->setMicrosoftOidcRole(MicrosoftOidcRole::VPN);
        $mappingVpn->setRoleVpnEndpointDevices(true);
        $mappingVpn->addAccessTag($at2);
        $configuration->addMicrosoftOidcRoleMapping($mappingVpn);

        $mappingSmartemsVpn = new ConfigurationMicrosoftOidcRoleMapping();
        $mappingSmartemsVpn->setRoleName('smartemsVpn');
        $mappingSmartemsVpn->setMicrosoftOidcRole(MicrosoftOidcRole::SMARTEMS_VPN);
        $mappingSmartemsVpn->addAccessTag($at2);
        $mappingSmartemsVpn->addAccessTag($at3);
        $configuration->addMicrosoftOidcRoleMapping($mappingSmartemsVpn);

        $mappingSmartemsVpn = new ConfigurationMicrosoftOidcRoleMapping();
        $mappingSmartemsVpn->setRoleName('smartemsVpn_ed');
        $mappingSmartemsVpn->setMicrosoftOidcRole(MicrosoftOidcRole::SMARTEMS_VPN);
        $mappingSmartemsVpn->setRoleVpnEndpointDevices(true);
        $mappingSmartemsVpn->addAccessTag($at2);
        $mappingSmartemsVpn->addAccessTag($at3);
        $configuration->addMicrosoftOidcRoleMapping($mappingSmartemsVpn);

        $manager->persist($configuration);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
        ];
    }
}
