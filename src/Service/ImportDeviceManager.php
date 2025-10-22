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

namespace App\Service;

use App\Deny\DeviceDeny;
use App\Entity\AccessTag;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\DeviceMasquerade;
use App\Entity\DeviceType;
use App\Entity\DeviceVariable;
use App\Entity\ImportFile;
use App\Entity\ImportFileRow;
use App\Entity\ImportFileRowLog;
use App\Entity\ImportFileRowVariable;
use App\Entity\Label;
use App\Entity\Template;
use App\Enum\FieldRequirement;
use App\Enum\ImportFileRowImportStatus;
use App\Enum\ImportFileRowParseStatus;
use App\Enum\ImportFileStatus;
use App\Enum\LogLevel;
use App\Exception\LogsException;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\EventDispatcherTrait;
use App\Service\Helper\TranslatorTrait;
use App\Service\Helper\ValidatorTrait;
use App\Service\Helper\VpnManagerTrait;
use Carve\ApiBundle\Service\Helper\DenyManagerTrait;

class ImportDeviceManager
{
    use DenyManagerTrait;
    use EntityManagerTrait;
    use EventDispatcherTrait;
    use DeviceCommunicationFactoryTrait;
    use VpnManagerTrait;
    use ValidatorTrait;
    use TranslatorTrait;

    public function importNext(ImportFile $importFile): void
    {
        $row = $this->getNextImportFileRow($importFile);
        if (!$row) {
            return;
        }

        if (ImportFileRowParseStatus::INVALID === $row->getParseStatus()) {
            $row->setImportStatus(ImportFileRowImportStatus::ERROR);

            $this->entityManager->persist($row);
            $this->entityManager->flush();

            $this->importFileUpdateStatus($importFile);

            return;
        }

        $this->clearLogs($row);

        $device = $this->getDevice($row);

        $errors = $this->validator->validate($device, null, ['Default', 'device:common', 'device:create']);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addLogError($row, $error->getPropertyPath(), $error->getMessage(), $error->getParameters(), '');
            }

            $row->setImportStatus(ImportFileRowImportStatus::ERROR);

            $this->entityManager->flush();

            $this->importFileUpdateStatus($importFile);

