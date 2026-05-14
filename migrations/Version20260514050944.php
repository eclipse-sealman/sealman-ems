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
 * Migration for issue #17 Minor update to v0.2.0 (custom data support)
 */
final class Version20260514050944 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Created custom data tables
        $this->addSql('CREATE TABLE communication_log_custom_data (id INT AUTO_INCREMENT NOT NULL, communication_log_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, value LONGTEXT NOT NULL, name VARCHAR(255) NOT NULL, variable_enabled TINYINT(1) NOT NULL, type VARCHAR(255) DEFAULT NULL, variable_name VARCHAR(255) DEFAULT NULL, INDEX IDX_86AF744251ED9B44 (communication_log_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_custom_data (id INT AUTO_INCREMENT NOT NULL, device_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, value LONGTEXT NOT NULL, name VARCHAR(255) NOT NULL, variable_enabled TINYINT(1) NOT NULL, type VARCHAR(255) DEFAULT NULL, variable_name VARCHAR(255) DEFAULT NULL, INDEX IDX_DE056F5894A4C7D4 (device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_type_custom_data_mapping (id INT AUTO_INCREMENT NOT NULL, device_type_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, path VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, variable_enabled TINYINT(1) NOT NULL, type VARCHAR(255) DEFAULT NULL, variable_name VARCHAR(255) DEFAULT NULL, INDEX IDX_D7FCB4E74FFA550E (device_type_id), INDEX IDX_D7FCB4E7B03A8386 (created_by_id), INDEX IDX_D7FCB4E7896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE communication_log_custom_data ADD CONSTRAINT FK_86AF744251ED9B44 FOREIGN KEY (communication_log_id) REFERENCES communication_log (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_custom_data ADD CONSTRAINT FK_DE056F5894A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_type_custom_data_mapping ADD CONSTRAINT FK_D7FCB4E74FFA550E FOREIGN KEY (device_type_id) REFERENCES device_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_type_custom_data_mapping ADD CONSTRAINT FK_D7FCB4E7B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE device_type_custom_data_mapping ADD CONSTRAINT FK_D7FCB4E7896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');

        // Added has_custom_data column to device_type
        $this->addSql('ALTER TABLE device_type ADD has_custom_data TINYINT(1) NOT NULL');
    }
}
