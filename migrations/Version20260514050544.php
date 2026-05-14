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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ! Migration file MUST **NOT** BE dependent on application code.
 *
 * @see development/Doctrine migrations.md for more details and example queries
 *
 * Migration from version 0.1.1
 * Migration for issue #17 Minor update to v0.2.0 (firmware update paths)
 */
final class Version20260514050544 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE firmware ADD required_firmware_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE firmware ADD CONSTRAINT FK_D5ECD7C4D7616A62 FOREIGN KEY (required_firmware_id) REFERENCES firmware (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D5ECD7C4D7616A62 ON firmware (required_firmware_id)');

        $this->addSql('ALTER TABLE device_type ADD firmware_schema1 VARCHAR(255) NOT NULL, ADD firmware_schema2 VARCHAR(255) NOT NULL, ADD firmware_schema3 VARCHAR(255) NOT NULL');

        $this->addSql(
            'ALTER TABLE device_type ADD allow_downgrade_firmware1 TINYINT(1) NOT NULL, ADD allow_downgrade_firmware2 TINYINT(1) NOT NULL, ADD allow_downgrade_firmware3 TINYINT(1) NOT NULL'
        );

        $this->addSql(
            'UPDATE device_type SET firmware_schema1 = "anySchema", firmware_schema2 = "anySchema", firmware_schema3 = "anySchema"'
        );
    }
}
