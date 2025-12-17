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
use App\Entity\Configuration;
use App\Service\Helper\VpnAddressManagerTrait;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VpnFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use VpnAddressManagerTrait;

    public function load(ObjectManager $manager): void
    {
        $configuration = $this->getReference(ProdFixtures\ConfigurationFixtures::CONFIGURATION_REFERENCE, Configuration::class);

        $previousConfiguration = clone $configuration;

        $configuration->setOpnsenseUrl('https://localhost/fake');
        $configuration->setOpnsenseApiKey('fakeApiKey');
        $configuration->setOpnsenseApiSecret('fakeApiSecret');
        $configuration->setOpnsenseTimeout(15);

        $configuration->setDevicesOpenvpnServerDescription('Fake Devices OpenVPN');
        $configuration->setDevicesOpenvpnServerIndex(null);
        $configuration->setTechniciansOpenvpnServerDescription('Fake Technicians OpenVPN');
        $configuration->setTechniciansOpenvpnServerIndex(null);

        $configuration->setDevicesVpnNetworks('172.14.0.0/16');
        $configuration->setDevicesVirtualVpnNetworks('10.16.0.0/14');
        $configuration->setTechniciansVpnNetworks('192.168.164.0/24');

        $configuration->setDevicesVpnNetworksRanges('172.14.0.2-172.14.255.253');
        $configuration->setDevicesVirtualVpnNetworksRanges('10.16.0.2-10.19.255.253');
        $configuration->setTechniciansVpnNetworksRanges('192.168.164.2-192.168.164.253');

        $configuration->setDevicesOvpnTemplate('
dev tun
persist-tun
persist-key
auth SHA1
client
resolv-retry infinite
remote 127.0.0.1 1494 udp
lport 0
verify-x509-name "C=PL, ST=Lubelskie, L=Lublin, O=Example, emailAddress=local@example.com, CN=Fake Devices OpenVPN Server Certificate" subject
remote-cert-tls server
<ca>
$ca
</ca>
<cert>
$certificate
</cert>
<key>
$privateKey
</key>
<tls-auth>
#
# 2048 bit OpenVPN static key
#
-----BEGIN OpenVPN Static key V1-----
43ddb08f2b34191584bf2636cc8fcbbb
0a4a68b294abd5569dfc03fe11145b04
6459e67c816406d67cfc33b89a2beef9
123751acb8bd3b5783a039f1ac775661
404363a36ecb6db350a14247015340a4
5b6d510398fe9b59e77f66d19100d0ed
b038ba2b53171d18dc61aa3dc5eec3b0
81908c7b49175cc8344d2a5f21e41813
be76ef6e2d3f8fbfcd795278ece81ae9
068a4a3daadb263b9d788e50c5042c5b
c895963beba01906b39b144f7b2ba231
70faaaffd02b5fe38e1a8af81ec576b7
776306c6cd0715ce257511eda9fae955
438ff073177fdaf26e8bb82e9661b5d6
360154c8b0fae22179e07468f10b5c1a
cd1b421592fd0b74d81a6b7fddb4e1a9
-----END OpenVPN Static key V1-----
</tls-auth>
key-direction 1
            ');
        $configuration->setTechniciansOvpnTemplate('
dev tun
persist-tun
persist-key
auth SHA1
client
resolv-retry infinite
remote 127.0.0.1 1495 udp
lport 0
verify-x509-name "C=PL, ST=Lubelskie, L=Lublin, O=Example, emailAddress=local@example.com, CN=Fake Technicians OpenVPN Server Certificate" subject
remote-cert-tls server
<ca>
$ca
</ca>
<cert>
$certificate
</cert>
<key>
$privateKey
</key>
<tls-auth>
#
# 2048 bit OpenVPN static key
#
-----BEGIN OpenVPN Static key V1-----
0a8400db45d94392fd4aba59b4175df1
50a168609dd71894bfbdd4a4082135d7
28a47fe87861c1cc642136524ebd4986
106676a328e248f703c0fe50a08f2316
5956bd604ba00fb87bd3f36fcccd270c
2623498e2d8d823c2b578d0785eb0621
9068d4f28a65cae49de062effe653849
b7eb40a28edef754de8fbb6694dda653
fc54455d0e00ab40d7180e6103a8c7dc
9bf4aa6093188a56a0a18794b6232328
d55ad23cbd4b1a35dc6a264d7da0758a
eed0e01a03e9348fefea9a2e9f869ac6
72cc4d54d019e2ab6aa2ebd5613de205
11a31e782939cda8a7814d4dd5c46c97
404375bcd5745b591a2fb86f94618634
2e3f18dcc73ae8d0e22df6a6885a7579
-----END OpenVPN Static key V1-----
</tls-auth>
key-direction 1
            ');

        $this->vpnAddressManager->processConfigurationSubnetsChange($previousConfiguration, $configuration);

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
