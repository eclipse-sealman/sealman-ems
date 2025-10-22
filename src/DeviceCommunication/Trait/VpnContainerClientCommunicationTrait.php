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

namespace App\DeviceCommunication\Trait;

use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\CertificateCategory;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\FieldRequirement;
use App\Enum\MasqueradeType;
use App\Exception\LogsException;
use App\Form\DeviceCommunication\VpnContainerClientRegisterAuthenticatorType;
use App\Model\FieldRequirementsModel;
use App\Model\ResponseModel;
use App\Model\VpnContainerClientModel;
use App\Model\VpnContainerClientResponseModel;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EventDispatcherTrait;
use App\Service\Helper\TranslatorTrait;
use App\Service\Helper\VpnLogManagerTrait;
use App\Service\Helper\VpnManagerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

trait VpnContainerClientCommunicationTrait
{
    use ConfigurationManagerTrait;
    use EventDispatcherTrait;
    use VpnLogManagerTrait;
    use VpnManagerTrait;
    use TranslatorTrait;

    /**
     * @var ?VpnContainerClientModel
     */
    protected $vpnContainerClientModel;

    public function getVpnContainerClientModel(): ?VpnContainerClientModel
    {
        return $this->vpnContainerClientModel;
    }

    public function setVpnContainerClientModel(?VpnContainerClientModel $vpnContainerClientModel)
    {
        $this->vpnContainerClientModel = $vpnContainerClientModel;
    }

