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

namespace App\Controller\Api;

use App\Attribute\Areas;
use App\Form\AuthenticationChangePasswordRequiredType;
use App\Form\AuthenticationTotpRequiredType;
use App\Model\AuthenticationData;
use App\Service\Helper\AuthenticationManagerTrait;
use App\Service\Helper\MicrosoftOidcManagerTrait;
use App\Service\Helper\PasswordManagerTrait;
use App\Service\Helper\UserTrait;
use App\Service\Helper\ValidatorTrait;
use Carve\ApiBundle\Attribute as Api;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Service\Attribute\Required;

#[Rest\Route('/authentication')]
#[Rest\View(serializerGroups: ['public'])]
// Area 'authenticationData' is a special case. Do not use it anywhere else. Read more in Attribute\Areas
#[Areas(['authenticationData', 'admin', 'smartems', 'vpnsecuritysuite'])]
#[OA\Tag(name: 'Authentication')]
class AuthenticationController extends AbstractFOSRestController
{
    use PasswordManagerTrait;
    use AuthenticationManagerTrait;
    use MicrosoftOidcManagerTrait;
    // UserTrait is providing $this->tokenStorage
    use UserTrait;
    use ValidatorTrait;

    #[Required]
    public string $refreshTokenParameterName;

    /**
     * @var RefreshTokenManagerInterface
     */
    protected $refreshTokenManager;

    /**
     * @var JWTTokenManagerInterface
     */
    protected $jwtManager;

    /**
     * @var AuthenticationSuccessHandler
     */
    protected $authenticationSuccessHandler;

    #[Required]
    public function setRefreshTokenManager(RefreshTokenManagerInterface $refreshTokenManager)
    {
        $this->refreshTokenManager = $refreshTokenManager;
    }

    #[Required]
    public function setJwtManager(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    #[Required]
    public function setAuthenticationSuccessHandler(AuthenticationSuccessHandler $authenticationSuccessHandler)
    {
        $this->authenticationSuccessHandler = $authenticationSuccessHandler;
    }

    #[Rest\Get('/token/extend/{refreshTokenString}')]
    #[Api\Summary('Extend refresh token for another access token TTL')]
    #[Api\Response204('Correct refresh token extended successfully')]
    #[Api\Parameter(in: 'path', name: 'refreshTokenString', description: 'Refresh token string')]
    public function extendAction(string $refreshTokenString, int $accessTokenTtl)
    {
        $refreshToken = $this->refreshTokenManager->get($refreshTokenString);
        if (!$refreshToken) {
            return;
        }

        if (!$refreshToken->isValid()) {
            return;
        }

        $extendedValid = new \DateTime();
        $extendedValid->modify('+'.$accessTokenTtl.' seconds');
        $refreshToken->setValid($extendedValid);

        $this->refreshTokenManager->save($refreshToken);

        // Empty response to avoid giving any information about validity of refreshTokenString
        return;
    }

    #[Rest\Post('/change/password/required')]
    #[Api\Summary('Change authenticated user password when password change is required. Password change is required when authenticated user roles include ROLE_CHANGEPASSWORDREQUIRED')]
    #[Api\RequestBody(content: new NA\Model(type: AuthenticationChangePasswordRequiredType::class))]
    #[Api\Response200(description: 'Returns updated authentication data', content: new NA\Model(type: AuthenticationData::class))]
    #[Api\Response400]
    public function changePasswordRequiredAction(Request $request)
    {
        $user = $this->getUser();

        $form = $this->createForm(AuthenticationChangePasswordRequiredType::class, null, ['user' => $user]);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPlainPassword = $form->get('newPlainPassword')->getData();

            $this->passwordManager->changePassword($user, $newPlainPassword);

            $token = $this->jwtManager->create($user);
            $lastRefreshToken = $this->refreshTokenManager->getLastFromUsername($user->getUsername());

            $data = [];
            $data['token'] = $token;
            $data[$this->refreshTokenParameterName] = $lastRefreshToken ? $lastRefreshToken->getRefreshToken() : null;

            $data = $this->authenticationManager->extendAuthenticationData($user, $data);

            return $data;
        }

        return $form;
    }

