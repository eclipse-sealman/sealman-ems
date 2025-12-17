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

namespace Tests\Utilities\Abstract;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Utilities\Abstract\Trait\AssertionsTrait;
use Tests\Utilities\Abstract\Trait\AuthenticationTrait;
use Tests\Utilities\Abstract\Trait\DataMixerTrait;
use Tests\Utilities\Abstract\Trait\HttpClientProxyTrait;
use Tests\Utilities\Abstract\Trait\MockFeatureTrait;
use Tests\Utilities\Abstract\Trait\RequestProxyTrait;
use Tests\Utilities\Abstract\Trait\ServiceProxyTrait;
use Tests\Utilities\ApiClient\ApiClient;

/**
 * Quick reminder about setUp() and tearDown():
 * static setUpBeforeClass() - Called before the first test of this test class is run
 * setUp() - Called before each test
 * tearDown() - Called after each test
 * static tearDownAfterClass() - Called after the last test of this test class is run.
 *
 * Kernel and client are initialized in setUpBeforeClass() so fixtures can be loaded.
 * They need kernel and after initializing kernel you cannot initialize client.
 * Fixtures are also loaded in setUpBeforeClass() and they are shared and affected by any executed test.
 *
 * Kernel and client is teared down in tearDown().
 * Client is re-created when needed in getHttpClient().
 *
 * Please remember that any test in one test case can be executed in any order.
 */
abstract class AbstractTestCase extends WebTestCase
{
    use AssertionsTrait;
    use AuthenticationTrait;
    use DataMixerTrait;
    use HttpClientProxyTrait;
    use RequestProxyTrait;
    use ServiceProxyTrait;
    use MockFeatureTrait;

    public const SMOKE = 'SMOKE';

    protected ?ApiClient $apiClient = null;
    protected bool $mock = true;

    /**
     * Any changes to database will be rolled back after each test case.
     * This also applies when using a provider as each dataset is a separate test case.
     */
    public static function useKeepStaticConnections(): bool
    {
        return true;
    }

    /**
     * Read more about mock() in development/tests/Mock usage.md.
     */
    public function mock(): void
    {
    }

    /**
     * Remove audit logs after fixtures are loaded.
     */
    public static function removeAuditLogsAfterFixtures(): bool
    {
        return false;
    }

    /**
     * Use following structure for DX friendly and readable fixtures:.
     *
     * use App\DataFixtures as ProdFixtures;
     * use Tests\DataFixtures as TestFixtures;
     *
     * return [
     *    ProdFixtures\ConfigurationFixtures::class,
     *    TestFixtures\Configuration\DisablePasswordRequirementsFixtures::class,
     * ];
     *
     * Groups can also be defined "Symfony" way i.e. ['prod'].
     *
     * @return array<string> List of fixtures to load
     */
    public static function getFixtureGroups(): array
    {
        return [];
    }

    public static function setUpBeforeClass(): void
    {
        $previousErrorReporting = error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        // Do not rollback changes to database yet. Let the fixtures load. Enable when needed after fixtures are loaded.
        \DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver::setKeepStaticConnections(false);

        static::createClient();

        $fixtureGroups = static::getFixtureGroups();
        if (count($fixtureGroups) > 0) {
            static::loadFixtures($fixtureGroups);
        }

        if (static::removeAuditLogsAfterFixtures()) {
            $connection = static::getContainer()->get('doctrine')->getManager()->getConnection();
            $statement = $connection->prepare('DELETE FROM audit_log');
            $statement->execute();
        }

        if (static::useKeepStaticConnections()) {
            \DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver::setKeepStaticConnections(true);
        }

        // Need to completely reboot kernel to reload everything after loading fixtures and allow DAMA to attach itself to events correctly when setKeepStaticConnections is changed
        static::ensureKernelShutdown();
        static::createClient();

        error_reporting($previousErrorReporting);
    }

    public function setUp(): void
    {
        parent::setUp();

        if (!static::$booted) {
            // Create client when kernel is not booted to avoid booting up kernel without client
            // This can be caused by executing static::getContainer() which used by getService() which is used by getRepository()
            // Common example is running getRepository() before loginX() or request()
            static::createClient();
        }
    }

