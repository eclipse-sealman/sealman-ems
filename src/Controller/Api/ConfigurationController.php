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
use App\Entity\Configuration;
use App\Enum\MicrosoftOidcCredential;
use App\Form\ConfigurationDocumentationType;
use App\Form\ConfigurationGeneralType;
use App\Form\ConfigurationLogsType;
use App\Form\ConfigurationRadiusType;
use App\Form\ConfigurationSsoType;
use App\Form\ConfigurationTotpType;
use App\Form\ConfigurationVpnType;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\SymfonyDirTrait;
use App\Service\Helper\VpnAddressManagerTrait;
use App\Service\UploadManager;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Gedmo\Sluggable\Util\Urlizer;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\Route('/configuration')]
#[Rest\View(serializerGroups: ['public'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
#[OA\Tag('Configuration')]
class ConfigurationController extends AbstractApiController
{
    use ConfigurationManagerTrait;
    use VpnAddressManagerTrait;
    use SymfonyDirTrait;
    use EncryptionManagerTrait;

    #[Rest\Get('/general')]
    #[Rest\View(serializerGroups: ['configuration:general'])]
    #[Api\Summary('Get general configuration')]
    #[Api\Response200Groups(description: 'Returns general configuration', content: new NA\Model(type: Configuration::class))]
    public function getGeneralAction()
    {
        return $this->getConfiguration();
    }

    #[Rest\Post('/general')]
    #[Rest\View(serializerGroups: ['configuration:general'])]
    #[Api\Summary('Edit general configuration')]
    #[Api\RequestBody(content: new NA\Model(type: ConfigurationGeneralType::class))]
    #[Api\Response200Groups(description: 'Returns edited general configuration', content: new NA\Model(type: Configuration::class))]
    #[Api\Response400]
    public function setGeneralAction(Request $request)
    {
        return $this->handleConfigurationForm(ConfigurationGeneralType::class, $request);
    }

    #[Rest\Get('/logs')]
    #[Rest\View(serializerGroups: ['configuration:logs'])]
    #[Api\Summary('Get logs configuration')]
    #[Api\Response200Groups(description: 'Returns logs configuration', content: new NA\Model(type: Configuration::class))]
    public function getLogsAction()
    {
        return $this->getConfiguration();
    }

    #[Rest\Post('/logs')]
    #[Rest\View(serializerGroups: ['configuration:logs'])]
    #[Api\Summary('Edit logs configuration')]
    #[Api\RequestBody(content: new NA\Model(type: ConfigurationLogsType::class))]
    #[Api\Response200Groups(description: 'Returns edited logs configuration', content: new NA\Model(type: Configuration::class))]
    #[Api\Response400]
    public function setLogsAction(Request $request)
    {
        return $this->handleConfigurationForm(ConfigurationLogsType::class, $request);
    }

    #[Rest\Get('/totp')]
    #[Rest\View(serializerGroups: ['configuration:totp'])]
    #[Api\Summary('Get TOTP configuration')]
    #[Api\Response200Groups(description: 'Returns TOTP configuration', content: new NA\Model(type: Configuration::class))]
    public function getTotpAction()
    {
        return $this->getConfiguration();
    }

    #[Rest\Post('/totp')]
    #[Rest\View(serializerGroups: ['configuration:totp'])]
    #[Api\Summary('Edit TOTP configuration')]
    #[Api\RequestBody(content: new NA\Model(type: ConfigurationTotpType::class))]
    #[Api\Response200Groups(description: 'Returns edited TOTP configuration', content: new NA\Model(type: Configuration::class))]
    #[Api\Response400]
    public function setTotpAction(Request $request)
    {
        return $this->handleConfigurationForm(ConfigurationTotpType::class, $request);
    }

    #[Rest\Get('/radius')]
    #[Rest\View(serializerGroups: ['configuration:radius'])]
    #[Api\Summary('Get radius configuration')]
    #[Api\Response200Groups(description: 'Returns radius configuration', content: new NA\Model(type: Configuration::class))]
    public function getRadiusAction()
    {
        return $this->getConfiguration();
    }

    #[Rest\Post('/radius')]
    #[Rest\View(serializerGroups: ['configuration:radius'])]
    #[Api\Summary('Edit radius configuration')]
    #[Api\RequestBody(content: new NA\Model(type: ConfigurationRadiusType::class))]
    #[Api\Response200Groups(description: 'Returns edited radius configuration', content: new NA\Model(type: Configuration::class))]
    #[Api\Response400]
    public function setRadiusAction(Request $request)
    {
        return $this->handleConfigurationForm(ConfigurationRadiusType::class, $request);
    }