    #[Rest\Post('/totp/required')]
    #[Api\Summary('Provide TOTP token. TOTP token is required when authenticated user roles include ROLE_TOTPREQUIRED')]
    #[Api\RequestBody(content: new NA\Model(type: AuthenticationTotpRequiredType::class))]
    #[Api\Response200(description: 'Returns updated authentication data', content: new NA\Model(type: AuthenticationData::class))]
    #[Api\Response204('Invalid TOTP token')]
    #[Api\Response400]
    public function totpRequiredAction(Request $request)
    {
        $form = $this->createForm(AuthenticationTotpRequiredType::class);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);
        $user = $this->getUser();

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $user->setTotpRequired(false);

                $token = $this->jwtManager->createFromPayload($user, ['totpAuthenticated' => true]);
                $lastRefreshToken = $this->refreshTokenManager->getLastFromUsername($user->getUsername());

                $data = [];
                $data['token'] = $token;
                $data[$this->refreshTokenParameterName] = $lastRefreshToken ? $lastRefreshToken->getRefreshToken() : null;

                $data = $this->authenticationManager->extendAuthenticationData($user, $data);

                // Note: resetLoginAttempts function does not execute entityManager->flush()
                $this->authenticationManager->resetLoginAttempts($user);
                $this->authenticationManager->registerLoginAttempt(true, true, $user->getUsername(), $user);

