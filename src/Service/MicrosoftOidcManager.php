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

namespace App\Service;

use App\Deny\CertificateDenyInterface;
use App\Entity\Certificate;
use App\Entity\CertificateType;
use App\Entity\ConfigurationMicrosoftOidcRoleMapping;
use App\Entity\MicrosoftOidcAuthorizationState;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Enum\MicrosoftOidcCredential;
use App\Enum\MicrosoftOidcRole;
use App\Enum\PkiType;
use App\Enum\SingleSignOn;
use App\Model\SsoUser;
use App\Service\Helper\CertificateManagerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\RouterInterfaceTrait;
use Carve\ApiBundle\Exception\RequestExecutionException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\Validator;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

class MicrosoftOidcManager
{
    use EntityManagerTrait;
    use EncryptionManagerTrait;
    use ConfigurationManagerTrait;
    use RouterInterfaceTrait;
    use CertificateManagerTrait;

    public const OAUTH_AUTHORITY = 'https://login.microsoftonline.com/';
    public const OAUTH_AUTHORIZE_ENDPOINT = '/oauth2/v2.0/authorize';
    public const OAUTH_TOKEN_ENDPOINT = '/oauth2/v2.0/token';

    public function isEnabled(): bool
    {
        return SingleSignOn::MICROSOFT_OIDC === $this->getConfiguration()->getSingleSignOn();
    }

    public function isCustomRedirectUrlAllowed(): bool
    {
        return $this->isEnabled() && $this->getConfiguration()->getSsoAllowCustomRedirectUrl();
    }

    public function getAuthorizationUrl(?string $redirectUri = null): string
    {
        $oAuthClient = $this->getOAuthClient($redirectUri);
        $authUrl = $oAuthClient->getAuthorizationUrl();

        $authorizationState = new MicrosoftOidcAuthorizationState();
        $authorizationState->setState($oAuthClient->getState());
        $authorizationState->setPkceCode($oAuthClient->getPkceCode());
        // Arbitrary time of +5 minutes
        $authorizationState->setExpireAt(new \DateTime('+5 minutes'));

        $this->entityManager->persist($authorizationState);
        $this->entityManager->flush();

        return $authUrl;
    }

    public function processAuthorization(string $code, string $state, ?string $redirectUri = null): User
    {
        $authorizationState = $this->findState($state);
        if (!$authorizationState) {
            throw new RequestExecutionException('error.sso.microsoftOidc.stateNotFound');
        }

        $pkceCode = $authorizationState->getPkceCode();

        $this->entityManager->remove($authorizationState);
        $this->entityManager->flush();

        $oAuthClient = $this->getOAuthClient($redirectUri);
        $oAuthClient->setPkceCode($pkceCode);

        $accessTokenOptions = [
            'code' => $code,
        ];

        $configuration = $this->getConfiguration();
        switch ($configuration->getMicrosoftOidcCredential()) {
            case MicrosoftOidcCredential::CERTIFICATE_UPLOAD:
            case MicrosoftOidcCredential::CERTIFICATE_GENERATE:
                $accessTokenOptions['client_assertion_type'] = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
                $accessTokenOptions['client_assertion'] = $this->getAccessTokenClientAssertion();
                break;
        }

        try {
            $accessToken = $oAuthClient->getAccessToken('authorization_code', $accessTokenOptions);
        } catch (\Throwable $e) {
            throw new RequestExecutionException('error.sso.microsoftOidc.accessToken.invalid', ['message' => $e->getMessage()]);
        }

        $this->validateAccessToken($accessToken);
        $idToken = $this->getIdToken($accessToken);

        $token = $this->getToken($idToken);
        $this->validateToken($token);

        $claims = $token->claims();

        // `sub` is unique identifier for user
        $username = $claims->get('sub');
        $name = $claims->get('preferred_username');
        if (!$name) {
            $name = $username;
        }

        $sessionId = $claims->get('sid');
        $roles = $claims->get('roles', []);

        $ssoUser = $this->getSsoUser($username, $name, $roles, $sessionId);
        $user = $this->processSsoUser($ssoUser);

        return $user;
    }

