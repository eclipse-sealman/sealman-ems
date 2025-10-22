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

namespace App\Model;

/**
 * Interface indicates whether an entity should be taken into account when creating AuditLogs by App\EventListener\AuditableListener.
 *
 * Entity fields (properties) that should be auditable (logged) need to have a serialization group AuditableInterface::GROUP
 * Example: #[Groups(['device:public', AuditableInterface::GROUP])].
 *
 * Entity fields (properties) that are encrypted and should be auditable (logged)
 * need to have a serialization group AuditableInterface::ENCRYPTED_GROUP
 * Example: #[Groups(['device:public', AuditableInterface::ENCRYPTED_GROUP])].
 */
interface AuditableInterface
{
    /**
     * Serialization group used for auditable properties.
     */
    public const GROUP = '__auditable__';

    /**
     * Serialization group used for encrypted auditable properties.
     */
    public const ENCRYPTED_GROUP = '__auditable:encrypted__';

    /**
     * UUID value meaning that encrypted value has not changed to minimize possibility of confusing value with non-encrypted one. Hardcoded in frontend.
     */
    public const VALUE_ENCRYPTED_UNCHANGED = 'd460d32e-0028-11ef-92c8-0242ac120002';

    /**
     * UUID value meaning that encrypted value has changed to minimize possibility of confusing value with non-encrypted one. Hardcoded in frontend.
     */
    public const VALUE_ENCRYPTED_CHANGED = '33b8afee-6b74-4742-a2ae-a47fdfb1ab57';
}