    #[Rest\Get('/vpn')]
    #[Rest\View(serializerGroups: ['configuration:vpn'])]
    #[Api\Summary('Get VPN configuration')]
    #[Api\Response200Groups(description: 'Returns VPN configuration', content: new NA\Model(type: Configuration::class))]
    #[Security("is_granted('ROLE_ADMIN_VPN')")]
    #[Areas(['admin:vpnsecuritysuite'])]
    public function getVpnAction()
    {
        return $this->getConfiguration();
    }

    #[Rest\Post('/vpn')]
    #[Rest\View(serializerGroups: ['configuration:vpn'])]
    #[Api\Summary('Edit VPN configuration')]
    #[Api\RequestBody(content: new NA\Model(type: ConfigurationVpnType::class))]
    #[Api\Response200Groups(description: 'Returns edited VPN configuration', content: new NA\Model(type: Configuration::class))]
    #[Api\Response400]
    #[Security("is_granted('ROLE_ADMIN_VPN')")]
    #[Areas(['admin:vpnsecuritysuite'])]
    public function setVpnAction(Request $request)
    {
        return $this->handleConfigurationForm(ConfigurationVpnType::class, $request);
    }

    #[Rest\Get('/documentation')]
    #[Rest\View(serializerGroups: ['configuration:documentation'])]
    #[Api\Summary('Get documentation configuration')]
    #[Api\Response200Groups(description: 'Returns documentation configuration', content: new NA\Model(type: Configuration::class))]
    public function getDocumentationAction()
    {
        return $this->getConfiguration();
    }

    #[Rest\Post('/documentation')]
    #[Rest\View(serializerGroups: ['configuration:documentation'])]
    #[Api\Summary('Edit documentation configuration')]
    #[Api\RequestBody(content: new NA\Model(type: ConfigurationDocumentationType::class))]
    #[Api\Response200Groups(description: 'Returns edited documentation configuration', content: new NA\Model(type: Configuration::class))]
    #[Api\Response400]
    public function setDocumentationAction(Request $request)
    {
        return $this->handleConfigurationForm(ConfigurationDocumentationType::class, $request);
    }

    protected function handleConfigurationForm(string $formClass, Request $request)
    {
        $previousConfiguration = clone $this->getConfiguration();

        return $this->handleForm($formClass, $request, function (Configuration $object, FormInterface $form) use ($previousConfiguration) {
            return $this->processConfiguration($object, $form, $previousConfiguration);
        }, $this->getConfiguration());
    }

    protected function processConfiguration(Configuration $object, FormInterface $form, Configuration $previousConfiguration)
    {
        if ($previousConfiguration->getDevicesOpenvpnServerDescription() != $object->getDevicesOpenvpnServerDescription()) {
            $object->setDevicesOpenvpnServerIndex(null);
        }

        if ($previousConfiguration->getTechniciansOpenvpnServerDescription() != $object->getTechniciansOpenvpnServerDescription()) {
            $object->setTechniciansOpenvpnServerIndex(null);
        }

        $this->vpnAddressManager->processConfigurationSubnetsChange($previousConfiguration, $object);

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $object;
    }

    #[Rest\Get('/sso')]
    #[Rest\View(serializerGroups: ['identification', 'configuration:sso'])]
    #[Api\Summary('Get single sign-on (SSO) configuration')]
    #[Api\Response200Groups(description: 'Returns sso configuration', content: new NA\Model(type: Configuration::class))]
    public function getSsoAction()
    {
        return $this->ssoDecrypt($this->getConfiguration());
    }

    #[Rest\Get('/sso/microsoftoidc/credential/uploadedcertificate/public')]
    #[Api\Summary('Get uploaded public key for microsoft certificate credential')]
    #[Api\Response200(description: 'Uploaded public key for microsoft certificate credential')]
    public function getSsoMicrosoftOidcCredentialUploadedCertificatePublicAction()
    {
        $encryptedPublic = $this->getConfiguration()->getMicrosoftOidcUploadedCertificatePublic();
        if (!$encryptedPublic) {
            throw new NotFoundHttpException();
        }

        $decryptedPublic = $this->encryptionManager->decrypt($encryptedPublic);

        return $decryptedPublic;
    }