            return;
        }

        $row->setImportStatus(ImportFileRowImportStatus::SUCCESS);

        // Persisting to allow for propper entity handling by events
        $this->entityManager->persist($device);

        try {
            // Fill deny to allow for propper certificate type handling
            $this->fillDeny(DeviceDeny::class, $device);
            $this->dispatchDeviceUpdated($device);
        } catch (LogsException $logsException) {
            $row->setImportStatus(ImportFileRowImportStatus::WARNING);
            foreach ($logsException->getErrors() as $error) {
                $this->addLogWarning($row, 'deviceType', $error['message'], $error['parameters'], '');
            }
        }

        $this->entityManager->persist($device);
        $this->entityManager->persist($row);
        $this->entityManager->flush();

        $this->importFileUpdateStatus($importFile);
    }

    protected function importFileUpdateStatus(ImportFile $importFile): void
    {
        $row = $this->getNextImportFileRow($importFile);

        if ($row) {
            $importFile->setStatus(ImportFileStatus::IMPORTING);
        } else {
            $importFile->setStatus(ImportFileStatus::FINISHED);
        }

        $this->entityManager->persist($importFile);
        $this->entityManager->flush();
    }

    protected function getDevice(ImportFileRow $row): Device
    {
        $importFile = $row->getImportFile();
        $deviceType = $row->getDeviceType();

        $template = $row->getTemplate();
        if ($template && !$deviceType->getHasTemplates()) {
            $this->addLogWarning($row, 'template', 'template.templatesDisabled');

            $template = null;
        }

        if ($template && $template->getDeviceType() !== $deviceType) {
            $this->addLogWarning($row, 'template', 'template.deviceTypeMismatch');

            $template = null;
        }

        $templateVersion = null;
        if ($template) {
            $templateVersion = $template->getProductionTemplate();

            if (!$templateVersion) {
                $this->addLogWarning($row, 'template', 'template.templateVersionProductionMissing');
            }
        }

        $device = new Device();
        $device->setTemplate($template);
        $device->setDeviceType($deviceType);
        $device->setName($row->getName());
        $device->setEnabled($row->getEnabled());

        // default values have to be set even if vpn license is not available
        $device->setVirtualSubnetCidr($deviceType->getVirtualSubnetCidr());
        $device->setMasqueradeType($deviceType->getMasqueradeType());

        if ($templateVersion && $importFile->getApplyAccessTags()) {
            $device->setAccessTags($templateVersion->getAccessTags());
        } else {
            $device->setAccessTags($row->getAccessTags());
        }

        $device->setLabels($row->getLabels());

        if (FieldRequirement::UNUSED !== $deviceType->getFieldSerialNumber()) {
            $device->setSerialNumber($row->getSerialNumber());
        }

        if (FieldRequirement::UNUSED !== $deviceType->getFieldImsi()) {
            $device->setImsi($row->getImsi());
        }

        if (FieldRequirement::UNUSED !== $deviceType->getFieldModel()) {
            $device->setModel($row->getModel());
        }

        if (FieldRequirement::UNUSED !== $deviceType->getFieldRegistrationId()) {
            $device->setRegistrationId($row->getRegistrationId());
        }

        if (FieldRequirement::UNUSED !== $deviceType->getFieldEndorsementKey()) {
            $device->setEndorsementKey($row->getEndorsementKey());
        }

        if (FieldRequirement::UNUSED !== $deviceType->getFieldHardwareVersion()) {
            $device->setHardwareVersion($row->getHardwareVersion());
        }

        if ($deviceType->getHasConfig1() && !$deviceType->getHasAlwaysReinstallConfig1()) {
            $device->setReinstallConfig1($row->getReinstallConfig1());
        }

        if ($deviceType->getHasConfig2() && !$deviceType->getHasAlwaysReinstallConfig2()) {
            $device->setReinstallConfig2($row->getReinstallConfig2());
        }

        if ($deviceType->getHasConfig3() && !$deviceType->getHasAlwaysReinstallConfig3()) {
            $device->setReinstallConfig3($row->getReinstallConfig3());
        }

        if ($deviceType->getHasVariables()) {
            if ($templateVersion && $importFile->getApplyVariables()) {
                foreach ($templateVersion->getVariables() as $templateVersionVariable) {
                    $deviceVariable = new DeviceVariable();
                    $deviceVariable->setName($templateVersionVariable->getName());
                    $deviceVariable->setVariableValue($templateVersionVariable->getVariableValue());
                    $device->addVariable($deviceVariable);
                }
            } else {
                foreach ($row->getVariables() as $rowVariable) {
                    $deviceVariable = new DeviceVariable();
                    $deviceVariable->setName($rowVariable->getName());
                    $deviceVariable->setVariableValue($rowVariable->getVariableValue());
                    $device->addVariable($deviceVariable);
                }
            }
        }

        if ($templateVersion) {
            $device->setDescription($templateVersion->getDeviceDescription());

            if ($deviceType->getIsEndpointDevicesAvailable()) {
                if ($templateVersion->getVirtualSubnetCidr()) {
                    $device->setVirtualSubnetCidr($templateVersion->getVirtualSubnetCidr());
                }

                foreach ($templateVersion->getEndpointDevices() as $templateEndpointDevice) {
                    $endpointDevice = new DeviceEndpointDevice();
                    $endpointDevice->setName($templateEndpointDevice->getName());
                    $endpointDevice->setDescription($templateEndpointDevice->getDescription());
                    $endpointDevice->setPhysicalIp($templateEndpointDevice->getPhysicalIp());
                    $endpointDevice->setPhysicalIpSortable(ip2long($templateEndpointDevice->getPhysicalIp()));
                    $endpointDevice->setVirtualIpHostPart($templateEndpointDevice->getVirtualIpHostPart());
                    $endpointDevice->setAccessTags($templateEndpointDevice->getAccessTags());

                    $device->addEndpointDevice($endpointDevice);
                }
            }

            if ($deviceType->getIsMasqueradeAvailable()) {
                if ($templateVersion->getMasqueradeType()) {
                    $device->setMasqueradeType($templateVersion->getMasqueradeType());
                }

                foreach ($templateVersion->getMasquerades() as $templateMasquerade) {
                    $masquerade = new DeviceMasquerade();
                    $masquerade->setSubnet($templateMasquerade->getSubnet());

                    $device->addMasquerade($masquerade);
                }
            }
        }

        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);
        $device->setIdentifier($communicationProcedure->generateIdentifier($device));
        $device->setUuid($communicationProcedure->getDeviceTypeUniqueUuid());
        $device->setHashIdentifier($communicationProcedure->getDeviceUniqueHashIdentifier());

        return $device;
    }

    public function getNextImportFileRow(ImportFile $importFile): ?ImportFileRow
    {
        $queryBuilder = $this->getRepository(ImportFileRow::class)->createQueryBuilder('ifr');
        $queryBuilder->andWhere('ifr.importFile = :importFile');
        $queryBuilder->andWhere('ifr.importStatus = :importStatus');
        $queryBuilder->setParameter('importFile', $importFile);
        $queryBuilder->setParameter('importStatus', ImportFileRowImportStatus::PENDING);
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getTotal(ImportFile $importFile): int
    {
        $queryBuilder = $this->getRepository(ImportFileRow::class)->createQueryBuilder('ifr');
        $queryBuilder->select('COUNT(ifr)');
        $queryBuilder->andWhere('ifr.importFile = :importFile');
        $queryBuilder->setParameter('importFile', $importFile);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getPending(ImportFile $importFile): int
    {
        $queryBuilder = $this->getRepository(ImportFileRow::class)->createQueryBuilder('ifr');
        $queryBuilder->select('COUNT(ifr)');
        $queryBuilder->andWhere('ifr.importFile = :importFile');
        $queryBuilder->andWhere('ifr.importStatus = :importStatus');
        $queryBuilder->setParameter('importFile', $importFile);
        $queryBuilder->setParameter('importStatus', ImportFileRowImportStatus::PENDING);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getStringFieldsMapping(): array
    {
        return [
            'name' => 0,
            'serialNumber' => 1,
            'imsi' => 2,
            'model' => 3,
            'registrationId' => 4,
            'endorsementKey' => 5,
            'hardwareVersion' => 6,
        ];
    }

    public function parse(ImportFile $file): void
    {
        $excelRows = $this->getExcelRows($file->getFilepath());

        foreach ($excelRows as $rowKey => $excelRow) {
            // Skip first row due to labels
            if (0 === $rowKey) {
                continue;
            }

            $filteredExcelRow = array_filter($excelRow, function ($value) {
                return !is_null($value);
            });

            if (count($filteredExcelRow) <= 0) {
                // Skip empty rows
                continue;
            }

            $row = new ImportFileRow();
            $row->setImportFile($file);
            $file->getRows()->add($row);
            $row->setRowKey($rowKey);

            $row->setDeviceType($this->getDeviceType($row, $excelRow));
            $row->setTemplate($this->getTemplate($row, $excelRow));
            $this->addAccessTags($row, $excelRow);
            $this->addLabels($row, $excelRow);
            $this->addVariables($row, $excelRow);
            $this->mapStringFields($row, $excelRow);

            $this->validate($row);

            $this->updateRowStatus($row);

            $this->entityManager->persist($row);
            // We need flush() inside loop to allow validating uniqueness via database
            $this->entityManager->flush();
        }
    }

    protected function getDeviceType(ImportFileRow $row, array $excelRow): ?DeviceType
    {
        $columnKey = 7;
        $deviceTypeName = $excelRow[$columnKey] ?? null;
        if (!$deviceTypeName) {
            $this->addLogError($row, 'deviceType', 'deviceType.nameMissing');

            return null;
        }

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => $deviceTypeName,
        ]);

        if (!$deviceType) {
            $this->addLogError($row, 'deviceType', 'deviceType.notFound', ['name' => $deviceTypeName]);

            return null;
        }

        if (!$deviceType->getEnabled()) {
            $this->addLogError($row, 'deviceType', 'deviceType.disabled', ['name' => $deviceTypeName]);

            return null;
        }

        if (!$deviceType->getIsAvailable()) {
            $this->addLogError($row, 'deviceType', 'deviceType.notAvailable', ['name' => $deviceTypeName]);

            return null;
        }

        return $deviceType;
    }

    protected function getTemplate(ImportFileRow $row, array $excelRow): ?Template
    {
        $columnKey = 8;
        $templateName = $excelRow[$columnKey] ?? null;
        if (!$templateName) {
            return null;
        }

        $deviceType = $row->getDeviceType();
        if (!$deviceType) {
            return null;
        }

        if (!$deviceType->getHasTemplates()) {
            $this->addLogWarning($row, 'template', 'template.templatesDisabled');

            return null;
        }

        $template = $this->getRepository(Template::class)->findOneBy([
            'name' => $templateName,
        ]);

        if (!$template) {
            $this->addLogWarning($row, 'template', 'template.notFound', ['name' => $templateName]);

            return null;
        }

        if ($template->getDeviceType() !== $deviceType) {
            $this->addLogWarning($row, 'template', 'template.deviceTypeMismatch');

            return null;
        }

        return $template;
    }

    protected function addAccessTags(ImportFileRow $row, array $excelRow): void
    {
        $columnKey = 9;
        $accessTagsString = $excelRow[$columnKey] ?? null;
        if (!$accessTagsString) {
            return;
        }

        $accessTagNames = explode(',', $accessTagsString);
        $accessTagNames = array_map('trim', $accessTagNames);

        foreach ($accessTagNames as $accessTagName) {
            $accessTag = $this->getRepository(AccessTag::class)->findOneBy([
                'name' => $accessTagName,
            ]);

            if (!$accessTag) {
                $this->addLogWarning($row, 'accessTags', 'accessTag.notFound', ['name' => $accessTagName]);

                continue;
            }

            $row->getAccessTags()->add($accessTag);
        }
    }

    protected function addLabels(ImportFileRow $row, array $excelRow): void
    {
        $columnKey = 10;
        $labelsString = $excelRow[$columnKey] ?? null;
        if (!$labelsString) {
            return;
        }

        $labelNames = explode(',', $labelsString);
        $labelNames = array_map('trim', $labelNames);

        foreach ($labelNames as $labelName) {
            $label = $this->getRepository(Label::class)->findOneBy([
                'name' => $labelName,
            ]);

            if (!$label) {
                $this->addLogWarning($row, 'labels', 'label.notFound', ['name' => $labelName]);

                continue;
            }

            $row->getLabels()->add($label);
        }
    }

    protected function addVariables(ImportFileRow $row, array $excelRow): void
    {
        $columnStartKey = 11;
        $excelRowHasVariables = true;

        $variableName = $excelRow[$columnStartKey] ?? null;
        // Variable value can be 0 or '' ("falsy" value)
        $variableValue = $excelRow[$columnStartKey + 1] ?? null;

        if (!$variableName && null === $variableValue) {
            $excelRowHasVariables = false;
        }

        if (!$excelRowHasVariables) {
            return;
        }

        $deviceType = $row->getDeviceType();
        if (!$deviceType) {
            $this->addLogWarning($row, 'variables', 'variable.missingDeviceType');

            return;
        }

        if (!$deviceType->getHasVariables()) {
            $this->addLogWarning($row, 'variables', 'variable.variablesDisabled');

            return;
        }

        for ($i = $columnStartKey; $i < count($excelRow); $i += 2) {
            $variableNumber = $i - $columnStartKey;
            $variableName = $excelRow[$i] ?? null;
            // Variable value can be 0 or '' ("falsy" value)
            $variableValue = $excelRow[$i + 1] ?? null;

            if (!$variableName && null === $variableValue) {
                // No further data. Break
                break;
            }

            if (!$variableName) {
                $this->addLogWarning($row, 'variables.'.$variableNumber.'.name', 'variable.missingName');

                continue;
            }

            if (null === $variableValue) {
                $this->addLogWarning($row, 'variables.'.$variableNumber.'.value', 'variable.missingValue');

                continue;
            }

            $variable = new ImportFileRowVariable();
            $variable->setName($variableName);
            $variable->setVariableValue($variableValue);
            $row->addVariable($variable);

            $errors = $this->validator->validate($variable, null, ['Default', 'importFileRow:import']);
            if (count($errors) > 0) {
                $row->removeVariable($variable);

                foreach ($errors as $error) {
                    $this->addLogWarning($row, 'variables.'.$variableNumber.'.'.$error->getPropertyPath(), $error->getMessage(), $error->getParameters(), '');
                }

                // Error. Break
                break;
            }

            $this->entityManager->persist($variable);
        }
    }

    protected function mapStringFields(ImportFileRow $row, array $excelRow): void
    {
        foreach ($this->getStringFieldsMapping() as $field => $columnKey) {
            $value = $excelRow[$columnKey] ?? null;
            if (null !== $value) {
                $value = trim((string) $value);
            }

            $setter = 'set'.ucfirst($field);

            $row->$setter($value);
        }
    }

    protected function validate(ImportFileRow $row): void
    {
        $this->validateField($row, 'name', true, true, true);
        $deviceType = $row->getDeviceType();
        if (!$deviceType) {
            return;
        }

        $this->validateFieldRequirement($row, 'serialNumber', $deviceType->getFieldSerialNumber(), true);
        $this->validateFieldRequirement($row, 'imsi', $deviceType->getFieldImsi(), true);
        $this->validateFieldRequirement($row, 'registrationId', $deviceType->getFieldRegistrationId(), true);
        $this->validateFieldRequirement($row, 'endorsementKey', $deviceType->getFieldEndorsementKey(), true);
        $this->validateFieldRequirement($row, 'hardwareVersion', $deviceType->getFieldHardwareVersion(), false);
        $this->validateFieldRequirement($row, 'model', $deviceType->getFieldModel(), false);
    }

    protected function validateFieldRequirement(ImportFileRow $row, string $fieldName, FieldRequirement $fieldRequirement, bool $unique): void
    {
        $this->validateField($row, $fieldName, FieldRequirement::UNUSED !== $fieldRequirement, FieldRequirement::REQUIRED === $fieldRequirement, $unique);
    }

    protected function validateField(ImportFileRow $row, string $fieldName, bool $used, bool $required, bool $unique): void
    {
        $getter = 'get'.ucfirst($fieldName);
        $value = $row->$getter();
        if (!$value && !$required) {
            return;
        }

        $translatedFieldName = $this->trans('label.'.$fieldName);
        if (!ctype_upper($translatedFieldName)) {
            $translatedFieldName = lcfirst($translatedFieldName);
        }

        $columnKey = $this->getStringFieldsMapping()[$fieldName];
        if ($value && !$used) {
            $setter = 'set'.ucfirst($fieldName);
            $row->$setter(null);

            $this->addLogWarning($row, $fieldName, 'field.disabled', ['fieldName' => $translatedFieldName]);

            return;
        }

        if (!$value && $required) {
            $this->addLogError($row, $fieldName, 'field.required', ['fieldName' => $translatedFieldName]);

            return;
        }

        if (!$value) {
            return;
        }

        if ($unique) {
            if ($this->isDeviceFieldDuplicated($row, $fieldName)) {
                $this->addLogError($row, $fieldName, 'field.deviceNotUnique', ['fieldName' => $translatedFieldName]);

                return;
            }

            if ($this->isImportRowFieldDuplicated($row, $fieldName)) {
                $this->addLogError($row, $fieldName, 'field.importFileNotUnique', ['fieldName' => $translatedFieldName]);

                return;
            }
        }
    }

    protected function isDeviceFieldDuplicated(ImportFileRow $row, string $fieldName): bool
    {
        $getter = 'get'.ucfirst($fieldName);
        $value = $row->$getter();

        $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder('d');
        $queryBuilder->andWhere('d.deviceType = :deviceType');
        $queryBuilder->andWhere('d.'.$fieldName.' = :value');
        $queryBuilder->setParameter('value', $value);
        $queryBuilder->setParameter('deviceType', $row->getDeviceType());
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult() ? true : false;
    }

    protected function isImportRowFieldDuplicated(ImportFileRow $row, string $fieldName): bool
    {
        $getter = 'get'.ucfirst($fieldName);
        $value = $row->$getter();

        $queryBuilder = $this->getRepository(ImportFileRow::class)->createQueryBuilder('ifr');
        $queryBuilder->andWhere('ifr.importFile = :importFile');
        $queryBuilder->andWhere('ifr.deviceType = :deviceType');
        $queryBuilder->andWhere('ifr.'.$fieldName.' = :value');
        $queryBuilder->setParameter('importFile', $row->getImportFile());
        $queryBuilder->setParameter('value', $value);
        $queryBuilder->setParameter('deviceType', $row->getDeviceType());
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult() ? true : false;
    }

    protected function updateRowStatus(ImportFileRow $row): void
    {
        $parseStatus = ImportFileRowParseStatus::VALID;

        foreach ($row->getLogs() as $log) {
            switch ($log->getLogLevel()) {
                case LogLevel::WARNING:
                    if (ImportFileRowParseStatus::VALID === $parseStatus) {
                        $parseStatus = ImportFileRowParseStatus::WARNING;
                    }
                    break;
                case LogLevel::ERROR:
                case LogLevel::CRITICAL:
                    $parseStatus = ImportFileRowParseStatus::INVALID;
                    break;
            }
        }

        $row->setParseStatus($parseStatus);
        $row->setImportStatus(ImportFileRowImportStatus::PENDING);
    }

    protected function clearLogs(ImportFileRow $row): void
    {
        $queryBuilder = $this->getRepository(ImportFileRowLog::class)->createQueryBuilder('ifrl');
        $queryBuilder->delete();
        $queryBuilder->andWhere('ifrl.row = :row');
        $queryBuilder->setParameter('row', $row);
        $queryBuilder->getQuery()->execute();
    }

    protected function addLogDebug(ImportFileRow $row, ?string $columnName, string $label, array $parameters = [], string $prefix = 'log.importDevice.'): void
    {
        $this->addLog(LogLevel::DEBUG, $row, $columnName, $label, $parameters, $prefix);
    }

    protected function addLogInfo(ImportFileRow $row, ?string $columnName, string $label, array $parameters = [], string $prefix = 'log.importDevice.'): void
    {
        $this->addLog(LogLevel::INFO, $row, $columnName, $label, $parameters, $prefix);
    }

    protected function addLogWarning(ImportFileRow $row, ?string $columnName, string $label, array $parameters = [], string $prefix = 'log.importDevice.'): void
    {
        $this->addLog(LogLevel::WARNING, $row, $columnName, $label, $parameters, $prefix);
    }

    protected function addLogError(ImportFileRow $row, ?string $columnName, string $label, array $parameters = [], string $prefix = 'log.importDevice.'): void
    {
        $this->addLog(LogLevel::ERROR, $row, $columnName, $label, $parameters, $prefix);
    }

    protected function addLogCritical(ImportFileRow $row, ?string $columnName, string $label, array $parameters = [], string $prefix = 'log.importDevice.'): void
    {
        $this->addLog(LogLevel::CRITICAL, $row, $columnName, $label, $parameters, $prefix);
    }

    protected function addLog(LogLevel $level, ImportFileRow $row, ?string $columnName, string $label, array $parameters = [], string $prefix = 'log.importDevice.'): void
    {
        $log = new ImportFileRowLog();
        $log->setLogLevel($level);
        $log->setColumnName($columnName);
        $log->setMessage($this->trans($prefix.$label, $parameters, null, true));

        $log->setRow($row);
        $row->getLogs()->add($log);

        $this->entityManager->persist($log);
    }

    protected function getExcelRows(string $filepath)
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $worksheet = $spreadsheet->getActiveSheet();
        // This is kind of ridiculous in case of some XLSX files, because it returns 1048576 rows instead of correct number
        // This leads to out of memory exception
        $highestRow = min($worksheet->getHighestRow(), 10000);
        $highestColumn = $worksheet->getHighestColumn();

        return $worksheet->rangeToArray('A1:'.$highestColumn.$highestRow);
    }
}
