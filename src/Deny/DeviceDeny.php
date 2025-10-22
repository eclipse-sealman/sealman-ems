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

namespace App\Deny;

use App\Entity\Device;
use App\Enum\CommunicationProcedure;
use App\Enum\Feature;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuthorizationCheckerTrait;

class DeviceDeny extends AbstractApiDuplicateCertificateTypeObjectDeny implements VpnConfigDenyInterface, VpnOpenConnectionDenyInterface, VpnCloseConnectionDenyInterface
{
    use VpnConfigDenyTrait;
    use VpnOpenConnectionDenyTrait;
    use VpnCloseConnectionDenyTrait;
    use AuthorizationCheckerTrait;
    use SecurityHelperTrait;

    public const ENABLE = 'enable';
    public const DISABLE = 'disable';
    public const VARIABLE_ADD = 'variableAdd';
    public const VARIABLE_DELETE = 'variableDelete';
    public const TEMPLATE_APPLY = 'templateApply';
    public const GENERATE_CONFIG_PRIMARY = 'generateConfigPrimary';
    public const GENERATE_CONFIG_SECONDARY = 'generateConfigSecondary';
    public const GENERATE_CONFIG_TERTIARY = 'generateConfigTertiary';
    public const BATCH_REINSTALL_CONFIG = 'batchReinstallConfig';
    public const BATCH_REINSTALL_FIRMWARE = 'batchReinstallFirmware';
    public const BATCH_REQUEST_DIAGNOSE = 'batchRequestDiagnose';
    public const BATCH_REQUEST_CONFIG = 'batchRequestConfig';
    public const ACCESS_TAG_ADD = 'accessTagAdd';
    public const ACCESS_TAG_DELETE = 'accessTagDelete';
    public const PREDEFINED_VARIABLES = 'predefinedVariables';
    public const LOGS = 'logs';
    public const COMMUNICATION_LOGS = 'communicationLogs';
    public const DEVICE_COMMANDS = 'deviceCommands';
    public const DIAGNOSE_LOGS = 'diagnoseLogs';
    public const CONFIG_LOGS = 'configLogs';
    public const VPN_LOGS = 'vpnLogs';
    public const SECRET_LOGS = 'secretLogs';
    public const VPN_ACTIONS = 'vpnActions';
    public const SHOW_CONFIG_EXPAND = 'showConfigExpand';

    public function enableDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if ($device->getEnabled()) {
            return 'alreadyEnabled';
        }

