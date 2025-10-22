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

use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\CommunicationProcedure;
use App\Model\DownloadFirmwareUrlModel;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\RouterInterfaceTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\Service\Attribute\Required;

class DeviceCommunicationFactory
{
    use EntityManagerTrait;
    use RouterInterfaceTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    protected null|RouteCollection $routeCollection = null;
    protected array $deviceTypeRouteNames = [];

    #[Required]
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getDeviceCommunicationByDeviceType(?DeviceType $deviceType): ?DeviceCommunicationInterface
    {
        if (!$deviceType) {
            return null;
        }

        if (!$deviceType->getCommunicationProcedure()) {
            return null;
        }

        $communicationProcedure = $this->getDeviceCommunicationByName($deviceType->getCommunicationProcedure()->value);
        $communicationProcedure->setDeviceType($deviceType);

        return $communicationProcedure;
    }

    public function getDeviceCommunicationByDevice(Device $device): ?DeviceCommunicationInterface
    {
        $communicationProcedure = $this->getDeviceCommunicationByDeviceType($device->getDeviceType());

        if (!$communicationProcedure) {
            return null;
        }

        $communicationProcedure->setDeviceType($device->getDeviceType());
        $communicationProcedure->setDevice($device);

        return $communicationProcedure;
    }

    public function getDeviceCommunicationByName(string $communicationProcedureName): DeviceCommunicationInterface
    {
        switch ($communicationProcedureName) {
            case CommunicationProcedure::NONE->value:
                return $this->container->get('App\DeviceCommunication\EmptyCommunication');
                break;
            case CommunicationProcedure::NONE_SCEP->value:
                return $this->container->get('App\DeviceCommunication\EmptyScepCommunication');
                break;
            case CommunicationProcedure::NONE_VPN->value:
                return $this->container->get('App\DeviceCommunication\EmptyVpnCommunication');
                break;
            case CommunicationProcedure::ROUTER_ONE_CONFIG->value:
                return $this->container->get('App\DeviceCommunication\RouterOneConfigCommunication');
                break;
            case CommunicationProcedure::ROUTER->value:
                return $this->container->get('App\DeviceCommunication\RouterCommunication');
                break;
            case CommunicationProcedure::ROUTER_DSA->value:
                return $this->container->get('App\DeviceCommunication\RouterDsaCommunication');
                break;
            case CommunicationProcedure::FLEXEDGE->value:
                return $this->container->get('App\DeviceCommunication\FlexEdgeCommunication');
                break;
            case CommunicationProcedure::SGGATEWAY->value:
                return $this->container->get('App\DeviceCommunication\SgGatewayCommunication');
                break;
            case CommunicationProcedure::VPNCONTAINERCLIENT->value:
                return $this->container->get('App\DeviceCommunication\VpnContainerClientCommunication');
                break;
            case CommunicationProcedure::EDGEGATEWAY->value:
                return $this->container->get('App\DeviceCommunication\EdgeGatewayCommunication');
                break;
            case CommunicationProcedure::EDGEGATEWAY_WITH_VPNCONTAINERCLIENT->value:
                return $this->container->get('App\DeviceCommunication\EdgeGatewayWithVpnContainerClientCommunication');
                break;
            default:
                return $this->container->get('App\DeviceCommunication\EmptyCommunication');
                break;
        }

        return $this->container->get('App\DeviceCommunication\EmptyCommunication');
    }

    public function getDeviceTypeRouteNames(): array
    {
        // Request to make sure deviceTypeRouteNames array is set
        $this->getDeviceTypesRoutes();

        return $this->deviceTypeRouteNames;
    }

    public function getDeviceTypesRoutes(): RouteCollection
    {
        // Caching to limit execution time - DeviceType routing list will not change during one request in most cases
        // Only during DeviceType edit form it will change, and after that routing table will not be used in that request (e.g. for URL generation)
        if ($this->routeCollection) {
            return $this->routeCollection;
        }

        $routes = new RouteCollection();

        try {
            $deviceTypes = $this->getRepository(DeviceType::class)->findBy(['enabled' => true]);
            foreach ($deviceTypes as $deviceType) {
                if (!$deviceType->getIsAvailable()) {
                    continue;
                }
                $communicationProcedure = $this->getDeviceCommunicationByDeviceType($deviceType);
                if ($communicationProcedure) {
                    $deviceTypeRoutes = $communicationProcedure->getRoutes($deviceType);
                    $routes->addCollection($deviceTypeRoutes);

                    foreach ($deviceTypeRoutes->all() as $key => $route) {
                        $this->deviceTypeRouteNames[$key] = $deviceType;
                    }
                }
            }
        } catch (\Exception $ex) {
            return new RouteCollection();
        }

        $this->routeCollection = $routes;

        return $this->routeCollection;
    }

