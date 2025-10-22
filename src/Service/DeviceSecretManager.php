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

use App\Entity\CommunicationLog;
use App\Entity\ConfigLog;
use App\Entity\DeviceSecret;
use App\Entity\DiagnoseLog;
use App\Entity\SecretLog;
use App\Enum\SecretOperation;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\TranslatorTrait;
use App\Service\Helper\UserTrait;

class DeviceSecretManager
{
    use EncryptionManagerTrait;
    use EntityManagerTrait;
    use TranslatorTrait;
    use UserTrait;

    public function encryptDeviceSecret(DeviceSecret $deviceSecret): void
    {
        if (null === $deviceSecret->getSecretValue()) {
            return;
        }

        $deviceSecret->setSecretValue($this->encryptionManager->encrypt($deviceSecret->getSecretValue()));
    }

    public function getDecryptedSecretValue(DeviceSecret $deviceSecret): ?string
    {
        if (null === $deviceSecret->getSecretValue()) {
            return null;
        }

        $decryptedSecret = $this->encryptionManager->decrypt($deviceSecret->getSecretValue());

        if (false === $decryptedSecret) {
            return null;
        }

        return $decryptedSecret;
    }

    public function getDecryptedPreviousSecretLogValue(SecretLog $secretLog): ?string
    {
        if (null === $secretLog->getPreviousSecretValue()) {
            return null;
        }

        $decryptedSecret = $this->encryptionManager->decrypt($secretLog->getPreviousSecretValue());

        if (false === $decryptedSecret) {
            return null;
        }

        return $decryptedSecret;
    }

    public function getDecryptedUpdatedSecretLogValue(SecretLog $secretLog): ?string
    {
        if (null === $secretLog->getUpdatedSecretValue()) {
            return null;
        }

        $decryptedSecret = $this->encryptionManager->decrypt($secretLog->getUpdatedSecretValue());

        if (false === $decryptedSecret) {
            return null;
        }

        return $decryptedSecret;
    }

    public function getDecryptedCommunicationLogContent(CommunicationLog $logObject): ?string
    {
        if (null === $logObject->getCommunicationLogContent()?->getContent()) {
            return null;
        }

        $decryptedContent = $this->encryptionManager->decrypt($logObject->getCommunicationLogContent()->getContent());

        if (false === $decryptedContent) {
            return null;
        }

        return $decryptedContent;
    }

    public function getDecryptedConfigLogContent(ConfigLog $logObject): ?string
    {
        if (null === $logObject->getConfigLogContent()?->getContent()) {
            return null;
        }

        $decryptedContent = $this->encryptionManager->decrypt($logObject->getConfigLogContent()->getContent());

        if (false === $decryptedContent) {
            return null;
        }

        return $decryptedContent;
    }

    public function getDecryptedDiagnoseLogContent(DiagnoseLog $logObject): ?string
    {
        if (null === $logObject->getDiagnoseLogContent()?->getContent()) {
            return null;
        }

        $decryptedContent = $this->encryptionManager->decrypt($logObject->getDiagnoseLogContent()->getContent());

        if (false === $decryptedContent) {
            return null;
        }

        return $decryptedContent;
    }

