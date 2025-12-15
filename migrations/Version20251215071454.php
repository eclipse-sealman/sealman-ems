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
 * Migration from version 0.1.0
 * Migration for issue #13 Migration produces an error due to lack of migrations
 */
final class Version20251215071454 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Fake SQL to avoid migration reporting an error
        $this->addSql('SELECT 1');
    }
}