        return null;
    }

    public function disableDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if (!$device->getEnabled()) {
            return 'alreadyDisabled';
        }

        return null;
    }

    public function variableAddDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        $deviceType = $device->getDeviceType();
        if (!$deviceType->getHasVariables()) {
            return 'variablesDisabled';
        }

        return null;
    }

    public function variableDeleteDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        $deviceType = $device->getDeviceType();
        if (!$deviceType->getHasVariables()) {
            return 'variablesDisabled';
        }

        return null;
    }

    public function templateApplyDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        $deviceType = $device->getDeviceType();
        if (!$deviceType->getHasTemplates()) {
            return 'templatesDisabled';
        }

        return null;
    }

    public function commonGenerateConfigDeny(Device $device, Feature $feature): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        $deviceType = $device->getDeviceType();
        if (!$deviceType->getHasTemplates()) {
            return 'templatesDisabled';
        }

        switch ($feature) {
            case Feature::PRIMARY:
                if (!$deviceType->getHasConfig1()) {
                    return 'config1Disabled';
                }
                break;
            case Feature::SECONDARY:
                if (!$deviceType->getHasConfig2()) {
                    return 'config2Disabled';
                }
                break;
            case Feature::TERTIARY:
                if (!$deviceType->getHasConfig3()) {
                    return 'config3Disabled';
                }
                break;
        }

        $template = $device->getTemplate();
        if (!$template) {
            return 'templateMissing';
        }

        $templateVersion = $device->getTemplateVersion();
        if (!$templateVersion) {
            return 'templateVersionMising';
        }

        switch ($feature) {
            case Feature::PRIMARY:
                if (!$templateVersion->getConfig1()) {
                    return 'config1Missing';
                }
                break;
            case Feature::SECONDARY:
                if (!$templateVersion->getConfig2()) {
                    return 'config2Missing';
                }
                break;
            case Feature::TERTIARY:
                if (!$templateVersion->getConfig3()) {
                    return 'config3Missing';
                }
                break;
        }

        return null;
    }

    public function generateConfigPrimaryDeny(Device $device): ?string
    {
        return $this->commonGenerateConfigDeny($device, Feature::PRIMARY);
    }

    public function generateConfigSecondaryDeny(Device $device): ?string
    {
        return $this->commonGenerateConfigDeny($device, Feature::SECONDARY);
    }

    public function generateConfigTertiaryDeny(Device $device): ?string
    {
        return $this->commonGenerateConfigDeny($device, Feature::TERTIARY);
    }

    public function batchReinstallConfigDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        // deviceType related security will be done in controller, due to batch action requirements

        return null;
    }

    public function batchReinstallFirmwareDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        // deviceType related security will be done in controller, due to batch action requirements

        return null;
    }

    public function batchRequestDiagnoseDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        // deviceType related security will be done in controller, due to batch action requirements

        return null;
    }

    public function batchRequestConfigDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        // deviceType related security will be done in controller, due to batch action requirements

        return null;
    }

    public function accessTagAddDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        // deviceType related security will be done in controller, due to batch action requirements

        return null;
    }

    public function accessTagDeleteDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        // deviceType related security will be done in controller, due to batch action requirements

        return null;
    }

    public function predefinedVariablesDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        return null;
    }

    public function showConfigExpandDeny(Device $device): ?string
    {
        if (!$device->getDeviceType()->getHasTemplates()) {
            return 'templatesDisabled';
        }

        if (!$device->getDeviceType()->getHasConfig1() && !$device->getDeviceType()->getHasConfig2() && !$device->getDeviceType()->getHasConfig3()) {
            return 'deviceTypeHasNoConfigCapability';
        }

        return null;
    }

    public function logsDeny(Device $device): ?string
    {
        if (
            null !== $this->communicationLogsDeny($device) &&
            null !== $this->deviceCommandsDeny($device) &&
            null !== $this->diagnoseLogsDeny($device) &&
            null !== $this->configLogsDeny($device) &&
            null !== $this->secretLogsDeny($device) &&
            null !== $this->vpnLogsDeny($device)
        ) {
            return 'accessDenied';
        }

        return null;
    }

    public function communicationLogsDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if (
            in_array(
                $device->getDeviceType()->getCommunicationProcedure(),
                [
                    CommunicationProcedure::NONE,
                    CommunicationProcedure::NONE_SCEP,
                    CommunicationProcedure::NONE_VPN,
                ]
            )) {
            return 'deviceTypeHasNoCommunicationCapability';
        }

        return null;
    }

    public function deviceCommandsDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if (!$device->getDeviceType()->getHasDeviceCommands()) {
            return 'deviceTypeHasNoDeviceCommandsCapability';
        }

        return null;
    }

    public function diagnoseLogsDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if (!$device->getDeviceType()->getHasRequestDiagnose()) {
            return 'deviceTypeHasNoRequestDiagnoseCapability';
        }

        return null;
    }

    public function configLogsDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if (!$device->getDeviceType()->getHasConfig1() && !$device->getDeviceType()->getHasConfig2() && !$device->getDeviceType()->getHasConfig3()) {
            return 'deviceTypeHasNoConfigCapability';
        }

        return null;
    }

    public function vpnLogsDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN_VPN') && !$this->isGranted('ROLE_ADMIN_SCEP') && !$this->isGranted('ROLE_VPN')) {
            return 'accessDenied';
        }

        // SCEP functionality creates vpnLogs
        if (!$device->getDeviceType()->getIsVpnAvailable() && !$device->getDeviceType()->getHasCertificates()) {
            return 'deviceTypeHasNoVpnCapability';
        }

        return null;
    }

    public function secretLogsDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return 'accessDenied';
        }

        return null;
    }

    public function editDeny(Device $device): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            // There is possiblity for VPN user to list device without access due to having access to device's endpoint device
            $hasDeviceAccess = false;
            foreach ($device->getAccessTags() as $accessTag) {
                if ($this->getUser()->getAccessTags()->contains($accessTag)) {
                    $hasDeviceAccess = true;
                    break;
                }
            }

            if (!$hasDeviceAccess) {
                return 'accessDenied';
            }
        }

        return null;
    }

    public function vpnActionsDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN_VPN') && !$this->isGranted('ROLE_VPN')) {
            return 'accessDenied';
        }

        if (!$device->getDeviceType()->getIsVpnAvailable()) {
            return 'deviceTypeHasNoVpnCapability';
        }

        return null;
    }

    public function duplicateDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        return null;
    }

    public function deleteDeny(Device $device): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return 'accessDenied';
        }

        return null;
    }
}
