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

use App\DeviceCommunication\DeviceCommunicationInterface;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\ConfigDeviceStatus;
use App\Enum\ConfigFormat;
use App\Enum\ConfigGenerator;
use App\Enum\Feature;
use App\Model\ConfigDevice;
use App\Service\Helper\CommunicationLogManagerTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\TemplateManagerTrait;
use App\Service\Helper\TranslatorTrait;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class ConfigManager
{
    use TranslatorTrait;
    use TemplateManagerTrait;
    use CommunicationLogManagerTrait;
    use DeviceCommunicationFactoryTrait;

    /**
     * Twig environment used to generate configs with variables.
     *
     * @var Environment
     */
    protected $deviceTwig;

    /**
     * Twig environment used to validate configs without variables.
     *
     * @var Environment
     */
    protected $configTwig;

    /**
     * Currently used deviceCommunication.
     *
     * @var ?DeviceCommunicationInterface
     */
    protected $deviceCommunication = null;

    public function getDeviceCommunication(): ?DeviceCommunicationInterface
    {
        return $this->deviceCommunication;
    }

    public function setDeviceCommunication(?DeviceCommunicationInterface $deviceCommunication)
    {
        $this->deviceCommunication = $deviceCommunication;
    }

    public function getDeviceVariables(DeviceType $deviceType, Device $device, bool $decryptSecretValues = false, bool $createLogs = true): ?array
    {
        if (!$this->getDeviceCommunication()) {
            $this->setDeviceCommunication($this->deviceCommunicationFactory->getDeviceCommunicationByDevice($device));
        }

        if (!$this->getDeviceCommunication()) {
            return [];
        }

        $this->getDeviceCommunication()->setDeviceType($deviceType);
        $this->getDeviceCommunication()->setDevice($device);

        return $this->getDeviceCommunication()->getDeviceVariables($decryptSecretValues, $createLogs);
    }

    public function generateDeviceConfig(DeviceType $deviceType, Device $device, Feature $feature, bool $decryptSecretValues = false, bool $createLogs = true): ConfigDevice
    {
        $getHasConfig = 'getHasConfig'.$feature->value;
        $getConfig = 'getConfig'.$feature->value;
        $getFormatConfig = 'getFormatConfig'.$feature->value;
        $getNameConfig = 'getNameConfig'.$feature->value;

        $configDevice = new ConfigDevice();
        $configDevice->setErrorMessageTemplate('log.configDeviceSystemError');
        $configDevice->setErrorMessageTemplateVariables([
            '{{ identifier }}' => $device && $device->getIdentifier() ? $device->getIdentifier() : 'N/A',
            '{{ deviceName }}' => $deviceType && $deviceType->getDeviceName() ? $deviceType->getDeviceName() : 'N/A',
            '{{ nameConfig }}' => $deviceType && $deviceType->$getNameConfig() ? $deviceType->$getNameConfig() : 'N/A',
        ]);

        if (!$device || !$deviceType || !$deviceType->$getHasConfig()) {
            return $configDevice;
        }

        if (!$this->getDeviceTemplate($device)) {
            $this->setConfigDeviceError($configDevice, 'log.deviceTemplateMissing');

            return $configDevice;
        } elseif (!$this->getDeviceTemplate($device)->$getConfig()) {
            $this->setConfigDeviceError($configDevice, 'log.deviceConfigMissing');

            return $configDevice;
        }

        $config = $this->getDeviceTemplate($device)->$getConfig();
        $configDevice->setConfig($config);

        $configDevice->setConfigTemplate($config->getContent());
        $configDevice->setGenerator($config->getGenerator());

        $variables = $this->getDeviceVariables($deviceType, $device, $decryptSecretValues, $createLogs);
        $configDevice->setVariables($variables);

        if (ConfigFormat::JSON == $deviceType->$getFormatConfig()) {
            $configDevice = $this->generateJsonConfig($configDevice);
        }

        if (ConfigFormat::PLAIN == $deviceType->$getFormatConfig()) {
            $configDevice = $this->generatePlainConfig($configDevice);
        }

        if ($configDevice->isGenerated()) {
            if ($createLogs) {
                $this->communicationLogManager->createLogDebug('log.configDeviceConfigGenerated');
            }
        } else {
            if ($createLogs) {
                $this->communicationLogManager->createLogError($configDevice->getErrorMessage(), [], null, null, false, false);
            }
        }

        return $configDevice;
    }

    protected function setConfigDeviceError(ConfigDevice $configDevice, string $message, bool $translate = true): void
    {
        $configDevice->setStatus(ConfigDeviceStatus::ERROR);
        $configDevice->setErrorMessageTemplate($message);
        $configDevice->setErrorMessage($this->trans($configDevice->getErrorMessageTemplate(), $configDevice->getErrorMessageTemplateVariables()));
    }

    protected function generatePlainConfig(ConfigDevice $configDevice): ConfigDevice
    {
        if (!$configDevice->isNew()) {
            return $configDevice;
        }

        try {
            $configGenerated = $this->generateConfig($configDevice);

            if (!$configGenerated) {
                $this->setConfigDeviceError($configDevice, 'log.configDeviceInvalidEmpty'); // fix messages
            } else {
                $configDevice->setStatus(ConfigDeviceStatus::GENERATED);
                $configDevice->setConfigGenerated($configGenerated);
            }
        } catch (\Throwable $exception) {
            $errorMessage = self::cleanTwigExceptionMessage($exception->getMessage());

            $this->setConfigDeviceError($configDevice, $errorMessage, false);
        }

        return $configDevice;
    }

    protected function generateJsonConfig(ConfigDevice $configDevice): ConfigDevice
    {
        $unescapedVariables = $configDevice->getVariables();
        $escapedVariables = $this->escapeJsonVariablesArray($unescapedVariables);
        $configDevice->setVariables($escapedVariables);

        $configDevice = $this->generatePlainConfig($configDevice);

        if (!$configDevice->isGenerated()) {
            return $configDevice;
        }

        $configGenerated = $configDevice->getConfigGenerated();

        if (null === json_decode($configGenerated)) {
            $configDevice->setConfigGenerated(null);
            $this->setConfigDeviceError($configDevice, 'log.configDeviceInvalidJson');
        }

        return $configDevice;
    }

    protected function escapeJsonVariablesArray(array $variables): array
    {
        return array_map(function ($variable) {
            return $this->escapeJsonVariable($variable);
        }, $variables);
    }

    protected function escapeJsonVariable(mixed $variable): mixed
    {
        // Using gettype() seems more readable and easier to extend than typehinting
        $type = gettype($variable);
        switch ($type) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'NULL':
                return $variable;
            case 'string':
                return $this->escapeJsonString($variable);
            case 'array':
                return array_map(function ($variableValue) {
                    return $this->escapeJsonVariable($variableValue);
                }, $variable);
        }

        throw new \Exception('Type "'.$type.'" is not supported by escapeJsonVariable');
    }

    protected function escapeJsonString(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        // list from www.json.org: (\b backspace, \f formfeed)
        $escapers = ['\\', '/', '"', "\n", "\r", "\t", "\x08", "\x0c"];
        $replacements = ['\\\\', '\\/', '\\"', '\\n', '\\r', '\\t', '\\f', '\\b'];
        $result = str_replace($escapers, $replacements, $value);

        return $result;
    }

    protected function generateConfig(ConfigDevice $configDevice): ?string
    {
        switch ($configDevice->getGenerator()) {
            case ConfigGenerator::PHP:
                return $this->generateConfigPhp($configDevice);
            case ConfigGenerator::TWIG:
                return $this->generateConfigTwig($configDevice);
            default:
                return null;
        }
    }

    /**
     * Apparently in PHP 7.0 or 7.1 (and later) eval function throw ParseError exception and qoute:
     * "It is not possible to catch a parse error in eval() using set_error_handler()."
     * https://www.php.net/manual/en/function.eval.php.
     */
    protected function generateConfigPhp(ConfigDevice $configDevice): ?string
    {
        $configTemplate = $configDevice->getConfigTemplate();
        $variables = $configDevice->getVariables();

        $errorHandler = set_error_handler(function ($errno, $errstr) {
            throw new \Exception($errstr);
        }, -1);

        try {
            extract($variables);

            ob_start();
            eval('?>'.$configTemplate);
            $configGenerated = ob_get_contents();

            ob_end_clean();
            set_error_handler($errorHandler);

            return $configGenerated;
        } catch (\Throwable $e) {
            ob_end_clean();
            set_error_handler($errorHandler);

            throw $e;
        }
    }

    protected function generateConfigTwig(ConfigDevice $configDevice): ?string
    {
        $twig = $this->getDeviceTwig();

        $template = $twig->createTemplate($configDevice->getConfigTemplate());

        return $twig->render($template, $configDevice->getVariables() ?? []);
    }

    public function validateConfigTwig(string $content): ?string
    {
        $twig = $this->getConfigTwig();

        try {
            $template = $twig->createTemplate($content);
            $twig->render($template);
        } catch (\Throwable $exception) {
            return self::cleanTwigExceptionMessage($exception->getMessage());
        }

        return null;
    }

    /**
     * Cleans exception message by replacing information about name of the template.
     *
     * Example:
     * 1. Unknown "asd" filter in "__string_template__a64f5ce08d3d1afd3b81388bc91ca44c40fd528d72396dd110bdcd804d9d32d7" at line 2.
     * 2. Too few arguments to function twig_array_reduce(), 2 passed in /var/www/application/vendor/twig/twig/src/Environment.php(358) : eval()'d code on line 45 and at least 3 expected
     *
     * Will be transformed to:
     * 1. Unknown "asd" filter in Twig template at line 2.
     * 2. Too few arguments to function twig_array_reduce(), 2 passed.
     */
    public static function cleanTwigExceptionMessage(string $message): string
    {
        $patterns = [
            '/"__string_template__([a-z0-9]{32})"/',
            '/ in \/var\/www\/(.*)/',
        ];

        $replacements = [
            'Twig template',
            '.',
        ];

        return preg_replace($patterns, $replacements, $message);
    }

    protected function getDeviceTwig(): Environment
    {
        if (!$this->deviceTwig) {
            $this->deviceTwig = $this->getTwig(true);
        }

        return $this->deviceTwig;
    }

    protected function getConfigTwig(): Environment
    {
        if (!$this->configTwig) {
            $this->configTwig = $this->getTwig(false);
        }

        return $this->configTwig;
    }

    protected function getTwig(bool $strictVariables): Environment
    {
        // Create instance of raw Twig Environment with any valid loader
        return new Environment(new ArrayLoader(), [
            // disable auto-escaping
            'autoescape' => false,
            // Do NOT ignore invalid variables in templates
            'strict_variables' => $strictVariables,
        ]);
    }
}
