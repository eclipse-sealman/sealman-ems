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

namespace Tests\Suites\Unit\Serializer;

use App\Entity\AuditLogChange;
use App\Enum\LogLevel;
use App\Model\LogModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Tests serialization of \DateTime object which should always be converted to UTC and formatted using ISO 8601.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('Unit')] // To be used in CI parallel tests
class DateTimeSerializeTest extends KernelTestCase
{
    public static function dateTimeProvider(): array
    {
        $dateTimes = [
            '2024-01-01 00:00:00' => new \DateTime('2024-01-01 00:00:00'),
            '2021-05-23 19:23:11' => new \DateTime('2021-05-23 19:23:11'),
            'Yesterday' => new \DateTime('-1 day'),
            'Now' => new \DateTime(),
            'Tomorrow' => new \DateTime('+1 day'),
            '2031-11-18 02:22:33' => new \DateTime('2031-11-18 02:22:33'),
            '2041-12-31 23:59:59' => new \DateTime('2041-12-31 23:59:59'),
        ];

        $timezones = [
            'unspecified' => null,
            'UTC' => new \DateTimeZone('UTC'),
            'America/Araguaina' => new \DateTimeZone('Antarctica/Casey'),
            'Europe/Berlin' => new \DateTimeZone('Europe/Berlin'),
            'America/New_York' => new \DateTimeZone('America/New_York'),
            'Asia/Tokyo' => new \DateTimeZone('Asia/Tokyo'),
        ];

        $dateSets = [];

        foreach ($dateTimes as $dateTimeName => $dateTime) {
            foreach ($timezones as $timezoneName => $timezone) {
                $data = clone $dateTime;

                if (null !== $timezone) {
                    $data->setTimezone(clone $timezone);
                }

                $dateSets[$dateTimeName.' with '.$timezoneName] = [$data];
            }
        }

        return $dateSets;
    }

    #[DataProvider('dateTimeProvider')]
    public function testDateTimeSerialization(\DateTime $dateTime): void
    {
        $serializer = $this->getSerializer();

        $serialized = $serializer->serialize($dateTime, 'json');
        $decoded = json_decode($serialized, true);
        $actual = $decoded;

        $this->assertEquals($this->getExpectedDateTime($dateTime), $actual);
    }

    #[DataProvider('dateTimeProvider')]
    public function testModelDateTimeSerialization(\DateTime $dateTime): void
    {
        $serializer = $this->getSerializer();

        $model = new LogModel(LogLevel::INFO, 'message', [], $dateTime);
        $serialized = $serializer->serialize($model, 'json');
        $decoded = json_decode($serialized, true);
        $actual = $decoded['createdAt'] ?? null;

        $this->assertEquals($this->getExpectedDateTime($dateTime), $actual);
    }

    #[DataProvider('dateTimeProvider')]
    public function testEntityDateTimeSerialization(\DateTime $dateTime): void
    {
        $serializer = $this->getSerializer();

        $entity = new AuditLogChange();
        $entity->setCreatedAt($dateTime);
        $serialized = $serializer->serialize($entity, 'json');
        $decoded = json_decode($serialized, true);
        $actual = $decoded['createdAt'] ?? null;

        $this->assertEquals($this->getExpectedDateTime($dateTime), $actual);
    }

    protected function getSerializer(): SerializerInterface
    {
        return $this->getContainer()->get(SerializerInterface::class);
    }

    protected function getExpectedDateTime(\DateTime $dateTime): string
    {
        $expectedDateTime = clone $dateTime;
        $expectedDateTime->setTimezone(new \DateTimeZone('UTC'));

        return $expectedDateTime->format('c');
    }
}
