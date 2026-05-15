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
 * Migration for issue #17 Minor update to v0.2.0 (upgrade Symfony to 7.4 with all composer dependencies)
 */
final class Version20260514050744 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log_change DROP FOREIGN KEY `FK_2873B57D4D059435`');
        $this->addSql('DROP INDEX UNIQ_2873B57D4D059435 ON audit_log_change');
        $this->addSql('ALTER TABLE audit_log_change DROP audit_log_change_values_id');
        $this->addSql('ALTER TABLE communication_log DROP FOREIGN KEY `FK_ED416163C1C4EB16`');
        $this->addSql('DROP INDEX UNIQ_ED416163C1C4EB16 ON communication_log');
        $this->addSql('ALTER TABLE communication_log DROP communication_log_content_id');
        $this->addSql('ALTER TABLE config_log DROP FOREIGN KEY `FK_907E09CA4F19A01D`');
        $this->addSql('DROP INDEX UNIQ_907E09CA4F19A01D ON config_log');
        $this->addSql('ALTER TABLE config_log DROP config_log_content_id');
        $this->addSql('ALTER TABLE diagnose_log DROP FOREIGN KEY `FK_FAD41D72B4B5AA48`');
        $this->addSql('DROP INDEX UNIQ_FAD41D72B4B5AA48 ON diagnose_log');
        $this->addSql('ALTER TABLE diagnose_log DROP diagnose_log_content_id');
    }
}