    protected function validateAccessToken(AccessToken $accessToken)
    {
        if ($accessToken->hasExpired()) {
            throw new RequestExecutionException('error.sso.microsoftOidc.accessToken.expired');
        }
    }

    protected function getIdToken(AccessToken $accessToken): string
    {
        $values = $accessToken->getValues();
        $idToken = $values['id_token'] ?? null;
        if (!$idToken) {
            throw new RequestExecutionException('error.sso.microsoftOidc.accessToken.missingIdtoken');
        }

        return $idToken;
    }

    protected function getToken(string $idToken): Token
    {
        $parser = new Parser(new JoseEncoder());

        try {
            $token = $parser->parse($idToken);
        } catch (InvalidTokenStructure $e) {
            throw new RequestExecutionException('error.sso.microsoftOidc.idTokenInvalid');
        } catch (UnsupportedHeaderFound $e) {
            throw new RequestExecutionException('error.sso.microsoftOidc.idTokenInvalid');
        }

        return $token;
    }

    protected function validateToken(Token $token)
    {
        $validator = new Validator();
        $configuration = $this->getConfiguration();

        // Validates `aud` claim
        if (!$validator->validate($token, new PermittedFor($configuration->getMicrosoftOidcAppId()))) {
            throw new RequestExecutionException('error.sso.microsoftOidc.idTokenInvalid');
        }

        // Validates `iat`, `nbf` and `exp` claims
        if (!$validator->validate($token, new StrictValidAt(SystemClock::fromUTC()))) {
            throw new RequestExecutionException('error.sso.microsoftOidc.idTokenInvalid');
        }
    }

    protected function getSsoUser(string $username, string $name, array $roles, ?string $sessionId = null): ?SsoUser
    {
        $ssoUser = new SsoUser();
        $ssoUser->setUsername($username);
        $ssoUser->setName($name);
        $ssoUser->setSessionId($sessionId);

        $mappings = $this->getRepository(ConfigurationMicrosoftOidcRoleMapping::class)->findBy([
            'roleName' => $roles,
        ]);

        $adminRoleFound = false;
        foreach ($mappings as $mapping) {
            if (MicrosoftOidcRole::ADMIN === $mapping->getMicrosoftOidcRole()) {
                $adminRoleFound = true;
                break;
            }
        }

        if ($adminRoleFound) {
            $ssoUser->setRoleAdmin(true);
        } else {
            foreach ($mappings as $mapping) {
                switch ($mapping->getMicrosoftOidcRole()) {
                    case MicrosoftOidcRole::SMARTEMS:
                        $ssoUser->setRoleSmartems(true);
                        break;
                    case MicrosoftOidcRole::VPN:
                        $ssoUser->setRoleVpn(true);

                        if ($mapping->getRoleVpnEndpointDevices()) {
                            $ssoUser->setRoleVpnEndpointDevices(true);
                        }
                        break;
                    case MicrosoftOidcRole::SMARTEMS_VPN:
                        $ssoUser->setRoleSmartems(true);
                        $ssoUser->setRoleVpn(true);

                        if ($mapping->getRoleVpnEndpointDevices()) {
                            $ssoUser->setRoleVpnEndpointDevices(true);
                        }
                        break;
                }

                $accessTags = $ssoUser->getAccessTags();

                foreach ($mapping->getAccessTags() as $accessTag) {
                    $accessTags[] = $accessTag;
                }

                $ssoUser->setAccessTags($accessTags);
            }
        }

        return $ssoUser;
    }