    // This method assumes use of VpnContainerClientControllerTrait as controller
    public function getRoutes(
        DeviceType $deviceType,
        string $controllerName = 'App\Controller\DeviceCommunication\VpnContainerClientController',
        string $routePrefix = 'vpnContainerClient'): RouteCollection
    {
        $routeCollection = new RouteCollection();

        $path = $deviceType->getValidRoutePrefix().'/register';
        $defaults = [
                '_controller' => $controllerName.'::registerAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['POST'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = $routePrefix.'Register'.$deviceType->getId();

        $routeCollection->add($routeName, $route);

        $path = $deviceType->getValidRoutePrefix().'/configuration/{uuid}';
        $defaults = [
                '_controller' => $controllerName.'::configurationAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['POST', 'GET'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = $routePrefix.'Configuration'.$deviceType->getId();

        $routeCollection->add($routeName, $route);

        $path = $deviceType->getValidRoutePrefix().'/send/logs/{uuid}';
        $defaults = [
                '_controller' => $controllerName.'::sendLogsAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['POST'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = $routePrefix.'SendLogs'.$deviceType->getId();

        $routeCollection->add($routeName, $route);

        return $routeCollection;
    }

    public function getCommunicationProcedureRequirementsOptional(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_ENDPOINT_DEVICES,
        ];
    }

    public function getCommunicationProcedureRequirementsRequired(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_TEMPLATES,
            CommunicationProcedureRequirement::HAS_VARIABLES,
            CommunicationProcedureRequirement::HAS_MASQUERADE,
            CommunicationProcedureRequirement::HAS_CERTIFICATES,
            CommunicationProcedureRequirement::HAS_VPN,
        ];
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getCommunicationProcedureCertificateCategoryRequired
     */
    public function getCommunicationProcedureCertificateCategoryRequired(): array
    {
        return [
            CertificateCategory::DEVICE_VPN,
        ];
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getCommunicationProcedureCertificateCategoryOptional
     */
    public function getCommunicationProcedureCertificateCategoryOptional(): array
    {
        return [
            CertificateCategory::CUSTOM,
        ];
    }

    public function getCommunicationProcedureFieldsRequirements(): FieldRequirementsModel
    {
        $fieldRequirements = new FieldRequirementsModel();

        $fieldRequirements->setFieldSerialNumber(FieldRequirement::OPTIONAL);

        return $fieldRequirements;
    }

    public function generateIdentifier(Device $device): string
    {
        if ($device->getUuid()) {
            return $device->getUuid();
        }

        return parent::generateIdentifier($device);
    }

    protected function findDeviceByUuid(string $uuid): ?Device
    {
        if (!$this->getDeviceType()) {
            return null;
        }

        return $this->getRepository(Device::class)->findOneBy(
            [
                'uuid' => $uuid,
                'deviceType' => $this->getDeviceType(),
            ]
        );
    }

    /**
     * Method provides device by checking request - method is specifically used during authentication, before controller is executed.
     */
    public function getRequestedDevice(Request $request): ?Device
    {
        // Checking if uuid was provided in URI (using Symfony Router matcher)
        $uuid = $request->attributes->get('uuid'); // if not provided $uuid = null

        // Try POST parameters
        if (!$uuid) {
            $vpnContainerClientModel = $this->executePostAuthenticatorForm($request, VpnContainerClientRegisterAuthenticatorType::class, ['validation_groups' => 'authentication'], true, true);
            if (!$vpnContainerClientModel) {
                return null;
            }

            // Checking if uuid was provided as validation groups are not used
            if (!$vpnContainerClientModel->getUuid()) {
                return null;
            }

            $uuid = $vpnContainerClientModel->getUuid();
        }

        return $this->findDeviceByUuid($uuid);
    }

    public function applyDenyAccess(): Response|ResponseModel|null
    {
        if ($this->configurationManager->isVpnSecuritySuiteBlocked()) {
            $this->getResponse()->setError('VPN Security Suite functionalities not available');
            $this->communicationLogManager->createLogError('log.noVpnSecuritySuite');

            return $this->getResponse();
        }

        return null;
    }

    protected function initilizeVpnContainerClientEndpoint(DeviceType $deviceType, Request $request, ?VpnContainerClientModel $vpnContainerClientModel, null|string $uuid = null): Response|ResponseModel|null
    {
        $this->setDeviceType($deviceType);
        $this->setRequest($request);
        $this->setVpnContainerClientModel($vpnContainerClientModel);
        $this->setResponse(new VpnContainerClientResponseModel());
        $this->getResponse()->setUuid($uuid ? $uuid : ($vpnContainerClientModel ? $vpnContainerClientModel->getUuid() : null));
        $this->getResponse()->setName($uuid ? null : ($vpnContainerClientModel ? $vpnContainerClientModel->getName() : null));
        $this->communicationLogManager->setRequest($this->getRequest());

        $securityResponse = $this->applyDenyAccess();
        if ($securityResponse) {
            return $securityResponse;
        }

        // check if DT is configured correctly for this communication procedure
        if (!$this->isCommunicationProcedureRequirementsSatisfied($this->getCommunicationProcedureRequirementsRequired())) {
            // detailed logs will be filled by method above
            $this->communicationLogManager->createLogCritical('log.communicationProcedureRequirementsNotSatisfied');
            // RESPONSE
            $this->getResponse()->setError('Device Type configuration error.');

            return $this->getResponse();
        }

        return null;
    }

    public function processVpnContainerClientRegister(DeviceType $deviceType, Request $request, VpnContainerClientModel $vpnContainerClientModel): ResponseModel
    {
        $initilizeResponse = $this->initilizeVpnContainerClientEndpoint($deviceType, $request, $vpnContainerClientModel);
        if ($initilizeResponse) {
            return $initilizeResponse;
        }

        if ($this->getVpnContainerClientModel()->getUuid()) {
            $this->setDevice($this->findDeviceByUuid($this->getVpnContainerClientModel()->getUuid()));

            if (!$this->getDevice()) {
                $this->getResponse()->setError('VPN Container Client cannot be found - register without UUID');
                $this->getResponse()->setClearUuid(true);
                $this->getResponse()->setUuid(null);
                $this->communicationLogManager->createLogError('log.vccRegisterUuidNotFound', ['identifier' => $this->getVpnContainerClientModel()->getUuid()]);

                return $this->getResponse();
            }
        }

        $this->communicationLogManager->createLogInfo('log.incomingRequest');

        if (!$this->getDevice()) {
            $this->processMissingVpnContainerClient();
        } else {
            $this->communicationLogManager->setDevice($this->getDevice());
            $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
            $this->communicationLogManager->createLogDebug('log.deviceFound');
        }

        if (!$this->getDevice()) {
            // if processMissingVpnContainerClient didn't create device finish communication procedure
            return $this->getResponse();
        }

        if ($this->processDeviceTypeMismatch()) {
            return $this->getResponse();
        }

        $this->updateVpnContainerClientLastDataInformation();

        $this->communicationLogManager->createLogDebug('log.requestProcessed', [], $this->getDeviceModelResponse($this->getResponse())->__toString());

        $this->entityManager->flush();

        $this->getResponse()->setName($this->getDevice()->getName());
        $this->getResponse()->setUuid($this->getDevice()->getUuid());

        return $this->getResponse();
    }

    public function processVpnContainerClientSendLogs(DeviceType $deviceType, Request $request, VpnContainerClientModel $vpnContainerClientModel, string $uuid): ResponseModel
    {
        $initilizeResponse = $this->initilizeVpnContainerClientEndpoint($deviceType, $request, $vpnContainerClientModel, $uuid);
        if ($initilizeResponse) {
            return $initilizeResponse;
        }

        $this->setDevice($this->findDeviceByUuid($uuid));

        if (!$this->getDevice()) {
            $this->getResponse()->setError("VPN Container Client with identifier = '".$uuid."' not found");
            $this->getResponse()->setUnregister(true);
            $this->communicationLogManager->createLogError('log.vccSendLogsNotFound', ['identifier' => $uuid]);

            return $this->getResponse();
        }

        $this->communicationLogManager->setDevice($this->getDevice());
        $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
        $this->communicationLogManager->createLogInfo('log.incomingRequest');

        if ($this->processDeviceTypeMismatch()) {
            return $this->getResponse();
        }

        // saves logs into diagnoseLog table and adds communication log message
        $this->communicationLogManager->handleDiagnoseLogModel($this->getVpnContainerClientModel()->getLogs());

        $this->entityManager->flush();

        $this->getResponse()->setUuid($this->getDevice()->getUuid());

        return $this->getResponse();
    }

    public function processVpnContainerClientConfiguration(DeviceType $deviceType, Request $request, string $uuid): ResponseModel
    {
        $incrementConnections = false;

        $initilizeResponse = $this->initilizeVpnContainerClientEndpoint($deviceType, $request, null, $uuid);
        if ($initilizeResponse) {
            return $initilizeResponse;
        }

        $this->setDevice($this->findDeviceByUuid($uuid));

        if (!$this->getDevice()) {
            $this->getResponse()->setError("VPN Container Client with identifier = '".$uuid."' not found");
            $this->getResponse()->setUnregister(true);
            $this->communicationLogManager->createLogError('log.vccConfigNotFound', ['identifier' => $uuid]);

            return $this->getResponse();
        }

        $this->communicationLogManager->setDevice($this->getDevice());
        $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
        $this->communicationLogManager->createLogInfo('log.incomingRequest');

        if ($this->processDeviceTypeMismatch()) {
            return $this->getResponse();
        }

        $this->updateVpnContainerClientLastDataInformation();

        $incrementConnections = true;

        if (!$this->getDevice()->getEnabled()) {
            $this->communicationLogManager->createLogWarning('log.deviceDisabled');

            $this->getResponse()->setError("VPN Container Client with identifier = '".$uuid."' is disabled");

            return $this->getResponse();
        }

        $deviceVpnCertificateType = $this->getDeviceVpnCertificateType();
        if (!$deviceVpnCertificateType) {
            $this->communicationLogManager->createLogWarning('log.deviceNoCertificate');

            $this->getResponse()->setError("VPN Container Client with identifier = '".$uuid."' SSL certificate doesn't exist or expired");

            return $this->getResponse();
        }

        // Keeping autorenew before checks, so in case of unforseen errors proper error will be responded
        $this->processAutoRenewCertificates();

        $certificate = $this->getCertificateByType($this->getDevice(), $deviceVpnCertificateType);
        if (!$certificate || !$certificate->hasCertificate()) {
            $this->communicationLogManager->createLogWarning('log.deviceNoCertificate');

            $this->getResponse()->setError("VPN Container Client with identifier = '".$uuid."' SSL certificate doesn't exist or expired");

            return $this->getResponse();
        }

        if (!$this->getDevice()->getVpnIp()) {
            $this->communicationLogManager->createLogWarning('log.deviceNoVpnIpAddress');

            $this->getResponse()->setError("VPN Container Client with identifier = '".$uuid."' doesn't have OpenVPN IP address assigned");

            return $this->getResponse();
        }

        $this->processVpnContainerClientAutoGenerationOrRenewDeviceSecrets();

        $this->prepareConfiguration();

        $this->communicationLogManager->createLogDebug('log.requestProcessed', [], $this->getDeviceModelResponse($this->getResponse())->__toString());

        $this->entityManager->flush();

        if ($incrementConnections) {
            $this->incrementDeviceConnections();
            $this->entityManager->flush();
        }

        $this->getResponse()->setName($this->getDevice()->getName());
        $this->getResponse()->setUuid($this->getDevice()->getUuid());

        return $this->getResponse();
    }

    // Method is separated from main flow to allow easier override especially in EdgeGatewayWithVpnContainerClientCommunication
    public function processVpnContainerClientAutoGenerationOrRenewDeviceSecrets(): void
    {
        $this->processAutoGenerationOrRenewDeviceSecrets();
    }

    protected function prepareConfiguration()
    {
        $config = [
            'openvpn' => $this->getOpenVpnConfiguration(),
            'nat' => $this->getNatConfiguration(),
            'masquerade' => $this->getMasqueradeConfiguration(),
            'routes' => $this->getRoutesConfiguration(),
        ];

        $this->getResponse()->setConfiguration($config);
    }

    protected function getRoutesConfiguration(): array
    {
        $config = [
            [
                'host' => $this->vpnManager->getDevicesVpnGateway(),
                'device' => 'openvpn',
            ],
            [
                'network' => $this->vpnManager->getTechniciansVpnNetwork(),
                'gateway' => $this->vpnManager->getDevicesVpnGateway(),
            ],
        ];

        return $config;
    }

    protected function getOpenVpnConfiguration(): array
    {
        $openvpnConfiguration = $this->vpnManager->generateConfiguration($this->getDevice());

        $config = $this->parseOpenVpnConfiguration($openvpnConfiguration);
        if (!isset($config['proto'])) {
            if (isset($config['remote'])) {
                $remoteArray = explode(' ', $config['remote']);
                if (3 == count($remoteArray)) {
                    $config['remote'] = $remoteArray[0].' '.$remoteArray[1];
                    $config['proto'] = $remoteArray[2];
                }
            }
        }

        if (count($config) < 2) {
            $this->vpnLogManager->createLogError('log.vpnInvalidConfigurationTemplate', device: $this->getDevice());
        }

        return $config;
    }

    protected function getMasqueradeConfiguration(): array
    {
        if (MasqueradeType::DISABLED == $this->getDevice()->getMasqueradeType()) {
            return [];
        }

        if (MasqueradeType::DEFAULT == $this->getDevice()->getMasqueradeType()) {
            return [
                ['subnet' => $this->vpnManager->getDevicesVpnNetwork()],
                ['subnet' => $this->vpnManager->getTechniciansVpnNetwork()],
            ];
        }

        // just a check this should never be true, due to previuos code
        if (MasqueradeType::ADVANCED !== $this->getDevice()->getMasqueradeType()) {
            return [];
        }

        $config = [];

        foreach ($this->getDevice()->getMasquerades() as $masquerade) {
            $config[] = [
                'subnet' => $masquerade->getSubnet(),
            ];
        }

        return $config;
    }

    protected function getNatConfiguration(): array
    {
        if (!$this->getDevice()->getVirtualIp()) {
            $this->vpnLogManager->createLogError('log.vpnNoVirtualIp', device: $this->getDevice());
        }

        $config = [
            [
                'source' => $this->getDevice()->getVirtualIp(),
                'destination' => $this->getDevice()->getVpnIp(),
            ],
        ];

        if ($this->getDevice()->getDeviceType()->getIsEndpointDevicesAvailable()) {
            foreach ($this->getDevice()->getEndpointDevices() as $endpointDevice) {
                $config[] = [
                'source' => $endpointDevice->getVirtualIp(),
                'destination' => $endpointDevice->getPhysicalIp(),
            ];
            }
        }

        return $config;
    }

    protected function parseOpenVpnConfiguration(string $openvpnConfiguration): array
    {
        $lines = explode("\n", $openvpnConfiguration);
        $config = [];

        $tag = null;
        // Read line by line
        foreach ($lines as $line) {
            $line = trim($line);
            // Save line only of not empty
            if ($this->isLine($line) && strlen($line) > 1) {
                $line = trim(preg_replace('/\s+/', ' ', $line));
                if (preg_match('/^(\S+)( (.*))?/', $line, $matches)) {
                    $key = strtolower($matches[1]);
                    if ($tag) {
                        if ($key == '</'.$tag.'>') {
                            $tag = null;
                        } else {
                            $config[$tag] .= $line;
                        }
                    } else {
                        if (preg_match('/^<(\S+)>/', $key, $tagMatches)) {
                            $tag = strtolower($tagMatches[1]);
                            $config[$tag] = '';
                        } else {
                            $config[$key] = isset($matches[3]) ? $matches[3] : true;
                        }
                    }
                }
            }
        }

        $config['ca'] = trim($config['ca'] ?? '');
        $config['cert'] = trim($config['cert'] ?? '');
        $config['key'] = trim($config['key'] ?? '');
        $config['tls-auth'] = trim($config['tls-auth'] ?? '');

        $remove['ca'] = 'CERTIFICATE';
        $remove['cert'] = 'CERTIFICATE';
        $remove['key'] = 'PRIVATE KEY';
        $remove['tls-auth'] = 'OpenVPN Static key V1';

        foreach ($remove as $key => $value) {
            $startString = '-----BEGIN '.$value.'-----';
            $endString = '-----END '.$value.'-----';
            if (0 === strpos($config[$key], $startString)) {
                if (strrpos($config[$key], $endString) === strlen($config[$key]) - strlen($endString)) {
                    $config[$key] = substr($config[$key], strlen($startString));
                    $config[$key] = substr($config[$key], 0, strlen($config[$key]) - strlen($endString));
                }
            }
        }

        $fullTag = '-----END CERTIFICATE----------BEGIN CERTIFICATE-----';
        $offset = 0;
        $endPos = strpos($config['ca'], $fullTag, $offset);
        if (false !== $endPos) {
            $caChain = [];
            while (false !== $endPos) {
                $caChain[] = substr($config['ca'], $offset, $endPos - $offset);
                $offset = $endPos + strlen($fullTag);
                $endPos = strpos($config['ca'], $fullTag, $offset);
            }
            $caChain[] = substr($config['ca'], $offset);
            $config['ca'] = $caChain;
        }

        return $config;
    }

    private function isLine(string $line): bool
    {
        return !(
            // Empty lines
            preg_match('/^\n+|^[\t\s]*\n+/m', $line) ||
            // Lines with comments
            preg_match('/^#/m', $line)
        );
    }

    protected function processMissingVpnContainerClient()
    {
        $this->communicationLogManager->createLogInfo('log.deviceNotFound', ['identifier' => $this->getVpnContainerClientModel()->getName()]);

        $this->createVpnContainerClient();
    }

    protected function createVpnContainerClient()
    {
        $this->setDevice(new Device());
        $this->getDevice()->setDeviceType($this->getDeviceType());
        $this->getDevice()->setVirtualSubnetCidr($this->getDeviceType()->getVirtualSubnetCidr());
        $this->getDevice()->setMasqueradeType($this->getDeviceType()->getMasqueradeType());
        $this->getDevice()->setName($this->getVpnContainerClientModel()->getName());
        $this->getDevice()->setSerialNumber($this->getVpnContainerClientModel()->getName());
        $this->getDevice()->setUuid($this->getDeviceTypeUniqueUuid());
        $this->getDevice()->setHashIdentifier($this->getDeviceUniqueHashIdentifier());
        $this->getDevice()->setIdentifier($this->generateIdentifier($this->getDevice()));

        $this->communicationLogManager->setDevice($this->getDevice());
        $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
        $this->communicationLogManager->createLogInfo('log.deviceCreate');

        $this->entityManager->persist($this->getDevice());

        $this->incrementDeviceConnections();

        $this->entityManager->flush();

        try {
            $this->dispatchDeviceUpdated($this->getDevice());
        } catch (LogsException $logsException) {
            // Do not handle LogsException, it is not vital to the whole process
            // All logs will be created any way
        }
    }

    // this function is used in controller directly
    protected function handleErrorResponse(DeviceType $deviceType, Request $request, FormInterface $form, array $messages, string $message): Response|ResponseModel
    {
        $response = new VpnContainerClientResponseModel();
        $response->setError($message);

        return $response;
    }

    protected function updateVpnContainerClientLastDataInformation(): void
    {
        $this->fillCommunicationData($this->getDevice());
    }
}