    public function enableMock(): void
    {
        $this->mock = true;
    }

    public function disableMock(): void
    {
        $this->mock = false;
    }

    /**
     * Is this class a smoke test class? getProviderNamedData() will filter out static::FULL provider data.
     *
     * Use Tests\Utilities\Smoke\SmokeTrait to quickly mark as class as smoke test class.
     */
    public static function isSmoke(): bool
    {
        return false;
    }

    public static function loadFixtures(array $fixtureGroups): void
    {
        $application = static::getApplication();
        $application->run(new ArrayInput([
            'command' => 'doctrine:fixtures:load',
            '--no-interaction' => true,
            '--group' => $fixtureGroups,
            // Use quite mode for readability
            '--quiet' => true,
        ]));
    }

    /**
     * @param callable $nameCallback ($row, $key) => string
     */
    public static function getProviderNamedData(iterable $data, callable $nameCallback): iterable
    {
        $namedData = [];

        foreach ($data as $key => $row) {
            if (static::isSmoke()) {
                if (!isset($row[0]) || $row[0] !== static::SMOKE) {
                    continue;
                }
            }

            if (isset($row[0]) && $row[0] === static::SMOKE) {
                array_shift($row);
            }

            $name = $nameCallback($row, $key);
            if (array_key_exists($name, $namedData)) {
                throw new \Exception('Name "'.$name.'" already exists (key = '.$key.'). Please adjust $nameCallback to return unique name for each row');
            }

            $namedData[$name] = $row;
        }

        return $namedData;
    }

    /**
     * Provider data row keys need to match name of parameters in test function or you named keys can be avoided. This function removes named keys.
     */
    public static function removeDataRowKeys(iterable $data): iterable
    {
        return array_map(fn ($values) => array_values($values), $data);
    }

    public function getApiClient(): ApiClient
    {
        if (null === $this->apiClient) {
            $this->apiClient = new ApiClient($this);
        }

        return $this->apiClient;
    }

    public function request(string $method, string $uri, array $parameters = [], array $files = [], array $server = [], ?string $content = null, bool $changeHistory = true): Crawler
    {
        $this->doMock();
        $requestResult = $this->getHttpClient()->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        if ($this->isDigestAuthorization()) {
            $this->includeDigestAuthorization();

            $this->doMock();
            $requestResult = $this->getHttpClient()->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

            // Digest authorization has to be calculated for every request. Clear to keep it clean.
            $this->clearDigestAuthorization();
        }

        return $requestResult;
    }

    public function jsonRequest(string $method, string $uri, array $parameters = [], array $server = [], bool $changeHistory = true): Crawler
    {
        $this->doMock();
        $requestResult = $this->getHttpClient()->jsonRequest($method, $uri, $parameters, $server, $changeHistory);

        if ($this->isDigestAuthorization()) {
            $this->includeDigestAuthorization();

            $this->doMock();
            $requestResult = $this->getHttpClient()->jsonRequest($method, $uri, $parameters, $server, $changeHistory);

            // Digest authorization has to be calculated for every request. Clear to keep it clean.
            $this->clearDigestAuthorization();
        }

        return $requestResult;
    }

    /**
     * Read more about mocking in development/tests/Mock usage.md.
     */
    protected function doMock(): void
    {
        if (false === $this->mock) {
            return;
        }

        $this->getHttpClient()->getKernel()->shutdown();
        $this->getHttpClient()->getKernel()->boot();
        $this->getHttpClient()->disableReboot();

        $this->mock();
    }

    /**
     * Mark every row as smoke that $callback($row, $key) returns true.
     */
    public static function markSmoke(array $data, callable $callback): array
    {
        return array_map(function ($row, $key) use ($callback) {
            if ($callback($row, $key)) {
                return [static::SMOKE, ...$row];
            }

            return $row;
        }, $data, array_keys($data));
    }

    /**
     * Count number of data marked as smoke.
     */
    public static function getSmokeCount(array $data): int
    {
        return array_reduce($data, function ($count, $row) {
            if (isset($row[0]) && $row[0] === static::SMOKE) {
                return $count + 1;
            }

            return $count;
        }, 0);
    }
}