    protected function processSsoUser(SsoUser $ssoUser): User
    {
        $user = $this->getRepository(User::class)->findOneBy([
            'username' => $ssoUser->getUsername(),
        ]);

        $created = false;

        if (!$user) {
            $user = new User();
            $user->setUsername($ssoUser->getUsername());
            // App\Security\Hasher\SsoHasher will not allow to login using username and password. Set the password to a random string anyway
            $password = bin2hex(random_bytes(16));
            $user->setPassword($password);
            $user->setEnabled(true);
            $user->setSsoUser(true);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $created = true;
        }

        if (!$user->getSsoUser()) {
            throw new RequestExecutionException('error.sso.microsoftOidc.userFoundWithoutSsoUser');
        }

        $user->setSsoName($ssoUser->getName());
        $user->setSsoSessionId($ssoUser->getSessionId());

        // Always reset roles and access tags before mapping
        $user->setRoleAdmin(false);
        $user->setRoleSmartems(false);
        $user->setRoleVpn(false);
        $user->setRoleVpnEndpointDevices(false);
        $user->getAccessTags()->clear();

        // Mapping roles is done as explicitly as possible
        if ($ssoUser->getRoleAdmin()) {
            $user->setRoleAdmin(true);
        }

        if ($ssoUser->getRoleSmartems()) {
            $user->setRoleSmartems(true);
        }

        if ($ssoUser->getRoleVpn()) {
            $user->setRoleVpn(true);
        }

        if ($ssoUser->getRoleVpnEndpointDevices()) {
            $user->setRoleVpnEndpointDevices(true);
        }

        foreach ($ssoUser->getAccessTags() as $accessTag) {
            $user->addAccessTag($accessTag);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        if ($created && $this->canGenerateTechnicianVpnCertificate($user)) {
            $this->generateTechnicianVpnCertificate($user);
        }

        return $user;
    }

    public function processLogout(string $sid): void
    {
        $user = $this->getRepository(User::class)->findOneBy([
            'ssoSessionId' => $sid,
        ]);

        if (!$user) {
            return;
        }

        $user->setSsoLogoutAt(new \DateTime());
        $user->setSsoSessionId(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $queryBuilder = $this->getRepository(RefreshToken::class)->createQueryBuilder('rt');
        $queryBuilder->delete();
        $queryBuilder->andWhere('rt.username = :username');
        $queryBuilder->setParameter('username', $user->getUsername());
        $queryBuilder->getQuery()->execute();
    }

    protected function canGenerateTechnicianVpnCertificate(User $user): bool
    {
        if (!$this->getConfiguration()->getSsoRoleVpnCertificateAutoGenerate()) {
            return false;
        }

        if (!$user->getRoleVpn()) {
            return false;
        }

        $certificate = $this->getTechnicianVpnCertificate($user);
        if (!$certificate) {
            return false;
        }

        // Cannot use App\Deny\CertificateDeny::class due to CertificateDenyInterface::GENERATE_CERTIFICATE verifying
        // $this->isGranted('ROLE_ADMIN_SCEP') which probably should not be there in the first place, but it requires a lot of refactoring and testing
        $certificateType = $certificate->getCertificateType();
        if (!$certificateType->getIsAvailable()) {
            return false;
        }

        if (!$certificateType->getPkiEnabled() || PkiType::NONE === $certificateType->getPkiType()) {
            return false;
        }

        if ($certificate->hasAnyCertificatePart()) {
            return false;
        }

        return true;
    }

    protected function generateTechnicianVpnCertificate(User $user): void
    {
        $certificate = $this->getTechnicianVpnCertificate($user);
        $this->certificateManager->generateCertificate($certificate);
    }

    protected function getTechnicianVpnCertificate(User $user): ?Certificate
    {
        $certificateType = $this->getRepository(CertificateType::class)->findTechnicianVpn();
        if (!$certificateType) {
            return null;
        }

        $certificate = $this->getRepository(Certificate::class)->findOneBy([
            'user' => $user,
            'certificateType' => $certificateType,
        ]);

        if (!$certificate) {
            $certificate = new Certificate();
            $certificate->setUser($user);
            $certificate->setCertificateType($certificateType);
        }

        return $certificate;
    }

    protected function findState(string $state): ?MicrosoftOidcAuthorizationState
    {
        $now = new \DateTime();

        $queryBuilder = $this->getRepository(MicrosoftOidcAuthorizationState::class)->createQueryBuilder('s');
        $queryBuilder->andWhere('s.state = :state');
        $queryBuilder->andWhere('s.expireAt > :now');
        $queryBuilder->setParameter('state', $state);
        $queryBuilder->setParameter('now', $now);
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    protected function getAccessTokenClientAssertion(): string
    {
        $configuration = $this->getConfiguration();
        $credential = $configuration->getMicrosoftOidcCredential();
        $supportedCredentials = [
            MicrosoftOidcCredential::CERTIFICATE_UPLOAD,
            MicrosoftOidcCredential::CERTIFICATE_GENERATE,
        ];

        if (!$credential) {
            throw new \Exception('Missing credential');
        }

        if (!in_array($credential, $supportedCredentials)) {
            throw new \Exception('Unsupported credential "'.$credential->value.'"');
        }

        switch ($configuration->getMicrosoftOidcCredential()) {
            case MicrosoftOidcCredential::CERTIFICATE_UPLOAD:
                $publicKeyThumbprint = $configuration->getMicrosoftOidcUploadedCertificatePublicThumbprint();
                $privateKeyContent = $this->encryptionManager->decrypt($configuration->getMicrosoftOidcUploadedCertificatePrivate());
                break;
            case MicrosoftOidcCredential::CERTIFICATE_GENERATE:
                $publicKeyThumbprint = $configuration->getMicrosoftOidcGeneratedCertificatePublicThumbprint();
                $privateKeyContent = $this->encryptionManager->decrypt($configuration->getMicrosoftOidcGeneratedCertificatePrivate());
                break;
        }

        $algorithm = new Sha256();
        $privateKey = InMemory::plainText($privateKeyContent);
        $iat = new \DateTimeImmutable('-15 seconds');
        $nbf = new \DateTimeImmutable('-15 seconds');
        $exp = new \DateTimeImmutable('+1 minute');

        $x5t = $publicKeyThumbprint;
        $x5t = \hex2bin($x5t);
        // base64 URL encode
        $x5t = \str_replace(['+', '/', '='], ['-', '_', ''], \base64_encode($x5t));

        $uuid4 = Uuid::v4()->toRfc4122();
        $jti = $uuid4;

        $tokenBuilder = new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());
        $tokenBuilder = $tokenBuilder
            ->issuedBy($configuration->getMicrosoftOidcAppId())
            ->permittedFor('https://login.microsoftonline.com/'.$configuration->getMicrosoftOidcDirectoryId().'/oauth2/v2.0/token')
            ->identifiedBy($jti)
            ->relatedTo($configuration->getMicrosoftOidcAppId())
            ->issuedAt($iat)
            ->canOnlyBeUsedAfter($nbf)
            ->expiresAt($exp)
            ->withHeader('x5t', $x5t);

        $token = $tokenBuilder->getToken($algorithm, $privateKey);

        return $token->toString();
    }

    protected function getOAuthClient(?string $redirectUri = null): GenericProvider
    {
        $configuration = $this->getConfiguration();

        if (null === $redirectUri) {
            $redirectUri = $this->routerInterface->generate('app_app_app', [], RouterInterface::ABSOLUTE_URL);
            // Frontend application route that will perform SSO login using data coming from Microsoft (code and state)
            // app/src/js/routes/SsoMicrosoftOidcLogin.tsx
            $redirectUri .= 'authentication/sso/microsoftoidc/login';
        }

        $options = [
            'clientId' => $configuration->getMicrosoftOidcAppId(),
            'redirectUri' => $redirectUri,
            'urlAuthorize' => self::OAUTH_AUTHORITY.$configuration->getMicrosoftOidcDirectoryId().self::OAUTH_AUTHORIZE_ENDPOINT,
            'urlAccessToken' => self::OAUTH_AUTHORITY.$configuration->getMicrosoftOidcDirectoryId().self::OAUTH_TOKEN_ENDPOINT,
            'urlResourceOwnerDetails' => '',
            'scopes' => 'openid profile',
            'timeout' => $configuration->getMicrosoftOidcTimeout(),
            'pkceMethod' => GenericProvider::PKCE_METHOD_S256,
        ];

        if (MicrosoftOidcCredential::CLIENT_SECRET === $configuration->getMicrosoftOidcCredential()) {
            $options['clientSecret'] = $this->encryptionManager->decrypt($configuration->getMicrosoftOidcClientSecret());
        }

        return new GenericProvider($options);
    }
}
