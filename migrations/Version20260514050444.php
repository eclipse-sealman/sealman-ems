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
 * Migration for issue #17 Minor update to v0.2.0 (hardware files)
 */
final class Version20260514050444 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE device_type_hardware (id INT AUTO_INCREMENT NOT NULL, device_type_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, hardware_version VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT NULL, INDEX IDX_C2C059B94FFA550E (device_type_id), INDEX IDX_C2C059B9B03A8386 (created_by_id), INDEX IDX_C2C059B9896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE firmware_hardware_file (id INT AUTO_INCREMENT NOT NULL, hardware_id INT NOT NULL, firmware_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, source_type VARCHAR(255) NOT NULL, md5 VARCHAR(255) NOT NULL, filename VARCHAR(255) DEFAULT NULL, filepath VARCHAR(255) DEFAULT NULL, external_url VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT NULL, INDEX IDX_B9D609A2C9CC762B (hardware_id), INDEX IDX_B9D609A2972206F2 (firmware_id), INDEX IDX_B9D609A2B03A8386 (created_by_id), INDEX IDX_B9D609A2896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_type_hardware ADD CONSTRAINT FK_C2C059B94FFA550E FOREIGN KEY (device_type_id) REFERENCES device_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_type_hardware ADD CONSTRAINT FK_C2C059B9B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE device_type_hardware ADD CONSTRAINT FK_C2C059B9896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE firmware_hardware_file ADD CONSTRAINT FK_B9D609A2C9CC762B FOREIGN KEY (hardware_id) REFERENCES device_type_hardware (id)');
        $this->addSql('ALTER TABLE firmware_hardware_file ADD CONSTRAINT FK_B9D609A2972206F2 FOREIGN KEY (firmware_id) REFERENCES firmware (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE firmware_hardware_file ADD CONSTRAINT FK_B9D609A2B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE firmware_hardware_file ADD CONSTRAINT FK_B9D609A2896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE device_type ADD has_hardwares TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE firmware ADD enable_hardware_files TINYINT(1) NOT NULL, CHANGE source_type source_type VARCHAR(255) DEFAULT NULL, CHANGE md5 md5 VARCHAR(255) DEFAULT NULL');
    }
}