    public function generateRandomSecret(DeviceSecret $deviceSecret): string
    {
        $lowercaseSet = mb_str_split('abcdefghjkmnpqrstuvwxyz');
        $uppercaseSet = mb_str_split('ABCDEFGHJKMNPQRSTUVWXYZ');
        $digitsSet = mb_str_split('123456789');
        $specialCharsSet = mb_str_split('!@#$%&*?');
        $allCharactersSet = array_merge($lowercaseSet, $uppercaseSet, $digitsSet, $specialCharsSet);

        $password = '';

        for ($i = 0; $i < $deviceSecret->getDeviceTypeSecret()->getSecretLowercaseLettersAmount(); ++$i) {
            $password .= $lowercaseSet[array_rand($lowercaseSet)];
        }

        for ($i = 0; $i < $deviceSecret->getDeviceTypeSecret()->getSecretUppercaseLettersAmount(); ++$i) {
            $password .= $uppercaseSet[array_rand($uppercaseSet)];
        }

        for ($i = 0; $i < $deviceSecret->getDeviceTypeSecret()->getSecretDigitsAmount(); ++$i) {
            $password .= $digitsSet[array_rand($digitsSet)];
        }

        for ($i = 0; $i < $deviceSecret->getDeviceTypeSecret()->getSecretSpecialCharactersAmount(); ++$i) {
            $password .= $specialCharsSet[array_rand($specialCharsSet)];
        }

        $pendingLength = $deviceSecret->getDeviceTypeSecret()->getSecretMinimumLength() - strlen($password);

        for ($i = 0; $i < $pendingLength; ++$i) {
            $password .= $allCharactersSet[array_rand($allCharactersSet)];
        }

        $password = str_shuffle($password);

        return $password;
    }

    public function createUserShowCommunicationLogContentLog(CommunicationLog $logObject): SecretLog
    {
        return $this->createSecretLog($logObject, SecretOperation::USER_SHOW_COMMUNICATION_LOG, 'log.secretLog.userShowCommunicationLogContent', ['logId' => $logObject->getId()]);
    }

    public function createUserShowConfigLogContentLog(ConfigLog $logObject): SecretLog
    {
        return $this->createSecretLog($logObject, SecretOperation::USER_SHOW_CONFIG_LOG, 'log.secretLog.userShowConfigLogContent', ['logId' => $logObject->getId()]);
    }

    public function createUserShowDiagnoseLogContentLog(DiagnoseLog $logObject): SecretLog
    {
        return $this->createSecretLog($logObject, SecretOperation::USER_SHOW_DIAGNOSE_LOG, 'log.secretLog.userShowDiagnoseLogContent', ['logId' => $logObject->getId()]);
    }

    public function createUserShowPreviousSecretLog(SecretLog $secretLog): SecretLog
    {
        return $this->createSecretLog($secretLog, SecretOperation::USER_SHOW_PREVIOUS_LOG, 'log.secretLog.userShowPreviousLog', ['secretLogId' => $secretLog->getId()]);
    }

    public function createUserShowUpdatedSecretLog(SecretLog $secretLog): SecretLog
    {
        return $this->createSecretLog($secretLog, SecretOperation::USER_SHOW_UPDATED_LOG, 'log.secretLog.userShowUpdatedLog', ['secretLogId' => $secretLog->getId()]);
    }

    public function createCommunicationShowSecretLog(DeviceSecret $deviceSecret): SecretLog
    {
        return $this->createSecretLog($deviceSecret, SecretOperation::COMMUNICATION_SHOW, 'log.secretLog.communicationShow');
    }

    public function createCommunicationRenewSecretLog(DeviceSecret $deviceSecret, string $previousSecretValue): SecretLog
    {
        return $this->createSecretLog($deviceSecret, SecretOperation::COMMUNICATION_RENEW, 'log.secretLog.communicationRenew', [], $deviceSecret->getSecretValue(), $previousSecretValue);
    }

    public function createCommunicationCreateSecretLog(DeviceSecret $deviceSecret): SecretLog
    {
        return $this->createSecretLog($deviceSecret, SecretOperation::COMMUNICATION_RENEW, 'log.secretLog.communicationCreate', [], $deviceSecret->getSecretValue());
    }

    public function createUserShowSecretLog(DeviceSecret $deviceSecret): SecretLog
    {
        return $this->createSecretLog($deviceSecret, SecretOperation::USER_SHOW, 'log.secretLog.userShow');
    }

    public function createUserCreateSecretLog(DeviceSecret $deviceSecret): SecretLog
    {
        return $this->createSecretLog($deviceSecret, SecretOperation::USER_CREATE, 'log.secretLog.userCreate', [], $deviceSecret->getSecretValue());
    }

