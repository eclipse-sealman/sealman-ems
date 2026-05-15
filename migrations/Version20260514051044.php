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
 * Migration for issue #17 Minor update to v0.2.0 (device authentication using mTLS)
 */
final class Version20260514051044 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Added device_type_certificate_type_mtls_scep_authentication_id column to device_type
        $this->addSql('ALTER TABLE device_type ADD device_type_certificate_type_mtls_scep_authentication_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device_type ADD CONSTRAINT FK_5E782136A77E6B0 FOREIGN KEY (device_type_certificate_type_mtls_scep_authentication_id) REFERENCES certificate_type (id)');
        $this->addSql('CREATE INDEX IDX_5E782136A77E6B0 ON device_type (device_type_certificate_type_mtls_scep_authentication_id)');

        // Added role_device_mtls_scep_credential column to user
        $this->addSql('ALTER TABLE user ADD role_device_mtls_scep_credential TINYINT(1) NOT NULL');

        // Added role_device_mtls_credential column to user
        $this->addSql('ALTER TABLE user ADD role_device_mtls_credential TINYINT(1) NOT NULL');

        // Created device_mtls_authentication table and device_mtls_authentication_device_type junction table
        $this->addSql('CREATE TABLE device_mtls_authentication (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, certificate_ca LONGTEXT NOT NULL, certificate_ca_subject LONGTEXT DEFAULT NULL, certificate_ca_valid_to DATETIME DEFAULT NULL, crl_type VARCHAR(255) NOT NULL, certificate_crl LONGTEXT DEFAULT NULL, certificate_crl_url VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT NULL, INDEX IDX_57D30B60B03A8386 (created_by_id), INDEX IDX_57D30B60896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_mtls_authentication_device_type (device_mtls_authentication_id INT NOT NULL, device_type_id INT NOT NULL, INDEX IDX_F23CC354D2B854E2 (device_mtls_authentication_id), INDEX IDX_F23CC3544FFA550E (device_type_id), PRIMARY KEY(device_mtls_authentication_id, device_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_mtls_authentication ADD CONSTRAINT FK_57D30B60B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE device_mtls_authentication ADD CONSTRAINT FK_57D30B60896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE device_mtls_authentication_device_type ADD CONSTRAINT FK_F23CC354D2B854E2 FOREIGN KEY (device_mtls_authentication_id) REFERENCES device_mtls_authentication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE device_mtls_authentication_device_type ADD CONSTRAINT FK_F23CC3544FFA550E FOREIGN KEY (device_type_id) REFERENCES device_type (id) ON DELETE CASCADE');

        // Insert fixture users for device mTLS authentication
        $this->addSql("INSERT INTO user (username, password, salt, role_smartems, role_admin, role_vpn, role_vpn_endpoint_devices, role_device, role_device_secret_credential, role_device_x509_credential, role_system, role_legacy_api, disable_password_expire, failed_login_attempts, totp_enabled, radius_user, radius_user_all_devices_access, sso_user, vpn_connected, role_device_mtls_credential, role_device_mtls_scep_credential, enabled, too_many_failed_login_attempts, created_at) VALUES ('deviceMTlsCredential', 'deviceMTlsCredential', 'deviceMTlsCredential', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, NOW())");
        $this->addSql("INSERT INTO user (username, password, salt, role_smartems, role_admin, role_vpn, role_vpn_endpoint_devices, role_device, role_device_secret_credential, role_device_x509_credential, role_system, role_legacy_api, disable_password_expire, failed_login_attempts, totp_enabled, radius_user, radius_user_all_devices_access, sso_user, vpn_connected, role_device_mtls_credential, role_device_mtls_scep_credential, enabled, too_many_failed_login_attempts, created_at) VALUES ('deviceMTlsScepCredential', 'deviceMTlsScepCredential', 'deviceMTlsScepCredential', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, NOW())");
    }
}
