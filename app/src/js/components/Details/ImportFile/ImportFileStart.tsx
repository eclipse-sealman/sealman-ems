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
import { ButtonDialogFormAlertFieldset, ButtonDialogFormAlertFieldsetProps, Checkbox, Optional } from "@arteneo/forge";
import { StartOutlined } from "@mui/icons-material";
import { useNavigate } from "react-router-dom";
import { ImportFileInterface } from "~app/entities/ImportFile/definitions";

interface ImportFileStartProps extends Optional<ButtonDialogFormAlertFieldsetProps, "dialogProps"> {
    importFile: ImportFileInterface;
}

const ImportFileStart = ({ importFile, ...props }: ImportFileStartProps) => {
    const navigate = useNavigate();

    return (
        <ButtonDialogFormAlertFieldset
            {...{
                label: "importFile.start.action",
                variant: "contained",
                color: "success",
                endIcon: <StartOutlined />,
                sx: {
                    borderWidth: 1,
                    borderStyle: "solid",
                    borderColor: "grey.300",
                },
                dialogProps: {
                    title: "importFile.start.title",
                    label: "importFile.start.label",
                    formProps: {
                        fields: {
                            applyVariables: <Checkbox />,
                            applyAccessTags: <Checkbox />,
                        },
                        endpoint: "/importfile/" + importFile.id,
                        onSubmitSuccess: () => {
                            navigate("/importfile/process/" + importFile.id);
                        },
                    },
                    ...props.dialogProps,
                },
            }}
        />
    );
};

export default ImportFileStart;
export { ImportFileStartProps };
