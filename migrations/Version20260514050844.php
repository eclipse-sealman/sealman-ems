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
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * ! Migration file MUST **NOT** BE dependent on application code.
 *
 * @see development/Doctrine migrations.md for more details and example queries
 *
 * Migration from version 0.1.1
 * Migration for issue #17 Minor update to v0.2.0 (upgrade Symfony to 7.4 with all composer dependencies)
 */
final class Version20260514050844 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT `id`, `connection_firewall_rules` FROM `vpn_connection`');
        foreach ($rows as $row) {
            $id = $row['id'];
            $rulesSerialized = $row['connection_firewall_rules'];

            $rules = unserialize($rulesSerialized);
            $rulesJson = json_encode($rules);

            $this->connection->executeStatement(
                'UPDATE `vpn_connection` SET `connection_firewall_rules` = ? WHERE `id` = ?',
                [$rulesJson, $id],
                [Types::TEXT, Types::INTEGER]
            );
        }

        $this->addSql('ALTER TABLE vpn_connection CHANGE connection_firewall_rules connection_firewall_rules JSON DEFAULT NULL');
    }
}
