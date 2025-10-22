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

export const humanizeEncryptedValues = (
    values: string,
    encryptedUnchagedValueReplace: string,
    encryptedChangedValueReplace: string
): string => {
    // App\Model\AuditableInterface::VALUE_ENCRYPTED_UNCHANGED
    const valueEncryptedUnchanged = "d460d32e-0028-11ef-92c8-0242ac120002";
    // App\Model\AuditableInterface::VALUE_ENCRYPTED_CHANGED
    const valueEncryptedChanged = "33b8afee-6b74-4742-a2ae-a47fdfb1ab57";

    return values
        .replace(valueEncryptedUnchanged, encryptedUnchagedValueReplace)
        .replace(valueEncryptedChanged, encryptedChangedValueReplace);
};