    #[Rest\Get('/sso/microsoftoidc/credential/uploadedcertificate/public/download')]
    #[Api\Summary('Download uploaded public key for microsoft certificate credential')]
    #[Api\Response200(description: 'Uploaded public key for microsoft certificate credential', content: new OA\MediaType(mediaType: 'application/x-x509-user-cert', schema: new OA\Schema(type: 'string')))]
    public function getSsoMicrosoftOidcCredentialUploadedCertificatePublicDownloadAction()
    {
        $encryptedPublic = $this->getConfiguration()->getMicrosoftOidcUploadedCertificatePublic();
        if (!$encryptedPublic) {
            throw new NotFoundHttpException();
        }

        $decryptedPublic = $this->encryptionManager->decrypt($encryptedPublic);
        $filename = 'microsoftoidc_credential_uploadedcertificate_public.crt';

        $response = new Response($decryptedPublic);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/x-x509-user-cert');

        return $response;
    }

    #[Rest\Get('/sso/microsoftoidc/credential/generatedcertificate/public')]
    #[Api\Summary('Get generated public key for microsoft certificate credential')]
    #[Api\Response200(description: 'Generated public key for microsoft certificate credential')]
    public function getSsoMicrosoftOidcCredentialGeneratedCertificatePublicAction()
    {
        $encryptedPublic = $this->getConfiguration()->getMicrosoftOidcGeneratedCertificatePublic();
        if (!$encryptedPublic) {
            throw new NotFoundHttpException();
        }

        $decryptedPublic = $this->encryptionManager->decrypt($encryptedPublic);

        return $decryptedPublic;
    }

    #[Rest\Get('/sso/microsoftoidc/credential/generatedcertificate/public/download')]
    #[Api\Summary('Download generated public key for microsoft certificate credential')]
    #[Api\Response200(description: 'Generated public key for microsoft certificate credential', content: new OA\MediaType(mediaType: 'application/x-x509-user-cert', schema: new OA\Schema(type: 'string')))]
    public function getSsoMicrosoftOidcCredentialGeneratedCertificatePublicDownloadAction()
    {
        $encryptedPublic = $this->getConfiguration()->getMicrosoftOidcGeneratedCertificatePublic();
        if (!$encryptedPublic) {
            throw new NotFoundHttpException();
        }

        $decryptedPublic = $this->encryptionManager->decrypt($encryptedPublic);
        $filename = 'microsoftoidc_credential_generatedcertificate_public.crt';

        $response = new Response($decryptedPublic);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/x-x509-user-cert');

        return $response;
    }

    #[Rest\Post('/sso')]
    #[Rest\View(serializerGroups: ['configuration:sso'])]
    #[Api\Summary('Edit single sign-on (SSO) configuration')]
    #[Api\RequestBody(content: new NA\Model(type: ConfigurationSsoType::class))]
    #[Api\Response200Groups(description: 'Returns edited sso configuration', content: new NA\Model(type: Configuration::class))]
    #[Api\Response400]
    public function setSsoAction(Request $request)
    {
        $previousConfiguration = clone $this->getConfiguration();

        return $this->handleForm(ConfigurationSsoType::class, $request, function (Configuration $object, FormInterface $form) use ($previousConfiguration) {
            // We need to encrypt client secret when it is given (even when credential is not "clientSecret")
            $this->processMicrosoftOidcCredentialClientSecret($object);

            switch ($object->getMicrosoftOidcCredential()) {
                case MicrosoftOidcCredential::CERTIFICATE_UPLOAD:
                    $this->processMicrosoftOidcCredentialCertificateUpload($object);
                    break;
                case MicrosoftOidcCredential::CERTIFICATE_GENERATE:
                    $this->processMicrosoftOidcCredentialCertificateGenerate($object);
                    break;
            }

            return $this->ssoDecrypt($this->processConfiguration($object, $form, $previousConfiguration));
        }, $this->getConfiguration());
    }

    protected function ssoDecrypt(Configuration $configuration): Configuration
    {
        $encryptedClientSecret = $configuration->getMicrosoftOidcClientSecret();
        if ($encryptedClientSecret) {
            $clientSecret = $this->encryptionManager->decrypt($encryptedClientSecret);
            $configuration->setDecryptedMicrosoftOidcClientSecret($clientSecret);
        }

        return $configuration;
    }

    protected function processMicrosoftOidcCredentialClientSecret(Configuration $configuration): void
    {
        $clientSecret = $configuration->getMicrosoftOidcClientSecret();
        if (!$clientSecret) {
            return;
        }

        $encryptedClientSecret = $this->encryptionManager->encrypt($clientSecret);
        $configuration->setMicrosoftOidcClientSecret($encryptedClientSecret);
    }

