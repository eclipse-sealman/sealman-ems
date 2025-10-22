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
import { Alert, Box } from "@mui/material";
import { useTranslation } from "react-i18next";
import DisplaySurface from "~app/components/Common/DisplaySurface";
import TableStaging from "~app/components/Details/Template/TableStaging";
import TableProduction from "~app/components/Details/Template/TableProduction";
import { TemplateInterface } from "~app/entities/Template/definitions";
import Create from "~app/components/Table/toolbar/Create";
import TemplateVersionDisplay from "~app/components/Details/Template/TemplateVersionDisplay";
import SurfaceTitle from "~app/components/Common/SurfaceTitle";
import { DeviceTypeProvider } from "~app/contexts/DeviceType";

interface TemplateDetailsProps {
    template: TemplateInterface;
}

const TemplateDetails = ({ template }: TemplateDetailsProps) => {
    const { t } = useTranslation();

    const stagingTemplateRepresentation =
        template.stagingTemplate?.representation ?? t("templateDetails.stagingTemplate.notSelected");

    const productionTemplateRepresentation =
        template.productionTemplate?.representation ?? t("templateDetails.productionTemplate.notSelected");

    return (
        <DeviceTypeProvider deviceType={template.deviceType}>
            <Box
                {...{
                    sx: {
                        display: "grid",
                        alignItems: "flex-start",
                        gridTemplateColumns: { xs: "minmax(0, 1fr)", lg: "repeat(2, minmax(0,1fr))" },
                        gap: { xs: 2, lg: 4 },
                        mb: 2,
                    },
                }}
            >
                <DisplaySurface
                    {...{
                        title: "templateDetails.stagingTemplate.title",
                        titleVariables: { representation: stagingTemplateRepresentation },
                        chipLabel: "templateDetails.stagingTemplate.chip",
                    }}
                >
                    {typeof template.stagingTemplate !== "undefined" ? (
                        <TemplateVersionDisplay {...{ templateVersion: template.stagingTemplate }} />
                    ) : (
                        <Box {...{ sx: { display: "flex", mt: 3, justifyContent: "center" } }}>
                            <Create
                                {...{
                                    deny: template?.deny,
                                    denyKey: "createTemplateVersion",
                                    denyBehavior: "hide",
                                    to: "/template/details/" + template.id + "/version/create",
                                    fullWidth: true,
                                }}
                            />
                        </Box>
                    )}
                </DisplaySurface>
                <DisplaySurface
                    {...{
                        title: "templateDetails.productionTemplate.title",
                        titleVariables: {
                            representation: productionTemplateRepresentation,
                        },
                        chipLabel: "templateDetails.productionTemplate.chip",
                        chipProps: { color: "warning" },
                    }}
                >
                    {typeof template.productionTemplate !== "undefined" ? (
                        <TemplateVersionDisplay {...{ templateVersion: template.productionTemplate }} />
                    ) : (
                        <Alert {...{ severity: "info" }}>{t("templateDetails.productionTemplate.empty")}</Alert>
                    )}
                </DisplaySurface>
            </Box>

            <Box {...{ sx: { mb: 2 } }}>
                <SurfaceTitle {...{ title: "templateDetails.table.staging", disableBackButton: true }} />
                <TableStaging {...{ template }} />
            </Box>

            <SurfaceTitle {...{ title: "templateDetails.table.production", disableBackButton: true }} />
            <TableProduction {...{ template }} />
        </DeviceTypeProvider>
    );
};

export default TemplateDetails;
export { TemplateDetailsProps };
