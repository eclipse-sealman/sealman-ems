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
 * Migration for issue #17 Minor update to v0.2.0 (support Elliptic Curve and EdDSA keys in SCEP)
 */
final class Version20260514051144 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE certificate_type CHANGE scep_key_length scep_key_type VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE certificate_type SET scep_key_type = "RSA4096" WHERE scep_key_type = "4096"');
        $this->addSql('UPDATE certificate_type SET scep_key_type = "RSA2048" WHERE scep_key_type = "2048"');
    }
}
