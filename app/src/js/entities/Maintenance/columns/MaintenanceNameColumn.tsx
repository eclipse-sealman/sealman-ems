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

import React from "react";
import { ColumnPathInterface } from "@arteneo/forge";
import { useTranslation } from "react-i18next";
import { getIn } from "formik";

const MaintenanceNameColumn = ({ result, columnName, path }: ColumnPathInterface) => {
    if (typeof columnName === "undefined") {
        throw new Error("MaintenanceNameColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("MaintenanceNameColumn component: Missing required result prop");
    }

    const { t } = useTranslation();
    const value = path ? getIn(result, path) : result;

    const type = value?.type;
    if (!type) {
        return null;
    }

    const scheduleInformation = value?.scheduledBackup ? (
        <>
            <br />
            <small>
                {typeof value?.maintenanceSchedule !== "undefined" ? (
                    <>
                        {t("maintenanceNameColumn.maintenanceSchedule", {
                            name: value?.maintenanceSchedule?.name,
                        })}
                    </>
                ) : (
                    t("maintenanceNameColumn.maintenanceScheduleDeleted")
                )}
            </small>
        </>
    ) : null;

    if (type === "backupForUpdate") {
        return <>{t("maintenanceNameColumn.backupForUpdate")}</>;
    }

    const hasPassword = value?.hasPassword;
    if (type === "backup") {
        let subject = "";
        if (value?.backupDatabase && value?.backupFilestorage) {
            subject = "databaseFilestorage";
        } else if (value?.backupDatabase) {
            subject = "database";
        } else if (value?.backupFilestorage) {
            subject = "filestorage";
        }

        const label = hasPassword ? "backupEncrypted" : "backup";

        return (
            <>
                {t("maintenanceNameColumn." + label, { subject: t("maintenanceNameColumn.subject." + subject) })}
                {scheduleInformation}
            </>
        );
    }

    if (type === "restore") {
        let subject = "";
        if (value?.restoreDatabase && value?.restoreFilestorage) {
            subject = "databaseFilestorage";
        } else if (value?.restoreDatabase) {
            subject = "database";
        } else if (value?.restoreFilestorage) {
            subject = "filestorage";
        }

        const label = hasPassword ? "restoreEncrypted" : "restore";

        return <>{t("maintenanceNameColumn." + label, { subject: t("maintenanceNameColumn.subject." + subject) })}</>;
    }

    return null;
};

export default MaintenanceNameColumn;
export { ColumnPathInterface as MaintenanceNameColumnProps };