    public function getRequestedDeviceType(Request $request): ?DeviceType
    {
        $matchedRoute = $this->routerInterface->matchRequest($request);
        if (!$matchedRoute || !isset($matchedRoute['_route'])) {
            return null;
        }
        // check if /device/check/auth/firmware was matched
        if ('device_download_auth_firmware' === $matchedRoute['_route']) {
            $downloadFirmwareUrlModel = $this->parseDownloadFirmwareUri($request);
            if (null === $downloadFirmwareUrlModel) {
                return null;
            }

            $deviceType = $this->getDeviceTypeBySlug($downloadFirmwareUrlModel->getDeviceTypeSlug());
            if (!$deviceType) {
                return null;
            }

            return $deviceType;
        }

        $deviceTypeRouteNames = $this->getDeviceTypeRouteNames();

        if (isset($deviceTypeRouteNames[$matchedRoute['_route']])) {
            return $deviceTypeRouteNames[$matchedRoute['_route']];
        }

        return null;
    }

    public function getRequestedDevice(Request $request): ?Device
    {
        $matchedRoute = $this->routerInterface->matchRequest($request);
        if (!$matchedRoute || !isset($matchedRoute['_route'])) {
            return null;
        }
        // check if /device/check/auth/firmware was matched
        if ('device_download_auth_firmware' === $matchedRoute['_route']) {
            $downloadFirmwareUrlModel = $this->parseDownloadFirmwareUri($request);
            if (null === $downloadFirmwareUrlModel) {
                return null;
            }

            $device = $this->getDeviceByHash($downloadFirmwareUrlModel->getDeviceHash());
            if (!$device) {
                return null;
            }

            return $device;
        }

        $deviceType = $this->getRequestedDeviceType($request);
        if (!$deviceType) {
            return null;
        }

        $communicationProcedure = $this->getDeviceCommunicationByDeviceType($deviceType);
        if (!$communicationProcedure) {
            return null;
        }

        return $communicationProcedure->getRequestedDevice($request);
    }

    public function getDeviceTypeBySlug(string $slug): ?DeviceType
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['slug' => $slug]);

        if (!$deviceType) {
            return null;
        }

        if (!$deviceType->getIsAvailable()) {
            return null;
        }

        return $deviceType;
    }

    public function getDeviceByHash(string $hash): ?Device
    {
        $device = $this->getRepository(Device::class)->findOneBy(['hashIdentifier' => $hash]);

        if (!$device) {
            return null;
        }

        if (!$device->getDeviceType()?->getIsAvailable()) {
            return null;
        }

        return $device;
    }

    /**
     * Validate and parse download firmware $uri from Request. When $request has valid uri function will return
     * DownloadFirmwareUrlModel. Otherwise it returns null.
     */
    public function parseDownloadFirmwareUri(Request $request): ?DownloadFirmwareUrlModel
    {
        $uri = $request->headers->get('X-Original-URI');

        if (!$uri) {
            return null;
        }

        // $uri is expected to be structured in following way:
        // /df/DEVICE_HASH/secret/DEVICE_TYPE_SLUG/UUID/FILENAME
        $prefix = '/df/';

        if (!str_starts_with($uri, $prefix)) {
            return null;
        }

        $uriParts = explode('/', substr($uri, \strlen($prefix)));

        // some times trailing slash might be added
        if (5 != count($uriParts) && 6 != count($uriParts)) {
            return null;
        }

        if (!$uriParts[0] || !$uriParts[1] || !$uriParts[2] || !$uriParts[3] || !$uriParts[4]) {
            return null;
        }

        return new DownloadFirmwareUrlModel($uriParts[0], $uriParts[1], $uriParts[2], $uriParts[3], $uriParts[4]);
    }
}