                return $data;
            } else {
                // Note: increaseFailedLoginAttempts function does not execute entityManager->flush()
                $this->authenticationManager->increaseFailedLoginAttempts($user);
                $this->authenticationManager->registerLoginAttempt(true, false, $user->getUsername(), $user);
            }
        }

        return $form;
    }

    #[Rest\Get('/get/roles')]
    #[Api\Summary('Returns authentication data')]
    #[Api\Response200(description: 'Returns authentication data', content: new NA\Model(type: AuthenticationData::class))]
    public function getRolesAction()
    {
        $user = $this->getUser();
        $token = $this->jwtManager->create($user);
        $lastRefreshToken = $this->refreshTokenManager->getLastFromUsername($user->getUsername());

        $data = [];
        $data['token'] = $token;
        $data[$this->refreshTokenParameterName] = $lastRefreshToken ? $lastRefreshToken->getRefreshToken() : null;

        return $this->authenticationManager->extendAuthenticationData($user, $data);
    }

    #[Rest\Get('/sso/microsoftoidc/redirect')]
    #[Api\Summary('Get Microsoft Entra ID with OpenID Connect authorization URL')]
    #[Api\Response200(description: 'Microsoft Entra ID with OpenID Connect authorization URL')]
    public function ssoMicrosoftOidcRedirectAction()
    {
        if (!$this->microsoftOidcManager->isEnabled()) {
            throw new NotFoundHttpException();
        }

        $authorizationUrl = $this->microsoftOidcManager->getAuthorizationUrl();

        return $this->redirect($authorizationUrl);
    }

    #[Rest\Get('/sso/microsoftoidc/custom/redirect')]
    #[Api\Summary('Get Microsoft Entra ID with OpenID Connect authorization URL with custom redirect URL. Requires enabling in single sign-on (SSO) configuration')]
    #[OA\Parameter(in: 'query', name: 'redirect', description: 'Custom redirect URL')]
    #[Api\Response200(description: 'Microsoft Entra ID with OpenID Connect authorization URL')]
    #[Api\Response400(description: 'Missing or invalid redirect URL')]
    public function ssoMicrosoftOidcCustomRedirectAction(Request $request)
    {
        // Method will also check if sso is enabled
        if (!$this->microsoftOidcManager->isCustomRedirectUrlAllowed()) {
            throw new NotFoundHttpException();
        }

        // Using this solution because due to whole URL placed in path, symfony is not able to parse (route) it correctly
        $redirect = $request->query->get('redirect');

        if (!$redirect) {
            throw new HttpException(400, 'Missing redirect URL');
        }

        $errors = $this->validator->validate($redirect, new Assert\Url());
        if (0 !== count($errors)) {
            throw new HttpException(400, 'Invalid redirect URL');
        }

        $authorizationUrl = $this->microsoftOidcManager->getAuthorizationUrl($redirect);

        return $this->redirect($authorizationUrl);
    }

    #[Rest\Get('/sso/microsoftoidc/authorize/{code}/{state}')]
    #[Api\Summary('Authorize user using code and state from Microsoft Entra ID with OpenID Connect')]
    #[Api\Parameter(in: 'path', name: 'code', description: 'Code from Microsoft Entra ID with OpenID Connect')]
    #[Api\Parameter(in: 'path', name: 'state', description: 'State from Microsoft Entra ID with OpenID Connect')]
    #[Api\Response200(description: 'Returns authentication data', content: new NA\Model(type: AuthenticationData::class))]
    public function ssoMicrosoftOidcAuthorizeAction(string $code, string $state)
    {
        if (!$this->microsoftOidcManager->isEnabled()) {
            throw new NotFoundHttpException();
        }

        $user = $this->microsoftOidcManager->processAuthorization($code, $state);

        // Login user so authentication handler can properly serialize roles
        $token = new UsernamePasswordToken($user, 'api', $user->getRoles());
        $this->tokenStorage->setToken($token);

        // lastLoginAt will be flushed by registerLoginAttempt()
        $user->setLastLoginAt(new \DateTime());
        // Note: resetLoginAttempts function does not execute entityManager->flush()
        $this->authenticationManager->resetLoginAttempts($user);
        $this->authenticationManager->registerLoginAttempt(true, false, $user->getUsername(), $user);

        // Prepare response using lexik_jwt_authentication.handler.authentication_success which is also used in default json login form
        return $this->authenticationSuccessHandler->handleAuthenticationSuccess($user);
    }

    #[Rest\Get('/sso/microsoftoidc/custom/authorize/{code}/{state}')]
    #[Api\Summary('Authorize user using code and state from Microsoft Entra ID with OpenID Connect with custom redirect URL. Requires enabling in single sign-on (SSO) configuration')]
    #[Api\Parameter(in: 'path', name: 'code', description: 'Code from Microsoft Entra ID with OpenID Connect')]
    #[Api\Parameter(in: 'path', name: 'state', description: 'State from Microsoft Entra ID with OpenID Connect')]
    #[OA\Parameter(in: 'query', name: 'redirect', description: 'Custom redirect URL')]
    #[Api\Response200(description: 'Returns authentication data', content: new NA\Model(type: AuthenticationData::class))]
    #[Api\Response400(description: 'Missing or invalid redirect URL')]
    public function ssoMicrosoftOidcCustomAuthorizeAction(Request $request, string $code, string $state)
    {
        // Method will also check if sso is enabled
        if (!$this->microsoftOidcManager->isCustomRedirectUrlAllowed()) {
            throw new NotFoundHttpException();
        }

        // Using this solution because due to whole URL placed in path, symfony is not able to parse (route) it correctly
        $redirect = $request->query->get('redirect');

        if (!$redirect) {
            throw new HttpException(400, 'Missing redirect URL');
        }

        $errors = $this->validator->validate($redirect, new Assert\Url());
        if (0 !== count($errors)) {
            throw new HttpException(400, 'Invalid redirect URL');
        }

        $user = $this->microsoftOidcManager->processAuthorization($code, $state, $redirect);

        // Login user so authentication handler can properly serialize roles
        $token = new UsernamePasswordToken($user, 'api', $user->getRoles());
        $this->tokenStorage->setToken($token);

        // Prepare response using lexik_jwt_authentication.handler.authentication_success which is also used in default json login form
        return $this->authenticationSuccessHandler->handleAuthenticationSuccess($user);
    }

    #[Rest\Get('/sso/microsoftoidc/logout')]
    #[OA\Parameter(in: 'query', name: 'sid', description: 'Session ID from Microsoft Entra ID with OpenID Connect')]
    #[Api\Summary('Logout user using sid from Microsoft Entra ID with OpenID Connect')]
    #[Api\Response200(description: 'Successfull response')]
    public function ssoMicrosoftOidcLogoutAction(Request $request)
    {
        if (!$this->microsoftOidcManager->isEnabled()) {
            throw new NotFoundHttpException();
        }

        $sid = $request->query->get('sid');
        if ($sid) {
            $this->microsoftOidcManager->processLogout($sid);
        }

        // Microsoft expects response with status 200
        return new Response();
    }
}
