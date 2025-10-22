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

namespace App\DeviceCommunication;

use App\DeviceCommunication\Trait\Abstract\AbstractDeviceCommunicationSecurityTrait;
use App\Entity\Certificate;
use App\Entity\CertificateType;
use App\Entity\CommunicationLog;
use App\Entity\Device;
use App\Entity\DeviceCommand;
use App\Entity\DeviceSecret;
use App\Entity\DeviceType;
use App\Entity\DeviceTypeCertificateType;
use App\Entity\DeviceTypeSecret;
use App\Entity\DeviceVariable;
use App\Entity\Firmware;
use App\Entity\TemplateVersion;
use App\Entity\Traits\CommunicationEntityInterface;
use App\Entity\Traits\FirmwareStatusEntityInterface;
use App\Entity\Traits\GsmEntityInterface;
use App\Enum\CertificateEncoding;
use App\Enum\CertificateEntity;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\ConfigGenerator;
use App\Enum\DeviceCommandStatus;
use App\Enum\Feature;
use App\Enum\FieldRequirement;
use App\Enum\LogLevel;
use App\Enum\SecretValueBehaviour;
use App\Enum\SourceType;
use App\Exception\LogsException;
use App\Model\ConfigDevice;
use App\Model\FieldRequirementsModel;
use App\Model\ResponseModel;
use App\Model\VariableInterface;
use App\Service\Helper\CertificateManagerTrait;
use App\Service\Helper\CommunicationLogManagerTrait;
use App\Service\Helper\ConfigManagerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\ConnectionAggregationManagerTrait;
use App\Service\Helper\DeviceSecretManagerTrait;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\RouterInterfaceTrait;
use App\Service\Helper\TemplateManagerTrait;
use App\Service\Helper\ViewHandlerTrait;
use App\Service\Helper\VpnAddressManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use ReflectionClass;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

abstract class AbstractDeviceCommunication implements DeviceCommunicationInterface
{
    use AbstractDeviceCommunicationSecurityTrait;

    use RouterInterfaceTrait;
    use EntityManagerTrait;
    use ConnectionAggregationManagerTrait;
    use CommunicationLogManagerTrait;
    use CertificateManagerTrait;
    use ConfigManagerTrait;
    use ConfigurationManagerTrait;
    use TemplateManagerTrait {
        getDeviceTemplate as traitGetDeviceTemplate;
    }
    use EncryptionManagerTrait;
    use ViewHandlerTrait;
    use EntityManagerTrait;
    use VpnAddressManagerTrait;
    use CertificateTypeHelperTrait;
    use DeviceSecretManagerTrait;

    /**
     * @var ?DeviceType
     */
    protected $deviceType;

    /**
     * Provides current device type.
     */
    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    /**
     * Sets current device type - should be done in communication controller controller.
     */
    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    /**
     * @var ?Device
     */
    protected $device;

    /**
     * Provides current device.
     */
    public function getDevice(): ?Device
    {
        return $this->device;
    }

