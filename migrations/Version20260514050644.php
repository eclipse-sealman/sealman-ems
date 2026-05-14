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
 * Migration for issue #17 Minor update to v0.2.0 (device variables type casting)
 */
final class Version20260514050644 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE device_variable ADD variable_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE import_file_row_variable ADD variable_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE template_version_variable ADD variable_type VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE device_variable SET variable_type = \'string\'');
        $this->addSql('UPDATE import_file_row_variable SET variable_type = \'string\'');
        $this->addSql('UPDATE template_version_variable SET variable_type = \'string\'');
    }
}
