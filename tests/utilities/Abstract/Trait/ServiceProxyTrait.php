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

namespace Tests\Utilities\Abstract\Trait;

use App\Entity\Configuration;
use App\Service\ConfigurationManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Code separated for readability.
 * To be used only with AbstractTestCase class.
 */
trait ServiceProxyTrait
{
    public static function getApplication(): Application
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        return $application;
    }

    public function getHttpClient(): KernelBrowser
    {
        return static::getClient();
    }

    public function setHttpClient(?AbstractBrowser $client = null): KernelBrowser
    {
        return static::getClient($client);
    }

    public function getKernel(): KernelInterface
    {
        return static::$kernel;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->getService('doctrine')->getManager();
    }

    public function getRepository(string $class): EntityRepository
    {
        return $this->getEntityManager()->getRepository($class);
    }

    public function getService(string $service)
    {
        return static::getContainer()->get($service);
    }

    public function getConfigurationManager(): ConfigurationManager
    {
        return $this->getService(ConfigurationManager::class);
    }

    public function getConfiguration(): Configuration
    {
        return $this->getConfigurationManager()->getConfiguration();
    }
}
