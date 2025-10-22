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
import { Chip, ChipProps, Tooltip } from "@mui/material";
import { HelpOutline } from "@mui/icons-material";
import { getIn } from "formik";
import { ColumnPathInterface } from "@arteneo/forge";
import { useTranslation } from "react-i18next";

type EnabledColumnProps = ColumnPathInterface;

const EnabledColumn = ({ result, columnName, path }: EnabledColumnProps) => {
    const { t } = useTranslation();
    const utils = useUtils();

    if (typeof columnName === "undefined") {
        throw new Error("EnabledColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("EnabledColumn component: Missing required result prop");
    }

    const user = path ? getIn(result, path) : result;
    if (!user) {
        return null;
    }

    const chipProps: ChipProps = {
        size: "small",
    };

    let tooltip: undefined | string = undefined;

    let enabledExpireAt = "unknown";
    if (user.enabledExpireAt) {
        const dateEnabledExpireAt = utils.date(user.enabledExpireAt);

        if (dateEnabledExpireAt != "Invalid Date") {
            enabledExpireAt = utils.format(dateEnabledExpireAt, "fullDateTime24h");
        }
    }

    if (user.enabled) {
        // isEnabled is a flag that also takes into consideration enabledExpireAt
        if (user.isEnabled) {
            chipProps.color = "success";
            chipProps.label = t("enabledColumn.yes");

            if (user.enabledExpireAt) {
                tooltip = t("enabledColumn.tooltip.enabledUntil", { enabledExpireAt });
                chipProps.icon = <HelpOutline />;
            }
        } else {
            tooltip = t("enabledColumn.tooltip.enabledExpired", { enabledExpireAt });

            chipProps.color = "error";
            chipProps.label = t("enabledColumn.no");
            chipProps.icon = <HelpOutline />;
        }
    } else {
        chipProps.color = "error";
        chipProps.label = t("enabledColumn.no");
    }

    const chip = <Chip {...chipProps} />;

    if (tooltip) {
        return <Tooltip {...{ title: tooltip }}>{chip}</Tooltip>;
    }

    return chip;
};

export default EnabledColumn;
export { EnabledColumnProps };