    public function createUserClearSecretLog(DeviceSecret $deviceSecret): SecretLog
    {
        return $this->createSecretLog($deviceSecret, SecretOperation::USER_CLEAR, 'log.secretLog.userClear', [], null, $deviceSecret->getSecretValue());
    }

    public function createUserEditSecretLog(DeviceSecret $deviceSecret, string $previousSecretValue): SecretLog
    {
        return $this->createSecretLog($deviceSecret, SecretOperation::USER_EDIT, 'log.secretLog.userEdit', [], $deviceSecret->getSecretValue(), $previousSecretValue);
    }

    protected function createSecretLog(DeviceSecret|SecretLog|CommunicationLog|ConfigLog|DiagnoseLog $object, SecretOperation $operation, string $message, array $messageVariables = [], ?string $updatedSecretValue = null, ?string $previousSecretValue = null): SecretLog
    {
        $secretLog = new SecretLog();

        switch (true) {
            case $object instanceof DeviceSecret:
                $secretLog->setDeviceSecret($object);
                $secretLog->setDevice($object?->getDevice());
                $secretLog->setDeviceTypeSecret($object?->getDeviceTypeSecret());
                $secretLog->setDeviceType($object?->getDeviceTypeSecret()?->getDeviceType());
                break;
            case $object instanceof SecretLog:
                $secretLog->setDeviceSecret($object?->getDeviceSecret());
                $secretLog->setDevice($object?->getDevice());
                $secretLog->setDeviceTypeSecret($object?->getDeviceTypeSecret());
                $secretLog->setDeviceType($object?->getDeviceType());
                break;
            case $object instanceof CommunicationLog:
                $secretLog->setDeviceSecret(null);
                $secretLog->setDevice($object?->getDevice());
                $secretLog->setDeviceTypeSecret(null);
                $secretLog->setDeviceType($object?->getDevice()?->getDeviceType());
                break;
            case $object instanceof ConfigLog:
                $secretLog->setDeviceSecret(null);
                $secretLog->setDevice($object?->getDevice());
                $secretLog->setDeviceTypeSecret(null);
                $secretLog->setDeviceType($object?->getDevice()?->getDeviceType());
                break;
            case $object instanceof DiagnoseLog:
                $secretLog->setDeviceSecret(null);
                $secretLog->setDevice($object?->getDevice());
                $secretLog->setDeviceTypeSecret(null);
                $secretLog->setDeviceType($object?->getDevice()?->getDeviceType());
                break;
            default:
                throw new \Exception('Unsupported object type: '.get_class($object).'.');
        }

        $secretLog->setOperation($operation);

        $secretLog->setUpdatedSecretValue($updatedSecretValue);
        $secretLog->setPreviousSecretValue($previousSecretValue);

        $translateMessageVariables = [
            '{{ userName }}' => $this->getUser()?->getRepresentation() ?: 'N/A',
            '{{ identifier }}' => $secretLog?->getDevice()?->getRepresentation() ?: 'N/A',
            '{{ name }}' => $secretLog?->getDevice()?->getName() ?: 'N/A',
            '{{ deviceType }}' => $secretLog?->getDeviceType()?->getName() ?: 'N/A',
            '{{ deviceName }}' => $secretLog?->getDeviceType()?->getDeviceName() ?: 'N/A',
            '{{ deviceTypeSecretName }}' => $secretLog?->getDeviceTypeSecret()?->getRepresentation() ?: 'N/A',
        ];

        $processedMessageVariables = [];
        foreach ($messageVariables as $messageVariableName => $messageVariableValue) {
            // Processing variable names from "variableName" to "{{ variableName }}" for convenience
            $processedMessageVariables['{{ '.$messageVariableName.' }}'] = $messageVariableValue;
        }

        $translateMessageVariables = array_merge($translateMessageVariables, $processedMessageVariables);

        $translatedMessage = $this->trans($message, $translateMessageVariables);

        $secretLog->setMessage($translatedMessage);

        $this->entityManager->persist($secretLog);

        return $secretLog;
    }
}