    protected function processMicrosoftOidcCredentialCertificateUpload(Configuration $configuration): void
    {
        if ($configuration->getMicrosoftOidcUploadedCertificatePublic()) {
            $publicCertificateTusFile = UploadManager::getTusFile($configuration->getMicrosoftOidcUploadedCertificatePublic());
            $publicCertificateFilepath = $publicCertificateTusFile['file_path'] ?? null;
            $publicCertificateContent = \file_get_contents($publicCertificateFilepath);
            $encryptedPublicCertificateContent = $this->encryptionManager->encrypt($publicCertificateContent);
            $configuration->setMicrosoftOidcUploadedCertificatePublic($encryptedPublicCertificateContent);

            $publicCertificate = \openssl_x509_parse($publicCertificateContent);
            $publicCertificateValidTo = new \DateTime();
            $publicCertificateValidTo->setTimestamp($publicCertificate['validTo_time_t']);
            $configuration->setMicrosoftOidcUploadedCertificatePublicValidTo($publicCertificateValidTo);
            $publicKeyThumbprint = \openssl_x509_fingerprint($publicCertificateContent);
            $configuration->setMicrosoftOidcUploadedCertificatePublicThumbprint($publicKeyThumbprint);
        }

        if ($configuration->getMicrosoftOidcUploadedCertificatePrivate()) {
            $privateCertificateTusFile = UploadManager::getTusFile($configuration->getMicrosoftOidcUploadedCertificatePrivate());
            $privateCertificateFilepath = $privateCertificateTusFile['file_path'] ?? null;
            $privateCertificateContent = \file_get_contents($privateCertificateFilepath);
            $encryptedPrivateCertificateContent = $this->encryptionManager->encrypt($privateCertificateContent);
            $configuration->setMicrosoftOidcUploadedCertificatePrivate($encryptedPrivateCertificateContent);
        }
    }

    protected function processMicrosoftOidcCredentialCertificateGenerate(Configuration $configuration): void
    {
        if (!$configuration->getMicrosoftOidcGenerateCertificate()) {
            return;
        }

        // Config existance is verified to have clear exception message in unlikely case that it is missing
        $openSslConfigPath = $this->projectDir.'/config/openssl/microsoft_oidc.conf';
        $fs = new Filesystem();
        if (!$fs->exists($openSslConfigPath)) {
            throw new \Exception('Missing '.$openSslConfigPath.' file');
        }

        // SCEP Server allows common name to be maximum of 53 characters, lets keep the same constraint here.
        $commonName = substr(Urlizer::urlize('selfSigned_sealman_'.$configuration->getCompanyName()), 0, 53);
        $dns = [
            'commonName' => $commonName,
        ];
        $expiryDays = $configuration->getMicrosoftOidcGenerateCertificateExpiryDays();
        $privateKey = \openssl_pkey_new();
        // OpenSSL config is overriden to allow skipping countryName (C), stateOrProvinceName (ST) and organizationName (O) in subject/issuer
        $csr = \openssl_csr_new($dns, $privateKey, ['config' => $openSslConfigPath]);
        $publicKey = \openssl_csr_sign($csr, null, $privateKey, $expiryDays);

        openssl_x509_export($publicKey, $publicCertificateContent);
        openssl_pkey_export($privateKey, $privateCertificateContent);

        $encryptedPublicCertificateContent = $this->encryptionManager->encrypt($publicCertificateContent);
        $configuration->setMicrosoftOidcGeneratedCertificatePublic($encryptedPublicCertificateContent);

        $encryptedPrivateCertificateContent = $this->encryptionManager->encrypt($privateCertificateContent);
        $configuration->setMicrosoftOidcGeneratedCertificatePrivate($encryptedPrivateCertificateContent);

        $publicCertificate = \openssl_x509_parse($publicCertificateContent);
        $publicCertificateValidTo = new \DateTime();
        $publicCertificateValidTo->setTimestamp($publicCertificate['validTo_time_t']);
        $configuration->setMicrosoftOidcGeneratedCertificatePublicValidTo($publicCertificateValidTo);
        $publicKeyThumbprint = \openssl_x509_fingerprint($publicCertificateContent);
        $configuration->setMicrosoftOidcGeneratedCertificatePublicThumbprint($publicKeyThumbprint);
    }
}
