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

namespace App\DependencyInjection;

use PDO;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DoctrineSslExtension extends Extension implements PrependExtensionInterface
{
    // Cannot use symfony env variable (SymfonyDirTrait), because env variables are not resolved yet
    protected string $filestorageDir = '';
    protected null|string $mysqlSslCa = null;
    protected bool $mysqlServerValidation = false;
    protected null|string $mysqlSslKey = null;
    protected null|string $mysqlSslCert = null;

    public function processBindVariables(ContainerBuilder $container)
    {
        $this->filestorageDir = $container->getParameter('FILESTORAGE_DIR') ?? '';
        $mysqlSslCa = $container->getParameter('MYSQL_SSL_CA');
        $mysqlServerValidation = $container->getParameter('MYSQL_SERVER_VALIDATION');
        $mysqlSslKey = $container->getParameter('MYSQL_SSL_KEY');
        $mysqlSslCert = $container->getParameter('MYSQL_SSL_CERT');

        if ($mysqlSslCa && strlen($mysqlSslCa) > 0) {
            if ('/' != substr($mysqlSslCa, 0, 1)) {
                $mysqlSslCa = '/'.$mysqlSslCa;
            }

            $this->mysqlSslCa = $mysqlSslCa;
        }

        $this->mysqlServerValidation = $this->getValidatedBooleanValue($mysqlServerValidation, false);

        if ($mysqlSslKey && strlen($mysqlSslKey) > 0) {
            if ('/' != substr($mysqlSslKey, 0, 1)) {
                $mysqlSslKey = '/'.$mysqlSslKey;
            }
            $this->mysqlSslKey = $mysqlSslKey;
        }
        if ($mysqlSslCert && strlen($mysqlSslCert) > 0) {
            if ('/' != substr($mysqlSslCert, 0, 1)) {
                $mysqlSslCert = '/'.$mysqlSslCert;
            }
            $this->mysqlSslCert = $mysqlSslCert;
        }
    }

    protected function getValidatedBooleanValue(mixed $value, bool $default = false): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value) || \is_float($value) || (\is_string($value) && strlen($value) > 0)) {
            // Copied from symfony ENV:bool
            return (bool) (filter_var($value, \FILTER_VALIDATE_BOOLEAN) ?: filter_var($value, \FILTER_VALIDATE_INT) ?: filter_var($value, \FILTER_VALIDATE_FLOAT));
        }

        return $default;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->processBindVariables($container);

        $configs = $container->getExtensionConfig('doctrine');

        $options = [];

        $container->setParameter('mysqlServerValidation', $this->mysqlServerValidation);
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $this->mysqlServerValidation;

        if ($this->mysqlSslCa) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $this->filestorageDir.$this->mysqlSslCa;
            $container->setParameter('mysqlSslCa', $this->filestorageDir.$this->mysqlSslCa);
        }
        if ($this->mysqlSslKey && $this->mysqlSslCert) {
            $options[PDO::MYSQL_ATTR_SSL_CERT] = $this->filestorageDir.$this->mysqlSslCert;
            $options[PDO::MYSQL_ATTR_SSL_KEY] = $this->filestorageDir.$this->mysqlSslKey;
            $container->setParameter('mysqlSslCert', $this->filestorageDir.$this->mysqlSslCert);
            $container->setParameter('mysqlSslKey', $this->filestorageDir.$this->mysqlSslKey);
        }

        if (count($options) > 0) {
            $container->prependExtensionConfig('doctrine', ['dbal' => ['options' => $options]]);
        }
    }
}
