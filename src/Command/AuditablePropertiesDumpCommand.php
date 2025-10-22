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

namespace App\Command;

use App\Model\AuditableInterface;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

class AuditablePropertiesDumpCommand extends Command
{
    use EntityManagerTrait;

    public function __construct(
        private string $projectDir,
        private ClassMetadataFactoryInterface $serializerMetadata,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:auditable:dump-properties');
        $this->setDescription('Dumping list of properties that are auditable as CSV to "'.$this->getCsvFile().'"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $auditables = [];

        $allMetadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($allMetadata as $metadata) {
            $entityClass = $metadata->getName();
            if (!$this->serializerMetadata->hasMetadataFor($entityClass)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($entityClass);

            $classMetadata = $this->serializerMetadata->getMetadataFor($entityClass);
            foreach ($classMetadata->getAttributesMetadata() as $attributeMetadata) {
                $groups = $attributeMetadata->getGroups();

                $auditable = in_array(AuditableInterface::GROUP, $groups) || in_array(AuditableInterface::ENCRYPTED_GROUP, $groups) ? true : false;
                if (!$auditable) {
                    continue;
                }

                $encrypted = in_array(AuditableInterface::ENCRYPTED_GROUP, $groups) ? true : false;

                $auditable = [];
                $auditable[] = $reflectionClass->getShortName(); // Entity name
                $auditable[] = $attributeMetadata->getName(); // Property name
                $auditable[] = $encrypted ? 'Yes' : 'No'; // Encrypted

                $auditables[] = $auditable;
            }
        }

        $fp = fopen($this->getCsvFile(), 'w');

        fputcsv($fp, [
            'Entity name',
            'Property name',
            'Encrypted',
        ], escape: '\\');

        foreach ($auditables as $auditable) {
            fputcsv($fp, $auditable, escape: '\\');
        }

        fclose($fp);

        return Command::SUCCESS;
    }

    protected function getCsvFile(): string
    {
        return $this->projectDir.'/auditable_properties.csv';
    }
}
