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
import { useUtils } from "@mui/x-date-pickers/internals/hooks/useUtils";
import { getIn } from "formik";
import { ColumnInterface } from "@arteneo/forge";
import { addDays, format } from "date-fns";
import { Box, SvgIconProps, Tooltip } from "@mui/material";
import { ManageAccountsOutlined, RefreshOutlined, SyncOutlined, SyncProblemOutlined } from "@mui/icons-material";
import { useTranslation } from "react-i18next";
import { isGenerate, isRenew } from "~app/entities/DeviceTypeSecret/enums";

const RenewedAtColumn = ({ result, columnName }: ColumnInterface) => {
    const { t } = useTranslation();
    const utils = useUtils();

    if (typeof columnName === "undefined") {
        throw new Error("RenewedAtColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("RenewedAtColumn component: Missing required result prop");
    }

    const renewedAtString = getIn(result, "renewedAt", null);
    const secretValueBehaviour = getIn(result, "deviceTypeSecret.secretValueBehaviour");

    const renewedAt = renewedAtString ? utils.date(renewedAtString) : null;
    if (renewedAt == "Invalid Date") {
        console.warn("RenewedAtColumn component: Could not parse date");
        return null;
    }

    const iconProps: SvgIconProps = {
        color: "info",
        fontSize: "small",
        sx: { cursor: "help" },
    };

    if (renewedAt === null) {
        if (isGenerate(secretValueBehaviour)) {
            return (
                <Tooltip title={t("deviceSecret.tooltip.renewedAt.generate")}>
                    <RefreshOutlined {...iconProps} />
                </Tooltip>
            );
        }

        return null;
    }

    const now = utils.date() as Date;

    let manualEditIcon = null;
    const manualEdit = getIn(result, "deviceTypeSecret.manualEdit", false);
    const manualEditRenewReminder = getIn(result, "deviceTypeSecret.manualEditRenewReminder", false);
    const manualEditRenewReminderAfterDays = getIn(result, "deviceTypeSecret.manualEditRenewReminderAfterDays", null);

    if (manualEdit && manualEditRenewReminder && manualEditRenewReminderAfterDays !== null) {
        const manualEditReminderAt = addDays(renewedAt as Date, manualEditRenewReminderAfterDays);
        if (now > manualEditReminderAt) {
            manualEditIcon = (
                <Tooltip title={t("deviceSecret.tooltip.renewedAt.manualExpired")}>
                    <ManageAccountsOutlined {...{ ...iconProps, color: "warning" }} />
                </Tooltip>
            );
        }
    }

    let renewIcon = null;
    const renewedAtFormatted = format(renewedAt as Date, "dd-MM-yyyy HH:mm:ss");
    const secretValueRenewAfterDays = getIn(result, "deviceTypeSecret.secretValueRenewAfterDays", null);
    const secretUseAsVariable = getIn(result, "deviceTypeSecret.useAsVariable", false);
    const forceRenewal = getIn(result, "forceRenewal", null);

    if (secretUseAsVariable) {
        if ((isGenerate(secretValueBehaviour) || isRenew(secretValueBehaviour)) && forceRenewal) {
            // Will be renewed on next communication, forceRenewal
            renewIcon = (
                <Tooltip title={t("deviceSecret.tooltip.renewedAt.forceRenewal")}>
                    <SyncProblemOutlined {...{ ...iconProps, color: "error" }} />
                </Tooltip>
            );
        }

        if (renewIcon === null && isRenew(secretValueBehaviour)) {
            if (secretValueRenewAfterDays !== null) {
                const renewAt = addDays(renewedAt as Date, secretValueRenewAfterDays);
                const renewAtFormatted = format(renewAt as Date, "dd-MM-yyyy HH:mm:ss");
                if (now > renewAt) {
                    // Will be renewed on next communication, expired
                    renewIcon = (
                        <Tooltip
                            title={t("deviceSecret.tooltip.renewedAt.renewExpired", {
                                renewAt: renewAtFormatted,
                            })}
                        >
                            <SyncProblemOutlined {...{ ...iconProps, color: "warning" }} />
                        </Tooltip>
                    );
                } else {
                    // Will be renewed on next communication, not expired
                    renewIcon = (
                        <Tooltip
                            title={t("deviceSecret.tooltip.renewedAt.renew", {
                                renewAt: renewAtFormatted,
                            })}
                        >
                            <SyncOutlined {...iconProps} />
                        </Tooltip>
                    );
                }
            }
        }
    }

    return (
        <Box display="flex" gap={0.5} alignItems="center">
            {renewedAtFormatted}
            {renewIcon}
            {manualEditIcon}
        </Box>
    );
};

export default RenewedAtColumn;
export { ColumnInterface as RenewedAtColumnProps };
