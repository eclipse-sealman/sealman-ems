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

namespace Tests\Suites\Unit\MicrosoftOidcManager;

use App\DataFixtures as ProdFixtures;
use App\Entity\MicrosoftOidcAuthorizationState;
use App\Entity\User;
use App\Service\MicrosoftOidcManager;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Utility\Accessor;

/**
 * I do not see a way to test:
 * - PKCE code
 * - Full login process
 * As it needs to go through microsoft.
 */
#[Group('full')]
#[Group('smoke')]
class MicrosoftOidcManagerTest extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\CertificateTypeFixtures::class,
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\UserDeviceSecretCredentialsFixtures::class,
            ProdFixtures\UserDeviceX509CredentialsFixtures::class,
            ProdFixtures\UserFixtures::class,
            TestFixtures\Configuration\SsoMicrosoftOidcFixtures::class,
        ];
    }

    public function testAuthorizationUrl()
    {
        $microsoftOidcManager = $this->getService(MicrosoftOidcManager::class);

        $authorizationUrl = $microsoftOidcManager->getAuthorizationUrl();

        $this->assertIsString($authorizationUrl);
    }

    public function testAuthorizationUrlWithCustomRedirectUrl()
    {
        $customRedirectUrl = 'https://example.com/custom-redirect-url';

        $microsoftOidcManager = $this->getService(MicrosoftOidcManager::class);

        $authorizationUrl = $microsoftOidcManager->getAuthorizationUrl($customRedirectUrl);

        $this->assertIsString($authorizationUrl);
        $this->assertStringContainsString(urlencode($customRedirectUrl), $authorizationUrl);
    }

    public function testStateInvalid()
    {
        $code = 'invalid';
        $state = 'invalid';

        $this->jsonGet('/web/api/authentication/sso/microsoftoidc/authorize/'.$code.'/'.$state);

        $this->assertResponse409ErrorMessage('error.sso.microsoftOidc.stateNotFound');
    }

    public function testStateExpired()
    {
        $microsoftOidcManager = $this->getService(MicrosoftOidcManager::class);
        // Trigger creation of authorization state
        $microsoftOidcManager->getAuthorizationUrl();

        $entityManager = $this->getEntityManager();
        $authorizationState = $this->getRepository(MicrosoftOidcAuthorizationState::class)->findOneBy([], ['id' => 'DESC']);
        $authorizationState->setExpireAt(new \DateTime('-1 second'));
        $entityManager->persist($authorizationState);
        $entityManager->flush();

        $code = 'invalid';
        $state = $authorizationState->getState();

        $this->jsonGet('/web/api/authentication/sso/microsoftoidc/authorize/'.$code.'/'.$state);

        $this->assertResponse409ErrorMessage('error.sso.microsoftOidc.stateNotFound');
    }

    public function testCodeInvalid()
    {
        $microsoftOidcManager = $this->getService(MicrosoftOidcManager::class);
        // Trigger creation of authorization state
        $microsoftOidcManager->getAuthorizationUrl();

        $authorizationState = $this->getRepository(MicrosoftOidcAuthorizationState::class)->findOneBy([], ['id' => 'DESC']);

        $code = 'invalid';
        $state = $authorizationState->getState();

        $this->jsonGet('/web/api/authentication/sso/microsoftoidc/authorize/'.$code.'/'.$state);

        $this->assertResponse409ErrorMessage('error.sso.microsoftOidc.accessToken.invalid');
    }

    public function testRoleMappingAdmin()
    {
        $this->testSsoUserRoleMapping(['admin'], true, false, false, false, [], '-admin');
    }

    public function testRoleMappingSmartems()
    {
        $this->testSsoUserRoleMapping(['smartems'], false, true, false, false, ['AT SSO 1'], '-smartems');
    }

    public function testRoleMappingVpn()
    {
        $this->testSsoUserRoleMapping(['vpn'], false, false, true, false, ['AT SSO 2'], '-vpn');
        $this->testSsoUserRoleMapping(['vpn_ed'], false, false, true, true, ['AT SSO 2'], '-vpn_ed');
    }

    public function testRoleMappingSmartemsVpn()
    {
        $this->testSsoUserRoleMapping(['smartemsVpn'], false, true, true, false, ['AT SSO 2', 'AT SSO 3'], '-smartemsVpn');
        $this->testSsoUserRoleMapping(['smartemsVpn_ed'], false, true, true, true, ['AT SSO 2', 'AT SSO 3'], '-smartemsVpn_ed');
    }

    public function testRoleMappingEmpty()
    {
        $this->testSsoUserRoleMapping([], false, false, false, false, [], '-empty');
    }

    public function testRoleMappingInvalid()
    {
        $this->testSsoUserRoleMapping(['invalid'], false, false, false, false, [], '-invalid');
    }

    public function testRoleMappingAdminVpn()
    {
        $this->testSsoUserRoleMapping(['admin', 'vpn'], true, false, false, false, [], '-admin-vpn');
        $this->testSsoUserRoleMapping(['admin', 'vpn_ed'], true, false, false, false, [], '-admin-vpn_ed');
    }

    public function testRoleMappingSmartemsAndVpn()
    {
        $this->testSsoUserRoleMapping(['smartems', 'vpn'], false, true, true, false, ['AT SSO 1', 'AT SSO 2'], '-smartems-and-vpn');
        $this->testSsoUserRoleMapping(['smartems', 'vpn_ed'], false, true, true, true, ['AT SSO 1', 'AT SSO 2'], '-smartems-and-vpn_ed');
    }

    public function testRoleMappingSmartems2AndSmartemsVpn()
    {
        $this->testSsoUserRoleMapping(['smartems2', 'smartemsVpn'], false, true, true, false, ['AT SSO 1', 'AT SSO 2', 'AT SSO 3'], '-smartems2-and-smartems-vpn');
        $this->testSsoUserRoleMapping(['smartems2', 'smartemsVpn_ed'], false, true, true, true, ['AT SSO 1', 'AT SSO 2', 'AT SSO 3'], '-smartems2-and-smartems-vpn_ed');
    }

    public function testRoleMappingSmartemsVpnAndVpn()
    {
        $this->testSsoUserRoleMapping(['smartemsVpn', 'vpn'], false, true, true, false, ['AT SSO 2', 'AT SSO 3'], '-smartemsVpn-and-vpn');
        $this->testSsoUserRoleMapping(['smartemsVpn', 'vpn_ed'], false, true, true, true, ['AT SSO 2', 'AT SSO 3'], '-smartemsVpn-and-vpn_ed');
        $this->testSsoUserRoleMapping(['smartemsVpn_ed', 'vpn'], false, true, true, true, ['AT SSO 2', 'AT SSO 3'], '-smartemsVpn_ed-and-vpn');
        $this->testSsoUserRoleMapping(['smartemsVpn_ed', 'vpn_ed'], false, true, true, true, ['AT SSO 2', 'AT SSO 3'], '-smartemsVpn_ed-and-vpn_ed');
    }

    public function testRoleMappingSmartemsVpnClear()
    {
        $this->testSsoUserRoleMapping(['smartemsVpn'], false, true, true, false, ['AT SSO 2', 'AT SSO 3'], '-smartemsVpn-clear');
        $this->testSsoUserRoleMapping(['smartemsVpn_ed'], false, true, true, true, ['AT SSO 2', 'AT SSO 3'], '-smartemsVpn_ed-clear');
        $this->testSsoUserRoleMapping(['invalid'], false, false, false, false, [], '-smartemsVpn-clear');
    }

    public function testRoleMappingAdminClear()
    {
        $this->testSsoUserRoleMapping(['admin'], true, false, false, false, [], '-admin-clear');
        $this->testSsoUserRoleMapping(['invalid'], false, false, false, false, [], '-admin-clear');
    }

    public function testLogout()
    {
        $microsoftOidcManager = $this->getService(MicrosoftOidcManager::class);

        $sid = 'example_sid';
        $username = 'sso-user-sid';
        $ssoUser = Accessor::invoke($microsoftOidcManager, 'getSsoUser', [$username, $username, [], $sid]);
        $user = Accessor::invoke($microsoftOidcManager, 'processSsoUser', [$ssoUser]);

        $this->assertSame($sid, $user->getSsoSessionId());

        $this->jsonGet('/web/api/authentication/sso/microsoftoidc/logout?sid='.$sid);

        $this->assertResponseStatusCodeSame(200);

        $user = $this->getRepository(User::class)->find($user->getId());

        $this->assertNull($user->getSsoSessionId());
        $this->assertNotNull($user->getSsoLogoutAt());
    }

    /**
     * Logout endpoint should always respond with 200, even when sid is invalid.
     */
    public function testLogoutInvalidSid()
    {
        $sid = 'invalid_sid';
        $this->jsonGet('/web/api/authentication/sso/microsoftoidc/logout?sid='.$sid);
        $this->assertResponseStatusCodeSame(200);
    }

    protected function testSsoUserRoleMapping(array $ssoRoleNames, bool $roleAdmin, bool $roleSmartems, bool $roleVpn, bool $roleVpnEndpointDevices, array $accessTagNames, string $uniqueSuffix)
    {
        $microsoftOidcManager = $this->getService(MicrosoftOidcManager::class);

        $username = 'sso-user-'.$uniqueSuffix;
        $ssoUser = Accessor::invoke($microsoftOidcManager, 'getSsoUser', [$username, $username, $ssoRoleNames]);
        $user = Accessor::invoke($microsoftOidcManager, 'processSsoUser', [$ssoUser]);

        $this->assertUserPermissions($user, $roleAdmin, $roleSmartems, $roleVpn, $roleVpnEndpointDevices, $accessTagNames);
    }

    protected function assertUserPermissions(User $user, bool $roleAdmin, bool $roleSmartems, bool $roleVpn, bool $roleVpnEndpointDevices, array $accessTagNames)
    {
        $this->assertSame(true, $user->getSsoUser(), 'User do not have $ssoUser flag set to true');
        $this->assertSame($roleAdmin, $user->getRoleAdmin(), 'User do not have $roleAdmin flag set to '.($roleAdmin ? 'true' : 'false'));
        $this->assertSame($roleSmartems, $user->getRoleSmartems(), 'User do not have $roleSmartems flag set to '.($roleSmartems ? 'true' : 'false'));
        $this->assertSame($roleVpn, $user->getRoleVpn(), 'User do not have $roleVpn flag set to '.($roleVpn ? 'true' : 'false'));
        $this->assertSame($roleVpnEndpointDevices, $user->getRoleVpnEndpointDevices(), 'User do not have $roleVpnEndpointDevices flag set to '.($roleVpnEndpointDevices ? 'true' : 'false'));

        $accessTags = $user->getAccessTags();
        $this->assertSame(count($accessTagNames), count($accessTags));

        foreach ($accessTagNames as $accessTagName) {
            $exist = $accessTags->exists(function ($key, $value) use ($accessTagName) {
                return $value->getName() === $accessTagName;
            });
            $this->assertTrue($exist, 'Missing access tag "'.$accessTagName.'"');
        }
    }
}