    /**
     * Sets current device.
     */
    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }

    /**
     * @var ?Request
     */
    protected $request;

    /**
     * Provides current HTTP request.
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Sets current HTTP request.
     */
    public function setRequest(?Request $request)
    {
        $this->request = $request;
    }

    /**
     * @var Response|ResponseModel|null
     */
    protected $response;

    /**
     * Provides current HTTP response in form of ResponseModel class or symfony HTTP Response class.
     */
    public function getResponse(): Response|ResponseModel|null
    {
        return $this->response;
    }

    /**
     * Sets current HTTP response.
     */
    public function setResponse(Response|ResponseModel|null $response)
    {
        $this->response = $response;
    }

    /**
     * Returns current TemplateVersion for device (parameter or in object $this->getDevice()). Takes into consideration if device is staging or production.
     */
    public function getDeviceTemplate(null|Device $device = null): ?TemplateVersion
    {
        if (null == $device) {
            $device = $this->getDevice();
        }

        return $this->traitGetDeviceTemplate($device);
    }

    /**
     * Configure if firmware downloaded for this device type/communication procedure should be secured by authentication
     * This method might disable authentication requirements set in device type - only for firmware download.
     */
    public function isFirmwareSecured(): bool
    {
        return false;
    }

    /**
     * Provides extra log data for communication logs translation as '{{ data }}'.
     */
    public function getLogData(): string
    {
        return '';
    }

    /**
     * Provides content column default value for communication logs.
     */
    public function getLogDefaultContent(): ?string
    {
        return null;
    }

    /**
     * Provides list of required requirements for device type using this communication procedure (hasX fields)
     * Required fields have to be set to true to have valid device type using this communication procedure
     * As values use enum CommunicationProcedureRequirement.
     */
    public function getCommunicationProcedureRequirementsRequired(): array
    {
        return [];
    }

    /**
     * Provides list of optional requirements for device type using this communication procedure (hasX fields)
     * Fields can be set true or false as needed to have valid device type using this communication procedure
     * As values use enum CommunicationProcedureRequirement.
     */
    public function getCommunicationProcedureRequirementsOptional(): array
    {
        return [];
    }

    /**
     * Provides list of required certificate categories for device type using this communication procedure (hasX fields)
     * Required fields have to be set to true to have valid device type using this communication procedure
     * As values use enum CertificateCategory.
     */
    public function getCommunicationProcedureCertificateCategoryRequired(): array
    {
        return [];
    }

    /**
     * Provides list of optional certificate categories for device type using this communication procedure (hasX fields)
     * Fields can be set true or false as needed to have valid device type using this communication procedure
     * As values use enum CertificateCategory.
     */
    public function getCommunicationProcedureCertificateCategoryOptional(): array
    {
        return [];
    }

    /**
     * Requirements from enum CommunicationProcedureRequirement that are not in one of methods above,
     * have to be set to false to have valid device type using this communication procedure.
     */

    /**
     * Provides validation requirements for extra fields (see FieldRequirementsModel).
     */
    public function getCommunicationProcedureFieldsRequirements(): FieldRequirementsModel
    {
        return new FieldRequirementsModel();
    }

    /**
     * List of fields with dynamic requirements to use in DeviceTypeValidator.
     */
    public function getProperiesWithFieldRequirements(): array
    {
        return [
            'fieldSerialNumber',
            'fieldImsi',
            'fieldModel',
            'fieldRegistrationId',
            'fieldEndorsementKey',
            'fieldHardwareVersion',
        ];
    }

    /**
     * List of validation groups based on FieldRequirementsModel to use in form configuration (in communication controller).
     */
    public function getDeviceTypeValidationGroups(DeviceType $deviceType): array
    {
        $validationGroups = [];
        $communicationFieldRequirements = $this->getCommunicationProcedureFieldsRequirements();

        foreach ($this->getProperiesWithFieldRequirements() as $property) {
            $validationGroup = $property.'Required';
            $getFunctionName = 'get'.ucfirst($property);
            if (
                FieldRequirement::REQUIRED_IN_COMMUNICATION == $communicationFieldRequirements->$getFunctionName() ||
                FieldRequirement::REQUIRED == $communicationFieldRequirements->$getFunctionName() ||
                FieldRequirement::REQUIRED_IN_COMMUNICATION == $deviceType->$getFunctionName() ||
                FieldRequirement::REQUIRED == $deviceType->$getFunctionName()
            ) {
                $validationGroups[] = $validationGroup;
            }
        }

        return $validationGroups;
    }

    /**
     * Sets field requirements for newly created device type as required by communication procedure.
     */
    public function setDefaultFieldRequirements(DeviceType $deviceType): void
    {
        $communicationFieldRequirements = $this->getCommunicationProcedureFieldsRequirements();

        foreach ($this->getProperiesWithFieldRequirements() as $property) {
            $getFunctionName = 'get'.ucfirst($property);
            $setFunctionName = 'set'.ucfirst($property);
            $deviceType->$setFunctionName($communicationFieldRequirements->$getFunctionName());
        }
    }

    /**
     * Checks if device type and system configuration is valid and available for this device type - to use for communication.
     */
    public function isDeviceTypeValid(): bool
    {
        if (!$this->getDeviceType()) {
            return false;
        }

        if (in_array(CommunicationProcedureRequirement::HAS_VPN, $this->getCommunicationProcedureRequirementsRequired())) {
            if (!$this->getDeviceType()->getIsVpnAvailable()) {
                return false;
            }
        }

        if (in_array(CommunicationProcedureRequirement::HAS_MASQUERADE, $this->getCommunicationProcedureRequirementsRequired())) {
            if (!$this->getDeviceType()->getIsMasqueradeAvailable()) {
                return false;
            }
        }

        if (in_array(CommunicationProcedureRequirement::HAS_ENDPOINT_DEVICES, $this->getCommunicationProcedureRequirementsRequired())) {
            if (!$this->getDeviceType()->getIsEndpointDevicesAvailable()) {
                return false;
            }
        }

        if (in_array(CommunicationProcedureRequirement::HAS_CERTIFICATES, $this->getCommunicationProcedureRequirementsRequired())) {
            foreach ($this->getCommunicationProcedureCertificateCategoryRequired() as $certificateCategory) {
                $requirementMet = false;
                foreach ($this->getDeviceType()->getCertificateTypes() as $deviceTypeCertificateType) {
                    if ($deviceTypeCertificateType->getCertificateType()->getCertificateCategory() == $certificateCategory) {
                        if ($deviceTypeCertificateType->getIsCertificateTypeAvailable()) {
                            $requirementMet = true;
                            break;
                        }
                    }
                }
                if (!$requirementMet) {
                    return false;
                }
            }
        }

        if (in_array(CommunicationProcedureRequirement::HAS_DEVICE_TO_NETWORK_CONNECTION, $this->getCommunicationProcedureRequirementsRequired())) {
            if (!$this->getDeviceType()->getIsDeviceToNetworkConnectionAvailable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if this communication procedure is valid in current system (are license requirements met).
     */
    public function isCommunicationProcedureValid(): bool
    {
        if (in_array(CommunicationProcedureRequirement::HAS_VPN, $this->getCommunicationProcedureRequirementsRequired())) {
            if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
                return false;
            }
        }

        if (in_array(CommunicationProcedureRequirement::HAS_MASQUERADE, $this->getCommunicationProcedureRequirementsRequired())) {
            if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
                return false;
            }
        }

        if (in_array(CommunicationProcedureRequirement::HAS_ENDPOINT_DEVICES, $this->getCommunicationProcedureRequirementsRequired())) {
            if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
                return false;
            }
        }

        if (in_array(CommunicationProcedureRequirement::HAS_CERTIFICATES, $this->getCommunicationProcedureRequirementsRequired())) {
            foreach ($this->getCommunicationProcedureCertificateCategoryRequired() as $certificateCategory) {
                $certificateType = $this->getCertificateTypeByCertificateCategory($certificateCategory, CertificateEntity::DEVICE);
                if (!$certificateType || !$certificateType->getIsAvailable()) {
                    return false;
                }
            }
        }

        if (in_array(CommunicationProcedureRequirement::HAS_DEVICE_TO_NETWORK_CONNECTION, $this->getCommunicationProcedureRequirementsRequired())) {
            if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
                return false;
            }
        }

        return true;
    }

    // overrride these functions to use process functionalities
    /**
     * Communication procedure should request diagnose data - prepare proper response.
     */
    protected function handleRequestDiagnoseData(): void
    {
    }

    /**
     * Communication procedure should request config data - prepare proper response.
     */
    protected function handleRequestConfigData(): void
    {
    }

    /**
     * Communication procedure should prepare proper error response for form validation errors.
     */
    protected function handleErrorResponse(DeviceType $deviceType, Request $request, FormInterface $form, array $messages, string $message): Response|ResponseModel
    {
    }

    /**
     * Communication procedure should reinstall firmware - prepare proper response.
     */
    protected function handleReinstallFirmware(Feature $feature, Firmware $firmware): void
    {
    }

    /**
     * Communication procedure should reinstall config - prepare proper response.
     */
    protected function handleReinstallConfig(Feature $feature, ConfigDevice $generatedConfig): void
    {
    }

    /**
     * Config generation method.
     */
    protected function handleConfigGeneration(Feature $feature, null|string $currentConfig = null, bool $sendTheSameConfig = true): bool
    {
        $this->configManager->setDeviceCommunication($this);

        $configDevice = $this->configManager->generateDeviceConfig($this->getDeviceType(), $this->getDevice(), $feature, true);

        if ($configDevice->isGenerated()) {
            $configGenerated = $configDevice->getConfigGenerated();
            if ($configGenerated && $currentConfig && 0 === strcmp($configGenerated, $currentConfig)) {
                if ($sendTheSameConfig) {
                    $this->communicationLogManager->createLogInfo('log.deviceConfigSameAsReceived');
                    $this->handleReinstallConfig($feature, $configDevice);

                    return true;
                } else {
                    $this->communicationLogManager->createLogInfo('log.deviceNoConfigWillBeSentSameAsReceived');
                }
            } else {
                $this->handleReinstallConfig($feature, $configDevice);

                return true;
            }
        } else {
            $this->communicationLogManager->createLogError('log.deviceConfigGenerationFailed');
        }

        return false;
    }

    /**
     * HTTP JSON response generated from ResponseModel object.
     */
    protected function getDeviceModelResponse(ResponseModel $responseModel): Response
    {
        return $this->viewHandler->createResponse($this->getAnnotatedView($this->getRequest(), $responseModel), $this->getRequest(), 'json');
    }

    /**
     * Default response for device type mismatch error.
     */
    protected function handleDeviceTypeMismatch(CommunicationLog $communicationLog)
    {
        $this->getResponse()->setContent($communicationLog->getMessage());
    }

    /**
     * SSL certificate encoding for variables values - based on device type settings - could also be overriden for customisation.
     */
    protected function handleCertificateEncoding(string $text, DeviceTypeCertificateType $deviceTypeCertificateType): string
    {
        switch ($deviceTypeCertificateType->getCertificateEncoding()) {
            case CertificateEncoding::ONE_LINE_PEM:
                return $this->encryptionManager->encodeCertificateToOneLine($text);
            case CertificateEncoding::HEX:
            default:
                return $this->encryptionManager->convertCertificateForConfig($text);
        }

        return $this->encryptionManager->convertCertificateForConfig($text);
    }

    /**
     * Default device identifier generation method.
     */
    public function generateIdentifier(Device $device): string
    {
        if ($device->getName()) {
            return $device->getName();
        }

        if ($device->getUuid()) {
            return $device->getUuid();
        }

        $uuid4 = substr(Uuid::v4()->toRfc4122(), 0, 36);
        // This will be unique whatever happens
        return 'Unknown-'.$uuid4First.'-'.time();
    }

    /**
     * Method check if rsrp is valid - should be overriden.
     */
    protected function isRsrpValid(?int $requiredMinRsrp): bool
    {
        return true;
    }

    /**
     * Method prepares form validation error response
     * this method is used in controller directly
     * It uses handleErrorResponse method.
     */
    public function prepareErrorResponse(DeviceType $deviceType, Request $request, FormInterface $form): Response|ResponseModel
    {
        $messages = ['Invalid request'];

        $logLevel = LogLevel::ERROR;
        if (empty($request->request->all()) && empty(json_decode($request->getContent(), true))) {
            // Router sometimes sends POST request without any parameters. Just return info
            $messages[] = 'Empty POST request';
            $logLevel = LogLevel::INFO;
        } else {
            foreach ($form->getErrors(true) as $error) {
                $messages[] = "Error at field '".$error->getOrigin()->getName()."': ".$this->trans($error->getMessage());
            }
        }

        $message = implode("\n", $messages);
        $this->communicationLogManager->createLog(
            $logLevel,
            'log.deviceInvalidRequest',
            ['errors' => $message],
            $request->__toString()
        );

        return $this->handleErrorResponse($deviceType, $request, $form, $messages, $message);
    }

    /**
     * Method checks if device provided diagnose data (based on header content type)
     * If yes it saves diagnose log.
     */
    protected function processReceivedDiagnoseData(string $requestHeaderContentType, ?string $content): bool
    {
        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->getHasRequestDiagnose()) {
            return false;
        }

        $contentType = strtolower($this->getRequest()->headers->get('Content-Type'));

        if ($contentType !== $requestHeaderContentType) {
            return false;
        }

        $this->communicationLogManager->handleDiagnoseLog($content);

        return true;
    }

    /**
     * Method saves config log if device provided it in request.
     */
    protected function processReceivedConfigLog(?string $content, Feature $feature = Feature::PRIMARY): bool
    {
        if (!$this->getDevice() || !$this->getDeviceType()) {
            return false;
        }

        $this->communicationLogManager->handleConfigLog($content, $feature);

        return true;
    }

    /**
     * Method checks if each of devices SSL certificates should be renewed
     * If yes it revokes current one and generates new one.
     * Returns true if any of certificates was renewed, false if all certificates stayed the same.
     */
    protected function processAutoRenewCertificates(): bool
    {
        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->getHasCertificates()) {
            return false;
        }

        if ($this->configurationManager->isScepBlocked()) {
            return false;
        }

        $certificateRenewed = false;

        foreach ($this->getDeviceType()->getCertificateTypes() as $deviceTypeCertificateType) {
            if (!$deviceTypeCertificateType->getIsCertificateTypeAvailable()) {
                continue;
            }

            // Optimization condition to limit amount of SQL queries - if true certificate will not be renewed anyway
            if (!$deviceTypeCertificateType->getEnableCertificatesAutoRenew() && !$deviceTypeCertificateType->getEnableSubjectAltName()) {
                continue;
            }

            $certificate = $this->getCertificateByType($this->getDevice(), $deviceTypeCertificateType->getCertificateType());
            if (!$certificate) {
                continue;
            }

            $renew = false;

            $certificateTypeRepresentation = $deviceTypeCertificateType->getCertificateType()->getRepresentation();

            if ($certificate->hasCertificate() && $certificate->getCertificateGenerated()) {
                if ($deviceTypeCertificateType->getEnableSubjectAltName() && $certificate->getRevokeCertificateOnNextCommunication()) {
                    $this->communicationLogManager->createLogInfo('log.deviceAutoRenewCertificateSubjectAltChange', ['certificateType' => $certificateTypeRepresentation]);
                    $renew = true;
                }

                if ($deviceTypeCertificateType->getEnableCertificatesAutoRenew()) {
                    // check if renew certs
                    $valid = $certificate->getCertificateValidTo();
                    $valid->sub(new \DateInterval('P'.$deviceTypeCertificateType->getCertificatesAutoRenewDaysBefore().'D'));
                    if (!$renew && $valid <= new \DateTime()) {
                        $this->communicationLogManager->createLogInfo('log.deviceAutoRenewCertificate', ['certificateType' => $certificateTypeRepresentation]);
                        $renew = true;
                    }
                }
            }

            if ($renew) {
                try {
                    // All errors are logged in VPN log
                    $this->certificateManager->revokeCertificate($certificate);

                    $this->certificateManager->generateCertificate($certificate);

                    $certificate->setRevokeCertificateOnNextCommunication(false);

                    $this->entityManager->persist($certificate);

                    $certificateRenewed = true;
                } catch (LogsException $logsException) {
                    $this->communicationLogManager->createLogWarning('log.deviceAutoRenewCertificateFailed', ['certificateType' => $certificateTypeRepresentation]);
                }
            }
        }

        return $certificateRenewed;
    }

    /**
     * Renews any device secrets that should be renewed and generates ones that are should be generated.
     *
     * @return bool True when at least one device secret has been renewed or generated
     */
    protected function processAutoGenerationOrRenewDeviceSecrets(): bool
    {
        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->getHasVariables()) {
            return false;
        }

        $processed = false;

        $deviceSecretsForRenewal = $this->getDeviceSecretsForRenewal();

        foreach ($deviceSecretsForRenewal as $deviceSecret) {
            $previousSecretValue = $deviceSecret->getSecretValue();

            $secretValue = $this->deviceSecretManager->generateRandomSecret($deviceSecret);
            $deviceSecret->setSecretValue($secretValue);
            $this->deviceSecretManager->encryptDeviceSecret($deviceSecret);
            $deviceSecret->setRenewedAt(new \DateTime());
            $deviceSecret->setForceRenewal(false);

            $this->entityManager->persist($deviceSecret);
            $this->entityManager->flush();

            $this->deviceSecretManager->createCommunicationRenewSecretLog($deviceSecret, $previousSecretValue);

            $processed = true;
        }

        $deviceTypeSecretsForGeneration = $this->getDeviceTypeSecretsForGeneration();

        foreach ($deviceTypeSecretsForGeneration as $deviceTypeSecret) {
            $deviceSecret = new DeviceSecret();
            $deviceSecret->setDeviceTypeSecret($deviceTypeSecret);
            $deviceSecret->setDevice($this->getDevice());

            $secretValue = $this->deviceSecretManager->generateRandomSecret($deviceSecret);
            $deviceSecret->setSecretValue($secretValue);
            $this->deviceSecretManager->encryptDeviceSecret($deviceSecret);
            $deviceSecret->setRenewedAt(new \DateTime());
            $deviceSecret->setForceRenewal(false);

            $this->entityManager->persist($deviceSecret);
            $this->entityManager->flush();

            $this->deviceSecretManager->createCommunicationCreateSecretLog($deviceSecret);

            $processed = true;
        }

        return $processed;
    }

    protected function getDeviceSecretsForRenewal(): iterable
    {
        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->getHasVariables()) {
            return [];
        }

        $queryBuilder = $this->getRepository(DeviceSecret::class)->createQueryBuilder('ds');
        $queryBuilder->leftJoin('ds.deviceTypeSecret', 'dts');
        $queryBuilder->andWhere('ds.device = :device');
        $queryBuilder->andWhere('dts.useAsVariable = :useAsVariable');
        $queryBuilder->andWhere('dts.secretValueBehaviour IN (:renewSecretValueBehaviours) OR (ds.forceRenewal = :forceRenewal AND dts.secretValueBehaviour = :generateSecretValueBehaviour)');
        $queryBuilder->andWhere(':now > DATEADD(ds.renewedAt, dts.secretValueRenewAfterDays, \'day\') OR ds.forceRenewal = :forceRenewal');
        $queryBuilder->setParameter('device', $this->getDevice());
        $queryBuilder->setParameter('useAsVariable', true);
        $queryBuilder->setParameter('forceRenewal', true);
        $queryBuilder->setParameter('generateSecretValueBehaviour', SecretValueBehaviour::GENERATE);
        $queryBuilder->setParameter('renewSecretValueBehaviours', SecretValueBehaviour::getRenewEnums());
        $queryBuilder->setParameter('now', new \DateTime());

        return $queryBuilder->getQuery()->getResult();
    }

    protected function getDeviceTypeSecretsForGeneration(): iterable
    {
        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->getHasVariables()) {
            return [];
        }

        $queryBuilder = $this->getRepository(DeviceTypeSecret::class)->createQueryBuilder('dts');
        $queryBuilder->leftJoin('dts.deviceSecrets', 'ds', 'WITH', 'ds.device = :device');
        $queryBuilder->andWhere('dts.deviceType = :deviceType');
        $queryBuilder->andWhere('dts.useAsVariable = :useAsVariable');
        $queryBuilder->andWhere('dts.secretValueBehaviour IN (:generateSecretValueBehaviours)');
        $queryBuilder->andWhere('ds.id IS NULL');
        $queryBuilder->setParameter('device', $this->getDevice());
        $queryBuilder->setParameter('deviceType', $this->getDeviceType());
        $queryBuilder->setParameter('useAsVariable', true);
        $queryBuilder->setParameter('generateSecretValueBehaviours', SecretValueBehaviour::getGenerateEnums());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Method checks if firmware could and should be reinstalled
     * It uses handleReinstallFirmware method.
     */
    protected function processReinstallFirmware(Feature $feature, bool $overrideMinRsrp = false, bool $createLogs = true): bool
    {
        $getReinstallFirmware = 'getReinstallFirmware'.$feature->value;
        $getHasFirmware = 'getHasFirmware'.$feature->value;
        $getFirmware = 'getFirmware'.$feature->value;

        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->$getHasFirmware()) {
            return false;
        }

        if ($this->getDevice()->$getReinstallFirmware()) {
            if (!$this->getDeviceTemplate()) {
                if ($createLogs) {
                    // Comment added for easier messages lookup
                    // 'log.deviceInstallFirmware1NoTemplate'
                    // 'log.deviceInstallFirmware2NoTemplate'
                    // 'log.deviceInstallFirmware3NoTemplate'
                    $this->communicationLogManager->createLogInfo('log.deviceInstallFirmware'.$feature->value.'NoTemplate');
                }
            } elseif (!$this->getDeviceTemplate()->$getFirmware()) {
                if ($createLogs) {
                    // Comment added for easier messages lookup
                    // 'log.deviceInstallFirmware1NoFirmware'
                    // 'log.deviceInstallFirmware2NoFirmware'
                    // 'log.deviceInstallFirmware3NoFirmware'
                    $this->communicationLogManager->createLogInfo('log.deviceInstallFirmware'.$feature->value.'NoFirmware');
                }
            } else {
                if (!$overrideMinRsrp && $this->getDeviceType()->getEnableFirmwareMinRsrp() && !$this->isRsrpValid($this->getDeviceType()->getFirmwareMinRsrp())) {
                    if ($createLogs) {
                        // Comment added for easier messages lookup
                        // 'log.deviceRsrpInvalidFirmware1'
                        // 'log.deviceRsrpInvalidFirmware2'
                        // 'log.deviceRsrpInvalidFirmware3'
                        $this->communicationLogManager->createLogWarning('log.deviceRsrpInvalidFirmware'.$feature->value);
                    }
                } else {
                    if ($createLogs) {
                        // Comment added for easier messages lookup
                        // 'log.deviceReinstallingFirmware1'
                        // 'log.deviceReinstallingFirmware2'
                        // 'log.deviceReinstallingFirmware3'
                        $this->communicationLogManager->createLogInfo('log.deviceReinstallingFirmware'.$feature->value);
                    }
                    $this->handleReinstallFirmware($feature, $this->getDeviceTemplate()->$getFirmware());

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Method checks if received firmware version is different than required by template.
     * If firmware or template is not available it returns false (as in same firmware versions), because it cannot be reinstalled anyway.
     */
    protected function processFirmware(Feature $feature, string $receivedFirmwareVersion, bool $createLogs = true): bool
    {
        $getHasFirmware = 'getHasFirmware'.$feature->value;
        $getFirmware = 'getFirmware'.$feature->value;

        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->$getHasFirmware()) {
            return false;
        }

        if (!$this->getDeviceTemplate()) {
            // Comment added for easier messages lookup
            // 'log.deviceFirmware1NoTemplate'
            // 'log.deviceFirmware2NoTemplate'
            // 'log.deviceFirmware3NoTemplate'
            $this->communicationLogManager->createLogInfo('log.deviceFirmware'.$feature->value.'NoTemplate');
        } elseif (!$this->getDeviceTemplate()->$getFirmware()) {
            // Comment added for easier messages lookup
            // 'log.deviceFirmware1NoFirmware'
            // 'log.deviceFirmware2NoFirmware'
            // 'log.deviceFirmware3NoFirmware'
            $this->communicationLogManager->createLogInfo('log.deviceFirmware'.$feature->value.'NoFirmware');
        } elseif ($this->getDeviceTemplate()->$getFirmware()->getVersion() !== $receivedFirmwareVersion) {
            // Comment added for easier messages lookup
            // 'log.deviceFirmware1NeedsUpdate'
            // 'log.deviceFirmware2NeedsUpdate'
            // 'log.deviceFirmware3NeedsUpdate'
            $this->communicationLogManager->createLogInfo('log.deviceFirmware'.$feature->value.'NeedsUpdate', [
                'currentVersion' => $receivedFirmwareVersion,
                'requiredVersion' => $this->getDeviceTemplate()->$getFirmware()->getVersion(),
            ]);

            return true;
        } else {
            // Comment added for easier messages lookup
            // 'log.deviceFirmware1UpToDate'
            // 'log.deviceFirmware2UpToDate'
            // 'log.deviceFirmware3UpToDate'
            $this->communicationLogManager->createLogInfo('log.deviceFirmware'.$feature->value.'UpToDate');
        }

        return false;
    }

    /**
     * Method checks if config could and should be reinstalled
     * It uses handleConfigGeneration method.
     */
    protected function processReinstallConfig(Feature $feature, null|string $currentConfig = null, bool $sendTheSameConfig = true, bool $overrideMinRsrp = false, bool $createLogs = true): bool
    {
        if ($this->getShouldReinstallConfig($feature, $currentConfig, $sendTheSameConfig, $overrideMinRsrp, $createLogs)) {
            return $this->handleConfigGeneration($feature, $currentConfig, $sendTheSameConfig);
        }

        return false;
    }

    /**
     * Method checks if config could and should be reinstalled. Just check not sending config response.
     */
    protected function getShouldReinstallConfig(Feature $feature, null|string $currentConfig = null, bool $sendTheSameConfig = true, bool $overrideMinRsrp = false, bool $createLogs = true, bool $expectedReinstallConfigFlag = false): bool
    {
        $getReinstallConfig = 'getReinstallConfig'.$feature->value;
        $getHasAlwaysReinstallConfig = 'getHasAlwaysReinstallConfig'.$feature->value;
        $getHasConfig = 'getHasConfig'.$feature->value;
        $getConfig = 'getConfig'.$feature->value;

        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->$getHasConfig()) {
            return false;
        }

        if ($this->getDeviceType()->$getHasAlwaysReinstallConfig() || $this->getDevice()->$getReinstallConfig() || $expectedReinstallConfigFlag) {
            if (!$this->getDeviceTemplate()) {
                if ($createLogs) {
                    // Comment added for easier messages lookup
                    // 'log.deviceInstallConfig1NoTemplate'
                    // 'log.deviceInstallConfig2NoTemplate'
                    // 'log.deviceInstallConfig3NoTemplate'
                    $this->communicationLogManager->createLogInfo('log.deviceInstallConfig'.$feature->value.'NoTemplate');
                }
            } elseif (!$this->getDeviceTemplate()->$getConfig()) {
                if ($createLogs) {
                    // Comment added for easier messages lookup
                    // 'log.deviceInstallConfig1NoConfig'
                    // 'log.deviceInstallConfig2NoConfig'
                    // 'log.deviceInstallConfig3NoConfig'
                    $this->communicationLogManager->createLogInfo('log.deviceInstallConfig'.$feature->value.'NoConfig');
                }
            } else {
                if (!$overrideMinRsrp && $this->getDeviceType()->getEnableConfigMinRsrp() && !$this->isRsrpValid($this->getDeviceType()->getConfigMinRsrp())) {
                    if ($createLogs) {
                        // Comment added for easier messages lookup
                        // 'log.deviceRsrpInvalidConfig1'
                        // 'log.deviceRsrpInvalidConfig2'
                        // 'log.deviceRsrpInvalidConfig3'
                        $this->communicationLogManager->createLogWarning('log.deviceRsrpInvalidConfig'.$feature->value);
                    }
                } else {
                    if ($createLogs) {
                        // Comment added for easier messages lookup
                        // 'log.deviceReinstallingConfig1'
                        // 'log.deviceReinstallingConfig2'
                        // 'log.deviceReinstallingConfig3'
                        $this->communicationLogManager->createLogInfo('log.deviceReinstallingConfig'.$feature->value);
                    }

                    return true;
                }
            }
        } else {
            if ($createLogs) {
                // Comment added for easier messages lookup
                // 'log.deviceReinstallingConfig1NoNeed'
                // 'log.deviceReinstallingConfig2NoNeed'
                // 'log.deviceReinstallingConfig3NoNeed'
                $this->communicationLogManager->createLogInfo('log.deviceReinstallingConfig'.$feature->value.'NoNeed');
            }
        }

        return false;
    }

    /**
     * Method checks if request diagnose data should responded
     * It uses handleRequestDiagnoseData method.
     */
    protected function processRequestDiagnoseData(bool $createLogs = true): bool
    {
        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->getHasRequestDiagnose()) {
            return false;
        }

        if ($this->getDevice()->getRequestDiagnoseData()) {
            $this->getDevice()->setRequestDiagnoseData(false);
            if ($createLogs) {
                $this->communicationLogManager->createLogInfo('log.deviceSendDiagnoseDataRequest');
            }

            $this->handleRequestDiagnoseData();

            return true;
        }

        return false;
    }

    /**
     * Method checks if request config data should responded
     * It uses handleRequestConfigData method.
     */
    protected function processRequestConfigData(bool $createLogs = true): bool
    {
        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->getHasRequestConfig()) {
            return false;
        }

        if ($this->getDevice()->getRequestConfigData()) {
            $this->getDevice()->setRequestConfigData(false);
            if ($createLogs) {
                $this->communicationLogManager->createLogInfo('log.deviceSendConfigDataRequest');
            }

            $this->handleRequestConfigData();

            return true;
        }

        return false;
    }

    /**
     * Method checks if device type of device and device type gather from request are a match
     * If not it responds with error response
     * It uses handleDeviceTypeMismatch method.
     */
    protected function processDeviceTypeMismatch(): bool
    {
        if (!$this->getDevice() || !$this->getDeviceType()) {
            return false;
        }

        if ($this->getDeviceType() !== $this->getDevice()->getDeviceType()) {
            $communicationLog = $this->communicationLogManager->createLogError(
                'log.deviceTypeMismatch',
                [
                    'deviceTypeFound' => $this->getDevice()->getDeviceType()->getName(),
                    'deviceTypeExpected' => $this->getDeviceType()->getName(),
                ],
                $this->getResponse()->__toString()
            );

            $this->handleDeviceTypeMismatch($communicationLog);

            return true;
        }

        return false;
    }

    /**
     * Method created pending DeviceCommand object based on current state.
     */
    protected function createCommand(string $commandName): ?DeviceCommand
    {
        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->getHasDeviceCommands()) {
            return null;
        }

        $deviceCommand = new DeviceCommand();
        $deviceCommand->setDevice($this->getDevice());
        $deviceCommand->setCommandName($commandName);
        $deviceCommand->setCommandTransactionId($this->generateUniqueCommandTransactionId());
        $deviceCommand->setCommandStatus(DeviceCommandStatus::PENDING);

        $expireAt = new \DateTime($this->getDeviceType()->getDeviceCommandExpireDuration());
        $deviceCommand->setExpireAt($expireAt);

        $this->entityManager->persist($deviceCommand);
        $this->entityManager->flush();

        return $deviceCommand;
    }

    /**
     * Method tries to find device by identifier (for current device type).
     */
    protected function findDeviceByIdentifier(string $identifier): ?Device
    {
        if (!$this->getDeviceType()) {
            return null;
        }

        return $this->getRepository(Device::class)->findOneBy(
            [
                'identifier' => $identifier,
                'deviceType' => $this->getDeviceType(),
            ]
        );
    }

    /**
     * Method generates unique DeviceCommand transaction ID.
     */
    protected function generateUniqueCommandTransactionId(): ?string
    {
        $uuid4 = substr(Uuid::v4()->toRfc4122(), 0, 36);
        $count = $this->getRepository(DeviceCommand::class)->count(['commandTransactionId' => $uuid4]);

        return $count > 0 ? $this->generateUniqueCommandTransactionId() : $uuid4;
    }

    /**
     * Method generates unique uuid for device (unique in deviceType group).
     */
    public function getDeviceTypeUniqueUuid(): ?string
    {
        if (!$this->getDeviceType()) {
            return null;
        }

        $uuid4 = substr(Uuid::v4()->toRfc4122(), 0, 36);
        $count = $this->getRepository(Device::class)->count(['uuid' => $uuid4, 'deviceType' => $this->getDeviceType()]);

        return $count > 0 ? $this->getDeviceTypeUniqueUuid() : $uuid4;
    }

    /**
     * Method generates unique hashIdentifier for device.
     */
    public function getDeviceUniqueHashIdentifier(): ?string
    {
        $hashIdentifier = substr(Uuid::v4()->toBase32(), 0, 14);
        $count = $this->getRepository(Device::class)->count(['hashIdentifier' => $hashIdentifier]);

        return $count > 0 ? $this->getDeviceUniqueHashIdentifier() : $hashIdentifier;
    }

    /**
     * Method checks if communication requirements are met by device type.
     */
    protected function isCommunicationProcedureRequirementsSatisfied(array $requirements): bool
    {
        $satisfied = true;

        foreach (CommunicationProcedureRequirement::cases() as $requirement) {
            $functionName = 'get'.ucfirst($requirement->value);
            if (in_array($requirement, $requirements) && !$this->getDeviceType()->$functionName()) {
                $this->communicationLogManager->createLogCritical('log.communicationProcedureRequire'.ucfirst($requirement->value));
                $satisfied = false;
            }
        }

        return $satisfied;
    }

    /**
     * Method prepares firmware download URL based on current configuration.
     */
    protected function getFirmwareUrl(Feature $feature, Firmware $firmware): string
    {
        $getHasFirmware = 'getHasFirmware'.$feature->value;
        $getCustomUrlFirmware = 'getCustomUrlFirmware'.$feature->value;

        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->$getHasFirmware() && !$firmware->getDownloadUrl()) {
            throw new \Exception('Unsupported state while generating firmware url.');
        }

        // This cannot return null due to condition performed above !$firmware->getDownloadUrl()
        if (SourceType::EXTERNAL_URL === $firmware->getSourceType()) {
            return $firmware->getExternalUrl();
        }

        if (SourceType::UPLOAD !== $firmware->getSourceType()) {
            throw new \Exception('Unsupported firmware sourceType: '.$firmware->getSourceType());
        }

        $host = $this->routerInterface->getContext()->getScheme().'://'.$this->routerInterface->getContext()->getHost();
        if ('http' == $this->routerInterface->getContext()->getScheme()) {
            if (80 != $this->routerInterface->getContext()->getHttpPort()) {
                $host .= ':'.$this->routerInterface->getContext()->getHttpPort();
            }
        }

        if ('https' == $this->routerInterface->getContext()->getScheme()) {
            if (443 != $this->routerInterface->getContext()->getHttpsPort()) {
                $host .= ':'.$this->routerInterface->getContext()->getHttpsPort();
            }
        }

        if (null !== $this->getDeviceType()->$getCustomUrlFirmware()) {
            $host = $this->getDeviceType()->$getCustomUrlFirmware();
        }

        // $url is structured in following way:
        // /df/DEVICE_HASH(14)/secret(6)/DEVICE_TYPE_SLUG(5-?)/UUID(6)/FILENAME(?)
        // 14+6+6 + ? = 26 + ?
        // 6 chars = 1 073 741 824 ~ 2^30
        // 14 chars = 1,18 x 10^21

        $url = '/df/'.$this->getDevice()->getHashIdentifier().'/'.$firmware->getSecret().'/'.$firmware->getUploadDirPart().'/'.$firmware->getFilename();

        return $host.$url;
    }

    /**
     * Should predefined variables be excluded from list provided to WebUI and config generation?
     */
    protected function getDeviceVariablesExcludeEmpty(): bool
    {
        return false;
    }

    /**
     * Method provides list of device variables (defined and predefined).
     */
    public function getDeviceVariables(bool $decryptSecretValues = false, bool $createLogs = true): array
    {
        $variables = $this->getPredefinedDeviceVariables($createLogs);
        $variables = array_merge($this->getDeviceSecretVariables($decryptSecretValues, $createLogs), $variables);
        $variables = array_merge($this->getDefinedDeviceVariables($createLogs), $variables);

        if ($this->getDeviceVariablesExcludeEmpty()) {
            $variables = array_filter($variables);
        }

        return $variables;
    }

    /**
     * Method provides list of device defined variables.
     */
    public function getDefinedDeviceVariables(bool $createLogs = true): array
    {
        if (!$this->getDevice()) {
            return [];
        }

        $variablesHydrated = $this->getRepository(DeviceVariable::class)
        ->createQueryBuilder('var')
        ->select('var.name, var.variableValue')
        ->andWhere('var.device = :device')
        ->setParameter('device', $this->getDevice())
        ->getQuery()
        ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $variables = [];

        foreach ($variablesHydrated as $variableData) {
            $variables[$variableData['name']] = $variableData['variableValue'];
        }

        return $variables;
    }

    /**
     * Method provides list of device secret predefined variables.
     */
    public function getDeviceSecretVariables(bool $decryptSecretValues = false, bool $createLogs = true): array
    {
        if (!$this->getDevice()) {
            return [];
        }

        $variables = [];

        foreach ($this->getDevice()->getDeviceSecrets() as $deviceSecret) {
            if (!$deviceSecret->getDeviceTypeSecret()->getUseAsVariable()) {
                continue;
            }

            $variables = \array_merge($variables, $this->getDeviceSecretEncodedVariables($deviceSecret, $decryptSecretValues, $createLogs));
        }

        return $variables;
    }

    /**
     * Method provides list of encoded variables based on device secret value.
     */
    protected function getDeviceSecretEncodedVariables(DeviceSecret $deviceSecret, bool $decryptSecretValues = false, bool $createLogs = true): array
    {
        $secretValue = null;
        if ($decryptSecretValues) {
            $this->deviceSecretManager->createCommunicationShowSecretLog($deviceSecret);
            $secretValue = $this->deviceSecretManager->getDecryptedSecretValue($deviceSecret);
        }

        return $this->getDeviceSecretValueEncodedVariables($deviceSecret, $secretValue, $createLogs);
    }

    /**
     * Method provides list of encoded variables based on device decrypted secret value.
     * If $decryptedSecretValue is null, ObscuredValue variables are returned.
     */
    public function getDeviceSecretValueEncodedVariables(DeviceSecret $deviceSecret, null|string $decryptedSecretValue = null, bool $createLogs = true): array
    {
        $variables = [];

        $variablePrefix = $deviceSecret->getDeviceTypeSecret()->getVariableNamePrefix();

        if (null !== $decryptedSecretValue) {
            $variables[$variablePrefix.'Plain'] = $decryptedSecretValue;
            $variables[$variablePrefix.'Base64'] = \base64_encode($decryptedSecretValue);
            $variables[$variablePrefix.'CryptMd5'] = crypt($decryptedSecretValue, '$1$'.$this->generateRandomString(8));
            $variables[$variablePrefix.'CryptBlowFish'] = crypt($decryptedSecretValue, '$2y$10$'.$this->generateRandomString(22));
            $variables[$variablePrefix.'CryptSha256'] = crypt($decryptedSecretValue, '$5$'.$this->generateRandomString(16));
            $variables[$variablePrefix.'CryptSha512'] = crypt($decryptedSecretValue, '$6$'.$this->generateRandomString(16));
        } else {
            $suffix = 'ObscuredValue';
            $variables[$variablePrefix.'Plain'] = 'Plain'.$suffix;
            $variables[$variablePrefix.'Base64'] = 'Base64'.$suffix.'==';
            $variables[$variablePrefix.'CryptMd5'] = '$1$'.$suffix;
            $variables[$variablePrefix.'CryptBlowFish'] = '$2y$10$'.$suffix;
            $variables[$variablePrefix.'CryptSha256'] = '$5$'.$suffix;
            $variables[$variablePrefix.'CryptSha512'] = '$6$'.$suffix;
        }

        return $variables;
    }

    public function generateRandomString(int $length = 10): string
    {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', intval(ceil($length / strlen($x))))), 1, $length);
    }

    /**
     * Method provides list of device predefined variables.
     * CertificateType variables are listing variables for all certificateTypes (this increases limit enforced by validator - no need to filter CertificateTypes).
     */
    public function getAllPredefinedDeviceVariableNames(bool $createLogs = true): array
    {
        $reflectionClass = new ReflectionClass(VariableInterface::class);
        $variableNames = $reflectionClass->getConstants();

        $certificateTypes = $this->getRepository(CertificateType::class)->findAll();
        foreach ($certificateTypes as $certificateType) {
            $variableNames = array_merge($variableNames, $this->getCertificateTypeVariableNames($certificateType));
        }

        return array_unique($variableNames);
    }

    /**
     * Method provides list of certificate variable names for CertificateType.
     * $variableNameSuffixes array is used to limit amount list of variables for specific communication procedure.
     */
    protected function getCertificateTypeVariableNames(CertificateType $certificateType, null|array $variableNameSuffixes = null): array
    {
        $variableNames = [];
        // set empty string for easier variableName calculation (it will be empty for deviceVpn)
        $variablePrefix = $certificateType->getVariablePrefix() ?: '';

        if (null == $variableNameSuffixes) {
            $variableNameSuffixes =
                [
                    VariableInterface::VARIABLE_NAME_CERTIFICATE,
                    VariableInterface::VARIABLE_NAME_CERTIFICATE_CHECKSUM,
                    VariableInterface::VARIABLE_NAME_CERTIFICATE_PLAIN,
                    VariableInterface::VARIABLE_NAME_PRIVATE_KEY,
                    VariableInterface::VARIABLE_NAME_PRIVATE_KEY_CHECKSUM,
                    VariableInterface::VARIABLE_NAME_PRIVATE_KEY_PLAIN,
                    VariableInterface::VARIABLE_NAME_CA,
                    VariableInterface::VARIABLE_NAME_CA_CHECKSUM,
                    VariableInterface::VARIABLE_NAME_CA_PLAIN,
                    VariableInterface::VARIABLE_NAME_ROOT_CA,
                    VariableInterface::VARIABLE_NAME_ROOT_CA_CHECKSUM,
                    VariableInterface::VARIABLE_NAME_ROOT_CA_PLAIN,
                    VariableInterface::VARIABLE_NAME_PRIVATE_KEY_PASSWORD,
                ];
        }

        foreach ($variableNameSuffixes as $variableNameSuffix) {
            $variableNames[] = $this->getCertificateVariableName($variablePrefix, $variableNameSuffix);
        }

        return $variableNames;
    }

    /**
     * Helper method to use in getOrderedListOfPredefinedVariablesNames for specific communication procedure
     * Method provides list of certificate variable names for available Certificates for deviceType.
     * $variableNameSuffixes array is used to limit amount list of variables for specific communication procedure.
     */
    protected function getDeviceTypeCertificateVariableNames(null|array $variableNameSuffixes = null): array
    {
        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated
        $variableNames = [];
        if (!$this->getDeviceType()) {
            return $variableNames;
        }

        foreach ($this->getDeviceType()->getCertificateTypes() as $deviceTypeCertificateType) {
            if ($deviceTypeCertificateType->getIsCertificateTypeAvailable()) {
                $certificateTypeVariables = $this->getCertificateTypeVariableNames($deviceTypeCertificateType->getCertificateType(), $variableNameSuffixes);
                $variableNames = array_merge($variableNames, $certificateTypeVariables);
            }
        }

        return $variableNames;
    }

    /**
     * Method calculates variable name with prefix (in camel case).
     */
    protected function getCertificateVariableName(?string $prefix, string $variableName): string
    {
        if (!$prefix || '' == $prefix) {
            return $variableName;
        }

        return $prefix.ucfirst($variableName);
    }

    /**
     * Method provides list of device predefined variables.
     */
    public function getPredefinedDeviceVariables(bool $createLogs = true): array
    {
        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated
        $variables = [];
        if ($this->getDeviceType()) {
            $variables = array_merge($variables, $this->getBasicDeviceVariables());
            if ($this->getDeviceType()->getHasGsm()) {
                $variables = array_merge($variables, $this->getGsmDeviceVariables());
            }
            if ($this->getDeviceType()->getHasCertificates()) {
                $variables = array_merge($variables, $this->getCertificatesDeviceVariables());
            }
            if ($this->getDeviceType()->getIsVpnAvailable()) {
                $variables = array_merge($variables, $this->getVpnDeviceVariables());
            }
            if ($this->getDeviceType()->getIsEndpointDevicesAvailable()) {
                $variables = array_merge($variables, $this->getEndpointDevicesDeviceVariables());
            }
            $variables = array_merge($variables, $this->getCustomDeviceVariables($createLogs));
        }

        return $this->processPredefinedDeviceVariables($variables, $createLogs);
    }

    /**
     * Method executes final processing of  list of device predefined variables (e.g. sorting)
     * By default method gets list and order of variables and filters and sorts them according to result of method.
     */
    protected function processPredefinedDeviceVariables(array $variables, bool $createLogs = true): array
    {
        $arrayVariables = [
            VariableInterface::VARIABLE_NAME_VIP_PREFIX,
            VariableInterface::VARIABLE_NAME_PIP_PREFIX,
            VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_PREFIX,
            VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_PREFIX,
        ];

        $orderedList = $this->getOrderedListOfPredefinedVariablesNames();

        if (null === $orderedList) {
            return $variables;
        }

        $orderedVariables = [];

        foreach ($orderedList as $variableName) {
            if (in_array($variableName, $arrayVariables)) {
                if ($this->getDevice()) {
                    $maxEndpointDevices = $this->vpnAddressManager->cidrToSize($this->getDevice()->getVirtualSubnetCidr());
                    for ($i = 0; $i <= $maxEndpointDevices; ++$i) {
                        $variableNameIndexed = $variableName.$i;
                        if (array_key_exists($variableNameIndexed, $variables)) {
                            $orderedVariables[$variableNameIndexed] = $variables[$variableNameIndexed];
                        }
                    }
                }
            } else {
                if (array_key_exists($variableName, $variables)) {
                    $orderedVariables[$variableName] = $variables[$variableName];
                }
            }
        }

        return $orderedVariables;
    }

    /**
     * Method provides list of predefined variable names
     * Result:
     * If null no processing is executed
     * If empty array no predefined variables will be returned
     * If array - only variables with names in array will be returned and will be ordered as in returned array.
     */
    protected function getOrderedListOfPredefinedVariablesNames(): ?array
    {
        return null;
    }

    /**
     * Method provides list of device predefined CUSTOM variables.
     */
    protected function getCustomDeviceVariables(bool $createLogs = true): array
    {
        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated
        return [];
    }

    /**
     * Method provides list of device predefined BASIC variables.
     */
    protected function getBasicDeviceVariables(): array
    {
        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated
        return [
            VariableInterface::VARIABLE_NAME_SERIAL => $this->getDevice()?->getSerialNumber(),
            VariableInterface::VARIABLE_NAME_SERIALNUMBER => $this->getDevice()?->getSerialNumber(),
            VariableInterface::VARIABLE_NAME_IDENTIFIER => $this->getDevice()?->getIdentifier(),
            VariableInterface::VARIABLE_NAME_NAME => $this->getDevice()?->getName(),
            VariableInterface::VARIABLE_NAME_XFORWARDEDFORIP => $this->getDevice()?->getXForwardedFor(),
            VariableInterface::VARIABLE_NAME_SOURCEIP => $this->getDevice()?->getHost(),
        ];
    }

    /**
     * Method provides list of device predefined VPN variables.
     */
    protected function getVpnDeviceVariables(): array
    {
        if (!$this->getDeviceType()?->getIsVpnAvailable()) {
            return [];
        }
        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated
        return [
            VariableInterface::VARIABLE_NAME_VPN_IP => $this->getDevice()?->getVpnIp(),
        ];
    }

    /**
     * Method provides list of device predefined ENDPOINT DEVICES variables.
     */
    protected function getEndpointDevicesDeviceVariables(): array
    {
        if (!$this->getDeviceType()?->getIsEndpointDevicesAvailable()) {
            return [];
        }

        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated

        if (!$this->getDevice()) {
            return [
                VariableInterface::VARIABLE_NAME_VIRTUAL_IP => null,
                VariableInterface::VARIABLE_NAME_VIRTUAL_SUBNET_IP => null,
                VariableInterface::VARIABLE_NAME_VIP_SUBNET => null,
                VariableInterface::VARIABLE_NAME_VIRTUAL_SUBNET => null,
                VariableInterface::VARIABLE_NAME_VIRTUAL_SUBNET_CIDR => null,
                VariableInterface::VARIABLE_NAME_VIP_PREFIX => null,
                VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_PREFIX => null,
                VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_ARRAY => [],
                VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_HOST_PART_ARRAY => [],
                VariableInterface::VARIABLE_NAME_PIP_PREFIX => null,
                VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_PREFIX => null,
                VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_ARRAY => [],
            ];
        }

        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated
        $variables = [
            VariableInterface::VARIABLE_NAME_VIRTUAL_IP => $this->getDevice()?->getVirtualIp(),
            VariableInterface::VARIABLE_NAME_VIRTUAL_SUBNET_IP => $this->getDevice()?->getVirtualSubnetIp(),
            VariableInterface::VARIABLE_NAME_VIP_SUBNET => $this->getDevice()?->getVirtualSubnet(),
            VariableInterface::VARIABLE_NAME_VIRTUAL_SUBNET => $this->getDevice()?->getVirtualSubnet(),
            VariableInterface::VARIABLE_NAME_VIRTUAL_SUBNET_CIDR => $this->getDevice()?->getVirtualSubnetCidr(),

            VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_ARRAY => [],
            VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_HOST_PART_ARRAY => [],
            VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_ARRAY => [],
        ];

        $variables[VariableInterface::VARIABLE_NAME_VIP_PREFIX.'0'] = $this->getDevice()->getVirtualIp();
        $variables[VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_PREFIX.'0'] = $this->getDevice()->getVirtualIp();

        $endpointDeviceIndex = 1;
        foreach ($this->getDevice()->getEndpointDevices() as $endpointDevice) {
            if ($this->getDeviceType()->getIsVpnAvailable()) {
                $endpointDeviceIndex = $endpointDevice->getVirtualIpHostPart();
                $variables[VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_ARRAY][$endpointDeviceIndex] = $endpointDevice->getPhysicalIp();
                $variables[VariableInterface::VARIABLE_NAME_PIP_PREFIX.$endpointDeviceIndex] = $endpointDevice->getPhysicalIp();
                $variables[VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_PREFIX.$endpointDeviceIndex] = $endpointDevice->getPhysicalIp();

                $variables[VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_HOST_PART_ARRAY][$endpointDeviceIndex] = $endpointDeviceIndex;

                $variables[VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_ARRAY][$endpointDeviceIndex] = $endpointDevice->getVirtualIp();
                $variables[VariableInterface::VARIABLE_NAME_VIP_PREFIX.$endpointDeviceIndex] = $endpointDevice->getVirtualIp();
                $variables[VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_PREFIX.$endpointDeviceIndex] = $endpointDevice->getVirtualIp();
            } else {
                $variables[VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_ARRAY][$endpointDeviceIndex] = $endpointDevice->getPhysicalIp();
                $variables[VariableInterface::VARIABLE_NAME_PIP_PREFIX.$endpointDeviceIndex] = $endpointDevice->getPhysicalIp();
                $variables[VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_PREFIX.$endpointDeviceIndex] = $endpointDevice->getPhysicalIp();
                ++$endpointDeviceIndex;
            }
        }

        return $variables;
    }

    /**
     * Method provides list of device predefined GSM variables.
     */
    protected function getGsmDeviceVariables(): array
    {
        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated
        return [
            VariableInterface::VARIABLE_NAME_IMEI => $this->getDevice()?->getImei(),
            VariableInterface::VARIABLE_NAME_IMSI => $this->getDevice()?->getImsi(),
            VariableInterface::VARIABLE_NAME_IMSI_UPPERCASE => $this->getDevice()?->getImsi(),
            VariableInterface::VARIABLE_NAME_IMSI2 => $this->getDevice()?->getImsi2(),
            VariableInterface::VARIABLE_NAME_OPERATORCODE => $this->getDevice()?->getOperatorCode(),
            VariableInterface::VARIABLE_NAME_BAND => $this->getDevice()?->getBand(),
            VariableInterface::VARIABLE_NAME_CELLID => $this->getDevice()?->getCellId(),
            VariableInterface::VARIABLE_NAME_NETWORKGENERATION => $this->getDevice()?->getNetworkGeneration(),
            VariableInterface::VARIABLE_NAME_RSRP => $this->getDevice()?->getRsrp(),
            VariableInterface::VARIABLE_NAME_RSRPVALUE => $this->getDevice()?->getRsrpValue(),
            VariableInterface::VARIABLE_NAME_CELLULARIP1 => $this->getDevice()?->getCellularIp1(),
            VariableInterface::VARIABLE_NAME_CELLULARUPTIME1 => $this->getDevice()?->getCellularUptime1(),
            VariableInterface::VARIABLE_NAME_CELLULARUPTIMESECONDS1 => $this->getDevice()?->getCellularUptimeSeconds1(),
            VariableInterface::VARIABLE_NAME_CELLULARIP2 => $this->getDevice()?->getCellularIp2(),
            VariableInterface::VARIABLE_NAME_CELLULARUPTIME2 => $this->getDevice()?->getCellularUptime2(),
            VariableInterface::VARIABLE_NAME_CELLULARUPTIMESECONDS2 => $this->getDevice()?->getCellularUptimeSeconds2(),
        ];
    }

    /**
     * Method provides list of device predefined SSL CERTIFICATES variables for all available CertificateTypes for current DeviceType.
     */
    protected function getCertificatesDeviceVariables(): array
    {
        $variables = [];

        foreach ($this->getDeviceType()->getCertificateTypes() as $deviceTypeCertificateType) {
            if ($deviceTypeCertificateType->getIsCertificateTypeAvailable()) {
                $certificateTypeVariables = $this->getCertificateTypeDeviceVariables($deviceTypeCertificateType);
                $variables = array_merge($variables, $certificateTypeVariables);
            }
        }

        return $variables;
    }

    /**
     * Method provides list of device predefined SSL CERTIFICATES variables for CertificateType.
     */
    protected function getCertificateTypeDeviceVariables(DeviceTypeCertificateType $deviceTypeCertificateType): array
    {
        $certificateType = $deviceTypeCertificateType->getCertificateType();

        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated
        $variables = [];
        // set empty string for easier variableName calculation (it will be empty for deviceVpn)
        $variablePrefix = $certificateType->getVariablePrefix() ?: '';

        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CERTIFICATE)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CERTIFICATE_CHECKSUM)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CERTIFICATE_PLAIN)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_PRIVATE_KEY)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_PRIVATE_KEY_CHECKSUM)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_PRIVATE_KEY_PLAIN)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CA)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CA_CHECKSUM)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CA_PLAIN)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_ROOT_CA)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_ROOT_CA_CHECKSUM)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_ROOT_CA_PLAIN)] = null;
        $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_PRIVATE_KEY_PASSWORD)] = null;

        $certificate = $this->getCertificateByType($this->getDevice(), $certificateType);

        if (!$certificate) {
            return $variables;
        }

        if ($certificate->getCertificate()) {
            $decrypted = $this->encryptionManager->decrypt($certificate->getCertificate());
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CERTIFICATE_PLAIN)] = $decrypted;
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CERTIFICATE_CHECKSUM)] = $this->encryptionManager->getCrc32bCertificateChecksum($decrypted);

            $decryptedConvertedHex = $this->handleCertificateEncoding($decrypted, $deviceTypeCertificateType);
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CERTIFICATE)] = $decryptedConvertedHex;
        }

        if ($certificate->getPrivateKey()) {
            $decrypted = $this->encryptionManager->decrypt($certificate->getPrivateKey());
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_PRIVATE_KEY_PLAIN)] = $decrypted;
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_PRIVATE_KEY_CHECKSUM)] = $this->encryptionManager->getCrc32bCertificateChecksum($decrypted);

            $decryptedConvertedHex = $this->handleCertificateEncoding($decrypted, $deviceTypeCertificateType);
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_PRIVATE_KEY)] = $decryptedConvertedHex;
        }

        if ($certificate->getCertificateCa()) {
            $decrypted = $this->encryptionManager->decrypt($certificate->getCertificateCa());
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CA_PLAIN)] = $decrypted;

            $decryptedRootCa = $this->encryptionManager->getRootCaFromCaChain($decrypted);
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_ROOT_CA_PLAIN)] = $decryptedRootCa;
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CA_CHECKSUM)] = $this->encryptionManager->getCrc32bCertificateChecksum($decrypted);
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_ROOT_CA_CHECKSUM)] = $this->encryptionManager->getCrc32bCertificateChecksum($decryptedRootCa);

            $decryptedConvertedHex = $this->handleCertificateEncoding($decrypted, $deviceTypeCertificateType);
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_CA)] = $decryptedConvertedHex;
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_ROOT_CA)] = $this->handleCertificateEncoding($decryptedRootCa, $deviceTypeCertificateType);
        }

        if ($certificate->getPkcsPrivateKeyPassword()) {
            $variables[$this->getCertificateVariableName($variablePrefix, VariableInterface::VARIABLE_NAME_PRIVATE_KEY_PASSWORD)] = $this->encryptionManager->decrypt($certificate->getPkcsPrivateKeyPassword());
        }

        return $variables;
    }

    /**
     * Method checks if at least one of certificate variables was used in config.
     */
    protected function isCertificateVariableUsedInDeviceConfig(Feature $feature): bool
    {
        $getHasConfig = 'getHasConfig'.$feature->value;
        $getConfig = 'getConfig'.$feature->value;
        $getFormatConfig = 'getFormatConfig'.$feature->value;
        $getNameConfig = 'getNameConfig'.$feature->value;

        if (!$this->getDevice() || !$this->getDeviceType() || !$this->getDeviceType()->$getHasConfig() || !$this->getDeviceType()->getHasCertificates()) {
            return false;
        }

        if (!$this->getDeviceTemplate()) {
            return false;
        }

        $config = $this->getDeviceTemplate()->$getConfig();

        if (!$config) {
            return false;
        }

        if (!$config->getContent()) {
            return false;
        }

        $variables = array_keys($this->getCertificatesDeviceVariables());

        foreach ($variables as $variable) {
            if (ConfigGenerator::PHP == $config->getGenerator()) {
                $variableName = '$'.$variable;
            }

            if (ConfigGenerator::TWIG == $config->getGenerator()) {
                $variableName = $variable;
            }

            if (false !== strpos($config->getContent(), $variableName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method fills GSM data based on RequestModel saved into logs or device.
     */
    public function fillGsmData(GsmEntityInterface $entity): GsmEntityInterface
    {
        return $entity;
    }

    /**
     * Method fills primary firmware version data based on RequestModel saved into logs or device.
     */
    public function fillVersionFirmware1(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        return $entity;
    }

    /**
     * Method fills secondary firmware version data based on RequestModel saved into logs or device.
     */
    public function fillVersionFirmware2(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        return $entity;
    }

    /**
     * Method fills tetriary firmware version data based on RequestModel saved into logs or device.
     */
    public function fillVersionFirmware3(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        return $entity;
    }

    /**
     * Method fills communication data based on RequestModel saved into logs or device.
     */
    public function fillCommunicationData(CommunicationEntityInterface $entity): CommunicationEntityInterface
    {
        $entity->setXForwardedFor($this->getRequest() ? $this->getRequest()->headers->get('X-Forwarded-For') : '');
        $entity->setHost($this->getRequest() ? $this->getRequest()->getClientIp() : 'N/A');
        $entity->setSeenAt(new \DateTime());

        return $entity;
    }

    /**
     * Method increments device connection count.
     */
    protected function incrementDeviceConnections(): void
    {
        if (!$this->getDevice()) {
            return;
        }

        $this->connectionAggregationManager->incrementDeviceConnectionAmount($this->getDevice());
    }
}
