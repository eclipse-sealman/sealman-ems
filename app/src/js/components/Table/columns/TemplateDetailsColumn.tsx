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
import { getIn } from "formik";
import { ButtonDialogFormFieldset, ColumnInterface } from "@arteneo/forge";
import composeGetFields from "~app/entities/TemplateVersion/fields";
import { getReinstallInitialValues } from "~app/routes/TemplateVersionStagingEdit";
import { EditOutlined } from "@mui/icons-material";
import { useDetails } from "~app/contexts/Details";
import { useUser } from "~app/contexts/User";

const TemplateDetailsColumn = ({ result, columnName }: ColumnInterface) => {
    const { isAccessGranted } = useUser();

    const { reload } = useDetails();

    if (typeof columnName === "undefined") {
        throw new Error("TextColumn component: Missing required columnName prop");
    }

    if (typeof result === "undefined") {
        throw new Error("TextColumn component: Missing required result prop");
    }

    const template = getIn(result, columnName);
    if (!template) {
        return null;
    }

    let details = <>{template.representation}</>;
    const templateVersion = result.staging ? template.stagingTemplate : template.productionTemplate;
    if (!templateVersion) {
        return details;
    }

    details = (
        <>
            {details} ({templateVersion.representation})
        </>
    );

    if (result.staging) {
        const deviceType = result.deviceType;
        const getFields = composeGetFields(deviceType, !isAccessGranted({ admin: true }), template.id);
        const fields = getFields();

        details = (
            <>
                {details}{" "}
                {/* TODO Arek When initializing this form it does not show loader (form is initializing, not dialog). In case form stays in the system fix it */}
                <ButtonDialogFormFieldset
                    {...{
                        label: "action.edit",
                        size: "small",
                        color: "info",
                        variant: "contained",
                        startIcon: <EditOutlined />,
                        sx: { ml: 1, py: 0, px: 1.25, fontSize: 13, lineHeight: 1, minWidth: 40 },
                        dialogProps: {
                            title: "templateDetailsColumn.dialog.title",
                            formProps: {
                                initialValues: getReinstallInitialValues(deviceType),
                                initializeEndpoint: "/templateversion/" + templateVersion.id,
                                endpoint: "/templateversion/" + templateVersion.id,
                                fields,
                                onSubmitSuccess: (defaultOnSubmitSuccess) => {
                                    defaultOnSubmitSuccess();
                                    reload();
                                },
                            },
                            dialogProps: {
                                maxWidth: "lg",
                            },
                        },
                    }}
                />
            </>
        );
    }

    return <>{details}</>;
};

export default TemplateDetailsColumn;
export { ColumnInterface as TemplateDetailsColumnProps };
